<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUser } from '@clerk/vue'
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js'
import { ROUTE_NAMES } from '@/router/routeNames.js'

const router = useRouter()
const route = useRoute()
const { isLoaded, isSignedIn } = useUser()
const authStore = useAuthenticationStore()

const isLoading = computed(() => !isLoaded.value)

// Hydrate Pinia auth state from an existing Clerk session (works even when user lands directly on protected routes).
watch([isLoaded, isSignedIn], async ([loaded, signedIn]) => {
  if (!loaded) return

  if (!signedIn) {
    if (authStore.isAuthenticated) {
      return
    }

    const isClerkSession = authStore.accountData?.authProvider === 'clerk'
      || authStore.clerkAccountData?.authProvider === 'clerk'

    if (isClerkSession) {
      authStore.performLogout()
    }

    if (route.meta?.requiresAuth && !authStore.isAuthenticated) {
      router.replace({ name: ROUTE_NAMES.clerkLogin })
    }
    return
  }

  if (authStore.isAuthenticated) return

  if (route.name === ROUTE_NAMES.clerkLogin || route.name === ROUTE_NAMES.acceptInvitation) return

  // Signed-in Clerk users must pass through PostLogin so the User Accounts DB
  // remains the source of truth for role, status, and dashboard destination.
  if (route.name !== ROUTE_NAMES.postLogin) {
    router.replace({ name: ROUTE_NAMES.postLogin })
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
