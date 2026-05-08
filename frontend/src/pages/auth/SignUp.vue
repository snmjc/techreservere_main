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

    <!-- Right Panel: Sign Up Form -->
    <section class="signup-page-form-panel">
      <img
        src="@/assets/FEU_Tech_official_seal.png"
        alt="FEU Tech Seal Watermark"
        class="signup-page-form-watermark"
      />
      <div class="signup-page-form-content">
        <form class="signup-form-container" @submit.prevent="handleSubmitSignUp">
          <h2 class="signup-form-heading">Sign Up</h2>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupLastName">Last Name:</label>
            <input
              id="signupLastName"
              v-model="signUpFormState.lastName"
              type="text"
              class="signup-form-input"
              placeholder="Dela Cruz"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupFirstName">First Name:</label>
            <input
              id="signupFirstName"
              v-model="signUpFormState.firstName"
              type="text"
              class="signup-form-input"
              placeholder="Juan"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupIdNumber">ID Number:</label>
            <input
              id="signupIdNumber"
              v-model="signUpFormState.idNumber"
              type="text"
              class="signup-form-input"
              placeholder="2023*****"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupEmail">FIT Email Address:</label>
            <input
              id="signupEmail"
              v-model="signUpFormState.emailAddress"
              type="email"
              class="signup-form-input"
              placeholder="jdelacruz@fit.edu.ph"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupDepartment">Department:</label>
            <input
              id="signupDepartment"
              v-model="signUpFormState.department"
              type="text"
              class="signup-form-input"
              placeholder="CCSMMA"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupRole">Role:</label>
            <select
              id="signupRole"
              v-model="signUpFormState.role"
              class="signup-form-select"
            >
              <option value="Student">Student</option>
              <option value="Faculty">Faculty</option>
              <option value="Staff">Staff</option>
            </select>
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupPassword">Password:</label>
            <input
              id="signupPassword"
              v-model="signUpFormState.password"
              type="password"
              class="signup-form-input"
              placeholder="••••••••"
            />
          </div>

          <div class="signup-form-row">
            <label class="signup-form-label" for="signupConfirmPassword">Confirm Password:</label>
            <input
              id="signupConfirmPassword"
              v-model="signUpFormState.confirmPassword"
              type="password"
              class="signup-form-input"
              placeholder="••••••••"
            />
          </div>

          <div v-if="signUpFormState.role === 'Student'" class="signup-form-row signup-form-row--document">
            <label class="signup-form-label" for="signupDocument">Supporting Document:</label>
            <div class="signup-form-document-wrapper">
              <input
                id="signupDocument"
                ref="documentInputRef"
                type="file"
                class="signup-form-document-input"
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                @change="handleDocumentSelection"
              />
              <label class="signup-form-document-button" for="signupDocument">
                {{ signUpFormState.documentFile ? 'Change File' : 'Choose File' }}
              </label>
              <span class="signup-form-document-name">
                {{ signUpFormState.documentFile ? signUpFormState.documentFile.name : 'No file selected (e.g. COR, Student ID)' }}
              </span>
            </div>
          </div>

          <div class="signup-form-confirm-row">
            <input
              id="signupConfirmCheck"
              v-model="signUpFormState.confirmedAcknowledgement"
              type="checkbox"
              class="signup-form-checkbox"
            />
            <label class="signup-form-confirm-text" for="signupConfirmCheck">
              I confirm that this account will be used solely for institutional equipment
              reservation purposes and is subject to Facilities Office policies.
            </label>
          </div>

          <button
            type="submit"
            class="signup-form-submit-button"
            :disabled="signUpSubmitting"
          >
            {{ signUpSubmitting ? 'Registering...' : 'Register' }}
          </button>

          <p v-if="signUpErrorMessage" class="signup-form-error-message">
            {{ signUpErrorMessage }}
          </p>

          <p class="signup-form-login-prompt">
            Already have an account?
            <a class="signup-form-login-link" href="#" @click.prevent="navigateToLogin">Log In</a>
          </p>
        </form>
      </div>
      <footer class="signup-page-footer">
        &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
      </footer>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { registerRequest } from '@/modules/authentication/services/authenticationService.js';
import './css/SignUp.css';

const router = useRouter();

const signUpSubmitting = ref(false);
const signUpErrorMessage = ref(null);

const documentInputRef = ref(null);

const signUpFormState = reactive({
  lastName: '',
  firstName: '',
  idNumber: '',
  emailAddress: '',
  department: '',
  role: 'Student',
  password: '',
  confirmPassword: '',
  documentFile: null,
  confirmedAcknowledgement: false,
});

/**
 * @function handleDocumentSelection
 * @description Captures the selected supporting document file.
 * @param {Event} eventObject - file input change event
 * @returns {void}
 */
function handleDocumentSelection(eventObject) {
  const fileList = eventObject.target.files;
  signUpFormState.documentFile = fileList && fileList.length > 0 ? fileList[0] : null;
}

/**
 * @function handleSubmitSignUp
 * @description Validates and submits the sign-up form.
 * @returns {void}
 */
async function handleSubmitSignUp() {
  signUpErrorMessage.value = null;

  if (signUpFormState.password !== signUpFormState.confirmPassword) {
    signUpErrorMessage.value = 'Passwords do not match.';
    return;
  }
  if (!signUpFormState.confirmedAcknowledgement) {
    signUpErrorMessage.value = 'You must confirm the acknowledgement to proceed.';
    return;
  }
  if (signUpFormState.role === 'Student' && !signUpFormState.documentFile) {
    signUpErrorMessage.value = 'Students must upload a supporting document.';
    return;
  }

  signUpSubmitting.value = true;
  try {
    await registerRequest({
      firstName: signUpFormState.firstName,
      lastName: signUpFormState.lastName,
      emailAddress: signUpFormState.emailAddress,
      passwordText: signUpFormState.password,
    });
    router.push({ name: 'loginPage' });
  } catch (error) {
    signUpErrorMessage.value = error.message || 'Registration failed. Please try again.';
  } finally {
    signUpSubmitting.value = false;
  }
}

function navigateToLogin() {
  router.push({ name: 'loginPage' });
}
</script>
