<script setup>
import { ref, onMounted, inject, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Header from '../../components/layout/Header.vue';
import OrderService from '../../services/OrderService';
import axios from '../../services/axiosConfig';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';

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

const statusDescriptions = {
  processing: '⚙️ Order Being Prepared',
  shipped: '🚚 On The Way',
  delivered: '✅ Successfully Delivered',
  cancelled: '❌ Order Cancelled'
};

onMounted(async () => {
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) { clearAuthState($auth); router.push('/login'); return; }
  if (!$auth.isLoggedIn) { router.push('/login'); return; }
  await loadOrderDetails();
});

async function loadOrderDetails() {
  try {
    isLoading.value = true;
    const orderId = route.params.id;
    order.value = await OrderService.getOrderById(orderId);
    const itemsResponse = await OrderService.getOrderItems(orderId);
    const orderItemsArray = Array.isArray(itemsResponse) ? itemsResponse : [];
    orderItems.value = await Promise.all(orderItemsArray.map(async (orderItem) => {
      try {
        const itemResponse = await axios.get(`/items/${orderItem.itemId}`);
        return { ...orderItem, itemDetails: itemResponse.data };
      } catch {
        return { ...orderItem, itemDetails: null };
      }
    }));
  } catch (error) {
    console.error('Failed to load order details:', error);
    errorMessage.value = 'Failed to load order details';
  } finally {
    isLoading.value = false;
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
  });
}

function formatPrice(price) { return `€${parseFloat(price).toFixed(2)}`; }

function goBack() {
  router.push(isAdmin.value ? '/admin' : '/orders');
}
</script>

<template>
  <div class="order-details-page" :class="{ 'admin-view': isAdmin }">
    <Header v-if="!isAdmin" />

    <div class="container">
      <button class="btn-back" @click="goBack">
        ← Back to {{ isAdmin ? 'Admin Panel' : 'Orders' }}
      </button>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading order details...</p>
      </div>

      <div v-else-if="errorMessage" class="error-message">{{ errorMessage }}</div>

      <div v-else-if="order" class="order-details">

        <!-- Header -->
        <div class="details-header">
          <div>
            <h1>Order #{{ order.orderNumber }}</h1>
            <p class="order-date">Placed on {{ formatDate(order.createdAt) }}</p>
          </div>
          <span class="status-badge" :style="{ backgroundColor: statusColors[order.status] }">
            {{ statusDescriptions[order.status] }}
          </span>
        </div>

        <!-- Info grid -->
        <div class="details-grid">
          <!-- Shipping -->
          <div class="info-card">
            <h2>📦 Shipping Information</h2>
            <div class="info-content">
              <p><strong>Address:</strong></p>
              <p>{{ order.street }} {{ order.houseNumber }},</p>
              <p>{{ order.zipCode }} {{ order.city }},</p>
              <p>{{ order.country }}</p>
              <p class="mt"><strong>Email:</strong> {{ order.email }}</p>
              <p v-if="order.phone"><strong>Phone:</strong> {{ order.phone }}</p>
              <div v-if="order.transactionId" class="sub-block">
                <p><strong>💳 Transaction ID:</strong></p>
                <p class="mono">{{ order.transactionId }}</p>
              </div>
              <div v-if="order.trackingNumber" class="sub-block">
                <p><strong>🚚 Tracking Number:</strong></p>
                <p class="tracking-number">{{ order.trackingNumber }}</p>
              </div>
            </div>
          </div>

          <!-- Timeline -->
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
                <div class="timeline-item" :class="{ completed: ['processing','shipped','delivered'].includes(order.status) }">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content"><strong>Processing</strong><p>Order is being prepared</p></div>
                </div>
                <div class="timeline-item" :class="{ completed: ['shipped','delivered'].includes(order.status) }">
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

          <!-- Payment issue -->
          <div v-if="order.paymentStatus === 'failed' || order.paymentStatus === 'refunded'" class="info-card">
            <h2>💳 Payment Status</h2>
            <p v-if="order.paymentStatus === 'failed'" class="payment-alert failed">⚠️ Payment failed. Please contact support.</p>
            <p v-if="order.paymentStatus === 'refunded'" class="payment-alert refunded">↩️ Payment has been refunded</p>
          </div>

          <!-- Summary -->
          <div class="info-card">
            <h2>💰 Order Summary</h2>
            <div class="order-summary">
              <div class="summary-row"><span>Subtotal:</span><span>{{ formatPrice(order.subtotal) }}</span></div>
              <div class="summary-row"><span>Tax:</span><span>{{ formatPrice(order.taxAmount) }}</span></div>
              <div class="summary-row"><span>Shipping:</span><span>{{ formatPrice(order.shippingCost) }}</span></div>
              <div class="summary-row total"><span><strong>Total:</strong></span><span><strong>{{ formatPrice(order.totalAmount) }}</strong></span></div>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="items-section">
          <h2>🛍️ Order Items</h2>
          <div class="items-list">
            <div v-for="orderItem in orderItems" :key="orderItem.orderItemId" class="order-item">
              <!-- Image -->
              <div class="item-image">
                <img v-if="orderItem.itemDetails?.imagesPath?.[0]" :src="orderItem.itemDetails.imagesPath[0]" :alt="orderItem.itemDetails.title" />
                <span v-else class="placeholder-icon">📦</span>
              </div>

              <!-- Info -->
              <div class="item-info">
                <h3>{{ orderItem.itemDetails?.title || 'Item' }}</h3>
                <p class="item-category">{{ orderItem.itemDetails?.type || '' }}</p>
              </div>

              <!-- Quantity -->
              <div class="item-quantity">Qty: {{ orderItem.quantity }}</div>

              <!-- Price -->
              <div class="item-price">
                <p class="price-label">Unit price</p>
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
  padding-top: 66px;
  padding-bottom: 3rem;
  color: white;
}
.order-details-page.admin-view { padding-top: 2rem; }

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 20px;
}

