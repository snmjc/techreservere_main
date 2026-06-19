<template>
  <div v-if="showAddAdminModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeAddAdminModal()">
    <section class="admin-wishlist-add-admin-modal admin-wishlist-create-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeAddAdminModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading">
        <h2>Create New Admin</h2>
        <p>Create an administrator request account that stays unverified until the Clerk invitation is accepted.</p>
      </div>

      <div class="admin-wishlist-add-section-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
        Account Information
      </div>

      <form class="admin-wishlist-add-form" @submit.prevent="createAdminAccount">
        <label>
          <span>ID Number</span>
          <input v-model.trim="addAdminForm.idNumber" type="text" inputmode="numeric" maxlength="10" placeholder="2023123456" required :disabled="isProcessing" @input="sanitizeAdminIdNumber" />
        </label>
        <label>
          <span>Last Name</span>
          <input v-model.trim="addAdminForm.lastName" type="text" placeholder="Last Name" minlength="2" required :disabled="isProcessing" @input="sanitizeAdminNameField('lastName')" />
        </label>
        <label>
          <span>First Name</span>
          <input v-model.trim="addAdminForm.firstName" type="text" placeholder="First Name" minlength="2" required :disabled="isProcessing" @input="sanitizeAdminNameField('firstName')" />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Admin Email</span>
          <input
            v-model.trim="addAdminForm.emailAddress"
            type="email"
            placeholder="admin@feutech.edu.ph"
            required
            :disabled="isProcessing"
          />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Role</span>
          <input type="text" value="Admin" readonly disabled />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Security Confirmation</span>
          <input
            v-model.trim="addAdminForm.confirmedAdminEmail"
            type="email"
            :placeholder="currentAdminEmail || 'admin@feutech.edu.ph'"
            required
            :disabled="isProcessing"
          />
        </label>

        <p class="admin-wishlist-add-helper">Default password: <strong>admin123</strong></p>
        <p class="admin-wishlist-add-helper">This account will remain in Wishlist as Unverified until you send the Clerk email invitation.</p>
        <p v-if="addAdminError" class="admin-wishlist-add-error">{{ addAdminError }}</p>
        <p v-else-if="showAdminCreateHelper" class="admin-wishlist-add-helper">{{ adminCreateHelperText }}</p>

        <div class="admin-wishlist-modal-actions">
          <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="closeAddAdminModal">
            Cancel
          </button>
          <button class="admin-wishlist-verify-button" type="submit" :disabled="isProcessing || !isAdminCreateFormReady">
            {{ isProcessing ? 'Creating...' : 'Create Admin' }}
          </button>
        </div>
      </form>
    </section>
  </div>

  <div v-if="showAddUserModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="closeAddUserModal">
    <section class="admin-wishlist-add-user-modal admin-wishlist-create-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" @click="closeAddUserModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--user">
        <h2>Add User Account</h2>
        <p>Create a user request record for verification.</p>
      </div>

      <div class="admin-wishlist-add-section-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
        Account Information
      </div>

      <form class="admin-wishlist-add-form" @submit.prevent="createUserAccount">
        <label>
          <span>Last Name</span>
          <input v-model.trim="addUserForm.lastName" type="text" placeholder="Vito" required />
        </label>
        <label>
          <span>First Name</span>
          <input v-model.trim="addUserForm.firstName" type="text" placeholder="Justin Timothy" required />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Institutional Email Address</span>
          <input v-model.trim="addUserForm.emailAddress" type="email" placeholder="jtvito@fit.edu.ph or jtvito@feutech.edu.ph" required />
        </label>
        <label>
          <span>ID Number</span>
          <input v-model.trim="addUserForm.idNumber" type="text" inputmode="numeric" maxlength="10" placeholder="2023123456" required @input="sanitizeUserIdNumber" />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Account Role</span>
          <input type="text" value="Borrower / User" readonly disabled />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>User Type</span>
          <select v-model="addUserForm.role" required>
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
          </select>
        </label>
        <label>
          <span>Password</span>
          <span class="admin-wishlist-password-field">
            <input
              v-model="addUserForm.password"
              :type="showAddUserPassword ? 'text' : 'password'"
              placeholder="Password"
              required
            />
            <button
              type="button"
              class="admin-wishlist-password-toggle"
              :aria-label="showAddUserPassword ? 'Hide password' : 'Show password'"
              @click="showAddUserPassword = !showAddUserPassword"
            >
              <svg v-if="showAddUserPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3l18 18" />
                <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                <path d="M9.88 4.24A9.77 9.77 0 0 1 12 4c6 0 10 8 10 8a17.5 17.5 0 0 1-3.1 4.35" />
                <path d="M6.61 6.61A17.5 17.5 0 0 0 2 12s4 8 10 8a9.77 9.77 0 0 0 5.39-1.61" />
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </span>
        </label>
        <label>
          <span>Confirm Password</span>
          <span class="admin-wishlist-password-field">
            <input
              v-model="addUserForm.confirmPassword"
              :type="showAddUserConfirmPassword ? 'text' : 'password'"
              placeholder="Confirm Password"
              required
            />
            <button
              type="button"
              class="admin-wishlist-password-toggle"
              :aria-label="showAddUserConfirmPassword ? 'Hide password' : 'Show password'"
              @click="showAddUserConfirmPassword = !showAddUserConfirmPassword"
            >
              <svg v-if="showAddUserConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3l18 18" />
                <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                <path d="M9.88 4.24A9.77 9.77 0 0 1 12 4c6 0 10 8 10 8a17.5 17.5 0 0 1-3.1 4.35" />
                <path d="M6.61 6.61A17.5 17.5 0 0 0 2 12s4 8 10 8a9.77 9.77 0 0 0 5.39-1.61" />
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </span>
        </label>

        <p v-if="addUserError" class="admin-wishlist-add-error">{{ addUserError }}</p>

        <div class="admin-wishlist-modal-actions">
          <button class="admin-wishlist-cancel-button" type="button" @click="closeAddUserModal">
            Cancel
          </button>
          <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing || !isUserCreateFormReady">
            {{ isProcessing ? 'Creating...' : 'Create Account' }}
          </button>
        </div>
      </form>
    </section>
  </div>

  <div v-if="showAddEmployeeModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeAddEmployeeModal()">
    <section class="admin-wishlist-add-employee-modal admin-wishlist-create-modal admin-wishlist-create-modal--employee">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeAddEmployeeModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--employee">
        <h2>Add Staff Account</h2>
        <p>Create an assignment-only staff record in Manage Accounts.</p>
      </div>

      <div class="admin-wishlist-add-section-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
        Account Information
      </div>

      <form class="admin-wishlist-add-form admin-wishlist-add-form--employee" @submit.prevent="createEmployeeAccount">
        <label>
          <span>Last Name</span>
          <input v-model.trim="addEmployeeForm.lastName" type="text" placeholder="Dela Cruz" required @input="sanitizeEmployeeNameField('lastName')" />
        </label>
        <label>
          <span>First Name</span>
          <input v-model.trim="addEmployeeForm.firstName" type="text" placeholder="Juan" required @input="sanitizeEmployeeNameField('firstName')" />
        </label>
        <label>
          <span>Phone</span>
          <input v-model.trim="addEmployeeForm.phone" type="tel" inputmode="numeric" maxlength="11" placeholder="09123456789" required @input="sanitizeEmployeePhone" />
        </label>
        <label>
          <span>Work ID Number</span>
          <input v-model.trim="addEmployeeForm.idNumber" type="text" inputmode="numeric" maxlength="10" placeholder="2023123456" required @input="sanitizeEmployeeIdNumber" />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Role</span>
          <input v-model="addEmployeeForm.role" type="text" readonly />
        </label>

        <div class="admin-wishlist-add-helper admin-wishlist-add-helper--employee" role="note" aria-label="Staff assignment note">
          <strong>Assignment-only staff record</strong>
          <span>This staff profile will appear in task assignment lists and receive SMS assignment updates. It is not used for sign-in.</span>
        </div>
        <p v-if="addEmployeeError" class="admin-wishlist-add-error">{{ addEmployeeError }}</p>

        <div class="admin-wishlist-modal-actions admin-wishlist-modal-actions--employee">
          <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="closeAddEmployeeModal">
            Cancel
          </button>
          <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing || !isEmployeeCreateFormReady">
            {{ isProcessing ? 'Creating...' : 'Create Account' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { adminWishlistApi } from '@/services/adminWishlistApi.js';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import '../css/AdminWishlist.css';
import {
  buildAdminAccountPayload,
  buildEmployeeAccountPayload,
  buildUserAccountPayload,
  formatCreateAccountError,
  getAdminCreateError,
  getEmployeeCreateError,
  getUserCreateError,
  sanitizeIdNumberInput,
  sanitizeNameInput,
  sanitizePhoneInput,
  validateEmployeeAccountForm,
} from '../wishlist/adminWishlistHelpers.js';

const props = defineProps({
  accounts: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['created']);
const authStore = useAuthenticationStore();

const isProcessing = ref(false);
const showAddAdminModal = ref(false);
const showAddUserModal = ref(false);
const showAddEmployeeModal = ref(false);
const showAddUserPassword = ref(false);
const showAddUserConfirmPassword = ref(false);
const addAdminError = ref('');
const addUserError = ref('');
const addEmployeeError = ref('');

const addAdminForm = reactive({
  idNumber: '',
  lastName: '',
  firstName: '',
  emailAddress: '',
  roleDesignation: 'ROLE_ADMIN',
  confirmedAdminEmail: '',
});

const addUserForm = reactive({
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
  roleDesignation: 'ROLE_BORROWER',
  role: 'Student',
  password: '',
  confirmPassword: '',
});

const addEmployeeForm = reactive({
  lastName: '',
  firstName: '',
  phone: '',
  idNumber: '',
  role: 'Maintenance Staff',
});

const currentAdminEmail = computed(() => {
  const account = authStore.accountData || authStore.clerkAccountData || {};
  return String(account.emailAddress || account.email || '').trim().toLowerCase();
});
const hasStartedAdminForm = computed(() => (
  addAdminForm.idNumber.trim() !== ''
  || addAdminForm.lastName.trim() !== ''
  || addAdminForm.firstName.trim() !== ''
  || addAdminForm.emailAddress.trim() !== ''
  || addAdminForm.confirmedAdminEmail.trim() !== ''
));
const adminCreateHelperText = computed(() => {
  const validationError = getAdminCreateError(addAdminForm, props.accounts);
  if (validationError) {
    return validationError;
  }

  if (!currentAdminEmail.value) {
    return 'Unable to confirm the responsible admin email. Please sign in again.';
  }

  if (addAdminForm.confirmedAdminEmail.trim() === '') {
    return 'Type your exact admin email in Security Confirmation to continue.';
  }

  if (addAdminForm.confirmedAdminEmail.trim().toLowerCase() !== currentAdminEmail.value) {
    return 'Please type your exact admin email before creating a new admin.';
  }

  return '';
});
const showAdminCreateHelper = computed(() => hasStartedAdminForm.value && adminCreateHelperText.value !== '');
const isAdminCreateFormReady = computed(() => adminCreateHelperText.value === '');
const isUserCreateFormReady = computed(() => getUserCreateError(addUserForm, props.accounts) === '');
const isEmployeeCreateFormReady = computed(() => validateEmployeeAccountForm(addEmployeeForm) === '');

function openForTab(tabName) {
  if (tabName === 'employee') return openAddEmployeeModal();
  if (tabName === 'user') return openAddUserModal();
  return openAddAdminModal();
}

function openAddAdminModal() {
  resetAddAdminForm();
  showAddAdminModal.value = true;
}

function closeAddAdminModal() {
  showAddAdminModal.value = false;
  resetAddAdminForm();
}

function openAddUserModal() {
  resetAddUserForm();
  showAddUserModal.value = true;
}

function closeAddUserModal() {
  showAddUserModal.value = false;
  resetAddUserForm();
}

function openAddEmployeeModal() {
  resetAddEmployeeForm();
  showAddEmployeeModal.value = true;
}

function closeAddEmployeeModal() {
  showAddEmployeeModal.value = false;
  resetAddEmployeeForm();
}

async function createAdminAccount() {
  if (isProcessing.value) return;

  addAdminError.value = getAdminCreateError(addAdminForm, props.accounts);
  if (!currentAdminEmail.value) {
    addAdminError.value = 'Unable to confirm the responsible admin email. Please sign in again.';
    return;
  }
  if (addAdminForm.confirmedAdminEmail.trim().toLowerCase() !== currentAdminEmail.value) {
    addAdminError.value = 'Please type your exact admin email before creating a new admin.';
    return;
  }
  if (addAdminError.value) return;

  await createAccount({
    type: 'admin',
    request: () => adminWishlistApi.createAdminAccount(
      buildAdminAccountPayload(addAdminForm),
      authStore.authToken,
    ),
    close: closeAddAdminModal,
  });
}

async function createUserAccount() {
  if (isProcessing.value) return;

  addUserError.value = getUserCreateError(addUserForm, props.accounts);
  if (addUserError.value) return;

  await createAccount({
    type: 'user',
    request: () => adminWishlistApi.createUserAccount(buildUserAccountPayload(addUserForm), authStore.authToken),
    close: closeAddUserModal,
  });
}

async function createEmployeeAccount() {
  if (isProcessing.value) return;

  addEmployeeError.value = getEmployeeCreateError(addEmployeeForm, props.accounts);
  if (addEmployeeError.value) return;

  await createAccount({
    type: 'employee',
    request: () => adminWishlistApi.createEmployeeAccount(buildEmployeeAccountPayload(addEmployeeForm), authStore.authToken),
    close: closeAddEmployeeModal,
  });
}

async function createAccount({ type, request, close }) {
  setCreateError(type, '');
  isProcessing.value = true;

  try {
    const result = await request();

    if (!result.success) {
      setCreateError(type, formatCreateAccountError(result, type));
      return;
    }

    close();
    emit('created', { type, data: result.data || null });
  } catch (error) {
    setCreateError(type, error?.message || `Unable to create ${type} account.`);
  } finally {
    isProcessing.value = false;
  }
}

function setCreateError(type, message) {
  if (type === 'admin') addAdminError.value = message;
  if (type === 'user') addUserError.value = message;
  if (type === 'employee') addEmployeeError.value = message;
}

function resetAddAdminForm() {
  addAdminForm.idNumber = '';
  addAdminForm.lastName = '';
  addAdminForm.firstName = '';
  addAdminForm.emailAddress = '';
  addAdminForm.roleDesignation = 'ROLE_ADMIN';
  addAdminForm.confirmedAdminEmail = '';
  addAdminError.value = '';
}

function resetAddUserForm() {
  addUserForm.lastName = '';
  addUserForm.firstName = '';
  addUserForm.emailAddress = '';
  addUserForm.idNumber = '';
  addUserForm.roleDesignation = 'ROLE_BORROWER';
  addUserForm.role = 'Student';
  addUserForm.password = '';
  addUserForm.confirmPassword = '';
  showAddUserPassword.value = false;
  showAddUserConfirmPassword.value = false;
  addUserError.value = '';
}

function resetAddEmployeeForm() {
  addEmployeeForm.lastName = '';
  addEmployeeForm.firstName = '';
  addEmployeeForm.phone = '';
  addEmployeeForm.idNumber = '';
  addEmployeeForm.role = 'Maintenance Staff';
  addEmployeeError.value = '';
}

function sanitizeAdminNameField(fieldName) {
  addAdminForm[fieldName] = sanitizeNameInput(addAdminForm[fieldName]);
}

function sanitizeEmployeeNameField(fieldName) {
  addEmployeeForm[fieldName] = sanitizeNameInput(addEmployeeForm[fieldName]);
}

function sanitizeEmployeePhone() {
  addEmployeeForm.phone = sanitizePhoneInput(addEmployeeForm.phone);
}

function sanitizeAdminIdNumber() {
  addAdminForm.idNumber = sanitizeIdNumberInput(addAdminForm.idNumber);
}

function sanitizeUserIdNumber() {
  addUserForm.idNumber = sanitizeIdNumberInput(addUserForm.idNumber);
}

function sanitizeEmployeeIdNumber() {
  addEmployeeForm.idNumber = sanitizeIdNumberInput(addEmployeeForm.idNumber);
}

defineExpose({ openForTab });
</script>
