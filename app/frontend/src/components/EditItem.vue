<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../services/axiosConfig';

const route = useRoute();
const router = useRouter();
const item = ref(null);
const errorMessage = ref('');
const newImages = ref([]);
const productTypes = ref([]);
const isLoadingTypes = ref(false);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmMessage = ref('');
const confirmTitle = ref('');

onMounted(async () => {
  await Promise.all([fetchItem(), fetchProductTypes()]);
});

async function fetchItem() {
  try {
    const response = await axios.get(`/items/${route.params.id}`);
    item.value = response.data;
    // Ensure price is formatted to 2 decimal places
    if (item.value && item.value.price !== undefined) {
      item.value.price = parseFloat(item.value.price).toFixed(2);
    }
  } catch (error) {
    errorMessage.value = 'Failed to load item.';
  }
}

async function fetchProductTypes() {
  try {
    isLoadingTypes.value = true;
    const response = await axios.get('/product-types');
    productTypes.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    productTypes.value = [];
  } finally {
    isLoadingTypes.value = false;
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
    formData.append('type', String(item.value.productTypeId ?? ''));

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
    errorMessage.value = error.response?.data?.error || 'Failed to update item.';
  }
}

async function deleteImage(index) {
  confirmTitle.value = 'Delete Image';
  confirmMessage.value = 'Are you sure you want to delete this image? This action cannot be undone.';
  confirmAction.value = async () => {
    try {
      await axios.delete(
        `/items/${route.params.id}/images/${index}`
      );
      item.value.imagesPath.splice(index, 1);
    } catch (error) {
      errorMessage.value = 'Failed to delete image.';
    }
  };
  showConfirmModal.value = true;
}

async function deleteItem() {
  confirmTitle.value = 'Delete Item';
  confirmMessage.value = 'Are you sure you want to delete this item? This action cannot be undone.';
  confirmAction.value = async () => {
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
  };
  showConfirmModal.value = true;
}

async function confirmDelete() {
  if (confirmAction.value) {
    await confirmAction.value();
  }
  closeConfirmModal();
}

function closeConfirmModal() {
  showConfirmModal.value = false;
  confirmAction.value = null;
  confirmMessage.value = '';
  confirmTitle.value = '';
}
</script>

<template>
  <div class="edit-container" v-if="item">
    <form @submit.prevent="updateItem" class="edit-form">
      <div class="form-header">
        <h2>Edit Item</h2>
        <p>Update your product information</p>
      </div>

      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <!-- Image Gallery -->
      <div v-if="item.imagesPath && item.imagesPath.length" class="images-section">
        <h3>Current Images</h3>
        <div class="images-gallery">
          <div v-for="(img, idx) in item.imagesPath" :key="idx" class="image-item">
            <img :src="img" :alt="`Image ${idx + 1}`" class="gallery-image" />
            <button 
              type="button" 
              class="btn-delete-image" 
              @click="deleteImage(idx)"
              title="Delete image"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- New Images Upload -->
      <div class="form-group">
        <label class="form-label">Add New Images (optional)</label>
        <div class="file-input-wrapper">
          <input 
            type="file" 
            class="file-input"
            @change="handleImageUpload" 
            multiple 
            accept="image/*" 
          />
          <div class="file-input-label">
            <span class="file-icon">📸</span>
            <span class="file-text">Click to upload or drag and drop</span>
          </div>
        </div>
        <p v-if="newImages.length" class="file-count">{{ newImages.length }} file(s) selected</p>
      </div>

      <!-- Title -->
      <div class="form-group">
        <label class="form-label">Item Title</label>
        <input v-model="item.title" class="form-input" required />
      </div>

      <!-- Description -->
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea v-model="item.description" class="form-textarea" rows="5" required></textarea>
      </div>

      <!-- Price -->
      <div class="form-group">
        <label class="form-label">Price (€)</label>
        <input v-model="item.price" type="text" step="0.01" min="0" class="form-input" required />
      </div>

      <!-- Type -->
      <div class="form-group">
        <label class="form-label">Product Type</label>
        <select v-model="item.productTypeId" class="form-select" :disabled="isLoadingTypes" required>
          <option value="" disabled>Select item type</option>
          <option v-for="type in productTypes" :key="type.productTypeId" :value="type.productTypeId">
            {{ type.name }}
          </option>
        </select>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary" @click="router.push('/my-items')">Cancel</button>
        <button type="button" class="btn btn-danger" @click="deleteItem">Delete Item</button>
      </div>
    </form>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmModal" class="modal-overlay" @click="closeConfirmModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <div class="warning-icon">⚠️</div>
          <h3>{{ confirmTitle }}</h3>
        </div>
        <div class="modal-body">
          <p>{{ confirmMessage }}</p>
        </div>
        <div class="modal-actions">
          <button @click="confirmDelete" class="btn-confirm">Confirm</button>
          <button @click="closeConfirmModal" class="btn-cancel">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.edit-container {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border-radius: 12px;
  padding: 40px;
  border: 1px solid #1a1a2e;
  max-width: 800px;
  margin: 0 auto;
}

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.form-header {
  text-align: center;
  margin-bottom: 10px;
}

