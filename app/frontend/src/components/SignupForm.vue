<template>
  <div class="row justify-content-center">
    <div class="card">
      <div class="card-header">
        <h3>Sign Up</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="signup">
          <div class="mb-3">
            <label for="firstName" class="form-label">First name</label>
            <input type="text" class="form-control" id="firstName" v-model="firstName" required />
          </div>
          <div class="mb-3">
            <label for="lastName" class="form-label">Last name</label>
            <input type="text" class="form-control" id="lastName" v-model="lastName" required />
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" v-model="username" required />
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" id="email" v-model="email" required />
          </div>
          <div class="mb-5">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" v-model="password" required />
          </div>
          <div class="mb-5">
            <label for="repeatPassword" class="form-label">Repeat password</label>
            <input type="password" class="form-control" id="repeatPassword" v-model="repeatPassword" required />
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign up</button>
        </form>
      </div>
      <div class="d-flex justify-content-center">
        <router-link to="/login">Already have an account? Login</router-link>
      </div>
      <div class="mt-3" style="height: 48px;">
        <div v-if="errorMessage" :class="{ 'alert': true, 'alert-danger': !success, 'alert-success': success }">
          {{ errorMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../services/axiosConfig';

export default {
  data() {
    return {
      firstName: '',
      lastName: '',
      username: '',
      email: '',
      password: '',
      repeatPassword: '',
      errorMessage: '',

    };
  },
  methods: {
    async signup() {
      if (this.password !== this.repeatPassword) {
        this.errorMessage = 'Passwords do not match.';
        return;
      }

      try {
        const response = await this.registerUser();
        if (response.data.success) {
          await this.authenticateUser();
          this.$router.push('/');
        } else {
          this.errorMessage = response.data.message;
        }
      } catch (error) {
        this.errorMessage = 'Error during registration. Please try again.';
      }
    },
    async registerUser() {
      return axios.post('/users', {
        firstName: this.firstName,
        lastName: this.lastName,
        username: this.username,
        email: this.email,
        password: this.password,
        repeatPassword: this.repeatPassword,
      });
    },
    async authenticateUser() {
      const response = await axios.get('/authenticate', {
        params: {
          username: this.username,
          password: this.password
        }
      });

      // Store the token and user in localStorage for persistence
      localStorage.setItem('jwtToken', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));

      // Update global properties
      this.$root.$auth.isLoggedIn = true;
      this.$root.$auth.token = response.data.token;
      this.$root.$auth.user = response.data.user;
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