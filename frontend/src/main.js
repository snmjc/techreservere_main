import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'
import { clerkPlugin } from '@clerk/vue'

const PLACEHOLDER_CLERK_KEYS = new Set([
  'pk_test_your_clerk_key_here',
  'your_clerk_publishable_key_here',
])

function resolveClerkPublishableKey() {
  const configuredKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY?.trim()

  if (!configuredKey || PLACEHOLDER_CLERK_KEYS.has(configuredKey)) {
    return ''
  }

  return configuredKey
}

const PUBLISHABLE_KEY = resolveClerkPublishableKey()

if (!PUBLISHABLE_KEY) {
  throw new Error('Add VITE_CLERK_PUBLISHABLE_KEY to the frontend environment before building.')
}

const techReservePinia = createPinia()

const techReserveApplication = createApp(App)
techReserveApplication.use(techReservePinia)
techReserveApplication.use(applicationRouter)
techReserveApplication.use(clerkPlugin, { publishableKey: PUBLISHABLE_KEY })
techReserveApplication.mount('#app')
