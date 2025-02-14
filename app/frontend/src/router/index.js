import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', component: () => import('../views/Home.vue') },
  { path: '/login', component: () => import('../views/Login.vue') },
  { path: '/signup', component: () => import('../views/Signup.vue') },
  { path: '/categories', component: () => import('../views/Categories.vue') },
  { path: '/categories/cassettes', component: () => import('../views/Cassettes.vue') },
  { path: '/categories/vinyls', component: () => import('../views/Vinyls.vue') },
  { path: '/categories/players', component: () => import('../views/Players.vue') }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;