<template>
  <main class="account-deactivated-page">
    <section class="account-deactivated-card">
      <img
        src="@/assets/TechReserve_LogoB.png"
        alt="TechReserve Logo"
        class="account-deactivated-logo"
      />
      <p class="account-deactivated-kicker">Account access disabled</p>
      <h1>Your account is deactivated</h1>
      <p class="account-deactivated-message">
        This account has been disabled by an administrator and no longer has access to the TechReserve system.
      </p>
      <p class="account-deactivated-support">
        Please contact the system administrator if you believe this needs to be restored.
      </p>
      <button class="account-deactivated-logout" type="button" @click="handleLogout">
        Log out
      </button>
    </section>
  </main>
</template>

<script setup>
import { useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { redirectToPostLogoutHome } from '@/modules/authentication/utils/logoutRedirect.js';

const authStore = useAuthenticationStore();
const { signOut } = useAuth();

async function handleLogout() {
  authStore.performLogout();

  try {
    await signOutClerk(signOut);
  } catch (error) {
    // Local database accounts may not have an active Clerk session.
  } finally {
    redirectToPostLogoutHome();
  }
}
</script>

<style scoped>
@import './css/AccountDeactivated.css';
</style>

