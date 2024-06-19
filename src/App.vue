<script setup>
import Home from "@/components/Home.vue";
import {useTheme} from "vuetify";
import {onMounted, ref} from "vue";
import Presets from "@/components/Presets.vue";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import axios from "axios";
import IconCommunity from "@/components/icons/IconCommunity.vue";

const theme = useTheme();

const settings = ref(false);
const apiKey = ref("");
const newKey = ref("");
const noKey = ref(false);
const showKey = ref(false);
const snack = ref(false);
const snackText = ref("");
const displayCallLimit = ref(false);
const showAdvancedInfo = ref(false);
const tab = ref("");
const worker = ref(null);

const devices = ref([]);
const refreshing = ref(false);
const lastRefresh = ref(dayjs());
const currentTime = ref(dayjs());
const blockStateChange = ref(false);
const storageSuccess = ref(false);
const apiSuccess = ref(false);
const presets = ref([]);

const emit = defineEmits(["refresh", "setDeviceCapability", "powerSwitch", "savePreset", "deletePreset", "updatePreset"]);

const Http = Object.freeze({
  GET: "GET",
  POST: "POST",
});

const DeviceState = Object.freeze({
  ONLINE: "online",
  POWER: "powerSwitch",
  BRIGHTNESS: "brightness",
  COLOR_TEMP: "colorTemperatureK",
});

const Capability = Object.freeze({
  POWER: {type: "devices.capabilities.on_off", instance: "powerSwitch"},
  BRIGHTNESS: {type: "devices.capabilities.range", instance: "brightness"},
  COLOR_TEMP: {type: "devices.capabilities.color_setting", instance: "colorTemperatureK"},
  DIY: {type: "devices.capabilities.dynamic_scene", instance: "diyScene"},
  SCENE: {type: "devices.capabilities.dynamic_scene", instance: "lightScene"},
});

function toggleTheme() {
  theme.global.name.value = theme.global.current.value.dark ? "light" : "dark";
}

function isDarkTheme() {
  return theme.global.name.value === "dark";
}

function storageAvailable(type) {
  let storage;
  try {
    storage = window[type];
    const x = "__storage_test__";
    storage.setItem(x, x);
    storage.removeItem(x);
    return true;
  } catch (e) {
    return (
        e instanceof DOMException &&
        // everything except Firefox
        (e.code === 22 ||
            // Firefox
            e.code === 1014 ||
            // test name field too, because code might not be present
            // everything except Firefox
            e.name === "QuotaExceededError" ||
            // Firefox
            e.name === "NS_ERROR_DOM_QUOTA_REACHED") &&
        // acknowledge QuotaExceededError only if there's something already stored
        storage &&
        storage.length !== 0
    );
  }
}

function submitKey() {
  apiKey.value = newKey.value;
  if (storageAvailable("localStorage")) {
    localStorage.setItem("apiKey", apiKey.value);
  } else {
    document.cookie = "Govee-API-Key=" + apiKey.value;
  }
}

function showSnackbar(text) {
  snack.value = true;
  snackText.value = text;
}

