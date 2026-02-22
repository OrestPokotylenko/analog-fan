<script setup>
defineProps({
  linkTo: {
    type: String,
    required: true
  },
  imageSrc: {
    type: String,
    default: ''
  },
  imageAlt: {
    type: String,
    default: ''
  },
  placeholderText: {
    type: String,
    default: 'No Image'
  },
  title: {
    type: String,
    required: true
  },
  price: {
    type: Number,
    default: null
  }
});
</script>

<template>
  <RouterLink :to="linkTo" class="card-link">
    <div class="card">
      <div class="card-image-container">
        <img
          v-if="imageSrc"
          :src="imageSrc"
          class="card-image"
          :alt="imageAlt"
        />
        <div v-else class="card-image-placeholder">{{ placeholderText }}</div>
        <slot name="overlay"></slot>
      </div>

      <div class="card-body">
        <slot name="header"></slot>
        <h3 class="card-title">{{ title }}</h3>
        <slot name="subtitle"></slot>
        <slot name="content"></slot>
        <div class="card-footer">
          <span v-if="price !== null" class="card-price">€{{ price.toFixed(2) }}</span>
          <slot name="footer"></slot>
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
  width: 100%;
}

.card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(233, 69, 96, 0.1);
  width: 100%;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 30px rgba(233, 69, 96, 0.2);
  border-color: rgba(233, 69, 96, 0.3);
}

.card-image-container {
  position: relative;
  width: 100%;
  aspect-ratio: 4 / 3;
  background: #0f0f1e;
  overflow: hidden;
  flex-shrink: 0;
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
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
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.card-title {
  font-size: 1.05em;
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

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  margin-top: 4px;
  gap: 12px;
}

.card-price {
  font-size: 1.3em;
  font-weight: 700;
  color: #e94560;
}

@media (max-width: 768px) {
  .card { border-radius: 10px; }
  .card-title { font-size: 1em; }
  .card-price { font-size: 1.2em; }
}
</style>