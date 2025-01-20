// Vuetify
import "vuetify/styles";
import { createVuetify } from "vuetify";
import { aliases, mdi } from "vuetify/iconsets/mdi";
import colors from "vuetify/util/colors";
import "@mdi/font/css/materialdesignicons.css";

const vuetify = createVuetify({
    theme: {
        defaultTheme: "light",
        themes: {
            light: {
                dark: false,
                colors: {
                    primary: colors.green.darken2,
                    danger: colors.red.darken4,
                },
            },
        },
    },
    icons: {
        defaultSet: "mdi",
        aliases,
        sets: {
            mdi,
        },
    },
    date: {
        locale: {
            en: "ru-RU",
        },
    },
});

export default vuetify;
