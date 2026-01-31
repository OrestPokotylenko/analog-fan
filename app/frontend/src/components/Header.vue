<script setup>
import { inject, ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../services/axiosConfig';

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
const searchQuery = ref('');

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
}

function closeMobileMenu() {
  mobileMenuOpen.value = false;
}

onMounted(fetchProductTypes);
</script>

<template>
  <header class="header">
    <div class="header-container">
      <RouterLink to="/" class="logo">🎵 Analog Fan</RouterLink>
      
      <form class="search-form" @submit.prevent="handleSearch">
        <div class="search-container">
          <input 
            v-model="searchQuery" 
            type="text" 
            class="search-input" 
            placeholder="Search for items..."
            @keyup.enter="handleSearch"
          />
          <button type="submit" class="search-btn">
            <span>🔍</span>
          </button>
        </div>
      </form>
      
      <button class="mobile-menu-btn" @click="toggleMobileMenu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav :class="['nav', { open: mobileMenuOpen }]">
        <div class="nav-categories">
          <a href="#" class="nav-link">Categories</a>
          <div class="dropdown">
            <RouterLink to="/categories" class="dropdown-item">All Categories</RouterLink>
            <RouterLink 
              v-for="type in productTypes" 
              :key="type.productTypeId"
              :to="`/category/${type.name.toLowerCase()}`"
              class="dropdown-item"
              @click="closeMobileMenu"
            >
              {{ type.name }}
            </RouterLink>
          </div>
        </div>

        <div class="nav-actions">
          <button class="cart-btn" title="Shopping Cart">
            <span>🛒</span>
          </button>
          <RouterLink to="/my-items" class="nav-button primary" @click="closeMobileMenu">Sell</RouterLink>
          
          <div v-if="!isLoggedIn" class="auth-buttons">
            <button class="nav-button primary" @click="login">Login</button>
          </div>
          <div v-else class="user-section">
            <span class="username">{{ user?.username }}</span>
            <button class="nav-button danger" @click="logout">Logout</button>
          </div>
        </div>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-bottom: 2px solid #e94560;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.header-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  height: 70px;
}

.logo {
  font-size: 1.8em;
  font-weight: 800;
  color: #e94560;
  text-decoration: none;
  letter-spacing: 1px;
  transition: color 0.3s;
  white-space: nowrap;
  flex-shrink: 0;
}

.logo:hover {
  color: #ff6b7a;
}

.search-form {
  flex: 1;
  max-width: 400px;
  margin: 0 20px;
}

.search-container {
  display: flex;
  gap: 0;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(233, 69, 96, 0.2);
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s;
}

.search-container:focus-within {
  border-color: rgba(233, 69, 96, 0.5);
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  color: white;
  padding: 10px 16px;
  font-size: 0.95em;
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.search-input:focus {
  outline: none;
}

.search-btn {
  background: transparent;
  border: none;
  color: #e94560;
  padding: 10px 16px;
  cursor: pointer;
  font-size: 1.1em;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-btn:hover {
  color: #ff6b7a;
}

.nav {
  display: flex;
  align-items: center;
  gap: 40px;
  flex-shrink: 0;
}

.nav-categories {
  position: relative;
}

.nav-link {
  color: #e0e0e0;
  text-decoration: none;
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 6px;
  transition: all 0.3s;
  font-size: 1em;
}

.nav-link:hover {
  color: #e94560;
  background-color: rgba(233, 69, 96, 0.1);
}

.dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  background: #0f3460;
  border-radius: 8px;
  min-width: 220px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  padding: 8px 0;
  margin-top: 10px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s ease;
}

.nav-categories:hover .dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: block;
  padding: 12px 20px;
  color: #e0e0e0;
  text-decoration: none;
  transition: all 0.3s;
  font-size: 0.95em;
}

.dropdown-item:hover {
  background-color: rgba(233, 69, 96, 0.2);
  color: #e94560;
  padding-left: 24px;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.nav-button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 0.95em;
  text-decoration: none;
  display: inline-block;
  white-space: nowrap;
}

.nav-button.primary {
  background-color: #e94560;
  color: white;
}

.nav-button.primary:hover {
  background-color: #ff6b7a;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(233, 69, 96, 0.3);
}

.nav-button.secondary {
  background-color: transparent;
  color: #e0e0e0;
  border: 2px solid #e0e0e0;
}

.nav-button.secondary:hover {
  background-color: #e0e0e0;
  color: #1a1a2e;
}

.nav-button.danger {
  background-color: #e94560;
  color: white;
}

.nav-button.danger:hover {
  background-color: #ff6b7a;
}

.user-section {
  display: flex;
  align-items: center;
  gap: 15px;
}

.username {
  color: #e0e0e0;
  font-weight: 600;
  font-size: 0.95em;
}

