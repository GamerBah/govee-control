<template>
  <v-container>
    <div class="d-flex align-baseline">
      <v-btn class="mb-5"
             variant="elevated"
             elevation="7" block @click="openPresetDialog(true)"
             size="large"
             color="secondary"
             rounded="lg"
             prepend-icon="mdi-plus">
        <b>Create Preset</b>
      </v-btn>
    </div>
    <div class="d-flex flex-wrap">
      <template v-if="presets.length">
        <v-row align="center">
          <v-col v-for="(preset, index) in presets" :key="preset.name" cols="4" xl="3">
            <v-card @click="editPreset(preset, index)"
                    class="card mx-auto my-auto"
                    rounded="lg"
                    variant="elevated"
                    elevation="7"
                    color="primary">
              <template v-slot:title>
                <span class="mr-3">{{ preset.name }}</span>
              </template>
              <template v-slot:append>
                <v-btn class="position-absolute"
                       style="top: 20%; right: 20px"
                       @click.stop="console.log('NYI')"
                       variant="elevated"
                       color="green"
                       icon>
                  <v-icon>mdi-play</v-icon>
                </v-btn>
              </template>

              <v-card-text>
                <span class="mr-3">{{ preset.actions + (preset.actions === 1 ? " action" : " actions") }}</span>
              </v-card-text>

            </v-card>
          </v-col>
        </v-row>
      </template>
      <template v-else>
        <v-row align="center">

        </v-row>
      </template>
    </div>

    <v-dialog persistent v-model="dialogPreset" max-width="60rem">
      <v-card rounded="xl" color="background">
        <template v-slot:title>
          <v-row class="d-flex align-center">
            <v-col cols="5">
              <span>{{ presetCardTitle }}</span>
            </v-col>
            <v-col cols="3">
              <v-btn @click="generateJSON(currentPreset)"
                     prepend-icon="mdi-content-copy"
                     variant="plain"
                     color="white"
                     rounded="lg">
                Copy JSON
              </v-btn>
            </v-col>
            <v-col cols="3">
              <v-btn @click="openJsonDialog(currentPreset)"
                     prepend-icon="mdi-eye"
                     variant="plain"
                     color="white"
                     rounded="lg">
                View JSON
              </v-btn>
            </v-col>
          </v-row>
        </template>
        <template v-slot:append v-if="!newPreset">
          <v-btn @click="dialogDelete = true" variant="plain" density="default" color="red" icon>
            <v-icon>mdi-trash-can</v-icon>
          </v-btn>
        </template>
        <v-divider/>
        <v-card-text>
          <v-container>
            <h2>Preset Name</h2>
            <v-row class="d-flex align-center my-2">
              <v-col>
                <v-text-field clearable
                              label="Name"
                              placeholder="New Preset"
                              persistent-placeholder
                              variant="solo-filled"
                              color="accent" validate-on="input" :rules="[nameRules.unique, nameRules.required]"
                              v-model="currentPreset.name"></v-text-field>
              </v-col>
            </v-row>
            <v-divider/>
            <h2 class="mt-5">Lighting Modes</h2>
            <v-row class="d-flex align-center my-2">
              <v-col v-for="device in devices" :key="device.name" cols="4">
                <v-card class="card mx-auto my-auto" rounded="lg" variant="elevated" color="primary" elevation="7">
                  <template v-slot:title>
                    <span class="text-body-1">{{ device.deviceName }}</span>
                  </template>

                  <v-card-actions class="mb-n5">
                    <v-select clearable
                              placeholder="No Action"
                              variant="filled"
                              color="accent"
                              :item-value="'value'"
                              :items="device.diy"
                              v-model="currentPreset.diy[device.deviceName]" @click:clear="removeDiySelection(device)"
                              @update:modelValue="updateSelectData(device, currentPreset.diy[device.deviceName])">
                      <template v-slot:item="{ props, item }">
                        <v-list-item v-if="showAdvancedInfo"
                                     v-bind="props"
                                     :title="passName(item)"
                                     :subtitle="'ID: ' + item.raw.value"></v-list-item>
                        <v-list-item v-else v-bind="props" :title="passName(item)"></v-list-item>
                      </template>
                      <template v-slot:selection="{ item, index }">
                        <span>{{ diyValues[item.value] ?? currentPreset.diy[device.deviceName].name }}</span>
                      </template>
                    </v-select>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>
            <v-card-actions class="mt-10">
              <v-row class="d-flex align-center text-center" no-gutters>
                <v-col>
                  <v-btn @click="resetPresetDialog"
                         block
                         prepend-icon="mdi-cancel"
                         variant="elevated"
                         color="secondary"
                         size="large"
                         rounded="lg">Cancel
                  </v-btn>
                </v-col>
                <v-spacer/>
                <v-col>
                  <v-btn @click="saveOrUpdatePreset"
                         block
                         prepend-icon="mdi-floppy"
                         variant="elevated"
                         color="green"
                         rounded="lg"
                         size="large"
                         :disabled="!validatePreset">Save
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-actions>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog persistent v-model="dialogDelete" max-width="30rem">
      <v-card rounded="xl" color="background">
        <v-card-text>
          <v-container>
            <v-row class="d-flex align-center text-center">
              <v-col cols="10">
                <h3>Are you sure you want to delete this preset? This cannot be undone.</h3>
              </v-col>
              <v-col cols="2">
                <v-icon color="yellow" size="x-large">mdi-alert-rhombus</v-icon>
              </v-col>
              <v-col>
                <v-btn @click="dialogDelete = false" color="red" block variant="elevated" rounded="lg" text="No"/>
              </v-col>
              <v-col>
                <v-btn @click="deletePreset" color="green" block variant="elevated" rounded="lg" text="Yes"/>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogJson" max-width="50rem" max-height="75vh" scrollable>
      <v-card rounded="xl" :title="currentPreset.name" subtitle="HTTP Request JSON Body" color="background">
        <template v-slot:append>
          <v-btn color="red" variant="text" icon @click="dialogJson = false;">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
        <v-card-text>
          <v-container>
            <pre v-html="json" class="bg-grey-darken-3"/>
          </v-container>
        </v-card-text>
        <v-card-actions class="ma-2">
          <v-btn @click="generateJSON(currentPreset)"
                 prepend-icon="mdi-content-copy"
                 variant="plain"
                 color="white"
                 rounded="lg">
            Copy JSON
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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
import IconCommunity from "@/components/icons/IconCommunity.vue";

