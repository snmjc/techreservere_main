<template>
  <div class="post-login">
    <p>Signing you in...</p>
  </div>
</template>

<script setup>
import { usePostLoginRedirect } from './composables/usePostLoginRedirect.js';

usePostLoginRedirect();
</script>

<style scoped>
.post-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  color: #555;
}
</style>
