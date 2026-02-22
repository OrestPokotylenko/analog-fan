<script setup>
import { inject, ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../../services/axiosConfig';
import CartService from '../../services/CartService';
import MessageService from '../../services/MessageService';
import WebSocketService from '../../services/WebSocketService';

const router = useRouter();

const $auth = inject('$auth', {
  isLoggedIn: false,
  user: null,
  token: null
});

const isLoggedIn = computed(() => $auth.isLoggedIn);
const user = computed(() => $auth.user);

const productTypes = ref([]);
const isLoadingTypes = ref(false);
const mobileMenuOpen = ref(false);
const mobileCategoriesOpen = ref(false);
const mobileSearchOpen = ref(false);
const searchQuery = ref('');
const cartCount = ref(0);
const unreadMessagesCount = ref(0);
const userDropdownOpen = ref(false);

const handleWebSocketMessage = (data) => {
  if (data.type === 'new_message') {
    updateUnreadMessagesCount();
  }
};

async function fetchProductTypes() {
  try {
    isLoadingTypes.value = true;
    const response = await axios.get('/product-types');
    productTypes.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Failed to load product types', error);
    productTypes.value = [];
  } finally {
    isLoadingTypes.value = false;
  }
}

function handleSearch(e) {
  if (e) e.preventDefault();
  if (searchQuery.value.trim()) {
    router.push({
      path: '/categories',
      query: { search: searchQuery.value }
    });
    searchQuery.value = '';
    mobileMenuOpen.value = false;
    mobileSearchOpen.value = false;
  }
}

function login() {
  router.push('/login');
}

function logout() {
  localStorage.removeItem('jwtToken');
  localStorage.removeItem('user');
  $auth.isLoggedIn = false;
  $auth.token = null;
  $auth.user = null;
  router.push('/');
  mobileMenuOpen.value = false;
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  if (!mobileMenuOpen.value) {
    mobileCategoriesOpen.value = false;
  }
}

function closeMobileMenu() {
  mobileMenuOpen.value = false;
  mobileCategoriesOpen.value = false;
  userDropdownOpen.value = false;
}

async function updateCartCount() {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.userId) {
      cartCount.value = await CartService.getCartCount(user.userId);
    } else {
      cartCount.value = 0;
    }
  } catch (error) {
    console.error('Failed to load cart count:', error);
    cartCount.value = 0;
  }
}

async function updateUnreadMessagesCount() {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.userId) {
      unreadMessagesCount.value = await MessageService.getUnreadCount(user.userId);
    } else {
      unreadMessagesCount.value = 0;
    }
  } catch (error) {
    console.error('Failed to load unread messages count:', error);
    unreadMessagesCount.value = 0;
  }
}

function handleStorageChange(e) {
  if (e.key === 'cart') updateCartCount();
}

function handleCartUpdate() { updateCartCount(); }
function handleMessagesUpdate() { updateUnreadMessagesCount(); }

function handleClickOutside(e) {
  if (userDropdownOpen.value && !e.target.closest('.user-dropdown-container')) {
    userDropdownOpen.value = false;
  }
}

