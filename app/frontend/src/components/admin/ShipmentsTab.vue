<script setup>
defineProps({ shipments: { type: Array, required: true } });

function statusColor(s) {
  return { pending: '#f59e0b', in_transit: '#3b82f6', out_for_delivery: '#8b5cf6', delivered: '#10b981', failed: '#ef4444', returned: '#6b7280' }[s] || '#6b7280';
}
function formatDate(d) {
  if (!d) return 'N/A';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Address</th><th>Tracking</th><th>Carrier</th><th>Status</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="s in shipments" :key="s.id">
          <td data-label="Order #"><strong>{{ s.order_number }}</strong></td>
          <td data-label="Customer">{{ s.email }}</td>
          <td data-label="Address">{{ s.street }} {{ s.house_number }}, {{ s.zip_code }} {{ s.city }}</td>
          <td data-label="Tracking">
            <a v-if="s.tracking_url" :href="s.tracking_url" target="_blank" class="tracking-link">{{ s.tracking_number }}</a>
            <span v-else>{{ s.tracking_number }}</span>
          </td>
          <td data-label="Carrier">{{ s.carrier }}</td>
          <td data-label="Status">
            <span class="status-badge" :style="{ backgroundColor: statusColor(s.delivery_status) }">{{ s.delivery_status }}</span>
          </td>
          <td data-label="Date">{{ formatDate(s.created_at) }}</td>
          <td data-label="Actions" class="actions-cell">
            <a :href="`http://localhost/api/shipments/${s.id}/label`" target="_blank" class="btn-action btn-view">📄 Label</a>
            <a v-if="s.tracking_url" :href="s.tracking_url" target="_blank" class="btn-action btn-track">📍 Track</a>
          </td>
        </tr>
        <tr v-if="shipments.length === 0"><td colspan="8" class="no-data">No shipments found</td></tr>
      </tbody>
    </table>
  </div>
</template>

<style src="./admin-table.css" />