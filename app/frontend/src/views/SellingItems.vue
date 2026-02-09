<script setup>
import Header from '../components/Header.vue';
import MyItemList from '../components/MyItemsList.vue';
</script>

<template>
    <Header />
    <div class="content d-flex">
        <RouterLink to="/my-items/upload" class="upload-btn ms-auto">
            <span class="btn-icon">📤</span>
            <span>Upload Item</span>
        </RouterLink>
        <MyItemList />
    </div>
</template>

<style scoped>
.content {
    padding: 20px;
}

.d-flex {
    display: flex;
}

.ms-auto {
    margin-left: auto;
    margin-bottom: 20px;
}

.upload-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 32px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    margin-bottom: 30px;
}

.upload-btn:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
    color: white;
}

.upload-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-icon {
    font-size: 1.2rem;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}
</style>