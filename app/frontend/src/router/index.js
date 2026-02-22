import { createRouter, createWebHistory } from 'vue-router';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const routes = [
  { path: '/', component: () => import('../views/home/Home.vue') },
  { path: '/login', component: () => import('../views/auth/Login.vue') },
  { path: '/signup', component: () => import('../views/auth/Signup.vue') },
  { path: '/reset-password', component: () => import('../views/auth/ResetPassword.vue') },
  { path: '/categories', component: () => import('../views/item/Categories.vue') },
  { path: '/category/:typeName', component: () => import('../views/item/CategoryItems.vue') },
  { path: '/item/:id', component: () => import('../views/item/ItemDetails.vue') },
  { path: '/my-items', component: () => import('../views/sales/SellingItems.vue') },
  { path: '/my-items/upload', component: () => import('../views/item/UploadItem.vue') },
  { path: '/my-items/:id', component: () => import('../views/item/EditItem.vue') },
  { path: '/my-sales', component: () => import('../views/sales/MySales.vue') },
  { path: '/cart', component: () => import('../views/order/ShoppingCart.vue') },
  { path: '/checkout', component: () => import('../views/order/Checkout.vue') },
  { path: '/order-confirmation', component: () => import('../views/order/OrderConfirmation.vue') },
  { path: '/orders', component: () => import('../views/order/Orders.vue') },
  { path: '/orders/:id', component: () => import('../views/order/OrderDetails.vue') },
  { path: '/profile', component: () => import('../views/user/Profile.vue') },
  { path: '/wishlist', component: () => import('../views/wishlist/Wishlist.vue') },
  { path: '/messages', component: () => import('../views/message/Messages.vue') },
  { path: '/messages/:conversationId', component: () => import('../views/message/Messages.vue') },
  { path: '/admin', component: () => import('../views/admin/Admin.vue') }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard to redirect admins and handle admin routes
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('jwtToken');
  const user = JSON.parse(localStorage.getItem('user') || 'null');
  
  // Check if token is expired and clear auth state immediately
  if (token && isTokenExpired(token)) {
    clearAuthState();
    if (to.path !== '/login' && to.path !== '/signup' && to.path !== '/reset-password' && to.path !== '/') {
      return next('/login');
    }
  }
  
  // Protected routes that require login
  const protectedRoutes = ['/profile', '/cart', '/checkout', '/order-confirmation', '/orders', '/my-items', '/my-sales', '/wishlist', '/messages', '/admin'];
  const isProtectedRoute = protectedRoutes.some(route => to.path.startsWith(route));
  
  if (isProtectedRoute && (!token || isTokenExpired(token))) {
    return next('/login');
  }
  
  // If user is admin
  if (user && user.role === 'admin') {
    // Allow admin panel, login, and order details viewing
    if (to.path === '/admin' || to.path === '/login' || to.path.match(/^\/orders\/\d+$/)) {
      next();
    } else {
      // Redirect to admin panel for all other pages
      next('/admin');
    }
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