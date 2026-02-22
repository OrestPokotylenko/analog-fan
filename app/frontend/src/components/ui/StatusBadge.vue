<template>
  <span :class="['status-badge', `status-${type}`, `size-${size}`]">
    <!-- <span v-if="showIcon" class="status-icon">{{ iconText }}</span> -->
    <span class="status-text">{{ label }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'default', // default, success, error, warning, info, processing, pending
    validator: (value) => [
      'default', 'success', 'error', 'warning', 'info', 
      'processing', 'pending', 'shipped', 'delivered', 'cancelled'
    ].includes(value)
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  showIcon: {
    type: Boolean,
    default: true
  }
});
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 20px;
  font-weight: 600;
  white-space: nowrap;
}

/* Sizes */
.size-small {
  padding: 4px 12px;
  font-size: 0.75rem;
}

.size-medium {
  padding: 6px 16px;
  font-size: 0.875rem;
}

.size-large {
  padding: 8px 20px;
  font-size: 1rem;
}

.status-icon {
  font-size: 1em;
  line-height: 1;
}

.status-text {
  line-height: 1;
}

/* Type variants */
.status-default {
  background: rgba(255, 255, 255, 0.1);
  color: #e0e0e0;
}

.status-success {
  background: rgba(16, 185, 129, 0.15);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-delivered {
  background: rgba(16, 185, 129, 0.15);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-error {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-cancelled {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-warning {
  background: rgba(251, 191, 36, 0.15);
  color: #fcd34d;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-pending {
  background: rgba(251, 191, 36, 0.15);
  color: #fcd34d;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-info {
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-processing {
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-shipped {
  background: rgba(139, 92, 246, 0.15);
  color: #c4b5fd;
  border: 1px solid rgba(139, 92, 246, 0.3);
}
</style>
