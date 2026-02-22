<template>
  <div class="order-card" @click="$emit('click', order.id)">
    <div class="order-header">
      <div class="order-info">
        <h3>Order #{{ order.orderNumber }}</h3>
        <p class="order-date">{{ formatDate(order.createdAt) }}</p>
      </div>
      <div class="order-badges">
        <StatusBadge 
          :label="statusDescriptions[order.status]"
          :type="order.status"
        />
      </div>
    </div>

    <div class="order-body">
      <div class="order-address">
        <p><strong>📍 Shipping Address:</strong></p>
        <p>{{ order.street }} {{ order.houseNumber }}</p>
        <p>{{ order.zipCode }} {{ order.city }}</p>
        <p>{{ order.country }}</p>
      </div>

      <div class="order-summary">
        <div class="summary-row">
          <span>Subtotal:</span>
          <span>{{ formatPrice(order.subtotal) }}</span>
        </div>
        <div class="summary-row">
          <span>Tax:</span>
          <span>{{ formatPrice(order.taxAmount) }}</span>
        </div>
        <div class="summary-row">
          <span>Shipping:</span>
          <span>{{ formatPrice(order.shippingCost) }}</span>
        </div>
        <div class="summary-row total">
          <span><strong>Total:</strong></span>
          <span><strong>{{ formatPrice(order.totalAmount) }}</strong></span>
        </div>
      </div>
    </div>

    <div class="order-footer">
      <div class="order-meta">
        <span v-if="order.trackingNumber">
          🚚 Tracking: {{ order.trackingNumber }}
        </span>
        <span v-if="order.deliveredAt">
          ✅ Delivered: {{ formatDate(order.deliveredAt) }}
        </span>
        <span v-else-if="order.shippedAt">
          📦 Shipped: {{ formatDate(order.shippedAt) }}
        </span>
      </div>
      <button class="btn-view-details">View Details →</button>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from '../ui/StatusBadge.vue';

defineProps({
  order: {
    type: Object,
    required: true
  },
  statusDescriptions: {
    type: Object,
    required: true
  }
});

defineEmits(['click']);

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};

const formatPrice = (price) => {
  return `€${parseFloat(price).toFixed(2)}`;
};
</script>

<style scoped>
.order-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 2rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}

.order-card:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(233, 69, 96, 0.3);
  transform: translateY(-2px);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.order-info h3 {
  color: #fff;
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}

.order-date {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  margin: 0;
}

.order-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.order-address {
  color: rgba(255, 255, 255, 0.8);
}

.order-address p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
}

.order-address strong {
  color: #fff;
}

.order-summary .summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.order-summary .summary-row.total {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 1rem;
  color: #e94560;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.order-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
}

.btn-view-details {
  background: linear-gradient(135deg, #e94560 0%, #d63850 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-view-details:hover {
  transform: translateX(5px);
  box-shadow: 0 5px 15px rgba(233, 69, 96, 0.4);
}

@media (max-width: 768px) {
  .order-body {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .order-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .btn-view-details {
    width: 100%;
  }
}
</style>