onMounted(() => {
  if (storageAvailable("localStorage")) {
    apiKey.value = localStorage.getItem("apiKey");
  } else {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; Govee-API-Key=`);
    if (parts.length === 2) {
      apiKey.value = parts.pop().split(";").shift();
    }
  }

  worker.value = new Worker("requestsWorker.js");
  worker.value.addEventListener("message", handleWorkerResponse);

  if (storageAvailable("localStorage")) {
    apiKey.value = localStorage.getItem("apiKey");
    lastRefresh.value = dayjs(localStorage.getItem("lastRefresh")) ?? dayjs();
    presets.value = JSON.parse(localStorage.getItem("presets")) ?? [];

    let deviceStorage = localStorage.getItem("devices");
    if (deviceStorage !== undefined && deviceStorage !== null && deviceStorage !== "[]") {
      devices.value = JSON.parse(localStorage.getItem("devices"));
      for (const value of devices.value) {
        value.states[DeviceState.ONLINE] = null;
        setDeviceStates(apiKey.value, value);
      }
      devices.value.sort((a, b) => {
        return a.sku.localeCompare(b.sku) || a.deviceName.localeCompare(b.deviceName);
      });
      storageSuccess.value = true;
    } else {
      if (apiKey.value !== null) {
        console.log(apiKey.value);
        refreshDevices().then(() => apiSuccess.value = true);
      } else {
        noKey.value = true;
      }
    }
  } else {
    apiKey.value = getCookie("Govee-API-Key");
    if (apiKey.value !== null || apiKey.value !== "") {
      refreshDevices();
    } else {
      noKey.value = true;
    }
  }

  let timeUpdate = setInterval(() => currentTime.value = dayjs(), 60000);
});

async function refreshDevices() {
  devices.value = [];
  refreshing.value = true;
  try {
    const response = await httpRequest(apiKey.value, "https://openapi.api.govee.com/router/api/v1/user/devices", Http.GET, {});
    const data = response.data;
    //skeletons.value = data.data.length;

    const devicePromises = data.data.map(item => {
      return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          reject("Promise timed out.");
        }, 5000); // Set your desired timeout value.

        Promise.resolve(transformItemToDevice(item))
            .then((deviceObj) => postDeviceIfHasDiy(deviceObj))
            .then((result) => {
              clearTimeout(timeout);
              resolve(result);
            })
            .catch((error) => {
              clearTimeout(timeout);
              reject(error);
            });
      });
    });

    devices.value = await Promise.all(devicePromises);
    devices.value.sort((a, b) => a.sku.localeCompare(b.sku));
  } catch (error) {
    console.log("Error:", error);
  } finally {
    refreshing.value = false;
    lastRefresh.value = dayjs();
    currentTime.value = dayjs();
    localStorage.setItem("lastRefresh", lastRefresh.value);
  }
}

async function handleWorkerResponse(e) {
  const diyData = e.data.response;
  const deviceObj = e.data.deviceObj;

  deviceObj.diy = diyData.payload.capabilities[0].parameters.options;
  deviceObj.diy.sort((a, b) => a.name.localeCompare(b.name));
  deviceObj.diy.forEach(obj => obj["loading"] = false);

  deviceObj.states = await getDeviceStates(apiKey.value, deviceObj);

  devices.value.push(deviceObj);
  localStorage.setItem("devices", JSON.stringify(devices.value.sort((a, b) => {
    return a.sku.localeCompare(b.sku) || a.deviceName.localeCompare(b.deviceName);
  })));
}

async function getDeviceStates(apiKey, device) {
  const response = await httpRequest(apiKey, "https://openapi.api.govee.com/router/api/v1/device/state", Http.POST, {
    requestId: "uuid",
    payload: {
      sku: device.sku,
      device: device.addr,
    },
  });

  const data = response.data;
  const capabilities = data.payload.capabilities;

  const deviceStates = {};
  for (const capability of capabilities) {
    if (Object.values(DeviceState).includes(capability.instance)) {
      deviceStates[capability.instance] = {value: capability.state.value, loading: false};
    }
  }
  return deviceStates;
}

async function setDeviceStates(apiKey, device) {
  const response = await httpRequest(apiKey, "https://openapi.api.govee.com/router/api/v1/device/state", Http.POST, {
    requestId: "uuid",
    payload: {
      sku: device.sku,
      device: device.addr,
    },
  });
  const data = response.data;
  const capabilities = data.payload.capabilities;

  const deviceStates = {};
  for (const capability of capabilities) {
    if (Object.values(DeviceState).includes(capability.instance)) {
      deviceStates[capability.instance] = {value: capability.state.value, loading: false};
    }
  }
  device.states = deviceStates;
}

async function httpRequest(apiKey, url, method, data) {
  return axios({
    url: "https://gamerbah.com/govee/api/request.php",
    method: method,
    params: {"api_key": apiKey, "api_url": url},
    headers: {"Content-Type": "application/json"},
    data: data,
  });
}

async function setDeviceCapability(device, capability, instance, value) {
  instance.loading = true;
  blockStateChange.value = true;
  let response = await httpRequest(apiKey.value, "https://openapi.api.govee.com/router/api/v1/device/control", Http.POST, generateJSON(device, capability, value));

  if (response.data.code === 200) {
    instance.value = value;
    instance.loading = false;
    blockStateChange.value = false;
  }
}

function deviceHasDiy(capabilities) {
  if (capabilities !== null) {
    return capabilities.some(capability => capability.instance === "diyScene");
  }
}

async function postDeviceIfHasDiy(deviceObj) {
  if (deviceHasDiy(deviceObj.capabilities)) {
    const obj = {
      php: "https://gamerbah.com/govee/api/request.php",
      apiKey: apiKey.value,
      deviceObj: deviceObj,
      url: "https://openapi.api.govee.com/router/api/v1/device/diy-scenes"
    };

    // Wrap the postMessage call in a new Promise, resolve when the worker sends back the result, reject on error
    return new Promise((resolve, reject) => {
      // handle message event
      worker.value.onmessage = async (e) => {
        const diyData = e.data.response;
        deviceObj.diy = diyData.payload.capabilities[0].parameters.options;
        deviceObj.diy.sort((a, b) => a.name.localeCompare(b.name));
        deviceObj.diy.forEach(obj => obj["loading"] = false);
        deviceObj.states = await getDeviceStates(apiKey.value, deviceObj);
        resolve(deviceObj);   // resolve the promise with the updated deviceObj
      };

      // handle error event
      worker.value.onerror = (err) => {
        console.log("Worker error: ", err);
        reject(err);
      };

      worker.value.postMessage(JSON.stringify(obj));
    });
  }

  // if a device doesn't have DIY capability, return it as is
  return deviceObj;
}

function transformItemToDevice(item) {
  return {
    sku: item.sku,
    addr: item.device,
    deviceName: item.deviceName,
    type: item.type,
    capabilities: item.capabilities,
    diy: [],
    states: [],
    dialogMenu: false,
  };
}

function isOn(device) {
  return device.states[DeviceState.POWER].value !== 0;
}

async function powerSwitch(device) {
  device.states[DeviceState.POWER].loading = true;
  const response = await httpRequest(apiKey.value, "https://openapi.api.govee.com/router/api/v1/device/control",
      Http.POST, generateJSON(device, Capability.POWER, isOn(device) ? 0 : 1));

  if (response.data.code === 200) {
    device.states[DeviceState.POWER].value = response.data.capability.value;
    device.states[DeviceState.POWER].loading = false;
  }
}

function generateJSON(device, capability, value) {
  const json = {
    requestId: "1",
    payload: {
      sku: device.sku,
      device: device.addr,
      capability: {
        type: capability.type,
        instance: capability.instance,
        value: value
      }
    }
  };
  return JSON.stringify(json, null, 2);
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(";").shift();
}

function savePreset(obj) {
  presets.value.push(obj);
  presets.value.sort((a, b) => {
    return a.name.localeCompare(b.name) || a.actions > b.actions;
  });
  localStorage.setItem("presets", JSON.stringify(presets.value));
}

function deletePreset(index) {
  presets.value.splice(index, 1);
  localStorage.setItem("presets", JSON.stringify(presets.value));
}

function updatePreset(index, preset) {
  presets.value[index] = preset;
  presets.value.sort((a, b) => {
    return a.name.localeCompare(b.name) || a.actions > b.actions;
  });
  localStorage.setItem("presets", JSON.stringify(presets.value));
}

</script>

<template>
  <v-app id="app">
    <v-app-bar app color="primary" elevation="7">
      <v-app-bar-nav-icon>
        <v-icon>mdi-gamepad-right</v-icon>
      </v-app-bar-nav-icon>
      <v-app-bar-title>Controllify for Govee</v-app-bar-title>
      <template v-slot:append>
        <v-icon>mdi-weather-sunny</v-icon>
        <v-switch class="px-2"
                  hide-details
                  v-model="theme.global.name.value"
                  true-value="dark"
                  false-value="light"
                  color="accent"/>
        <v-icon>mdi-weather-night</v-icon>
        <v-divider vertical thickness="2" class="mx-5 my-2"/>
        <v-btn icon class="mr-5" @click="settings = true; newKey = apiKey">
          <v-icon>mdi-cog</v-icon>
        </v-btn>
      </template>
      <template v-slot:extension>
        <v-tabs v-model="tab" align-tabs="title" grow color="accent">
          <v-tab rounded="false"
                 key="devices"
                 text="Devices"
                 value="devices"
                 prepend-icon="mdi-lightbulb-group"></v-tab>
          <v-tab rounded="false" key="presets" text="Presets" value="presets" prepend-icon="mdi-palette"></v-tab>
        </v-tabs>
      </template>
    </v-app-bar>

    <!-- MAIN -->
    <v-main>
      <v-container>
        <v-tabs-window v-model="tab">
          <v-tabs-window-item key="devices" value="devices">
            <Home @refresh="refreshDevices"
                  @set-device-capability="setDeviceCapability"
                  @power-switch="powerSwitch"
                  :devices="devices"
                  :refreshing="refreshing"
                  :current-time="currentTime"
                  :last-refresh="lastRefresh"
                  :block-state-change="blockStateChange" :show-advanced-info="showAdvancedInfo"/>
          </v-tabs-window-item>
          <v-tabs-window-item key="presets" value="presets">
            <Presets @save-preset="savePreset"
                     @delete-preset="deletePreset"
                     @update-preset="updatePreset"
                     :devices="devices"
                     :presets="presets"
                     :show-advanced-info="showAdvancedInfo"/>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-container>
    </v-main>


    <v-snackbar v-model="storageSuccess" :timeout="4000" color="success" variant="elevated">
      Retrieved devices from local storage!
    </v-snackbar>

    <v-snackbar v-model="apiSuccess" :timeout="4000" color="success" variant="elevated">
      Retrieved devices from Govee API!
    </v-snackbar>

    <!---->

    <!-- DIALOGS FOR MAIN APP PAGE -->

    <!---->

    <v-dialog v-model="settings" max-width="60em" v-on:close="showKey = false">
      <v-card prepend-icon="mdi-cog" title="Settings" rounded="xl">
        <template v-slot:append>
          <v-btn variant="plain" color="red" icon @click="settings = false">
            <v-icon icon="mdi-close"></v-icon>
          </v-btn>
        </template>
        <v-divider class="my-2"/>
        <v-card-text>
          <v-container>
            <h3>GOVEE API</h3>
            <v-row class="d-flex align-center my-2">
              <v-col cols="10">
                <v-text-field prepend-icon="mdi-key"
                              label="API Key"
                              variant="solo-filled"
                              hide-details
                              :append-icon="showKey ? 'mdi-eye' : 'mdi-eye-off'"
                              :type="showKey ? 'text' : 'password'"
                              @click:append="showKey = !showKey"
                              v-model="newKey"/>
              </v-col>
              <v-col cols="2">
                <v-btn block
                       size="large"
                       rounded="lg"
                       color="secondary"
                       @click="submitKey(); showSnackbar('API Key Updated')">Save
                </v-btn>
              </v-col>
            </v-row>
            <v-divider/>
            <h3 class="mt-5">GENERAL</h3>
            <v-row class="d-flex align-center">
              <v-col cols="3">
                <span>Dark Mode</span>
              </v-col>
              <v-col cols="3">
                <v-switch hide-details
                          v-model="theme.global.name.value"
                          true-value="dark"
                          false-value="light"
                          color="accent"></v-switch>
              </v-col>
            </v-row>
            <v-row class="d-flex align-center">
              <v-col cols="3">
                <span>Display API Call Limit</span>
              </v-col>
              <v-col cols="3">
                <v-switch hide-details v-model="displayCallLimit" color="accent"></v-switch>
              </v-col>
            </v-row>
            <v-row class="d-flex align-center">
              <v-col cols="3">
                <span>Advanced Device Info</span>
              </v-col>
              <v-col cols="3">
                <v-switch hide-details v-model="showAdvancedInfo" color="accent"></v-switch>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="noKey" max-width="50rem">
      <v-card rounded="xl" title="Uh oh!" subtitle="No Govee API Key" color="background">
        <template v-slot:append>
          <v-btn color="red" variant="text" icon @click="noKey = false;">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
        <v-card-text>
          <v-container>
            <span>It looks like you don't have an API key set! Click settings in the top right corner to set a key.</span>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack" :text="snackText" :timeout="4000" color="success" variant="elevated"/>

  </v-app>
</template>

<style scoped>

</style>