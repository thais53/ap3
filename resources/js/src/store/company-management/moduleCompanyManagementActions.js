/*=========================================================================================
  File Name: moduleCalendarActions.js
  Description: Calendar Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from '@/axios.js'

export default {
  addCompany({ commit }, item) {
    console.log(item)
    return new Promise((resolve, reject) => {
      axios.post("/api/company-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, {id: response.data.id}))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchCompanies ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/company-management/index')
        .then((response) => {
          console.log('axios.get /api/company-management/index');
          console.log(response);
          
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeCompany ({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/company-management/${id}`)
        .then((response) => {
          commit('REMOVE_ITEM', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
