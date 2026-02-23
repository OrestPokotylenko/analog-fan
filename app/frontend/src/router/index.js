import { createRouter, createWebHistory } from 'vue-router';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const routes = [
  { name: 'home', path: '/', component: () => import('../views/home/Home.vue') },
  { name: 'login', path: '/login', component: () => import('../views/auth/Login.vue') },
  { name: 'signup', path: '/signup', component: () => import('../views/auth/Signup.vue') },
  { name: 'reset-password', path: '/reset-password', component: () => import('../views/auth/ResetPassword.vue') },
  { name: 'categories', path: '/categories', component: () => import('../views/item/Categories.vue') },
  { name: 'category-items', path: '/category/:typeName', component: () => import('../views/item/CategoryItems.vue') },
  { name: 'item-details', path: '/item/:id', component: () => import('../views/item/ItemDetails.vue') },
  { name: 'my-items', path: '/my-items', component: () => import('../views/sales/SellingItems.vue') },
  { name: 'upload-item', path: '/my-items/upload', component: () => import('../views/item/UploadItem.vue') },
  { name: 'edit-item', path: '/my-items/:id', component: () => import('../views/item/EditItem.vue') },
  { name: 'my-sales', path: '/my-sales', component: () => import('../views/sales/MySales.vue') },
  { name: 'cart', path: '/cart', component: () => import('../views/order/ShoppingCart.vue') },
  { name: 'checkout', path: '/checkout', component: () => import('../views/order/Checkout.vue') },
  { name: 'order-confirmation', path: '/order-confirmation', component: () => import('../views/order/OrderConfirmation.vue') },
  { name: 'orders', path: '/orders', component: () => import('../views/order/Orders.vue') },
  { name: 'order-details', path: '/orders/:id', component: () => import('../views/order/OrderDetails.vue') },
  { name: 'profile', path: '/profile', component: () => import('../views/user/Profile.vue') },
  { name: 'wishlist', path: '/wishlist', component: () => import('../views/wishlist/Wishlist.vue') },
  { name: 'messages', path: '/messages', component: () => import('../views/message/Messages.vue') },
  { name: 'conversation', path: '/messages/:conversationId', component: () => import('../views/message/Messages.vue') },
  { name: 'admin', path: '/admin', component: () => import('../views/admin/Admin.vue') }
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