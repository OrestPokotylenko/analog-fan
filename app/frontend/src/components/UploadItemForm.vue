<script setup>
import { ref, onMounted } from 'vue';
import axios from '../services/axiosConfig';
import { useRouter } from 'vue-router';
import TextInput from './reusable/TextInput.vue';

const title = ref('');
const images = ref();
const description = ref('');
const price = ref('');
const type = ref('');
const productTypes = ref([]);
const isLoadingTypes = ref(false);

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
    submitButton.disabled = true;

    if (!validatePrice()) {
    submitButton.disabled = false;
    return;
  }

  const formData = new FormData();
  formData.append('title', title.value);
  formData.append('description', description.value);
  formData.append('price', price.value);
  formData.append('type', type.value);

  images.value.forEach((image) => {
    formData.append('images[]', image);
  });

  await postItem(formData);
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
            description.value = '';
            price.value = '';
            type.value = '';
            submitButton.disabled = false;

            router.push('/your-items');
        }
    } catch (error) {
        alert('An error occurred while publishing the item. Please try again.');
    }
}

function validateImages(event) {
  const selectedFiles = Array.from(event.target.files);
  const validExtensions = ['image/png', 'image/jpeg'];

  const validFiles = selectedFiles.filter((file) =>
    validExtensions.includes(file.type)
  );

  if (validFiles.length !== selectedFiles.length) {
    alert('Only PNG and JPG files are allowed.');
  }

  images.value = [...(images.value || []), ...validFiles];
}

function validatePrice() {
  const priceValue = price.value.trim();

  if (!priceValue) {
    alert('Price is required.');
    return false;
  }

  const priceNumber = parseFloat(priceValue);
  if (isNaN(priceNumber) || priceNumber <= 0) {
    alert('Please enter a valid positive number for the price.');
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
                <TextInput 
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
                        {{ t.name }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="price" class="form-label">Price (€)</label>
                <TextInput 
                    id="price"
                    v-model="price" 
                    type="text"
                    placeholder="e.g., 15.99" 
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
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label for="images" class="form-label">Images (PNG/JPG)</label>
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

            <button id="submitButton" type="submit" class="btn-submit">Publish Item</button>
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
    background: #1a1a2e;
    color: white;
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.form-textarea::placeholder,
.form-select {
    color: #b0b0b0;
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
    background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
    color: white;
    padding: 14px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
}

.btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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