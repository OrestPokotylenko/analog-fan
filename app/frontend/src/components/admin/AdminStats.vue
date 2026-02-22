<script setup>
defineProps({
  stats: { type: Object, required: true }
});
defineEmits(['navigate']);

</script>

<template>
  <div class="stats-grid">
    <div class="stat-card" @click="$emit('navigate', 'users')">
      <div class="stat-icon">👥</div>
      <div class="stat-info"><h3>Users</h3><p class="stat-number">{{ stats.totalUsers }}</p></div>
    </div>
    <div class="stat-card" @click="$emit('navigate', 'items')">
      <div class="stat-icon">📦</div>
      <div class="stat-info"><h3>Items</h3><p class="stat-number">{{ stats.totalItems }}</p></div>
    </div>
    <div class="stat-card" @click="$emit('navigate', 'orders')">
      <div class="stat-icon">🛒</div>
      <div class="stat-info"><h3>Orders</h3><p class="stat-number">{{ stats.totalOrders }}</p></div>
    </div>
    <div class="stat-card" @click="$emit('navigate', 'types')">
      <div class="stat-icon">🏷️</div>
      <div class="stat-info"><h3>Types</h3><p class="stat-number">{{ stats.totalProductTypes }}</p></div>
    </div>
    <div class="stat-card" @click="$emit('navigate', 'shipments')">
      <div class="stat-icon">🚚</div>
      <div class="stat-info"><h3>Shipments</h3><p class="stat-number">{{ stats.totalShipments }}</p></div>
    </div>
  </div>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.stat-card {
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.stat-card:hover { background: rgba(255,255,255,0.09); transform: translateY(-3px); }
.stat-icon { font-size: 2.5rem; flex-shrink: 0; }
.stat-info h3 { margin: 0 0 6px; font-size: 0.9rem; color: #a0a0a0; }
.stat-number {
  margin: 0; font-size: 2rem; font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

@media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
@media (max-width: 700px) {
  .stat-card { padding: 16px; gap: 12px; }
  .stat-icon { font-size: 1.8rem; }
  .stat-number { font-size: 1.6rem; }
}
</style>