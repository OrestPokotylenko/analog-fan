<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../services/axiosConfig';
import ItemsGrid from '../ui/ItemsGrid.vue';
import MyItemCard from '../common/MyItemCard.vue';

const items = ref([]);
const route = useRoute();

onMounted(async () => {
  await fetchItems();
});

watch(() => route.path, async (newPath) => {
  if (newPath === '/my-items') {
    await fetchItems();
  }
});

async function fetchItems() {
  try {
    const response = await axios.get('/items/my-items');
    items.value = response.data;
  } catch (error) {
    console.error('Failed to fetch items:', error);
  }
}
</script>

<template>
  <ItemsGrid
    :items="items"
    :card-component="MyItemCard"
    empty-message="You have no items listed yet."
  />
</template>