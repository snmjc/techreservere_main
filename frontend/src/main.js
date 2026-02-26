import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'

const techReserveApplication = createApp(App)
techReserveApplication.use(applicationRouter)
techReserveApplication.mount('#app')
