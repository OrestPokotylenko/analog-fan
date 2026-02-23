<template>
  <div class="loading-container">
    <div class="spinner" :class="sizeClass"></div>
    <p v-if="message" class="loading-message">{{ message }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  }
});

const sizeClass = computed(() => `spinner-${props.size}`);
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: white;
}

.spinner {
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.spinner-small {
  width: 30px;
  height: 30px;
  border: 3px solid rgba(233, 69, 96, 0.2);
  border-top-color: #e94560;
}

.spinner-medium {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(233, 69, 96, 0.2);
  border-top-color: #e94560;
}

.spinner-large {
  width: 70px;
  height: 70px;
  border: 5px solid rgba(233, 69, 96, 0.2);
  border-top-color: #e94560;
}

@keyframes spin {
  to { 
    transform: rotate(360deg); 
  }
}

.loading-message {
  margin-top: 1rem;
  font-size: 1rem;
  color: #a0a0a0;
}
</style>
