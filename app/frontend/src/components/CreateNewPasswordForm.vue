<template>
  <div class="password-container">
    <div class="password-card">
      <div class="password-header">
        <h2>Create New Password</h2>
        <p>Enter your new password to reset your account</p>
      </div>

      <form @submit.prevent="resetPassword" class="password-form">
        <div class="form-group">
          <label for="password" class="form-label">New Password</label>
          <input 
            type="password" 
            id="password" 
            v-model="password" 
            class="form-input"
            placeholder="Enter new password"
            required 
          />
        </div>

        <div class="form-group">
          <label for="repeatPassword" class="form-label">Confirm Password</label>
          <input 
            type="password" 
            id="repeatPassword" 
            v-model="repeatPassword" 
            class="form-input"
            placeholder="Confirm password"
            required 
          />
        </div>

        <button type="submit" class="btn-reset" :disabled="isSubmitting">Reset Password</button>
      </form>

      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>

      <div class="password-footer">
        <p>Ready to login? 
          <router-link to="/login" class="link">Go to Login</router-link>
        </p>
      </div>
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
.password-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  margin-top: 70px;
  padding-top: 40px;
}

.password-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 16px;
  padding: 50px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.password-header {
  text-align: center;
  margin-bottom: 40px;
}

.password-header h2 {
  font-size: 2em;
  font-weight: 800;
  color: white;
  margin: 0 0 10px 0;
}

.password-header p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0;
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 30px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  color: #e0e0e0;
  font-weight: 600;
  font-size: 0.95em;
  letter-spacing: 0.3px;
}

.form-input {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(233, 69, 96, 0.2);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 1em;
  transition: all 0.3s ease;
}

.form-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.form-input:focus {
  outline: none;
  border-color: rgba(233, 69, 96, 0.6);
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
}

.btn-reset {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px 24px;
  font-size: 1em;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  letter-spacing: 0.5px;
  margin-top: 10px;
}

.btn-reset:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
}

.btn-reset:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  color: #ff7a8a;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.9em;
  margin-bottom: 20px;
}

.password-footer {
  text-align: center;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  padding-top: 20px;
}

.password-footer p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0;
}

.link {
  color: #e94560;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.link:hover {
  color: #ff6b7a;
  text-decoration: underline;
}

@media (max-width: 480px) {
  .password-card {
    padding: 30px 20px;
  }

  .password-header h2 {
    font-size: 1.6em;
  }

  .form-input {
    padding: 10px 12px;
    font-size: 0.95em;
  }
}
</style>