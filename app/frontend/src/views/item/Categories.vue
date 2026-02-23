<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import PageLayout from '../../components/layout/PageLayout.vue';
import ItemCard from '../../components/common/ItemCard.vue';
import FilterPanel from '../../components/items/FilterPanel.vue';
import axios from '../../services/axiosConfig';
import { useItemFilters } from '../../composables/useItemFilters';

const router = useRouter();
const route = useRoute();
const categories = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const searchTerm = ref('');
const rawSearchItems = ref([]);
const likedItems = ref([]);
const isSearching = ref(false);
const searchError = ref('');

const {
  minPrice, maxPrice, selectedConditions, selectedTypes,
  sortOrder, filteredItems, activeFilterCount, clearFilters,
} = useItemFilters(rawSearchItems);

function navigateToCategory(categoryName) {
  router.push(`/category/${categoryName.toLowerCase()}`);
}

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

async function fetchItemsBySearch(term) {
  const normalizedTerm = term.trim().toLowerCase();
  if (!normalizedTerm) {
    rawSearchItems.value = [];
    return;
  }

  try {
    isSearching.value = true;
    searchError.value = '';
    clearFilters();
    const response = await axios.get('/items');
    const allItems = Array.isArray(response.data) ? response.data : [];
    rawSearchItems.value = allItems.filter((item) => {
      const title       = String(item.title       ?? '').toLowerCase();
      const description = String(item.description ?? '').toLowerCase();
      const genre       = String(item.genre        ?? '').toLowerCase();
      return title.includes(normalizedTerm)
          || description.includes(normalizedTerm)
          || genre.includes(normalizedTerm);
    });
  } catch (error) {
    console.error('Failed to search items', error);
    searchError.value = 'Failed to search items';
    rawSearchItems.value = [];
  } finally {
    isSearching.value = false;
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

watch(
  () => route.query.search,
  (newSearch) => {
    searchTerm.value = String(newSearch ?? '').trim();
    if (searchTerm.value) {
      fetchItemsBySearch(searchTerm.value);
    } else {
      rawSearchItems.value = [];
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
  <PageLayout>
  <div class="categories-page">
    <div class="container container-lg">
      <div class="page-header">
        <h1 class="page-title">
          {{ searchTerm ? `Search results for "${searchTerm}"` : 'Browse Categories' }}
        </h1>
        <p class="page-subtitle">
          {{ searchTerm ? 'Matching items by name, description or genre' : 'Explore our collection of vintage audio equipment' }}
        </p>
      </div>

      <!-- ── Search results ── -->
      <div v-if="searchTerm">
        <div v-if="isSearching" class="loading-state">
          <p>Searching items...</p>
        </div>

        <div v-else-if="searchError" class="error-state">
          <p>{{ searchError }}</p>
        </div>

        <template v-else>
          <FilterPanel
            v-model:minPrice="minPrice"
            v-model:maxPrice="maxPrice"
            v-model:selectedConditions="selectedConditions"
            v-model:selectedTypes="selectedTypes"
            v-model:sortOrder="sortOrder"
            :productTypes="categories"
            :showTypeFilter="true"
            :activeFilterCount="activeFilterCount"
            @clear="clearFilters"
          />

          <p v-if="rawSearchItems.length" class="result-count">
            {{ filteredItems.length === rawSearchItems.length
              ? `${rawSearchItems.length} result${rawSearchItems.length !== 1 ? 's' : ''}`
              : `${filteredItems.length} of ${rawSearchItems.length} results` }}
          </p>

          <div v-if="!filteredItems.length" class="empty-state">
            <p>{{ rawSearchItems.length ? 'No items match your filters.' : `No items found for "${searchTerm}"` }}</p>
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

      <!-- ── Category cards ── -->
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
            @click="navigateToCategory(category.typeName)"
          >
            <div class="card-background" :style="category.imageUrl ? { backgroundImage: `url(${category.imageUrl})` } : {}"></div>
            <div class="card-content">
              <div v-if="!category.imageUrl" class="card-icon">🎵</div>
              <h2 class="card-title">{{ category.typeName }}</h2>
              <p class="card-subtitle">Browse collection</p>
              <div class="card-arrow">→</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </PageLayout>
</template>

<style scoped>
.categories-page {
  padding-bottom: 50px;
}

.container {
  padding: 40px 30px;
}

.page-header {
  text-align: center;
  margin-bottom: 60px;
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
  margin: 0 auto;
  max-width: 600px;
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
  padding: 60px 30px;
  color: #b0b0b0;
  font-size: 1.1em;
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

/* ── Category cards ── */
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
  inset: 0;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  background-size: cover;
  background-position: center;
  z-index: 1;
}
.card-background::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(26,26,46,0.7) 0%, rgba(22,33,62,0.8) 100%);
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
.category-card:hover .card-content { background: rgba(233, 69, 96, 0.1); }

.card-icon { font-size: 3.5em; margin-bottom: 20px; transition: transform 0.3s; }
.category-card:hover .card-icon { transform: scale(1.1); }

.card-title { font-size: 1.8em; color: white; font-weight: 700; margin: 0 0 10px; }
.card-subtitle { font-size: 0.95em; color: #b0b0b0; margin: 0 0 20px; }

.card-arrow { font-size: 1.5em; color: #e94560; opacity: 0; transition: all 0.3s; transform: translateX(-5px); }
.category-card:hover .card-arrow { opacity: 1; transform: translateX(5px); }

/* ── Responsive ── */
@media (max-width: 1024px) {
  .categories-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
  .items-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }
}

@media (max-width: 768px) {
  .container { padding: 30px 20px; }
  .page-header { margin-bottom: 40px; }
  .page-title { font-size: 2.2em; }
  .page-subtitle { font-size: 1em; }
  .categories-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
  .items-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
  .category-card { height: 240px; }
  .card-icon { font-size: 2.8em; margin-bottom: 15px; }
  .card-title { font-size: 1.4em; }
  .card-subtitle { font-size: 0.85em; }
}

@media (max-width: 480px) {
  .page-title { font-size: 1.8em; }
  .categories-grid { grid-template-columns: 1fr; }
  .items-grid { grid-template-columns: 1fr; }
  .category-card { height: 200px; }
  .card-title { font-size: 1.3em; }
}
</style>
