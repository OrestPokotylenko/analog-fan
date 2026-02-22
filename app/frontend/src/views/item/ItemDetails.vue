<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../services/axiosConfig';
import CartService from '../../services/CartService';
import Header from '../../components/layout/Header.vue';
import LoginPrompt from '../../components/common/LoginPrompt.vue';

const route = useRoute();
const router = useRouter();
const item = ref(null);
const seller = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');
const isLiked = ref(false);
const cartItems = ref([]);
const selectedImageIndex = ref(0);
const showNotification = ref(false);
const notificationMessage = ref('');
const addedToCart = ref(false);
const showLoginPrompt = ref(false);
const isLiking = ref(false);
const isAddingToCart = ref(false);
const selectedQuantity = ref(1);

onMounted(async () => {
  await fetchItem();
  await fetchLikedItems();
  await fetchCart();
});

async function fetchItem() {
  try {
    isLoading.value = true;
    const response = await axios.get(`/items/${route.params.id}`);
    item.value = response.data;
    selectedImageIndex.value = 0;
    selectedQuantity.value = 1; // Reset quantity when loading new item
    if (item.value.userId) {
      await fetchSeller(item.value.userId);
    }
  } catch (error) {
    console.error('Failed to fetch item:', error);
    errorMessage.value = 'Failed to load item details';
  } finally {
    isLoading.value = false;
  }
}

async function fetchSeller(userId) {
  try {
    const response = await axios.get(`/users/${userId}`);
    seller.value = response.data;
  } catch (error) {
    console.error('Failed to fetch seller:', error);
  }
}

async function fetchLikedItems() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    return;
  }

  try {
    const response = await axios.get(`/liked-items/${user.userId}`);
    const likedIds = response.data.map((item) => item.itemId);
    isLiked.value = likedIds.includes(parseInt(route.params.id));
  } catch (error) {
    console.error('Failed to fetch liked items:', error);
  }
}

async function fetchCart() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    cartItems.value = [];
    return;
  }

  try {
    cartItems.value = await CartService.getCartItems(user.userId);
  } catch (error) {
    console.error('Failed to fetch cart:', error);
    cartItems.value = [];
  }
}

async function toggleLike() {
  if (isLiking.value) return;
  
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    showLoginPrompt.value = true;
    return;
  }

  try {
    isLiking.value = true;
    if (isLiked.value) {
      await axios.delete('/liked-items', {
        data: {
          itemId: item.value.itemId,
          userId: user.userId
        }
      });
    } else {
      await axios.post('/liked-items', {
        itemId: item.value.itemId,
        userId: user.userId
      });
    }
    isLiked.value = !isLiked.value;
  } catch (error) {
    console.error('Failed to toggle like:', error);
  } finally {
    isLiking.value = false;
  }
}

function showToast(message, duration = 3000) {
  notificationMessage.value = message;
  showNotification.value = true;
  setTimeout(() => {
    showNotification.value = false;
  }, duration);
}

async function addToCart() {
  if (isAddingToCart.value) return;
  
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  if (item.value.userId === user.userId) {
    showToast('You cannot add your own items to cart');
    return;
  }

  if (item.value.quantity < 1) {
    showToast('This item is out of stock');
    return;
  }

  if (selectedQuantity.value > item.value.quantity) {
    showToast(`Only ${item.value.quantity} available`);
    return;
  }

  const existingItem = cartItems.value.find(ci => ci.itemId === item.value.itemId);
  if (existingItem) {
    showToast('Item already in cart');
    return;
  }

  try {
    isAddingToCart.value = true;
    // Optimistic update - show immediately
    addedToCart.value = true;
    showToast(`✓ Added ${selectedQuantity.value} to cart!`);
    
    // Add to local cart items immediately (optimistic)
    cartItems.value.push({
      itemId: item.value.itemId,
      title: item.value.title,
      price: item.value.price,
      quantity: selectedQuantity.value,
      images: item.value.images
    });
    
    // Add to backend
    const success = await CartService.addItem(
      user.userId,
      item.value.itemId,
      selectedQuantity.value
    );
    
    if (!success) {
      showToast('Failed to sync with server');
      addedToCart.value = false;
      // Remove from local cart if backend failed
      cartItems.value = cartItems.value.filter(ci => ci.itemId !== item.value.itemId);
    }
  } catch (error) {
    console.error('Failed to add to cart:', error);
    showToast('Failed to add to cart');
    addedToCart.value = false;
    cartItems.value = cartItems.value.filter(ci => ci.itemId !== item.value.itemId);
  } finally {
    isAddingToCart.value = false;
    setTimeout(() => {
      addedToCart.value = false;
    }, 2000);
  }
}

