<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../../services/axiosConfig';
import Header from '../../components/layout/Header.vue';
import ItemsGrid from '../../components/ui/ItemsGrid.vue';
import ItemCard from '../../components/common/ItemCard.vue';
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue';
import AlertMessage from '../../components/ui/AlertMessage.vue';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');

const wishlistItems = ref([]);
const likedItemIds = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
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
      router.push('/login');
      return;
    }

    const likedResponse = await axios.get(`/liked-items/${user.userId}`);
    const likedItems = likedResponse.data;
    const itemIds = likedItems.map(item => item.itemId);
    likedItemIds.value = itemIds;

    if (itemIds.length > 0) {
      const itemResponses = await Promise.all(itemIds.map(id => axios.get(`/items/${id}`)));
      wishlistItems.value = itemResponses.map(r => r.data);
    }
  } catch (error) {
    console.error('Failed to load wishlist:', error);
    errorMessage.value = 'Failed to load your wishlist.';
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <Header />
  <div class="page">
    <section class="content">
      <div class="container">
        <div class="section-header">
          <div class="section-titles">
            <h2 class="section-title">My Wishlist</h2>
            <p class="section-subtitle">Items you've saved for later</p>
          </div>
        </div>

        <LoadingSpinner v-if="isLoading" message="Loading your wishlist..." />
        <AlertMessage v-else-if="errorMessage" :message="errorMessage" type="error" />
        <ItemsGrid
          v-else
          :items="wishlistItems"
          :liked-items="likedItemIds"
          :card-component="ItemCard"
          empty-message="Your wishlist is empty. Start exploring and save items you love!"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.page {
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
  padding-top: 66px;
}

.content {
  padding: 60px 30px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 40px;
  gap: 20px;
  flex-wrap: wrap;
}

.section-titles {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.section-title {
  font-size: 2.8em;
  color: white;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.5px;
}

.section-subtitle {
  font-size: 1.1em;
  color: #b0b0b0;
  margin: 0;
}

@media (max-width: 768px) {
  .content { padding: 24px 20px; }
  .section-title { font-size: 2em; }
  .section-header { margin-bottom: 24px; }
}

@media (max-width: 480px) {
  .content { padding: 16px 16px; }
  .section-title { font-size: 1.6em; }
  .section-subtitle { font-size: 1em; }
}
</style>