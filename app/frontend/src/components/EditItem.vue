<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../services/axiosConfig';
import TrashBinIcon from '../assets/trash-bin-icon.svg';

const route = useRoute();
const router = useRouter();
const item = ref(null);
const errorMessage = ref('');
const newImages = ref([]);

const itemTypes = ['Cassette', 'Vinyl', 'Player'];

onMounted(async () => {
  await fetchItem();
});

async function fetchItem() {
  try {
    const response = await axios.get(`/items/${route.params.id}`);
    item.value = response.data;
  } catch (error) {
    console.error('Fetch error:', error);
    errorMessage.value = 'Failed to load item.';
  }
}

function handleImageUpload(event) {
  const files = Array.from(event.target.files);
  newImages.value = files;
}

async function updateItem() {
  try {
    const formData = new FormData();
    formData.append('title', String(item.value.title ?? ''));
    formData.append('description', String(item.value.description ?? ''));
    formData.append('price', String(item.value.price ?? 0));
    formData.append('type', String(item.value.type ?? ''));

    if (item.value.imagesPath && item.value.imagesPath.length > 0) {
      item.value.imagesPath.forEach((img) => {
        formData.append('existing_images[]', img);
      });
    }

    if (newImages.value.length > 0) {
      newImages.value.forEach((file) => {
        formData.append('images[]', file);
      });
    }

    const response = await axios.put(
      `/items/${route.params.id}`,
      formData
    );

    if (response.data.success) {
      router.push('/my-items');
    } else {
      errorMessage.value = response.data.error || 'Failed to update item.';
    }
  } catch (error) {
    console.error('Update error:', error.response?.data || error.message);
    errorMessage.value = error.response?.data?.error || 'Failed to update item.';
  }
}

async function deleteImage(index) {
  if (confirm('Are you sure you want to delete this image?')) {
    try {
      await axios.delete(
        `/items/${route.params.id}/images/${index}`
      );
      item.value.imagesPath.splice(index, 1);
    } catch (error) {
      console.error('Delete image error:', error);
      errorMessage.value = 'Failed to delete image.';
    }
  }
}

async function deleteItem() {
  if (!confirm('Delete this item? This cannot be undone.')) return;
  try {
    const resp = await axios.delete(`/items/${route.params.id}`);
    if (resp.data?.success !== false) {
      router.push('/my-items');
    } else {
      errorMessage.value = resp.data.error || 'Failed to delete item.';
    }
  } catch (error) {
    console.error('Delete item error:', error.response?.data || error.message);
    errorMessage.value = error.response?.data?.error || 'Failed to delete item.';
  }
}
</script>

<template>
  <div class="edit-item-form" v-if="item">
    <h2>Edit Item</h2>
    
    <div v-if="errorMessage" class="alert alert-danger mb-3">
      {{ errorMessage }}
    </div>

    <form @submit.prevent="updateItem">
      <div id="itemImagesCarousel" class="carousel slide mb-4" data-bs-ride="carousel"
        v-if="item.imagesPath && item.imagesPath.length">
        <div class="carousel-inner">
          <div v-for="(img, idx) in item.imagesPath" :key="idx" :class="['carousel-item', { active: idx === 0 }]">
            <div class="image-container">
              <img :src="img" class="d-block w-100" :alt="`Image ${idx + 1}`"
                style="max-height: 400px; object-fit: contain;" />
              <button type="button" class="btn-delete-image" @click="deleteImage(idx)" title="Delete image">
                <img :src="TrashBinIcon" class="icon" />
              </button>
            </div>
          </div>
        </div>
        <button v-if="item.imagesPath.length > 1" class="carousel-control-prev" type="button"
          data-bs-target="#itemImagesCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button v-if="item.imagesPath.length > 1" class="carousel-control-next" type="button"
          data-bs-target="#itemImagesCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label">Add New Images (optional)</label>
        <input type="file" class="form-control" @change="handleImageUpload" multiple accept="image/*" />
        <small class="text-muted">Selected: {{ newImages.length }} file(s)</small>
      </div>

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input v-model="item.title" class="form-control" required />
      </div>
      
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea v-model="item.description" class="form-control" rows="4" required></textarea>
      </div>
      
      <div class="mb-3">
        <label class="form-label">Price (€)</label>
        <input v-model.number="item.price" type="number" step="0.01" min="0" class="form-control" required />
      </div>

      <div class="mb-3">
        <label class="form-label">Type</label>
        <select v-model="item.type" class="form-select" required>
          <option value="" disabled>Select item type</option>
          <option v-for="type in itemTypes" :key="type" :value="type">
            {{ type }}
          </option>
        </select>
      </div>
      
      <div class="actions">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary ms-2" @click="router.push('/my-items')">Cancel</button>
        <button type="button" class="btn btn-danger ms-2" @click="deleteItem">Delete Item</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.carousel-control-prev,
.carousel-control-next {
  width: 50px;
  height: 50px;
  top: 50%;
  transform: translateY(-50%);
  filter: invert(1) grayscale(1) brightness(0.3);
  z-index: 5;
}

.image-container {
  position: relative;
}

.btn-delete-image {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(255, 0, 0, 0.7);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.3s;
  z-index: 10;
}

.btn-delete-image:hover {
  background: rgba(255, 0, 0, 0.9);
}

.icon {
  width: 20px;
  height: 20px;
  filter: invert(1);
}

.actions {
  margin-top: 10px;
}
</style>