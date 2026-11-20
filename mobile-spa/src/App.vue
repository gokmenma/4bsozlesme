<script setup>
import BottomNav from './components/BottomNav.vue'
import NavigationProgress from './components/NavigationProgress.vue'
import InstallPrompt from './components/InstallPrompt.vue'
import { auth } from './services/auth.js'
</script>

<template>
  <div class="app-shell">
    <NavigationProgress />
    <InstallPrompt />
    <router-view />
    <BottomNav v-if="auth.state.authenticated && $route.meta.requiresAuth" />
  </div>
</template>
