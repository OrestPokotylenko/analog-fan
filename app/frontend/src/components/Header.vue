<template>
  <header class="navbar navbar-expand-lg navbar-light bg-light fixed-top w-100">
    <div class="container-fluid">
      <router-link to="/" class="navbar-brand">Analog-fan</router-link>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse d-flex justify-content-between align-items-center" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <router-link to="/" class="nav-link active">Home</router-link>
          </li>
          <li class="nav-item dropdown">
            <router-link to="/categories" class="nav-link dropdown-toggle" id="categoriesDropdown" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">
              Categories
            </router-link>
            <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
              <li><router-link to="/categories/cassettes" class="dropdown-item">Cassettes</router-link></li>
              <li><router-link to="/categories/vinyls" class="dropdown-item">Vinyl</router-link></li>
              <li><router-link to="/categories/players" class="dropdown-item">Players</router-link></li>
            </ul>
          </li>
        </ul>
        <form class="d-flex mx-auto search-form" @submit.prevent="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
            v-model="searchQuery" />
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
        <div class="ms-auto">
          <button v-if="!isLoggedIn" class="btn btn-outline-primary" @click="login">Login</button>
          <div v-else class="navbar-text">
            Welcome, {{ user ? user.username : '' }}
            <button class="btn btn-outline-danger ms-2" @click="logout">Logout</button>
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
</style>