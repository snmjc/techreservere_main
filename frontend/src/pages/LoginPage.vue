<!-- ===== AI GENERATED: LoginPage ===== -->
<template>
  <div class="login-page-wrapper">
    <!-- Left Panel: Branding -->
    <section class="login-page-branding-panel">
      <div class="login-page-branding-overlay"></div>
      <div class="login-page-branding-content">
        <img
          src="@/assets/TechReserve_LogoA.png"
          alt="TechReserve Logo"
          class="login-page-logo"
/>
        <h1 class="login-page-brand-title">
          <span class="login-page-brand-tech">Tech</span><span class="login-page-brand-reserve">Reserve</span>
        </h1>
        <h2 class="login-page-brand-subtitle">
          Analytics-Driven Equipment Readiness and Reservation System
        </h2>
        <p class="login-page-brand-description">
          Supporting efficient equipment coordination and institutional
          resource planning at <strong>FEU Institute of Technology</strong>.
        </p>
      </div>
    </section>

    <!-- Right Panel: Login Form -->
    <section class="login-page-form-panel">
      <img
          src="@/assets/FEU_Tech_official_seal.png"
          alt="FEU Tech Seal Watermark"
          class="login-page-form-watermark"
        />
      <div class="login-page-form-content">
        <AuthenticationLoginFormComponent
          :login-submitting="loginSubmitting"
          :login-error-message="loginErrorMessage"
          @submit-login-credentials="handleSubmitLoginCredentials"
        />
      </div>
      <footer class="login-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticationLoginFormComponent from '@/modules/authentication/components/AuthenticationLoginFormComponent.vue';
import './css/loginPage.css';

const loginSubmitting = ref(false);
const loginErrorMessage = ref(null);

/**
 * @function handleSubmitLoginCredentials
 * @description Handles login credential submission from form component.
 * @param {Object} credentialPayload
 * @param {string} credentialPayload.usernameOrEmail
 * @param {string} credentialPayload.passwordText
 * @param {boolean} credentialPayload.rememberSession
 * @returns {void}
 */
function handleSubmitLoginCredentials(credentialPayload) {
  loginSubmitting.value = true;
  loginErrorMessage.value = null;

  // TODO: Wire to authentication composable → service → backend
  console.log('Login submitted:', credentialPayload);

  setTimeout(() => {
    loginSubmitting.value = false;
  }, 1500);
}
</script>
