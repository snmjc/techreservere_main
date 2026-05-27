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

const authStore = useAuthenticationStore();
const { signOut } = useAuth();

async function handleLogout() {
  authStore.performLogout();

  try {
    await signOutClerk(signOut);
  } catch (error) {
    // Local database accounts may not have an active Clerk session.
  } finally {
    window.location.href = '/clerk-login';
  }
}
</script>

<style scoped>
.account-deactivated-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  background: #f3f6f4;
  color: #101827;
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.account-deactivated-card {
  width: min(100%, 520px);
  padding: 2rem;
  border: 1px solid #d8e1dc;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 18px 50px rgba(15, 63, 42, 0.12);
  text-align: center;
}

.account-deactivated-logo {
  width: 72px;
  height: 72px;
  object-fit: contain;
  margin-bottom: 1rem;
}

.account-deactivated-kicker {
  margin: 0 0 0.45rem;
  color: #b42318;
  font-size: 0.78rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.account-deactivated-card h1 {
  margin: 0;
  color: #101827;
  font-size: 1.7rem;
  font-weight: 900;
  letter-spacing: 0;
}

.account-deactivated-message,
.account-deactivated-support {
  margin: 1rem 0 0;
  color: #52615a;
  font-size: 0.98rem;
  line-height: 1.55;
}

.account-deactivated-support {
  margin-top: 0.55rem;
}

.account-deactivated-logout {
  margin-top: 1.5rem;
  min-width: 140px;
  border: 0;
  border-radius: 8px;
  padding: 0.8rem 1.25rem;
  color: #ffffff;
  background: #0f6f43;
  cursor: pointer;
  font-weight: 850;
}

.account-deactivated-logout:hover {
  background: #095b35;
}
</style>