.cart-btn {
  background: none;
  border: none;
  color: #e0e0e0;
  font-size: 1.5em;
  cursor: pointer;
  transition: all 0.3s;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-btn:hover {
  color: #e94560;
  transform: scale(1.1);
}

.auth-buttons {
  display: flex;
  gap: 10px;
}

.mobile-menu-btn {
  display: none;
  flex-direction: column;
  background: none;
  border: none;
  cursor: pointer;
  gap: 6px;
  width: 30px;
  height: 24px;
  flex-shrink: 0;
}

.mobile-menu-btn span {
  width: 100%;
  height: 2px;
  background-color: #e0e0e0;
  border-radius: 2px;
  transition: all 0.3s;
}

@media (max-width: 1024px) {
  .search-form {
    max-width: 300px;
  }
}

@media (max-width: 768px) {
  .header-container {
    height: 60px;
    padding: 0 15px;
    gap: 10px;
  }

  .logo {
    font-size: 1.4em;
  }

  .search-form {
    display: none;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .nav {
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    flex-direction: column;
    gap: 20px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    border-bottom: 2px solid #e94560;
    padding: 0 20px;
    align-items: stretch;
  }

  .nav.open {
    max-height: 600px;
    padding: 20px;
  }

  .nav-categories {
    width: 100%;
  }

  .nav-link {
    display: block;
  }

  .dropdown {
    position: static;
    opacity: 1;
    visibility: visible;
    transform: none;
    background: rgba(15, 52, 96, 0.8);
    margin-top: 0;
    margin-left: 0;
    box-shadow: none;
    padding: 0;
  }

  .dropdown-item {
    border-left: 3px solid transparent;
  }

  .dropdown-item:hover {
    border-left-color: #e94560;
  }

  .nav-actions {
    width: 100%;
    flex-direction: column;
    gap: 10px;
    margin-left: 0;
  }

  .nav-button {
    width: 100%;
    text-align: center;
  }

  .user-section {
    width: 100%;
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .header-container {
    padding: 0 10px;
  }

  .logo {
    font-size: 1.2em;
  }
}

.nav-link {
  color: #e0e0e0;
  text-decoration: none;
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 6px;
  transition: all 0.3s;
  font-size: 1em;
}

.nav-link:hover {
  color: #e94560;
  background-color: rgba(233, 69, 96, 0.1);
}

.dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  background: #0f3460;
  border-radius: 8px;
  min-width: 220px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  padding: 8px 0;
  margin-top: 10px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s ease;
}

.nav-categories:hover .dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: block;
  padding: 12px 20px;
  color: #e0e0e0;
  text-decoration: none;
  transition: all 0.3s;
  font-size: 0.95em;
}

.dropdown-item:hover {
  background-color: rgba(233, 69, 96, 0.2);
  color: #e94560;
  padding-left: 24px;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-left: auto;
}

.nav-button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 0.95em;
  text-decoration: none;
  display: inline-block;
  white-space: nowrap;
}

.nav-button.primary {
  background-color: #e94560;
  color: white;
}

.nav-button.primary:hover {
  background-color: #ff6b7a;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(233, 69, 96, 0.3);
}

.nav-button.secondary {
  background-color: transparent;
  color: #e0e0e0;
  border: 2px solid #e0e0e0;
}

.nav-button.secondary:hover {
  background-color: #e0e0e0;
  color: #1a1a2e;
}

.nav-button.danger {
  background-color: #e94560;
  color: white;
}

.nav-button.danger:hover {
  background-color: #ff6b7a;
}

.user-section {
  display: flex;
  align-items: center;
  gap: 15px;
}

.username {
  color: #e0e0e0;
  font-weight: 600;
  font-size: 0.95em;
}

.auth-buttons {
  display: flex;
  gap: 10px;
}

.mobile-menu-btn {
  display: none;
  flex-direction: column;
  background: none;
  border: none;
  cursor: pointer;
  gap: 6px;
  width: 30px;
  height: 24px;
}

.mobile-menu-btn span {
  width: 100%;
  height: 2px;
  background-color: #e0e0e0;
  border-radius: 2px;
  transition: all 0.3s;
}

@media (max-width: 768px) {
  .header-container {
    height: 60px;
    padding: 0 15px;
  }

  .logo {
    font-size: 1.4em;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .nav {
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    flex-direction: column;
    margin-left: 0;
    padding: 20px;
    gap: 20px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    border-bottom: 2px solid #e94560;
  }

  .nav.open {
    max-height: 500px;
  }

  .nav-categories {
    width: 100%;
  }

  .nav-link {
    display: block;
  }

  .dropdown {
    position: static;
    opacity: 1;
    visibility: visible;
    transform: none;
    background: rgba(15, 52, 96, 0.8);
    margin-top: 0;
    margin-left: 0;
    box-shadow: none;
    padding: 0;
  }

  .dropdown-item {
    border-left: 3px solid transparent;
  }

  .dropdown-item:hover {
    border-left-color: #e94560;
  }

  .nav-actions {
    width: 100%;
    flex-direction: column;
    gap: 10px;
    margin-left: 0;
  }

  .nav-button {
    width: 100%;
    text-align: center;
  }

  .user-section {
    width: 100%;
    flex-direction: column;
  }
}
</style>