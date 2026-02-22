<template>
  <div class="reset-container">
    <div class="reset-card">
      <div class="reset-header">
        <h2>Reset Password</h2>
        <p>Enter your email to receive a reset link</p>
      </div>

      <form @submit.prevent="resetPassword" class="reset-form">
        <div class="form-group">
          <label for="email" class="form-label">Email Address</label>
          <input 
            type="email" 
            id="email" 
            v-model="email" 
            class="form-input"
            placeholder="your@email.com"
            required 
          />
        </div>

        <button 
          type="submit" 
          class="btn-reset"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Sending...' : 'Send Reset Link' }}
        </button>
      </form>

      <div v-if="errorMessage" :class="['message', { 'error': !success, 'success': success }]">
        {{ errorMessage }}
      </div>

      <div class="reset-footer">
        <p>Remember your password? 
          <router-link to="/login" class="link">Login</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../services/axiosConfig';

export default {
  data() {
    return {
      email: '',
      errorMessage: '',
      success: false,
      isSubmitting: false
    };
  },
  methods: {
    async resetPassword() {
      if (this.isSubmitting) return;
      
      try {
        this.isSubmitting = true;
        this.errorMessage = '';
        const response = await axios.get('/reset-password', {
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
      } finally {
        this.isSubmitting = false;
      }
    }
    }
  };
</script>

<style scoped>
.reset-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 20vh;
}

.reset-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 16px;
  padding: 50px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.reset-header {
  text-align: center;
  margin-bottom: 40px;
}

.reset-header h2 {
  font-size: 2em;
  font-weight: 800;
  color: white;
  margin: 0 0 10px 0;
}

.reset-header p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0;
}

.reset-form {
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

.btn-reset:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
}

.message {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.9em;
  margin-bottom: 20px;
}

.message.error {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  color: #ff7a8a;
}

.message.success {
  background: rgba(40, 167, 69, 0.1);
  border: 1px solid rgba(40, 167, 69, 0.3);
  color: #28a745;
}

.reset-footer {
  text-align: center;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  padding-top: 20px;
}

.reset-footer p {
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
  .reset-card {
    padding: 30px 20px;
  }

  .reset-header h2 {
    font-size: 1.6em;
  }

  .form-input {
    padding: 10px 12px;
    font-size: 0.95em;
  }
}
</style>