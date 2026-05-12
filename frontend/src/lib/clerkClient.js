import { Clerk } from '@clerk/clerk-js'

const clerkPublishableKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY

export const clerk = new Clerk({
  publishableKey: clerkPublishableKey,
})

export const initClerk = async () => {
  await clerk.load()
  return clerk
}
