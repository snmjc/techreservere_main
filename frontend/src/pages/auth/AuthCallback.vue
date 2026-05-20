<template>
  <div class="auth-callback-wrapper">
    <div class="loading-content">
      <div class="spinner"></div>
      <p>Signing you in...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUser, useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
const { isSignedIn, user } = useUser();
const { getToken } = useAuth();
const authStore = useAuthenticationStore();

onMounted(async () => {
  console.log('[AuthCallback] Mounted, checking auth state...');
  
  // Wait a moment for Clerk to initialize
  await new Promise(resolve => setTimeout(resolve, 500));
  
  if (!isSignedIn.value) {
    console.log('[AuthCallback] User not signed in, redirecting to login');
    router.push('/clerk-login');
    return;
  }

  console.log('[AuthCallback] User is signed in, loading account...');
  
  try {
    // Load account data from backend
    const account = await authStore.loadClerkAccount(getToken);
    
    if (!account) {
      console.log('[AuthCallback] No account found, redirecting to login');
      router.push('/clerk-login');
      return;
    }

    const accountStatus = account.status;
    const userRole = account.roleDesignation;
    
    console.log('[AuthCallback] Account loaded:', { status: accountStatus, role: userRole });

    // Redirect based on account status and role
    if (accountStatus === 'pending') {
      router.push('/request-pending');
    } else if (accountStatus === 'approved') {
      if (userRole === 'ROLE_ADMIN') {
        router.push('/admin/dashboard');
      } else if (userRole === 'ROLE_FACULTY') {
        router.push('/borrower/my-reservations'); // Faculty uses borrower dashboard for now
      } else {
        router.push('/borrower/my-reservations');
      }
    } else {
      console.log('[AuthCallback] Account not approved:', accountStatus);
      router.push('/clerk-login');
    }
  } catch (error) {
    console.error('[AuthCallback] Error during authentication:', error);
    router.push('/clerk-login');
  }
});
</script>

<style scoped>
.auth-callback-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg, #0a783c 0%, #086332 100%);
}

.loading-content {
  text-align: center;
  color: white;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-content p {
  font-size: 1.1rem;
  margin: 0;
}
</style>
