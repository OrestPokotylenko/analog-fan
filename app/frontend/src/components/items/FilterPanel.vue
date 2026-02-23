<script setup>
import { ref } from 'vue';
import { CONDITIONS } from '../../composables/useItemFilters';

const props = defineProps({
  minPrice:            { type: String,  default: '' },
  maxPrice:            { type: String,  default: '' },
  selectedConditions:  { type: Array,   default: () => [] },
  selectedTypes:       { type: Array,   default: () => [] },
  sortOrder:           { type: String,  default: '' },
  productTypes:        { type: Array,   default: () => [] },
  showTypeFilter:      { type: Boolean, default: false },
  activeFilterCount:   { type: Number,  default: 0 },
});

const emit = defineEmits([
  'update:minPrice', 'update:maxPrice',
  'update:selectedConditions', 'update:selectedTypes',
  'update:sortOrder', 'clear',
]);

const open = ref(false);

function toggleCondition(val) {
  const next = props.selectedConditions.includes(val)
    ? props.selectedConditions.filter(c => c !== val)
    : [...props.selectedConditions, val];
  emit('update:selectedConditions', next);
}

function toggleType(id) {
  const next = props.selectedTypes.includes(id)
    ? props.selectedTypes.filter(t => t !== id)
    : [...props.selectedTypes, id];
  emit('update:selectedTypes', next);
}
</script>

<template>
  <div class="filter-bar">
    <!-- Toggle button -->
    <button class="filter-toggle" @click="open = !open" type="button">
      <span class="filter-icon">⚙</span>
      Filters
      <span v-if="activeFilterCount" class="filter-badge">{{ activeFilterCount }}</span>
      <span class="chevron" :class="{ rotated: open }">▾</span>
    </button>

    <!-- Sort (always visible) -->
    <select
      class="sort-select"
      :value="sortOrder"
      @change="emit('update:sortOrder', $event.target.value)"
    >
      <option value="">Sort: Default</option>
      <option value="price_asc">Price: Low → High</option>
      <option value="price_desc">Price: High → Low</option>
    </select>

    <!-- Clear button -->
    <button
      v-if="activeFilterCount"
      class="btn-clear"
      @click="emit('clear')"
      type="button"
    >
      Clear ({{ activeFilterCount }})
    </button>
  </div>

  <!-- Expanded panel -->
  <Transition name="panel">
    <div v-if="open" class="filter-panel">
      <!-- Price range -->
      <div class="filter-group">
        <p class="filter-group-label">Price (€)</p>
        <div class="price-inputs">
          <input
            class="price-input"
            type="number"
            min="0"
            placeholder="Min"
            :value="minPrice"
            @input="emit('update:minPrice', $event.target.value)"
          />
          <span class="price-dash">–</span>
          <input
            class="price-input"
            type="number"
            min="0"
            placeholder="Max"
            :value="maxPrice"
            @input="emit('update:maxPrice', $event.target.value)"
          />
        </div>
      </div>

      <!-- Condition -->
      <div class="filter-group">
        <p class="filter-group-label">Condition</p>
        <div class="pill-group">
          <button
            v-for="c in CONDITIONS"
            :key="c.value"
            type="button"
            class="pill"
            :class="{ active: selectedConditions.includes(c.value) }"
            @click="toggleCondition(c.value)"
          >
            {{ c.label }}
          </button>
        </div>
      </div>

      <!-- Type (optional) -->
      <div v-if="showTypeFilter && productTypes.length" class="filter-group">
        <p class="filter-group-label">Type</p>
        <div class="pill-group">
          <button
            v-for="t in productTypes"
            :key="t.productTypeId"
            type="button"
            class="pill"
            :class="{ active: selectedTypes.includes(t.productTypeId) }"
            @click="toggleType(t.productTypeId)"
          >
            {{ t.typeName }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* ── Bar ── */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.filter-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(233, 69, 96, 0.1);
  border: 1px solid rgba(233, 69, 96, 0.3);
  color: #e0e0e0;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9em;
  font-weight: 600;
  transition: all 0.2s;
}
.filter-toggle:hover {
  background: rgba(233, 69, 96, 0.2);
  border-color: #e94560;
}

.filter-icon { font-size: 0.95em; }

.filter-badge {
  background: #e94560;
  color: white;
  border-radius: 10px;
  padding: 1px 7px;
  font-size: 0.8em;
  font-weight: 700;
}

.chevron {
  display: inline-block;
  transition: transform 0.25s;
  font-size: 0.9em;
}
.chevron.rotated { transform: rotate(180deg); }

.sort-select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #e0e0e0;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.9em;
  cursor: pointer;
  transition: border-color 0.2s;
}
.sort-select:focus {
  outline: none;
  border-color: #e94560;
}
.sort-select option { background: #16213e; }

.btn-clear {
  background: transparent;
  border: 1px solid rgba(233, 69, 96, 0.4);
  color: #e94560;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85em;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-clear:hover { background: rgba(233, 69, 96, 0.1); }

/* ── Expanded panel ── */
.filter-panel {
  background: rgba(22, 33, 62, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 20px 24px;
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
}

.filter-group { display: flex; flex-direction: column; gap: 10px; }
.filter-group-label {
  margin: 0;
  font-size: 0.78em;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #b0b0b0;
  font-weight: 700;
}

.price-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}
.price-input {
  width: 90px;
  background: rgba(15, 15, 30, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  padding: 7px 10px;
  color: white;
  font-size: 0.9em;
  transition: border-color 0.2s;
}
.price-input:focus { outline: none; border-color: #e94560; }
.price-input::placeholder { color: #555; }
.price-dash { color: #b0b0b0; }

.pill-group { display: flex; flex-wrap: wrap; gap: 8px; }
.pill {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #b0b0b0;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 0.85em;
  cursor: pointer;
  transition: all 0.2s;
}
.pill:hover { border-color: #e94560; color: #e0e0e0; }
.pill.active {
  background: rgba(233, 69, 96, 0.2);
  border-color: #e94560;
  color: #e94560;
  font-weight: 600;
}

/* ── Transition ── */
.panel-enter-active, .panel-leave-active { transition: all 0.25s ease; }
.panel-enter-from, .panel-leave-to { opacity: 0; transform: translateY(-8px); }

@media (max-width: 480px) {
  .filter-panel { padding: 16px; gap: 16px; }
  .price-input { width: 75px; }
}
</style>
