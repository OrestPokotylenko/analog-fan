<script setup>
import { ref, onMounted, inject, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../../services/axiosConfig';
import OrderService from '../../services/OrderService';
import { isTokenExpired, clearAuthState } from '../../services/authHelpers';

import AdminHeader from '../../components/admin/AdminHeader.vue';
import AdminTabs from '../../components/admin/AdminTabs.vue';
import AdminStats from '../../components/admin/AdminStats.vue';
import AdminModal from '../../components/admin/AdminModal.vue';
import UsersTab from '../../components/admin/UsersTab.vue';
import ItemsTab from '../../components/admin/ItemsTab.vue';
import OrdersTab from '../../components/admin/OrdersTab.vue';
import ShipmentsTab from '../../components/admin/ShipmentsTab.vue';
import ProductTypesTab from '../../components/admin/ProductTypesTab.vue';

const router = useRouter();
const $auth = inject('$auth');

const users = ref([]);
const items = ref([]);
const productTypes = ref([]);
const orders = ref([]);
const shipments = ref([]);
const stats = ref({ totalUsers: 0, totalItems: 0, totalProductTypes: 0, totalOrders: 0 });
const isLoading = ref(true);
const activeTab = ref('dashboard');
const isDeletingUser = ref(false);
const isDeletingItem = ref(false);
const isDeletingType = ref(false);
const isSavingType = ref(false);
const isConfirming = ref(false);

const showTypeModal = ref(false);
const editingType = ref(null);
const typeFormName = ref('');
const typeFormImage = ref(null);
const imagePreview = ref(null);
const typeFormSupportsGenre = ref(false);

const showConfirmModal = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmAction = ref(null);

const showErrorModal = ref(false);
const errorTitle = ref('');
const errorMessage = ref('');

const tabs = computed(() => [
  { key: 'dashboard', label: 'Dashboard', count: null },
  { key: 'users',     label: 'Users',     count: users.value.length },
  { key: 'items',     label: 'Items',     count: items.value.length },
  { key: 'orders',    label: 'Orders',    count: orders.value.length },
  { key: 'types',     label: 'Types',     count: productTypes.value.length },
  { key: 'shipments', label: 'Shipments', count: shipments.value.length },
]);

onMounted(async () => {
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) { clearAuthState($auth); router.push('/login'); return; }
  if (!$auth.user || $auth.user.role !== 'admin') { router.push('/'); return; }
  await loadData();
});

async function loadData() {
  try {
    isLoading.value = true;
    const [usersRes, itemsRes, typesRes, ordersRes, shipmentsRes] = await Promise.all([
      axios.get('/users'), axios.get('/items'), axios.get('/product-types'),
      OrderService.getAllOrders(), axios.get('/shipments/admin'),
    ]);
    users.value = usersRes.data;
    items.value = itemsRes.data;
    productTypes.value = typesRes.data;
    orders.value = ordersRes.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    shipments.value = shipmentsRes.data.data || [];
    stats.value = { totalUsers: users.value.length, totalItems: items.value.length, totalProductTypes: productTypes.value.length, totalOrders: orders.value.length, totalShipments: shipments.value.length };
  } catch (e) { console.error('Failed to load admin data:', e); }
  finally { isLoading.value = false; }
}

function promptConfirm(title, message, action) {
  confirmTitle.value = title; confirmMessage.value = message; confirmAction.value = action;
  showConfirmModal.value = true;
}

async function runConfirm() {
  if (isConfirming.value || !confirmAction.value) return;
  try { isConfirming.value = true; await confirmAction.value(); }
  finally { isConfirming.value = false; showConfirmModal.value = false; confirmAction.value = null; }
}

function deleteUser(userId) {
  promptConfirm('Delete User', 'Delete this user and all their items? This cannot be undone.', async () => {
    try { isDeletingUser.value = true; await axios.delete(`/users/${userId}`); await loadData(); }
    finally { isDeletingUser.value = false; }
  });
}

