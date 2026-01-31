<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Header from '../components/Header.vue';
import ItemCard from '../components/ItemCard.vue';
import axios from '../services/axiosConfig';

const router = useRouter();
const route = useRoute();
const categories = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const searchTerm = ref('');
const items = ref([]);
const likedItems = ref([]);
const isSearching = ref(false);
const searchError = ref('');

async function fetchCategories() {
  try {
    isLoading.value = true;
    const response = await axios.get('/product-types');
    categories.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Failed to load categories', error);
    errorMessage.value = 'Failed to load categories';
  } finally {
    isLoading.value = false;
  }
}

function navigateToCategory(categoryName) {
  router.push(`/category/${categoryName.toLowerCase()}`);
}

async function fetchItemsBySearch(term) {
  const normalizedTerm = term.trim().toLowerCase();
  if (!normalizedTerm) {
    items.value = [];
    return;
  }

  try {
    isSearching.value = true;
    searchError.value = '';
    const response = await axios.get('/items');
    const allItems = Array.isArray(response.data) ? response.data : [];
    items.value = allItems.filter((item) => {
      const title = String(item.title ?? '').toLowerCase();
      const description = String(item.description ?? '').toLowerCase();
      return title.includes(normalizedTerm) || description.includes(normalizedTerm);
    });
  } catch (error) {
    console.error('Failed to search items', error);
    searchError.value = 'Failed to search items';
    items.value = [];
  } finally {
    isSearching.value = false;
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

watch(
  () => route.query.search,
  (newSearch) => {
    searchTerm.value = String(newSearch ?? '').trim();
    if (searchTerm.value) {
      fetchItemsBySearch(searchTerm.value);
    } else {
      items.value = [];
      searchError.value = '';
    }
  },
  { immediate: true }
);

onMounted(async () => {
  await fetchCategories();
  await fetchLikedItems();
});
</script>

<template>
  <Header />
  <div class="categories-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">
          {{ searchTerm ? `Search results for "${searchTerm}"` : 'Browse Categories' }}
        </h1>
        <p class="page-subtitle">
          {{ searchTerm ? 'Matching items by name or description' : 'Explore our collection of vintage audio equipment' }}
        </p>
      </div>

      <div v-if="searchTerm">
        <div v-if="isSearching" class="loading-state">
          <p>Searching items...</p>
        </div>

        <div v-else-if="searchError" class="error-state">
          <p>{{ searchError }}</p>
        </div>

        <div v-else-if="!items.length" class="empty-state">
          <p>No items found for "{{ searchTerm }}"</p>
        </div>

        <div v-else class="items-grid">
          <ItemCard
            v-for="item in items"
            :key="item.itemId"
            :item="item"
            :likedItems="likedItems"
          />
        </div>
      </div>

      <div v-else>
        <div v-if="isLoading" class="loading-state">
          <p>Loading categories...</p>
        </div>

        <div v-else-if="errorMessage" class="error-state">
          <p>{{ errorMessage }}</p>
        </div>

        <div v-else-if="!categories.length" class="empty-state">
          <p>No categories available</p>
        </div>

        <div v-else class="categories-grid">
          <div
            v-for="category in categories"
            :key="category.productTypeId"
            class="category-card"
            @click="navigateToCategory(category.name)"
          >
            <div class="card-background"></div>
            <div class="card-content">
              <div class="card-icon">🎵</div>
              <h2 class="card-title">{{ category.name }}</h2>
              <p class="card-subtitle">Browse collection</p>
              <div class="card-arrow">→</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.categories-page {
  padding-top: 70px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
  padding-bottom: 50px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 30px;
}

.page-header {
  text-align: center;
  margin-bottom: 80px;
}

.page-title {
  font-size: 3em;
  color: white;
  font-weight: 800;
  margin: 0 0 15px 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  font-size: 1.2em;
  color: #b0b0b0;
  margin: 0;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 30px;
  color: #b0b0b0;
  font-size: 1.1em;
}

.error-state {
  color: #ff6b7a;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 30px;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
}

.category-card {
  position: relative;
  height: 280px;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.category-card:hover {
  transform: translateY(-8px);
  border-color: #e94560;
}

.card-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  z-index: 1;
}

.card-content {
  position: relative;
  z-index: 2;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  text-align: center;
  transition: all 0.3s;
}

.category-card:hover .card-content {
  background: rgba(233, 69, 96, 0.1);
}

.card-icon {
  font-size: 3.5em;
  margin-bottom: 20px;
  transition: transform 0.3s;
}

.category-card:hover .card-icon {
  transform: scale(1.1);
}

.card-title {
  font-size: 1.8em;
  color: white;
  font-weight: 700;
  margin: 0 0 10px 0;
  letter-spacing: 0.5px;
}

.card-subtitle {
  font-size: 0.95em;
  color: #b0b0b0;
  margin: 0 0 20px 0;
}

.card-arrow {
  font-size: 1.5em;
  color: #e94560;
  opacity: 0;
  transition: all 0.3s;
  transform: translateX(-5px);
}

.category-card:hover .card-arrow {
  opacity: 1;
  transform: translateX(5px);
}

@media (max-width: 1024px) {
  .categories-grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 25px;
  }

  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 25px;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 30px 20px;
  }

  .page-header {
    margin-bottom: 50px;
  }

  .page-title {
    font-size: 2.2em;
  }

  .page-subtitle {
    font-size: 1em;
  }

  .categories-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
  }

  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
  }

  .category-card {
    height: 240px;
  }

  .card-icon {
    font-size: 2.8em;
    margin-bottom: 15px;
  }

  .card-title {
    font-size: 1.4em;
  }

  .card-subtitle {
    font-size: 0.85em;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.8em;
  }

  .page-subtitle {
    font-size: 0.95em;
  }

  .categories-grid {
    grid-template-columns: 1fr;
  }

  .category-card {
    height: 200px;
  }

  .card-icon {
    font-size: 2.5em;
  }

  .card-title {
    font-size: 1.3em;
  }
}
</style>