function buyNow() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  if (item.value.userId === user.userId) {
    alert('You cannot buy your own items');
    return;
  }

  if (item.value.quantity < 1) {
    showToast('This item is out of stock');
    return;
  }

  addToCart();
  router.push('/cart');
}

function incrementQuantity() {
  if (selectedQuantity.value < item.value.quantity) {
    selectedQuantity.value++;
  }
}

function decrementQuantity() {
  if (selectedQuantity.value > 1) {
    selectedQuantity.value--;
  }
}

const formattedDate = computed(() => {
  if (!item.value?.createdAt) return '';
  return new Date(item.value.createdAt).toLocaleDateString();
});

const currentImage = computed(() => {
  if (!item.value?.imagesPath?.length) return '';
  return item.value.imagesPath[selectedImageIndex.value] || item.value.imagesPath[0];
});

function selectImage(index) {
  selectedImageIndex.value = index;
}

function showPrevImage() {
  if (!item.value?.imagesPath?.length) return;
  selectedImageIndex.value =
    (selectedImageIndex.value - 1 + item.value.imagesPath.length) %
    item.value.imagesPath.length;
}

function showNextImage() {
  if (!item.value?.imagesPath?.length) return;
  selectedImageIndex.value =
    (selectedImageIndex.value + 1) % item.value.imagesPath.length;
}

async function contactSeller() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    showLoginPrompt.value = true;
    return;
  }

  if (item.value.userId === user.userId) {
    showToast('You cannot message yourself');
    return;
  }

  // Navigate to messages page with item and seller info
  // Conversation will be created when first message is sent
  router.push({
    path: '/messages/new',
    query: {
      itemId: item.value.itemId,
      sellerId: item.value.userId,
      sellerUsername: seller.value?.username || 'Seller',
      itemTitle: item.value.title
    }
  });
}

onUnmounted(() => {
  // cleanup
});
</script>

<template>
  <Header />
  <div class="details-page">
    <!-- Toast Notification -->
    <transition name="toast">
      <div v-if="showNotification" class="toast-notification">
        {{ notificationMessage }}
      </div>
    </transition>
    
    <div v-if="isLoading" class="loading-state">
      <p>Loading item...</p>
    </div>

    <div v-else-if="errorMessage" class="error-state">
      <p>{{ errorMessage }}</p>
    </div>

    <div v-else-if="item" class="container">
      <!-- Images Section - Full width on top -->
      <div class="images-section-top">
        <div 
          v-if="item.imagesPath && item.imagesPath.length"
          class="image-container"
        >
          <div class="main-image-wrapper">
            <button
              v-if="item.imagesPath.length > 1"
              class="carousel-btn prev"
              @click="showPrevImage"
              aria-label="Previous image"
            >
              ‹
            </button>
            <img 
              :src="currentImage" 
              :alt="item.title"
              class="main-image"
            />
            <button
              v-if="item.imagesPath.length > 1"
              class="carousel-btn next"
              @click="showNextImage"
              aria-label="Next image"
            >
              ›
            </button>
          </div>
          <div v-if="item.imagesPath.length > 1" class="thumbnail-scroll">
            <button
              v-for="(img, idx) in item.imagesPath"
              :key="idx"
              type="button"
              class="thumbnail-btn"
              :class="{ active: idx === selectedImageIndex }"
              @click="selectImage(idx)"
            >
              <img 
                :src="img" 
                :alt="`Thumbnail ${idx + 1}`"
                class="thumbnail"
              />
            </button>
          </div>
        </div>
        <div v-else class="no-image">
          <p>No images available</p>
        </div>
      </div>

      <!-- Details Section -->
      <div class="info-section">
        <!-- Title and Like Button -->
        <div class="header-section">
          <div class="title-area">
            <p class="product-type">{{ item.type || 'Product' }}</p>
            <h1 class="product-title">{{ item.title }}</h1>
            <p class="product-date">Listed {{ formattedDate }}</p>
          </div>
          <button 
            class="like-button"
            :class="{ liked: isLiked }"
            @click="toggleLike"
            :title="isLiked ? 'Remove from wishlist' : 'Add to wishlist'"
            :disabled="isLiking"
          >
            {{ isLiking ? '⏳' : '❤️' }}
          </button>
        </div>

        <!-- Price and Action Buttons -->
        <div class="price-action-section">
          <div class="price-box">
            <span class="price-label">Price</span>
            <p class="price">€{{ item.price.toFixed(2) }}</p>
            <span class="stock-info" :class="{ 'out-of-stock': item.quantity === 0 }">
              {{ item.quantity === 0 ? 'Out of Stock' : `${item.quantity} available` }}
            </span>
          </div>
          <div class="actions-section">
            <!-- Quantity Selector -->
            <div class="quantity-selector" v-if="item.quantity > 0">
              <label class="quantity-label">Quantity:</label>
              <div class="quantity-controls">
                <button 
                  type="button" 
                  class="qty-btn" 
                  @click="decrementQuantity"
                  :disabled="selectedQuantity <= 1"
                >
                  −
                </button>
                <input 
                  type="number" 
                  v-model.number="selectedQuantity" 
                  class="qty-input" 
                  min="1" 
                  :max="item.quantity"
                  @input="selectedQuantity = Math.max(1, Math.min(item.quantity, selectedQuantity))"
                />
                <button 
                  type="button" 
                  class="qty-btn" 
                  @click="incrementQuantity"
                  :disabled="selectedQuantity >= item.quantity"
                >
                  +
                </button>
              </div>
            </div>

            <button 
              class="btn btn-primary" 
              :class="{ 'btn-added': addedToCart, 'btn-disabled': item.quantity === 0 }"
              @click="addToCart"
              :disabled="isAddingToCart || addedToCart || item.quantity === 0"
            >
              {{ item.quantity === 0 ? 'Out of Stock' : (addedToCart ? '✓ Added!' : (isAddingToCart ? 'Adding...' : 'Add to Cart')) }}
            </button>
            <button 
              class="btn btn-accent" 
              @click="buyNow"
              :disabled="item.quantity === 0"
            >
              Buy Now
            </button>
          </div>
        </div>

        <!-- Description -->
        <div class="description-box">
          <h3>Description</h3>
          <p class="description-text">{{ item.description }}</p>
        </div>

        <!-- Seller Info at Bottom -->
        <div v-if="seller" class="seller-box">
          <h3>Seller</h3>
          <div class="seller-details">
            <p class="seller-name">{{ seller.username }}</p>
            <p class="seller-email">{{ seller.email }}</p>
          </div>
          <button class="btn btn-secondary contact-seller-btn" @click="contactSeller">
            💬 Contact Seller
          </button>
        </div>
      </div>
    </div>
  </div>

  <LoginPrompt
    :show="showLoginPrompt"
    @close="showLoginPrompt = false"
    @login="router.push('/login')"
  />
