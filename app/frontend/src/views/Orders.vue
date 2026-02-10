<script setup>
import { ref, onMounted, inject, computed } from 'vue';
import { useRouter } from 'vue-router';
import Header from '../components/Header.vue';
import OrderService from '../services/OrderService';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');

const orders = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

const statusColors = {
  pending: '#f59e0b',
  processing: '#3b82f6',
  shipped: '#8b5cf6',
  delivered: '#10b981',
  cancelled: '#ef4444'
};

const paymentStatusColors = {
  pending: '#f59e0b',
  paid: '#10b981',
  failed: '#ef4444',
  refunded: '#6b7280'
};

const statusDescriptions = {
  pending: '⏳ Awaiting Processing',
  processing: '⚙️ Order Being Prepared',
  shipped: '🚚 On The Way',
  delivered: '✅ Successfully Delivered',
  cancelled: '❌ Order Cancelled'
};

const paymentStatusDescriptions = {
  pending: '⏳ Payment Pending',
  paid: '✅ Payment Received',
  failed: '❌ Payment Failed',
  refunded: '↩️ Payment Refunded'
};

onMounted(async () => {
  // Check token expiration immediately
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) {
    clearAuthState($auth);
    router.push('/login');
    return;
  }
  
  if (!$auth.isLoggedIn) {
    router.push('/login');
    return;
  }
  
  // Prevent admins from accessing this page
  const user = JSON.parse(localStorage.getItem('user') || 'null');
  if (user && user.role === 'admin') {
    router.push('/admin');
    return;
  }
  
  await loadOrders();
});

async function loadOrders() {
  try {
    isLoading.value = true;
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!user || !user.userId) {
      errorMessage.value = 'Please log in to view your orders';
      router.push('/login');
      return;
    }

    const response = await OrderService.getUserOrders(user.userId);
    orders.value = response.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  } catch (error) {
    console.error('Failed to load orders:', error);
    errorMessage.value = 'Failed to load your orders';
  } finally {
    isLoading.value = false;
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatPrice(price) {
  return `€${parseFloat(price).toFixed(2)}`;
}

function viewOrderDetails(orderId) {
  router.push(`/orders/${orderId}`);
}
</script>

<template>
  <div class="orders-page">
    <Header />
    
    <div class="container">
      <div class="orders-header">
        <h1>My Orders</h1>
        <p class="subtitle">View and track your orders</p>
      </div>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading your orders...</p>
      </div>

      <div v-else-if="errorMessage" class="error-message">
        <p>{{ errorMessage }}</p>
      </div>

      <div v-else-if="orders.length === 0" class="empty-state">
        <div class="empty-icon">📦</div>
        <h2>No orders yet</h2>
        <p>Start shopping and your orders will appear here!</p>
        <button @click="router.push('/categories')" class="btn-explore">
          Start Shopping
        </button>
      </div>

      <div v-else class="orders-list">
        <div 
          v-for="order in orders" 
          :key="order.id" 
          class="order-card"
          @click="viewOrderDetails(order.id)"
        >
          <div class="order-header">
            <div class="order-info">
              <h3>Order #{{ order.orderNumber }}</h3>
              <p class="order-date">{{ formatDate(order.createdAt) }}</p>
            </div>
            <div class="order-badges">
              <span 
                class="status-badge" 
                :style="{ backgroundColor: statusColors[order.status] }"
                :title="statusDescriptions[order.status]"
              >
                {{ statusDescriptions[order.status] }}
              </span>
            </div>
          </div>

          <div class="order-body">
            <div class="order-address">
              <p><strong>📍 Shipping Address:</strong></p>
              <p>{{ order.street }} {{ order.houseNumber }}</p>
              <p>{{ order.zipCode }} {{ order.city }}, {{ order.province }}</p>
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
      </div>
    </div>
  </div>
</template>

<style scoped>
.orders-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  padding-top: 80px;
  padding-bottom: 3rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.orders-header {
  text-align: center;
  margin-bottom: 3rem;
  color: white;
}

.orders-header h1 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
}

.loading {
  text-align: center;
  color: white;
  padding: 3rem;
}

.spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: white;
  padding: 2rem;
  border-radius: 15px;
  text-align: center;
  color: #ef4444;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.empty-state {
  background: white;
  padding: 4rem 2rem;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
}

.empty-state h2 {
  color: #333;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #666;
  margin-bottom: 2rem;
}

.btn-explore {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 25px;
  font-size: 1rem;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-explore:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.order-card {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.order-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.order-info h3 {
  color: #667eea;
  margin-bottom: 0.25rem;
  font-size: 1.3rem;
}

.order-date {
  color: #6b7280;
  font-size: 0.9rem;
}

.order-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.status-badge,
.payment-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.order-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 1rem;
}

.order-address {
  color: #374151;
}

.order-address p {
  margin: 0.25rem 0;
  line-height: 1.6;
}

.order-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 10px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: #4b5563;
}

.summary-row.total {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 2px solid #e5e7eb;
  color: #111827;
  font-size: 1.1rem;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 2px solid #f3f4f6;
}

.order-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  color: #6b7280;
  font-size: 0.9rem;
}

.btn-view-details {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.6rem 1.5rem;
  border: none;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-view-details:hover {
  transform: scale(1.05);
}

@media (max-width: 768px) {
  .order-body {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .order-header {
    flex-direction: column;
    gap: 1rem;
  }

  .order-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .btn-view-details {
    width: 100%;
  }
}
</style>
