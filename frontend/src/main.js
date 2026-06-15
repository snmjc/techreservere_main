import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import applicationRouter from './router/index.js'
import { clerkPlugin } from '@clerk/vue'
import { resolveClerkRedirectOptions } from './modules/authentication/utils/clerkRedirects.js'

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

function decodeClerkDomain(publishableKey) {
  const encodedDomain = publishableKey.split('_').slice(2).join('_')

  if (!encodedDomain) {
    return ''
  }

  try {
    return atob(encodedDomain).replace(/\$$/, '')
  } catch (error) {
    console.error('Unable to decode the Clerk publishable key domain.', error)
    return ''
  }
}

const PUBLISHABLE_KEY = resolveClerkPublishableKey()

if (!PUBLISHABLE_KEY) {
  throw new Error('Add VITE_CLERK_PUBLISHABLE_KEY to the frontend environment before building.')
}

const LIVE_FRONTEND_HOSTS = new Set([
  'techreserve.farahkenawy.codes',
])
const ACTIVE_CLERK_PROXY_URL = 'https://clerk.techreserve.farahkenawy.codes'

const currentFrontendHost = typeof window !== 'undefined' ? window.location.hostname : ''
const isLiveFrontendHost = LIVE_FRONTEND_HOSTS.has(currentFrontendHost)
const isDevelopmentClerkKey = PUBLISHABLE_KEY.startsWith('pk_test_')
const clerkFrontendApiDomain = decodeClerkDomain(PUBLISHABLE_KEY)

if (isLiveFrontendHost && isDevelopmentClerkKey) {
  console.error(
    `Clerk is disabled on ${currentFrontendHost}: expected a pk_live_ publishable key, received ${PUBLISHABLE_KEY.slice(0, 12)}...`,
  )
}

const shouldDisableClerkOnLiveHost = isLiveFrontendHost && isDevelopmentClerkKey

if (isLiveFrontendHost && !isDevelopmentClerkKey && !clerkFrontendApiDomain) {
  console.warn('Unable to decode the Clerk publishable key domain on the live frontend host.')
}

const techReservePinia = createPinia()

const techReserveApplication = createApp(App)
techReserveApplication.use(techReservePinia)
techReserveApplication.use(applicationRouter)

if (!shouldDisableClerkOnLiveHost) {
  const clerkPluginOptions = {
    publishableKey: PUBLISHABLE_KEY,
    ...resolveClerkRedirectOptions(),
  }

  if (isLiveFrontendHost && !isDevelopmentClerkKey) {
    clerkPluginOptions.proxyUrl = ACTIVE_CLERK_PROXY_URL
  }

  techReserveApplication.use(clerkPlugin, clerkPluginOptions)
}

techReserveApplication.mount('#app')
