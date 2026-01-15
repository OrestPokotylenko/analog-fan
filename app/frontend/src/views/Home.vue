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
  <div class="content">
    <div class="cards-container">
      <div v-for="item in items" :key="item.itemId" class="card-wrapper">
        <ItemCard :item="item" :likedItems="likedItems" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.cards-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card-wrapper {
  width: 100%;
  height: auto;
}
</style>