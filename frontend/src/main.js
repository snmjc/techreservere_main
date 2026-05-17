import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'
import { clerkPlugin } from '@clerk/vue'

// Use fallback key if environment variable is not set
const PUBLISHABLE_KEY = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY || 'pk_test_cHJpbWFyeS1yb29zdGVyLTgwLmNsZXJrLmFjY291bnRzLmRldiQ'

if (!PUBLISHABLE_KEY) {
  throw new Error('Add your Clerk Publishable Key to the .env file')
}

const techReservePinia = createPinia()

const techReserveApplication = createApp(App)
techReserveApplication.use(techReservePinia)
techReserveApplication.use(applicationRouter)
techReserveApplication.use(clerkPlugin, { publishableKey: PUBLISHABLE_KEY })
techReserveApplication.mount('#app')
