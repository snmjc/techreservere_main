<!-- ===== AI GENERATED: AdminManageAccountsPage ===== -->
<template>
  <AdminSidebarLayoutComponent
    :role-label="'ADMINISTRATOR'"
    :navigation-items="adminNavigationItems"
  >
    <!-- Page Heading -->
    <h2 class="manage-accounts-page-heading">User Accounts</h2>

    <!-- Tabs: Admin | User -->
    <div class="manage-accounts-tabs-row">
      <button
        class="manage-accounts-tab-button"
        :class="{ 'manage-accounts-tab-button--active': activeAccountTab === 'admin' }"
        @click="handleTabChange('admin')"
      >
        Admin <span class="manage-accounts-tab-count">({{ adminAccountsList.length }})</span>
      </button>
      <div class="manage-accounts-tab-divider"></div>
      <button
        class="manage-accounts-tab-button"
        :class="{ 'manage-accounts-tab-button--active': activeAccountTab === 'user' }"
        @click="handleTabChange('user')"
      >
        User <span class="manage-accounts-tab-count">({{ userAccountsList.length }})</span>
      </button>
    </div>

    <!-- Toolbar: Search + Showing + Edit List -->
    <div class="manage-accounts-toolbar">
      <div class="manage-accounts-search-group">
        <label class="manage-accounts-search-label" for="accountSearchInput">Search:</label>
        <input
          id="accountSearchInput"
          v-model="searchQueryText"
          type="text"
          class="manage-accounts-search-input"
          placeholder="Name"
        />
      </div>
      <div class="manage-accounts-showing-group">
        <label class="manage-accounts-showing-label" for="accountShowingSelect">Showing:</label>
        <select
          id="accountShowingSelect"
          v-model="showingFilterValue"
          class="manage-accounts-showing-select"
        >
          <option value="all">All</option>
          <option value="admin">Admin</option>
          <option value="user">User</option>
        </select>
        <button class="manage-accounts-sort-button" aria-label="Sort" @click="handleToggleSortOrder">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <polyline points="19 12 12 19 5 12"/>
          </svg>
        </button>
      </div>
      <div class="manage-accounts-toolbar-spacer"></div>
      <button class="manage-accounts-edit-list-button" @click="handleToggleEditListMode">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        {{ editListModeActive ? 'Done' : 'Edit List' }}
      </button>
    </div>

    <!-- Account Table -->
    <AccountUserTableComponent
      :account-list="currentTabAccountList"
      :search-query-text="searchQueryText"
      :edit-list-mode-active="editListModeActive"
      :selected-for-deletion="selectedForDeletion"
      @view-account-details="handleViewAccountDetails"
      @toggle-delete-selection="handleToggleDeleteSelection"
    />

    <!-- Footer -->
    <div class="manage-accounts-page-footer">
      &copy; 2026 TECHRESERVE. DATAMS MANAGEMENT.
    </div>

    <!-- Floating Delete Bar -->
    <div
      v-if="editListModeActive && selectedForDeletion.length > 0"
      class="manage-accounts-delete-floating-bar"
    >
      <span class="manage-accounts-delete-selected-count">Selected ({{ selectedForDeletion.length }})</span>
      <button class="manage-accounts-delete-confirm-button" @click="handleConfirmDeleteSelected">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6l-2 14H7L5 6"/>
          <path d="M10 11v6"/>
          <path d="M14 11v6"/>
          <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
        Delete
      </button>
    </div>

    <!-- View Account Modal -->
    <AccountViewModalComponent
      :account-record="selectedAccountRecord"
      @close-account-modal="handleCloseAccountModal"
      @edit-account-record="handleEditAccountRecord"
      @reset-account-access="handleResetAccountAccess"
      @disable-account-record="handleDisableAccountRecord"
    />
  </AdminSidebarLayoutComponent>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminSidebarLayoutComponent from '@/shared/components/AdminSidebarLayoutComponent.vue';
import '@/shared/components/adminSidebarLayout.css';
import './css/adminManageAccountsPage.css';
import { adminNavigationItems } from '@/shared/constants/adminNavigationItems.js';
import AccountUserTableComponent from '@/modules/account/components/AccountUserTableComponent.vue';
import AccountViewModalComponent from '@/modules/account/components/AccountViewModalComponent.vue';
import '@/modules/account/components/accountViewModal.css';

