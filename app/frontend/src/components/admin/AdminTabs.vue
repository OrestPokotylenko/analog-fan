<script setup>
defineProps({
  tabs: { type: Array, required: true },
  activeTab: { type: String, required: true }
});
defineEmits(['update:activeTab']);
</script>

<template>
  <div class="tabs-wrapper">
    <div class="tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="['tab', { active: activeTab === tab.key }]"
        @click="$emit('update:activeTab', tab.key)"
      >
        {{ tab.label }}
        <span v-if="tab.count != null" class="tab-count"> ({{ tab.count }})</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.tabs-wrapper {
  overflow-x: auto;
  margin-bottom: 24px;
  scrollbar-width: none;
}
.tabs-wrapper::-webkit-scrollbar { display: none; }

.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 2px solid rgba(255,255,255,0.1);
  min-width: max-content;
}

.tab {
  padding: 12px 18px;
  background: transparent;
  color: #a0a0a0;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
  transition: color 0.2s;
}
.tab:hover { color: white; }
.tab.active { color: white; border-bottom-color: #667eea; }
.tab-count { opacity: 0.7; font-weight: 400; }
</style>