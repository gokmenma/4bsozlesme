<script setup>
import { CircleCheck } from '@lucide/vue'
defineProps({ message: { type: String, default: '' } })
</script>

<template>
  <Teleport to="body">
    <Transition name="m-island">
      <div v-if="message" class="m-island" role="status" aria-live="polite"><CircleCheck :size="18" /><span>{{ message }}</span></div>
    </Transition>
  </Teleport>
</template>
