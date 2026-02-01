<script setup>
import { computed } from 'vue';
import likeFilled from '../assets/like-filled.svg';
import likeUnfilled from '../assets/like-unfilled.svg';
import axios from '../services/axiosConfig';

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

const isLiked = computed(() => props.likedItems.includes(props.item.itemId));

async function likeItem() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) {
    console.error('User not found in localStorage or userId is missing');
    return;
  }

  if (!isLiked.value) {
    await createLikedItem(user.userId);
    props.likedItems.push(props.item.itemId);
  } else {
    await deleteLikedItem(user.userId);
    const index = props.likedItems.indexOf(props.item.itemId);
    if (index > -1) props.likedItems.splice(index, 1);
  }
}

async function createLikedItem(userId) {
  try {
    await axios.post('/liked-items', {
      itemId: props.item.itemId,
      userId: userId
    });
  } catch (error) {
    console.error('Failed to create liked item:', error);
  }
}

async function deleteLikedItem(userId) {
  try {
    await axios.delete('/liked-items', {
      data: {
        itemId: props.item.itemId,
        userId: userId
      }
    });
  } catch (error) {
    console.error('Failed to delete liked item:', error);
  }
}
</script>

<template>
  <RouterLink :to="`/item/${item.itemId}`" class="card-link">
    <div class="card">
      <div class="card-image-container">
      <img
        v-if="item.imagesPath?.[0]"
        :src="item.imagesPath[0]"
        class="card-image"
        :alt="item.title"
      />
      <div v-else class="card-image-placeholder">No Image</div>
      <button class="like-btn" @click.prevent.stop="likeItem">
        <img :src="isLiked ? likeFilled : likeUnfilled" class="like-icon" />
      </button>
      </div>

      <div class="card-body">
        <h3 class="card-title">{{ item.title }}</h3>
        
        <p class="card-type">{{ item.type }}</p>
        <p class="card-description">{{ item.description }}</p>
        
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
  height: 280px;
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


.like-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(233, 69, 96, 0.9);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  backdrop-filter: blur(4px);
  z-index: 10;
}

.like-btn:hover {
  background: #e94560;
  transform: scale(1.1);
}

.like-icon {
  width: 20px;
  height: 20px;
  filter: brightness(0) invert(1);
}

.card-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
  gap: 12px;
}

.card-title {
  font-size: 1.2em;
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

.card-type {
  font-size: 0.8em;
  color: #e94560;
  font-weight: 700;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card-description {
  font-size: 0.9em;
  color: #c0c0c0;
  margin: 0;
  flex: 1;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid rgba(233, 69, 96, 0.1);
  gap: 12px;
}

.card-price {
  font-size: 1.5em;
  font-weight: 700;
  color: #e94560;
}


@media (max-width: 768px) {
  .card {
    border-radius: 10px;
  }

  .card-image-container {
    height: 220px;
  }

  .card-title {
    font-size: 1.05em;
  }

  .card-description {
    font-size: 0.85em;
    -webkit-line-clamp: 2;
    line-clamp: 2;
  }

  .card-price {
    font-size: 1.3em;
  }

}
</style>