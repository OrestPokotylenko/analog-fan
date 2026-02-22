<template>
  <div class="create-label">
    <div v-if="!labelCreated">
      <h3>Create Shipping Label</h3>
      
      <div v-if="loading" class="loading">
        <p>Loading shipping rates...</p>
      </div>

      <div v-else-if="rates.length > 0" class="rates-container">
        <p class="instruction">Select a shipping rate to create the label:</p>
        
        <div class="rates-list">
          <label 
            v-for="rate in rates" 
            :key="rate.rate_id"
            class="rate-option"
            :class="{ selected: selectedRateId === rate.rate_id }"
          >
            <input 
              type="radio" 
              :value="rate.rate_id" 
              v-model="selectedRateId"
              name="rate"
            />
            <div class="rate-details">
              <div class="rate-header">
                <span class="carrier">{{ rate.carrier }}</span>
                <span class="price">${{ rate.amount }} {{ rate.currency }}</span>
              </div>
              <div class="rate-service">{{ rate.service }}</div>
              <div class="rate-delivery" v-if="rate.estimated_days">
                Estimated delivery: {{ rate.estimated_days }} business day{{ rate.estimated_days > 1 ? 's' : '' }}
              </div>
            </div>
          </label>
        </div>

        <div class="actions">
          <button 
            @click="createLabel" 
            :disabled="!selectedRateId || creating"
            class="btn-primary"
          >
            {{ creating ? 'Creating Label...' : 'Create Shipping Label' }}
          </button>
          <button @click="$emit('cancel')" class="btn-secondary">
            Cancel
          </button>
        </div>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button @click="loadRates" class="btn-secondary">Try Again</button>
      </div>
    </div>

    <div v-else class="success">
      <h3>✓ Shipping Label Created!</h3>
      
      <div class="label-info">
        <p><strong>Tracking Number:</strong> {{ shipment.trackingNumber }}</p>
        <p><strong>Carrier:</strong> {{ shipment.carrier }}</p>
        <p><strong>Service:</strong> {{ shipment.service }}</p>
      </div>

      <div class="actions">
        <a 
          :href="shipment.labelUrl" 
          target="_blank" 
          class="btn-primary"
        >
          📄 Print Shipping Label
        </a>
        <a 
          v-if="shipment.trackingUrl"
          :href="shipment.trackingUrl" 
          target="_blank" 
          class="btn-secondary"
        >
          Track Package
        </a>
        <button @click="$emit('complete', shipment)" class="btn-secondary">
          Done
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  orderId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['complete', 'cancel']);

const loading = ref(true);
const error = ref(null);
const rates = ref([]);
const selectedRateId = ref(null);
const creating = ref(false);
const labelCreated = ref(false);
const shipment = ref(null);

onMounted(() => {
  loadRates();
});

async function loadRates() {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await fetch(`/api/shipments/rates/order/${props.orderId}`);
    
    if (!response.ok) {
      throw new Error('Failed to load shipping rates');
    }
    
    const data = await response.json();
    rates.value = data.rates || [];
    
    // Auto-select the first rate (usually cheapest)
    if (rates.value.length > 0) {
      selectedRateId.value = rates.value[0].rate_id;
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
}

async function createLabel() {
  if (!selectedRateId.value) return;
  
  try {
    creating.value = true;
    error.value = null;
    
    const response = await fetch('/api/shipments/label', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        order_id: props.orderId,
        rate_id: selectedRateId.value,
      }),
    });
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Failed to create shipping label');
    }
    
    shipment.value = await response.json();
    labelCreated.value = true;
    
    // Automatically open the label in a new window for printing
    if (shipment.value.labelUrl) {
      window.open(shipment.value.labelUrl, '_blank');
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    creating.value = false;
  }
}
</script>

<style scoped>
.create-label {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 24px;
  max-width: 600px;
}

h3 {
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 1.5rem;
}

.instruction {
  color: #666;
  margin-bottom: 16px;
}

.rates-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.rate-option {
  display: flex;
  align-items: flex-start;
  padding: 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.rate-option:hover {
  border-color: #1976d2;
  background: #f5f9ff;
}

.rate-option.selected {
  border-color: #1976d2;
  background: #e3f2fd;
}

.rate-option input[type="radio"] {
  margin-right: 12px;
  margin-top: 4px;
  cursor: pointer;
}

.rate-details {
  flex: 1;
}

.rate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.carrier {
  font-weight: 700;
  font-size: 1.125rem;
  color: #333;
}

.price {
  font-weight: 700;
  font-size: 1.25rem;
  color: #1976d2;
}

.rate-service {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 4px;
}

.rate-delivery {
  font-size: 0.75rem;
  color: #999;
}

.actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-primary,
.btn-secondary {
  padding: 12px 24px;
  border-radius: 6px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  transition: all 0.3s;
}

.btn-primary {
  background: #1976d2;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1565c0;
}

.btn-primary:disabled {
  background: #bbdefb;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
}

.btn-secondary:hover {
  background: #e0e0e0;
}

.loading,
.error {
  padding: 40px;
  text-align: center;
}

.error {
  color: #d32f2f;
}

.error button {
  margin-top: 16px;
}

.success {
  text-align: center;
}

.success h3 {
  color: #388e3c;
  font-size: 1.75rem;
}

.label-info {
  background: #f9f9f9;
  padding: 20px;
  border-radius: 6px;
  margin: 20px 0;
  text-align: left;
}

.label-info p {
  margin: 8px 0;
  color: #333;
}

.success .actions {
  justify-content: center;
  flex-wrap: wrap;
}
</style>
