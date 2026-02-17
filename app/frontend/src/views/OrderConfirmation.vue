<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Header from '../components/Header.vue';
import OrderService from '../services/OrderService';
import PaymentService from '../services/PaymentService';
import CartService from '../services/CartService';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const route = useRoute();
const $auth = inject('$auth');

const order = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');
const paymentVerified = ref(false);

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

  await handlePaymentReturn();
});

async function handlePaymentReturn() {
  try {
    isLoading.value = true;
    const user = JSON.parse(localStorage.getItem('user'));
    
    // Check if returning from Stripe payment (iDEAL redirect)
    const paymentIntent = route.query.payment_intent;
    const paymentIntentClientSecret = route.query.payment_intent_client_secret;
    const orderId = route.query.order_id;
    
    if (paymentIntent && paymentIntentClientSecret) {
      // Verify payment status
      const status = await PaymentService.getPaymentStatus(paymentIntent);
      
      if (status === 'succeeded') {
        paymentVerified.value = true;
        
        // Find and update the order
        if (orderId) {
          await OrderService.updateOrderStatus(orderId, {
            payment_status: 'paid',
            order_status: 'processing',
            transaction_id: paymentIntent,
          });
          order.value = await OrderService.getOrderById(orderId);
        } else {
          // Find the most recent pending order
          const tempOrders = await OrderService.getUserOrders(user.userId);
          const pendingOrder = tempOrders.find(o => o.paymentStatus === 'pending');
          
          if (pendingOrder) {
            await OrderService.updateOrderStatus(pendingOrder.id, {
              payment_status: 'paid',
              order_status: 'processing',
              transaction_id: paymentIntent,
            });
            order.value = await OrderService.getOrderById(pendingOrder.id);
          }
        }
        
        // Clear the cart
        const userCart = await CartService.getOrCreateCart(user.userId);
        await CartService.clearCart(userCart.cartId);
      } else {
        errorMessage.value = 'Payment was not successful. Please contact support.';
      }
    } else if (orderId) {
      // Direct navigation with order ID (for card payments)
      order.value = await OrderService.getOrderById(orderId);
      paymentVerified.value = true;
    } else {
      errorMessage.value = 'No order information found.';
    }
  } catch (error) {
    console.error('Failed to process order confirmation:', error);
    errorMessage.value = 'Failed to load order details.';
  } finally {
    isLoading.value = false;
  }
}

function formatPrice(price) {
  return `€${parseFloat(price).toFixed(2)}`;
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
  });
}

function viewOrderDetails() {
  if (order.value) {
    router.push(`/orders/${order.value.id}`);
  }
}

function continueShopping() {
  router.push('/categories');
}

function goToOrders() {
  router.push('/orders');
}
</script>