/* ── Back button ── */
.btn-back {
  background: rgba(255,255,255,0.08);
  color: white;
  padding: 10px 20px;
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 25px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 2rem;
  display: inline-block;
  transition: background 0.2s, transform 0.2s;
}
.btn-back:hover { background: rgba(255,255,255,0.13); transform: translateX(-4px); }

/* ── Loading / error ── */
.loading { text-align: center; padding: 3rem; color: rgba(255,255,255,0.6); }
.spinner {
  width: 48px; height: 48px;
  border: 4px solid rgba(255,255,255,0.15);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }
.error-message { background: rgba(239,68,68,0.1); border: 1px solid #ef4444; border-radius: 10px; padding: 1.5rem; color: #ef4444; text-align: center; }

/* ── Order details wrapper ── */
.order-details {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  padding: 2rem;
}

/* ── Header ── */
.details-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  flex-wrap: wrap;
}
.details-header h1 { color: white; font-size: 1.8rem; margin: 0 0 6px; }
.order-date { color: rgba(255,255,255,0.55); font-size: 0.95rem; margin: 0; }

.status-badge {
  padding: 8px 16px;
  border-radius: 20px;
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Info grid ── */
.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.info-card {
  background: rgba(255,255,255,0.03);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.08);
}
.info-card h2 { color: white; font-size: 1.1rem; margin: 0 0 1rem; }

.info-content p { color: rgba(255,255,255,0.68); margin: 4px 0; line-height: 1.6; font-size: 0.93rem; }
.mt { margin-top: 12px !important; }

