const PLACEHOLDER_CLERK_KEYS = new Set([
  'pk_test_your_clerk_key_here',
  'your_clerk_publishable_key_here',
])

const LEGACY_CLERK_DOMAIN = 'clerk.farahkenawy.codes'

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
export function resolveClerkPublishableKey() {
  const configuredKey = getConfiguredClerkKey()

  if (!configuredKey) {
    return ''
  }

  const configuredDomain = decodeClerkDomain(configuredKey)
  if (configuredDomain === LEGACY_CLERK_DOMAIN) {
    console.error(
      `Configured Clerk domain ${LEGACY_CLERK_DOMAIN} is no longer used. Set VITE_CLERK_PUBLISHABLE_KEY to the active Clerk domain before starting the frontend.`,
    )
    return ''
  }

  return configuredKey
}
