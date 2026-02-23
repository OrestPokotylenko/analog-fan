<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import PageLayout from '../../components/layout/PageLayout.vue';
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue';
import axios from '../../services/axiosConfig';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';
import { useToast } from '../../composables/useToast';

const router = useRouter();
const $auth = inject('$auth');

const orders = ref([]);
const shipments = ref([]);
const isLoading = ref(true);
const testingStatus = ref({});
const selectedTestStatus = ref({});
const toastType = ref('success');

const { toastVisible, toastMessage, showToast: _showToast } = useToast();

function showToast(message, type = 'success') {
  toastType.value = type;
  _showToast(message, 3500);
}

const statusColors = {
  pending: '#f59e0b',
  processing: '#3b82f6',
  shipped: '#8b5cf6',
  delivered: '#10b981',
  cancelled: '#ef4444'
};

const shipmentStatusColors = {
  label_created: '#3b82f6',
  pre_transit: '#f59e0b',
  transit: '#8b5cf6',
  out_for_delivery: '#ec4899',
  delivered: '#10b981',
  failure: '#ef4444',
  returned: '#6b7280',
  unknown: '#6b7280'
};

onMounted(async () => {
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) {
    clearAuthState($auth);
    router.push('/login');
    return;
  }
  
  if (!$auth.isLoggedIn) {
    router.push('/login');
    return;
  }
  
  await loadData();
});

