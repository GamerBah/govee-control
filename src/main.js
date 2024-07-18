import { createApp } from 'vue'
import App from './App.vue'

import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

import { md3 } from 'vuetify/blueprints'
import colors from 'vuetify/util/colors'

const vuetify = createVuetify({
    components,
    directives,
    ssr: true,
    blueprint: md3,
    theme: {
        defaultTheme: 'dark',
        themes: {
            light: {
                dark: false,
                colors: {
                    primary: colors.red.darken1, // #E53935
                    secondary: colors.red.lighten4, // #FFCDD2
                    background: colors.grey.lighten2,
                }
            },
            dark: {
                dark: true,
                colors: {
                    primary: "#303073",
                    secondary: "#00497A",
                    "primary-dark": "#1E1E48",
                    accent: "#FFE747",
                    background: colors.grey.darken4,
                    disconnected: "#2D1C20",
                    white: colors.grey.lighten5,
                    lightGray: colors.grey.darken3,
                }
            },
        },
    }
})

createApp(App).use(vuetify).mount('#app')
