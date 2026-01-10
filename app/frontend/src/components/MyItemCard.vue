<script setup>

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  likedItems: {
    type: Array,
    required: true
  }
});
</script>

<template>
  <RouterLink :to="`/my-items/${item.itemId}`">
    <div class="card d-flex flex-column justify-content-between">
      <img :src="item.imagesPath[0]" class="item-image" :alt="item.title" />
      <div class="card-body">
        <p>{{ item.title }}</p>
        <div class="d-flex justify-content-between align-items-center">
          <strong>€{{ item.price.toFixed(2) }}</strong>
        </div>
      </div>
    </div>
  </RouterLink>
</template>

<style scoped>
.card {
  width: 300px;
  height: 410px;
  margin: 10px;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  cursor: pointer;
}

.item-image {
  object-fit: cover;
  border-radius: 5px;
}

.card-body {
  flex: unset;
  text-align: center;
}

a {
  display: block;
  width: 300px;
  text-decoration: none;
  color: inherit;
}
</style>