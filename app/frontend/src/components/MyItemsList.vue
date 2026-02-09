<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../services/axiosConfig';
import MyItemCard from '../components/MyItemCard.vue';

const items = ref([]);
const route = useRoute();

onMounted(async () => {
    await fetchItems();
});

// Refetch items when navigating back to this component
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
    <div class="content">
        <div class="cards-container">
            <div v-for="item in items" :key="item.itemId" class="card-wrapper">
                <MyItemCard :item="item" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start;
}

.card-wrapper {
    width: 300px;
    height: auto;
}
</style>