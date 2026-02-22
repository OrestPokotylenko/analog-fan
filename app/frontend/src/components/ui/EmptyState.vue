<template>
  <div class="empty-state">
    <div class="empty-icon">{{ icon }}</div>
    <h3 v-if="title">{{ title }}</h3>
    <p v-if="description">{{ description }}</p>
    <slot name="action"></slot>
  </div>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    default: '📭'
  },
  title: {
    type: String,
    default: 'No items found'
  },
  description: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: white;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 20px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { 
    transform: scale(1); 
    opacity: 1;
  }
  50% { 
    transform: scale(1.05); 
    opacity: 0.8;
  }
}

.empty-state h3 {
  font-size: 1.8rem;
  margin-bottom: 10px;
  color: #ffffff;
}

.empty-state p {
  color: #a0a0a0;
  font-size: 1.1rem;
  margin-bottom: 20px;
}
</style>
