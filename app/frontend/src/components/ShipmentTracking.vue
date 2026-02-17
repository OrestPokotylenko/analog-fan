<template>
  <div v-if="shipment" class="shipment-tracking">
    <div class="tracking-header">
      <h3>📦 Shipment Tracking</h3>
      <span :class="['status-badge', `status-${shipment.deliveryStatus}`]">
        {{ formatStatus(shipment.deliveryStatus) }}
      </span>
    </div>

    <div class="tracking-info">
      <div class="info-row">
        <span class="label">Tracking Number:</span>
        <span class="value">{{ shipment.trackingNumber }}</span>
      </div>
      
      <div class="info-row">
        <span class="label">Carrier:</span>
        <span class="value">{{ shipment.carrier }} - {{ shipment.service }}</span>
      </div>
      
      <div class="info-row" v-if="shipment.estimatedDeliveryDate">
        <span class="label">Estimated Delivery:</span>
        <span class="value">{{ formatDate(shipment.estimatedDeliveryDate) }}</span>
      </div>
      
      <div class="info-row" v-if="shipment.shippedAt">
        <span class="label">Shipped On:</span>
        <span class="value">{{ formatDate(shipment.shippedAt) }}</span>
      </div>
      
      <div class="info-row" v-if="shipment.deliveredAt">
        <span class="label">Delivered On:</span>
        <span class="value">{{ formatDate(shipment.deliveredAt) }}</span>
      </div>
    </div>

    <div class="tracking-actions">
      <a 
        v-if="shipment.trackingUrl" 
        :href="shipment.trackingUrl" 
        target="_blank" 
        class="btn-track"
      >
        Track on Carrier Website →
      </a>
      
      <a 
        v-if="shipment.labelUrl && isAdmin" 
        :href="shipment.labelUrl" 
        target="_blank" 
        class="btn-label"
      >
        View Shipping Label
      </a>
      
      <button 
        @click="refreshTracking" 
        :disabled="refreshing"
        class="btn-refresh"
      >
        {{ refreshing ? 'Refreshing...' : '🔄 Refresh Status' }}
      </button>
    </div>

    <div v-if="trackingHistory && trackingHistory.length > 0" class="tracking-history">
      <h4>Tracking History</h4>
      <div class="timeline">
        <div 
          v-for="(event, index) in trackingHistory" 
          :key="index" 
          class="timeline-event"
        >
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <p class="event-status">{{ event.status_details || event.status }}</p>
            <p class="event-location" v-if="event.location">{{ event.location }}</p>
            <p class="event-date">{{ formatDateTime(event.status_date) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else-if="loading" class="loading">
    <p>Loading shipment information...</p>
  </div>

  <div v-else-if="error" class="error">
    <p>{{ error }}</p>
  </div>

  <div v-else class="no-shipment">
    <p>No shipment information available yet.</p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
  orderId: {
    type: Number,
    required: true,
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
});

const shipment = ref(null);
const loading = ref(true);
const error = ref(null);
const refreshing = ref(false);

const trackingHistory = computed(() => {
  if (!shipment.value?.trackingHistory) return [];
  // Sort by date, most recent first
  return [...shipment.value.trackingHistory].sort(
    (a, b) => new Date(b.status_date) - new Date(a.status_date)
  );
});

onMounted(() => {
  loadShipment();
});

async function loadShipment() {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await fetch(`/api/shipments/order/${props.orderId}`);
    
    if (response.ok) {
      shipment.value = await response.json();
    } else if (response.status === 404) {
      // No shipment yet - this is ok
      shipment.value = null;
    } else {
      throw new Error('Failed to load shipment information');
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
}

async function refreshTracking() {
  if (!shipment.value?.id) return;
  
  try {
    refreshing.value = true;
    
    const response = await fetch(`/api/shipments/${shipment.value.id}/tracking`, {
      method: 'PUT',
    });
    
    if (response.ok) {
      shipment.value = await response.json();
    } else {
      throw new Error('Failed to refresh tracking information');
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    refreshing.value = false;
  }
}

function formatStatus(status) {
  const statusMap = {
    'label_created': 'Label Created',
    'pre_transit': 'Pre-Transit',
    'transit': 'In Transit',
    'out_for_delivery': 'Out for Delivery',
    'delivered': 'Delivered',
    'returned': 'Returned',
    'failure': 'Delivery Failed',
    'unknown': 'Unknown',
  };
  return statusMap[status] || status;
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
}

function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
.shipment-tracking {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 24px;
  margin: 20px 0;
}

.tracking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.tracking-header h3 {
  margin: 0;
  font-size: 1.5rem;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-label_created { background: #e3f2fd; color: #1976d2; }
.status-pre_transit { background: #fff3e0; color: #f57c00; }
.status-transit { background: #fff9c4; color: #f57f17; }
.status-out_for_delivery { background: #f3e5f5; color: #7b1fa2; }
.status-delivered { background: #e8f5e9; color: #388e3c; }
.status-returned { background: #ffebee; color: #d32f2f; }
.status-failure { background: #ffcdd2; color: #c62828; }
.status-unknown { background: #f5f5f5; color: #616161; }

.tracking-info {
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row .label {
  font-weight: 600;
  color: #666;
}

.info-row .value {
  color: #333;
}

.tracking-actions {
  display: flex;
  gap: 12px;
  margin: 20px 0;
  flex-wrap: wrap;
}

.btn-track,
.btn-label,
.btn-refresh {
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-track {
  background: #1976d2;
  color: white;
}

.btn-track:hover {
  background: #1565c0;
}

.btn-label {
  background: #424242;
  color: white;
}

.btn-label:hover {
  background: #212121;
}

.btn-refresh {
  background: #f5f5f5;
  color: #333;
}

.btn-refresh:hover:not(:disabled) {
  background: #e0e0e0;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.tracking-history {
  margin-top: 30px;
}

.tracking-history h4 {
  margin-bottom: 16px;
  font-size: 1.125rem;
}

.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e0e0e0;
}

.timeline-event {
  position: relative;
  margin-bottom: 20px;
}

.timeline-dot {
  position: absolute;
  left: -26px;
  top: 4px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #1976d2;
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-content {
  background: #f9f9f9;
  padding: 12px 16px;
  border-radius: 6px;
}

.event-status {
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #333;
}

.event-location {
  font-size: 0.875rem;
  color: #666;
  margin: 0 0 4px 0;
}

.event-date {
  font-size: 0.75rem;
  color: #999;
  margin: 0;
}

.loading,
.error,
.no-shipment {
  padding: 40px;
  text-align: center;
  color: #666;
}

.error {
  color: #d32f2f;
}
</style>
