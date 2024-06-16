<template>
  <v-container>
    <div class="d-flex align-baseline">
      <v-btn class="mb-5"
             variant="elevated"
             elevation="7"
             block
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
            <v-card class="card mx-auto my-auto"
                    rounded="lg"
                    variant="elevated"
                    elevation="7">
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
  data() {
    return {
      presets: [],
    };
  }
}

</script>