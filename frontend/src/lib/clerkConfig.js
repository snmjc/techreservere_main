const PLACEHOLDER_CLERK_KEYS = new Set([
  'pk_test_your_clerk_key_here',
  'your_clerk_publishable_key_here',
])

const BROKEN_CLERK_DOMAIN = 'clerk.farahkenawy.codes'
const DEFAULT_FALLBACK_CLERK_PUBLISHABLE_KEY = 'pk_test_cHJpbWFyeS1yb29zdGVyLTgwLmNsZXJrLmFjY291bnRzLmRldiQ'

export function decodeClerkDomain(publishableKey) {
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

function getConfiguredClerkKey() {
  const configuredKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY?.trim()

  if (!configuredKey || PLACEHOLDER_CLERK_KEYS.has(configuredKey)) {
    return ''
  }

  return configuredKey
}

function getFallbackClerkKey() {
  const configuredFallbackKey = import.meta.env.VITE_CLERK_FALLBACK_PUBLISHABLE_KEY?.trim()

  if (configuredFallbackKey && !PLACEHOLDER_CLERK_KEYS.has(configuredFallbackKey)) {
    return configuredFallbackKey
  }

  return DEFAULT_FALLBACK_CLERK_PUBLISHABLE_KEY
}

export function resolveClerkPublishableKey() {
  const configuredKey = getConfiguredClerkKey()
  const fallbackKey = getFallbackClerkKey()

  if (!configuredKey) {
    return fallbackKey
  }

  const configuredDomain = decodeClerkDomain(configuredKey)
  if (configuredDomain === BROKEN_CLERK_DOMAIN) {
    console.warn(
      `Configured Clerk domain ${BROKEN_CLERK_DOMAIN} is unavailable; falling back to ${decodeClerkDomain(fallbackKey)}.`,
    )
    return fallbackKey
  }

  return configuredKey
}
