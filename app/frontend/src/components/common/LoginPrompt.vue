<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-icon">❤️</div>
      <h3>Sign in to save to wishlist</h3>
      <p>Create an account or log in to save items to your wishlist</p>
      <div class="modal-actions">
        <button @click="$emit('login')" class="btn-login">Log In</button>
        <button @click="$emit('close')" class="btn-cancel">Maybe Later</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    required: true
  }
})

defineEmits(['close', 'login'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 20px;
  padding: 2.5rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  text-align: center;
  animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    transform: translateY(-50px) scale(0.9);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

.modal-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.modal-content h3 {
  color: #fff;
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.modal-content p {
  color: #a0a0a0;
  font-size: 0.95rem;
  margin-bottom: 2rem;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn-login,
.btn-cancel {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-login {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-cancel {
  background: transparent;
  color: #a0a0a0;
  border: 1px solid #333;
}

.btn-cancel:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
  border-color: #555;
}
</style>
