<script setup>
defineProps({
  users: { type: Array, required: true },
  isDeleting: { type: Boolean, default: false }
});
defineEmits(['delete']);

function formatDate(d) {
  if (!d) return 'N/A';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.userId">
          <td data-label="ID">{{ user.userId }}</td>
          <td data-label="Username">{{ user.username }}</td>
          <td data-label="Email">{{ user.email }}</td>
          <td data-label="Role">
            <span :class="['badge', user.role === 'admin' ? 'badge-admin' : 'badge-user']">{{ user.role }}</span>
          </td>
          <td data-label="Created">{{ formatDate(user.createdAt) }}</td>
          <td data-label="Actions">
            <button v-if="user.role !== 'admin'" class="btn-action btn-delete" :disabled="isDeleting" @click="$emit('delete', user.userId)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style src="./admin-table.css" />