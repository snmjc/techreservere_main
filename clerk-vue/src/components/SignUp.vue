<template>
  <div class="signup-container">
    <div class="signup-card">
      <h1>Sign Up</h1>
      <SignUp />
    </div>
  </div>
</template>

<script setup lang="ts">
import { SignUp } from '@clerk/vue'
</script>

<style scoped>
.signup-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.signup-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

h1 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}
</style>