function deleteItem(itemId) {
  promptConfirm('Delete Item', 'Delete this item? This cannot be undone.', async () => {
    try { isDeletingItem.value = true; await axios.delete(`/items/${itemId}`); await loadData(); }
    finally { isDeletingItem.value = false; }
  });
}

function deleteProductType(typeId) {
  promptConfirm('Delete Product Type', 'Delete this product type? This cannot be undone.', async () => {
    try {
      isDeletingType.value = true;
      const res = await axios.delete(`/product-types/${typeId}`);
      if (res.status === 409) { errorTitle.value = 'Cannot Delete'; errorMessage.value = 'Type is in use by items.'; showErrorModal.value = true; }
      else await loadData();
    } catch { errorTitle.value = 'Delete Failed'; errorMessage.value = 'An error occurred.'; showErrorModal.value = true; }
    finally { isDeletingType.value = false; }
  });
}

function cancelOrder(order) {
  promptConfirm('Cancel Order', `Cancel order #${order.orderNumber}?`, async () => {
    await OrderService.cancelOrder(order.id); await loadData();
  });
}

function refundOrder(order) {
  promptConfirm('Refund Order', `Refund order #${order.orderNumber}?`, async () => {
    await OrderService.refundOrder(order.id); await loadData();
  });
}

function openCreateTypeModal() {
  editingType.value = null;
  typeFormName.value = '';
  typeFormImage.value = null;
  imagePreview.value = null;
  typeFormSupportsGenre.value = false;
  showTypeModal.value = true;
}
function openEditTypeModal(type) {
  editingType.value = type;
  typeFormName.value = type.typeName;
  typeFormImage.value = null;
  imagePreview.value = type.imageUrl || null;
  typeFormSupportsGenre.value = !!type.supportsGenre;
  showTypeModal.value = true;
}
function closeTypeModal() {
  showTypeModal.value = false;
  editingType.value = null;
  typeFormName.value = '';
  typeFormImage.value = null;
  imagePreview.value = null;
  typeFormSupportsGenre.value = false;
}

function handleImageChange(e) {
  const file = e.target.files[0];
  if (file) { typeFormImage.value = file; const r = new FileReader(); r.onload = ev => { imagePreview.value = ev.target.result; }; r.readAsDataURL(file); }
}

async function saveProductType() {
  if (!typeFormName.value.trim() || isSavingType.value) return;
  try {
    isSavingType.value = true;
    const fd = new FormData();
    fd.append('name', typeFormName.value);
    fd.append('supports_genre', typeFormSupportsGenre.value ? '1' : '0');
    if (typeFormImage.value) fd.append('image', typeFormImage.value);
    const cfg = { headers: { 'Content-Type': 'multipart/form-data' } };
    if (editingType.value) await axios.put(`/product-types/${editingType.value.productTypeId}`, fd, cfg);
    else await axios.post('/product-types', fd, cfg);
    closeTypeModal(); await loadData();
  } catch (e) { console.error(e); } finally { isSavingType.value = false; }
}

function logout() {
  localStorage.removeItem('jwtToken'); localStorage.removeItem('user');
  $auth.isLoggedIn = false; $auth.token = null; $auth.user = null;
  router.push('/login');
}
</script>

