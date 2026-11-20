<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Hammer } from '@lucide/vue'
import TopBar from '../components/TopBar.vue'
import { moduleByPath } from '../services/modules.js'

const route = useRoute()
const mod = computed(() => moduleByPath(route.path))
</script>

<template>
  <div class="page">
    <TopBar :eyebrow="'MODÜL'" :title="mod?.label || 'Modül'" :back="true" />
    <main class="module">
      <div class="card">
        <span class="card__icon"><component :is="mod?.icon || Hammer" :size="26" /></span>
        <h2>{{ mod?.label }}</h2>
        <p>{{ mod?.subtitle }}</p>
        <span class="card__tag">Mobil ekranı hazırlanıyor</span>
      </div>
    </main>
  </div>
</template>

<style scoped>
.page { min-height: 100svh; }
.module { padding: 14px 16px calc(112px + var(--ds-safe-bottom)); max-width: 520px; margin: 0 auto; }
.card { display: grid; justify-items: center; gap: 8px; padding: 36px 22px; text-align: center; background: var(--ds-surface); border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-bento); }
.card__icon { display: grid; place-items: center; width: 60px; height: 60px; border-radius: 20px; background: var(--ds-surface-dark); color: #fff; }
.card h2 { margin: 8px 0 0; font-size: 20px; }
.card p { margin: 0; color: var(--ds-muted); font-size: 14px; }
.card__tag { margin-top: 10px; padding: 6px 14px; border-radius: 999px; background: var(--ds-surface-subtle); color: var(--ds-muted); font-size: 12px; font-weight: 700; }
</style>
