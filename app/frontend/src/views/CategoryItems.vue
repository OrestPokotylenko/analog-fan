<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../services/axiosConfig';
import Header from '../components/Header.vue';
import ItemCard from '../components/ItemCard.vue';

const route = useRoute();
const items = ref([]);
const likedItems = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const categoryType = ref('');
const productTypeId = ref(null);
const productTypes = ref([]);

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
  const type = productTypes.value.find(t => t.name.toLowerCase() === categoryType.value.toLowerCase());
  productTypeId.value = type ? type.productTypeId : null;
}

async function fetchItemsByType() {
  if (!productTypeId.value) {
    return;
  }

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
  <div class="content">
    <div class="category-header">
      <h1 class="category-title">{{ categoryType || 'Category' }}</h1>
    </div>

    <div v-if="isLoading" class="text-center">
      <p class="loading-text">Loading items...</p>
    </div>

    <div v-else-if="errorMessage" class="text-center">
      <p class="error-text">{{ errorMessage }}</p>
    </div>

    <div v-else-if="!items.length" class="text-center">
      <p class="no-items-text">No items found in this category</p>
    </div>

    <div v-else class="cards-container">
      <div v-for="item in items" :key="item.itemId" class="card-wrapper">
        <ItemCard :item="item" :likedItems="likedItems" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.content {
  padding: 100px 50px 50px;
  min-height: 100vh;
}

.category-header {
  margin-bottom: 40px;
  text-align: center;
}

.category-title {
  font-size: 2.5em;
  color: rgba(255, 255, 255, 0.87);
  text-transform: capitalize;
}

.loading-text,
.error-text,
.no-items-text {
  font-size: 1.2em;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 50px;
}

.error-text {
  color: #ff6b6b;
}

.cards-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card-wrapper {
  width: 100%;
  height: auto;
}

@media (prefers-color-scheme: light) {
  .category-title {
    color: #213547;
  }

  .loading-text,
  .no-items-text {
    color: #666;
  }
}
</style>