.sub-block {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,0.08);
}
.mono { font-family: monospace; font-size: 0.88rem; color: #e5e7eb; word-break: break-all; }
.tracking-number { font-family: monospace; font-size: 1rem; color: #667eea; font-weight: 700; }

/* ── Timeline ── */
.timeline { display: flex; flex-direction: column; gap: 1.25rem; }

.timeline-item {
  display: flex;
  gap: 1rem;
  position: relative;
  opacity: 0.45;
}
.timeline-item.completed { opacity: 1; }

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 9px; top: 22px;
  width: 2px;
  height: calc(100% + 1.25rem - 20px);
  background: rgba(255,255,255,0.1);
}
.timeline-item.completed:has(+ .timeline-item.completed):not(:last-child)::after { background: #10b981; }
.timeline-item.completed.cancelled:not(:last-child)::after { background: #ef4444; }

.timeline-dot {
  width: 20px; height: 20px;
  border-radius: 50%;
  background: rgba(255,255,255,0.15);
  border: 3px solid rgba(255,255,255,0.1);
  flex-shrink: 0;
  margin-top: 2px;
}
.timeline-item.completed .timeline-dot { background: #10b981; border-color: #10b981; }
.timeline-item.cancelled .timeline-dot { background: #ef4444; border-color: #ef4444; }

.timeline-content strong { color: white; display: block; margin-bottom: 2px; font-size: 0.95rem; }
.timeline-content p { color: rgba(255,255,255,0.55); font-size: 0.85rem; margin: 0; }

/* ── Payment alert ── */
.payment-alert {
  padding: 10px 14px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.93rem;
  margin: 0;
}
.payment-alert.failed { color: #ef4444; background: rgba(239,68,68,0.1); border-left: 4px solid #ef4444; }
.payment-alert.refunded { color: #fbbf24; background: rgba(251,191,36,0.1); border-left: 4px solid #fbbf24; }

/* ── Summary ── */
.order-summary { display: flex; flex-direction: column; gap: 10px; }
.summary-row { display: flex; justify-content: space-between; color: rgba(255,255,255,0.68); font-size: 0.93rem; }
.summary-row.total {
  margin-top: 8px; padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.1);
  color: white; font-size: 1.1rem;
}

/* ── Items section ── */
.items-section {
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.items-section > h2 { color: white; font-size: 1.3rem; margin: 0 0 1.25rem; }
.items-list { display: flex; flex-direction: column; gap: 1rem; }

.order-item {
  display: grid;
  grid-template-columns: 90px 1fr auto auto;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
}

.item-image {
  width: 90px;
  height: 90px;
  border-radius: 8px;
  overflow: hidden;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.item-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
.placeholder-icon { font-size: 2.5rem; }

.item-info { min-width: 0; }
.item-info h3 { color: white; font-size: 1rem; margin: 0 0 6px; line-height: 1.3; }
.item-category { color: #667eea; font-size: 0.82rem; font-weight: 600; margin: 0; }

.item-quantity {
  padding: 8px 14px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
}

.item-price { text-align: right; }
.price-label { color: rgba(255,255,255,0.5); font-size: 0.78rem; margin: 0 0 2px; }
.price-value { color: rgba(255,255,255,0.75); font-size: 0.92rem; margin: 0 0 4px; }
.subtotal-value { color: #667eea; font-size: 1.1rem; font-weight: 700; margin: 0; }

/* ── Responsive ── */
@media (max-width: 900px) {
  .details-grid { grid-template-columns: 1fr; }
  .order-details { padding: 1.5rem; }
}

@media (max-width: 768px) {
  .container { padding: 1.25rem 16px; }
  .details-header h1 { font-size: 1.4rem; }

  /*
   * Layout: [image] [info      ]
   *         [image] [qty][price]
   * Image spans 2 rows, info fills top, qty+price share bottom row
   */
  .order-item {
    grid-template-columns: 80px 1fr 1fr;
    grid-template-rows: auto auto;
    gap: 0.5rem 1rem;
    align-items: start;
  }
  .item-image {
    width: 80px;
    height: 80px;
    grid-column: 1;
    grid-row: 1 / 3;
    align-self: start;
  }
  .item-info     { grid-column: 2 / 4; grid-row: 1; }
  .item-quantity { grid-column: 2; grid-row: 2; align-self: center; justify-self: start; }
  .item-price    { grid-column: 3; grid-row: 2; align-self: center; text-align: right; justify-self: end; }
}

@media (max-width: 480px) {
  .order-details { padding: 1rem; }
  .details-header h1 { font-size: 1.2rem; }

  /*
   * Layout: [image] [info ]
   *         [image] [qty  ]
   *         [    price row ]  ← full width, separated by a line
   */
  .order-item {
    grid-template-columns: 68px 1fr;
    grid-template-rows: auto auto auto;
    gap: 0.4rem 0.875rem;
    padding: 1rem;
  }
  .item-image {
    width: 68px;
    height: 68px;
    grid-column: 1;
    grid-row: 1 / 3;   /* only spans info + qty rows, not price */
    align-self: start;
  }
  .item-info     { grid-column: 2; grid-row: 1; }
  .item-quantity { grid-column: 2; grid-row: 2; justify-self: start; align-self: center; }

  /* price goes full width below with a separator */
  .item-price {
    grid-column: 1 / 3;
    grid-row: 3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    margin-top: 0.5rem;
    padding-top: 0.625rem;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .item-price .price-label { display: none; }
  .item-price .price-value {
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
    margin: 0;
  }
  .item-price .subtotal-value { font-size: 1rem; margin: 0; }
}
</style>