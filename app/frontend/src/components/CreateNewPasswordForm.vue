<template>
  <div class="row justify-content-center">
    <div class="card">
      <div class="card-header">
        <h3>Create New Password</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="resetPassword">
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" v-model="password" required />
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Repeat Password</label>
            <input type="password" class="form-control" id="repeatPassword" v-model="repeatPassword" required />
          </div>
          <button type="submit" class="btn btn-primary w-100" :disabled="isSubmitting">Reset Password</button>
        </form>
      </div>
      <div class="d-flex justify-content-center">
        <router-link to="/login">Would you like to login?</router-link>
      </div>
    </div>
    <div class="mt-3" style="height: 48px;">
      <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
    </div>
  </div>
</template>

<script>
import axios from '../services/axiosConfig';

export default {
  data() {
    return {
      password: '',
      repeatPassword: '',
      isSubmitting: false,
      token: '',
      errorMessage: ''
    };
  },
  created() {
    this.token = this.$route.query.token;
  },
  methods: {
    async resetPassword() {
      if (this.password !== this.repeatPassword) {
        this.errorMessage = 'Passwords do not match.';
        return;
      }

      this.isSubmitting = true;

      try {
        const response = await axios.put('/reset-password', {
          token: this.token,
          password: this.password
        });

        console.log(response);
        this.$router.push('/login');

      } catch (error) {
        this.errorMessage = 'Error during password reseet. Please try again.';
      } finally {
        this.isSubmitting = false;
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