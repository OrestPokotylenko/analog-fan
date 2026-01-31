<template>
    <div class="auth-page">
        <router-link to="/" class="logo-link">
            Analog Fan
        </router-link>
        <div class="form-container">
            <SignupForm />
        </div>
    </div>
</template>

<script>
import SignupForm from '../components/SignupForm.vue';

export default {
    components: {
        SignupForm
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
</style>