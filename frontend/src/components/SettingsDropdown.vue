<!-- ===== Settings Dropdown Component ===== -->
<template>
  <div class="settings-dropdown-wrapper">
    <!-- Settings Icon Button -->
    <button
      class="settings-icon-btn"
      @click="toggleDropdown"
      title="Settings"
    >
      ⚙️
    </button>

    <!-- Dropdown Menu -->
    <div v-if="isOpen" class="settings-dropdown-menu">
      <div class="dropdown-header">
        <h3>Settings</h3>
        <router-link to="/settings" class="view-all-link">View All Settings</router-link>
      </div>

      <div class="dropdown-divider"></div>

      <div class="dropdown-content">
        <!-- Account Settings -->
        <router-link to="/settings/account" class="dropdown-item">
          <span class="item-icon">👤</span>
          <span class="item-text">Account Settings</span>
        </router-link>

        <!-- Security -->
        <router-link to="/settings/security" class="dropdown-item">
          <span class="item-icon">🔒</span>
          <span class="item-text">Security</span>
        </router-link>

        <!-- Preferences -->
        <router-link to="/settings/preferences" class="dropdown-item">
          <span class="item-icon">⚙️</span>
          <span class="item-text">Preferences</span>
        </router-link>
      </div>

      <div class="dropdown-divider"></div>

      <!-- Logout -->
      <button type="button" @click.prevent.stop="handleLogout" class="dropdown-item logout-item">
        <span class="item-icon">🚪</span>
        <span class="item-text">Logout</span>
      </button>
    </div>

    <!-- Overlay to close dropdown -->
    <div v-if="isOpen" class="dropdown-overlay" @click="closeDropdown"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@clerk/vue';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore.js';

const router = useRouter();
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

  // Sign out from Clerk first to clear the httpOnly session cookie
  console.log('[SettingsDropdown] Calling Clerk signOut...');
  try {
    await signOut();
  } catch (e) {
    console.warn('[SettingsDropdown] Clerk signOut error:', e);
  }

  // Clear app state
  authStore.performLogout();
  localStorage.removeItem('authToken');
  localStorage.removeItem('userRole');
  localStorage.removeItem('userData');
  localStorage.removeItem('clerkToken');

  // Clear all Clerk-related localStorage keys
  Object.keys(localStorage).forEach(key => {
    if (key.startsWith('__clerk') || key.includes('clerk')) {
      localStorage.removeItem(key);
    }
  });

  console.log('[SettingsDropdown] logout complete; redirecting to login');

  // Redirect to login page
  window.location.href = '/clerk-login';
};

// Close dropdown when clicking outside
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
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.settings-icon-btn:hover {
  background-color: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.settings-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  min-width: 280px;
  margin-top: 0.5rem;
  z-index: 1000;
  overflow: hidden;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dropdown-header h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #333;
  margin: 0;
}

.view-all-link {
  font-size: 0.85rem;
  color: #1a6e3a;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.view-all-link:hover {
  color: #145a30;
  text-decoration: underline;
}

.dropdown-divider {
  height: 1px;
  background-color: #eee;
}

.dropdown-content {
  padding: 0.5rem 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1rem;
  color: #333;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  font-weight: 500;
}

.dropdown-item:hover {
  background-color: #f5f5f5;
  padding-left: 1.25rem;
}

.item-icon {
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
}

.item-text {
  flex: 1;
}

.logout-item {
  color: #d32f2f;
  padding: 0.875rem 1rem;
  margin-top: 0.5rem;
}

.logout-item:hover {
  background-color: #ffebee;
  padding-left: 1.25rem;
}

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
}

@media (max-width: 768px) {
  .settings-dropdown-menu {
    min-width: 250px;
    right: -10px;
  }
}
</style>
