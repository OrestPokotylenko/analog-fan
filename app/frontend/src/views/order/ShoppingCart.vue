<template>
  <PageLayout>
  <div class="cart-page">
    <div class="container container-lg">
      <div class="cart-header">
        <div class="header-content">
          <div>
            <h1>Shopping Cart</h1>
            <p v-if="cartItems.length">{{ cartItems.length }} {{ cartItems.length === 1 ? 'item' : 'items' }} in your cart</p>
          </div>
          <button 
            v-if="cartItems.length" 
            @click="clearCart" 
            class="btn-clear-cart"
            :disabled="isClearing || isRemoving"
          >
            {{ isClearing ? 'Clearing...' : 'Clear Cart' }}
          </button>
        </div>
      </div>

      <div v-if="cartItems.length === 0" class="empty-cart">
        <div class="empty-icon">🛒</div>
        <h2>Your cart is empty</h2>
        <p>Start shopping to add items to your cart</p>
        <router-link to="/categories" class="btn btn-primary">Browse Items</router-link>
      </div>

      <div v-else class="cart-content">
        <div class="cart-items">
          <CartItem 
            v-for="item in cartItems" 
            :key="item.id"
            :item="item"
            :is-removing="isRemoving"
            @remove="removeFromCart"
          />
        </div>

        <div class="cart-summary">
          <CartSummary 
            :subtotal="subtotal"
            :shipping="shipping"
          >
            <template #actions>
              <button @click="proceedToCheckout" class="btn btn-checkout">
                Proceed to Checkout
              </button>

              <router-link to="/categories" class="link-continue">
                Continue Shopping
              </router-link>
            </template>
          </CartSummary>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="showNotification" class="toast-notification">
        {{ notificationMessage }}
      </div>
    </Transition>

    <!-- Confirm Dialog -->
    <Transition name="modal">
      <div v-if="showConfirmDialog" class="modal-overlay" @click="cancelClearCart">
        <div class="confirm-dialog" @click.stop>
          <h3>Clear Cart?</h3>
          <p>Are you sure you want to remove all items from your cart?</p>
          <div class="dialog-actions">
            <button 
              @click="cancelClearCart" 
              class="btn btn-secondary"
              :disabled="isClearing"
            >
              Cancel
            </button>
            <button 
              @click="confirmClearCart" 
              class="btn btn-danger"
              :disabled="isClearing"
            >
              {{ isClearing ? 'Clearing...' : 'Clear Cart' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
  </PageLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import CartService from '../../services/CartService';
import PageLayout from '../../components/layout/PageLayout.vue';
import CartItem from '../../components/cart/CartItem.vue';
import CartSummary from '../../components/cart/CartSummary.vue';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';
import { useToast } from '../../composables/useToast';

const router = useRouter();
const $auth = inject('$auth');
const cartItems = ref([]);
const shipping = ref(5.99);
const userCart = ref(null);
const showConfirmDialog = ref(false);
const isRemoving = ref(false);
const isClearing = ref(false);

const { toastVisible: showNotification, toastMessage: notificationMessage, showToast } = useToast();

onMounted(() => {
  // Check token expiration immediately
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) {
    clearAuthState($auth);
    router.push('/login');
    return;
  }
  loadCart();
});

async function loadCart() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  try {
    userCart.value = await CartService.getOrCreateCart(user.userId);
    cartItems.value = await CartService.getCartItems(user.userId);
  } catch (error) {
    console.error('Failed to load cart:', error);
    cartItems.value = [];
  }
}

async function removeFromCart(cartItemId) {
  if (!userCart.value || isRemoving.value) return;
  
  try {
    isRemoving.value = true;
    const success = await CartService.removeItem(userCart.value.cartId, cartItemId);
    if (success) {
      await loadCart();
      showToast('Item removed from cart');
    } else {
      showToast('Failed to remove item');
    }
  } finally {
    isRemoving.value = false;
  }
}

async function clearCart() {
  if (!userCart.value) return;
  showConfirmDialog.value = true;
}

async function confirmClearCart() {
  if (isClearing.value) return;
  
  try {
    isClearing.value = true;
    showConfirmDialog.value = false;
    const success = await CartService.clearCart(userCart.value.cartId);
    if (success) {
      cartItems.value = [];
      showToast('Cart cleared successfully');
    } else {
      showToast('Failed to clear cart');
    }
  } finally {
    isClearing.value = false;
  }
}

function cancelClearCart() {
  showConfirmDialog.value = false;
}

function proceedToCheckout() {
  router.push('/checkout');
}

const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const total = computed(() => {
  return cartItems.value.length > 0 ? subtotal.value + shipping.value : 0;
});
</script>

<style scoped>
.cart-page {
  padding-bottom: 60px;
}

.container {
  padding: 40px 30px;
}

