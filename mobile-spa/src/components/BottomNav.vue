<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronRight, House, LayoutGrid, LogOut, Search, UserRound, X } from '@lucide/vue'
import { auth } from '../services/auth.js'
import { DOCK_KEYS, MODULES, MODULE_GROUPS } from '../services/modules.js'

const route = useRoute()
const router = useRouter()
const menuOpen = ref(false)
const query = ref('')
const sheet = ref(null)
let previousOverflow = ''
let returnFocus = null

const allowed = computed(() => MODULES.filter((m) => auth.can(m.route)))
const dockItems = computed(() => DOCK_KEYS.map((key) => allowed.value.find((m) => m.key === key)).filter(Boolean))
const dockKeySet = computed(() => new Set(dockItems.value.map((m) => m.key)))
const columns = computed(() => dockItems.value.length + 2)

const groups = computed(() => {
  const term = query.value.trim().toLocaleLowerCase('tr-TR')
  return MODULE_GROUPS
    .map((group) => ({
      ...group,
      items: allowed.value.filter((m) => m.group === group.id && !dockKeySet.value.has(m.key) && (!term || `${m.label} ${m.subtitle}`.toLocaleLowerCase('tr-TR').includes(term))),
    }))
    .filter((group) => group.items.length)
})

const moreActive = computed(() => menuOpen.value || (route.meta.module && !dockItems.value.some((m) => m.to === route.path)))

watch(menuOpen, async (open) => {
  if (open) {
    returnFocus = document.activeElement
    previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    await nextTick()
    sheet.value?.querySelector('button')?.focus()
  } else {
    query.value = ''
    document.body.style.overflow = previousOverflow
    if (returnFocus?.isConnected) returnFocus.focus()
  }
})
watch(() => route.fullPath, () => { menuOpen.value = false })
onBeforeUnmount(() => { if (menuOpen.value) document.body.style.overflow = previousOverflow })

function handleKeydown(event) {
  if (event.key === 'Escape') { menuOpen.value = false; return }
  if (event.key !== 'Tab') return
  const focusable = [...sheet.value.querySelectorAll('button, input, [tabindex="0"]')]
  const first = focusable[0]
  const last = focusable.at(-1)
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus() }
}

function open(path) {
  router.push(path)
}

async function logout() {
  menuOpen.value = false
  await auth.logout()
  await router.replace('/giris')
}
</script>

<template>
  <Transition name="sheet">
    <div v-if="menuOpen" class="sheet-layer" @keydown="handleKeydown" @click.self="menuOpen = false">
      <section ref="sheet" class="sheet" role="dialog" aria-modal="true" aria-label="Tüm menüler">
        <header class="sheet__header">
          <div>
            <span class="sheet__eyebrow">MENÜ</span>
            <h2 class="sheet__title">Daha Fazla</h2>
          </div>
          <button type="button" class="sheet__close" aria-label="Kapat" @click="menuOpen = false"><X :size="19" /></button>
        </header>

        <div class="sheet__content">
          <section v-for="group in groups" :key="group.id" class="sheet__group">
            <h3>{{ group.label }}</h3>
            <div class="sheet__grid">
              <button v-for="item in group.items" :key="item.key" type="button" class="sheet-item" :class="`theme-${item.theme}`" @click="open(item.to)">
                <span class="sheet-item__icon"><component :is="item.icon" :size="20" /></span>
                <span class="sheet-item__text">
                  <strong>{{ item.label }}</strong>
                  <small>{{ item.subtitle }}</small>
                </span>
                <ChevronRight :size="16" class="sheet-item__arrow" />
              </button>
            </div>
          </section>

          <p v-if="!groups.length" class="sheet__empty">Eşleşen menü bulunamadı.</p>
        </div>

        <footer class="sheet__footer">
          <label class="sheet__search">
            <Search :size="17" />
            <input v-model="query" type="search" placeholder="Menüde ara…" aria-label="Menüde ara" />
          </label>
          <button type="button" class="sheet__profile" aria-label="Profil" title="Profil" @click="open('/profil')"><UserRound :size="19" /></button>
          <button type="button" class="sheet__logout" aria-label="Çıkış yap" title="Çıkış yap" @click="logout"><LogOut :size="19" /></button>
        </footer>
      </section>
    </div>
  </Transition>

  <nav class="bottom-nav" aria-label="Ana gezinme" :style="{ '--cols': columns }">
    <router-link to="/" class="nav-link" :class="{ 'is-active': route.path === '/' }" :aria-current="route.path === '/' ? 'page' : undefined">
      <House :size="22" />
      <span>Ana Sayfa</span>
    </router-link>

    <router-link
      v-for="item in dockItems"
      :key="item.key"
      :to="item.to"
      class="nav-link"
      :class="{ 'is-active': route.path === item.to }"
      :aria-current="route.path === item.to ? 'page' : undefined"
    >
      <component :is="item.icon" :size="22" />
      <span>{{ item.navLabel }}</span>
    </router-link>

    <button type="button" class="nav-link" :class="{ 'is-active': moreActive }" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
      <LayoutGrid :size="22" />
      <span>Diğer</span>
    </button>
  </nav>
