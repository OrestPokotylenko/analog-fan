<script setup>
import Header from '../../components/layout/Header.vue';
import MyItemList from '../../components/items/MyItemsList.vue';
</script>

<template>
  <Header />
  <div class="page">
    <section class="content">
      <div class="container">
        <div class="section-header">
          <div class="section-titles">
            <h2 class="section-title">My Items</h2>
            <p class="section-subtitle">Manage your listed items</p>
          </div>
          <RouterLink to="/my-items/upload" class="upload-btn">
            <span class="btn-icon">📤</span>
            <span>Upload Item</span>
          </RouterLink>
        </div>
        <MyItemList />
      </div>
    </section>
  </div>
</template>

<style scoped>
.page {
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
  padding-top: 66px;
}

.content {
  padding: 60px 30px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 40px;
  gap: 20px;
  flex-wrap: wrap;
}

.section-titles {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.section-title {
  font-size: 2.8em;
  color: white;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.5px;
}

.section-subtitle {
  font-size: 1.1em;
  color: #b0b0b0;
  margin: 0;
}

.upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #e94560;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 4px 14px rgba(233, 69, 96, 0.35);
  flex-shrink: 0;
}

.upload-btn:hover {
  background: #ff6b7a;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.45);
}

.upload-btn:active {
  transform: translateY(0);
}

.btn-icon {
  font-size: 1.1em;
}

@media (max-width: 768px) {
  .content { padding: 24px 20px; }
  .section-title { font-size: 2em; }
  .section-header { margin-bottom: 24px; }
}

@media (max-width: 480px) {
  .content { padding: 16px 16px; }
  .section-title { font-size: 1.6em; }
  .section-subtitle { font-size: 1em; }
  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .upload-btn { width: 100%; justify-content: center; }
}
</style>