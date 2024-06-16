<template>
  <v-container>
    <div class="d-flex align-baseline">
      <v-btn class="mb-5"
             variant="elevated"
             elevation="7"
             color="secondary"
             rounded="lg"
             @click="devices = []; getDevices()"
             prepend-icon="mdi-refresh"
             :loading="refreshing">
        <b>Refresh</b>
      </v-btn>
      <span id="refresh">Last refreshed {{ timeSinceRefresh() }}</span>
    </div>
    <div class="d-flex flex-wrap">
      <template v-if="devices.length">
        <v-row align="center">
          <v-col v-for="device in devices" :key="device.name" cols="4" xl="3">
            <v-card class="card mx-auto my-auto"
                    rounded="lg"
                    @click="selectDevice(device)"
                    variant="elevated"
                    elevation="7"
                    :color="connectionStatus(device) === 0 ? `disconnected` : isOn(device) ? `primary` : `primary-dark`"
                    :disabled="connectionStatus(device) === 0">
              <template v-slot:title>
                <span class="mr-3">{{ device.deviceName }}</span>
                <span id="sku" class="flex-grow-1 text-grey">{{ device.sku }}</span>
              </template>
              <template v-slot:append>
                <v-btn icon
                       variant="plain"
                       density="compact"
                       @click.stop="powerSwitch(device)"
                       :color="connectionStatus(device) === -1 ? 'info' : isOn(device) ? 'green' : 'red'"
                       :loading="connectionStatus(device) === -1 || device.states[capabilities.POWER.instance].loading">
                  <v-icon>mdi-power</v-icon>
                </v-btn>
              </template>

              <v-card-text>
                <v-chip class="mr-2" color="white" prepend-icon="mdi-lightbulb-on">
                  <div v-if="connectionStatus(device) === 1">{{ brightness(device) }}%</div>
                  <div v-else>???</div>
                </v-chip>
                <v-chip class="mr-2"
                        v-if="colorTemp(device) !== 0"
                        :color="kToHex(colorTemp(device))"
                        prepend-icon="mdi-thermometer">
                  <div v-if="connectionStatus(device) === 1">{{ colorTemp(device) }}K</div>
                  <div v-else>???</div>
                </v-chip>
              </v-card-text>

              <v-card-actions v-if="connectionStatus(device) === 1" class="d-flex align-baseline ml-2">
                <v-icon color="green" size="x-small">mdi-wifi</v-icon>
                <span class="ml-2 text-green">Connected</span>
              </v-card-actions>
              <v-card-actions v-else-if="connectionStatus(device) === 0" class="d-flex align-baseline ml-2">
                <v-icon color="red" size="x-small">mdi-connection</v-icon>
                <span class="ml-2 text-red">Disconnected</span>
              </v-card-actions>
              <v-card-actions v-else class="d-flex align-baseline ml-2">
                <v-icon color="info" size="x-small">mdi-connection</v-icon>
                <span class="ml-2 text-indigo">Connecting...</span>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </template>
      <template v-else>
        <v-row align="center">
          <v-col v-for="x in skeletons" :key="x" cols="4" xl="3">
            <v-skeleton-loader class="card mx-auto my-auto" type="article" :loading="!devices.length"/>
          </v-col>
        </v-row>
      </template>

      <!-- THIS NEEDS TO BE IN ITS OWN COMPONENT IN THE FUTURE -->
      <!-- Device Card -->

      <v-dialog v-for="device in devices" v-model="device.dialogMenu" max-width="80em">
        <v-card rounded="xl" color="background">
          <template v-slot:title>
            <span class="mr-3">{{ device.deviceName }}</span>
            <span id="sku" class="text-grey">{{ device.sku }}</span>
            <span class="pl-5 flex-grow-1">
              <v-icon color="green" size="x-small">mdi-wifi</v-icon>
              <span class="ml-2 text-green text-body-1">Connected</span>
            </span>
          </template>
          <template v-slot:append>
            <v-btn color="red" variant="text" icon @click="device.dialogMenu = false;">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </template>
          <v-divider class="mt-2 mx-5"/>
          <v-card-text>
            <v-container>
              <v-row>
                <!-- CONTROLS -->
                <v-col>
                  <v-row no-gutters>
                    <v-col class="px-2">
                      <v-slider @end="setDeviceCapability(device, capabilities.BRIGHTNESS, device.states[capabilities.BRIGHTNESS.instance], brightnessSlider)"
                                style="min-width: 42px;"
                                color="accent"
                                v-model="brightnessSlider"
                                :disabled="blockStateChange"
                                direction="vertical"
                                min="1"
                                max="100"
                                step="1">
                        <template v-slot:prepend>
                          {{ brightnessSlider }}%
                        </template>
                        <template v-slot:append>
                          <v-icon class="mb-2">mdi-lightbulb-on</v-icon>
                        </template>
                      </v-slider>
                    </v-col>
                    <v-col class="px-2">
                      <v-slider @end="setDeviceCapability(device, capabilities.COLOR_TEMP, device.states[capabilities.COLOR_TEMP.instance], kelvinSlider)"
                                style="min-width: 42px"
                                v-model="kelvinSlider"
                                :disabled="kelvinSlider < 2000 || blockStateChange"
                                direction="vertical"
                                :color="kToHex(kelvinSlider)"
                                min="2000"
                                max="9000"
                                step="100">
                        <template v-slot:prepend>
                          <span :style="'color: ' + kToHex(kelvinSlider)">{{ kelvinSlider }}K</span>
                        </template>
                        <template v-slot:append>
                          <v-icon class="mb-2" :color="kToHex(kelvinSlider)">mdi-thermometer</v-icon>
                        </template>
                      </v-slider>
                    </v-col>
                  </v-row>
                </v-col>

                <!-- PRESETS -->
                <v-col cols="8">
                  <v-row no-gutters class="justify-end">
                    <v-col v-for="diy in device.diy" :key="diy.value" class="flex-grow-1" cols="5">
                      <v-card rounded="lg"
                              variant="elevated"
                              elevation="5"
                              color="secondary"
                              class="ma-1"
                              :disabled="blockStateChange"
                              @click="setDeviceCapability(device, capabilities.DIY, diy, diy.value)">
                        <template v-slot:title>
                          <span class="mr-3 text-body-2">{{ diy.name }}</span>
                        </template>
                        <template v-slot:append>
                          <v-btn @click.stop="copyJSON(generateJSON(device, capabilities.DIY, diy.value))"
                                 variant="plain"
                                 color="default"
                                 size="small"
                                 icon>
                            <v-icon>mdi-content-copy</v-icon>
                            <v-tooltip activator="parent" location="top" open-delay="250ms">
                              Copy Request Body
                            </v-tooltip>
                          </v-btn>
                          <v-btn @click.stop="openJSONDialog(device, capabilities.DIY, diy, diy.value)"
                                 variant="plain"
                                 color="default"
                                 size="small"
                                 icon>
                            <v-icon>mdi-code-json</v-icon>
                            <v-tooltip activator="parent" location="top" open-delay="250ms">
                              View JSON
                            </v-tooltip>
                          </v-btn>
                        </template>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>
              <v-overlay :model-value="blockStateChange" persistent contained class="align-center justify-center">
                <v-progress-circular color="primary" indeterminate size="64" width="7"/>
              </v-overlay>
            </v-container>
          </v-card-text>
        </v-card>
      </v-dialog>

      <v-dialog v-model="jsonDialog" max-width="50rem">
        <v-card rounded="xl"
                :title="selected.deviceName + ' &#x00bb; ' + capability.name"
                subtitle="HTTP Request JSON Body"
                color="background">
          <template v-slot:append>
            <v-btn color="red" variant="text" icon @click="jsonDialog = false;">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </template>
          <v-card-text>
            <v-container>
              <pre v-html="json" class="bg-grey-darken-3"/>
            </v-container>
          </v-card-text>
        </v-card>
      </v-dialog>

      <v-dialog v-model="noKeySet" max-width="50rem">
        <v-card rounded="xl"
                title="Uh oh!"
                subtitle="No Govee API Key"
                color="background">
          <template v-slot:append>
            <v-btn color="red" variant="text" icon @click="noKeySet = false;">
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

      <v-snackbar v-model="storageSuccess" :timeout="4000" color="success" variant="elevated">
        Retrieved devices from local storage!
      </v-snackbar>

      <v-snackbar v-model="apiSuccess" :timeout="4000" color="success" variant="elevated">
        Retrieved devices from Govee API!
      </v-snackbar>
    </div>
  </v-container>
