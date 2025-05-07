<script setup>
import { computed } from 'vue';
import likeFilled from '../assets/like-filled.svg';
import likeUnfilled from '../assets/like-unfilled.svg';
import axios from 'axios';

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
    await axios.post('http://localhost/api/liked-items', {
      itemId: props.item.itemId,
      userId: userId
    });
  } catch (error) {
    console.error('Failed to create liked item:', error);
  }
}

async function deleteLikedItem(userId) {
  try {
    await axios.delete('http://localhost/api/liked-items', {
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
  <RouterLink :to="`/item/${item.itemId}`">
    <div class="card d-flex flex-column justify-content-between">
      <img :src="item.imagesPath[0]" class="item-image" :alt="item.title" />
      <div class="card-body">
        <p>{{ item.title }}</p>
        <div class="d-flex justify-content-between align-items-center">
          <strong>€{{ item.price }}</strong>
          <button class="like-button" @click.stop.prevent="likeItem">
            <img :src="isLiked ? likeFilled : likeUnfilled" class="like-img" />
          </button>
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

.like-button {
  background: white;
  padding: 0;
}

.like-img {
  width: 30px;
  height: 30px;
}

a {
  display: block;
  width: 300px;
  text-decoration: none;
  color: inherit;
}
</style>