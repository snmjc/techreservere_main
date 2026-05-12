# Clerk Authentication Setup for TechReserve

This guide explains how to set up Clerk authentication for your TechReserve application.

## What is Clerk?

Clerk is a modern authentication platform that handles user sign-up, login, and session management. Your backend already has Clerk integration built in via `AuthenticationMiddleware.php`.

## Step 1: Create a Clerk Account

1. Go to https://clerk.com
2. Sign up for a free account
3. Create a new application called "TechReserve"
4. Choose "Web" as your application type

## Step 2: Get Your Clerk Keys

1. In Clerk Dashboard, go to **API Keys** (or **Developers** → **API Keys**)
2. Copy your **Publishable Key** (starts with `pk_`)
3. Copy your **Secret Key** (starts with `sk_`)

## Step 3: Update Your .env File

Replace the placeholder values in your `.env` file:

```env
VITE_CLERK_PUBLISHABLE_KEY=pk_your_actual_publishable_key_here
CLERK_SECRET_KEY=sk_your_actual_secret_key_here
```

Example:
```env
VITE_CLERK_PUBLISHABLE_KEY=pk_test_abc123xyz789
CLERK_SECRET_KEY=sk_test_def456uvw012
```

## Step 4: Install Clerk Packages

```bash
cd frontend
npm install @clerk/clerk-react
```

The package is already configured in `src/lib/clerkClient.js`.

## Step 5: Update Your Main App Component

In your `frontend/src/main.js` or main app component, initialize Clerk:

```javascript
import { initClerk } from './lib/clerkClient'

// Initialize Clerk before mounting the app
initClerk().then(() => {
  // Your app initialization code
})
```

## Step 6: Create a Login Component

Create a new file `frontend/src/components/ClerkLogin.vue`:

```vue
<template>
  <div class="clerk-login">
    <h1>TechReserve Login</h1>
    <div id="clerk-sign-in"></div>
  </div>
</template>

<script>
import { clerk } from '@/lib/clerkClient'

export default {
  name: 'ClerkLogin',
  mounted() {
    if (clerk.loaded) {
      clerk.mountSignIn(document.getElementById('clerk-sign-in'))
    }
  }
}
</script>

<style scoped>
.clerk-login {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
</style>
```

## Step 7: Protect Routes with Authentication

Update your router to check if user is authenticated:

```javascript
import { clerk } from '@/lib/clerkClient'

router.beforeEach(async (to, from, next) => {
  // Check if route requires authentication
  if (to.meta.requiresAuth) {
    const user = clerk.user
    if (!user) {
      next('/login')
    } else {
      next()
    }
  } else {
    next()
  }
})
```

## Step 8: Get User Information in Your App

```javascript
import { clerk } from '@/lib/clerkClient'

// Get current user
const user = clerk.user

// Access user properties
console.log(user.firstName)
console.log(user.lastName)
console.log(user.emailAddresses[0].emailAddress)
console.log(user.publicMetadata) // Custom metadata
```

## Backend Integration

Your backend already has Clerk authentication in `AuthenticationMiddleware.php`. It:

1. Checks for Clerk JWT tokens in request headers
2. Validates tokens with Clerk
3. Creates/updates user accounts in the database
4. Allows authenticated API requests

### Backend Environment Variables

Add to your backend `.env`:
```
CLERK_SECRET_KEY=sk_your_actual_secret_key_here
```

## Testing Clerk Login

1. Update your `.env` file with real Clerk keys
2. Restart Docker: `docker compose down && docker compose up -d`
3. Go to http://localhost:5173
4. Click login and use Clerk's sign-up/login interface
5. After login, you'll be redirected to your app

## Troubleshooting

### "Clerk is not defined"
- Make sure `initClerk()` is called before mounting the app
- Check that `VITE_CLERK_PUBLISHABLE_KEY` is set in `.env`

### Login not working
- Verify your Clerk keys are correct
- Check browser console for errors
- Ensure Clerk application is active in Clerk Dashboard

### Backend not recognizing user
- Verify `CLERK_SECRET_KEY` is set in backend `.env`
- Check that Clerk JWT token is being sent in request headers
- Review `AuthenticationMiddleware.php` logs

## Next Steps

1. Get your Clerk keys from https://clerk.com
2. Update `.env` file with your keys
3. Restart Docker Compose
4. Test login at http://localhost:5173

Your TechReserve application will then have full authentication with Clerk!
