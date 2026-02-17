<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../services/axiosConfig';
import OrderService from '../services/OrderService';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');

const users = ref([]);
const items = ref([]);
const productTypes = ref([]);
const orders = ref([]);
const shipments = ref([]);
const stats = ref({
  totalUsers: 0,
  totalItems: 0,
  totalProductTypes: 0,
  totalOrders: 0,
  totalShipments: 0
});
const isLoading = ref(true);
const activeTab = ref('dashboard');
const showTypeModal = ref(false);
const editingType = ref(null);
const typeFormName = ref('');
const typeFormImage = ref(null);
const imagePreview = ref(null);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmMessage = ref('');
const confirmTitle = ref('');
const showErrorModal = ref(false);
const errorMessage = ref('');
const errorTitle = ref('');
const isSavingType = ref(false);
const isDeletingUser = ref(false);
const isDeletingItem = ref(false);
const isDeletingType = ref(false);
const isConfirming = ref(false);

const statusColors = {
  pending: '#f59e0b',
  processing: '#3b82f6',
  shipped: '#8b5cf6',
  delivered: '#10b981',
  cancelled: '#ef4444'
};

const paymentStatusColors = {
  pending: '#f59e0b',
  paid: '#10b981',
  failed: '#ef4444',
  refunded: '#6b7280'
};

const statusDescriptions = {
  pending: '⏳ Awaiting Processing',
  processing: '⚙️ Order Being Prepared',
  shipped: '🚚 On The Way',
  delivered: '✅ Successfully Delivered',
  cancelled: '❌ Order Cancelled'
};

const paymentStatusDescriptions = {
  pending: '⏳ Payment Pending',
  paid: '✅ Payment Received',
  failed: '❌ Payment Failed',
  refunded: '↩️ Payment Refunded'
};

onMounted(async () => {
  // Check token expiration immediately
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) {
    clearAuthState($auth);
    router.push('/login');
    return;
  }
  
  // Check if user is admin
  if (!$auth.user || $auth.user.role !== 'admin') {
    router.push('/');
    return;
  }
  
  await loadData();
});

async function loadData() {
  try {
    isLoading.value = true;
    
    // Load users
    const usersResponse = await axios.get('/users');
    users.value = usersResponse.data;
    
    // Load all items
    const itemsResponse = await axios.get('/items');
    items.value = itemsResponse.data;
    
    // Load product types
    const typesResponse = await axios.get('/product-types');
    productTypes.value = typesResponse.data;
    
    // Load orders
    const ordersResponse = await OrderService.getAllOrders();
    orders.value = ordersResponse.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    
    // Load shipments
    const shipmentsResponse = await axios.get('/shipments/admin');
    shipments.value = shipmentsResponse.data.data || [];
    
    // Calculate stats
    stats.value = {
      totalUsers: users.value.length,
      totalItems: items.value.length,
      totalProductTypes: productTypes.value.length,
      totalOrders: orders.value.length,
      totalShipments: shipments.value.length
    };
  } catch (error) {
    console.error('Failed to load admin data:', error);
  } finally {
    isLoading.value = false;
  }
}

async function deleteUser(userId) {
  if (isDeletingUser.value) return;
  confirmTitle.value = 'Delete User';
  confirmMessage.value = 'Are you sure you want to delete this user and all their items? This action cannot be undone.';
  confirmAction.value = async () => {
    try {
      isDeletingUser.value = true;
      await axios.delete(`/users/${userId}`);
      await loadData();
    } catch (error) {
      console.error('Failed to delete user:', error);
      alert('Failed to delete user');
    } finally {
      isDeletingUser.value = false;
    }
  };
  showConfirmModal.value = true;
}

