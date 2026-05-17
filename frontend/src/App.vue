<script setup lang="ts">
import { useClerk } from '@clerk/vue'
import { ref, onMounted } from 'vue'

const { isSignedIn, user } = useClerk()
const isLoading = ref(true)

onMounted(() => {
  // Wait for Clerk to initialize
  setTimeout(() => {
    isLoading.value = false
  }, 1000)
})
</script>

<template>
  <div v-if="isLoading" class="loading-container">
    <p>Loading...</p>
  </div>
  <div v-else>
    <router-view />
  </div>
</template>

<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  font-size: 1.2rem;
  color: #666;
}
</style>
