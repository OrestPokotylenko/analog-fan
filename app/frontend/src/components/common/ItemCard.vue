<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import likeFilled from '../../assets/like-filled.svg';
import likeUnfilled from '../../assets/like-unfilled.svg';
import axios from '../../services/axiosConfig';
import BaseCard from '../ui/BaseCard.vue';
import LoginPrompt from './LoginPrompt.vue';

const router = useRouter();

const props = defineProps({
  item: { type: Object, required: true },
  likedItems: { type: Array, required: true }
});

const isLiked = computed(() => props.likedItems.includes(props.item.itemId));
const showLoginPrompt = ref(false);

async function likeItem() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || !user.userId) { showLoginPrompt.value = true; return; }
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
  try { await axios.post('/liked-items', { itemId: props.item.itemId, userId }); }
  catch (e) { console.error('Failed to create liked item:', e); }
}

async function deleteLikedItem(userId) {
  try { await axios.delete('/liked-items', { data: { itemId: props.item.itemId, userId } }); }
  catch (e) { console.error('Failed to delete liked item:', e); }
}
</script>

<template>
  <div class="card-outer">
    <BaseCard
      :link-to="`/item/${item.itemId}`"
      :image-src="item.imagesPath?.[0] || ''"
      :image-alt="item.title"
      :title="item.title"
      :price="item.price"
      image-height="auto"
    >
      <!-- Like button in the image overlay -->
      <template #overlay>
        <button class="like-btn" @click.prevent.stop="likeItem">
          <img :src="isLiked ? likeFilled : likeUnfilled" class="like-icon" />
        </button>
      </template>

      <!-- Type label under the title -->
      <template #subtitle>
        <p class="card-type">{{ item.type }}</p>
      </template>
    </BaseCard>

    <LoginPrompt
      :show="showLoginPrompt"
      @close="showLoginPrompt = false"
      @login="router.push('/login')"
    />
  </div>
</template>

<style scoped>
.card-outer {
  min-width: 0;
}

.like-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(233, 69, 96, 0.9);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
  z-index: 10;
}
.like-btn:hover { background: #e94560; transform: scale(1.1); }

.like-icon {
  width: 18px;
  height: 18px;
  filter: brightness(0) invert(1);
}

.card-type {
  font-size: 0.75em;
  color: #e94560;
  font-weight: 700;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>