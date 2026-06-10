<!-- ===== AI GENERATED: AdminSidebarLayoutComponent ===== -->
<template>
  <div class="admin-layout-wrapper">
    <aside class="admin-sidebar">
      <div class="admin-sidebar-brand">
        <img
          src="@/assets/TechReserve_LogoA.png"
          alt="TechReserve Logo"
          class="admin-sidebar-logo"
        />
        <div class="admin-sidebar-brand-copy">
          <p class="admin-sidebar-brand-university">FEU Tech</p>
          <h1 class="admin-sidebar-brand-title">
            <span class="admin-sidebar-brand-tech">Tech</span><span class="admin-sidebar-brand-reserve">Reserve</span>
          </h1>
        </div>
      </div>

      <div class="admin-sidebar-role-badge">
        {{ portalLabel }}
      </div>

      <nav class="admin-sidebar-navigation">
        <router-link
          v-for="navigationItem in navigationItems"
          :key="navigationItem.routeName"
          :to="{ name: navigationItem.routeName }"
          class="admin-sidebar-nav-item"
          :class="{ 'admin-sidebar-nav-item--active': isActiveRoute(navigationItem.routeName) }"
        >
          <span class="admin-sidebar-nav-icon" v-html="navigationItem.iconSvg"></span>
          <span class="admin-sidebar-nav-label">{{ navigationItem.label }}</span>
        </router-link>
      </nav>

      <div class="admin-sidebar-help-card">
        <p class="admin-sidebar-help-title">Need help?</p>
        <p class="admin-sidebar-help-text">Contact the system administrator for account and reservation support.</p>
      </div>
    </aside>

    <div class="admin-main-area">
      <header class="admin-topbar">
        <div class="admin-topbar-actions">
          <NotificationDropdown />
          <div class="admin-topbar-user">
            <span class="admin-topbar-avatar">
              <img v-if="userProfilePhoto" :src="userProfilePhoto" alt="Profile photo" />
              <span v-else>{{ userInitials }}</span>
            </span>
            <span class="admin-topbar-user-text">{{ displayedNameLabel }}</span>
          </div>
          <SettingsDropdown />
        </div>
      </header>

      <main class="admin-content-area">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import SettingsDropdown from '@/components/SettingsDropdown.vue';
import './adminSidebarLayout.css';

/**
 * @typedef {Object} AdminSidebarLayoutProps
 * @property {string} roleLabel - Optional fallback label when no user is logged in
 * @property {Array<Object>} navigationItems - Sidebar navigation items
 */
const props = defineProps({
  roleLabel: {
    type: String,
    required: false,
    default: '',
  },
  navigationItems: {
    type: Array,
    required: true,
  },
});

const currentRoute = useRoute();
const authStore = useAuthenticationStore();

const isAdminPortal = computed(() => {
  return props.navigationItems.some((item) => String(item.routeName).startsWith('admin'));
});

const isEmployeePortal = computed(() => String(props.roleLabel).toUpperCase() === 'EMPLOYEE');

const portalLabel = computed(() => {
  if (isAdminPortal.value) return 'Admin Portal';
  if (isEmployeePortal.value) return 'Employee Portal';
  return 'Borrower Portal';
});

/**
 * @function displayedNameLabel
 * @description Builds a "LASTNAME, FIRSTNAME" label from the logged-in account.
 *              Falls back to the roleLabel prop when no account is available.
 */
const displayedNameLabel = computed(() => {
  const account = authStore.accountData;
  if (account && (account.lastName || account.firstName)) {
    const lastName = (account.lastName || '').toUpperCase();
    const firstName = (account.firstName || '').toUpperCase();
    if (lastName && firstName) {
      return `${lastName}, ${firstName}`;
    }
    return lastName || firstName;
  }
  return props.roleLabel;
});

const userInitials = computed(() => {
  const label = displayedNameLabel.value || portalLabel.value;
  return label
    .split(/[\s,]+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');
});

const userProfilePhoto = computed(() => {
  return authStore.accountData?.profilePhotoData || authStore.clerkAccountData?.profilePhotoData || '';
});

/**
 * @function isActiveRoute
 * @description Checks if given route name matches the current active route.
 * @param {string} routeName - Route name to check
 * @returns {boolean}
 */
function isActiveRoute(routeName) {
  return currentRoute.name === routeName;
}
</script>
