<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import Header from '../components/Header.vue';
import CartService from '../services/CartService';
import OrderService from '../services/OrderService';
import PaymentService from '../services/PaymentService';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');

const cartItems = ref([]);
const isLoading = ref(true);
const isProcessing = ref(false);
const errorMessage = ref('');
const paymentElements = ref(null);
const paymentElement = ref(null);
const clientSecret = ref('');
const createdOrder = ref(null);
const selectedPaymentMethod = ref('card');

// Form data
const formData = ref({
  email: '',
  phone: '',
  street: '',
  houseNumber: '',
  city: '',
  zipCode: '',
  country: 'Netherlands',
  paymentMethod: 'credit_card'
});

const shipping = 5.99;
const taxRate = 0.21; // 21% VAT

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

  await loadCart();
  await initializeStripe();
  
  // Pre-fill email from user data
  const user = JSON.parse(localStorage.getItem('user'));
  if (user && user.email) {
    formData.value.email = user.email;
  }
});

onUnmounted(() => {
  if (paymentElement.value) {
    paymentElement.value.destroy();
  }
});

async function loadCart() {
  try {
    isLoading.value = true;
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!user || !user.userId) {
      errorMessage.value = 'Please log in to checkout';
      router.push('/login');
      return;
    }

    cartItems.value = await CartService.getCartItems(user.userId);
    
    if (cartItems.value.length === 0) {
      errorMessage.value = 'Your cart is empty';
      router.push('/cart');
    }
  } catch (error) {
    console.error('Failed to load cart:', error);
    errorMessage.value = 'Failed to load cart items';
  } finally {
    isLoading.value = false;
  }
}

const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const taxAmount = computed(() => {
  return subtotal.value * taxRate;
});

const total = computed(() => {
  return subtotal.value + taxAmount.value + shipping;
});

async function initializeStripe() {
  try {
    await PaymentService.initialize();
    
    // Create payment intent immediately
    const paymentIntent = await PaymentService.createPaymentIntent(
      total.value,
      null // No order ID yet
    );
    
    clientSecret.value = paymentIntent.clientSecret;
    
    // Initialize payment elements
    const { elements, paymentElement: element } = await PaymentService.createCheckoutSession(clientSecret.value);
    paymentElements.value = elements;
    paymentElement.value = element;
    
    // Listen for payment method changes
    element.on('change', (event) => {
      if (event.value?.type) {
        selectedPaymentMethod.value = event.value.type;
      }
    });
    
    // Mount payment element
    const container = document.getElementById('payment-element');
    if (container) {
      element.mount('#payment-element');
    }
  } catch (error) {
    console.error('Failed to initialize Stripe:', error);
    errorMessage.value = 'Failed to initialize payment system';
  }
}

