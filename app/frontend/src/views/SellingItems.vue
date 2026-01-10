<script setup>
import Header from '../components/Header.vue';
import MyItemList from '../components/MyItemsList.vue';
</script>

<template>
    <Header />
    <div class="content d-flex">
        <RouterLink to="/my-items/upload" class="btn btn-primary ms-auto">Upload Item</RouterLink>
        <MyItemList />
    </div>
</template>

<style scoped></style>