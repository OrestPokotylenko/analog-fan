<script setup>
import { inject, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../services/axiosConfig';

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
    const response = await axios.get('/authenticate', {
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
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h2>Welcome Back</h2>
        <p>Sign in to your account</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <input 
            type="text" 
            id="username" 
            v-model="username" 
            class="form-input"
            placeholder="Enter your username"
            required 
          />
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <input 
            type="password" 
            id="password" 
            v-model="password" 
            class="form-input"
            placeholder="Enter your password"
            required 
          />
        </div>

        <button type="submit" class="btn-login">Sign In</button>
      </form>

      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>

      <div class="login-footer">
        <p>Don't have an account? 
          <router-link to="/signup" class="link">Sign up</router-link>
        </p>
        <router-link to="/reset-password" class="link-secondary">Forgot password?</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  margin-top: 70px;
}

.login-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 16px;
  padding: 50px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.login-header h2 {
  font-size: 2em;
  font-weight: 800;
  color: white;
  margin: 0 0 10px 0;
}

.login-header p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0;
}

.login-form {
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

.btn-login {
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

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
}

.btn-login:active {
  transform: translateY(0);
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

.login-footer {
  text-align: center;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  padding-top: 20px;
}

.login-footer p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0 0 12px 0;
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

.link-secondary {
  color: #b0b0b0;
  text-decoration: none;
  font-size: 0.9em;
  transition: color 0.3s;
}

.link-secondary:hover {
  color: #e94560;
}

@media (max-width: 480px) {
  .login-card {
    padding: 30px 20px;
  }

  .login-header h2 {
    font-size: 1.6em;
  }

  .form-input {
    padding: 10px 12px;
    font-size: 0.95em;
  }
}
</style>