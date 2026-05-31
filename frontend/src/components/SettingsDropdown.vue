<!-- ===== Settings Dropdown Component ===== -->
<template>
  <div class="settings-dropdown-wrapper">
    <button
      class="settings-icon-btn"
      type="button"
      @click="toggleDropdown"
      title="Settings"
      aria-label="Settings"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3" />
        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.4.6.7 1 .7h.6a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.3Z" />
      </svg>
    </button>

    <div v-if="isOpen" class="settings-dropdown-menu">
      <div class="dropdown-header">
        <h3>Settings</h3>
        <router-link to="/settings" class="view-all-link">View All Settings</router-link>
      </div>

      <div class="dropdown-divider"></div>

      <div class="dropdown-content">
        <router-link to="/settings/account" class="dropdown-item">
          <span class="item-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21a8 8 0 0 0-16 0" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </span>
          <span class="item-text">Account Settings</span>
        </router-link>

        <router-link to="/settings/security" class="dropdown-item">
          <span class="item-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
              <path d="m9 12 2 2 4-4" />
            </svg>
          </span>
          <span class="item-text">Security</span>
        </router-link>

        <router-link to="/settings/preferences" class="dropdown-item">
          <span class="item-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 21v-7" />
              <path d="M4 10V3" />
              <path d="M12 21v-9" />
              <path d="M12 8V3" />
              <path d="M20 21v-5" />
              <path d="M20 12V3" />
              <path d="M2 14h4" />
              <path d="M10 8h4" />
              <path d="M18 16h4" />
            </svg>
          </span>
          <span class="item-text">Preferences</span>
        </router-link>
      </div>

      <div class="dropdown-divider"></div>

      <button type="button" @click.prevent.stop="handleLogout" class="dropdown-item logout-item">
        <span class="item-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="M16 17l5-5-5-5" />
            <path d="M21 12H9" />
          </svg>
        </span>
        <span class="item-text">Logout</span>
      </button>
    </div>

    <div v-if="isOpen" class="dropdown-overlay" @click="closeDropdown"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';
import { signOutClerk } from '@/modules/authentication/utils/clerkAuthUtils.js';
import { redirectToPostLogoutHome } from '@/modules/authentication/utils/logoutRedirect.js';

const { signOut } = useAuth();
const authStore = useAuthenticationStore();
const isOpen = ref(false);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

const handleLogout = async () => {
  closeDropdown();

  authStore.performLogout();

  const timeoutPromise = new Promise((resolve) => setTimeout(resolve, 1500));
  try {
    await Promise.race([signOutClerk(signOut), timeoutPromise]);
  } catch (e) {
    // Ignore logout network errors and still return the user to the app home.
  } finally {
    redirectToPostLogoutHome();
  }
};

const handleClickOutside = (event) => {
  const dropdown = document.querySelector('.settings-dropdown-wrapper');
  if (dropdown && !dropdown.contains(event.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.settings-dropdown-wrapper {
  position: relative;
  display: inline-block;
}

.settings-icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  padding: 0;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.settings-icon-btn svg {
  width: 20px;
  height: 20px;
}

.settings-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  min-width: 280px;
  margin-top: 0.5rem;
  background: #ffffff;
  border: 1px solid #dce8e2;
  border-radius: 8px;
  box-shadow: 0 18px 45px rgba(20, 51, 41, 0.14);
  z-index: 1000;
  overflow: hidden;
}

.dropdown-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.dropdown-header h3 {
  margin: 0;
  color: #17211d;
  font-size: 1rem;
  font-weight: 800;
}

.view-all-link {
  color: #08734f;
  font-size: 0.78rem;
  font-weight: 800;
  text-decoration: none;
}

.dropdown-divider {
  height: 1px;
  background-color: #e8efeb;
}

.dropdown-content {
  padding: 0.4rem;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.75rem;
  color: #17211d;
  text-align: left;
  text-decoration: none;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.88rem;
  font-weight: 700;
}

.dropdown-item:hover {
  background: #f5f8f6;
}

.item-icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  color: #08734f;
  background: #d1fae5;
  border-radius: 50%;
  font-size: 0.72rem;
  font-weight: 850;
}

.item-icon svg {
  width: 15px;
  height: 15px;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.item-text {
  flex: 1;
}

.logout-item {
  margin: 0.4rem;
  color: #b91c1c;
}

.logout-item .item-icon {
  color: #b91c1c;
  background: #fee2e2;
}

.dropdown-overlay {
  position: fixed;
  inset: 0;
  z-index: 999;
}

@media (max-width: 768px) {
  .settings-dropdown-menu {
    min-width: 250px;
    right: -10px;
  }
}
</style>
