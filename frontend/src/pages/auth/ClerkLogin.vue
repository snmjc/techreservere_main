<!-- ===== Clerk Login Page ===== -->
<template>
  <div class="login-wrapper">
    <!-- Left Panel - Branding -->
    <div class="left-panel">
      <div class="left-content">
        <img src="@/assets/TechReserve_LogoA.png" alt="TechReserve Logo" class="logo-image" />
        
        <h1 class="brand-title">
          <span class="tech-text">Tech</span><span class="reserve-text">Reserve</span>
        </h1>

        <h2 class="tagline">Analytics-Driven Equipment Readiness and Reservation System</h2>

        <p class="description">Supporting efficient resource coordination and institutional resource-sharing in FEU Institute of Technology</p>
      </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="right-panel">
      <div class="right-content">
        <h2 class="welcome-heading">Welcome!</h2>

        <!-- Clerk Sign In (Modal) -->
        <div class="login-form">
          <SignInButton mode="modal">
            <button class="sign-in-btn">Sign in with Clerk</button>
          </SignInButton>
        </div>

        <!-- Sign Up Link -->
        <div class="signup-prompt">
          <p>Don't have an account? <router-link to="/custom-signup" class="signup-link">Sign up</router-link></p>
        </div>

        <!-- Footer -->
        <div class="login-footer">
          <p>© 2025 TECHRESERVE. DATABASE MANAGEMENT</p>
        </div>
      </div>

      <!-- Background Pattern -->
      <div class="background-pattern"></div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { SignInButton, useUser, useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
const { isSignedIn, user } = useUser();
const { getToken } = useAuth();
const authStore = useAuthenticationStore();

// Watch for successful Clerk sign-in, sync auth store, and redirect
watch(isSignedIn, async (signedIn) => {
  if (signedIn && user.value) {
    // Get Clerk session token
    const token = await getToken.value();

    // Sync Clerk user data into the auth store using the new setClerkAuth function
    authStore.setClerkAuth(token, {
      accountIdentifier: user.value.id,
      firstName: user.value.firstName || '',
      lastName: user.value.lastName || '',
      emailAddress: user.value.primaryEmailAddress?.emailAddress || '',
      roleDesignation: user.value.publicMetadata?.role || 'ROLE_BORROWER',
      contactNumber: user.value.publicMetadata?.contactNumber || '',
      isActive: true,
    });

    // Redirect based on role
    const role = user.value.publicMetadata?.role;
    if (role === 'ROLE_ADMIN') {
      router.push({ name: 'adminDashboardPage' });
    } else {
      router.push({ name: 'borrowerMyReservationsPage' });
    }
  }
}, { immediate: true });
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.login-wrapper {
  display: flex;
  min-height: 100vh;
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ===== LEFT PANEL ===== */
.left-panel {
  width: 50%;
  background: linear-gradient(135deg, #1a6e3a 0%, #0f5a2a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4rem 3rem;
  position: relative;
  overflow: hidden;
}

.left-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    linear-gradient(45deg, rgba(255,255,255,0.03) 25%, transparent 25%),
    linear-gradient(-45deg, rgba(255,255,255,0.03) 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.03) 75%),
    linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.03) 75%);
  background-size: 60px 60px;
  background-position: 0 0, 0 30px, 30px -30px, -30px 0px;
  opacity: 0.5;
}

.left-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
  max-width: 300px;
}

.logo-image {
  width: 120px;
  height: 120px;
  margin-bottom: 1.5rem;
  filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
}

.brand-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.tech-text {
  color: white;
}

.reserve-text {
  color: #fbbf24;
}

.tagline {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  line-height: 1.4;
  color: white;
}

.description {
  font-size: 0.85rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
}

/* ===== RIGHT PANEL ===== */
.right-panel {
  width: 50%;
  background: linear-gradient(135deg, #f0f0f0 0%, #e5e5e5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  position: relative;
  overflow: hidden;
}

.background-pattern {
  position: absolute;
  top: -150px;
  right: -150px;
  width: 500px;
  height: 500px;
  background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pattern" width="20" height="20" patternUnits="userSpaceOnUse"><text x="10" y="15" font-size="10" fill="rgba(0,0,0,0.06)" text-anchor="middle">TECHRESERVE</text></pattern></defs><rect width="100" height="100" fill="url(%23pattern)"/></svg>');
  background-size: 200px 200px;
  opacity: 0.5;
  border-radius: 50%;
}

.right-content {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 380px;
}

.welcome-heading {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 2rem;
  text-align: center;
}

/* ===== FORM STYLING ===== */
.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
  background-color: white;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.form-input::placeholder {
  color: #999;
}

/* ===== FORM OPTIONS ===== */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: #333;
  font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.forgot-password {
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.forgot-password:hover {
  color: #145a30;
  text-decoration: underline;
}

/* ===== SIGN IN BUTTON ===== */
.sign-in-btn {
  width: 100%;
  padding: 0.875rem 2rem;
  background-color: #1a6e3a;
  color: white;
  border: none;
  border-radius: 24px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 1.5rem;
}

.sign-in-btn:hover:not(:disabled) {
  background-color: #145a30;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(26, 110, 58, 0.3);
}

.sign-in-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ===== SIGNUP SECTION ===== */
.signup-prompt {
  text-align: center;
  margin: 1rem 0;
  font-size: 0.85rem;
  color: #666;
}

.signup-link {
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.signup-link:hover {
  color: #145a30;
  text-decoration: underline;
}

/* ===== FOOTER ===== */
.login-footer {
  text-align: center;
  font-size: 0.75rem;
  color: #999;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #ddd;
}

.login-footer p {
  margin: 0;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .left-panel {
    width: 45%;
    padding: 3rem 2rem;
  }

  .right-panel {
    width: 55%;
    padding: 3rem 2rem;
  }

  .logo-image {
    width: 100px;
    height: 100px;
  }

  .brand-title {
    font-size: 2rem;
  }

  .tagline {
    font-size: 1rem;
  }
}

@media (max-width: 768px) {
  .login-wrapper {
    flex-direction: column;
  }

  .left-panel {
    width: 100%;
    padding: 3rem 2rem;
    min-height: 50vh;
  }

  .right-panel {
    width: 100%;
    padding: 3rem 2rem;
    min-height: 50vh;
  }

  .logo-image {
    width: 80px;
    height: 80px;
  }

  .brand-title {
    font-size: 1.75rem;
  }

  .tagline {
    font-size: 0.95rem;
  }

  .welcome-heading {
    font-size: 1.5rem;
  }
}
</style>