export default {
  components: {IconCommunity},
  props: {
    devices: Array,
    presets: Array,
    showAdvancedInfo: Boolean,
  },
  computed: {
    validatePreset() {
      return this.currentPreset.name !== null
          && this.currentPreset.name !== ""
          && this.currentPreset.actions !== 0
          && this.presets.find(preset => preset.name === this.currentPreset.name) === undefined
          || this.currentPreset.name === this.oldName;
    },
    presetCardTitle() {
      if (this.newPreset) {
        return "Create a new lighting preset";
      } else {
        return "Editing " + this.oldName ?? "Create a new lighting preset";
      }
    },
  },
  data() {
    return {
      dialogPreset: false,
      dialogDelete: false,
      dialogJson: false,
      currentPreset: null,
      currentIndex: null,
      oldName: null,
      newPreset: false,
      diyValues: {},
      nameRules: {
        required: value => !!value || "Required",
        unique: value => (this.presets.find(preset => preset.name === value) === undefined || value === this.oldName) || "Name already in use",
      },
      json: null,
    };
  },
  methods: {
    generatePresetData() {
      let diy = {};
      this.newPreset = true;
      this.currentPreset = {
        name: null,
        actions: 0,
        diy: diy,
      };
    },
    editPreset(preset, index) {
      this.newPreset = false;
      let diy = {};
      for (let prop in preset.diy) {
        diy[prop] = preset.diy[prop];
      }
      this.currentPreset = {
        name: preset.name,
        actions: preset.actions,
        diy: diy,
      };
      this.oldName = this.currentPreset.name;
      this.currentIndex = index;
      this.dialogPreset = true;
    },
    updateSelectData(device, value) {
      this.currentPreset.diy[device.deviceName] = {
        sku: device.sku,
        addr: device.addr,
        value: value,
        name: this.diyValues[value]
      };
      this.currentPreset.actions = Object.keys(this.currentPreset.diy).length;
    },
    removeDiySelection(device) {
      delete this.currentPreset.diy[device.deviceName];
      this.currentPreset.actions--;
    },
    passName(item) {
      this.diyValues[item.raw.value] = item.raw.name;
      return item.raw.name;
    },
    openPresetDialog(creating) {
      this.dialogPreset = true;
      this.newPreset = creating;
      this.generatePresetData();
    },
    resetPresetDialog() {
      this.dialogPreset = false;
      this.currentIndex = null;
    },
    saveOrUpdatePreset() {
      if (this.newPreset) {
        this.$emit("savePreset", this.currentPreset);
        this.dialogPreset = false;
        this.newPreset = false;
      } else {
        this.$emit("updatePreset", this.currentIndex, this.currentPreset);
        this.dialogPreset = false;
      }
    },
    deletePreset() {
      this.$emit("deletePreset", this.currentIndex);
      this.dialogDelete = false;
      this.dialogPreset = false;
    },
    generateJSON(preset) {
      const json = {
        actions: []
      };
      for (let [key, value] of Object.entries(preset.diy)) {
        const request = {
          requestId: "1",
          payload: {
            sku: value.sku,
            device: value.addr,
            capability: {
              type: "devices.capabilities.dynamic_scene",
              instance: "diyScene",
              value: value.value
            }
          }
        };
        json.actions.push(request);
      }
      return JSON.stringify(json, null, 2);
    },
    openJsonDialog(preset) {
      this.json = syntaxHighlight(this.generateJSON(preset));
      this.dialogJson = true;
    }
  },
  watch: {
    devices(value) {
      this.devices = value;
    }
  }
};

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

</script>