const activeAccountTab = ref('admin');
const searchQueryText = ref('');
const showingFilterValue = ref('all');
const sortOrderAscending = ref(true);
const selectedAccountRecord = ref(null);
const editListModeActive = ref(false);
const selectedForDeletion = ref([]);

/**
 * @constant {Array<Object>} adminAccountsList
 * @description Static admin account records for display.
 */
const adminAccountsList = ref([
  {
    accountIdNumber: '2019*****',
    accountFullName: 'PARICCE HERMIONE DELOS SANTOS',
    accountLastName: 'Delos Santos',
    accountFirstName: 'Paricce Hermione',
    accountEmailAddress: 'phdelos@fit.edu.ph',
    accountRole: 'ADMIN',
    accountType: 'Admin',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 30, 2026',
  },
  {
    accountIdNumber: '2021*****',
    accountFullName: 'FARAH AHMED ABDELSATTAR KENAWY',
    accountLastName: 'Kenawy',
    accountFirstName: 'Farah Ahmed Abdelsattar',
    accountEmailAddress: 'faakenawy@fit.edu.ph',
    accountRole: 'ADMIN',
    accountType: 'Admin',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 28, 2026',
  },
  {
    accountIdNumber: '2022*****',
    accountFullName: 'EADWARD ANDREI LLADOC',
    accountLastName: 'Lladoc',
    accountFirstName: 'Eadward Andrei',
    accountEmailAddress: 'ealladoc@fit.edu.ph',
    accountRole: 'ADMIN',
    accountType: 'Admin',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 25, 2026',
  },
  {
    accountIdNumber: '2023*****',
    accountFullName: 'SEAN ACE MOJICA',
    accountLastName: 'Mojica',
    accountFirstName: 'Sean Ace',
    accountEmailAddress: 'samojica@fit.edu.ph',
    accountRole: 'ADMIN',
    accountType: 'Admin',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 30, 2026',
  },
]);

/**
 * @constant {Array<Object>} userAccountsList
 * @description Static user account records for display.
 */
const userAccountsList = ref([
  {
    accountIdNumber: '2018*****',
    accountFullName: 'ANTONIO REYES',
    accountLastName: 'Reyes',
    accountFirstName: 'Antonio',
    accountEmailAddress: 'areyes@fit.edu.ph',
    accountRole: 'FACULTY',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 30, 2026',
  },
  {
    accountIdNumber: '2019*****',
    accountFullName: 'EDUARDO MENDOZA',
    accountLastName: 'Mendoza',
    accountFirstName: 'Eduardo',
    accountEmailAddress: 'emendoza@fit.edu.ph',
    accountRole: 'FACULTY',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 28, 2026',
  },
  {
    accountIdNumber: '2021*****',
    accountFullName: 'RICARDO SANTOS',
    accountLastName: 'Santos',
    accountFirstName: 'Ricardo',
    accountEmailAddress: 'rsantos@fit.edu.ph',
    accountRole: 'FACULTY',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 25, 2026',
  },
  {
    accountIdNumber: '2021*****',
    accountFullName: 'LEONARDO DOMINGO',
    accountLastName: 'Domingo',
    accountFirstName: 'Leonardo',
    accountEmailAddress: 'ldomingo@fit.edu.ph',
    accountRole: 'FACULTY',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 22, 2026',
  },
  {
    accountIdNumber: '2023*****',
    accountFullName: 'MARK ANTHONY REYES',
    accountLastName: 'Reyes',
    accountFirstName: 'Mark Anthony',
    accountEmailAddress: 'mareyes@fit.edu.ph',
    accountRole: 'STUDENT',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 29, 2026',
  },
  {
    accountIdNumber: '2023*****',
    accountFullName: 'JOSHUA DELA CRUZ',
    accountLastName: 'Dela Cruz',
    accountFirstName: 'Joshua',
    accountEmailAddress: 'jdcruz@fit.edu.ph',
    accountRole: 'STUDENT',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 27, 2026',
  },
  {
    accountIdNumber: '2023*****',
    accountFullName: 'CHRISTIAN LLOYD VILLANUEVA',
    accountLastName: 'Villanueva',
    accountFirstName: 'Christian Lloyd',
    accountEmailAddress: 'clvillanueva@fit.edu.ph',
    accountRole: 'STUDENT',
    accountType: 'User',
    accountStatus: 'Active',
    accountCreatedOn: 'February 26, 2026',
    accountLastLogin: 'January 26, 2026',
  },
]);

