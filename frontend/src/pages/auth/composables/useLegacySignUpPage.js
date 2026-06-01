import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth, useSignUp } from '@clerk/vue';
import { ROUTE_NAMES } from '@/router/routeNames.js';
import { apiUrl } from '@/shared/utils/apiBase.js';

export function useLegacySignUpPage() {
  const router = useRouter();
  const { signUp, isLoaded } = useSignUp();
  const { signOut } = useAuth();

  const showVerification = ref(false);
  const isLoading = ref(false);
  const errorMessage = ref('');
  const verificationCode = ref('');
  const form = ref(createInitialForm());
  const isStudentRole = computed(() => form.value.role === 'ROLE_BORROWER');

  watch(
    () => form.value.role,
    (role) => {
      if (role === 'ROLE_FACULTY') {
        removeFile();
      }
    },
  );

  function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
      form.value.file = file;
    }
  }

  function removeFile() {
    form.value.file = null;
    const input = document.getElementById('supporting-file');
    if (input) {
      input.value = '';
    }
  }

  async function handleRegister() {
    errorMessage.value = '';

    const validationError = validateRegistrationForm(form.value, isLoaded.value);
    if (validationError) {
      errorMessage.value = validationError;
      return;
    }

    isLoading.value = true;
    try {
      const result = await createClerkSignup(signUp.value, form.value);
      await handleSignupResult(result);
    } catch (err) {
      errorMessage.value = resolveClerkErrorMessage(err, 'Registration failed. Please try again.');
    } finally {
      isLoading.value = false;
    }
  }

  async function handleVerification() {
    if (!verificationCode.value) return;

    isLoading.value = true;
    errorMessage.value = '';
    try {
      const result = await signUp.value.attemptEmailAddressVerification({ code: verificationCode.value });
      if (result.status === 'complete') {
        await completeSignup(result.createdUserId);
      }
    } catch (err) {
      errorMessage.value = resolveClerkErrorMessage(err, 'Invalid code. Please try again.');
    } finally {
      isLoading.value = false;
    }
  }

  async function resendCode() {
    try {
      await signUp.value.prepareEmailAddressVerification({ strategy: 'email_code' });
      errorMessage.value = '';
    } catch (err) {
      errorMessage.value = 'Failed to resend code.';
    }
  }

  function navigateToLogin() {
    router.push({ name: ROUTE_NAMES.clerkLogin });
  }

  async function handleSignupResult(result) {
    if (result.status === 'missing_requirements') {
      await signUp.value.prepareEmailAddressVerification({ strategy: 'email_code' });
      showVerification.value = true;
    } else if (result.status === 'complete') {
      await completeSignup(result.createdUserId);
    }
  }

  async function completeSignup(clerkUserId) {
    await saveToPostgres(clerkUserId, form.value);
    await signOut.value();
    router.push({ name: ROUTE_NAMES.clerkLogin });
  }

  return {
    showVerification,
    isLoading,
    errorMessage,
    verificationCode,
    form,
    isStudentRole,
    handleFileChange,
    removeFile,
    handleRegister,
    handleVerification,
    resendCode,
    navigateToLogin,
  };
}

function createInitialForm() {
  return {
    lastName: '',
    firstName: '',
    idNumber: '',
    email: '',
    department: '',
    role: 'ROLE_BORROWER',
    password: '',
    confirmPassword: '',
    agreed: false,
    file: null,
  };
}

function validateRegistrationForm(form, isLoaded) {
  if (form.password !== form.confirmPassword) {
    return 'Passwords do not match.';
  }

  if (!form.agreed) {
    return 'Please agree to the Facilities Office policies.';
  }

  if (!isLoaded) {
    return 'Authentication service not ready. Please try again.';
  }

  return '';
}

function createClerkSignup(signUp, form) {
  return signUp.create({
    emailAddress: form.email,
    password: form.password,
    firstName: form.firstName,
    lastName: form.lastName,
  });
}

async function saveToPostgres(clerkUserId, form) {
  try {
    const response = await fetch(apiUrl('/api/v1/users/register'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        clerkUserId,
        firstName: form.firstName,
        lastName: form.lastName,
        emailAddress: form.email,
        role: form.role,
        contactNumber: form.idNumber,
        department: form.department,
      }),
    });

    if (!response.ok && response.status !== 409) {
      const errorData = await response.json();
      console.error('Failed to save user to PostgreSQL:', errorData);
      throw new Error(errorData.message || 'Failed to save to PostgreSQL');
    }

    const data = await response.json();
    console.log('Account saved to PostgreSQL:', data);
    return data;
  } catch (err) {
    console.error('Error saving to PostgreSQL:', err);
    throw err;
  }
}

function resolveClerkErrorMessage(err, fallbackMessage) {
  return err.errors?.[0]?.longMessage || err.errors?.[0]?.message || fallbackMessage;
}