</template>

<style>
#sku {
  color: #606060;
  font-size: smaller;
  padding: 0 10px;
}

#refresh {
  margin-left: 10px;
  color: darkgrey;
  font-style: italic;
}

.v-input__prepend, .v-input__append {
  justify-content: center;
}

pre {
  border-radius: 16px;
  padding: 10px 20px;
}

.string {
  color: lightgreen;
}

.number {
  color: lightseagreen;
}

.boolean {
  color: lightskyblue;
}

.null {
  color: magenta;
}

.key {
  color: lightcoral;
}

</style>

<script>
import axios from "axios";
import IconCommunity from "@/components/icons/IconCommunity.vue";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(relativeTime);

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

export default {
  components: {IconCommunity},
  data() {
    return {
      apiKey: "apiKey",
      typedKey: "",
      devices: [],
      presetDialog: false,
      selected: null,
      capability: null,
      skeletons: 0,
      json: "",
      jsonDialog: false,
      storageSuccess: false,
      apiSuccess: false,
      worker: null,
      lastRefresh: null,
      currentTime: dayjs(),
      refreshing: false,
      noKeySet: false,
      brightnessSlider: 100,
      kelvinSlider: 2000,
      blockStateChange: false,
      capabilities: {
        POWER: Capability.POWER,
        BRIGHTNESS: Capability.BRIGHTNESS,
        COLOR_TEMP: Capability.COLOR_TEMP,
        DIY: Capability.DIY,
        SCENE: Capability.SCENE,
      },
    };
  },
  async created() {
    this.myWorker = new Worker("requestsWorker.js");
    this.myWorker.addEventListener("message", this.handleWorkerResponse);

    if (storageAvailable("localStorage")) {
      let storage = localStorage.getItem("devices");
      this.apiKey = localStorage.getItem("apiKey");
      this.lastRefresh = dayjs(localStorage.getItem("lastRefresh")) ?? dayjs();
      if (storage !== undefined && storage !== null && storage !== "[]") {
        this.devices = JSON.parse(localStorage.getItem("devices"));
        for (const value of this.devices) {
          value.states[DeviceState.ONLINE] = null;
          this.setDeviceStates(this.apiKey, value);
        }
        this.devices.sort((a, b) => {
          return a.sku.localeCompare(b.sku) || a.deviceName.localeCompare(b.deviceName);
        });
        this.storageSuccess = true;
      } else {
        if (this.apiKey !== null) {
          console.log(this.apiKey)
          await this.getDevices().then(() => this.apiSuccess = true);
        } else {
          this.noKeySet = true;
        }
      }
    } else {
      this.apiKey = this.getCookie("Govee-API-Key");
      if (this.apiKey !== null || this.apiKey !== "") {
        await this.getDevices();
      } else {
        this.noKeySet = true;
      }
    }

    let timeUpdate = setInterval(() => this.currentTime = dayjs(), 60000);
  },
  methods: {
    selectDevice(device) {
      this.selected = device;
      this.brightnessSlider = device.states[DeviceState.BRIGHTNESS].value;
      this.kelvinSlider = device.states[DeviceState.COLOR_TEMP].value;

      device.dialogMenu = true;
    },
    connectionStatus(device) {
      let state = device.states[DeviceState.ONLINE];
      if (state === null || state === undefined) return -1;
      switch (device.states[DeviceState.ONLINE].value ?? false) {
        case true:
          return 1;
        case false:
          return 0;
        default:
          return -1;
      }
    },
    isOn(device) {
      return device.states[DeviceState.POWER].value !== 0;
    },
    connectionColor(device) {
      switch (device.states[DeviceState.ONLINE].value) {
        case false:
          return "red";
        case true:
          return "green";
        default:
          return "info";
      }
    },
    colorTemp(device) {
      return device.states[DeviceState.COLOR_TEMP].value ?? 0;
    },
    kToHex(temp) {
      if (temp < 2000) {
        return "gray";
      } else {
        let rgb = colorTemperatureToRGB(temp);
        return "#" + ((1 << 24) + (rgb.r << 16) + (rgb.g << 8) + rgb.b).toString(16).substring(1).split(".")[0];
      }
    },
    brightness(device) {
      return device.states[DeviceState.BRIGHTNESS].value ?? 0;
    },
    async powerSwitch(device) {
      device.states[DeviceState.POWER].loading = true;
      const response = await this.httpRequest(this.apiKey, "https://openapi.api.govee.com/router/api/v1/device/control",
          Http.POST, this.generateJSON(device, Capability.POWER, this.isOn(device) ? 0 : 1));

      if (response.data.code === 200) {
        device.states[DeviceState.POWER].value = response.data.capability.value;
        device.states[DeviceState.POWER].loading = false;
      }
    },
    timeSinceRefresh() {
      return dayjs(this.currentTime).to(this.lastRefresh);
    },
    transformItemToDevice(item) {
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
    },
    async postDeviceIfHasDiy(deviceObj) {
      if (this.deviceHasDiy(deviceObj.capabilities)) {
        const obj = {
          php: "https://gamerbah.com/govee/api/request.php",
          apiKey: this.apiKey,
          deviceObj: deviceObj,
          url: "https://openapi.api.govee.com/router/api/v1/device/diy-scenes"
        };

        // Wrap the postMessage call in a new Promise, resolve when the worker sends back the result, reject on error
        return new Promise((resolve, reject) => {
          // handle message event
          this.myWorker.onmessage = async (e) => {
            const diyData = e.data.response;
            deviceObj.diy = diyData.payload.capabilities[0].parameters.options;
            deviceObj.diy.sort((a, b) => a.name.localeCompare(b.name));
            deviceObj.diy.forEach(obj => obj["loading"] = false);
            deviceObj.states = await this.getDeviceStates(this.apiKey, deviceObj);
            resolve(deviceObj);   // resolve the promise with the updated deviceObj
          };

          // handle error event
          this.myWorker.onerror = (err) => {
            console.log("Worker error: ", err);
            reject(err);
          };

          this.myWorker.postMessage(JSON.stringify(obj));
        });
      }

      // if a device doesn't have DIY capability, return it as is
      return deviceObj;
    },
    async getDevices() {
      this.refreshing = true;
      try {
        const response = await this.httpRequest(this.apiKey, "https://openapi.api.govee.com/router/api/v1/user/devices", Http.GET, {});
        const data = response.data;
        this.skeletons = data.data.length;

        const devicePromises = data.data.map(item => {
          return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
              reject("Promise timed out.");
            }, 5000); // Set your desired timeout value.

            Promise.resolve(this.transformItemToDevice(item))
                .then((deviceObj) => this.postDeviceIfHasDiy(deviceObj))
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

        this.devices = await Promise.all(devicePromises);
        this.devices.sort((a, b) => a.sku.localeCompare(b.sku));
      } catch (error) {
        console.log("Error:", error);
      } finally {
        this.refreshing = false;
        this.lastRefresh = dayjs();
        this.currentTime = dayjs();
        localStorage.setItem("lastRefresh", this.lastRefresh);
      }
    },
    async handleWorkerResponse(e) {
      const diyData = e.data.response;
      const deviceObj = e.data.deviceObj;

      deviceObj.diy = diyData.payload.capabilities[0].parameters.options;
      deviceObj.diy.sort((a, b) => a.name.localeCompare(b.name));
      deviceObj.diy.forEach(obj => obj["loading"] = false);

      deviceObj.states = await this.getDeviceStates(this.apiKey, deviceObj);

      this.devices.push(deviceObj);
      localStorage.setItem("devices", JSON.stringify(this.devices.sort((a, b) => {
        return a.sku.localeCompare(b.sku) || a.deviceName.localeCompare(b.deviceName);
      })));
    },
    async getDeviceStates(apiKey, device) {
      const response = await this.httpRequest(apiKey, "https://openapi.api.govee.com/router/api/v1/device/state", Http.POST, {
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
    },
    async setDeviceStates(apiKey, device) {
      const response = await this.httpRequest(apiKey, "https://openapi.api.govee.com/router/api/v1/device/state", Http.POST, {
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
    },
    async setDeviceCapability(device, capability, instance, value) {
      instance.loading = true;
      this.blockStateChange = true;
      let response = await this.httpRequest(this.apiKey, "https://openapi.api.govee.com/router/api/v1/device/control", Http.POST, this.generateJSON(device, capability, value));

      if (response.data.code === 200) {
        instance.value = value;
        instance.loading = false;
        this.blockStateChange = false;
      }
    },
    async httpRequest(apiKey, url, method, data) {
      return axios({
        url: "https://gamerbah.com/govee/api/request.php",
        method: method,
        params: {"api_key": apiKey, "api_url": url},
        headers: {"Content-Type": "application/json"},
        data: data,
      });
    },
    getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(";").shift();
    },
    deviceHasDiy(capabilities) {
      if (capabilities !== null) {
        return capabilities.some(capability => capability.instance === "diyScene");
      }
    },
    generateJSON(device, capability, value) {
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
    },
    async copyJSON(generatedJSON) {
      try {
        await navigator.clipboard.writeText(generatedJSON);
        console.log("Copying to clipboard was successful!");
      } catch (err) {
        console.error("Error in copying to clipboard: ", err);
      }
    },
    openJSONDialog(device, capability, instance, value) {
      this.capability = instance;
      this.json = syntaxHighlight(this.generateJSON(device, capability, value));
      this.jsonDialog = true;
    }
  },
};

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

const Http = Object.freeze({
  GET: "GET",
  POST: "POST",
});

function syntaxHighlight(json) {
  if (typeof json != "string") {
    json = JSON.stringify(json, undefined, 2);
  }
  json = json.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
    let cls = "number";
    if (/^"/.test(match)) {
      if (/:$/.test(match)) {
        cls = "key";
      } else {
        cls = "string";
      }
    } else if (/true|false/.test(match)) {
      cls = "boolean";
    } else if (/null/.test(match)) {
      cls = "null";
    }
    return "<span class=\"" + cls + "\">" + match + "</span>";
  });
}

function colorTemperatureToRGB(kelvin) {
  let temp = kelvin / 100;
  let red, green, blue;
  if (temp <= 66) {
    red = 255;
    green = temp;
    green = 99.4708025861 * Math.log(green) - 161.1195681661;
    if (temp <= 19) {
      blue = 0;
    } else {
      blue = temp - 10;
      blue = 138.5177312231 * Math.log(blue) - 305.0447927307;
    }
  } else {
    red = temp - 60;
    red = 329.698727446 * Math.pow(red, -0.1332047592);
    green = temp - 60;
    green = 288.1221695283 * Math.pow(green, -0.0755148492);
    blue = 255;
  }

  return {
    r: clamp(red, 0, 255),
    g: clamp(green, 0, 255),
    b: clamp(blue, 0, 255)
  };
}

function clamp(x, min, max) {
  if (x < min) {
    return min;
  }
  if (x > max) {
    return max;
  }
  return x;
}

</script>