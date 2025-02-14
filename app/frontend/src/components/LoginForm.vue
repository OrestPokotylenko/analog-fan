<template>
  <div class="row justify-content-center">
    <div class="card">
      <div class="card-header">
        <h3>Login</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="handleLogin">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" v-model="username" required />
          </div>
          <div class="mb-5">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" v-model="password" required />
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
      <div class="d-flex justify-content-between">
        <router-link to="/signup">Sign up</router-link>
        <router-link to="/forgot-password">Forgot Password?</router-link>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      username: '',
      password: '',
    };
  },
  methods: {
    async handleLogin() {
      try {
        const response = await axios.get('http://localhost/api/authenticate', {
          params: {
            username: this.username,
            password: this.password
          }
        });

        if (response.data.success) {
          localStorage.setItem('jwtToken', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));

          this.$root.$auth.isLoggedIn = true;
          this.$root.$auth.token = response.data.token;
          this.$root.$auth.user = response.data.user;

          this.$router.push('/');
        } else {
          console.error('Login failed:', response.data.message);
        }
      } catch (error) {
        console.error('Error during login:', error);
      }
    }
  }
};
</script>

<style scoped>
.container {
  padding-top: 50px;
}

.card {
  margin-top: 20px;
}

.card-header {
  text-align: center;
}

.card-body {
  padding: 20px;
}

@media (max-width: 768px) {
  .card {
    width: 100%;
    max-width: 90%;
    margin: auto;
  }
}

@media (min-width: 768px) and (max-width: 991px) {
  .card {
    width: 100%;
    max-width: 600px;
    margin: auto;
  }
}

@media (min-width: 992px) and (max-width: 1199px) {
  .card {
    width: 100%;
    max-width: 700px;
    margin: auto;
  }
}

@media (min-width: 1200px) {
  .card {
    width: 200%;
    max-width: 800px;
    margin: auto;
  }
}
</style>