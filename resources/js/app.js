import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import Toaster from "./components/ui/toast/Toaster.vue";
import Countdown from 'vue3-flip-countdown'
import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import zh from './locales/zh.json'
import ms from './locales/ms.json'

const appName = import.meta.env.VITE_APP_NAME || "S-CONNECT";

// Get saved language from localStorage or default to English
const savedLanguage = localStorage.getItem('visitor-language') || 'en'

const i18n = createI18n({
    legacy: false,
    locale: savedLanguage,
    fallbackLocale: 'en',
    messages: {
        en,
        zh,
        ms
    }
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h("div", {}, [h(Toaster), h(App, props),]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(Countdown)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
