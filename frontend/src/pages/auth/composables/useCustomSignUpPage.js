import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSignUp } from '@clerk/vue';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import { apiUrl } from '@/shared/utils/apiBase.js';
import {
  getSupportingFileAcceptValue,
  validateSignupSupportingFile,
} from '../signupSupportingDocumentHelpers.js';

const DEPARTMENT_OPTIONS = Object.freeze([
  'Information Technology',
  'Computer Science',
  'Multimedia and Arts',
  'Civil Engineering',
  'Mechanical Engineering',
  'Computer Engineering',
  'Electronics Engineering',
  'Electrical Engineering',
]);

export function useCustomSignUpPage() {
  const router = useRouter();
  const route = useRoute();
  const { isLoaded: isSignUpLoaded } = useSignUp();
  const formData = ref({
    lastName: '',
    firstName: '',
    idNumber: '',
    fitEmailAddress: '',
    department: '',
    role: 'Student',
    supportingFile: null,
    password: '',
    confirmPassword: '',
    acceptTerms: false,
  });

  const errors = ref({});
  const isLoading = ref(false);
  const successMessage = ref('');
  const awaitingVerification = ref(false);
  const verificationCode = ref('');
  const signupRequestCreated = ref(false);
  const showPassword = ref(false);
  const showConfirmPassword = ref(false);
  const studentSupportingFileInput = ref(null);
  const departmentOptions = DEPARTMENT_OPTIONS;
  const firstErrorMessage = computed(() => Object.values(errors.value)[0] || '');
  const isStudentRole = computed(() => formData.value.role === 'Student');
  const supportingFileAccept = getSupportingFileAcceptValue();
  const invitationTicket = computed(() => String(route.query.__clerk_ticket || '').trim());
  const invitationStatus = computed(() => String(route.query.__clerk_status || '').trim().toLowerCase());
  const isInvitationMode = computed(() => invitationTicket.value !== '' && invitationStatus.value === 'sign_up');
  const invitationCopy = computed(() => (
    isInvitationMode.value
      ? 'Finish setting your password so your invited account can be activated immediately.'
      : 'Use your official @fit.edu.ph or @feutech.edu.ph email and institutional details.'
  ));

  watch(
    () => formData.value.role,
    (role) => {
      if (role === 'Faculty') {
        removeStudentSupportingFile();
      }
    },
  );

  watch(isInvitationMode, (active) => {
    if (active) {
      formData.value.role = 'Faculty';
    }
  }, { immediate: true });

  function openStudentSupportingFile() {
    studentSupportingFileInput.value?.click();
  }

  function handleStudentSupportingFileChange(event) {
    const selectedFile = event.target.files?.[0] || null;
    const validationError = validateSupportingFile(selectedFile);

    if (validationError) {
      errors.value.supportingFile = validationError;
      event.target.value = '';
      formData.value.supportingFile = null;
      return;
    }

    errors.value.supportingFile = '';
    formData.value.supportingFile = selectedFile;
  }

  function removeStudentSupportingFile() {
    formData.value.supportingFile = null;
    if (studentSupportingFileInput.value) {
      studentSupportingFileInput.value.value = '';
    }
  }

  function validateSupportingFile(file) {
    return validateSignupSupportingFile(file);
  }

  async function handleSignUp() {
    if (!validateForm()) return;

    isLoading.value = true;
    errors.value = {};
    successMessage.value = '';

    try {
      if (isInvitationMode.value) {
        await acceptInvitationSignup();
        return;
      }

      if (!signupRequestCreated.value) {
        await createSignupRequest();
      }

      successMessage.value = 'Your request was submitted. Please wait for an administrator to send your Clerk invitation.';
      setTimeout(() => {
        router.push('/request-pending');
      }, 1200);
    } catch (error) {
      errors.value.submit = error?.message || 'Unable to submit signup request. Please try again.';
    } finally {
      isLoading.value = false;
    }
  }

  async function handleVerifyEmail() {
    errors.value.submit = 'Please use the Clerk invitation email sent by the administrator.';
  }

  async function acceptInvitationSignup() {
    if (!isSignUpLoaded.value) {
      throw new Error('Clerk authentication is still loading. Please try the invitation link again.');
    }

    const clerk = await waitForClerk();
    if (!clerk?.client?.signUp || !clerk?.setActive) {
      throw new Error('Clerk authentication is still loading. Please try the invitation link again.');
    }

    const result = await clerk.client.signUp.create({
      strategy: 'ticket',
      ticket: invitationTicket.value,
      password: formData.value.password,
    });

    if (result.status !== 'complete' || !result.createdSessionId) {
      throw new Error('This invitation could not be completed yet. Please check the required details and try again.');
    }

    await clerk.setActive({ session: result.createdSessionId });
    router.replace({ name: ROUTE_NAMES.postLogin });
  }

  async function createSignupRequest() {
    const requestBody = buildSignupRequestBody();
    const multipartForm = new FormData();
    Object.entries(requestBody).forEach(([key, value]) => {
      multipartForm.append(key, value == null ? '' : String(value));
    });

    if (formData.value.supportingFile) {
      multipartForm.append('supportingDocument', formData.value.supportingFile);
    }

    const response = await fetch(apiUrl('/api/v1/users/signup-requests'), {
      method: 'POST',
      body: multipartForm,
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(result.errorMessage || result.message || 'Unable to submit signup request.');
    }

    signupRequestCreated.value = true;
    return result.data;
  }

  function buildSignupRequestBody() {
    return {
      firstName: formData.value.firstName.trim(),
      lastName: formData.value.lastName.trim(),
      emailAddress: formData.value.fitEmailAddress.trim(),
      idNumber: formData.value.idNumber.trim(),
      department: formData.value.department.trim(),
      role: formData.value.role,
      passwordText: formData.value.password,
      confirmPasswordText: formData.value.confirmPassword,
      acceptedPrivacy: formData.value.acceptTerms,
    };
  }

  function validateForm() {
    errors.value = {};
    if (!isInvitationMode.value) {
      validateRequiredName('lastName', 'Last name');
      validateRequiredName('firstName', 'First name');
    }
    if (!isInvitationMode.value) {
      validateRequiredText('idNumber', 'ID number is required.');
      validateRequiredText('department', 'Department is required.');
    }
    validateEmail();
    validatePassword();
    if (!isInvitationMode.value) {
      validateStudentProof();
    }

    if (!formData.value.acceptTerms) {
      errors.value.acceptTerms = 'Please confirm the account purpose policy.';
    }

    return Object.keys(errors.value).length === 0;
  }

  function validateRequiredName(fieldName, label) {
    const value = formData.value[fieldName].trim();
    if (!value) {
      errors.value[fieldName] = `${label} is required.`;
    } else if (!isValidName(value)) {
      errors.value[fieldName] = `${label} must contain letters only. Do not enter email, numbers, or symbols.`;
    }
  }

  function validateRequiredText(fieldName, message) {
    if (!formData.value[fieldName].trim()) {
      errors.value[fieldName] = message;
      return;
    }

    if (fieldName === 'idNumber' && !/^\d{9}$/.test(formData.value[fieldName].trim())) {
      errors.value[fieldName] = 'ID number must be exactly 9 digits.';
    }
  }

  function sanitizeIdNumberField() {
    formData.value.idNumber = String(formData.value.idNumber || '').replace(/\D/g, '').slice(0, 9);
  }

  function validateEmail() {
    if (isInvitationMode.value) {
      return;
    }

    if (!formData.value.fitEmailAddress.trim()) {
      errors.value.fitEmailAddress = 'Institutional email address is required.';
    } else if (!isInstitutionalUserEmail(formData.value.fitEmailAddress)) {
      errors.value.fitEmailAddress = 'Please use a valid @fit.edu.ph or @feutech.edu.ph email address.';
    }
  }

  function validatePassword() {
    if (!formData.value.password) {
      errors.value.password = 'Password is required.';
    } else if (!isStrongPassword(formData.value.password)) {
      errors.value.password = 'Password must be at least 8 characters and include uppercase letters, lowercase letters, and numbers.';
    }

    if (formData.value.password !== formData.value.confirmPassword) {
      errors.value.confirmPassword = 'Passwords do not match.';
    }
  }

  function validateStudentProof() {
    if (!isStudentRole.value) return;

    if (!formData.value.supportingFile) {
      errors.value.supportingFile = 'Proof of enrollment or School ID is required.';
    } else {
      const supportingFileError = validateSupportingFile(formData.value.supportingFile);
      if (supportingFileError) {
        errors.value.supportingFile = supportingFileError;
      }
    }
  }

  return {
    formData,
    departmentOptions,
    isLoading,
    successMessage,
    awaitingVerification,
    verificationCode,
    showPassword,
    showConfirmPassword,
    studentSupportingFileInput,
    firstErrorMessage,
    isStudentRole,
    isInvitationMode,
    invitationCopy,
    supportingFileAccept,
    sanitizeIdNumberField,
    openStudentSupportingFile,
    handleStudentSupportingFileChange,
    removeStudentSupportingFile,
    handleSignUp,
    handleVerifyEmail,
  };
}

function isValidName(value) {
  return /^[A-Za-z][A-Za-z .'-]*$/.test(value.trim());
}

function isInstitutionalUserEmail(value) {
  return /^[^\s@]+@(fit|feutech)\.edu\.ph$/i.test(value.trim());
}

function isStrongPassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(value);
}

function waitForClerk(timeoutMs = 4000) {
  if (window.Clerk?.loaded) {
    return Promise.resolve(window.Clerk);
  }

  return new Promise((resolve) => {
    const startedAt = Date.now();
    const timer = window.setInterval(() => {
      if (window.Clerk?.loaded) {
        window.clearInterval(timer);
        resolve(window.Clerk);
        return;
      }

      if (Date.now() - startedAt >= timeoutMs) {
        window.clearInterval(timer);
        resolve(window.Clerk || null);
      }
    }, 100);
  });
}

