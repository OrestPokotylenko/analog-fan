<script setup>
import BaseCard from '../ui/BaseCard.vue';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  // likedItems is passed down by ItemsGrid but not used in this card
  likedItems: {
    type: Array,
    default: () => []
  }
});
</script>

<template>
  <BaseCard
    :link-to="`/my-items/${item.itemId}`"
    :image-src="item.imagesPath?.[0]"
    :image-alt="item.title"
    :title="item.title"
    :price="item.price"
    image-height="250px"
  />
</template>

<style scoped>
/* Any MyItemCard-specific styles can go here if needed */
</style>