<template>
  <div class="order-confirmation-page">
    <Header />
    
    <div class="container">
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Processing your order...</p>
      </div>

      <div v-else-if="errorMessage" class="error-state">
        <div class="error-icon">⚠️</div>
        <h1>Something Went Wrong</h1>
        <p class="error-text">{{ errorMessage }}</p>
        <button @click="goToOrders" class="btn-primary">View My Orders</button>
      </div>

      <div v-else-if="paymentVerified && order" class="success-state">
        <div class="success-animation">
          <div class="checkmark-circle">
            <div class="checkmark">✓</div>
          </div>
        </div>

        <h1 class="success-title">Order Confirmed! 🎉</h1>
        <p class="success-subtitle">
          Thank you for your purchase! We've sent a confirmation email to <strong>{{ order.email }}</strong>
        </p>

        <div class="order-summary-card">
          <div class="order-header">
            <div>
              <h2>Order #{{ order.orderNumber }}</h2>
              <p class="order-date">{{ formatDate(order.createdAt) }}</p>
            </div>
            <div class="status-badge success">
              ✅ Payment Received
            </div>
          </div>

          <div class="order-details">
            <div class="detail-row">
              <span class="label">📦 Shipping Address:</span>
              <span class="value">
                {{ order.street }} {{ order.houseNumber }}, 
                {{ order.zipCode }} {{ order.city }}, 
                {{ order.country }}
              </span>
            </div>

            <div class="detail-row">
              <span class="label">📧 Email:</span>
              <span class="value">{{ order.email }}</span>
            </div>

            <div v-if="order.phone || order.phoneNumber" class="detail-row">
              <span class="label">📱 Phone:</span>
              <span class="value">{{ order.phone || order.phoneNumber }}</span>
            </div>

            <div class="detail-row">
              <span class="label">💳 Payment Method:</span>
              <span class="value">{{ order.paymentMethod }}</span>
            </div>

            <div v-if="order.transactionId" class="detail-row">
              <span class="label">🔐 Transaction ID:</span>
              <span class="value" style="font-family: monospace; font-size: 0.85rem; word-break: break-all;">{{ order.transactionId }}</span>
            </div>
          </div>

          <div class="price-summary">
            <div class="price-row">
              <span>Subtotal:</span>
              <span>{{ formatPrice(order.subtotal) }}</span>
            </div>
            <div class="price-row">
              <span>Tax (21%):</span>
              <span>{{ formatPrice(order.taxAmount) }}</span>
            </div>
            <div class="price-row">
              <span>Shipping:</span>
              <span>{{ formatPrice(order.shippingCost) }}</span>
            </div>
            <div class="price-row total">
              <span>Total:</span>
              <span>{{ formatPrice(order.totalAmount) }}</span>
            </div>
          </div>
        </div>

        <div class="info-card">
          <h3>📬 What's Next?</h3>
          <ul>
            <li>We'll send you updates about your order via email</li>
            <li>Your order will be prepared and shipped soon</li>
            <li>You can track your order status anytime in your orders page</li>
            <li>Expected delivery: 3-5 business days</li>
          </ul>
        </div>

        <div class="action-buttons">
          <button @click="viewOrderDetails" class="btn-secondary">
            View Order Details
          </button>
          <button @click="continueShopping" class="btn-primary">
            Continue Shopping
          </button>
        </div>

        <div class="additional-actions">
          <button @click="goToOrders" class="link-button">
            View All Orders →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.order-confirmation-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  padding-bottom: 4rem;
  padding-top: 70px;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.loading {
  text-align: center;
  padding: 4rem 2rem;
  color: white;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state, .success-state {
  text-align: center;
  color: white;
}

.error-state {
  padding: 2rem;
}

.error-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
}

.error-text {
  margin: 1rem 0 2rem;
  font-size: 1.1rem;
}

.success-animation {
  margin-bottom: 2rem;
}

.checkmark-circle {
  width: 120px;
  height: 120px;
  margin: 0 auto;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.checkmark {
  font-size: 4rem;
  color: #10b981;
  font-weight: bold;
  animation: checkmarkAppear 0.5s 0.3s ease-out both;
}

@keyframes checkmarkAppear {
  from {
    transform: scale(0) rotate(-45deg);
    opacity: 0;
  }
  to {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

.success-title {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  font-weight: 700;
}

.success-subtitle {
  font-size: 1.1rem;
  margin-bottom: 2rem;
  opacity: 0.95;
}

.order-summary-card, .info-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  text-align: left;
  color: #333;
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #f3f4f6;
  margin-bottom: 1.5rem;
}

.order-header h2 {
  font-size: 1.5rem;
  margin: 0 0 0.25rem 0;
  color: #1f2937;
}

.order-date {
  color: #6b7280;
  margin: 0;
  font-size: 0.9rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
}

.status-badge.success {
  background: #d1fae5;
  color: #065f46;
}

.order-details {
  margin-bottom: 1.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row .label {
  font-weight: 600;
  color: #4b5563;
  flex-shrink: 0;
  margin-right: 1rem;
}

.detail-row .value {
  text-align: right;
  color: #1f2937;
}

.price-summary {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-top: 1.5rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 1rem;
}

.price-row.total {
  border-top: 2px solid #e94560;
  margin-top: 0.5rem;
  padding-top: 1rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: #e94560;
}

.info-card h3 {
  margin: 0 0 1rem 0;
  color: #e94560;
  font-size: 1.25rem;
}

.info-card ul {
  margin: 0;
  padding-left: 1.5rem;
}

.info-card li {
  padding: 0.5rem 0;
  color: #4b5563;
  line-height: 1.6;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
}

.btn-primary, .btn-secondary {
  padding: 1rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 2px solid white;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.additional-actions {
  margin-top: 2rem;
}

.link-button {
  background: none;
  border: none;
  color: white;
  font-size: 1rem;
  cursor: pointer;
  text-decoration: underline;
  opacity: 0.9;
  transition: opacity 0.3s ease;
}

.link-button:hover {
  opacity: 1;
}

@media (max-width: 640px) {
  .success-title {
    font-size: 2rem;
  }

  .order-header {
    flex-direction: column;
    gap: 1rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-primary, .btn-secondary {
    width: 100%;
  }

  .detail-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .detail-row .value {
    text-align: left;
  }
}
</style>
