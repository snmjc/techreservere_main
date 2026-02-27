import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'

const techReservePinia = createPinia()

const techReserveApplication = createApp(App)
techReserveApplication.use(techReservePinia)
techReserveApplication.use(applicationRouter)
techReserveApplication.mount('#app')
