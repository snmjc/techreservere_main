<!-- ===== AI GENERATED: AuthenticationLoginFormComponent ===== -->
<template>
  <form class="login-form-container" @submit.prevent="handleLoginSubmit">
    <h1 class="login-form-heading">Welcome!</h1>

    <div class="login-form-field-group">
      <label class="login-form-label" for="usernameOrEmailInput">
        Username or Email
      </label>
      <input
        id="usernameOrEmailInput"
        v-model="usernameOrEmailValue"
        type="text"
        class="login-form-input"
        placeholder="jdcruz"
        autocomplete="username"
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
      <a href="#" class="login-form-forgot-link">Forgot password?</a>
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
      <a href="#" class="login-form-signup-link">Sign up</a>
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

const usernameOrEmailValue = ref('');
const passwordValue = ref('');
const rememberMeChecked = ref(false);
const passwordVisible = ref(false);

/**
 * @function handleLoginSubmit
 * @description Emits login credentials to parent on form submit.
 * @returns {void}
 */
function handleLoginSubmit() {
  emit('submitLoginCredentials', {
    usernameOrEmail: usernameOrEmailValue.value,
    passwordText: passwordValue.value,
    rememberSession: rememberMeChecked.value,
  });
}
</script>