async function deleteItem(itemId) {
  if (isDeletingItem.value) return;
  confirmTitle.value = 'Delete Item';
  confirmMessage.value = 'Are you sure you want to delete this item? This action cannot be undone.';
  confirmAction.value = async () => {
    try {
      isDeletingItem.value = true;
      await axios.delete(`/items/${itemId}`);
      await loadData();
    } catch (error) {
      console.error('Failed to delete item:', error);
      alert('Failed to delete item');
    } finally {
      isDeletingItem.value = false;
    }
  };
  showConfirmModal.value = true;
}

async function deleteProductType(typeId) {
  if (isDeletingType.value) return;
  confirmTitle.value = 'Delete Product Type';
  confirmMessage.value = 'Are you sure you want to delete this product type? This action cannot be undone.';
  confirmAction.value = async () => {
    try {
      isDeletingType.value = true;
      const response = await axios.delete(`/product-types/${typeId}`);
      
      // Check if it's a conflict error (foreign key constraint)
      if (response.status === 409) {
        errorTitle.value = 'Cannot Delete Product Type';
        errorMessage.value = 'This product type is currently being used by one or more items. Please remove or reassign those items before deleting this product type.';
        showErrorModal.value = true;
      } else {
        await loadData();
      }
    } catch (error) {
      errorTitle.value = 'Delete Failed';
      errorMessage.value = 'An unexpected error occurred while trying to delete the product type. Please try again.';
      showErrorModal.value = true;
    } finally {
      isDeletingType.value = false;
    }
  };
  showConfirmModal.value = true;
}

async function confirmDelete() {
  if (isConfirming.value || !confirmAction.value) return;
  try {
    isConfirming.value = true;
    await confirmAction.value();
  } finally {
    isConfirming.value = false;
    closeConfirmModal();
  }
}

function closeConfirmModal() {
  showConfirmModal.value = false;
  confirmAction.value = null;
  confirmMessage.value = '';
  confirmTitle.value = '';
}

function closeErrorModal() {
  showErrorModal.value = false;
  errorMessage.value = '';
  errorTitle.value = '';
}

function openCreateTypeModal() {
  editingType.value = null;
  typeFormName.value = '';
  typeFormImage.value = null;
  imagePreview.value = null;
  showTypeModal.value = true;
}

function openEditTypeModal(type) {
  editingType.value = type;
  typeFormName.value = type.typeName;
  typeFormImage.value = null;
  imagePreview.value = type.imageUrl || null;
  showTypeModal.value = true;
}

function closeTypeModal() {
  showTypeModal.value = false;
  editingType.value = null;
  typeFormName.value = '';
  typeFormImage.value = null;
  imagePreview.value = null;
}

