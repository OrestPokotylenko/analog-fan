<script setup>
defineProps({
  show: { type: Boolean, required: true },
  title: { type: String, required: true },
  maxWidth: { type: String, default: '500px' }
});
defineEmits(['close']);
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click="$emit('close')">
      <div class="modal-content" :style="{ maxWidth }" @click.stop>
        <div class="modal-header">
          <h2>{{ title }}</h2>
          <button class="btn-close" @click="$emit('close')">✕</button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
        <div class="modal-footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.72);
  display: flex; align-items: center; justify-content: center;
  z-index: 2000; padding: 16px;
}
.modal-content {
  background: #1a1a2e;
  border-radius: 12px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.modal-header h2 { margin: 0; font-size: 1.3rem; color: white; }
.btn-close {
  background: none; border: none; color: white;
  font-size: 1.3rem; cursor: pointer; padding: 4px 8px;
  border-radius: 4px; transition: background 0.2s;
}
.btn-close:hover { background: rgba(255,255,255,0.1); }
.modal-body { padding: 20px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid rgba(255,255,255,0.1);
}
</style>