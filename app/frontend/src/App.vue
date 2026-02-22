<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import NotificationToast from './components/common/NotificationToast.vue';
import WebSocketService from './services/WebSocketService';
import MessageService from './services/MessageService';

const router = useRouter();
const route = useRoute();
const notificationToast = ref(null);
const user = computed(() => JSON.parse(localStorage.getItem('user')));

// WebSocket message handler
const handleWebSocketMessage = async (data) => {
  if (data.type === 'new_message') {
    const conversationId = data.conversationId;
    const message = data.message;
    
    // Skip if we're the sender
    if (message.senderId === user.value?.userId) {
      return;
    }
    
    // Don't show notifications if we're on the messages page at all (list or specific conversation)
    const isOnMessagesPage = route.path.startsWith('/messages');
    
    // Only show notification if NOT on messages page
    if (!isOnMessagesPage) {
      // Get conversation details for better notification
      let conversationDetails = null;
      try {
        conversationDetails = await MessageService.getConversation(conversationId);
      } catch (error) {
        console.error('Failed to get conversation details:', error);
      }
      
      const senderUsername = message.senderUsername || 'Someone';
      const messageText = message.messageText || 'New message';
      const itemTitle = conversationDetails?.itemTitle || '';
      
      // Show in-app toast notification
      if (notificationToast.value) {
        notificationToast.value.showNotification(
          conversationId,
          senderUsername,
          messageText,
          itemTitle
        );
      }
      
      // Show browser notification
      showBrowserNotification(conversationId, senderUsername, messageText, itemTitle);
    }
    
    // Trigger header unread count update
    window.dispatchEvent(new CustomEvent('messages-updated'));
  }
};

function showBrowserNotification(conversationId, senderUsername, messageText, itemTitle) {
  if ('Notification' in window && Notification.permission === 'granted') {
    const title = itemTitle ? `${senderUsername} (${itemTitle})` : senderUsername;
    const notification = new Notification('New Message from ' + title, {
      body: messageText,
      icon: '/favicon.ico',
      tag: `conversation-${conversationId}`,
      requireInteraction: false,
      data: { conversationId } // Store conversationId in notification
    });

    notification.onclick = () => {
      window.focus();
      router.push(`/messages/${conversationId}`);
      notification.close();
    };

    // Auto close after 5 seconds
    setTimeout(() => {
      notification.close();
    }, 5000);
  }
}

onMounted(() => {
  // Request notification permission if not already granted
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
  
  // Initialize WebSocket if user is logged in
  if (user.value?.userId) {
    WebSocketService.connect(user.value.userId);
    WebSocketService.onMessage(handleWebSocketMessage);
  }
});

onUnmounted(() => {
  WebSocketService.removeMessageHandler(handleWebSocketMessage);
});
</script>

<template>
  <NotificationToast ref="notificationToast" />
  <router-view />
</template>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

#app {
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Source Sans Pro',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  display: block;
  place-items: unset;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
}

/* Global Utility Classes */
.text-center {
  text-align: center;
}

.content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
  width: 12px;
}

::-webkit-scrollbar-track {
  background: #0f0f1e;
}

::-webkit-scrollbar-thumb {
  background: #e94560;
  border-radius: 6px;
}

::-webkit-scrollbar-thumb:hover {
  background: #ff6b7a;
}
</style>