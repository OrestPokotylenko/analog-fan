<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
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
    const response = await axios.get('http://localhost/api/items');
    items.value = response.data;
  } catch (error) {
    console.error('Failed to fetch items:', error);
  }
}

async function fetchLikedItems() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    console.error('User not found in localStorage or userId is missing');
    return;
  }

  try {
    const response = await axios.get(`http://localhost/api/liked-items/${user.userId}`);
    likedItems.value = response.data.map((item) => item.itemId);
  } catch (error) {
    console.error('Failed to fetch liked items:', error);
  }
}
</script>

<template>
  <Header />
  <div class="d-flex flex-wrap">
    <div v-for="item in items" :key="item.itemId">
      <ItemCard :item="item" :likedItems="likedItems" />
    </div>
  </div>
</template>

<style>
</style>