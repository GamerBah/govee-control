<template>
  <v-container>
    <div class="d-flex align-baseline">
      <v-btn class="mb-5"
             variant="elevated"
             elevation="7"
             block
             @click="openPresetDialog"
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
          <v-col v-for="preset in presets" :key="preset.name" cols="4" xl="3">
            <v-card class="card mx-auto my-auto" rounded="lg" variant="elevated" elevation="7">
              <template v-slot:title>
                <span class="mr-3">{{ preset.name }}</span>
              </template>

              <v-card-text>

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
  </v-container>

  <v-dialog persistent v-model="dialogPreset" max-width="60rem">
    <v-card rounded="xl" :title="currentPreset?.name ?? 'Create a new lighting preset'" color="background">
      <template v-slot:append>
        <v-btn color="red" variant="text" icon @click="resetPresetDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </template>
      <v-divider/>
      <v-card-text>
        <v-container>
          <h2>Preset Name</h2>
          <v-row class="d-flex align-center my-2">
            <v-col>
              <v-text-field clearable
                            label="Preset Name"
                            placeholder="New Preset"
                            persistent-placeholder
                            variant="solo-filled"
                            color="accent"
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
                            v-model="currentPreset.diy[device.deviceName]"
                            @click:clear="delete currentPreset.diy[device.deviceName]"
                            @update:modelValue="updateSelectData(device, currentPreset.diy[device.deviceName])">
                    <template v-slot:item="{ props, item }">
                      <v-list-item v-if="showAdvancedInfo"
                                   v-bind="props"
                                   :title="passName(item)"
                                   :subtitle="'ID: ' + item.raw.value"></v-list-item>
                      <v-list-item v-else v-bind="props" :title="passName(item)"></v-list-item>
                    </template>
                    <template v-slot:selection="{ item, index }">
                      <span>{{ diyValues[item.value] }}</span>
                    </template>
                  </v-select>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>
          <v-card-actions>
            <v-spacer/>
            <v-btn block
                   prepend-icon="mdi-cancel"
                   variant="elevated"
                   color="red"
                   min-width="15%"
                   min-height="50px"
                   rounded="lg">Cancel
            </v-btn>
            <v-spacer/>
            <v-btn block
                   prepend-icon="mdi-floppy"
                   variant="elevated"
                   color="green"
                   min-width="15%"
                   min-height="50px"
                   rounded="lg">Save
            </v-btn>
            <v-spacer/>
          </v-card-actions>
        </v-container>
      </v-card-text>
    </v-card>
  </v-dialog>

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
  computed: {},
  components: {IconCommunity},
  props: {
    devices: Array,
    showAdvancedInfo: Boolean,
  },
  data() {
    return {
      dialogPreset: false,
      currentPreset: null,
      currentIndex: null,
      diyValues: {},
      presets: [],
    };
  },
  methods: {
    generatePresetData() {
      let diy = {};
      this.currentPreset = {
        name: null,
        actions: 0,
        diy: diy,
      };
    },
    getDiyList(device) {
      let array = [];
      if (device !== null) {
        for (let i = 0; i < device.diy.length; i++) {
          let diy = device.diy[i];
          array.push({name: diy.name, selected: false});
        }
      }
      this.selectedDiy = array;
    },
    updateSelectData(device, value) {
      this.currentPreset.diy[device.deviceName] = {sku: device.sku, addr: device.addr, value: value};
    },
    passName(item) {
      this.diyValues[item.raw.value] = item.raw.name;
      return item.raw.name;
    },
    openPresetDialog() {
      this.dialogPreset = true;
      this.generatePresetData();
    },
    resetPresetDialog() {
      this.dialogPreset = false;
    },
  },
  watch: {
    devices(value) {
      this.devices = value;
    }
  }
};

</script>