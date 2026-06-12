import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'
import { clerkPlugin } from '@clerk/vue'
import { resolveClerkPublishableKey } from './lib/clerkConfig.js'

const PUBLISHABLE_KEY = resolveClerkPublishableKey()

if (!PUBLISHABLE_KEY) {
  throw new Error('Add a valid Clerk publishable key to the frontend environment before building.')
}

const techReservePinia = createPinia()

const techReserveApplication = createApp(App)
techReserveApplication.use(techReservePinia)
techReserveApplication.use(applicationRouter)
techReserveApplication.use(clerkPlugin, { publishableKey: PUBLISHABLE_KEY })

techReserveApplication.mount('#app')