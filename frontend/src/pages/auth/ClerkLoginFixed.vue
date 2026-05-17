<!-- ===== Clerk Login Page - Fixed ===== -->
<template>
  <div class="login-wrapper">
    <!-- Main Content -->
    <div class="login-content">
      <!-- Left Panel - Green Branding -->
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

      <!-- Right Panel - Clerk Widget -->
      <div class="right-panel">
        <div class="right-content">
          <!-- Clerk will mount here -->
          <div id="clerk-sign-in"></div>
        </div>

        <!-- Background Pattern -->
        <div class="background-pattern"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  // Initialize Clerk after component is mounted
  initializeClerk();
});

function initializeClerk() {
  // Try to get the publishable key from environment
  let publishableKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY;
  
  // Fallback to the test key if the primary one is not set
  if (!publishableKey || publishableKey === 'your_clerk_publishable_key_here') {
    publishableKey = 'pk_test_cHJpbWFyeS1yb29zdGVyLTgwLmNsZXJrLmFjY291bnRzLmRldiQ';
  }
  
  if (!publishableKey) {
    console.error('Clerk publishable key is missing');
    return;
  }
  
  console.log('Using Clerk key:', publishableKey.substring(0, 20) + '...');

  // Check if Clerk script is already loaded
  if (window.Clerk) {
    mountClerkWidget(publishableKey);
    return;
  }

  // Load Clerk script
  const script = document.createElement('script');
  script.src = 'https://cdn.clerk.com/clerk.js';
  script.async = true;
  
  script.onload = () => {
    console.log('Clerk script loaded');
    mountClerkWidget(publishableKey);
  };

  script.onerror = () => {
    console.error('Failed to load Clerk script from CDN');
  };

  document.head.appendChild(script);
}

function mountClerkWidget(publishableKey) {
  if (!window.Clerk) {
    console.error('Clerk is not available');
    return;
  }

  window.Clerk.load({
    publishableKey: publishableKey
  }).then(() => {
    console.log('Clerk loaded successfully');
    const element = document.getElementById('clerk-sign-in');
    if (element) {
      console.log('Mounting Clerk SignIn widget');
      window.Clerk.mountSignIn(element, {
        redirectUrl: '/admin/dashboard',
        appearance: {
          baseTheme: 'light',
          variables: {
            colorPrimary: '#1a6e3a',
            colorInputBackground: '#ffffff',
            colorInputBorder: '#ddd'
          }
        }
      });
    } else {
      console.error('clerk-sign-in element not found');
    }
  }).catch(err => {
    console.error('Clerk initialization error:', err);
  });
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  height: 100%;
  width: 100%;
}

.login-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ===== LOGIN CONTENT ===== */
.login-content {
  display: flex;
  flex: 1;
  width: 100%;
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
  max-width: 420px;
}

#clerk-sign-in {
  width: 100%;
}

/* ===== CLERK STYLING ===== */
:deep(.cl-root) {
  width: 100%;
}

:deep(.cl-card) {
  border: none;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

:deep(.cl-headerTitle) {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 1.5rem;
}

:deep(.cl-headerSubtitle) {
  display: none;
}

:deep(.cl-formFieldInput) {
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 0.75rem 1rem;
  font-size: 0.9rem;
  background-color: white;
  margin-bottom: 1rem;
}

:deep(.cl-formFieldInput:focus) {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

:deep(.cl-formFieldInput::placeholder) {
  color: #999;
}

:deep(.cl-button) {
  background-color: #1a6e3a;
  color: white;
  border: none;
  border-radius: 24px;
  padding: 0.875rem 2rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
  margin-top: 1rem;
}

:deep(.cl-button:hover) {
  background-color: #145a30;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(26, 110, 58, 0.3);
}

:deep(.cl-socialButtonsBlockButton) {
  border: 1px solid #ddd;
  border-radius: 6px;
  background-color: white;
  margin: 0.5rem 0;
}

:deep(.cl-socialButtonsBlockButton:hover) {
  border-color: #1a6e3a;
  background-color: #f9f9f9;
}

:deep(.cl-dividerLine) {
  background-color: #ddd;
}

:deep(.cl-dividerText) {
  color: #999;
  font-size: 0.85rem;
}

:deep(.cl-footerActionLink) {
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
}

:deep(.cl-footerActionLink:hover) {
  color: #145a30;
  text-decoration: underline;
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
  .login-content {
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

  .right-content {
    max-width: 100%;
  }
}
</style>
