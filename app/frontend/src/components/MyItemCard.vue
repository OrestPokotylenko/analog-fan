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
  <RouterLink :to="`/my-items/${item.itemId}`" class="card-link">
    <div class="card">
      <div class="card-image-container">
        <img 
          v-if="item.imagesPath?.[0]"
          :src="item.imagesPath[0]" 
          class="card-image" 
          :alt="item.title"
        />
        <div v-else class="card-image-placeholder">No Image</div>
      </div>

      <div class="card-body">
        <h3 class="card-title">{{ item.title }}</h3>
        <p class="card-description">{{ item.description.substring(0, 80) }}...</p>
        <div class="card-footer">
          <span class="card-price">€{{ item.price.toFixed(2) }}</span>
        </div>
      </div>
    </div>
  </RouterLink>
</template>

<style scoped>
.card-link {
  text-decoration: none;
  color: inherit;
  display: block;
  height: 100%;
}

.card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  height: 100%;
  border: 1px solid rgba(233, 69, 96, 0.1);
}

.card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(233, 69, 96, 0.2);
  border-color: rgba(233, 69, 96, 0.3);
}

.card-image-container {
  position: relative;
  width: 100%;
  height: 250px;
  background: #0f0f1e;
  overflow: hidden;
  cursor: pointer;
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.1) 0%, rgba(233, 69, 96, 0.05) 100%);
  color: #666;
  font-weight: 500;
  font-size: 0.9em;
}


.card-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex: 1;
  gap: 12px;
}

.card-title {
  font-size: 1.1em;
  font-weight: 700;
  color: #e0e0e0;
  margin: 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-description {
  font-size: 0.85em;
  color: #a0a0a0;
  margin: 0;
  flex: 1;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  gap: 12px;
}

.card-price {
  font-size: 1.3em;
  font-weight: 700;
  color: #e94560;
}


@media (max-width: 768px) {
  .card {
    border-radius: 10px;
  }

  .card-image-container {
    height: 200px;
  }

  .card-title {
    font-size: 1em;
  }

  .card-price {
    font-size: 1.1em;
  }

}
</style>