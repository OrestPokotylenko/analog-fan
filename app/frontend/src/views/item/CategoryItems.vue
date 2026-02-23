<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../services/axiosConfig';
import PageLayout from '../../components/layout/PageLayout.vue';
import ItemCard from '../../components/common/ItemCard.vue';
import FilterPanel from '../../components/items/FilterPanel.vue';
import { useItemFilters } from '../../composables/useItemFilters';

const route = useRoute();
const items = ref([]);
const likedItems = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const categoryType = ref('');
const productTypeId = ref(null);
const productTypes = ref([]);

const {
  minPrice, maxPrice, selectedConditions, selectedTypes,
  sortOrder, filteredItems, activeFilterCount, clearFilters,
} = useItemFilters(items);

onMounted(async () => {
  categoryType.value = route.params.typeName || '';
  await fetchProductTypes();
  await matchTypeNameToId();
  await fetchItemsByType();
  await fetchLikedItems();
});

watch(() => route.params.typeName, (newTypeName) => {
  categoryType.value = newTypeName || '';
  matchTypeNameToId();
  fetchItemsByType();
  clearFilters();
});

async function fetchProductTypes() {
  try {
    const response = await axios.get('/product-types');
    productTypes.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Failed to load product types', error);
    productTypes.value = [];
  }
}

function matchTypeNameToId() {
  const type = productTypes.value.find(t => t.typeName.toLowerCase() === categoryType.value.toLowerCase());
  productTypeId.value = type ? type.productTypeId : null;
}

async function fetchItemsByType() {
  if (!productTypeId.value) return;
  try {
    isLoading.value = true;
    errorMessage.value = '';
    const response = await axios.get(`/items/type/${productTypeId.value}`);
    items.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error('Failed to fetch items:', error);
    errorMessage.value = 'Failed to load items';
    items.value = [];
  } finally {
    isLoading.value = false;
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
  <PageLayout>
  <div class="category-page">
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">{{ categoryType || 'Category' }}</h1>
        <p class="hero-subtitle">Explore our collection</p>
      </div>
    </div>

    <div class="container container-xl">
      <div v-if="isLoading" class="loading-state">
        <p>Loading items...</p>
      </div>

      <div v-else-if="errorMessage" class="error-state">
        <p>{{ errorMessage }}</p>
      </div>

      <template v-else>
        <FilterPanel
          v-model:minPrice="minPrice"
          v-model:maxPrice="maxPrice"
          v-model:selectedConditions="selectedConditions"
          v-model:selectedTypes="selectedTypes"
          v-model:sortOrder="sortOrder"
          :activeFilterCount="activeFilterCount"
          @clear="clearFilters"
        />

        <p v-if="filteredItems.length !== items.length" class="result-count">
          Showing {{ filteredItems.length }} of {{ items.length }} items
        </p>

        <div v-if="!filteredItems.length" class="empty-state">
          <p>{{ items.length ? 'No items match your filters.' : 'No items found in this category.' }}</p>
          <button v-if="activeFilterCount" class="btn-reset" @click="clearFilters">Clear filters</button>
        </div>

        <div v-else class="items-grid">
          <ItemCard
            v-for="item in filteredItems"
            :key="item.itemId"
            :item="item"
            :likedItems="likedItems"
          />
        </div>
      </template>
    </div>
  </div>
  </PageLayout>
</template>

<style scoped>
.category-page {
  min-height: unset;
  padding-bottom: 60px;
}

.hero-section {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  padding: 80px 30px;
  text-align: center;
  color: white;
  margin-bottom: 60px;
}

.hero-content {
  max-width: 1200px;
  margin: 0 auto;
}

.hero-title {
  font-size: 3em;
  font-weight: 800;
  margin: 0 0 15px 0;
  letter-spacing: -1px;
  line-height: 1.1;
  text-transform: capitalize;
}

.hero-subtitle {
  font-size: 1.2em;
  margin: 0;
  opacity: 0.95;
}

.container {
  padding: 0 30px;
}

.result-count {
  color: #b0b0b0;
  font-size: 0.9em;
  margin: 0 0 20px;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 80px 30px;
  font-size: 1.1em;
}

.loading-state,
.empty-state {
  color: #b0b0b0;
}

.error-state { color: #ff6b7a; }

.btn-reset {
  margin-top: 16px;
  background: transparent;
  border: 1px solid rgba(233, 69, 96, 0.4);
  color: #e94560;
  padding: 8px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9em;
  transition: all 0.2s;
}
.btn-reset:hover { background: rgba(233, 69, 96, 0.1); }

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1024px) {
  .items-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }
}

@media (max-width: 768px) {
  .hero-section { padding: 60px 20px; margin-bottom: 40px; }
  .hero-title   { font-size: 2.2em; }
  .hero-subtitle { font-size: 1em; }
  .container    { padding: 0 20px; }
  .items-grid   { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 1.8em; }
  .items-grid { grid-template-columns: 1fr; }
}
</style>
