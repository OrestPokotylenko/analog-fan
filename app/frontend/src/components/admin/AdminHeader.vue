<script setup>
defineProps({ title: { type: String, default: '🛡️ Admin Dashboard' } });
defineEmits(['logout']);
</script>

<template>
  <div class="admin-header">
    <h1>{{ title }}</h1>
    <button class="btn-logout" @click="$emit('logout')">Logout</button>
  </div>
</template>

<style scoped>
.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
  margin-bottom: 24px;
}
.admin-header h1 { margin: 0; font-size: 1.8rem; color: white; }

.btn-logout {
  padding: 9px 18px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  white-space: nowrap;
  transition: background 0.2s;
}
.btn-logout:hover { background: #ff6b7a; }

@media (max-width: 600px) {
  .admin-header h1 { font-size: 1.3rem; }
}
</style>