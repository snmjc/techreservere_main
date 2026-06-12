import { Clerk } from '@clerk/clerk-js'
import { resolveClerkPublishableKey } from './clerkConfig.js'

const clerkPublishableKey = resolveClerkPublishableKey()

export const clerk = new Clerk({
  publishableKey: clerkPublishableKey,
})

export const initClerk = async () => {
  await clerk.load()
  return clerk
}
