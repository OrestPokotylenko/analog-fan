<script setup>
defineProps({
  items: { type: Array, required: true },
  isDeleting: { type: Boolean, default: false }
});
defineEmits(['delete']);
</script>

<template>
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr><th>ID</th><th>Title</th><th>Price</th><th>Type</th><th>Owner</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.itemId">
          <td data-label="ID">{{ item.itemId }}</td>
          <td data-label="Title">{{ item.title }}</td>
          <td data-label="Price">€{{ item.price.toFixed(2) }}</td>
          <td data-label="Type">{{ item.type }}</td>
          <td data-label="Owner">User #{{ item.userId }}</td>
          <td data-label="Actions">
            <button class="btn-action btn-delete" :disabled="isDeleting" @click="$emit('delete', item.itemId)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style src="./admin-table.css" />