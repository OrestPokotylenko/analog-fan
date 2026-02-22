<script setup>
defineProps({
  items: {
    type: Array,
    required: true
  },
  likedItems: {
    type: Array,
    default: () => []
  },
  emptyMessage: {
    type: String,
    default: 'No items found.'
  },
  cardComponent: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div v-if="items.length === 0" class="empty-state">
    <p>{{ emptyMessage }}</p>
  </div>
  <div v-else class="items-grid">
    <component
      :is="cardComponent"
      v-for="item in items"
      :key="item.itemId"
      :item="item"
      :likedItems="likedItems"
    />
  </div>
</template>

<style scoped>
.items-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 30px;
}

@media (max-width: 1200px) {
  .items-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
  }
}

@media (max-width: 900px) {
  .items-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
}

@media (max-width: 480px) {
  .items-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

.empty-state {
  text-align: center;
  color: #8892b0;
  padding: 60px 20px;
  font-size: 1.1em;
}
</style>