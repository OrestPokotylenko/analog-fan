<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import TextInput from './reusable/TextInput.vue';

const title = ref('');
const images = ref();
const description = ref('');
const price = ref('');
const type = ref('');
const types = ['Cassette', 'Vinyl', 'Player'];

const router = useRouter();

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
        const response = await axios.post('http://localhost/api/items', data, {
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
    <div class="container">
        <form @submit.prevent="handleSubmit" class="item-form">
            <div class="form-group">
                <TextInput v-model="title" label="Title" placeholder="Enter item title" required />
            </div>

            <div class="form-group">
                <label for="images">Images</label>
                <input id="images" type="file" multiple class="form-control" accept=".png, .jpg, .jpeg" @change="validateImages" />
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" v-model="description" class="form-control"
                    placeholder="Enter item description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <TextInput v-model="price" label="Price (€)" placeholder="Enter item price" type="text" required />
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" v-model="type" class="form-control" required>
                    <option value="" disabled>Select item type</option>
                    <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <button id="submitButton" type="submit" class="btn btn-primary">Publish Item</button>
        </form>
    </div>
</template>

<style scoped>
.container {
    height: auto;
    border: 1px solid #ccc;
    border-radius: 10px;
    padding: 50px;
}

.item-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-control {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
</style>