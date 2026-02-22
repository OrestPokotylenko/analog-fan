<template>
  <div class="summary-card">
    <h2>{{ title }}</h2>
    
    <div class="summary-row">
      <span>Subtotal</span>
      <span>€{{ subtotal.toFixed(2) }}</span>
    </div>
    
    <div v-if="taxAmount !== undefined" class="summary-row">
      <span>Tax</span>
      <span>€{{ taxAmount.toFixed(2) }}</span>
    </div>
    
    <div class="summary-row">
      <span>Shipping</span>
      <span>€{{ shipping.toFixed(2) }}</span>
    </div>
    
    <div class="summary-divider"></div>
    
    <div class="summary-row total">
      <span>Total</span>
      <span>€{{ total.toFixed(2) }}</span>
    </div>

    <slot name="actions"></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Order Summary'
  },
  subtotal: {
    type: Number,
    required: true
  },
  shipping: {
    type: Number,
    required: true
  },
  taxAmount: {
    type: Number,
    default: undefined
  }
});

const total = computed(() => {
  let sum = props.subtotal + props.shipping;
  if (props.taxAmount !== undefined) {
    sum += props.taxAmount;
  }
  return sum;
});
</script>

<style scoped>
.summary-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 2rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: sticky;
  top: 100px;
}

.summary-card h2 {
  color: #fff;
  font-size: 1.5rem;
  margin: 0 0 1.5rem 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  color: rgba(255, 255, 255, 0.8);
  font-size: 1rem;
}

.summary-row.total {
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  margin-top: 1rem;
}

.summary-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 1rem 0;
}

@media (max-width: 1024px) {
  .summary-card {
    position: static;
  }
}
</style>
