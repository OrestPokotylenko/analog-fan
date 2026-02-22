<script setup>
const props = defineProps({
  orders: { type: Array, required: true }
});
defineEmits(['view', 'cancel', 'refund']);

const statusColors = { pending: '#f59e0b', processing: '#3b82f6', shipped: '#8b5cf6', delivered: '#10b981', cancelled: '#ef4444' };
const paymentStatusColors = { pending: '#f59e0b', paid: '#10b981', failed: '#ef4444', refunded: '#6b7280' };
const statusLabels = { pending: '⏳ Pending', processing: '⚙️ Processing', shipped: '🚚 Shipped', delivered: '✅ Delivered', cancelled: '❌ Cancelled' };
const paymentLabels = { pending: '⏳ Pending', paid: '✅ Paid', failed: '❌ Failed', refunded: '↩️ Refunded' };

function formatDate(d) {
  if (!d) return 'N/A';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr><th>Order #</th><th>Email</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td data-label="Order #"><strong>{{ order.orderNumber }}</strong></td>
          <td data-label="Email">{{ order.email }}</td>
          <td data-label="Total">€{{ parseFloat(order.totalAmount).toFixed(2) }}</td>
          <td data-label="Status">
            <span class="status-badge" :style="{ backgroundColor: statusColors[order.status] }">{{ statusLabels[order.status] }}</span>
          </td>
          <td data-label="Payment">
            <span class="status-badge" :style="{ backgroundColor: paymentStatusColors[order.paymentStatus] }">{{ paymentLabels[order.paymentStatus] }}</span>
          </td>
          <td data-label="Date">{{ formatDate(order.createdAt) }}</td>
          <td data-label="Actions" class="actions-cell">
            <button class="btn-action btn-view" @click="$emit('view', order.id)">View</button>
            <button v-if="order.status !== 'cancelled' && order.status !== 'delivered'" class="btn-action btn-cancel" @click="$emit('cancel', order)">Cancel</button>
            <button v-if="order.paymentStatus === 'paid'" class="btn-action btn-refund" @click="$emit('refund', order)">Refund</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style src="./admin-table.css" />