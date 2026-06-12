<!-- ===== AI GENERATED: AuthenticationLoginFormComponent ===== -->
<template>
  <form class="login-form-container" @submit.prevent="handleLoginSubmit">
    <h1 class="login-form-heading">Welcome!</h1>

    <div class="login-form-field-group">
      <label class="login-form-label" for="emailAddressInput">
        Email
      </label>
      <input
        id="emailAddressInput"
        v-model="emailAddressValue"
        type="email"
        class="login-form-input"
        placeholder="jdelacruz@fit.edu.ph"
        autocomplete="email"
        required
      />
    </div>

    <div class="login-form-field-group">
      <label class="login-form-label" for="passwordInput">
        Password
      </label>
      <input
        id="passwordInput"
        v-model="passwordValue"
        :type="passwordVisible ? 'text' : 'password'"
        class="login-form-input"
        placeholder="••••••••••••••••••"
        autocomplete="current-password"
        required
      />
    </div>

    <div class="login-form-options-row">
      <label class="login-form-checkbox-label">
        <input
          v-model="rememberMeChecked"
          type="checkbox"
          class="login-form-checkbox"
        />
        <span>Remember me</span>
      </label>
    </div>

    <button
      type="submit"
      class="login-form-submit-button"
      :disabled="loginSubmitting"
    >
      {{ loginSubmitting ? 'Signing in...' : 'Sign in' }}
    </button>

    <p v-if="loginErrorMessage" class="login-form-error-message">
      {{ loginErrorMessage }}
    </p>

    <p class="login-form-signup-prompt">
      Don't have an account?
      <router-link :to="{ name: 'customSignUpPage' }" class="login-form-signup-link">Sign up</router-link>
    </p>
  </form>
</template>

<script setup>
import { ref } from 'vue';

/**
 * @typedef {Object} AuthenticationLoginFormProps
 * @property {boolean} loginSubmitting - Whether login is in progress
 * @property {string|null} loginErrorMessage - Error message to display
 */
const props = defineProps({
  loginSubmitting: {
    type: Boolean,
    required: false,
    default: false,
  },
  loginErrorMessage: {
    type: String,
    required: false,
    default: null,
  },
});

const emit = defineEmits(['submitLoginCredentials']);

const emailAddressValue = ref('');
const passwordValue = ref('');
const rememberMeChecked = ref(false);
const passwordVisible = ref(false);

/**
 * @function handleLoginSubmit
 * @description Emits login credentials to parent on form submit.
 * @returns {void}
 */
const ALLOWED_EMAIL_DOMAINS = ['@fit.edu.ph', '@feu.edu.ph', '@techreserve.feu.edu.ph'];

function handleLoginSubmit() {
  const trimmedEmail = emailAddressValue.value.trim().toLowerCase();

  if (!trimmedEmail || !passwordValue.value) {
    emit('submitLoginCredentials', {
      usernameOrEmail: '',
      passwordText: '',
      rememberSession: rememberMeChecked.value,
      validationError: 'Please enter both email and password.',
    });
    return;
  }

  const hasAllowedDomain = ALLOWED_EMAIL_DOMAINS.some((domain) => trimmedEmail.endsWith(domain));
  if (!hasAllowedDomain) {
    emit('submitLoginCredentials', {
      usernameOrEmail: '',
      passwordText: '',
      rememberSession: rememberMeChecked.value,
      validationError: 'Email must use an FEU institutional domain (e.g. @fit.edu.ph).',
    });
    return;
  }

  emit('submitLoginCredentials', {
    usernameOrEmail: trimmedEmail,
    passwordText: passwordValue.value,
    rememberSession: rememberMeChecked.value,
  });
}
</script>