<template>
  <div class="admin-page">
    <AdminHeader @logout="logout" />

    <div v-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>Loading admin data...</p>
    </div>

    <template v-else>
      <AdminTabs :tabs="tabs" :active-tab="activeTab" @update:active-tab="activeTab = $event" />

      <div class="tab-content">
        <AdminStats   v-if="activeTab === 'dashboard'" :stats="stats" @navigate="activeTab = $event" />
        <UsersTab     v-if="activeTab === 'users'"     :users="users" :is-deleting="isDeletingUser" @delete="deleteUser" />
        <ItemsTab     v-if="activeTab === 'items'"     :items="items" :is-deleting="isDeletingItem" @delete="deleteItem" />
        <OrdersTab    v-if="activeTab === 'orders'"    :orders="orders" @view="router.push(`/orders/${$event}`)" @cancel="cancelOrder" @refund="refundOrder" />
        <ShipmentsTab v-if="activeTab === 'shipments'" :shipments="shipments" />
        <ProductTypesTab v-if="activeTab === 'types'" :product-types="productTypes" :is-deleting="isDeletingType" :is-saving="isSavingType" @add="openCreateTypeModal" @edit="openEditTypeModal" @delete="deleteProductType" />
      </div>
    </template>

    <!-- Product Type Modal -->
    <AdminModal :show="showTypeModal" :title="editingType ? 'Edit Product Type' : 'Add Product Type'" @close="closeTypeModal">
      <div class="form-group">
        <label>Type Name</label>
        <input v-model="typeFormName" type="text" placeholder="Enter name" class="form-input" />
      </div>
      <div class="form-group">
        <label>Type Image</label>
        <input type="file" accept="image/*" class="form-input" @change="handleImageChange" />
        <div v-if="imagePreview" class="image-preview"><img :src="imagePreview" alt="Preview" /></div>
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" v-model="typeFormSupportsGenre" /> Supports Genre
        </label>
      </div>
      <template #footer>
        <button class="btn-secondary" :disabled="isSavingType" @click="closeTypeModal">Cancel</button>
        <button class="btn-primary" :disabled="isSavingType" @click="saveProductType">{{ isSavingType ? 'Saving...' : (editingType ? 'Update' : 'Create') }}</button>
      </template>
    </AdminModal>

    <!-- Confirm Modal -->
    <AdminModal :show="showConfirmModal" :title="confirmTitle" max-width="420px" @close="showConfirmModal = false">
      <div class="modal-center">
        <div class="modal-icon">⚠️</div>
        <p>{{ confirmMessage }}</p>
      </div>
      <template #footer>
        <button class="btn-secondary" :disabled="isConfirming" @click="showConfirmModal = false">Cancel</button>
        <button class="btn-danger" :disabled="isConfirming" @click="runConfirm">{{ isConfirming ? 'Processing...' : 'Confirm' }}</button>
      </template>
    </AdminModal>

    <!-- Error Modal -->
    <AdminModal :show="showErrorModal" :title="errorTitle" max-width="420px" @close="showErrorModal = false">
      <div class="modal-center">
        <div class="modal-icon">🚫</div>
        <p>{{ errorMessage }}</p>
      </div>
      <template #footer>
        <button class="btn-primary" @click="showErrorModal = false">Got it</button>
      </template>
    </AdminModal>
  </div>
</template>

<style scoped>
.admin-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1e 100%);
  padding: 20px;
  color: white;
}
@media (max-width: 600px) { .admin-page { padding: 12px; } }

.loading { display: flex; flex-direction: column; align-items: center; padding: 80px 20px; color: #a0a0a0; }
.spinner { width: 48px; height: 48px; border: 4px solid rgba(255,255,255,0.1); border-top-color: #667eea; border-radius: 50%; animation: spin 0.9s linear infinite; margin-bottom: 16px; }
@keyframes spin { to { transform: rotate(360deg); } }

.tab-content { animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 6px; color: #a0a0a0; font-weight: 600; font-size: 0.9rem; }
.form-input { width: 100%; padding: 11px 14px; box-sizing: border-box; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.95rem; }
.form-input:focus { outline: none; border-color: #667eea; }
.image-preview { margin-top: 12px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); max-width: 280px; }
.image-preview img { width: 100%; height: auto; display: block; }

.modal-center { text-align: center; }
.modal-center p { color: #e0e0e0; line-height: 1.6; margin: 0; }
.modal-icon { font-size: 3.5rem; margin-bottom: 16px; }

.btn-primary { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
.btn-primary:hover { background: #5568d3; }
.btn-secondary { padding: 10px 20px; background: rgba(255,255,255,0.08); color: white; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-secondary:hover { background: rgba(255,255,255,0.14); }
.btn-danger { padding: 10px 20px; background: #e94560; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-danger:hover { background: #ff6b7a; }
</style>