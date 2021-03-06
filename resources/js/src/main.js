/*=========================================================================================
  File Name: main.js
  Description: main vue(js) file
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import Vue from "vue";
import App from "./App.vue";

// Vuesax Component Framework
import Vuesax from "vuesax";

Vue.use(Vuesax);

// axios
import axios from "./axios.js";
Vue.prototype.$http = axios;

// ACL
import acl from "./acl/acl";

// Theme Configurations
import "../themeConfig.js";

// Globally Registered Components
import "./globalComponents.js";

// Vue Router
import router from "./router";

// Vuex Store
import store from "./store/store";

// Tour
import VueTour from "vue-tour";
Vue.use(VueTour);
require("vue-tour/dist/vue-tour.css");

// VeeValidate
import VeeValidate from "vee-validate";
import fr from "vee-validate/dist/locale/fr";
Vue.use(VeeValidate, {
    events: "input",
    locale: "fr",
    dictionary: {
        fr
    }
});

// Vuejs - Vue wrapper for hammerjs
import { VueHammer } from "vue2-hammer";
Vue.use(VueHammer);

// PrismJS
import "prismjs";
import "prismjs/themes/prism-tomorrow.css";

Vue.config.productionTip = false;

new Vue({
    router,
    store,
    acl,
    render: h => h(App)
}).$mount("#app");