function handleImageChange(event) {
  const file = event.target.files[0];
  if (file) {
    typeFormImage.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

async function saveProductType() {
  if (!typeFormName.value.trim()) {
    alert('Please enter a name');
    return;
  }

  if (isSavingType.value) return;

  try {
    isSavingType.value = true;
    const formData = new FormData();
    formData.append('name', typeFormName.value);
    
    if (typeFormImage.value) {
      formData.append('image', typeFormImage.value);
    }

    if (editingType.value) {
      // Update existing type - use FormData to support image updates
      await axios.put(`/product-types/${editingType.value.productTypeId}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
    } else {
      // Create new type
      await axios.post('/product-types', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
    }
    closeTypeModal();
    await loadData();
  } catch (error) {
    console.error('Failed to save product type:', error);
    alert('Failed to save product type');
  } finally {
    isSavingType.value = false;
  }
}

function formatDateShort(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric'
  });
}

function getShipmentStatusColor(status) {
  const colors = {
    pending: '#f59e0b',
    in_transit: '#3b82f6',
    out_for_delivery: '#8b5cf6',
    delivered: '#10b981',
    failed: '#ef4444',
    returned: '#6b7280'
  };
  return colors[status] || '#6b7280';
}

function viewOrderDetailsAdmin(orderId) {
  router.push(`/orders/${orderId}`);
}

async function cancelOrder(order) {
  confirmTitle.value = 'Cancel Order';
  confirmMessage.value = `Are you sure you want to cancel order #${order.orderNumber}? This action cannot be undone.`;
  confirmAction.value = async () => {
    try {
      await OrderService.cancelOrder(order.id);
      await loadData();
    } catch (error) {
      console.error('Failed to cancel order:', error);
      alert('Failed to cancel order');
    }
  };
  showConfirmModal.value = true;
}

async function refundOrder(order) {
  confirmTitle.value = 'Refund Order';
  confirmMessage.value = `Are you sure you want to refund order #${order.orderNumber}? The payment status will be changed to refunded.`;
  confirmAction.value = async () => {
    try {
      await OrderService.refundOrder(order.id);
      await loadData();
    } catch (error) {
      console.error('Failed to refund order:', error);
      alert('Failed to refund order');
    }
  };
  showConfirmModal.value = true;
}

function logout() {
  localStorage.removeItem('jwtToken');
  localStorage.removeItem('user');
  $auth.isLoggedIn = false;
  $auth.token = null;
  $auth.user = null;
  router.push('/login');
}
</script>

<template>
  <div class="admin-page">
    <div class="admin-header">
      <h1>🛡️ Admin Dashboard</h1>
      <button @click="logout" class="btn-logout">Logout</button>
    </div>

    <div v-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>Loading admin data...</p>
    </div>

    <div v-else class="admin-content">
      <div class="tabs">
        <button 
          :class="['tab', { active: activeTab === 'dashboard' }]" 
          @click="activeTab = 'dashboard'"
        >
          Dashboard
        </button>
        <button 
          :class="['tab', { active: activeTab === 'users' }]" 
          @click="activeTab = 'users'"
        >
          Users ({{ users.length }})
        </button>
        <button 
          :class="['tab', { active: activeTab === 'items' }]" 
          @click="activeTab = 'items'"
        >
          Items ({{ items.length }})
        </button>
        <button 
          :class="['tab', { active: activeTab === 'orders' }]" 
          @click="activeTab = 'orders'"
        >
          Orders ({{ orders.length }})
        </button>
        <button 
          :class="['tab', { active: activeTab === 'types' }]" 
          @click="activeTab = 'types'"
        >
          Product Types ({{ productTypes.length }})
        </button>
        <button 
          :class="['tab', { active: activeTab === 'shipments' }]" 
          @click="activeTab = 'shipments'"
        >
          Shipments ({{ shipments.length }})
        </button>
      </div>

      <!-- Dashboard Tab -->
      <div v-if="activeTab === 'dashboard'" class="tab-content">
        <div class="stats-grid">
          <div class="stat-card" @click="activeTab = 'users'">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
              <h3>Total Users</h3>
              <p class="stat-number">{{ stats.totalUsers }}</p>
            </div>
          </div>
          <div class="stat-card" @click="activeTab = 'items'">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
              <h3>Total Items</h3>
              <p class="stat-number">{{ stats.totalItems }}</p>
            </div>
          </div>
          <div class="stat-card" @click="activeTab = 'orders'">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
              <h3>Total Orders</h3>
              <p class="stat-number">{{ stats.totalOrders }}</p>
            </div>
          </div>
          <div class="stat-card" @click="activeTab = 'types'">
            <div class="stat-icon">🏷️</div>
            <div class="stat-info">
              <h3>Product Types</h3>
              <p class="stat-number">{{ stats.totalProductTypes }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Tab -->
      <div v-if="activeTab === 'users'" class="tab-content">
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.userId">
                <td>{{ user.userId }}</td>
                <td>{{ user.username }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <span :class="['badge', user.role === 'admin' ? 'badge-admin' : 'badge-user']">
                    {{ user.role }}
                  </span>
                </td>
                <td>{{ user.createdAt }}</td>
                <td>
                  <button
                    v-if="user.role !== 'admin'"
                    @click="deleteUser(user.userId)" 
                    class="btn-delete"
                    :disabled="isDeletingUser"
                  >
                    {{ isDeletingUser ? 'Deleting...' : 'Delete' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Items Tab -->
      <div v-if="activeTab === 'items'" class="tab-content">
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Price</th>
                <th>Type</th>
                <th>Owner</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.itemId">
                <td>{{ item.itemId }}</td>
                <td>{{ item.title }}</td>
                <td>€{{ item.price.toFixed(2) }}</td>
                <td>{{ item.type }}</td>
                <td>User #{{ item.userId }}</td>
                <td>
                  <button 
                    @click="deleteItem(item.itemId)" 
                    class="btn-delete"
                    :disabled="isDeletingItem"
                  >
                    {{ isDeletingItem ? 'Deleting...' : 'Delete' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Orders Tab -->
      <div v-if="activeTab === 'orders'" class="tab-content">
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>User Email</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id">
                <td><strong>{{ order.orderNumber }}</strong></td>
                <td>{{ order.email }}</td>
                <td>€{{ parseFloat(order.totalAmount).toFixed(2) }}</td>
                <td>
                  <span 
                    class="status-badge-table" 
                    :style="{ backgroundColor: statusColors[order.status] }"
                  >
                    {{ statusDescriptions[order.status] }}
                  </span>
                </td>
                <td>
                  <span 
                    class="status-badge-table" 
                    :style="{ backgroundColor: paymentStatusColors[order.paymentStatus] }"
                  >
                    {{ paymentStatusDescriptions[order.paymentStatus] }}
                  </span>
                </td>
                <td>{{ formatDateShort(order.createdAt) }}</td>
                <td>
                  <button @click="viewOrderDetailsAdmin(order.id)" class="btn-view">
                    View
                  </button>
                  <button 
                    v-if="order.status !== 'cancelled' && order.status !== 'delivered'" 
                    @click="cancelOrder(order)" 
                    class="btn-cancel"
                  >
                    Cancel
                  </button>
                  <button 
                    v-if="order.paymentStatus === 'paid' && order.paymentStatus !== 'refunded'" 
                    @click="refundOrder(order)" 
                    class="btn-refund"
                  >
                    Refund
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Shipments Tab -->
      <div v-if="activeTab === 'shipments'" class="tab-content">
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Tracking #</th>
                <th>Carrier</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="shipment in shipments" :key="shipment.id">
                <td><strong>{{ shipment.order_number }}</strong></td>
                <td>{{ shipment.email }}</td>
                <td>
                  {{ shipment.street }} {{ shipment.house_number }}<br>
                  {{ shipment.zip_code }} {{ shipment.city }}, {{ shipment.country }}
                </td>
                <td>
                  <a 
                    v-if="shipment.tracking_url" 
                    :href="shipment.tracking_url" 
                    target="_blank" 
                    class="tracking-link"
                  >
                    {{ shipment.tracking_number }}
                  </a>
                  <span v-else>{{ shipment.tracking_number }}</span>
                </td>
                <td>{{ shipment.carrier }}</td>
                <td>
                  <span 
                    class="status-badge-table" 
                    :style="{ backgroundColor: getShipmentStatusColor(shipment.delivery_status) }"
                  >
                    {{ shipment.delivery_status }}
                  </span>
                </td>
                <td>{{ formatDateShort(shipment.created_at) }}</td>
                <td>
                  <a 
                    :href="`http://localhost/api/shipments/${shipment.id}/label`" 
                    target="_blank" 
                    class="btn-view"
                  >
                    📄 Label
                  </a>
                  <a 
                    v-if="shipment.tracking_url" 
                    :href="shipment.tracking_url" 
                    target="_blank" 
                    class="btn-track"
                  >
                    📍 Track
                  </a>
                </td>
              </tr>
              <tr v-if="shipments.length === 0">
                <td colspan="8" class="no-data">No shipments found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Product Types Tab -->
      <div v-if="activeTab === 'types'" class="tab-content">
        <div class="action-bar">
          <button @click="openCreateTypeModal" class="btn-primary">
            + Add Product Type
          </button>
        </div>
        
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="type in productTypes" :key="type.productTypeId">
                <td>{{ type.productTypeId }}</td>
                <td>
                  <img 
                    v-if="type.imageUrl" 
                    :src="type.imageUrl" 
                    alt="Product type image"
                    class="table-thumb"
                  />
                  <span v-else class="no-image">No image</span>
                </td>
                <td>{{ type.typeName }}</td>
                <td>
                  <button 
                    @click="openEditTypeModal(type)" 
                    class="btn-edit"
                    :disabled="isSavingType || isDeletingType"
                  >
                    Edit
                  </button>
                  <button 
                    @click="deleteProductType(type.productTypeId)" 
                    class="btn-delete"
                    :disabled="isDeletingType"
                  >
                    {{ isDeletingType ? 'Deleting...' : 'Delete' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Product Type Modal -->
    <div v-if="showTypeModal" class="modal-overlay" @click="closeTypeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ editingType ? 'Edit Product Type' : 'Add Product Type' }}</h2>
          <button @click="closeTypeModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Type Name</label>
            <input 
              v-model="typeFormName" 
              type="text" 
              placeholder="Enter product type name"
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label>Type Image</label>
            <input 
              type="file" 
              accept="image/*"
              @change="handleImageChange"
              class="form-input"
            />
            <div v-if="imagePreview" class="image-preview">
              <img :src="imagePreview" alt="Preview" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            @click="closeTypeModal" 
            class="btn-secondary"
            :disabled="isSavingType"
          >
            Cancel
          </button>
          <button 
            @click="saveProductType" 
            class="btn-primary"
            :disabled="isSavingType"
          >
            {{ isSavingType ? 'Saving...' : (editingType ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmModal" class="modal-overlay" @click="closeConfirmModal">
      <div class="modal-content confirm-modal" @click.stop>
        <div class="modal-header">
          <h2>{{ confirmTitle }}</h2>
          <button @click="closeConfirmModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="confirm-icon">⚠️</div>
          <p class="confirm-message">{{ confirmMessage }}</p>
        </div>
        <div class="modal-footer">
          <button 
            @click="closeConfirmModal" 
            class="btn-secondary"
            :disabled="isConfirming"
          >
            Cancel
          </button>
          <button 
            @click="confirmDelete" 
            class="btn-danger"
            :disabled="isConfirming"
          >
            {{ isConfirming ? 'Processing...' : 'Confirm' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal" class="modal-overlay" @click="closeErrorModal">
      <div class="modal-content error-modal" @click.stop>
        <div class="modal-header">
          <h2>{{ errorTitle }}</h2>
          <button @click="closeErrorModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="error-icon">🚫</div>
          <p class="error-message">{{ errorMessage }}</p>
        </div>
        <div class="modal-footer">
          <button @click="closeErrorModal" class="btn-primary">
            Got it
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1e 100%);
  padding: 20px;
  color: white;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  margin-bottom: 30px;
}

.admin-header h1 {
  margin: 0;
  font-size: 2rem;
}

.btn-logout {
  padding: 10px 20px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-logout:hover {
  background: #ff6b7a;
  transform: translateY(-2px);
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.tab {
  padding: 15px 30px;
  background: transparent;
  color: #a0a0a0;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
}

.tab:hover {
  color: white;
}

.tab.active {
  color: white;
  border-bottom-color: #667eea;
}

.tab-content {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 30px;
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.3s;
}

.stat-card:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-5px);
}

.stat-icon {
  font-size: 3rem;
}

.stat-info h3 {
  margin: 0 0 10px 0;
  font-size: 1rem;
  color: #a0a0a0;
}

.stat-number {
  margin: 0;
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.table-container {
  overflow-x: auto;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 20px;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th {
  text-align: left;
  padding: 15px;
  border-bottom: 2px solid rgba(255, 255, 255, 0.1);
  font-weight: 600;
  color: #667eea;
}

.admin-table td {
  padding: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.admin-table tr:hover {
  background: rgba(255, 255, 255, 0.03);
}

.table-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid rgba(255, 255, 255, 0.1);
}

.no-image {
  color: #666;
  font-style: italic;
  font-size: 0.9rem;
}

.badge {
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge-admin {
  background: #667eea;
  color: white;
}

.badge-user {
  background: rgba(255, 255, 255, 0.1);
  color: #a0a0a0;
}

.btn-delete {
  padding: 8px 16px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-delete:hover {
  background: #ff6b7a;
  transform: scale(1.05);
}

.btn-edit {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  margin-right: 8px;
}

.btn-edit:hover {
  background: #764ba2;
  transform: scale(1.05);
}

.btn-primary {
  padding: 10px 20px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-primary:hover {
  background: #764ba2;
  transform: translateY(-2px);
}

.btn-secondary {
  padding: 10px 20px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.15);
}

.action-bar {
  margin-bottom: 20px;
  display: flex;
  justify-content: flex-end;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #1a1a2e;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  overflow-x: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.btn-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 5px;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #a0a0a0;
  font-weight: 600;
}

.form-input {
  width: 100%;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 5px;
  color: white;
  font-size: 1rem;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: rgba(255, 255, 255, 0.08);
}

.image-preview {
  margin-top: 15px;
  max-width: 300px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.1);
}

.image-preview img {
  width: 100%;
  height: auto;
  display: block;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.confirm-modal {
  max-width: 450px;
  overflow: hidden;
}

.confirm-icon {
  font-size: 4rem;
  text-align: center;
  margin-bottom: 20px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.8; }
}

.confirm-message {
  text-align: center;
  font-size: 1.1rem;
  line-height: 1.6;
  color: #e0e0e0;
  margin: 0;
}

.btn-danger {
  padding: 10px 20px;
  background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%);
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-danger:hover {
  background: linear-gradient(135deg, #ff6b7a 0%, #ff8596 100%);
  transform: translateY(-2px);
}

.error-modal {
  max-width: 450px;
  overflow: hidden;
}

.error-icon {
  font-size: 4rem;
  text-align: center;
  margin-bottom: 20px;
}

.error-message {
  text-align: center;
  font-size: 1.05rem;
  line-height: 1.6;
  color: #e0e0e0;
  margin: 0;
}

.status-badge-table {
  padding: 5px 12px;
  border-radius: 15px;
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
  letter-spacing: 0.3px;
  display: inline-block;
  white-space: nowrap;
}

.btn-view {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  margin-left: 5px;
}

.btn-view:hover {
  background: #5568d3;
  transform: scale(1.05);
}

.order-info-display {
  margin-top: 20px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.order-info-display h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: #667eea;
  font-size: 1rem;
}

.order-info-display p {
  margin: 8px 0;
  color: #e0e0e0;
  line-height: 1.6;
}

.stat-card {
  cursor: pointer;
}

.btn-cancel {
  padding: 8px 16px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  margin-left: 5px;
}

.btn-cancel:hover {
  background: #d97706;
  transform: scale(1.05);
}

.btn-refund {
  padding: 8px 16px;
  background: #8b5cf6;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  margin-left: 5px;
}

.btn-refund:hover {
  background: #7c3aed;
  transform: scale(1.05);
}

.btn-track {
  padding: 8px 16px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  margin-left: 5px;
  text-decoration: none;
  display: inline-block;
}

.btn-track:hover {
  background: #059669;
  transform: scale(1.05);
}

.tracking-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.tracking-link:hover {
  color: #5568d3;
  text-decoration: underline;
}

.no-data {
  text-align: center;
  padding: 40px;
  color: #a0a0a0;
  font-style: italic;
}

@media (max-width: 768px) {
  .admin-header h1 {
    font-size: 1.5rem;
  }

  .tabs {
    overflow-x: auto;
  }

  .tab {
    padding: 10px 20px;
    font-size: 0.9rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: scroll;
  }
}
</style>
