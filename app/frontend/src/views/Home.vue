<script setup>
import { ref, onMounted } from 'vue';
import axios from '../services/axiosConfig';
import Header from '../components/Header.vue';
import ItemCard from '../components/ItemCard.vue';

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
  if (!user || !user.userId) {
    return;
  }

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
        <div class="items-grid">
          <ItemCard 
            v-for="item in items" 
            :key="item.itemId" 
            :item="item" 
            :likedItems="likedItems" 
          />
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.home {
  padding-top: 70px;
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
  margin: 0 0 40px 0;
  opacity: 0.95;
  line-height: 1.6;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.cta-button {
  display: inline-block;
  background-color: #1a1a2e;
  color: #e94560;
  padding: 16px 45px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 700;
  transition: all 0.3s;
  font-size: 1.1em;
  border: 2px solid #1a1a2e;
}

.cta-button:hover {
  background-color: transparent;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.featured {
  padding: 100px 30px;
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

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
}

@media (max-width: 1024px) {
  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 25px;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 80px 20px 60px;
  }

  .hero-title {
    font-size: 2.2em;
  }

  .hero-subtitle {
    font-size: 1.1em;
  }

  .featured {
    padding: 60px 20px;
  }

  .section-title {
    font-size: 2em;
  }

  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 1.8em;
  }

  .hero-subtitle {
    font-size: 1em;
  }

  .section-title {
    font-size: 1.6em;
  }

  .items-grid {
    grid-template-columns: 1fr;
  }
}
</style>