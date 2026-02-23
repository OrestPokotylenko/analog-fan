<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../services/axiosConfig';
import { useRouter } from 'vue-router';
import { FormInput } from '../ui';

const title = ref('');
const images = ref();
const imagePreviews = ref([]);
const description = ref('');
const price = ref('');
const quantity = ref('1');
const type = ref('');
const productTypes = ref([]);
const isLoadingTypes = ref(false);
const isSubmitting = ref(false);
const formError = ref('');
const imageError = ref('');

const router = useRouter();

async function fetchProductTypes() {
    try {
        isLoadingTypes.value = true;
        const response = await axios.get('/product-types');
        productTypes.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Failed to load product types', error);
        productTypes.value = [];
    } finally {
        isLoadingTypes.value = false;
    }
}

onMounted(fetchProductTypes);

async function handleSubmit() {
    if (isSubmitting.value) return;
    
    formError.value = '';

    if (!validatePrice()) {
        return;
    }

    if (!validateQuantity()) {
        return;
    }

    try {
        isSubmitting.value = true;
        const formData = new FormData();
        formData.append('title', title.value);
        formData.append('description', description.value);
        formData.append('price', price.value);
        formData.append('type', type.value);
        formData.append('quantity', quantity.value);

        if (images.value && images.value.length > 0) {
            images.value.forEach((image) => {
                formData.append('images[]', image);
            });
        }

        await postItem(formData);
    } finally {
        isSubmitting.value = false;
    }
}

async function postItem(data) {
    try {
        const response = await axios.post('/items', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.status === 200) {
            title.value = '';
            images.value = null;
            imagePreviews.value = [];
            description.value = '';
            price.value = '';
            quantity.value = '1';
            type.value = '';

            router.push('/my-items');
        }
    } catch (error) {
        console.error('Failed to publish item:', error);
        formError.value = 'Failed to publish item. Please try again.';
    }
}

function validateImages(event) {
  const selectedFiles = Array.from(event.target.files);
  const validExtensions = ['image/png', 'image/jpeg'];

  const validFiles = selectedFiles.filter((file) =>
    validExtensions.includes(file.type)
  );

  if (validFiles.length !== selectedFiles.length) {
    imageError.value = 'Some files were skipped. Only PNG and JPG files are allowed.';
    setTimeout(() => { imageError.value = ''; }, 4000);
  }

  images.value = [...(images.value || []), ...validFiles];
  
  // Create previews
  validFiles.forEach((file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreviews.value.push(e.target.result);
    };
    reader.readAsDataURL(file);
  });
}

function removeImage(index) {
  images.value.splice(index, 1);
  imagePreviews.value.splice(index, 1);
}

function validatePrice() {
  const priceValue = price.value.trim();

  if (!priceValue) {
    formError.value = 'Price is required.';
    return false;
  }

  const priceNumber = parseFloat(priceValue);
  if (isNaN(priceNumber) || priceNumber <= 0) {
    formError.value = 'Please enter a valid positive number for the price.';
    return false;
  }

  return true;
}

function validateQuantity() {
  const quantityValue = quantity.value.trim();

  if (!quantityValue) {
    formError.value = 'Quantity is required.';
    return false;
  }

  const quantityNumber = parseInt(quantityValue);
  if (isNaN(quantityNumber) || quantityNumber < 1) {
    formError.value = 'Please enter a valid quantity (at least 1).';
    return false;
  }

  return true;
}
</script>

