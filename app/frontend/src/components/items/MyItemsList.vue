<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../services/axiosConfig';
import ItemsGrid from '../ui/ItemsGrid.vue';
import MyItemCard from '../common/MyItemCard.vue';
import Pagination from '../ui/Pagination.vue';

const items = ref([]);
const route = useRoute();
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);
const itemsPerPage = 20;

onMounted(async () => {
  await fetchItems();
});

watch(() => route.path, async (newPath) => {
  if (newPath === '/my-items') {
    currentPage.value = 1;
    await fetchItems();
  }
});

watch(currentPage, () => fetchItems());

async function fetchItems() {
  try {
    const response = await axios.get('/items/my-items', {
      params: { page: currentPage.value, limit: itemsPerPage },
    });
    const payload = response.data;
    items.value = payload.data ?? payload;
    totalPages.value = payload.totalPages ?? 1;
    totalItems.value = payload.total ?? items.value.length;
  } catch (error) {
    console.error('Failed to fetch items:', error);
  }
}

function onPageChange(page) {
  currentPage.value = page;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<template>
  <ItemsGrid
    :items="items"
    :card-component="MyItemCard"
    empty-message="You have no items listed yet."
  />
  <Pagination
    :page="currentPage"
    :totalPages="totalPages"
    :total="totalItems"
    :limit="itemsPerPage"
    @update:page="onPageChange"
  />
</template>