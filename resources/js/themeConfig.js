/*=========================================================================================
  File Name: themeConfig.js
  Description: Theme configuration
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: hhttp://www.themeforest.net/user/pixinvent
==========================================================================================*/

// MAIN COLORS - VUESAX THEME COLORS
export const colors = {
  primary: '#2196f3',
  success: '#2D8F7B',
  danger: '#CE645D',
  warning: '#E8B92E',
  dark: '#424242',
  light: '#E3E3E3',
}

// PROJECT COLORS
export const project_colors = [
  '#51E898',
  '#61BD4F',
  '#F2D600',
  '#FF9F1A',
  '#EB5A46',
  '#FF78CB',
  '#C377E0',
  '#00C2E0',
  '#0079BF',
  '#344563',
  ''
];
// TAG COLORS
export const tag_colors = [
  '#51E898',
  '#61BD4F',
  '#F2D600',
  '#FF9F1A',
  '#EB5A46',
  '#FF78CB',
  '#C377E0',
  '#00C2E0',
  '#0079BF',
  '#344563',
  ''
];

// CONFIGS
const themeConfig = {
  disableCustomizer: false,       // options[Boolean] : true, false(default)
  disableThemeTour: false,       // options[Boolean] : true, false(default)
  footerType: 'static',    // options[String]  : static(default) / sticky / hidden
  hideScrollToTop: false,       // options[Boolean] : true, false(default)
  mainLayoutType: 'vertical',  // options[String]  : vertical(default) / horizontal
  navbarColor: '#fff',      // options[String]  : HEX color / rgb / rgba / Valid HTML Color name - (default: #fff)
  navbarType: 'floating',  // options[String]  : floating(default) / static / sticky / hidden
  routerTransition: 'zoom-fade', // options[String]  : zoom-fade / slide-fade / fade-bottom / fade / zoom-out / none(default)
  rtl: false,       // options[Boolean] : true, false(default)
  sidebarCollapsed: false,       // options[Boolean] : true, false(default)
  theme: 'semi-dark',     // options[String]  : "light"(default), "dark", "semi-dark"

  // Not required yet - WIP
  userInfoLocalStorageKey: "user_info"

  // NOTE: themeTour will be disabled in screens < 1200. Please refer docs for more info.
}

import Vue from 'vue'
import Vuesax from 'vuesax'
Vue.use(Vuesax, { theme: { colors }, rtl: themeConfig.rtl })

export default themeConfig
