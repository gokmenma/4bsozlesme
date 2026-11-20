<script setup>
import BottomNav from './components/BottomNav.vue'
import NavigationProgress from './components/NavigationProgress.vue'
import { auth } from './services/auth.js'
</script>

<template>
  <div class="app-shell">
    <NavigationProgress />
    <router-view />
    <BottomNav v-if="auth.state.authenticated && $route.meta.requiresAuth" />
  </div>
</template>
