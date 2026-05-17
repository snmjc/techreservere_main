<!-- ===== AI GENERATED: SignUpPage ===== -->
<template>
  <div class="signup-page-wrapper">
    <!-- Left Panel: Branding -->
    <section class="signup-page-branding-panel">
      <div class="signup-page-branding-overlay"></div>
      <div class="signup-page-branding-content">
        <img
          src="@/assets/TechReserve_LogoB.png"
          alt="TechReserve Logo"
          class="signup-page-logo"
        />
        <h1 class="signup-page-brand-title">
          <span class="signup-page-brand-tech">Tech</span><span class="signup-page-brand-reserve">Reserve</span>
        </h1>
        <h2 class="signup-page-brand-subtitle">
          Analytics-Driven Equipment Readiness and Reservation System
        </h2>
        <p class="signup-page-brand-description">
          Supporting efficient equipment coordination and institutional
          resource planning at <strong>FEU Institute of Technology</strong>.
        </p>
      </div>
    </section>

    <!-- Right Panel: Clerk Sign Up -->
    <section class="signup-page-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="signup-page-form-watermark"
      />
      <div class="signup-page-form-content">
        <div class="clerk-signup-container">
          <h2 class="signup-form-heading">Sign Up</h2>
          
          <div class="clerk-signup-wrapper">
            <SignUp 
              :afterSignUpUrl="afterSignUpUrl"
              :signInUrl="signInUrl"
              redirectUrl="/request-pending"
            />
          </div>

          <p class="signup-form-login-prompt">
            Already have an account?
            <a class="signup-form-login-link" href="#" @click.prevent="navigateToLogin">Log In</a>
          </p>
        </div>
      </div>
      <footer class="signup-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { SignUp } from '@clerk/vue';
import { useUser, useAuth } from '@clerk/vue';
import './css/SignUp.css';

const router = useRouter();
const { user } = useUser();
const { getToken } = useAuth();

const afterSignUpUrl = '/request-pending';
const signInUrl = '/clerk-login';

// Watch for successful sign-up and save user data to backend
const saveUserToBackend = async () => {
  if (user.value) {
    try {
      const token = await getToken.value();
      
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/users/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          clerkUserId: user.value.id,
          firstName: user.value.firstName || '',
          lastName: user.value.lastName || '',
          emailAddress: user.value.primaryEmailAddress?.emailAddress || '',
          role: 'ROLE_BORROWER',
          contactNumber: user.value.primaryPhoneNumber?.phoneNumber || '',
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        console.error('Failed to save user to backend:', error);
      }
    } catch (error) {
      console.error('Error saving user to backend:', error);
    }
  }
};

// Save user data when user is available
if (user.value) {
  saveUserToBackend();
}

function navigateToLogin() {
  router.push({ name: 'clerkLoginPage' });
}
</script>
