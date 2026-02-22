<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../services/axiosConfig';
import Header from '../../components/layout/Header.vue';
import ItemsGrid from '../../components/ui/ItemsGrid.vue';
import ItemCard from '../../components/common/ItemCard.vue';

const items = ref([]);
const likedItems = ref([]);

onMounted(async () => {
  await fetchItems();
  await fetchLikedItems();
});

async function fetchItems() {
  try {
    const response = await axios.get('/items');
    items.value = response.data;
  } catch (error) {
    console.error('Failed to fetch items:', error);
  }
}

async function fetchLikedItems() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) return;
  try {
    const response = await axios.get(`/liked-items/${user.userId}`);
    likedItems.value = response.data.map((item) => item.itemId);
  } catch (error) {
    console.error('Failed to fetch liked items:', error);
  }
}
</script>

<template>
  <Header />
  <div class="home">
    <section class="hero">
      <div class="hero-content">
        <h1 class="hero-title">Welcome to Analog Fan</h1>
        <p class="hero-subtitle">Discover vintage cassettes, vinyl records, and audio players</p>
      </div>
    </section>

    <section class="featured">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Featured Items</h2>
          <p class="section-subtitle">Explore our latest collection</p>
        </div>
        <ItemsGrid
          :items="items"
          :liked-items="likedItems"
          :card-component="ItemCard"
          empty-message="No items available yet."
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.home {
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
}

.hero {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  padding: 120px 30px 80px;
  text-align: center;
  color: white;
}

.hero-content {
  max-width: 1200px;
  margin: 0 auto;
}

.hero-title {
  font-size: 3.5em;
  font-weight: 800;
  margin: 0 0 20px 0;
  letter-spacing: -1px;
  line-height: 1.1;
}

.hero-subtitle {
  font-size: 1.4em;
  margin: 0 auto 40px;
  opacity: 0.95;
  line-height: 1.6;
  max-width: 600px;
}

.featured {
  padding: 60px 30px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 60px;
}

.section-title {
  font-size: 2.8em;
  color: white;
  font-weight: 800;
  margin: 0 0 15px 0;
  letter-spacing: -0.5px;
}

.section-subtitle {
  font-size: 1.1em;
  color: #b0b0b0;
  margin: 0;
}

@media (max-width: 768px) {
  .hero { padding: 80px 20px 60px; }
  .hero-title { font-size: 2.2em; }
  .hero-subtitle { font-size: 1.1em; }
  .featured { padding: 60px 20px; }
  .section-title { font-size: 2em; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 1.8em; }
  .hero-subtitle { font-size: 1em; }
  .section-title { font-size: 1.6em; }
}
</style>