<template>
  <div class="row justify-content-center">
    <div class="card">
      <div class="card-header">
        <h3>Reset password</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="resetPassword">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" id="email" v-model="email" required />
          </div>
          <button type="submit" id="resetButon" class="btn btn-primary w-100">Reset password</button>
        </form>
      </div>
      <div class="d-flex justify-content-center">
        <router-link to="/login">Would you like to login?</router-link>
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
import axios from 'axios';

export default {
  data() {
    return {
      email: '',
      errorMessage: '',
      success: false
    };
  },
  methods: {
    async resetPassword() {
      resetButon.disabled = true;

      try {
        const response = await axios.get('http://localhost/api/reset-password', {
          params: {
            email: this.email
          }
        });

        if (response.data.success) {
          this.success = true;
          this.errorMessage = response.data.message;
          this.$router.push('/login');
        } else {
          this.success = false;
          this.errorMessage = response.data.message;
        }

      } catch (error) {
        this.errorMessage = "An error occurred. Please try again.";
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