onMounted(() => {
  fetchProductTypes();
  updateCartCount();
  updateUnreadMessagesCount();
  window.addEventListener('storage', handleStorageChange);
  window.addEventListener('cart-updated', handleCartUpdate);
  window.addEventListener('messages-updated', handleMessagesUpdate);
  document.addEventListener('click', handleClickOutside);

  const user = JSON.parse(localStorage.getItem('user') || 'null');
  if (user && user.userId) {
    if (!WebSocketService.isConnected()) {
      WebSocketService.connect(user.userId);
    }
    WebSocketService.onMessage(handleWebSocketMessage);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('storage', handleStorageChange);
  window.removeEventListener('cart-updated', handleCartUpdate);
  window.removeEventListener('messages-updated', handleMessagesUpdate);
  document.removeEventListener('click', handleClickOutside);
  WebSocketService.removeMessageHandler(handleWebSocketMessage);
});
</script>

<template>
  <header class="header">
    <div class="header-container">

      <!-- Logo -->
      <RouterLink to="/" class="logo" @click="closeMobileMenu">🎵 Analog Fan</RouterLink>

      <!-- Desktop Search -->
      <form class="search-form desktop-only" @submit.prevent="handleSearch">
        <div class="search-container">
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search for items..."
            @keyup.enter="handleSearch"
          />
          <button type="submit" class="search-btn">🔍</button>
        </div>
      </form>

      <!-- Right side actions (desktop) -->
      <div class="header-right desktop-only">
        <!-- Categories dropdown -->
        <div class="nav-categories">
          <button class="nav-link">Categories ▾</button>
          <div class="dropdown">
            <RouterLink to="/categories" class="dropdown-item">All Categories</RouterLink>
            <RouterLink
              v-for="type in productTypes"
              :key="type.productTypeId"
              :to="`/category/${type.typeName.toLowerCase()}`"
              class="dropdown-item"
            >
              {{ type.typeName }}
            </RouterLink>
          </div>
        </div>

        <!-- Cart -->
        <RouterLink to="/cart" class="cart-btn" title="Shopping Cart">
          🛒
          <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
        </RouterLink>

        <!-- Auth -->
        <button v-if="!isLoggedIn" class="nav-button primary" @click="login">Login</button>

        <div v-else class="user-dropdown-container">
          <button class="username-btn" @click.stop="userDropdownOpen = !userDropdownOpen">
            {{ user?.username }} <span class="arrow" :class="{ rotated: userDropdownOpen }">▼</span>
          </button>
          <div :class="['user-dropdown', { show: userDropdownOpen }]">
            <RouterLink to="/profile" class="user-dropdown-item" @click="userDropdownOpen = false">👤 Profile</RouterLink>
            <RouterLink to="/messages" class="user-dropdown-item" @click="userDropdownOpen = false">
              💬 Messages
              <span v-if="unreadMessagesCount > 0" class="dropdown-badge">{{ unreadMessagesCount }}</span>
            </RouterLink>
            <RouterLink to="/wishlist" class="user-dropdown-item" @click="userDropdownOpen = false">❤️ Wishlist</RouterLink>
            <RouterLink to="/orders" class="user-dropdown-item" @click="userDropdownOpen = false">📦 My Orders</RouterLink>
            <RouterLink to="/my-items" class="user-dropdown-item" @click="userDropdownOpen = false">🛍️ My Items</RouterLink>
            <RouterLink to="/my-sales" class="user-dropdown-item" @click="userDropdownOpen = false">💰 My Sales</RouterLink>
            <button class="user-dropdown-item logout-item" @click="logout">🚪 Logout</button>
          </div>
        </div>
      </div>

      <!-- Mobile: icon row -->
      <div class="mobile-icons">
        <button class="icon-btn" @click="mobileSearchOpen = !mobileSearchOpen" title="Search">🔍</button>
        <RouterLink to="/cart" class="icon-btn cart-btn" title="Cart">
          🛒
          <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
        </RouterLink>
        <button
          class="icon-btn hamburger"
          @click="toggleMobileMenu"
          :class="{ active: mobileMenuOpen }"
          title="Menu"
          aria-label="Toggle menu"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>

    <!-- Mobile Search Bar (slides down) -->
    <div :class="['mobile-search-bar', { open: mobileSearchOpen }]">
      <form @submit.prevent="handleSearch" class="mobile-search-form">
        <input
          v-model="searchQuery"
          type="text"
          class="search-input"
          placeholder="Search for items..."
          @keyup.enter="handleSearch"
        />
        <button type="submit" class="search-btn">🔍</button>
      </form>
    </div>

    <!-- Mobile Menu -->
    <div :class="['mobile-menu', { open: mobileMenuOpen }]">
      <!-- Categories accordion -->
      <div class="mobile-section">
        <button class="mobile-section-header" @click="mobileCategoriesOpen = !mobileCategoriesOpen">
          <span>Categories</span>
          <span class="arrow" :class="{ rotated: mobileCategoriesOpen }">▼</span>
        </button>
        <div :class="['mobile-section-body', { open: mobileCategoriesOpen }]">
          <RouterLink to="/categories" class="mobile-link" @click="closeMobileMenu">All Categories</RouterLink>
          <RouterLink
            v-for="type in productTypes"
            :key="type.productTypeId"
            :to="`/category/${type.typeName.toLowerCase()}`"
            class="mobile-link"
            @click="closeMobileMenu"
          >
            {{ type.typeName }}
          </RouterLink>
        </div>
      </div>

      <!-- Not logged in -->
      <div v-if="!isLoggedIn" class="mobile-section">
        <button class="nav-button primary full-width" @click="login(); closeMobileMenu()">Login</button>
      </div>

      <!-- Logged in -->
      <div v-else class="mobile-section">
        <div class="mobile-user-label">👤 {{ user?.username }}</div>
        <RouterLink to="/profile" class="mobile-link" @click="closeMobileMenu">Profile</RouterLink>
        <RouterLink to="/messages" class="mobile-link" @click="closeMobileMenu">
          Messages
          <span v-if="unreadMessagesCount > 0" class="dropdown-badge">{{ unreadMessagesCount }}</span>
        </RouterLink>
        <RouterLink to="/wishlist" class="mobile-link" @click="closeMobileMenu">Wishlist</RouterLink>
        <RouterLink to="/orders" class="mobile-link" @click="closeMobileMenu">My Orders</RouterLink>
        <RouterLink to="/my-items" class="mobile-link" @click="closeMobileMenu">My Items</RouterLink>
        <RouterLink to="/my-sales" class="mobile-link" @click="closeMobileMenu">My Sales</RouterLink>
        <button class="mobile-link logout-link" @click="logout">Logout 🚪</button>
      </div>
    </div>
  </header>
</template>

<style scoped>
/* ── Base ── */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-bottom: 2px solid #e94560;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  margin: 0;
  padding: 0;
}