async function placeOrder() {
  if (isProcessing.value) return;
  
  // Validation
  if (!formData.value.email || !formData.value.phone || 
      !formData.value.street || !formData.value.houseNumber ||
      !formData.value.city || !formData.value.zipCode) {
    errorMessage.value = 'Please fill in all required fields';
    return;
  }

  if (!paymentElements.value) {
    errorMessage.value = 'Payment method not loaded. Please refresh the page.';
    return;
  }

  try {
    isProcessing.value = true;
    errorMessage.value = '';
    
    const user = JSON.parse(localStorage.getItem('user'));
    
    // Create order data (using snake_case for backend)
    const orderData = {
      user_id: user.userId,
      email: formData.value.email,
      phone_number: formData.value.phone,
      street: formData.value.street,
      house_number: formData.value.houseNumber,
      city: formData.value.city,
      zip_code: formData.value.zipCode,
      country: formData.value.country,
      subtotal: subtotal.value,
      tax_amount: taxAmount.value,
      shipping_cost: shipping,
      total_amount: total.value,
      payment_method: selectedPaymentMethod.value === 'card' ? 'Credit Card' : selectedPaymentMethod.value === 'ideal' ? 'iDEAL' : selectedPaymentMethod.value === 'google_pay' ? 'Google Pay' : 'Other',
      payment_status: 'pending',
      order_status: 'processing'
    };

    // Create the order
    createdOrder.value = await OrderService.createOrder(orderData);
    
    // Create order items for each cart item
    for (const cartItem of cartItems.value) {
      await OrderService.createOrderItem({
        order_id: createdOrder.value.id,
        item_id: cartItem.itemId,
        quantity: cartItem.quantity,
        price_at_purchase: cartItem.price
      });
    }
    
    // Process payment
    const billingDetails = {
      name: user.username || formData.value.email,
      email: formData.value.email,
      phone: formData.value.phone,
      address: {
        line1: `${formData.value.street} ${formData.value.houseNumber}`,
        city: formData.value.city,
        state: '', // Required by Stripe even if not collected
        postal_code: formData.value.zipCode,
        country: formData.value.country === 'Netherlands' ? 'NL' : formData.value.country,
      },
    };
    
    const paymentResult = await PaymentService.processPaymentWithElements(paymentElements.value, billingDetails);
    
    // Update order status with transaction ID
    await OrderService.updateOrderStatus(createdOrder.value.id, {
      payment_status: 'paid',
      order_status: 'processing',
      transaction_id: paymentResult.paymentIntentId || null,
    });
    
    // Clear the cart
    const userCart = await CartService.getOrCreateCart(user.userId);
    await CartService.clearCart(userCart.cartId);
    
    // Redirect to order confirmation page
    router.push(`/order-confirmation?order_id=${createdOrder.value.id}`);
  } catch (error) {
    console.error('Failed to place order:', error);
    errorMessage.value = error.message || 'Failed to place order. Please try again.';
  } finally {
    isProcessing.value = false;
  }
}

function goBack() {
  router.push('/cart');
}
</script>

