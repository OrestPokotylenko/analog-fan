<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import EditItem from '../../components/items/EditItem.vue';
</script>

<template>
  <PageLayout>
    <div class="container container-md">
      <EditItem />
    </div>
  </PageLayout>
</template>

<style scoped>
.container {
  padding: 40px 30px;
}

@media (max-width: 768px) {
  .container { padding: 30px 20px; }
}

@media (max-width: 480px) {
  .container { padding: 20px 15px; }
}
</style>
