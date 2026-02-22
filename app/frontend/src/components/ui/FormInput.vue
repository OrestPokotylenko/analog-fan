<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="required-indicator">*</span>
    </label>
    
    <textarea
      v-if="type === 'textarea'"
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      class="form-textarea"
      :class="{ 'has-error': error }"
    ></textarea>
    
    <select
      v-else-if="type === 'select'"
      :id="id"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :required="required"
      :disabled="disabled"
      class="form-select"
      :class="{ 'has-error': error }"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    
    <input
      v-else
      :type="type"
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :min="min"
      :max="max"
      :step="step"
      class="form-input"
      :class="{ 'has-error': error }"
    />
    
    <p v-if="error" class="error-text">{{ error }}</p>
    <p v-if="hint && !error" class="hint-text">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: String,
    required: true
  },
  label: {
    type: String,
    default: ''
  },
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text', // text, email, password, number, textarea, select, file, url, date
    validator: (value) => [
      'text', 'email', 'password', 'number', 'textarea', 
      'select', 'file', 'url', 'date', 'tel'
    ].includes(value)
  },
  placeholder: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  rows: {
    type: Number,
    default: 4
  },
  options: {
    type: Array,
    default: () => []
  },
  min: {
    type: Number,
    default: undefined
  },
  max: {
    type: Number,
    default: undefined
  },
  step: {
    type: [Number, String],
    default: undefined
  }
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.form-label {
  color: #e0e0e0;
  font-weight: 600;
  font-size: 0.95em;
  letter-spacing: 0.3px;
}

.required-indicator {
  color: #e94560;
  margin-left: 4px;
}

.form-input,
.form-select,
.form-textarea {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(233, 69, 96, 0.2);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 1em;
  font-family: inherit;
  transition: all 0.3s ease;
  width: 100%;
}

.form-input::placeholder,
.form-select::placeholder,
.form-textarea::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: rgba(233, 69, 96, 0.6);
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
}

.form-input:disabled,
.form-select:disabled,
.form-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select option {
  background: #1a1a2e;
  color: white;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.has-error {
  border-color: rgba(220, 53, 69, 0.6);
}

.has-error:focus {
  border-color: rgba(220, 53, 69, 0.8);
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.error-text {
  color: #ff7a8a;
  font-size: 0.875em;
  margin: 0;
}

.hint-text {
  color: #a0a0a0;
  font-size: 0.875em;
  margin: 0;
}

@media (max-width: 480px) {
  .form-input,
  .form-select,
  .form-textarea {
    padding: 10px 12px;
    font-size: 0.95em;
  }
}
</style>
