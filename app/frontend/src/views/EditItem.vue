<script setup>
import Header from '../components/Header.vue';
import EditItem from '../components/EditItem.vue';
</script>

<template>
    <Header />
    <div class="edit-page">
        <div class="container">
            <EditItem />
        </div>
    </div>
</template>

<style scoped>
.edit-page {
    padding-top: 70px;
    background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
    min-height: 100vh;
    padding-bottom: 60px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 30px;
}

@media (max-width: 768px) {
    .container {
        padding: 30px 20px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 20px 15px;
    }
}
</style>