.header-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  height: 64px;
}

/* ── Logo ── */
.logo {
  font-size: 1.6em;
  font-weight: 800;
  color: #e94560;
  text-decoration: none;
  letter-spacing: 1px;
  white-space: nowrap;
  flex-shrink: 0;
  transition: color 0.2s;
}
.logo:hover { color: #ff6b7a; }

/* ── Desktop search ── */
.search-form {
  flex: 1;
  margin: 0 16px;
}

.search-container,
.mobile-search-form {
  display: flex;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(233,69,96,0.25);
  border-radius: 8px;
  overflow: hidden;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-container:focus-within,
.mobile-search-form:focus-within {
  border-color: rgba(233,69,96,0.6);
  box-shadow: 0 0 0 3px rgba(233,69,96,0.12);
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  padding: 10px 14px;
  font-size: 0.95em;
  outline: none;
}
.search-input::placeholder { color: rgba(255,255,255,0.38); }

.search-btn {
  background: transparent;
  border: none;
  color: #e94560;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 1em;
  transition: color 0.2s;
}
.search-btn:hover { color: #ff6b7a; }

/* ── Desktop right section ── */
.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
  margin-left: auto;
}

/* ── Categories dropdown ── */
.nav-categories { position: relative; }

.nav-link {
  background: none;
  border: none;
  color: #e0e0e0;
  font-weight: 600;
  font-size: 0.95em;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  transition: color 0.2s, background 0.2s;
}
.nav-link:hover {
  color: #e94560;
  background: rgba(233,69,96,0.1);
}

.dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  background: #0f3460;
  border-radius: 8px;
  min-width: 200px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  padding: 6px 0;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: all 0.2s ease;
  z-index: 200;
}
.nav-categories:hover .dropdown,
.nav-categories:focus-within .dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: block;
  padding: 10px 18px;
  color: #e0e0e0;
  text-decoration: none;
  font-size: 0.93em;
  transition: background 0.2s, color 0.2s, padding-left 0.2s;
}
.dropdown-item:hover {
  background: rgba(233,69,96,0.18);
  color: #e94560;
  padding-left: 24px;
}

