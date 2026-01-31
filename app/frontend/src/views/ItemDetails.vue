<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../services/axiosConfig';
import Header from '../components/Header.vue';

const route = useRoute();
const router = useRouter();
const item = ref(null);
const seller = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');
const isLiked = ref(false);
const cartItems = ref([]);
const selectedImageIndex = ref(0);

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
  try {
    const cartData = localStorage.getItem('cart');
    cartItems.value = cartData ? JSON.parse(cartData) : [];
  } catch (error) {
    console.error('Failed to fetch cart:', error);
    cartItems.value = [];
  }
}

async function toggleLike() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  try {
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
  }
}

function addToCart() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  if (item.value.userId === user.userId) {
    alert('You cannot add your own items to cart');
    return;
  }

  const cartItem = {
    itemId: item.value.itemId,
    title: item.value.title,
    price: item.value.price,
    image: item.value.imagesPath?.[0]
  };

  const existingItem = cartItems.value.find(ci => ci.itemId === item.value.itemId);
  if (existingItem) {
    alert('Item already in cart');
    return;
  }

  cartItems.value.push(cartItem);
  localStorage.setItem('cart', JSON.stringify(cartItems.value));
  alert('Added to cart!');
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

  addToCart();
  router.push('/cart');
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

onUnmounted(() => {
  // cleanup
});
</script>

<template>
  <Header />
  <div class="details-page">
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
          >
            ❤️
          </button>
        </div>

        <!-- Price and Action Buttons -->
        <div class="price-action-section">
          <div class="price-box">
            <span class="price-label">Price</span>
            <p class="price">€{{ item.price.toFixed(2) }}</p>
          </div>
          <div class="actions-section">
            <button class="btn btn-primary" @click="addToCart">
              Add to Cart
            </button>
            <button class="btn btn-accent" @click="buyNow">
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
        </div>
      </div>
    </div>
  </div>
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

.actions-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
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
</style>
