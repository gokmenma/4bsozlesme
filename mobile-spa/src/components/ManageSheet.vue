<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { X } from '@lucide/vue'

defineProps({ title: { type: String, required: true } })
const emit = defineEmits(['close'])
const root = ref(null)
let previousOverflow = ''
let returnFocus = null

function onKey(event) {
  if (event.key === 'Escape') emit('close')
}

onMounted(async () => {
  returnFocus = document.activeElement
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  await nextTick()
  root.value?.querySelector('input, select, textarea, button')?.focus()
})

onBeforeUnmount(() => {
  document.body.style.overflow = previousOverflow
  if (returnFocus?.isConnected) returnFocus.focus()
})
</script>

<template>
  <Teleport to="body">
    <div class="m-layer" @keydown="onKey" @click.self="emit('close')">
      <section ref="root" class="m-sheet" role="dialog" aria-modal="true" :aria-label="title">
        <header class="m-sheet__head">
          <h2>{{ title }}</h2>
          <button type="button" class="m-close" aria-label="Kapat" @click="emit('close')"><X :size="19" /></button>
        </header>
        <slot />
      </section>
    </div>
  </Teleport>
</template>