/* ── Cart button ── */
.cart-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e0e0e0;
  text-decoration: none;
  font-size: 1.4em;
  padding: 6px;
  transition: color 0.2s, transform 0.2s;
}
.cart-btn:hover { color: #e94560; transform: scale(1.1); }

.cart-badge, .dropdown-badge {
  background: linear-gradient(135deg, #e94560, #ff6b7a);
  color: #fff;
  font-size: 0.62rem;
  font-weight: 700;
  border-radius: 10px;
  min-width: 17px;
  height: 17px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  box-shadow: 0 2px 6px rgba(233,69,96,0.4);
}
.cart-badge {
  position: absolute;
  top: 0;
  right: 0;
}

/* ── Nav button ── */
.nav-button {
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.93em;
  white-space: nowrap;
  transition: all 0.2s;
}
.nav-button.primary {
  background: #e94560;
  color: #fff;
}
.nav-button.primary:hover {
  background: #ff6b7a;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(233,69,96,0.35);
}
.full-width { width: 100%; text-align: center; }

/* ── User dropdown ── */
.user-dropdown-container { position: relative; }

.username-btn {
  background: transparent;
  border: 2px solid #e94560;
  color: #e0e0e0;
  padding: 8px 14px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.93em;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s, color 0.2s;
}
.username-btn:hover {
  background: rgba(233,69,96,0.1);
  color: #e94560;
}

.arrow {
  font-size: 0.65em;
  display: inline-block;
  transition: transform 0.2s;
}
.arrow.rotated { transform: rotate(180deg); }

.user-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #0f3460;
  border-radius: 8px;
  min-width: 195px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  padding: 6px 0;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: all 0.2s ease;
  z-index: 200;
}
.user-dropdown.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.user-dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  color: #e0e0e0;
  text-decoration: none;
  font-size: 0.93em;
  font-weight: 500;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.user-dropdown-item:hover {
  background: rgba(233,69,96,0.18);
  color: #e94560;
}
.user-dropdown-item .dropdown-badge { margin-left: auto; }

.logout-item {
  border-top: 1px solid rgba(233,69,96,0.2);
  margin-top: 4px;
}

/* ── Mobile icons (always visible on mobile) ── */
.mobile-icons {
  display: none;
  align-items: center;
  gap: 4px;
  margin-left: auto;
}

.icon-btn {
  position: relative;
  background: none;
  border: none;
  color: #e0e0e0;
  font-size: 1.35em;
  cursor: pointer;
  padding: 7px 9px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s, background 0.2s;
  text-decoration: none;
}
.icon-btn:hover { color: #e94560; background: rgba(233,69,96,0.1); }

/* Hamburger */
.hamburger {
  flex-direction: column;
  gap: 5px;
  font-size: 1em;
  width: 38px;
  height: 38px;
}
.hamburger span {
  display: block;
  width: 22px;
  height: 2px;
  background: #e0e0e0;
  border-radius: 2px;
  transition: all 0.3s;
  transform-origin: center;
}
.hamburger.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.active span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ── Mobile search bar ── */
.mobile-search-bar {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
  background: #12122a;
  border-top: 1px solid rgba(233,69,96,0.15);
}
.mobile-search-bar.open {
  max-height: 80px;
}
.mobile-search-form {
  margin: 10px 16px;
  border-radius: 8px;
}

/* ── Mobile menu panel ── */
.mobile-menu {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.35s ease;
  background: #12122a;
  border-top: 1px solid rgba(233,69,96,0.2);
}
.mobile-menu.open {
  max-height: 600px;
  overflow-y: auto;
}

.mobile-section {
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding: 4px 0;
}

.mobile-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  background: none;
  border: none;
  color: #e0e0e0;
  font-size: 0.97em;
  font-weight: 700;
  padding: 14px 20px;
  cursor: pointer;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  transition: color 0.2s;
}
.mobile-section-header:hover { color: #e94560; }

.mobile-section-body {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}
.mobile-section-body.open { max-height: 400px; }

.mobile-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 28px;
  color: rgba(224,224,224,0.85);
  text-decoration: none;
  font-size: 0.95em;
  transition: color 0.2s, background 0.2s, padding-left 0.2s;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
}
.mobile-link:hover {
  color: #e94560;
  background: rgba(233,69,96,0.08);
  padding-left: 34px;
}

.logout-link {
  color: #e94560;
  border-top: 1px solid rgba(233,69,96,0.15);
  margin-top: 4px;
}

.mobile-user-label {
  padding: 12px 20px 6px;
  color: #e94560;
  font-weight: 700;
  font-size: 0.95em;
}

.mobile-section .nav-button { margin: 10px 20px; width: calc(100% - 40px); }

/* Tablet/mid: shrink elements but keep desktop layout */
@media (max-width: 1024px) and (min-width: 769px) {
  .header-container { gap: 10px; padding: 0 14px; }
  .logo { font-size: 1.35em; }
  .search-form { margin: 0 10px; }
  .search-input { padding: 9px 12px; font-size: 0.93em; }
  .nav-link { padding: 7px 10px; font-size: 0.88em; }
  .username-btn { padding: 7px 10px; font-size: 0.88em; }
  .nav-button { padding: 7px 12px; font-size: 0.88em; }
  .header-right { gap: 10px; }
}

@media (max-width: 768px) {
  .header-container { height: 58px; padding: 0 14px; gap: 10px; }
  .logo { font-size: 1.3em; }
  .desktop-only { display: none !important; }
  .mobile-icons { display: flex; }
}

@media (max-width: 400px) {
  .logo { font-size: 1.1em; }
  .header-container { padding: 0 10px; }
}
</style>