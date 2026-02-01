import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', component: () => import('../views/Home.vue') },
  { path: '/login', component: () => import('../views/Login.vue') },
  { path: '/signup', component: () => import('../views/Signup.vue') },
  { path: '/reset-password', component: () => import('../views/ResetPassword.vue') },
  { path: '/categories', component: () => import('../views/Categories.vue') },
  { path: '/category/:typeName', component: () => import('../views/CategoryItems.vue') },
  { path: '/item/:id', component: () => import('../views/ItemDetails.vue') },
  { path: '/my-items', component: () => import('../views/SellingItems.vue') },
  { path: '/my-items/upload', component: () => import('../views/UploadItem.vue') },
  { path: '/my-items/:id', component: () => import('../views/EditItem.vue') },
  { path: '/cart', component: () => import('../views/ShoppingCart.vue') }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;