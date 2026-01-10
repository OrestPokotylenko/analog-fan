<script setup>
import { inject, ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

// Inject $auth from global properties
const $auth = inject('$auth', {
  isLoggedIn: false,
  user: null,
  token: null
});

// Computed properties for authentication state
const isLoggedIn = computed(() => $auth.isLoggedIn);
const user = computed(() => $auth.user);

// Methods
function search() {
  console.log('Searching for:', searchQuery.value);
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
}
</script>

<template>
  <header class="navbar navbar-expand-lg navbar-light bg-light fixed-top w-100">
    <div class="container-fluid">
      <RouterLink to="/" class="navbar-brand">Analog-fan</RouterLink>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse d-flex" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <RouterLink to="/category" class="nav-link dropdown-toggle" id="categoriesDropdown" role="button"
              aria-expanded="false">
              Categories
            </RouterLink>
            <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
              <li>
                <RouterLink to="/category/cassettes" class="dropdown-item">Cassettes</RouterLink>
              </li>
              <li>
                <RouterLink to="/category/vinyls" class="dropdown-item">Vinyl</RouterLink>
              </li>
              <li>
                <RouterLink to="/category/players" class="dropdown-item">Players</RouterLink>
              </li>
            </ul>
          </li>
        </ul>
        <form class="d-flex mx-5 search-form" @submit.prevent="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
            v-model="searchQuery" />
          <button class="btn btn-outline-success nav-button" type="submit">Search</button>
        </form>
        <RouterLink to="/my-items" class="btn btn-outline-primary nav-button">Sell</RouterLink>
        <RouterLink to="/cart" class="btn btn-outline-secondary ms-auto nav-button">Cart</RouterLink>
        <RouterLink to="/watchlist" class="btn btn-outline-secondary ms-5 nav-button">Watchlist</RouterLink>
        <div class="ms-5">
          <button v-if="!isLoggedIn" class="btn btn-outline-primary nav-button" @click="login">Login</button>
          <div v-else class="navbar-text">
            Welcome, {{ user ? user.username : '' }}
            <button class="btn btn-outline-danger ms-2 nav-button" @click="logout">Logout</button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
header {
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1030;
}

.search-form {
  width: 40%;
}

.nav-item.dropdown:hover .dropdown-menu {
  display: block;
}

.nav-button {
  width: 90px;
}
</style>