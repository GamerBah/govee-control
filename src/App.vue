<script setup>
import Home from "@/components/Home.vue";
import {useTheme} from "vuetify";
import {onMounted, ref} from "vue";
import Presets from "@/components/Presets.vue";

const theme = useTheme();

const settings = ref(false);
const apiKey = ref("");
const newKey = ref("");
const showKey = ref(false);
const snack = ref(false);
const snackText = ref("");
const displayCallLimit = ref(false);
const tab = ref("");

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
});

</script>

<template>
  <v-app>
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
    <v-main>
      <v-container>
        <v-tabs-window v-model="tab">
          <v-tabs-window-item key="devices" value="devices">
            <Home/>
          </v-tabs-window-item>
          <v-tabs-window-item key="presets" value="presets">
            <Presets/>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-container>
    </v-main>

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
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack" :text="snackText" :timeout="4000" color="success" variant="elevated"/>

  </v-app>
</template>

<style scoped>

</style>