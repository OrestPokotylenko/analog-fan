<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Header from '../components/Header.vue';
import axios from '../services/axiosConfig';

const router = useRouter();
const categories = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');

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

onMounted(fetchCategories);
</script>

<template>
  <Header />
  <div class="content">
    <h1 class="title">Choose a Category</h1>
    
    <div v-if="isLoading" class="text-center">
      <p class="loading-text">Loading categories...</p>
    </div>
    
    <div v-else-if="errorMessage" class="text-center">
      <p class="error-text">{{ errorMessage }}</p>
    </div>
    
    <div v-else-if="!categories.length" class="text-center">
      <p class="no-categories-text">No categories available</p>
    </div>
    
    <div v-else class="categories-container">
      <div
        v-for="category in categories"
        :key="category.productTypeId"
        class="category-card"
        @click="navigateToCategory(category.name)"
      >
        <div class="category-icon">📦</div>
        <h2 class="category-name">{{ category.name }}</h2>
        <p class="category-description">Browse {{ category.name }}</p>
        <button class="explore-button">Explore</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.content {
  display: flex;
  flex-direction: column;
  height: 100%;
  margin: 100px 50px;
}

.title {
  text-align: center;
  font-size: 2.5em;
  margin-bottom: 50px;
  color: rgba(255, 255, 255, 0.87);
}

.loading-text,
.error-text,
.no-categories-text {
  font-size: 1.2em;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 50px;
}

.error-text {
  color: #ff6b6b;
}

.categories-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  padding: 20px;
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 30px;
  border: 2px solid #646cff;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(100, 108, 255, 0.1), rgba(100, 108, 255, 0.05));
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  min-height: 350px;
}

.category-card:hover {
  transform: translateY(-10px);
  border-color: #535bf2;
  background: linear-gradient(135deg, rgba(100, 108, 255, 0.2), rgba(100, 108, 255, 0.1));
  box-shadow: 0 10px 30px rgba(100, 108, 255, 0.3);
}

.category-icon {
  font-size: 5em;
  margin-bottom: 20px;
}

.category-name {
  font-size: 1.8em;
  margin: 15px 0;
  color: rgba(21, 25, 255, 0.87);
}

.category-description {
  font-size: 1em;
  color: rgba(21, 25, 255, 0.87);
  margin-bottom: 25px;
}

.explore-button {
  padding: 10px 30px;
  font-size: 1em;
  font-weight: 500;
  background-color: #646cff;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.25s;
}

.explore-button:hover {
  background-color: #535bf2;
}

@media (prefers-color-scheme: light) {
  .title {
    color: #213547;
  }

  .loading-text,
  .no-categories-text {
    color: #666;
  }

  .category-name {
    color: #213547;
  }

  .category-description {
    color: #555;
  }

  .category-card {
    border-color: #646cff;
    background: linear-gradient(135deg, rgba(100, 108, 255, 0.05), rgba(100, 108, 255, 0.02));
  }

  .category-card:hover {
    background: linear-gradient(135deg, rgba(100, 108, 255, 0.1), rgba(100, 108, 255, 0.05));
  }
}
</style>