<template>
    <div class="form-container">
        <form @submit.prevent="handleSubmit" class="upload-form">
            <div class="form-header">
                <h2>Upload New Item</h2>
                <p>Share your vintage audio collection</p>
            </div>

            <div class="form-group">
                <label for="title" class="form-label">Item Title</label>
                <FormInput 
                    id="title"
                    v-model="title" 
                    placeholder="e.g., Vintage Cassette Tape - The Beatles" 
                    required 
                />
            </div>

            <div class="form-group">
                <label for="type" class="form-label">Product Type *</label>
                <select 
                    id="type" 
                    v-model="type" 
                    class="form-select" 
                    :disabled="isLoadingTypes" 
                    required
                >
                    <option value="" disabled>Select item type</option>
                    <option v-for="t in productTypes" :key="t.productTypeId" :value="t.productTypeId">
                        {{ t.typeName }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="price" class="form-label">Price (€)</label>
                <FormInput 
                    id="price"
                    v-model="price" 
                    type="text"
                    placeholder="e.g., 15.99" 
                    required 
                />
            </div>

            <div class="form-group">
                <label for="quantity" class="form-label">Quantity Available</label>
                <FormInput 
                    id="quantity"
                    v-model="quantity" 
                    type="number"
                    min="1"
                    placeholder="e.g., 5" 
                    required 
                />
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea 
                    id="description" 
                    v-model="description" 
                    class="form-textarea"
                    placeholder="Describe the item condition, features, and any notable details..."
                    rows="5"
                ></textarea>
            </div>

            <div class="form-group">
                <label for="images" class="form-label">Images (PNG/JPG)</label>
                <p v-if="imageError" class="field-error">{{ imageError }}</p>
                
                <!-- Image Previews -->
                <div v-if="imagePreviews.length > 0" class="image-previews">
                    <div v-for="(preview, index) in imagePreviews" :key="index" class="preview-item">
                        <img :src="preview" :alt="`Preview ${index + 1}`" class="preview-image" />
                        <button 
                            type="button" 
                            class="btn-remove-preview" 
                            @click="removeImage(index)"
                            title="Remove image"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <div class="file-input-wrapper">
                    <input 
                        id="images" 
                        type="file" 
                        multiple 
                        class="file-input"
                        accept=".png, .jpg, .jpeg" 
                        @change="validateImages" 
                    />
                    <div class="file-input-label">
                        <span class="file-icon">📸</span>
                        <span class="file-text">Click to upload images or drag and drop</span>
                    </div>
                </div>
            </div>

            <p v-if="formError" class="form-error-message">{{ formError }}</p>

            <button 
                type="submit" 
                class="btn-submit"
                :disabled="isSubmitting"
            >
                <span class="btn-icon">{{ isSubmitting ? '⏳' : '📤' }}</span>
                <span>{{ isSubmitting ? 'Publishing...' : 'Publish Item' }}</span>
            </button>
        </form>
    </div>
</template>

<style scoped>
.form-container {
    background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
    border-radius: 12px;
    padding: 40px;
    border: 1px solid #1a1a2e;
}

.upload-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.form-header {
    text-align: center;
    margin-bottom: 20px;
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
    font-size: 0.95em;
}

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
    background: #16213e;
    color: white;
    padding: 10px;
}

.form-select option:hover {
    background: #1f2c4d;
}

.form-select option[disabled] {
    color: #666;
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.form-textarea::placeholder,
.form-select {
    color: #b0b0b0;
}

/* Image Previews */
.image-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 15px;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #1a1a2e;
    transition: all 0.3s;
}

.preview-item:hover {
    border-color: #e94560;
}

.preview-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.btn-remove-preview {
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

.preview-item:hover .btn-remove-preview {
    opacity: 1;
}

.btn-remove-preview:hover {
    background: #ff6b7a;
    transform: scale(1.1);
}

.file-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #e94560;
    border-radius: 8px;
    padding: 40px 20px;
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
    font-size: 2.5em;
}

.file-text {
    color: #b0b0b0;
    font-size: 0.95em;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 40px;
    border: none;
    border-radius: 10px;
    font-size: 1.1em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
}

.btn-submit:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

.btn-submit:active {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.btn-icon {
    font-size: 1.3rem;
    animation: float 3s ease-in-out infinite;
}

.form-error-message {
    background: rgba(233, 69, 96, 0.1);
    border: 1px solid rgba(233, 69, 96, 0.4);
    color: #ff6b7a;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.9em;
    margin: 0;
}

.field-error {
    color: #ff6b7a;
    font-size: 0.85em;
    margin: 4px 0 0 0;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

@media (max-width: 768px) {
    .form-container {
        padding: 30px 20px;
    }

    .form-header h2 {
        font-size: 1.6em;
    }

    .form-group {
        gap: 6px;
    }

    .file-input-wrapper {
        padding: 30px 15px;
    }

    .file-icon {
        font-size: 2em;
    }
}

@media (max-width: 480px) {
    .form-container {
        padding: 20px 15px;
    }

    .form-header h2 {
        font-size: 1.4em;
    }

    .form-header p {
        font-size: 0.85em;
    }

    .form-label {
        font-size: 0.85em;
    }
}
</style>