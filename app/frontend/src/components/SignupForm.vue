<template>
  <div class="signup-container">
    <div class="signup-card">
      <div class="signup-header">
        <h2>Create Account</h2>
        <p>Join our community of analog enthusiasts</p>
      </div>

      <form @submit.prevent="signup" class="signup-form">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName" class="form-label">First name</label>
            <input type="text" id="firstName" v-model="firstName" class="form-input" required />
          </div>
          <div class="form-group">
            <label for="lastName" class="form-label">Last name</label>
            <input type="text" id="lastName" v-model="lastName" class="form-input" required />
          </div>
        </div>

        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <input type="text" id="username" v-model="username" class="form-input" placeholder="Choose a username" required />
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input type="email" id="email" v-model="email" class="form-input" placeholder="your@email.com" required />
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" v-model="password" class="form-input" placeholder="Enter password" required />
        </div>

        <div class="form-group">
          <label for="repeatPassword" class="form-label">Confirm Password</label>
          <input type="password" id="repeatPassword" v-model="repeatPassword" class="form-input" placeholder="Confirm password" required />
        </div>

        <button type="submit" class="btn-signup" :disabled="isSigningUp">
          {{ isSigningUp ? 'Creating Account...' : 'Create Account' }}
        </button>
      </form>

      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>

      <div class="signup-footer">
        <p>Already have an account? 
          <router-link to="/login" class="link">Login</router-link>
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
      firstName: '',
      lastName: '',
      username: '',
      email: '',
      password: '',
      repeatPassword: '',
      errorMessage: '',
      isSigningUp: false
    };
  },
  methods: {
    async signup() {
      if (this.isSigningUp) return;
      
      if (this.password !== this.repeatPassword) {
        this.errorMessage = 'Passwords do not match.';
        return;
      }

      try {
        this.isSigningUp = true;
        this.errorMessage = '';
        const response = await this.registerUser();
        if (response.data.success) {
          await this.authenticateUser();
          this.$router.push('/');
        } else {
          this.errorMessage = response.data.message;
        }
      } catch (error) {
        this.errorMessage = 'Error during registration. Please try again.';
      } finally {
        this.isSigningUp = false;
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
.signup-container {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  margin-top: 70px;
  padding-top: 40px;
}

.signup-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 16px;
  padding: 50px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.signup-header {
  text-align: center;
  margin-bottom: 40px;
}

.signup-header h2 {
  font-size: 2em;
  font-weight: 800;
  color: white;
  margin: 0 0 10px 0;
}

.signup-header p {
  color: #b0b0b0;
  font-size: 0.95em;
  margin: 0;
}

.signup-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 30px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
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

.btn-signup {
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

.btn-signup:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
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

.signup-footer {
  text-align: center;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  padding-top: 20px;
}

.signup-footer p {
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

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .signup-card {
    padding: 30px 20px;
  }

  .signup-header h2 {
    font-size: 1.6em;
  }

  .form-input {
    padding: 10px 12px;
    font-size: 0.95em;
  }
}
</style>