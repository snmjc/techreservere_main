const DEFAULT_AUTH_REDIRECT_PATH = '/dashboard'

function normalizeRedirectPath(rawValue, fallback = DEFAULT_AUTH_REDIRECT_PATH) {
  const candidate = String(rawValue ?? '').trim()

  if (candidate === '') {
    return fallback
  }

  if (candidate.startsWith('/')) {
    return candidate
  }

  try {
    const parsedUrl = new URL(candidate)
    return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}` || fallback
  } catch {
    return fallback
  }
}

export function resolveClerkRedirectOptions() {
  const signInForceRedirectUrl = normalizeRedirectPath(
    import.meta.env.VITE_CLERK_SIGN_IN_FORCE_REDIRECT_URL,
    DEFAULT_AUTH_REDIRECT_PATH,
  )
  const signUpForceRedirectUrl = normalizeRedirectPath(
    import.meta.env.VITE_CLERK_SIGN_UP_FORCE_REDIRECT_URL,
    DEFAULT_AUTH_REDIRECT_PATH,
  )
  const signInFallbackRedirectUrl = normalizeRedirectPath(
    import.meta.env.VITE_CLERK_SIGN_IN_FALLBACK_REDIRECT_URL,
    signInForceRedirectUrl,
  )
  const signUpFallbackRedirectUrl = normalizeRedirectPath(
    import.meta.env.VITE_CLERK_SIGN_UP_FALLBACK_REDIRECT_URL,
    signUpForceRedirectUrl,
  )

  return {
    signInForceRedirectUrl,
    signUpForceRedirectUrl,
    signInFallbackRedirectUrl,
    signUpFallbackRedirectUrl,
  }
}