/**
 * @function currentTabAccountList
 * @description Returns the account list based on the active tab.
 * @returns {Array<Object>}
 */
const currentTabAccountList = computed(() => {
  if (activeAccountTab.value === 'admin') {
    return adminAccountsList.value;
  }
  return userAccountsList.value;
});

/**
 * @function handleTabChange
 * @description Switches the active account tab and resets search.
 * @param {string} tabName - The tab to switch to ('admin' or 'user')
 * @returns {void}
 */
function handleTabChange(tabName) {
  activeAccountTab.value = tabName;
  searchQueryText.value = '';
  editListModeActive.value = false;
  selectedForDeletion.value = [];
}

/**
 * @function handleToggleSortOrder
 * @description Toggles sort order between ascending and descending.
 * @returns {void}
 */
function handleToggleSortOrder() {
  sortOrderAscending.value = !sortOrderAscending.value;
}

/**
 * @function handleViewAccountDetails
 * @description Opens the view account modal with selected record.
 * @param {Object} accountRecord - The account record to view
 * @returns {void}
 */
function handleViewAccountDetails(accountRecord) {
  selectedAccountRecord.value = accountRecord;
}

/**
 * @function handleCloseAccountModal
 * @description Closes the account view modal.
 * @returns {void}
 */
function handleCloseAccountModal() {
  selectedAccountRecord.value = null;
}

/**
 * @function handleEditAccountRecord
 * @description Handles Edit Account action from the modal.
 * @param {Object} accountRecord - The account record to edit
 * @returns {void}
 */
function handleEditAccountRecord(accountRecord) {
  // TODO: Wire to edit account flow
  console.log('Edit account:', accountRecord);
}

/**
 * @function handleResetAccountAccess
 * @description Handles Reset Access action from the modal.
 * @param {Object} accountRecord - The account record to reset
 * @returns {void}
 */
function handleResetAccountAccess(accountRecord) {
  // TODO: Wire to reset access flow
  console.log('Reset access:', accountRecord);
}

/**
 * @function handleDisableAccountRecord
 * @description Handles Disable Account action from the modal.
 * @param {Object} accountRecord - The account record to disable
 * @returns {void}
 */
function handleDisableAccountRecord(accountRecord) {
  // TODO: Wire to disable account flow
  console.log('Disable account:', accountRecord);
}

/**
 * @function handleToggleEditListMode
 * @description Toggles edit list mode on/off and clears selections when exiting.
 * @returns {void}
 */
function handleToggleEditListMode() {
  editListModeActive.value = !editListModeActive.value;
  if (!editListModeActive.value) {
    selectedForDeletion.value = [];
  }
}

/**
 * @function handleToggleDeleteSelection
 * @description Toggles an account in/out of the deletion selection list.
 * @param {Object} accountRecord - The account record to toggle
 * @returns {void}
 */
function handleToggleDeleteSelection(accountRecord) {
  const existingIndex = selectedForDeletion.value.findIndex(
    (selected) =>
      selected.accountIdNumber === accountRecord.accountIdNumber &&
      selected.accountFullName === accountRecord.accountFullName
  );
  if (existingIndex >= 0) {
    selectedForDeletion.value.splice(existingIndex, 1);
  } else {
    selectedForDeletion.value.push(accountRecord);
  }
}

/**
 * @function handleConfirmDeleteSelected
 * @description Removes all selected accounts from the current tab list.
 * @returns {void}
 */
function handleConfirmDeleteSelected() {
  const targetList = activeAccountTab.value === 'admin' ? adminAccountsList : userAccountsList;
  targetList.value = targetList.value.filter((accountRecord) => {
    return !selectedForDeletion.value.some(
      (selected) =>
        selected.accountIdNumber === accountRecord.accountIdNumber &&
        selected.accountFullName === accountRecord.accountFullName
    );
  });
  selectedForDeletion.value = [];
}
</script>