<template>
  <div class="checkout-page">
    <Header />
    
    <div class="container">
      <button @click="goBack" class="btn-back">← Back to Cart</button>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading checkout...</p>
      </div>

      <div v-else class="checkout-content">
        <div class="checkout-form">
          <h1>Checkout</h1>
          
          <div v-if="errorMessage" class="error-banner">
            {{ errorMessage }}
          </div>

          <div class="form-section">
            <h2>Contact Information</h2>
            <div class="form-row">
              <div class="form-group">
                <label>Email *</label>
                <input 
                  v-model="formData.email" 
                  type="email" 
                  placeholder="your.email@example.com"
                  required
                />
              </div>
              <div class="form-group">
                <label>Phone *</label>
                <input 
                  v-model="formData.phone" 
                  type="tel" 
                  placeholder="+31 6 12345678"
                  required
                />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h2>Shipping Address</h2>
            <div class="form-row">
              <div class="form-group flex-2">
                <label>Street *</label>
                <input 
                  v-model="formData.street" 
                  type="text" 
                  placeholder="Street name"
                  required
                />
              </div>
              <div class="form-group">
                <label>Number *</label>
                <input 
                  v-model="formData.houseNumber" 
                  type="text" 
                  placeholder="123A"
                  required
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>City *</label>
                <input 
                  v-model="formData.city" 
                  type="text" 
                  placeholder="Amsterdam"
                  required
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>Zip Code *</label>
                <input 
                  v-model="formData.zipCode" 
                  type="text" 
                  placeholder="1012 AB"
                  required
                />
              </div>
              <div class="form-group">
                <label>Country *</label>
                <select v-model="formData.country" required>
                  <option value="Netherlands">Netherlands</option>
                  <option value="Belgium">Belgium</option>
                  <option value="Germany">Germany</option>
                  <option value="France">France</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h2>Payment Method</h2>
            <div id="payment-element" class="payment-element-container"></div>
          </div>
        </div>

        <div class="order-summary">
          <div class="summary-card">
            <h2>Order Summary</h2>
            
            <div class="order-items">
              <div v-for="item in cartItems" :key="item.id" class="summary-item">
                <div class="item-info">
                  <img v-if="item.images?.[0]" :src="item.images[0]" :alt="item.title" />
                  <div v-else class="item-placeholder">📦</div>
                  <div>
                    <p class="item-title">{{ item.title }}</p>
                    <p class="item-qty">Qty: {{ item.quantity }}</p>
                  </div>
                </div>
                <p class="item-price">€{{ (item.price * item.quantity).toFixed(2) }}</p>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row">
              <span>Subtotal</span>
              <span>€{{ subtotal.toFixed(2) }}</span>
            </div>
            
            <div class="summary-row">
              <span>Tax (21%)</span>
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

            <button 
              @click="placeOrder" 
              :disabled="isProcessing || !clientSecret"
              class="btn-place-order"
            >
              {{ isProcessing ? 'Processing Payment...' : 'Place Order' }}
            </button>

            <p class="security-note">🔒 Your payment information is secure</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.checkout-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  padding-top: 70px;
  padding-bottom: 3rem;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.btn-back {
  background: white;
  color: #e94560;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 25px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 2rem;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.btn-back:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
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

.checkout-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

.checkout-form {
  background: white;
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.checkout-form h1 {
  color: #e94560;
  font-size: 2rem;
  margin-bottom: 1.5rem;
}

.error-banner {
  background: #fee;
  border: 2px solid #f88;
  color: #c33;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.form-section {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid #f0f0f0;
}

.form-section:last-of-type {
  border-bottom: none;
}

.form-section h2 {
  color: #374151;
  font-size: 1.3rem;
  margin-bottom: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.flex-2 {
  grid-column: span 1;
}

.form-group label {
  color: #4b5563;
  font-weight: 600;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  padding: 0.875rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 1rem;
  transition: border-color 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #e94560;
}

.payment-methods {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.payment-option {
  flex: 1;
  min-width: 150px;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.payment-option:hover {
  border-color: #e94560;
  background: #f9fafb;
}

.payment-option input[type="radio"] {
  cursor: pointer;
}

.payment-option span {
  font-weight: 600;
  color: #374151;
}

.order-summary {
  position: sticky;
  top: 2rem;
  height: fit-content;
}

.summary-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.summary-card h2 {
  color: #374151;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.order-items {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

.item-info {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex: 1;
}

.item-info img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
}

.item-placeholder {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
  border-radius: 8px;
  font-size: 1.5rem;
}

.item-title {
  font-weight: 600;
  color: #374151;
  margin: 0;
  font-size: 0.9rem;
}

.item-qty {
  color: #6b7280;
  font-size: 0.85rem;
  margin: 0.25rem 0 0;
}

.item-price {
  font-weight: 700;
  color: #e94560;
  white-space: nowrap;
}

.summary-divider {
  height: 2px;
  background: #f0f0f0;
  margin: 1rem 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  color: #4b5563;
  font-size: 1rem;
}

.summary-row.total {
  margin-top: 1rem;
  padding-top: 1rem;
  color: #111827;
  font-size: 1.3rem;
  font-weight: 700;
}

.btn-place-order {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  border: none;
  border-radius: 15px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  margin-top: 1.5rem;
}

.btn-place-order:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(233, 69, 96, 0.4);
}

.btn-place-order:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.security-note {
  text-align: center;
  color: #6b7280;
  font-size: 0.85rem;
  margin-top: 1rem;
}

.payment-element-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
  min-height: 200px;
}

.btn-create-order {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-create-order:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(233, 69, 96, 0.4);
}

.btn-create-order:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.payment-info {
  text-align: center;
  padding: 16px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: white;
  font-size: 0.95rem;
}

.payment-info p {
  margin: 0;
}

@media (max-width: 968px) {
  .checkout-content {
    grid-template-columns: 1fr;
  }

  .order-summary {
    position: static;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .payment-methods {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  .checkout-container {
    padding: 20px 15px;
  }
  
  .checkout-form,
  .order-summary {
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
  .checkout-container {
    padding: 15px 10px;
  }
  
  .checkout-form,
  .order-summary {
    padding: 20px;
  }
  
  .submit-btn {
    padding: 12px 24px;
    font-size: 0.95em;
  }
  
  .order-item {
    flex-direction: column;
    gap: 10px;
  }
  
  .item-image {
    width: 100%;
    max-width: 200px;
  }
}
</style>
