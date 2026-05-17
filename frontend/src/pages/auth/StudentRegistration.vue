<!-- ===== Student Registration Page ===== -->
<template>
  <div class="student-registration-container">
    <div class="student-registration-card">
      <h1 class="student-registration-title">Student Registration</h1>
      
      <!-- Error Messages -->
      <div v-if="errorMessage" class="student-registration-error">
        {{ errorMessage }}
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="student-registration-success">
        {{ successMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="student-registration-form">
        <!-- Account Type -->
        <div class="student-registration-form-group">
          <label for="accountType" class="student-registration-label">Account Type</label>
          <select
            id="accountType"
            v-model="formData.accountType"
            class="student-registration-input"
            disabled
          >
            <option value="User">User</option>
          </select>
        </div>

        <!-- First Name -->
        <div class="student-registration-form-group">
          <label for="firstName" class="student-registration-label">First Name *</label>
          <input
            id="firstName"
            v-model="formData.firstName"
            type="text"
            class="student-registration-input"
            placeholder="Enter your first name"
            @blur="validateField('firstName')"
          />
          <span v-if="errors.firstName" class="student-registration-error-text">{{ errors.firstName }}</span>
        </div>

        <!-- Last Name -->
        <div class="student-registration-form-group">
          <label for="lastName" class="student-registration-label">Last Name *</label>
          <input
            id="lastName"
            v-model="formData.lastName"
            type="text"
            class="student-registration-input"
            placeholder="Enter your last name"
            @blur="validateField('lastName')"
          />
          <span v-if="errors.lastName" class="student-registration-error-text">{{ errors.lastName }}</span>
        </div>

        <!-- ID Number -->
        <div class="student-registration-form-group">
          <label for="idNumber" class="student-registration-label">ID Number (e.g. 2023*****) *</label>
          <input
            id="idNumber"
            v-model="formData.idNumber"
            type="text"
            class="student-registration-input"
            placeholder="e.g. 2023-00001"
            @blur="validateField('idNumber')"
          />
          <span v-if="errors.idNumber" class="student-registration-error-text">{{ errors.idNumber }}</span>
        </div>

        <!-- FIT Email Address -->
        <div class="student-registration-form-group">
          <label for="email" class="student-registration-label">FIT Email Address (@fit.edu.ph) *</label>
          <input
            id="email"
            v-model="formData.email"
            type="email"
            class="student-registration-input"
            placeholder="your.email@fit.edu.ph"
            @blur="validateField('email')"
          />
          <span v-if="errors.email" class="student-registration-error-text">{{ errors.email }}</span>
        </div>

        <!-- Department -->
        <div class="student-registration-form-group">
          <label for="department" class="student-registration-label">Department *</label>
          <select
            id="department"
            v-model="formData.department"
            class="student-registration-input"
            @blur="validateField('department')"
          >
            <option value="">Select Department</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Engineering">Engineering</option>
            <option value="Business Administration">Business Administration</option>
            <option value="Liberal Arts">Liberal Arts</option>
            <option value="Hospitality Management">Hospitality Management</option>
          </select>
          <span v-if="errors.department" class="student-registration-error-text">{{ errors.department }}</span>
        </div>

        <!-- Role -->
        <div class="student-registration-form-group">
          <label for="role" class="student-registration-label">Role *</label>
          <select
            id="role"
            v-model="formData.role"
            class="student-registration-input"
            @blur="validateField('role')"
          >
            <option value="">Select Role</option>
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
          </select>
          <span v-if="errors.role" class="student-registration-error-text">{{ errors.role }}</span>
        </div>

        <!-- Password -->
        <div class="student-registration-form-group">
          <label for="password" class="student-registration-label">Password *</label>
          <input
            id="password"
            v-model="formData.password"
            type="password"
            class="student-registration-input"
            placeholder="Enter your password"
            @blur="validateField('password')"
          />
          <span v-if="errors.password" class="student-registration-error-text">{{ errors.password }}</span>
        </div>

        <!-- Confirm Password -->
        <div class="student-registration-form-group">
          <label for="confirmPassword" class="student-registration-label">Confirm Password *</label>
          <input
            id="confirmPassword"
            v-model="formData.confirmPassword"
            type="password"
            class="student-registration-input"
            placeholder="Confirm your password"
            @blur="validateField('confirmPassword')"
          />
          <span v-if="errors.confirmPassword" class="student-registration-error-text">{{ errors.confirmPassword }}</span>
        </div>

        <!-- Consent Checkbox -->
        <div class="student-registration-form-group student-registration-checkbox-group">
          <input
            id="consent"
            v-model="formData.consent"
            type="checkbox"
            class="student-registration-checkbox"
            @change="validateField('consent')"
          />
          <label for="consent" class="student-registration-checkbox-label">
            I agree to the Terms and Conditions and Privacy Policy *
          </label>
          <span v-if="errors.consent" class="student-registration-error-text">{{ errors.consent }}</span>
        </div>

        <!-- Register Button -->
        <button
          type="submit"
          class="student-registration-button"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Registering...' : 'Register' }}
        </button>

        <!-- Login Link -->
        <p class="student-registration-login-link">
          Already have an account? <router-link to="/login">Login here</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { registerRequest } from '@/modules/authentication/services/authenticationService.js';

const router = useRouter();
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const formData = ref({
  accountType: 'User',
  firstName: '',
  lastName: '',
  idNumber: '',
  email: '',
  department: '',
  role: '',
  password: '',
  confirmPassword: '',
  consent: false
});

const errors = ref({
  firstName: '',
  lastName: '',
  idNumber: '',
  email: '',
  department: '',
  role: '',
  password: '',
  confirmPassword: '',
  consent: ''
});

/**
 * @function validateField
 * @description Validates individual form fields
 * @param {string} fieldName - Name of the field to validate
 */
function validateField(fieldName) {
  errors.value[fieldName] = '';

  switch (fieldName) {
    case 'firstName':
      if (!formData.value.firstName.trim()) {
        errors.value.firstName = 'First name is required';
      } else if (formData.value.firstName.trim().length < 2) {
        errors.value.firstName = 'First name must be at least 2 characters';
      }
      break;

    case 'lastName':
      if (!formData.value.lastName.trim()) {
        errors.value.lastName = 'Last name is required';
      } else if (formData.value.lastName.trim().length < 2) {
        errors.value.lastName = 'Last name must be at least 2 characters';
      }
      break;

    case 'idNumber':
      if (!formData.value.idNumber.trim()) {
        errors.value.idNumber = 'ID number is required';
      } else if (!/^\d{4}-?\d{5}$/.test(formData.value.idNumber)) {
        errors.value.idNumber = 'ID number must be in format: 2023-00001 or 202300001';
      }
      break;

    case 'email':
      if (!formData.value.email.trim()) {
        errors.value.email = 'Email is required';
      } else if (!formData.value.email.includes('@fit.edu.ph')) {
        errors.value.email = 'Only @fit.edu.ph email addresses are accepted';
      } else if (!/^[a-zA-Z0-9._-]+@fit\.edu\.ph$/.test(formData.value.email)) {
        errors.value.email = 'Please enter a valid FIT email address';
      }
      break;

    case 'department':
      if (!formData.value.department) {
        errors.value.department = 'Department is required';
      }
      break;

    case 'role':
      if (!formData.value.role) {
        errors.value.role = 'Role is required';
      }
      break;

    case 'password':
      if (!formData.value.password) {
        errors.value.password = 'Password is required';
      } else if (formData.value.password.length < 8) {
        errors.value.password = 'Password must be at least 8 characters';
      } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(formData.value.password)) {
        errors.value.password = 'Password must contain uppercase, lowercase, and numbers';
      }
      break;

    case 'confirmPassword':
      if (!formData.value.confirmPassword) {
        errors.value.confirmPassword = 'Please confirm your password';
      } else if (formData.value.password !== formData.value.confirmPassword) {
        errors.value.confirmPassword = 'Passwords do not match';
      }
      break;

    case 'consent':
      if (!formData.value.consent) {
        errors.value.consent = 'You must agree to the terms and conditions';
      }
      break;
  }
}

/**
 * @function validateForm
 * @description Validates entire form before submission
 * @returns {boolean} - True if form is valid
 */
function validateForm() {
  errorMessage.value = '';
  let isValid = true;

  const fieldsToValidate = [
    'firstName',
    'lastName',
    'idNumber',
    'email',
    'department',
    'role',
    'password',
    'confirmPassword',
    'consent'
  ];

  fieldsToValidate.forEach(field => {
    validateField(field);
    if (errors.value[field]) {
      isValid = false;
    }
  });

  return isValid;
}

/**
 * @function handleRegister
 * @description Handles form submission and registration
 */
async function handleRegister() {
  if (!validateForm()) {
    errorMessage.value = 'Please fix the errors above before registering';
    return;
  }

  isLoading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const response = await registerRequest({
      firstName: formData.value.firstName,
      lastName: formData.value.lastName,
      emailAddress: formData.value.email,
      passwordText: formData.value.password
    });

    successMessage.value = 'Registration successful! Redirecting to login...';
    
    // Reset form
    formData.value = {
      accountType: 'User',
      firstName: '',
      lastName: '',
      idNumber: '',
      email: '',
      department: '',
      role: '',
      password: '',
      confirmPassword: '',
      consent: false
    };

    // Redirect to login after 2 seconds
    setTimeout(() => {
      router.push('/login');
    }, 2000);
  } catch (error) {
    errorMessage.value = error.message || 'Registration failed. Please try again.';
    console.error('Registration error:', error);
  } finally {
    isLoading.value = false;
  }
}
</script>

<style scoped>
@import './css/StudentRegistration.css';
</style>