</template>

<style scoped>
.details-page {
  padding-top: 70px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
  padding-bottom: 60px;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 40px 30px;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 100px 30px;
  color: #b0b0b0;
  font-size: 1.1em;
}

.error-state {
  color: #ff6b7a;
}

/* Images Section - Full Width Top */
.images-section-top {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 50px;
  width: 100%;
}

.image-container {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.main-image-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 600px;
  width: 100%;
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border-radius: 12px;
  border: 2px solid #16213e;
  padding: 20px;
  box-sizing: border-box;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 8px;
}

.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: rgba(233, 69, 96, 0.85);
  color: white;
  font-size: 2em;
  font-weight: normal;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  transition: all 0.3s;
  padding: 0;
  text-align: center;
}

.carousel-btn:hover {
  background: #e94560;
  transform: translateY(-50%) scale(1.05);
}

.carousel-btn.prev {
  left: 10px;
}

.carousel-btn.next {
  right: 10px;
}

.thumbnail-scroll {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 10px;
}

.thumbnail-btn {
  border: 2px solid transparent;
  padding: 0;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.thumbnail-btn.active {
  border-color: #e94560;
  box-shadow: 0 0 0 2px rgba(233, 69, 96, 0.2);
}

.thumbnail {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid transparent;
  transition: all 0.3s;
}

.thumbnail:hover {
  border-color: #e94560;
  transform: scale(1.05);
}

.no-image {
  width: 100%;
  height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 2px dashed #e94560;
  border-radius: 12px;
  color: #b0b0b0;
  font-size: 1.1em;
}

/* Info Section */
.info-section {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
}

.title-area {
  flex: 1;
}

.product-type {
  font-size: 0.75em;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: #e94560;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.product-title {
  font-size: 1.8em;
  color: white;
  font-weight: 800;
  margin: 0 0 8px 0;
  line-height: 1.2;
  letter-spacing: -0.5px;
}

.product-date {
  font-size: 0.85em;
  color: #b0b0b0;
  margin: 0;
}

.like-button {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 2px solid #16213e;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  font-size: 1.8em;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.like-button:hover {
  border-color: #e94560;
  transform: scale(1.1);
}

.like-button.liked {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

/* Price and Action Section */
.price-action-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.price-box {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  padding: 25px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.price-label {
  font-size: 0.75em;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 700;
}

.price {
  font-size: 2em;
  color: white;
  font-weight: 800;
  margin: 0;
  line-height: 1;
}

.stock-info {
  font-size: 0.85em;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 600;
  padding: 4px 8px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  display: inline-block;
  width: fit-content;
}

.stock-info.out-of-stock {
  background: rgba(0, 0, 0, 0.2);
  color: rgba(255, 255, 255, 0.7);
}

.actions-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quantity-selector {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 1px solid #1a1a2e;
  border-radius: 8px;
}

.quantity-label {
  font-size: 0.9em;
  color: white;
  font-weight: 600;
  margin: 0;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.qty-btn {
  background: #e94560;
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 6px;
  font-size: 1.2em;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.qty-btn:hover:not(:disabled) {
  background: #ff6b7a;
  transform: scale(1.05);
}

.qty-btn:disabled {
  background: #555;
  cursor: not-allowed;
  opacity: 0.5;
}

.qty-input {
  width: 60px;
  height: 36px;
  text-align: center;
  background: #1a1a2e;
  border: 1px solid #2a2a3e;
  border-radius: 6px;
  color: white;
  font-size: 1em;
  font-weight: 600;
}

.qty-input:focus {
  outline: none;
  border-color: #e94560;
}

/* Remove number input spinner arrows */
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.qty-input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}

.btn {
  padding: 14px 20px;
  border: none;
  border-radius: 8px;
  font-size: 0.9em;
  font-weight: 700;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s;
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

.btn-primary.btn-disabled,
.btn-primary:disabled {
  background: linear-gradient(135deg, #2a2a3e 0%, #1f1f2e 100%);
  border-color: #555;
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-accent {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
}

.btn-accent:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
}

.description-box,
.seller-box {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #1a1a2e;
}

.description-box h3,
.seller-box h3 {
  font-size: 1em;
  color: white;
  font-weight: 700;
  margin: 0 0 12px 0;
  letter-spacing: 0.5px;
}

.description-text {
  color: #b0b0b0;
  line-height: 1.6;
  margin: 0;
  font-size: 0.9em;
}

.seller-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.seller-name {
  font-size: 0.95em;
  color: white;
  font-weight: 700;
  margin: 0;
}

.seller-email {
  font-size: 0.85em;
  color: #b0b0b0;
  margin: 0;
}

.contact-seller-btn {
  margin-top: 16px;
  width: 100%;
  padding: 12px 20px;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 0.95em;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3);
}

.contact-seller-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(233, 69, 96, 0.4);
}

@media (max-width: 1024px) {
  .price-action-section {
    grid-template-columns: 1fr;
  }

  .product-title {
    font-size: 1.6em;
  }

  .price {
    font-size: 1.8em;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 30px 20px;
  }

  .images-section-top {
    margin-bottom: 40px;
  }

  .product-title {
    font-size: 1.5em;
  }

  .price-box {
    padding: 20px;
  }

  .price {
    font-size: 1.6em;
  }

  .description-box,
  .seller-box {
    padding: 15px;
  }

  .actions-section {
    flex-direction: column;
  }

  .btn {
    padding: 12px 18px;
    font-size: 0.85em;
  }
}

@media (max-width: 480px) {
  .product-title {
    font-size: 1.3em;
  }

  .product-date {
    font-size: 0.8em;
  }

  .price {
    font-size: 1.5em;
  }

  .description-text {
    font-size: 0.85em;
  }

  .like-button {
    width: 45px;
    height: 45px;
    font-size: 1.5em;
  }

  .price-action-section {
    grid-template-columns: 1fr;
  }
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 100px;
  right: 30px;
  background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
  color: white;
  padding: 16px 24px;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(39, 174, 96, 0.3);
  font-weight: 600;
  font-size: 1em;
  z-index: 2000;
  animation: slideIn 0.3s ease-out;
}

.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
  animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(400px);
    opacity: 0;
  }
}

/* Button Success State */
.btn-added {
  background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%) !important;
  transform: scale(1.05);
  animation: successPulse 0.4s ease;
}

@keyframes successPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1.05);
  }
}

@media (max-width: 768px) {
  .toast-notification {
    top: 80px;
    right: 20px;
    left: 20px;
    text-align: center;
  }
}
</style>

