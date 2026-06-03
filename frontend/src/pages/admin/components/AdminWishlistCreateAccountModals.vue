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
        <h2>Add Admin Account</h2>
        <p>Create an administrator request record for verification.</p>
      </div>

      <AccountSectionLabel />

      <form class="admin-wishlist-add-form" @submit.prevent="createAdminAccount">
        <label>
          <span>Last Name</span>
          <input v-model.trim="addAdminForm.lastName" type="text" placeholder="Last Name" minlength="2" required :disabled="isProcessing" @input="sanitizeAdminNameField('lastName')" />
        </label>
        <label>
          <span>First Name</span>
          <input v-model.trim="addAdminForm.firstName" type="text" placeholder="First Name" minlength="2" required :disabled="isProcessing" @input="sanitizeAdminNameField('firstName')" />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Email</span>
          <input v-model.trim="addAdminForm.emailAddress" type="email" placeholder="Email" required :disabled="isProcessing" />
        </label>
        <label>
          <span>ID Number</span>
          <input v-model.trim="addAdminForm.idNumber" type="text" placeholder="ID Number" required :disabled="isProcessing" />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Default Password</span>
          <input type="text" value="admin123" readonly disabled />
        </label>

        <p v-if="addAdminError" class="admin-wishlist-add-error">{{ addAdminError }}</p>

        <div class="admin-wishlist-modal-actions">
          <button class="admin-wishlist-cancel-button" type="button" :disabled="isProcessing" @click="closeAddAdminModal">
            Cancel
          </button>
          <button class="admin-wishlist-verify-button" type="submit" :disabled="isProcessing">
            {{ isProcessing ? 'Creating...' : 'Create Account' }}
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

      <AccountSectionLabel />

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
          <span>FIT Email Address</span>
          <input v-model.trim="addUserForm.emailAddress" type="email" placeholder="jtvito@fit.edu.ph" required />
        </label>
        <label>
          <span>ID Number</span>
          <input v-model.trim="addUserForm.idNumber" type="text" placeholder="2023*****" required />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Role</span>
          <select v-model="addUserForm.role" required>
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
          </select>
        </label>
        <PasswordField
          v-model="addUserForm.password"
          label="Password"
          placeholder="Password"
          :visible="showAddUserPassword"
          @toggle="showAddUserPassword = !showAddUserPassword"
        />
        <PasswordField
          v-model="addUserForm.confirmPassword"
          label="Confirm Password"
          placeholder="Confirm Password"
          :visible="showAddUserConfirmPassword"
          @toggle="showAddUserConfirmPassword = !showAddUserConfirmPassword"
        />

        <p v-if="addUserError" class="admin-wishlist-add-error">{{ addUserError }}</p>

        <div class="admin-wishlist-modal-actions">
          <button class="admin-wishlist-cancel-button" type="button" @click="closeAddUserModal">
            Cancel
          </button>
          <button class="admin-wishlist-send-invite-button" type="submit" :disabled="isProcessing">
            {{ isProcessing ? 'Creating...' : 'Create Account' }}
          </button>
        </div>
      </form>
    </section>
  </div>

  <div v-if="showAddEmployeeModal" class="admin-wishlist-modal-overlay admin-wishlist-modal-overlay--top" @click.self="!isProcessing && closeAddEmployeeModal()">
    <section class="admin-wishlist-add-employee-modal admin-wishlist-create-modal">
      <button class="admin-wishlist-modal-close" type="button" aria-label="Close" :disabled="isProcessing" @click="closeAddEmployeeModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </button>

      <div class="admin-wishlist-modal-heading admin-wishlist-modal-heading--employee">
        <h2>Add Staff Account</h2>
        <p>Create a staff request record for verification.</p>
      </div>

      <AccountSectionLabel />

      <form class="admin-wishlist-add-form" @submit.prevent="createEmployeeAccount">
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
          <input v-model.trim="addEmployeeForm.phone" type="tel" inputmode="numeric" maxlength="10" placeholder="9123456789" required @input="sanitizeEmployeePhone" />
        </label>
        <label>
          <span>Work ID Number</span>
          <input v-model.trim="addEmployeeForm.idNumber" type="text" placeholder="2023-****" required />
        </label>
        <label class="admin-wishlist-field-wide">
          <span>Role</span>
          <input v-model="addEmployeeForm.role" type="text" readonly />
        </label>

        <p v-if="addEmployeeError" class="admin-wishlist-add-error">{{ addEmployeeError }}</p>

        <div class="admin-wishlist-modal-actions">
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
import {
  buildAdminAccountPayload,
  buildEmployeeAccountPayload,
  buildUserAccountPayload,
  formatCreateAccountError,
  getAdminCreateError,
  getEmployeeCreateError,
  getUserCreateError,
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
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
});

const addUserForm = reactive({
  lastName: '',
  firstName: '',
  emailAddress: '',
  idNumber: '',
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
  if (addAdminError.value) return;

  await createAccount({
    type: 'admin',
    request: () => adminWishlistApi.createAdminAccount(buildAdminAccountPayload(addAdminForm), authStore.authToken),
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
  isProcessing.value = true;
  const result = await request();
  isProcessing.value = false;

  if (!result.success) {
    setCreateError(type, formatCreateAccountError(result, type));
    return;
  }

  close();
  emit('created', type);
}

function setCreateError(type, message) {
  if (type === 'admin') addAdminError.value = message;
  if (type === 'user') addUserError.value = message;
  if (type === 'employee') addEmployeeError.value = message;
}

function resetAddAdminForm() {
  addAdminForm.lastName = '';
  addAdminForm.firstName = '';
  addAdminForm.emailAddress = '';
  addAdminForm.idNumber = '';
  addAdminError.value = '';
}

function resetAddUserForm() {
  addUserForm.lastName = '';
  addUserForm.firstName = '';
  addUserForm.emailAddress = '';
  addUserForm.idNumber = '';
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

defineExpose({ openForTab });
</script>

<script>
export default {
  components: {
    AccountSectionLabel: {
      template: `
        <div class="admin-wishlist-add-section-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
          Account Information
        </div>
      `,
    },
    PasswordField: {
      props: {
        modelValue: { type: String, default: '' },
        label: { type: String, required: true },
        placeholder: { type: String, default: '' },
        visible: { type: Boolean, default: false },
      },
      emits: ['update:modelValue', 'toggle'],
      template: `
        <label>
          <span>{{ label }}</span>
          <span class="admin-wishlist-password-field">
            <input
              :value="modelValue"
              :type="visible ? 'text' : 'password'"
              :placeholder="placeholder"
              required
              @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
              type="button"
              class="admin-wishlist-password-toggle"
              :aria-label="visible ? 'Hide password' : 'Show password'"
              @click="$emit('toggle')"
            >
              <svg v-if="visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
      `,
    },
  },
};
</script>