</template>

<style scoped>
.bottom-nav { position: fixed; z-index: 50; left: 50%; bottom: calc(12px + var(--ds-safe-bottom)); transform: translateX(-50%); width: min(calc(100% - 32px), 440px); min-height: 72px; padding: 8px; display: grid; grid-template-columns: repeat(var(--cols, 5), minmax(0, 1fr)); align-items: center; border: 1px solid var(--ds-border-dark); border-radius: var(--ds-radius-pill); background: var(--ds-surface-dark); box-shadow: var(--ds-shadow-float); }
.nav-link { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; height: 54px; padding: 5px 2px; border: 0; border-radius: var(--ds-radius-pill); background: transparent; color: var(--ds-muted-light); text-decoration: none; cursor: pointer; transition: background .18s ease, color .18s ease, transform .12s ease; }
.nav-link span { font: 600 10px/1.2 var(--ds-font-main); white-space: nowrap; }
.nav-link:active { transform: scale(.92); }
.nav-link.is-active { background: var(--ds-surface); color: var(--ds-ink); }
.nav-link:focus-visible { outline: 2px solid #fff; outline-offset: -2px; }

.sheet-layer { position: fixed; inset: 0; z-index: 80; display: flex; align-items: flex-end; justify-content: center; background: rgba(15, 18, 24, .5); }
.sheet { width: min(100%, 520px); max-height: 88svh; display: flex; flex-direction: column; padding: 18px 16px calc(18px + var(--ds-safe-bottom)); border-radius: 28px 28px 0 0; background: var(--ds-surface); box-shadow: 0 -16px 40px rgba(0, 0, 0, .25); }
.sheet__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.sheet__eyebrow { color: var(--ds-muted); font-size: 10px; font-weight: 800; letter-spacing: .12em; }
.sheet__title { margin: 2px 0 0; font-size: 22px; font-weight: 800; }
.sheet__close { display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); cursor: pointer; }
.sheet__footer { display: flex; align-items: center; gap: 10px; flex-shrink: 0; padding-top: 12px; margin-top: 8px; border-top: 1px solid var(--ds-surface-subtle); background: var(--ds-surface); }
.sheet__search { flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 14px; border-radius: var(--ds-radius-pill); background: var(--ds-surface-subtle); color: var(--ds-muted); }
.sheet__search input { flex: 1; min-width: 0; border: 0; background: transparent; outline: 0; font-size: 16px; color: var(--ds-ink); }
.sheet__content { flex: 1; min-height: 0; overflow-y: auto; display: grid; gap: 18px; padding-bottom: 4px; }
.sheet__group h3 { margin: 0 0 8px 4px; color: var(--ds-muted); font-size: 11px; font-weight: 800; letter-spacing: .1em; }
.sheet__grid { display: grid; gap: 8px; }
.sheet-item { display: flex; align-items: center; gap: 12px; width: 100%; min-height: 60px; padding: 10px 12px; border: 0; border-radius: var(--ds-radius-tile); background: var(--ds-surface-subtle); text-align: left; cursor: pointer; color: var(--ds-ink); }
.sheet-item:active { transform: scale(.985); }
.sheet-item__icon { display: grid; place-items: center; flex-shrink: 0; width: 40px; height: 40px; border-radius: 14px; background: var(--ds-surface); color: var(--tone, var(--ds-ink)); box-shadow: var(--ds-shadow-tile); }
.sheet-item__text { flex: 1; min-width: 0; display: grid; gap: 2px; }
.sheet-item__text strong { font-size: 14px; font-weight: 700; }
.sheet-item__text small { color: var(--ds-muted); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sheet-item__arrow { color: var(--ds-muted-light); flex-shrink: 0; }
.theme-blue { --tone: #0284c7; } .theme-emerald { --tone: #059669; } .theme-amber { --tone: #d97706; } .theme-purple { --tone: #7c3aed; } .theme-coral { --tone: #e11d48; }
.sheet__empty { text-align: center; color: var(--ds-muted); font-size: 14px; margin: 12px 0; }
.sheet__profile { display: grid; place-items: center; flex-shrink: 0; width: 48px; height: 48px; border: 0; border-radius: 50%; background: var(--ds-surface-subtle); color: var(--ds-ink); cursor: pointer; }
.sheet__logout { display: grid; place-items: center; flex-shrink: 0; width: 48px; height: 48px; border: 0; border-radius: 50%; background: var(--ds-danger-bg); color: var(--ds-danger-ink); cursor: pointer; }

.sheet-enter-active, .sheet-leave-active { transition: opacity .2s ease; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .28s cubic-bezier(.16, 1, .3, 1); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
@media (prefers-reduced-motion: reduce) { .sheet-enter-active, .sheet-leave-active, .sheet-enter-active .sheet, .sheet-leave-active .sheet, .nav-link { transition: none; } }
</style>
