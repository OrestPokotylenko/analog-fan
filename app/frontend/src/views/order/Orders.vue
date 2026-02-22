<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Header from '../../components/layout/Header.vue';
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import AlertMessage from '../../components/ui/AlertMessage.vue';
import AppButton from '../../components/ui/AppButton.vue';
import OrderCard from '../../components/order/OrderCard.vue';
import OrderService from '../../services/OrderService';
import PaymentService from '../../services/PaymentService';
import CartService from '../../services/CartService';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';

const router = useRouter();
const route = useRoute();
const $auth = inject('$auth');

const orders = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

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
  
  // Check if returning from Stripe payment (iDEAL redirect)
  const paymentIntent = route.query.payment_intent;
  const paymentIntentClientSecret = route.query.payment_intent_client_secret;
  
  if (paymentIntent && paymentIntentClientSecret) {
    try {
      // Verify payment status
      const status = await PaymentService.getPaymentStatus(paymentIntent);
      
      if (status === 'succeeded') {
        // Find the most recent pending order and update it
        const tempOrders = await OrderService.getUserOrders(user.userId);
        const pendingOrder = tempOrders.find(o => o.paymentStatus === 'pending');
        
        if (pendingOrder) {
          await OrderService.updateOrderStatus(pendingOrder.id, {
            payment_status: 'paid',
            order_status: 'processing',
            transaction_id: paymentIntent,
          });
          
          // Clear the cart
          const userCart = await CartService.getOrCreateCart(user.userId);
          await CartService.clearCart(userCart.cartId);
        }
      }
    } catch (error) {
      console.error('Failed to process payment return:', error);
    }
    
    // Clean up URL
    router.replace({ path: '/orders' });
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

      <LoadingSpinner v-if="isLoading" message="Loading your orders..." />

      <AlertMessage v-else-if="errorMessage" :message="errorMessage" type="error" />

      <EmptyState 
        v-else-if="orders.length === 0" 
        icon="📦"
        title="No orders yet"
        description="Start shopping and your orders will appear here!"
      >
        <template #action>
          <AppButton @click="router.push('/categories')">
            Start Shopping
          </AppButton>
        </template>
      </EmptyState>

      <div v-else class="orders-list">
        <OrderCard 
          v-for="order in orders" 
          :key="order.id"
          :order="order"
          :status-descriptions="statusDescriptions"
          @click="viewOrderDetails"
        />
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

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
</style>
