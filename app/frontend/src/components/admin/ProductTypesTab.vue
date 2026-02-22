<script setup>
defineProps({
  productTypes: { type: Array, required: true },
  isDeleting: { type: Boolean, default: false },
  isSaving: { type: Boolean, default: false }
});
defineEmits(['add', 'edit', 'delete']);
</script>

<template>
  <div>
    <div class="action-bar">
      <button class="btn-primary" @click="$emit('add')">+ Add Product Type</button>
    </div>
    <div class="table-container">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>Image</th><th>Name</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <tr v-for="type in productTypes" :key="type.productTypeId">
            <td data-label="ID">{{ type.productTypeId }}</td>
            <td data-label="Image">
              <img v-if="type.imageUrl" :src="type.imageUrl" alt="type" class="table-thumb" />
              <span v-else class="no-image">No image</span>
            </td>
            <td data-label="Name">{{ type.typeName }}</td>
            <td data-label="Actions" class="actions-cell">
              <button class="btn-action btn-edit" :disabled="isSaving || isDeleting" @click="$emit('edit', type)">Edit</button>
              <button class="btn-action btn-delete" :disabled="isDeleting" @click="$emit('delete', type.productTypeId)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
@import './admin-table.css';

.action-bar { display: flex; justify-content: flex-end; margin-bottom: 16px; }
.btn-primary {
  padding: 10px 20px; background: #667eea; color: white;
  border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s;
}
.btn-primary:hover { background: #5568d3; }
</style>