<template>
  <div class="dashboard-container">
    <div class="dashboard-card">
      <div class="header">
        <h1>Welcome to Dashboard</h1>
        <button @click="handleSignOut" class="btn-logout-small">Sign Out</button>
      </div>
      <div v-if="user">
        <p>Hello, {{ user.firstName }} {{ user.lastName }}!</p>
        <p>Email: {{ user.primaryEmailAddress?.emailAddress }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useUser, useAuth } from '@clerk/vue'
import { useRouter } from 'vue-router'

const { user } = useUser()
const { signOut } = useAuth()
const router = useRouter()

async function handleSignOut() {
  try {
    await signOut.value()
    router.push('/login')
  } catch (error) {
    console.error('Sign out error:', error)
    router.push('/login')
  }
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.dashboard-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

h1 {
  margin: 0;
  color: #333;
  font-size: 1.5rem;
}

.btn-logout-small {
  padding: 0.5rem 1rem;
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background-color 0.3s;
}

.btn-logout-small:hover {
  background-color: #da190b;
}

p {
  margin: 0.5rem 0;
  color: #666;
}
</style>
