import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', component: () => import('../views/Home.vue') },
  { path: '/login', component: () => import('../views/Login.vue') },
  { path: '/signup', component: () => import('../views/Signup.vue') },
  { path: '/reset-password', component: () => import('../views/ResetPassword.vue') },
  { path: '/category', component: () => import('../views/Categories.vue') },
  { path: '/category/cassettes', component: () => import('../views/Cassettes.vue') },
  { path: '/category/vinyls', component: () => import('../views/Vinyls.vue') },
  { path: '/category/players', component: () => import('../views/Players.vue') },
  { path: '/your-items', component: () => import('../views/SellingItems.vue') },
  { path: '/your-items/upload', component: () => import('../views/UploadItem.vue') }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;