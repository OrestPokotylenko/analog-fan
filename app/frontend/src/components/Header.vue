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
              <li><RouterLink to="/category/cassettes" class="dropdown-item">Cassettes</RouterLink></li>
              <li><RouterLink to="/category/vinyls" class="dropdown-item">Vinyl</RouterLink></li>
              <li><RouterLink to="/category/players" class="dropdown-item">Players</RouterLink></li>
            </ul>
          </li>
        </ul>
        <form class="d-flex mx-5 search-form" @submit.prevent="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
            v-model="searchQuery" />
          <button class="btn btn-outline-success nav-button" type="submit">Search</button>
        </form>
        <RouterLink to="/your-items" class="btn btn-outline-primary nav-button">Sell</RouterLink>
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

<script>
export default {
  data() {
    return {
      searchQuery: ''
    };
  },
  computed: {
    isLoggedIn() {
      return this.$auth.isLoggedIn;
    },
    user() {
      return this.$auth.user;
    }
  },
  methods: {
    search() {
      console.log('Searching for:', this.searchQuery);
      // Implement your search logic here
    },
    login() {
      this.$router.push('/login');
    },
    logout() {
      localStorage.removeItem('jwtToken');
      localStorage.removeItem('user');
      this.$auth.isLoggedIn = false;
      this.$auth.token = null;
      this.$auth.user = null;
      this.$router.push('/');
    }
  }
};
</script>

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