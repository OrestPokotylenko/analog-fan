<script setup>
import { inject, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const $auth = inject('$auth', {
  isLoggedIn: false,
  user: null,
  token: null
});

// Reactive state
const username = ref('');
const password = ref('');
const errorMessage = ref('');

// Methods
async function handleLogin() {
  try {
    const response = await axios.get('http://localhost/api/authenticate', {
      params: {
        username: username.value,
        password: password.value
      }
    });

    if (response.data.success) {
      // Update localStorage
      localStorage.setItem('jwtToken', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));

      // Update $auth global property
      $auth.isLoggedIn = true;
      $auth.token = response.data.token;
      $auth.user = response.data.user;

      // Redirect to home page
      router.push('/');
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error) {
    errorMessage.value = 'An error occurred during login. Please try again.';
  }
}
</script>

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
        <router-link to="/reset-password">Forgot Password?</router-link>
      </div>
      <div class="mt-3" style="height: 48px;">
        <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
      </div>
    </div>
  </div>
</template>

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