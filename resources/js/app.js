import './bootstrap';

import.meta.glob(["../images/**", "../fonts/**"]);

import { createApp } from "vue";
import { createPinia } from "pinia";
import { ZiggyVue } from "ziggy-js";
import router from "@/router/index";
import vuetify from "@/vuetify";

import App from "@/components/App.vue";

const app = createApp(App);

app.use(createPinia()).use(router).use(vuetify).use(ZiggyVue).mount("#app");
