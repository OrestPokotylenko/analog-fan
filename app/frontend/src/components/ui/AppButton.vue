<template>
  <button 
    :type="type"
    :class="['app-button', `variant-${variant}`, `size-${size}`, { 'is-loading': loading, 'is-disabled': disabled }]"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="button-spinner"></span>
    <span class="button-content" :class="{ 'button-loading': loading }">
      <slot></slot>
    </span>
  </button>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value)
  },
  variant: {
    type: String,
    default: 'primary', // primary, secondary, danger, ghost, outline
    validator: (value) => ['primary', 'secondary', 'danger', 'ghost', 'outline'].includes(value)
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['click']);
</script>

<style scoped>
.app-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: inherit;
  font-weight: 700;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  letter-spacing: 0.5px;
  text-transform: none;
}

.app-button:hover:not(.is-disabled):not(.is-loading) {
  transform: translateY(-2px);
}

.app-button:active:not(.is-disabled):not(.is-loading) {
  transform: translateY(0);
}

.app-button.is-disabled,
.app-button.is-loading {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Sizes */
.size-small {
  padding: 8px 16px;
  font-size: 0.875rem;
}

.size-medium {
  padding: 14px 24px;
  font-size: 1rem;
}

.size-large {
  padding: 16px 32px;
  font-size: 1.125rem;
}

/* Variants */
.variant-primary {
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
}

.variant-primary:hover:not(.is-disabled):not(.is-loading) {
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
}

.variant-secondary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.variant-secondary:hover:not(.is-disabled):not(.is-loading) {
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.variant-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.variant-danger:hover:not(.is-disabled):not(.is-loading) {
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.variant-ghost {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.2);
  color: white;
}

.variant-ghost:hover:not(.is-disabled):not(.is-loading) {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
}

.variant-outline {
  background: transparent;
  border: 2px solid #e94560;
  color: #e94560;
}

.variant-outline:hover:not(.is-disabled):not(.is-loading) {
  background: rgba(233, 69, 96, 0.1);
}

/* Loading state */
.button-spinner {
  position: absolute;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.button-content.button-loading {
  opacity: 0;
}
</style>
