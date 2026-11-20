import { createRouter, createWebHashHistory } from 'vue-router'
import LoginView from './views/LoginView.vue'
import HomeView from './views/HomeView.vue'
import ModuleView from './views/ModuleView.vue'
import PersonnelView from './views/PersonnelView.vue'
import ProfileView from './views/ProfileView.vue'
import { MODULES } from './services/modules.js'
import { auth } from './services/auth.js'
import { startNavigation, finishNavigation } from './services/navigation.js'

const routes = [
  {
    path: '/giris',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profil',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  ...MODULES.map((m) => ({
    path: m.to,
    name: `module-${m.key}`,
    component: m.key === 'personnel' ? PersonnelView : ModuleView,
    meta: { requiresAuth: true, module: m.key, permission: m.route },
  })),
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

router.beforeEach(async (to, from) => {
  if (to.fullPath !== from.fullPath) startNavigation()
  await auth.bootstrap()

  if (to.meta.requiresAuth && !auth.state.authenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.state.authenticated) {
    return { name: 'home' }
  }

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    return { name: 'home' }
  }

  return true
})

router.afterEach(() => finishNavigation())
router.onError(() => finishNavigation())

export default router
