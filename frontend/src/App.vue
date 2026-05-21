<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth, useUser } from '@clerk/vue'
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js'
import { getClerkToken } from '@/modules/authentication/utils/clerkAuthUtils.js'
import { resolveRole } from '@/modules/authentication/utils/roleUtils.js'

const router = useRouter()
const route = useRoute()
const { isLoaded, isSignedIn, user } = useUser()
const { getToken } = useAuth()
const authStore = useAuthenticationStore()

const isLoading = computed(() => !isLoaded.value)

// Hydrate Pinia auth state from an existing Clerk session (works even when user lands directly on protected routes).
watch([isLoaded, isSignedIn, user], async ([loaded, signedIn, clerkUser]) => {
  if (!loaded) return

  if (!signedIn) {
    if (authStore.accountData?.authProvider === 'clerk') {
      authStore.performLogout()
    }

    if (route.meta?.requiresAuth) {
      router.replace({ name: 'clerkLoginPage' })
    }
    return
  }

  if (!clerkUser) return
  if (authStore.isAuthenticated) return

  let token: string | null = null
  try {
    token = await getClerkToken(getToken)
  } catch (e) {
    token = null
  }

  authStore.setClerkAuth(token, {
    accountIdentifier: clerkUser.id,
    firstName: clerkUser.firstName || '',
    lastName: clerkUser.lastName || '',
    emailAddress: clerkUser.primaryEmailAddress?.emailAddress || '',
    roleDesignation: resolveRole(clerkUser.publicMetadata?.role, clerkUser.primaryEmailAddress?.emailAddress || ''),
    contactNumber: clerkUser.publicMetadata?.contactNumber || '',
    isActive: true,
    authProvider: 'clerk',
  })

  // If the user is on the login page but already signed in, route them to the correct dashboard.
  if (route.name === 'clerkLoginPage' || route.name === 'loginPage' || route.path === '/') {
    if (authStore.userRole === 'ROLE_ADMIN') {
      router.replace({ name: 'adminDashboardPage' })
    } else {
      router.replace({ name: 'borrowerMyReservationsPage' })
    }
  }
}, { immediate: true })
</script>

<template>
  <div v-if="isLoading" class="loading-container">
    <p>Loading...</p>
  </div>
  <div v-else>
    <router-view />
  </div>
</template>

<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  font-size: 1.2rem;
  color: #666;
}
</style>
