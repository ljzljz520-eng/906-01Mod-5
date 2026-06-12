import { createRouter, createWebHistory } from 'vue-router'
import SearchPage from '../views/SearchPage.vue'
import FavoritesPage from '../views/FavoritesPage.vue'
import HistoryPage from '../views/HistoryPage.vue'
import SecurityAdminPage from '../views/SecurityAdminPage.vue'
import SecurityAdvisoriesPage from '../views/SecurityAdvisoriesPage.vue'

const routes = [
  {
    path: '/',
    name: 'Search',
    component: SearchPage
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: FavoritesPage
  },
  {
    path: '/history',
    name: 'History',
    component: HistoryPage
  },
  {
    path: '/security',
    name: 'SecurityAdvisories',
    component: SecurityAdvisoriesPage
  },
  {
    path: '/admin/security',
    name: 'SecurityAdmin',
    component: SecurityAdminPage
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
