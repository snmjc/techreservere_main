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
@import './css/PostLogin.css';
</style>

