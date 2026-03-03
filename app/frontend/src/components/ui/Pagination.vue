<script setup>
import { computed } from 'vue';

const props = defineProps({
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  total: { type: Number, default: 0 },
  limit: { type: Number, default: 20 },
  /** Maximum number of page buttons visible at once */
  maxVisible: { type: Number, default: 5 },
});

const emit = defineEmits(['update:page']);

const pages = computed(() => {
  const { page, totalPages, maxVisible } = props;
  if (totalPages <= maxVisible) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }
  const half = Math.floor(maxVisible / 2);
  let start = Math.max(1, page - half);
  let end = start + maxVisible - 1;
  if (end > totalPages) {
    end = totalPages;
    start = Math.max(1, end - maxVisible + 1);
  }
  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const showStartEllipsis = computed(() => pages.value[0] > 1);
const showEndEllipsis = computed(() => pages.value[pages.value.length - 1] < props.totalPages);

function goTo(p) {
  if (p >= 1 && p <= props.totalPages && p !== props.page) {
    emit('update:page', p);
  }
}
</script>

<template>
  <nav v-if="totalPages > 1" class="pagination" aria-label="Pagination">
    <button
      class="pagination-btn"
      :disabled="page <= 1"
      @click="goTo(page - 1)"
      aria-label="Previous page"
    >
      ‹
    </button>

    <template v-if="showStartEllipsis">
      <button class="pagination-btn" @click="goTo(1)">1</button>
      <span class="pagination-ellipsis">…</span>
    </template>

    <button
      v-for="p in pages"
      :key="p"
      class="pagination-btn"
      :class="{ active: p === page }"
      @click="goTo(p)"
    >
      {{ p }}
    </button>

    <template v-if="showEndEllipsis">
      <span class="pagination-ellipsis">…</span>
      <button class="pagination-btn" @click="goTo(totalPages)">{{ totalPages }}</button>
    </template>

    <button
      class="pagination-btn"
      :disabled="page >= totalPages"
      @click="goTo(page + 1)"
      aria-label="Next page"
    >
      ›
    </button>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 40px;
  flex-wrap: wrap;
}

.pagination-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 38px;
  height: 38px;
  padding: 0 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.04);
  color: #b0b0b0;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled):not(.active) {
  background: rgba(233, 69, 96, 0.15);
  border-color: rgba(233, 69, 96, 0.4);
  color: #ff6b7a;
}

.pagination-btn.active {
  background: #e94560;
  border-color: #e94560;
  color: white;
  box-shadow: 0 4px 12px rgba(233, 69, 96, 0.35);
}

.pagination-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.pagination-ellipsis {
  color: #666;
  padding: 0 4px;
  font-size: 0.9rem;
  user-select: none;
}
</style>
