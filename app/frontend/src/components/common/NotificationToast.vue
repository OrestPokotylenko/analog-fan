<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const notifications = ref([]);
const router = useRouter();

function showNotification(conversationId, senderUsername, messageText, itemTitle) {
  const notification = {
    id: Date.now(),
    conversationId,
    senderUsername,
    messageText,
    itemTitle,
    show: true
  };

  notifications.value.push(notification);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    removeNotification(notification.id);
  }, 5000);
}

function removeNotification(id) {
  const index = notifications.value.findIndex(n => n.id === id);
  if (index > -1) {
    notifications.value[index].show = false;
    setTimeout(() => {
      notifications.value.splice(index, 1);
    }, 300);
  }
}

function handleClick(notification) {
  router.push(`/messages/${notification.conversationId}`);
  removeNotification(notification.id);
}

// Expose the showNotification method so parent can call it
defineExpose({
  showNotification
});
</script>

<template>
  <div class="notification-container">
    <transition-group name="notification">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="['notification-toast', { 'notification-exit': !notification.show }]"
        @click="handleClick(notification)"
      >
        <div class="notification-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
        <div class="notification-content">
          <div class="notification-header">
            <strong>{{ notification.senderUsername }}</strong>
            <span v-if="notification.itemTitle" class="notification-item">· {{ notification.itemTitle }}</span>
          </div>
          <div class="notification-message">{{ notification.messageText }}</div>
          <div class="notification-action">Click to view conversation</div>
        </div>
        <button class="notification-close" @click.stop="removeNotification(notification.id)">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.notification-container {
  position: fixed;
  top: 80px;
  right: 20px;
  z-index: 9999;
  pointer-events: none;
}

.notification-toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid #e94560;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  min-width: 350px;
  max-width: 400px;
  box-shadow: 0 8px 32px rgba(233, 69, 96, 0.3);
  cursor: pointer;
  pointer-events: auto;
  transition: all 0.3s ease;
}

.notification-toast:hover {
  transform: translateX(-5px);
  box-shadow: 0 12px 40px rgba(233, 69, 96, 0.4);
  border-color: #ff6b7a;
}

.notification-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  background: rgba(233, 69, 96, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e94560;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
  color: #ffffff;
  font-size: 14px;
}

.notification-header strong {
  color: #e94560;
  font-weight: 600;
}

.notification-item {
  color: #a0a0c0;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-message {
  color: #ffffff;
  font-size: 14px;
  line-height: 1.4;
  margin-bottom: 6px;
  max-height: 60px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}

.notification-action {
  color: #e94560;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.notification-close {
  flex-shrink: 0;
  background: transparent;
  border: none;
  color: #a0a0c0;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.notification-close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

/* Animation */
.notification-enter-active {
  animation: slideIn 0.3s ease-out;
}

.notification-exit {
  animation: slideOut 0.3s ease-in;
}

.notification-leave-active {
  animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(400px);
    opacity: 0;
  }
}

@media (max-width: 768px) {
  .notification-container {
    right: 10px;
    left: 10px;
  }

  .notification-toast {
    min-width: unset;
    max-width: unset;
    width: 100%;
  }
}
</style>
