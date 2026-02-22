<template>
  <div class="auth-page">
    <router-link to="/" class="logo-link">
      Analog Fan
    </router-link>
    <div class="form-container">
      <div v-if="isLoading" class="text-center">
        <p>Loading...</p>
      </div>
      <ResetPasswordForm v-else-if="!hasToken" />
      <CreateNewPasswordForm v-else-if="isTokenValid" />
      <div v-else class="alert alert-danger">Invalid or expired token</div>
    </div>
  </div>
</template>

<script>
import axios from '../../services/axiosConfig';
import ResetPasswordForm from '../../components/auth/ResetPasswordForm.vue';
import CreateNewPasswordForm from '../../components/auth/CreateNewPasswordForm.vue';

export default {
  components: {
    ResetPasswordForm,
    CreateNewPasswordForm
  },
  data() {
    return {
      isTokenValid: false,
      hasToken: !!this.$route.query.token,
      isLoading: true
    };
  },
  async created() {
    if (this.hasToken) {
      try {
        const response = await axios.get('/validate-token', {
          params: { token: this.$route.query.token }
        });

        if (response.data.isValid) {
          this.isTokenValid = true;
        } else {
          this.$router.push('/login');
        }
      } catch (error) {
        console.error('Error validating token:', error);
        this.$router.push('/login');
      } finally {
        this.isLoading = false;
      }
    } else {
      this.isLoading = false;
    }
  }
};
</script>

<style scoped>
.auth-page {
  width: 100vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  overflow: hidden;
  position: relative;
}

.logo-link {
  position: absolute;
  top: 30px;
  left: 30px;
  font-size: 1.5em;
  font-weight: 800;
  color: white;
  text-decoration: none;
  transition: all 0.3s;
  letter-spacing: 0.5px;
}

.logo-link:hover {
  color: #e94560;
  transform: scale(1.05);
}

.form-container {
  width: 90%;
  max-width: 600px;
}

.text-center {
  text-align: center;
  color: #b0b0b0;
}

.alert {
  padding: 20px;
  border-radius: 8px;
  text-align: center;
}

.alert-danger {
  background: rgba(255, 107, 122, 0.1);
  color: #ff6b7a;
  border: 1px solid #ff6b7a;
}
</style>
  
  <style scoped>
  .text-center {
    text-align: center;
    margin-top: 20px;
  }
  </style>