.cart-header {
  margin-bottom: 40px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.cart-header h1 {
  font-size: 2.5em;
  color: white;
  font-weight: 800;
  margin: 0 0 10px 0;
}

.cart-header p {
  color: #b0b0b0;
  font-size: 1em;
  margin: 0;
}

.btn-clear-cart {
  background: transparent;
  border: 2px solid #ff4757;
  color: #ff4757;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9em;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-clear-cart:hover {
  background: #ff4757;
  color: white;
  transform: translateY(-2px);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.confirm-dialog {
  background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
  border-radius: 16px;
  padding: 32px;
  max-width: 450px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  border: 2px solid rgba(233, 69, 96, 0.3);
}

.confirm-dialog h3 {
  color: white;
  font-size: 1.5em;
  margin: 0 0 12px 0;
}

.confirm-dialog p {
  color: #b0b0b0;
  margin: 0 0 24px 0;
  line-height: 1.6;
}

.dialog-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.dialog-actions .btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-secondary {
  background: transparent;
  border: 2px solid #6c757d;
  color: #6c757d;
}

.btn-secondary:hover {
  background: #6c757d;
  color: white;
  transform: translateY(-2px);
}

.btn-danger {
  background: linear-gradient(135deg, #e94560 0%, #ff4757 100%);
  color: white;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.4);
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .confirm-dialog,
.modal-leave-active .confirm-dialog {
  transition: transform 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-from .confirm-dialog {
  transform: scale(0.9) translateY(-20px);
}

.modal-leave-to .confirm-dialog {
  transform: scale(0.9) translateY(20px);
}

.empty-cart {
  text-align: center;
  padding: 80px 20px;
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border-radius: 16px;
  border: 2px dashed rgba(233, 69, 96, 0.3);
}

.empty-icon {
  font-size: 4em;
  margin-bottom: 20px;
  opacity: 0.5;
}

.empty-cart h2 {
  font-size: 1.8em;
  color: white;
  font-weight: 700;
  margin: 0 0 10px 0;
}

.empty-cart p {
  color: #b0b0b0;
  font-size: 1em;
  margin: 0 0 30px 0;
}

.cart-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
  align-items: start;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.cart-item {
  display: grid;
  grid-template-columns: 120px 1fr auto;
  gap: 20px;
  align-items: center;
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 12px;
  padding: 20px;
  transition: all 0.3s;
}

.cart-item:hover {
  border-color: rgba(233, 69, 96, 0.3);
  transform: translateY(-2px);
}

.item-image {
  width: 120px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  background: #0f0f1e;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-details h3 {
  font-size: 1.2em;
  color: white;
  font-weight: 700;
  margin: 0;
}

.item-quantity {
  font-size: 1em;
  color: white;
  font-weight: 600;
  margin: 0;
  padding: 6px 12px;
  background: rgba(233, 69, 96, 0.2);
  border: 1px solid rgba(233, 69, 96, 0.4);
  border-radius: 6px;
  display: inline-block;
  width: fit-content;
}

.item-price {
  font-size: 1.4em;
  color: #e94560;
  font-weight: 800;
  margin: 0;
}

.btn-remove {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 107, 122, 0.1);
  color: #ff6b7a;
  font-size: 1.3em;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove:hover {
  background: rgba(255, 107, 122, 0.2);
  transform: scale(1.1);
}

.cart-summary {
  position: sticky;
  top: 90px;
}

.summary-card {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 1px solid rgba(233, 69, 96, 0.1);
  border-radius: 16px;
  padding: 30px;
}

.summary-card h2 {
  font-size: 1.5em;
  color: white;
  font-weight: 700;
  margin: 0 0 25px 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  color: #b0b0b0;
  font-size: 1em;
}

.summary-row.total {
  color: white;
  font-size: 1.3em;
  font-weight: 800;
  padding-top: 20px;
}

.summary-divider {
  height: 1px;
  background: rgba(233, 69, 96, 0.1);
  margin: 15px 0;
}

.btn {
  padding: 14px 24px;
  border: none;
  border-radius: 8px;
  font-size: 1em;
  font-weight: 700;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  color: white;
  border: 2px solid #e94560;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
}

.btn-checkout {
  width: 100%;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  margin-top: 25px;
}

.btn-checkout:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
}

.link-continue {
  display: block;
  text-align: center;
  color: #e94560;
  text-decoration: none;
  font-size: 0.95em;
  font-weight: 600;
  margin-top: 15px;
  transition: color 0.3s;
}

.link-continue:hover {
  color: #ff6b7a;
  text-decoration: underline;
}

@media (max-width: 1024px) {
  .cart-content {
    grid-template-columns: 1fr;
  }

  .cart-summary {
    position: relative;
    top: 0;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 30px 20px;
  }

  .cart-header h1 {
    font-size: 2em;
  }

  .cart-item {
    grid-template-columns: 80px 1fr auto;
    gap: 15px;
    padding: 15px;
  }

  .item-image {
    width: 80px;
    height: 80px;
  }

  .item-details h3 {
    font-size: 1em;
  }

  .item-price {
    font-size: 1.2em;
  }

  .summary-card {
    padding: 25px 20px;
  }
}

@media (max-width: 480px) {
  .cart-header h1 {
    font-size: 1.6em;
  }

  .cart-item {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .item-image {
    width: 100%;
    height: 200px;
    margin: 0 auto;
  }

  .btn-remove {
    margin: 0 auto;
  }
}
</style>
