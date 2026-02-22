<template>
  <div class="cart-item">
    <div class="item-image">
      <img v-if="item.images?.[0]" :src="item.images[0]" :alt="item.title" />
      <div v-else class="no-image">No Image</div>
    </div>
    <div class="item-details">
      <h3>{{ item.title }}</h3>
      <p class="item-quantity">Quantity: {{ item.quantity }}</p>
      <p class="item-price">€{{ item.price.toFixed(2) }}</p>
    </div>
    <button 
      @click="$emit('remove', item.id)" 
      class="btn-remove"
      :disabled="isRemoving"
    >
      <span>{{ isRemoving ? '⏳' : '🗑️' }}</span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  item: {
    type: Object,
    required: true
  },
  isRemoving: {
    type: Boolean,
    default: false
  }
});

defineEmits(['remove']);
</script>

<style scoped>
.cart-item {
  display: flex;
  gap: 1.5rem;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.cart-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(233, 69, 96, 0.3);
}

.item-image {
  width: 120px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.3);
  font-size: 0.875rem;
}

.item-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.item-details h3 {
  color: #fff;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.item-quantity {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
  margin: 0;
}

.item-price {
  color: #e94560;
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}

.btn-remove {
  background: rgba(233, 69, 96, 0.1);
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.3s ease;
  height: fit-content;
  font-size: 1.25rem;
}

.btn-remove:hover:not(:disabled) {
  background: rgba(233, 69, 96, 0.2);
  border-color: #e94560;
  transform: translateY(-2px);
}

.btn-remove:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .cart-item {
    flex-direction: column;
    gap: 1rem;
  }

  .item-image {
    width: 100%;
    height: 200px;
  }

  .btn-remove {
    width: 100%;
  }
}
</style>
