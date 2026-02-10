<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../services/axiosConfig';
import Header from '../components/Header.vue';
import ItemCard from '../components/ItemCard.vue';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');

const wishlistItems = ref([]);
const likedItemIds = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

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
  await loadWishlist();
});

async function loadWishlist() {
  try {
    isLoading.value = true;
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!user || !user.userId) {
      errorMessage.value = 'Please log in to view your wishlist';
      router.push('/login');
      return;
    }

    // Fetch liked items
    const likedResponse = await axios.get(`/liked-items/${user.userId}`);
    const likedItems = likedResponse.data;
    
    // Extract item IDs
    const itemIds = likedItems.map(item => item.itemId);
    likedItemIds.value = itemIds;

    // Fetch full item details for each liked item
    if (itemIds.length > 0) {
      const itemPromises = itemIds.map(id => axios.get(`/items/${id}`));
      const itemResponses = await Promise.all(itemPromises);
      wishlistItems.value = itemResponses.map(response => response.data);
    }
  } catch (error) {
    console.error('Failed to load wishlist:', error);
    errorMessage.value = 'Failed to load your wishlist';
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="wishlist-page">
    <Header />
    
    <div class="container">
      <div class="wishlist-header">
        <h1>My Wishlist</h1>
        <p class="subtitle">Items you've saved for later</p>
      </div>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading your wishlist...</p>
      </div>

      <div v-else-if="errorMessage" class="error-message">
        <p>{{ errorMessage }}</p>
      </div>

      <div v-else-if="wishlistItems.length === 0" class="empty-state">
        <div class="empty-icon">💝</div>
        <h2>Your wishlist is empty</h2>
        <p>Start exploring and save items you love!</p>
        <button @click="router.push('/categories')" class="btn-explore">
          Explore Categories
        </button>
      </div>

      <div v-else class="items-grid">
        <ItemCard
          v-for="item in wishlistItems"
          :key="item.itemId"
          :item="item"
          :likedItems="likedItemIds"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.wishlist-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  padding-top: 70px;
  padding-bottom: 60px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 40px 20px;
}

.wishlist-header {
  text-align: center;
  margin-bottom: 50px;
}

.wishlist-header h1 {
  font-size: 3rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 10px;
}

.subtitle {
  color: #a0a0a0;
  font-size: 1.2rem;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: white;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  text-align: center;
  padding: 80px 20px;
  color: #ff6b6b;
  font-size: 1.2rem;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: white;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 20px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.empty-state h2 {
  font-size: 2rem;
  margin-bottom: 15px;
  color: white;
}

.empty-state p {
  font-size: 1.2rem;
  color: #a0a0a0;
  margin-bottom: 30px;
}

.btn-explore {
  padding: 15px 40px;
  font-size: 1.1rem;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 50px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-explore:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
  padding: 20px 0;
}

@media (max-width: 768px) {
  .wishlist-header h1 {
    font-size: 2rem;
  }

  .subtitle {
    font-size: 1rem;
  }

  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
  }

  .empty-state h2 {
    font-size: 1.5rem;
  }

  .empty-state p {
    font-size: 1rem;
  }
}
</style>