async function loadData() {
  try {
    isLoading.value = true;
    const user = JSON.parse(localStorage.getItem('user'));
    
    // Get orders containing items sold by this user
    const ordersResponse = await axios.get(`/orders/seller/${user.userId}`);
    orders.value = ordersResponse.data || [];
    
    // Get shipments for these orders
    const orderIds = orders.value.map(o => o.id);
    if (orderIds.length > 0) {
      const shipmentsPromises = orderIds.map(orderId => 
        axios.get(`/shipments/order/${orderId}`).catch(() => null)
      );
      const shipmentsResults = await Promise.all(shipmentsPromises);
      shipments.value = shipmentsResults
        .filter(r => r && r.data)
        .map(r => r.data);
    }
  } catch (error) {
    console.error('Failed to load sales data:', error);
  } finally {
    isLoading.value = false;
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function downloadLabel(shipmentId) {
  // Use full URL to avoid Vue Router interference
  window.open(`http://localhost/api/shipments/${shipmentId}/label`, '_blank');
}

function trackShipment(trackingUrl) {
  window.open(trackingUrl, '_blank');
}

async function testStatusChange(shipment) {
  const newStatus = selectedTestStatus.value[shipment.id];
  if (!newStatus) {
    showToast('Please select a status first', 'error');
    return;
  }
  
  try {
    testingStatus.value[shipment.id] = true;
    
    // Call the mock webhook endpoint
    await axios.post('/webhooks/sendcloud/mock', {
      tracking_number: shipment.trackingNumber,
      status: newStatus
    });
    
    // Reload data to see the change
    await loadData();
    
    showToast(`Status updated to: ${newStatus}`, 'success');
  } catch (error) {
    console.error('Failed to update status:', error);
    showToast('Failed to update status: ' + (error.response?.data?.error || error.message), 'error');
  } finally {
    testingStatus.value[shipment.id] = false;
  }
}
</script>

<template>
  <PageLayout>
  <Transition name="toast">
    <div v-if="toastVisible" class="toast-notification" :class="toastType">
      {{ toastMessage }}
    </div>
  </Transition>
  <div class="my-sales-page">
    <div class="sales-header">
      <h1>📦 My Sales</h1>
      <p class="subtitle">Track shipments for items you've sold</p>
    </div>

    <LoadingSpinner v-if="isLoading" message="Loading your sales..." />

    <div v-else class="sales-content">
      <!-- Shipments -->
      <div class="tab-content">
        <div v-if="shipments.length === 0" class="empty-state">
          <div class="empty-icon">📦</div>
          <h3>No Shipments Yet</h3>
          <p>Shipments for your sold items will appear here</p>
        </div>

        <div v-else class="shipments-grid">
          <div v-for="shipment in shipments" :key="shipment.id" class="shipment-card">
            <div class="shipment-card-header">
              <h3>{{ shipment.carrier }} - {{ shipment.trackingNumber }}</h3>
              <span 
                class="status-badge"
                :style="{ backgroundColor: shipmentStatusColors[shipment.deliveryStatus] || '#6b7280' }"
              >
                {{ shipment.deliveryStatus }}
              </span>
            </div>

            <div class="shipment-card-details">
              <div class="detail-row">
                <span class="label">Order:</span>
                <span>#{{ orders.find(o => o.id === shipment.orderId)?.orderNumber }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Created:</span>
                <span>{{ formatDate(shipment.createdAt) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Tracking:</span>
                <a 
                  v-if="shipment.trackingUrl"
                  :href="shipment.trackingUrl"
                  target="_blank"
                  class="tracking-link"
                >
                  {{ shipment.trackingNumber }}
                </a>
                <span v-else>{{ shipment.trackingNumber }}</span>
              </div>
            </div>

            <div class="shipment-card-actions">
              <button @click="downloadLabel(shipment.id)" class="btn-download">
                📄 Download Label
              </button>
              <button 
                v-if="shipment.trackingUrl"
                @click="trackShipment(shipment.trackingUrl)" 
                class="btn-track"
              >
                📍 Track Package
              </button>
            </div>

            <!-- Test Status Change -->
            <div class="test-controls">
              <div class="test-label">🧪 Test Status Change:</div>
              <div class="test-inputs">
                <select 
                  v-model="selectedTestStatus[shipment.id]"
                  class="status-select"
                >
                  <option value="">Select status...</option>
                  <option value="label_created">Label Created</option>
                  <option value="pre_transit">Pre-Transit</option>
                  <option value="transit">In Transit</option>
                  <option value="out_for_delivery">Out for Delivery</option>
                  <option value="delivered">Delivered</option>
                  <option value="failure">Delivery Failed</option>
                  <option value="returned">Returned</option>
                </select>
                <button 
                  @click="testStatusChange(shipment)"
                  :disabled="testingStatus[shipment.id]"
                  class="btn-test"
                >
                  {{ testingStatus[shipment.id] ? '⏳ Updating...' : '🔄 Update' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </PageLayout>
</template>

<style scoped>
.my-sales-page {
  padding: 20px;
  color: white;
}

.sales-header {
  text-align: center;
  padding: 80px 20px 40px;
}

.sales-header h1 {
  font-size: 2.5rem;
  padding-bottom: 10px;
  margin-bottom: 0px; 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #a0a0a0;
  font-size: 1.1rem;
}

.sales-content {
  max-width: 1400px;
  margin: 0 auto;
}



.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 20px;
}

.empty-state h3 {
  font-size: 1.8rem;
  margin-bottom: 10px;
}

.empty-state p {
  color: #a0a0a0;
  font-size: 1.1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-row:last-child {
  border-bottom: none;
}

.label {
  font-weight: 600;
  color: #a0a0a0;
  font-size: 0.9rem;
}

.status-badge {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: 600;
  color: white;
  text-transform: uppercase;
}

.btn-download,
.btn-track {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-download {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-download:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-track {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-track:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
}

.tracking-link {
 
 color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.tracking-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

.shipments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
  padding: 20px 0;
}

.shipment-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 25px;
  transition: all 0.3s;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.shipment-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  border-color: rgba(102, 126, 234, 0.5);
}

.shipment-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.shipment-card-header h3 {
  margin: 0;
  font-size: 1.2rem;
  color: #667eea;
}

.shipment-card-details {
  margin-bottom: 20px;
}

.shipment-card-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}

.test-controls {
  background: rgba(251, 191, 36, 0.1);
  border: 1px dashed rgba(251, 191, 36, 0.4);
  border-radius: 8px;
  padding: 15px;
  margin-top: 15px;
}

.test-label {
  color: #fbbf24;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.test-inputs {
  display: flex;
  gap: 10px;
  align-items: center;
}

.status-select {
  flex: 1;
  padding: 10px 15px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s;
}

.status-select:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(251, 191, 36, 0.5);
}

.status-select:focus {
  outline: none;
  border-color: #fbbf24;
  box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.2);
}

.status-select option {
  background: #1a1a2e;
  color: white;
}

.btn-test {
  padding: 10px 20px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #1a1a2e;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s;
  white-space: nowrap;
}

.btn-test:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(251, 191, 36, 0.4);
}

.btn-test:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.toast-notification {
  position: fixed;
  top: 90px;
  bottom: auto;
  right: 24px;
  padding: 14px 22px;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
  z-index: 9999;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
  background: none;
}

.toast-notification.success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.toast-notification.error {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateX(60px);
}

@media (max-width: 768px) {
  .shipments-grid {
    grid-template-columns: 1fr;
  }
  
  .test-inputs {
    flex-direction: column;
  }
  
  .status-select {
    width: 100%;
  }
  
  .btn-test {
    width: 100%;
  }
}
</style>
