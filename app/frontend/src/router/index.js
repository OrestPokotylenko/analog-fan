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
  { path: '/cart', component: () => import('../views/ShoppingCart.vue') },
  { path: '/profile', component: () => import('../views/Profile.vue') },
  { path: '/wishlist', component: () => import('../views/Wishlist.vue') },
  { path: '/admin', component: () => import('../views/Admin.vue') }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard to redirect admins and handle admin routes
router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('user') || 'null');
  
  // If user is admin and trying to access non-admin pages
  if (user && user.role === 'admin' && to.path !== '/admin' && to.path !== '/login') {
    next('/admin');
  }
  // If non-admin trying to access admin page
  else if (to.path === '/admin' && (!user || user.role !== 'admin')) {
    next('/');
  }
  // Allow navigation
  else {
    next();
  }
});

export default router;