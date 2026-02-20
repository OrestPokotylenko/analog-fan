<script setup>
import { ref, onMounted, inject, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Header from '../components/Header.vue';
import OrderService from '../services/OrderService';
import axios from '../services/axiosConfig';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const route = useRoute();
const $auth = inject('$auth');

const order = ref(null);
const orderItems = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

const isAdmin = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || 'null');
  return user && user.role === 'admin';
});

const statusColors = {
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
  await loadOrderDetails();
});

async function loadOrderDetails() {
  try {
    isLoading.value = true;
    const orderId = route.params.id;
    
    // Fetch order details
    const orderResponse = await OrderService.getOrderById(orderId);
    order.value = orderResponse;

    // Fetch order items
    const itemsResponse = await OrderService.getOrderItems(orderId);
    
    // Ensure itemsResponse is an array
    const orderItemsArray = Array.isArray(itemsResponse) ? itemsResponse : [];
    
    // Fetch item details for each order item
    const itemDetailsPromises = orderItemsArray.map(async (orderItem) => {
      try {
        const itemResponse = await axios.get(`/items/${orderItem.itemId}`);
        return {
          ...orderItem,
          itemDetails: itemResponse.data
        };
      } catch (error) {
        console.error(`Failed to load item ${orderItem.itemId}:`, error);
        return {
          ...orderItem,
          itemDetails: null
        };
      }
    });

    orderItems.value = await Promise.all(itemDetailsPromises);
  } catch (error) {
    console.error('Failed to load order details:', error);
    errorMessage.value = 'Failed to load order details';
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

function goBack() {
  // Check if user is admin, redirect to admin panel
  if (isAdmin.value) {
    router.push('/admin');
  } else {
    router.push('/orders');
  }
}
</script>

<template>
  <div class="order-details-page" :class="{ 'admin-view': isAdmin }">
    <Header v-if="!isAdmin" />
    
    <div class="container">
      <button @click="goBack" class="btn-back">
        ← Back to {{ isAdmin ? 'Admin Panel' : 'Orders' }}
      </button>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading order details...</p>
      </div>

      <div v-else-if="errorMessage" class="error-message">
        <p>{{ errorMessage }}</p>
      </div>

      <div v-else-if="order" class="order-details">
        <div class="details-header">
          <div>
            <h1>Order #{{ order.orderNumber }}</h1>
            <p class="order-date">Placed on {{ formatDate(order.createdAt) }}</p>
          </div>
          <div class="status-badges">
            <span 
              class="status-badge" 
              :style="{ backgroundColor: statusColors[order.status] }"
            >
              {{ statusDescriptions[order.status] }}
            </span>
          </div>
        </div>

        <div class="details-grid">
          <!-- Shipping Information -->
          <div class="info-card">
            <h2>📦 Shipping Information</h2>
            <div class="info-content">
              <p><strong>Address:</strong></p>
              <p>{{ order.street }} {{ order.houseNumber }},</p>
              <p>{{ order.zipCode }} {{ order.city }},</p>
              <p>{{ order.country }}</p>
              
              <p class="info-label"><strong>Email:</strong> {{ order.email }}</p>
              <p v-if="order.phone"><strong>Phone:</strong> {{ order.phone }}</p>
              
              <div v-if="order.transactionId" class="transaction-info">
                <p><strong>💳 Transaction ID:</strong></p>
                <p class="transaction-id">{{ order.transactionId }}</p>
              </div>
              
              <div v-if="order.trackingNumber" class="tracking-info">
                <p><strong>🚚 Tracking Number:</strong></p>
                <p class="tracking-number">{{ order.trackingNumber }}</p>
              </div>
            </div>
          </div>

          <!-- Order Timeline -->
          <div class="info-card">
            <h2>📅 Order Timeline</h2>
            <div class="timeline">
              <div class="timeline-item completed">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                  <strong>Order Placed</strong>
                  <p>{{ formatDate(order.createdAt) }}</p>
                </div>
              </div>
              
              <template v-if="order.status === 'cancelled'">
                <div class="timeline-item completed cancelled">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>Order Cancelled</strong>
                    <p>This order has been cancelled</p>
                  </div>
                </div>
              </template>
              
              <template v-else>
                <div class="timeline-item" :class="{ completed: ['processing', 'shipped', 'delivered'].includes(order.status) }">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>Processing</strong>
                    <p>Order is being prepared</p>
                  </div>
                </div>
                
                <div class="timeline-item" :class="{ completed: ['shipped', 'delivered'].includes(order.status) }">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>Shipped</strong>
                    <p>{{ order.shippedAt ? formatDate(order.shippedAt) : (order.status === 'shipped' || order.status === 'delivered' ? 'In transit' : 'Pending') }}</p>
                  </div>
                </div>
                
                <div class="timeline-item" :class="{ completed: order.status === 'delivered' }">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>Delivered</strong>
                    <p>{{ order.deliveredAt ? formatDate(order.deliveredAt) : 'Pending' }}</p>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Payment Status (only shown if there's an issue) -->
          <div v-if="order.paymentStatus === 'failed' || order.paymentStatus === 'refunded'" class="info-card">
            <h2>💳 Payment Status</h2>
            <div class="info-content">
              <p v-if="order.paymentStatus === 'failed'" class="payment-failed">
                ⚠️ Payment failed. Please contact support.
              </p>
              <p v-if="order.paymentStatus === 'refunded'" class="payment-refunded">
                ↩️ Payment has been refunded
              </p>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="info-card">
            <h2>💰 Order Summary</h2>
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
        </div>

        <!-- Order Items -->
        <div class="items-section">
          <h2>🛍️ Order Items</h2>
          <div class="items-list">
            <div v-for="orderItem in orderItems" :key="orderItem.orderItemId" class="order-item">
              <div v-if="orderItem.itemDetails?.imagesPath?.[0]" class="item-image">
                <img :src="orderItem.itemDetails.imagesPath[0]" :alt="orderItem.itemDetails.title" />
              </div>
              <div v-else class="item-image placeholder">
                <span>📦</span>
              </div>

              <div class="item-info">
                <h3>{{ orderItem.itemDetails?.title || 'Item' }}</h3>
                <p class="item-description">{{ orderItem.itemDetails?.description || 'No description' }}</p>
                <p class="item-category">{{ orderItem.itemDetails?.type || '' }}</p>
              </div>

              <div class="item-quantity">
                <span>Qty: {{ orderItem.quantity }}</span>
              </div>

              <div class="item-price">
                <p class="price-label">Price:</p>
                <p class="price-value">{{ formatPrice(orderItem.priceAtPurchase) }}</p>
                <p class="subtotal-value">{{ formatPrice(orderItem.quantity * orderItem.priceAtPurchase) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-details-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  padding-top: 80px;
  padding-bottom: 3rem;
}

.order-details-page.admin-view {
  padding-top: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.btn-back {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  padding: 0.75rem 1.5rem;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 25px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 2rem;
  transition: all 0.3s;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateX(-5px);
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

.order-details {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.details-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 3px solid rgba(255, 255, 255, 0.1);
}

.details-header h1 {
  color: white;
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.order-date {
  color: rgba(255, 255, 255, 0.6);
  font-size: 1rem;
}

.status-badges {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.status-badge,
.payment-badge {
  padding: 0.6rem 1.2rem;
  border-radius: 25px;
  color: white;
  font-size: 0.85rem;
  font-weight: bold;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-card {
  background: rgba(255, 255, 255, 0.03);
  padding: 1.5rem;
  border-radius: 15px;
  border: 2px solid rgba(255, 255, 255, 0.1);
}

.info-card h2 {
  color: white;
  font-size: 1.2rem;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-content p {
  color: rgba(255, 255, 255, 0.7);
  margin: 0.5rem 0;
  line-height: 1.6;
}

.info-label {
  margin-top: 1rem !important;
}

.transaction-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid rgba(255, 255, 255, 0.1);
}

.transaction-id {
  font-family: monospace;
  font-size: 0.9rem;
  color: #e5e7eb;
  word-break: break-all;
}

.payment-failed {
  color: #ef4444 !important;
  font-weight: 600;
  padding: 0.75rem;
  background: rgba(239, 68, 68, 0.1);
  border-radius: 8px;
  border-left: 4px solid #ef4444;
}

.payment-refunded {
  color: #fbbf24 !important;
  font-weight: 600;
  padding: 0.75rem;
  background: rgba(251, 191, 36, 0.1);
  border-radius: 8px;
  border-left: 4px solid #fbbf24;
}

.tracking-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid rgba(255, 255, 255, 0.1);
}

.tracking-number {
  font-family: monospace;
  font-size: 1.1rem;
  color: #667eea;
  font-weight: bold;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.timeline-item {
  display: flex;
  gap: 1rem;
  position: relative;
  opacity: 0.5;
}

.timeline-item.completed {
  opacity: 1;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 9px;
  top: 25px;
  width: 2px;
  height: calc(100% + 1.5rem - 24px);
  background: rgba(255, 255, 255, 0.1);
}

.timeline-item.completed:has(+ .timeline-item.completed):not(:last-child)::after {
  background: #10b981;
}

.timeline-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  border: 3px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
  margin-top: 3px;
}

.timeline-item.completed .timeline-dot {
  background: #10b981;
}

.timeline-item.cancelled .timeline-dot {
  background: #ef4444;
  border-color: #ef4444;
}

.timeline-item.completed.cancelled:not(:last-child)::after {
  background: #ef4444;
}

.timeline-content strong {
  color: white;
  display: block;
  margin-bottom: 0.25rem;
}

.timeline-content p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
}

.order-summary {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  color: rgba(255, 255, 255, 0.7);
}

.summary-row.total {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 2px solid rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 1.2rem;
}

.items-section {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 3px solid rgba(255, 255, 255, 0.1);
}

.items-section h2 {
  color: white;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.order-item {
  display: grid;
  grid-template-columns: 100px 1fr auto auto;
  gap: 1.5rem;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 15px;
  border: 2px solid rgba(255, 255, 255, 0.1);
  align-items: center;
}

.item-image {
  width: 100px;
  height: 100px;
  border-radius: 10px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(255, 255, 255, 0.1);
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-image.placeholder {
  font-size: 3rem;
}

.item-info h3 {
  color: white;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.item-description {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.item-category {
  color: #667eea;
  font-size: 0.85rem;
  font-weight: 600;
}

.item-quantity {
  text-align: center;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  color: white;
  font-weight: 600;
  border: 2px solid rgba(255, 255, 255, 0.1);
}

.item-price {
  text-align: right;
}

.price-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.85rem;
  margin-bottom: 0.25rem;
}

.price-value {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.subtotal-value {
  color: #667eea;
  font-size: 1.2rem;
  font-weight: bold;
}

@media (max-width: 968px) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .details-header {
    flex-direction: column;
    gap: 1rem;
  }

  .order-item {
    grid-template-columns: 80px 1fr;
    gap: 1rem;
  }

  .item-quantity,
  .item-price {
    grid-column: 2;
    text-align: left;
  }
}

@media (max-width: 768px) {
  .order-details-container {
    padding: 20px 15px;
  }
  
  .details-card {
    padding: 24px;
  }
  
  h2 {
    font-size: 1.5em;
  }
  
  h3 {
    font-size: 1.2em;
  }
}

@media (max-width: 480px) {
  .order-details-container {
    padding: 15px 10px;
  }
  
  .details-card {
    padding: 20px;
  }
  
  .order-item {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .item-image {
    max-width: 100%;
  }
  
  .back-btn {
    padding: 8px 16px;
    font-size: 0.9em;
  }
}
</style>
