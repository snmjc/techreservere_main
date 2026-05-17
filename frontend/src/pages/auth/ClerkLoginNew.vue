<!-- ===== Clerk Login Page - Exact Design Match ===== -->
<template>
  <div class="login-wrapper">
    <!-- Top Bar -->
    <div class="top-bar">
      <span class="page-title">Login Page Administrator - 1A</span>
    </div>

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

    <!-- Right Panel - Login Form -->
    <div class="right-panel">
      <div class="right-content">
        <!-- Clerk Sign In Widget -->
        <div id="clerk-signin" class="clerk-container"></div>

        <!-- Fallback Form (shown if Clerk doesn't load) -->
        <div v-if="showForm" class="fallback-form">
          <h2 class="welcome-heading">Welcome!</h2>

          <!-- Username/Email Field -->
          <div class="form-group">
            <label for="email">Username or Email</label>
            <input id="email" type="email" placeholder="jdecruz" class="form-input" />
          </div>

          <!-- Password Field -->
          <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" placeholder="••••••••••••••••" class="form-input" />
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" />
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot password?</a>
          </div>

          <!-- Sign In Button -->
          <button class="sign-in-btn" @click="handleSignIn">Sign in</button>

          <!-- Sign Up Link -->
          <div class="signup-prompt">
            <p>Don't have an account? <a href="#" class="signup-link">Sign up</a></p>
          </div>

          <!-- Footer -->
          <div class="login-footer">
            <p>© 2025 TECHRESERVE. DATABASE MANAGEMENT</p>
          </div>
        </div>
      </div>

      <!-- Background Pattern -->
      <div class="background-pattern"></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const showForm = ref(true);

onMounted(() => {
  // Initialize Clerk using the global window.Clerk object
  const clerkPublishableKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY;
  
  if (!clerkPublishableKey) {
    console.error('Clerk publishable key not found');
    return;
  }

  // Load Clerk script dynamically
  const script = document.createElement('script');
  script.src = 'https://cdn.clerk.com/clerk.js';
  script.async = true;
  script.onload = () => {
    if (window.Clerk) {
      window.Clerk.load({
        publishableKey: clerkPublishableKey
      }).then(() => {
        const signInElement = document.getElementById('clerk-signin');
        if (signInElement) {
          window.Clerk.mountSignIn(signInElement, {
            redirectUrl: '/admin/dashboard',
            appearance: {
              baseTheme: 'light',
              elements: {
                rootBox: 'clerk-root-box',
                card: 'clerk-signin-card',
                headerTitle: 'clerk-header-title',
                headerSubtitle: 'clerk-header-subtitle',
                socialButtonsBlockButton: 'clerk-social-btn',
                formButtonPrimary: 'clerk-primary-btn',
                formFieldInput: 'clerk-form-input',
                footerActionLink: 'clerk-footer-link'
              }
            }
          });
          showForm.value = false;
        }
      }).catch((error) => {
        console.error('Clerk load error:', error);
      });
    }
  };
  script.onerror = () => {
    console.error('Failed to load Clerk script');
  };
  document.head.appendChild(script);
});

const handleSignIn = () => {
  // Handle manual sign in if needed
  router.push('/admin/dashboard');
};
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.login-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ===== TOP BAR ===== */
.top-bar {
  background-color: #2a2a2a;
  color: #999;
  padding: 0.75rem 2rem;
  font-size: 0.85rem;
  border-bottom: 1px solid #1a1a1a;
}

.page-title {
  color: #999;
}

/* ===== MAIN CONTENT AREA ===== */
.login-wrapper > div:not(.top-bar) {
  display: flex;
  flex: 1;
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

/* ===== CLERK CONTAINER ===== */
.clerk-container {
  width: 100%;
  min-height: 400px;
}

:deep(.cl-root) {
  width: 100%;
}

:deep(.cl-card) {
  border: none;
  border-radius: 8px;
  box-shadow: none;
  background: transparent;
}

:deep(.cl-headerTitle) {
  display: none;
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
}

:deep(.cl-formFieldInput:focus) {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
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
}

:deep(.cl-button:hover) {
  background-color: #145a30;
}

:deep(.cl-socialButtonsBlockButton) {
  border: 1px solid #ddd;
  border-radius: 6px;
  background-color: white;
}

:deep(.cl-socialButtonsBlockButton:hover) {
  border-color: #1a6e3a;
  background-color: #f9f9f9;
}

/* ===== FALLBACK FORM ===== */
.fallback-form {
  width: 100%;
}

/* ===== FORM STYLING ===== */
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

.sign-in-btn:hover {
  background-color: #145a30;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(26, 110, 58, 0.3);
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
    width: 120px;
    height: 120px;
  }

  .brand-title {
    font-size: 2.4rem;
  }

  .tagline {
    font-size: 1.1rem;
  }

  .welcome-heading {
    font-size: 2rem;
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
    width: 100px;
    height: 100px;
  }

  .brand-title {
    font-size: 2rem;
  }

  .tagline {
    font-size: 1rem;
  }

  .welcome-heading {
    font-size: 1.75rem;
  }

  .right-content {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .left-panel,
  .right-panel {
    padding: 2rem 1.5rem;
  }

  .logo-image {
    width: 80px;
    height: 80px;
    margin-bottom: 1rem;
  }

  .brand-title {
    font-size: 1.75rem;
    margin-bottom: 1rem;
  }

  .tagline {
    font-size: 0.95rem;
    margin-bottom: 1rem;
  }

  .description {
    font-size: 0.85rem;
  }

  .welcome-heading {
    font-size: 1.5rem;
    margin-bottom: 2rem;
  }
}
</style>
