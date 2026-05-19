<!-- ===== AI GENERATED: AdminSidebarLayoutComponent ===== -->
<template>
  <div class="admin-layout-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-sidebar-brand">
        <p class="admin-sidebar-brand-university">FEUTECH</p>
        <img
          src="@/assets/TechReserve_LogoA.png"
          alt="TechReserve Logo"
          class="admin-sidebar-logo"
        />
        <h1 class="admin-sidebar-brand-title">
          <span class="admin-sidebar-brand-tech">Tech</span><span class="admin-sidebar-brand-reserve">Reserve</span>
        </h1>
      </div>

      <div class="admin-sidebar-role-badge">
        {{ displayedNameLabel }}
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

    </aside>

    <!-- Main Content Area -->
    <div class="admin-main-area">
      <!-- Top Bar -->
      <header class="admin-topbar">
        <div class="admin-topbar-spacer"></div>
        <div class="admin-topbar-actions">
          <NotificationDropdown />
          <SettingsDropdown />
        </div>
      </header>

      <!-- Green Accent Bar -->
      <div class="admin-topbar-accent"></div>

      <!-- Page Content -->
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
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import SettingsDropdown from '@/components/SettingsDropdown.vue';

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

/**
 * @function isActiveRoute
 * @description Checks if given route name matches the current active route.
 * @param {string} routeName - Route name to check
 * @returns {boolean}
 */
function isActiveRoute(routeName) {
  return currentRoute.name === routeName;
}

/**
 * @function handleLogout
 * @description Clears auth state and redirects to login page.
 */
async function handleLogout() {
  authStore.performLogout();
  localStorage.removeItem('authToken');
  localStorage.removeItem('userRole');
  localStorage.removeItem('userData');
  localStorage.removeItem('clerkToken');

  console.log('[AdminSidebarLayout] logout clicked; navigating to clerkLoginPage');
  const timeoutPromise = new Promise((resolve) => setTimeout(resolve, 1500));
  try {
    await Promise.race([signOut(), timeoutPromise]);
  } catch (e) {
    // ignore
  } finally {
    window.location.href = '/clerk-login';
  }
}
</script>