.form-header h2 {
  font-size: 2em;
  color: white;
  font-weight: 800;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.form-header p {
  color: #b0b0b0;
  margin: 0;
}

.error-banner {
  background: linear-gradient(135deg, rgba(255, 107, 122, 0.2), rgba(255, 107, 122, 0.1));
  border: 1px solid #ff6b7a;
  border-radius: 8px;
  padding: 15px;
  color: #ff9ca5;
  font-size: 0.95em;
}

/* Images Section */
.images-section {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.images-section h3 {
  font-size: 1.1em;
  color: white;
  font-weight: 700;
  margin: 0;
}

.images-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
}

.image-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #1a1a2e;
  transition: all 0.3s;
}

.image-item:hover {
  border-color: #e94560;
}

.gallery-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.btn-delete-image {
  position: absolute;
  top: 5px;
  right: 5px;
  background: #e94560;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  font-weight: bold;
  font-size: 1.2em;
  transition: all 0.3s;
  opacity: 0;
}

.image-item:hover .btn-delete-image {
  opacity: 1;
}

.btn-delete-image:hover {
  background: #ff6b7a;
  transform: scale(1.1);
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 0.9em;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #e0e0e0;
  font-weight: 700;
}

.form-input,
.form-select,
.form-textarea {
  background: linear-gradient(135deg, #0f0f1e 0%, #16213e 100%);
  border: 2px solid #1a1a2e;
  border-radius: 8px;
  padding: 12px 15px;
  color: white;
  font-size: 0.95em;
  font-family: inherit;
  transition: all 0.3s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #e94560;
  box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
}

.form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select option {
  background: #1a1a2e;
  color: white;
}

.form-textarea {
  resize: vertical;
  min-height: 140px;
}

.file-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed #e94560;
  border-radius: 8px;
  padding: 30px 20px;
  background: rgba(233, 69, 96, 0.05);
  cursor: pointer;
  transition: all 0.3s;
}

.file-input-wrapper:hover {
  border-color: #ff6b7a;
  background: rgba(233, 69, 96, 0.1);
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.file-input-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
  pointer-events: none;
}

.file-icon {
  font-size: 2.2em;
}

.file-text {
  color: #b0b0b0;
  font-size: 0.9em;
}

.file-count {
  font-size: 0.85em;
  color: #b0b0b0;
  margin: 8px 0 0 0;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 15px;
}

.btn {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 0.95em;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s;
  min-width: 120px;
}

.btn-primary {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
}

.btn-secondary {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  color: white;
  border: 2px solid #e94560;
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  transform: translateY(-3px);
}

.btn-danger {
  background: linear-gradient(135deg, rgba(255, 107, 122, 0.3), rgba(233, 69, 96, 0.3));
  color: #ff9ca5;
  border: 2px solid #ff6b7a;
}

.btn-danger:hover {
  background: linear-gradient(135deg, rgba(255, 107, 122, 0.4), rgba(233, 69, 96, 0.4));
  transform: translateY(-3px);
}

@media (max-width: 768px) {
  .edit-container {
    padding: 30px 20px;
  }

  .form-header h2 {
    font-size: 1.6em;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    min-width: auto;
  }

  .images-gallery {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
}

@media (max-width: 480px) {
  .edit-container {
    padding: 20px 15px;
  }

  .form-header h2 {
    font-size: 1.4em;
  }

  .file-input-wrapper {
    padding: 20px 15px;
  }

  .file-icon {
    font-size: 1.8em;
  }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease-out;
}

.modal-content {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border: 2px solid #e94560;
  border-radius: 12px;
  padding: 30px;
  max-width: 450px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: slideUp 0.3s ease-out;
}

.modal-header {
  text-align: center;
  margin-bottom: 20px;
}

.warning-icon {
  font-size: 3em;
  animation: pulse 1.5s ease-in-out infinite;
}

.modal-header h3 {
  color: white;
  font-size: 1.5em;
  font-weight: 700;
  margin: 10px 0 0 0;
}

.modal-body {
  margin-bottom: 25px;
}

.modal-body p {
  color: #b0b0b0;
  font-size: 1em;
  line-height: 1.5;
  margin: 0;
  text-align: center;
}

.modal-actions {
  display: flex;
  gap: 10px;
}

.btn-confirm {
  flex: 1;
  padding: 12px 24px;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.95em;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
}

.btn-cancel {
  flex: 1;
  padding: 12px 24px;
  background: transparent;
  color: white;
  border: 2px solid #e94560;
  border-radius: 8px;
  font-size: 0.95em;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel:hover {
  background: rgba(233, 69, 96, 0.1);
  transform: translateY(-2px);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(30px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}
</style>