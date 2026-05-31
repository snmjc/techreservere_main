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
        <div class="admin-topbar-page">
          <div>
            <p class="admin-topbar-eyebrow">{{ portalSubtitle }}</p>
            <h2 class="admin-topbar-title">{{ currentPageTitle }}</h2>
          </div>
        </div>

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
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { redirectToPostLogoutHome } from '@/modules/authentication/utils/logoutRedirect.js';
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
const router = useRouter();
const authStore = useAuthenticationStore();
const { signOut } = useAuth();

const currentNavigationItem = computed(() => {
  return props.navigationItems.find((item) => item.routeName === currentRoute.name);
});

const isAdminPortal = computed(() => {
  return props.navigationItems.some((item) => String(item.routeName).startsWith('admin'));
});

const portalLabel = computed(() => (isAdminPortal.value ? 'Admin Portal' : 'Borrower Portal'));

const portalSubtitle = computed(() => (isAdminPortal.value ? 'System administrator' : 'Reservation workspace'));

const currentPageTitle = computed(() => currentNavigationItem.value?.label || currentRoute.meta?.title || 'TechReserve');

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

async function handleLogout() {
  try {
    await signOut();
  } catch (e) {
    // Continue local logout even if Clerk has no active session.
  }

  authStore.performLogout();

  const timeoutPromise = new Promise((resolve) => setTimeout(resolve, 1500));
  try {
    await Promise.race([signOutClerk(signOut), timeoutPromise]);
  } catch (e) {
    // ignore
  } finally {
    redirectToPostLogoutHome();
  }
}
</script>
