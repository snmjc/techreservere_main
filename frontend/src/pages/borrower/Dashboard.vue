<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>Welcome back, {{ userFullName }}!</h1>
      <p class="subtitle">Here's your reservation overview</p>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards">
      <div class="stat-card active-reservations">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.activeReservations }}</div>
          <div class="stat-label">Active Reservations</div>
        </div>
      </div>

      <div class="stat-card approved-requests">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.approvedRequests }}</div>
          <div class="stat-label">Approved Requests</div>
        </div>
      </div>

      <div class="stat-card pending-requests">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.pendingRequests }}</div>
          <div class="stat-label">Pending Requests</div>
        </div>
      </div>

      <div class="stat-card completed-reservations">
        <div class="stat-icon">✓</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.completedReservations }}</div>
          <div class="stat-label">Completed Reservations</div>
        </div>
      </div>
    </div>

    <!-- Quick Access Section -->
    <div class="quick-access">
      <h2>Quick Access</h2>
      <div class="quick-access-buttons">
        <button @click="goToCreateReservation" class="quick-btn primary">
          <span class="btn-icon">➕</span>
          Create New Reservation
        </button>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
      <h2>Recent Activity</h2>
      <div v-if="loading" class="loading">Loading...</div>
      <div v-else-if="recentActivity.length === 0" class="no-activity">
        No recent activity
      </div>
      <div v-else class="activity-list">
        <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
          <div class="activity-icon">{{ getActivityIcon(activity.status) }}</div>
          <div class="activity-details">
            <div class="activity-title">{{ activity.facility_name || activity.title }}</div>
            <div class="activity-date">{{ formatDate(activity.created_at) }}</div>
          </div>
          <div class="activity-status" :class="activity.status.toLowerCase()">
            {{ activity.status }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthenticationStore } from '@/modules/authentication/store/authenticationStore';

const router = useRouter();
const authStore = useAuthenticationStore();

const stats = ref({
  activeReservations: 0,
  approvedRequests: 0,
  pendingRequests: 0,
  completedReservations: 0
});

const recentActivity = ref([]);
const loading = ref(true);

const userFullName = computed(() => authStore.userFullName);

onMounted(async () => {
  await loadDashboardData();
});

async function loadDashboardData() {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/dashboard/borrower/summary', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': authStore.authToken ? `Bearer ${authStore.authToken}` : ''
      }
    });

    if (!response.ok) {
      throw new Error('Failed to load dashboard data');
    }

    const data = await response.json();
    console.log('Dashboard API Response:', data);
    
    // Handle different response structures
    if (data.data) {
      stats.value = data.data;
    } else if (data.activeReservations !== undefined) {
      stats.value = data;
    } else {
      // If no data structure matches, set to zeros
      stats.value = {
        activeReservations: 0,
        approvedRequests: 0,
        pendingRequests: 0,
        completedReservations: 0
      };
    }

    // For recent activity, we'll need a separate endpoint or include it in the summary
    // For now, keeping it empty until backend endpoint is ready
    recentActivity.value = [];
  } catch (error) {
    console.error('Error loading dashboard data:', error);
    // Set to zeros when API fails or for new accounts with no data
    stats.value = {
      activeReservations: 0,
      approvedRequests: 0,
      pendingRequests: 0,
      completedReservations: 0
    };
    recentActivity.value = [];
  } finally {
    loading.value = false;
  }
}

function goToCreateReservation() {
  console.log('Navigating to create reservation...');
  alert('Navigating to create reservation page...');
  router.push('/borrower/create-reservation').catch(err => {
    console.error('Navigation error:', err);
    alert('Navigation error: ' + err.message);
  });
}

function getActivityIcon(status) {
  const icons = {
    'Approved': '✅',
    'Pending': '⏳',
    'Completed': '✓',
    'Active': '📅',
    'Rejected': '❌'
  };
  return icons[status] || '📋';
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
}
</script>

<style scoped src="./css/Dashboard.css"></style>
