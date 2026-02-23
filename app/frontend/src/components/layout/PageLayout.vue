<script setup>
import Header from './Header.vue';

defineProps({
  paddingBottom: {
    type: String,
    default: '60px'
  }
});
</script>

<template>
  <Header />
  <div class="page-layout" :style="{ paddingBottom }">
    <slot />
  </div>
</template>

<style scoped>
.page-layout {
  padding-top: 70px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
}
</style>
