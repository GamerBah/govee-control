<template>
  <v-container>
    <div class="d-flex align-baseline">
      <v-btn class="mb-5"
             variant="elevated"
             elevation="7"
             color="secondary"
             rounded="lg"
             @click="$emit('refresh')"
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
                <span v-if="showAdvancedInfo" id="sku" class="flex-grow-1 text-grey">{{ device.sku }}</span>
              </template>
              <template v-slot:append>
                <v-btn icon
                       variant="plain"
                       density="compact"
                       @click.stop="$emit('powerSwitch', device)"
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
            <span v-if="showAdvancedInfo" id="sku" class="text-grey">{{ device.sku }}</span>
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
                      <v-slider @end="$emit('setDeviceCapability', device, capabilities.BRIGHTNESS, device.states[capabilities.BRIGHTNESS.instance], brightnessSlider)"
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
                      <v-slider @end="$emit('setDeviceCapability', device, capabilities.COLOR_TEMP, device.states[capabilities.COLOR_TEMP.instance], kelvinSlider)"
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
                              @click="$emit('setDeviceCapability', device, capabilities.DIY, diy, diy.value)">
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
  props: {
    devices: Array,
    refreshing: Boolean,
    currentTime: dayjs,
    lastRefresh: dayjs,
    blockStateChange: Boolean,
    showAdvancedInfo: Boolean,
  },
  data() {
    return {
      apiKey: "apiKey",
      typedKey: "",
      presetDialog: false,
      selected: null,
      capability: null,
      skeletons: 0,
      json: "",
      jsonDialog: false,
      brightnessSlider: 100,
      kelvinSlider: 2000,
      capabilities: {
        POWER: Capability.POWER,
        BRIGHTNESS: Capability.BRIGHTNESS,
        COLOR_TEMP: Capability.COLOR_TEMP,
        DIY: Capability.DIY,
        SCENE: Capability.SCENE,
      },
    };
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
    isOn(device) {
      return device.states[DeviceState.POWER].value !== 0;
    },
    timeSinceRefresh() {
      return dayjs(this.currentTime).to(this.lastRefresh);
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
  }
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