<!-- ===== AI GENERATED: AccountUserTableComponent ===== -->
<template>
  <div class="account-user-table-wrapper">
    <table class="account-user-table">
      <thead>
        <tr class="account-user-table-header-row">
          <th class="account-user-table-header-cell">ID No.</th>
          <th class="account-user-table-header-cell">Name</th>
          <th class="account-user-table-header-cell">Role</th>
          <th class="account-user-table-header-cell">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="accountRecord in filteredAccountList"
          :key="accountRecord.accountIdNumber + accountRecord.accountFullName"
          class="account-user-table-body-row"
        >
          <td class="account-user-table-cell account-user-table-cell--id">
            {{ accountRecord.accountIdNumber }}
          </td>
          <td class="account-user-table-cell account-user-table-cell--name">
            {{ accountRecord.accountFullName }}
          </td>
          <td class="account-user-table-cell account-user-table-cell--role">
            {{ accountRecord.accountRole }}
          </td>
          <td class="account-user-table-cell account-user-table-cell--actions">
            <!-- Normal mode: View Account link -->
            <button
              v-if="!editListModeActive"
              class="account-user-table-view-button"
              @click="handleViewAccountClick(accountRecord)"
            >
              View Account
            </button>
            <!-- Edit mode: Delete toggle circle -->
            <button
              v-else
              class="account-user-table-delete-toggle"
              :class="{ 'account-user-table-delete-toggle--selected': isAccountSelected(accountRecord) }"
              :aria-label="isAccountSelected(accountRecord) ? 'Deselect for deletion' : 'Select for deletion'"
              @click="handleToggleDeleteSelection(accountRecord)"
            >
              <span class="account-user-table-delete-toggle-inner"></span>
            </button>
          </td>
        </tr>
        <tr v-if="filteredAccountList.length === 0">
          <td colspan="4" class="account-user-table-cell account-user-table-empty-row">
            No accounts found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * @typedef {Object} AccountUserTableProps
 * @property {Array<Object>} accountList - Array of account records
 * @property {string} searchQueryText - Current search query for filtering
 * @property {boolean} editListModeActive - Whether the table is in edit/delete mode
 * @property {Array<Object>} selectedForDeletion - Accounts currently selected for deletion
 */
const props = defineProps({
  accountList: {
    type: Array,
    required: true,
  },
  searchQueryText: {
    type: String,
    required: false,
    default: '',
  },
  editListModeActive: {
    type: Boolean,
    required: false,
    default: false,
  },
  selectedForDeletion: {
    type: Array,
    required: false,
    default: () => [],
  },
});

const emit = defineEmits(['viewAccountDetails', 'toggleDeleteSelection']);

/**
 * @function filteredAccountList
 * @description Filters account list based on search query matching name or ID.
 * @returns {Array<Object>}
 */
const filteredAccountList = computed(() => {
  const queryLower = props.searchQueryText.toLowerCase().trim();
  if (!queryLower) {
    return props.accountList;
  }
  return props.accountList.filter((accountRecord) => {
    return (
      accountRecord.accountFullName.toLowerCase().includes(queryLower) ||
      accountRecord.accountIdNumber.toLowerCase().includes(queryLower)
    );
  });
});

/**
 * @function isAccountSelected
 * @description Checks if an account is currently selected for deletion.
 * @param {Object} accountRecord - The account to check
 * @returns {boolean}
 */
function isAccountSelected(accountRecord) {
  return props.selectedForDeletion.some(
    (selected) =>
      selected.accountIdNumber === accountRecord.accountIdNumber &&
      selected.accountFullName === accountRecord.accountFullName
  );
}

/**
 * @function handleViewAccountClick
 * @description Emits event to parent when View Account is clicked.
 * @param {Object} accountRecord - The account record to view
 * @returns {void}
 */
function handleViewAccountClick(accountRecord) {
  emit('viewAccountDetails', accountRecord);
}

/**
 * @function handleToggleDeleteSelection
 * @description Emits toggle event to parent for delete selection.
 * @param {Object} accountRecord - The account record to toggle
 * @returns {void}
 */
function handleToggleDeleteSelection(accountRecord) {
  emit('toggleDeleteSelection', accountRecord);
}
</script>
