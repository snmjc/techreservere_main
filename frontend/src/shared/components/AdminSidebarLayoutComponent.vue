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
        {{ roleLabel }}
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

      <div class="admin-sidebar-logout">
        <button class="admin-sidebar-logout-button" @click="handleLogout">
          <span class="admin-sidebar-nav-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </span>
          <span class="admin-sidebar-nav-label">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main-area">
      <!-- Top Bar -->
      <header class="admin-topbar">
        <div class="admin-topbar-spacer"></div>
        <div class="admin-topbar-actions">
          <button class="admin-topbar-icon-button" aria-label="Notifications">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="admin-topbar-notification-dot"></span>
          </button>
          <button class="admin-topbar-icon-button" aria-label="Settings">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
          </button>
          <button class="admin-topbar-icon-button admin-topbar-avatar-button" aria-label="Profile">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </button>
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
import { useRoute, useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

/**
 * @typedef {Object} AdminSidebarLayoutProps
 * @property {string} roleLabel - Role display label (e.g. "ADMINISTRATOR")
 * @property {Array<Object>} navigationItems - Sidebar navigation items
 */
const props = defineProps({
  roleLabel: {
    type: String,
    required: true,
  },
  navigationItems: {
    type: Array,
    required: true,
  },
});

const currentRoute = useRoute();
const router = useRouter();
const authStore = useAuthenticationStore();

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
function handleLogout() {
  authStore.performLogout();
  router.push({ name: 'loginPage' });
}
</script>
