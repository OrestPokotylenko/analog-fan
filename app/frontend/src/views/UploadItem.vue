<script setup>
import Header from '../components/Header.vue';
import UploadItemForm from '../components/UploadItemForm.vue';
</script>

<template>
    <Header />
    <div class="content justify-content-center">
        <UploadItemForm />
    </div>
</template>

<style scoped>
.content {
  margin: 100px auto;
  padding: 20px;
  border-radius: 5px;
}
</style>