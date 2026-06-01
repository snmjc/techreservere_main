import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiUrl } from '@/shared/utils/apiBase.js';

export function useCustomSignUpPage() {
  const router = useRouter();
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
  const firstErrorMessage = computed(() => Object.values(errors.value)[0] || '');
  const isStudentRole = computed(() => formData.value.role === 'Student');

  watch(
    () => formData.value.role,
    (role) => {
      if (role === 'Faculty') {
        removeStudentSupportingFile();
      }
    },
  );

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

  async function handleSignUp() {
    if (!validateForm()) return;

    isLoading.value = true;
    errors.value = {};
    successMessage.value = '';

    try {
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

  async function createSignupRequest() {
    const supportingDocumentData = await readFileAsDataUrl(formData.value.supportingFile);
    const response = await fetch(apiUrl('/api/v1/users/signup-requests'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(buildSignupRequestBody(supportingDocumentData)),
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(result.errorMessage || result.message || 'Unable to submit signup request.');
    }

    signupRequestCreated.value = true;
    return result.data;
  }

  function buildSignupRequestBody(supportingDocumentData) {
    return {
      firstName: formData.value.firstName.trim(),
      lastName: formData.value.lastName.trim(),
      emailAddress: formData.value.fitEmailAddress.trim(),
      idNumber: formData.value.idNumber.trim(),
      department: formData.value.department.trim(),
      role: formData.value.role,
      supportingDocumentName: isStudentRole.value && formData.value.supportingFile ? formData.value.supportingFile.name : null,
      supportingDocumentMimeType: isStudentRole.value && formData.value.supportingFile ? formData.value.supportingFile.type : null,
      supportingDocumentData,
      passwordText: formData.value.password,
      confirmPasswordText: formData.value.confirmPassword,
      acceptedPrivacy: formData.value.acceptTerms,
    };
  }

  function validateForm() {
    errors.value = {};
    validateRequiredName('lastName', 'Last name');
    validateRequiredName('firstName', 'First name');
    validateRequiredText('idNumber', 'ID number is required.');
    validateRequiredText('department', 'Department is required.');
    validateEmail();
    validatePassword();
    validateStudentProof();

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
    }
  }

  function validateEmail() {
    if (!formData.value.fitEmailAddress.trim()) {
      errors.value.fitEmailAddress = 'FIT email address is required.';
    } else if (!isFitEmail(formData.value.fitEmailAddress)) {
      errors.value.fitEmailAddress = 'Please use your official @fit.edu.ph email address.';
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
      errors.value.supportingFile = 'PDF proof is required for student requests.';
    } else if (!isPdfFile(formData.value.supportingFile)) {
      errors.value.supportingFile = 'Student proof must be uploaded as a PDF file.';
    }
  }

  return {
    formData,
    isLoading,
    successMessage,
    awaitingVerification,
    verificationCode,
    showPassword,
    showConfirmPassword,
    studentSupportingFileInput,
    firstErrorMessage,
    isStudentRole,
    openStudentSupportingFile,
    handleStudentSupportingFileChange,
    removeStudentSupportingFile,
    handleSignUp,
    handleVerifyEmail,
  };
}

function validateSupportingFile(file) {
  if (!file) return '';
  if (!isPdfFile(file)) return 'Student proof must be uploaded as a PDF file.';
  if (file.size > 5 * 1024 * 1024) return 'Supporting file must be 5 MB or smaller.';
  return '';
}

function isValidName(value) {
  return /^[A-Za-z][A-Za-z .'-]*$/.test(value.trim());
}

function isFitEmail(value) {
  return /^[^\s@]+@fit\.edu\.ph$/i.test(value.trim());
}

function isStrongPassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(value);
}

function isPdfFile(file) {
  return file?.type === 'application/pdf' || String(file?.name || '').toLowerCase().endsWith('.pdf');
}

function readFileAsDataUrl(file) {
  if (!file) return Promise.resolve(null);

  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Unable to read supporting file.'));
    reader.readAsDataURL(file);
  });
}
