<!-- ===== Custom Sign Up Page ===== -->
<template>
  <div class="signup-container">
    <div class="signup-card">
      <!-- Header -->
      <div class="signup-header">
        <div class="logo">◆ TechReserve</div>
        <h1>Create Your Account</h1>
        <p>Join TechReserve to manage your reservations</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSignUp" class="signup-form">
        <!-- Full Name -->
        <div class="form-group">
          <label for="fullName">Full Name *</label>
          <input
            id="fullName"
            v-model="formData.fullName"
            type="text"
            placeholder="Enter your full name"
            required
          />
          <span v-if="errors.fullName" class="error-message">{{ errors.fullName }}</span>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email Address *</label>
          <input
            id="email"
            v-model="formData.email"
            type="email"
            placeholder="Enter your email"
            required
          />
          <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input
            id="phone"
            v-model="formData.phone"
            type="tel"
            placeholder="Enter your phone number"
          />
        </div>

        <!-- Department -->
        <div class="form-group">
          <label for="department">Department *</label>
          <input
            id="department"
            v-model="formData.department"
            type="text"
            placeholder="Enter your department"
            required
          />
        </div>

        <!-- Organization -->
        <div class="form-group">
          <label for="organization">Organization *</label>
          <input
            id="organization"
            v-model="formData.organization"
            type="text"
            placeholder="Enter your organization"
            required
          />
        </div>

        <!-- Terms & Conditions -->
        <div class="form-group checkbox">
          <input
            id="terms"
            v-model="formData.acceptTerms"
            type="checkbox"
            required
          />
          <label for="terms">
            I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
          </label>
          <span v-if="errors.acceptTerms" class="error-message">{{ errors.acceptTerms }}</span>
        </div>

        <!-- Error Message -->
        <div v-if="errors.submit" class="error-box">
          {{ errors.submit }}
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="success-box">
          {{ successMessage }}
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="submit-btn"
          :disabled="isLoading"
        >
          <span v-if="isLoading">Creating Account...</span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <!-- Login Link -->
      <div class="login-link">
        Already have an account? <router-link to="/login">Log in here</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { pendingUserService } from '@/services/pendingUserApi';

const router = useRouter();

const formData = ref({
  fullName: '',
  email: '',
  phone: '',
  department: '',
  organization: '',
  acceptTerms: false
});

const errors = ref({});
const isLoading = ref(false);
const successMessage = ref('');

const validateForm = () => {
  errors.value = {};

  if (!formData.value.fullName.trim()) {
    errors.value.fullName = 'Full name is required';
  }

  if (!formData.value.email.trim()) {
    errors.value.email = 'Email is required';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
    errors.value.email = 'Please enter a valid email address';
  }

  if (!formData.value.department.trim()) {
    errors.value.department = 'Department is required';
  }

  if (!formData.value.organization.trim()) {
    errors.value.organization = 'Organization is required';
  }

  if (!formData.value.acceptTerms) {
    errors.value.acceptTerms = 'You must accept the terms and conditions';
  }

  return Object.keys(errors.value).length === 0;
};

const handleSignUp = async () => {
  if (!validateForm()) {
    return;
  }

  isLoading.value = true;
  errors.value = {};
  successMessage.value = '';

  try {
    // Register user via backend API
    const result = await pendingUserService.registerUser({
      email: formData.value.email,
      fullName: formData.value.fullName,
      phone: formData.value.phone,
      department: formData.value.department,
      organization: formData.value.organization
    });

    if (!result.success) {
      errors.value.submit = result.error || 'Failed to create account. Please try again.';
      isLoading.value = false;
      return;
    }

    // Show success message
    successMessage.value = 'Account created successfully! Your account is pending approval. You will receive an email once it has been reviewed.';

    // Reset form
    formData.value = {
      fullName: '',
      email: '',
      phone: '',
      department: '',
      organization: '',
      acceptTerms: false
    };

    // Redirect to login after 3 seconds
    setTimeout(() => {
      router.push('/login');
    }, 3000);
  } catch (error) {
    errors.value.submit = error.message || 'An unexpected error occurred. Please try again.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.signup-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.signup-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 3rem;
  width: 100%;
  max-width: 450px;
}

.signup-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a6e3a;
  margin-bottom: 1rem;
}

.signup-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
  margin: 0.5rem 0;
}

.signup-header p {
  font-size: 0.95rem;
  color: #666;
  margin: 0.5rem 0 0 0;
}

.signup-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"] {
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus {
  outline: none;
  border-color: #1a6e3a;
  box-shadow: 0 0 0 3px rgba(26, 110, 58, 0.1);
}

.form-group.checkbox {
  flex-direction: row;
  align-items: flex-start;
  gap: 0.75rem;
}

.form-group.checkbox input[type="checkbox"] {
  margin-top: 0.25rem;
  cursor: pointer;
  width: 18px;
  height: 18px;
}

.form-group.checkbox label {
  font-size: 0.9rem;
  font-weight: 500;
  margin: 0;
  cursor: pointer;
}

.form-group.checkbox a {
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
}

.form-group.checkbox a:hover {
  text-decoration: underline;
}

.error-message {
  font-size: 0.85rem;
  color: #d32f2f;
  margin-top: -0.25rem;
}

.error-box {
  padding: 1rem;
  background-color: #ffebee;
  border: 1px solid #ef5350;
  border-radius: 6px;
  color: #c62828;
  font-size: 0.9rem;
  text-align: center;
}

.success-box {
  padding: 1rem;
  background-color: #e8f5e9;
  border: 1px solid #66bb6a;
  border-radius: 6px;
  color: #2e7d32;
  font-size: 0.9rem;
  text-align: center;
}

.submit-btn {
  padding: 0.875rem 1.5rem;
  background-color: #1a6e3a;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s ease;
  margin-top: 1rem;
}

.submit-btn:hover:not(:disabled) {
  background-color: #145a30;
}

.submit-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.login-link {
  text-align: center;
  font-size: 0.9rem;
  color: #666;
  margin-top: 1.5rem;
}

.login-link a {
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
}

.login-link a:hover {
  text-decoration: underline;
}

@media (max-width: 600px) {
  .signup-card {
    padding: 2rem;
  }

  .signup-header h1 {
    font-size: 1.5rem;
  }
}
</style>
