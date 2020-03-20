/*=========================================================================================
  Description: Module Actions
==========================================================================================*/

import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {    
    return new Promise((resolve, reject) => {
      axios.post("/api/permission-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, {id: response.data.id}))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  editItem({ commit }, item) {    
    commit('EDIT_ITEM', item)
    return
  },
  fetchItems ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/permission-management/index')
        .then((response) => {          
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItem (context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/permission-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  updateItem ({ commit }, payload) {    
    return new Promise((resolve, reject) => {
      axios.post(`/api/permission-management/update/${payload.id}`,payload )
        .then((response) => {          
          commit('UPDATE_ITEM', payload)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeRecord ({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/permission-management/destroy/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}