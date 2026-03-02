<script setup>
import { ref, onMounted, onUnmounted, watch, inject } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import NotificationToast from './components/common/NotificationToast.vue';
import WebSocketService from './services/WebSocketService';
import MessageService from './services/MessageService';

const router = useRouter();
const route = useRoute();
const notificationToast = ref(null);
const $auth = inject('$auth');
let wsHandlerRegistered = false;

const handleWebSocketMessage = async (data) => {
  if (data.type === 'new_message') {
    const conversationId = data.conversationId;
    const message = data.message;
    
    if (message.senderId == $auth.user?.userId) {
      return;
    }
    
    const isOnMessagesPage = route.path.startsWith('/messages');
    
    if (!isOnMessagesPage) {
      let conversationDetails = null;
      try {
        conversationDetails = await MessageService.getConversation(conversationId);
      } catch (error) {
        console.error('Failed to get conversation details:', error);
      }
      
      const senderUsername = message.senderUsername || 'Someone';
      const messageText = message.messageText || 'New message';
      const itemTitle = conversationDetails?.itemTitle || '';
      
      if (notificationToast.value) {
        notificationToast.value.showNotification(
          conversationId,
          senderUsername,
          messageText,
          itemTitle
        );
      }
      
      showBrowserNotification(conversationId, senderUsername, messageText, itemTitle);
    }
    
    window.dispatchEvent(new CustomEvent('messages-updated'));
  }
};

function connectWebSocket() {
  if ($auth.user?.userId && !wsHandlerRegistered) {
    if (!WebSocketService.isConnected()) {
      WebSocketService.connect($auth.user.userId);
    }
    WebSocketService.onMessage(handleWebSocketMessage);
    wsHandlerRegistered = true;
  }
}

function showBrowserNotification(conversationId, senderUsername, messageText, itemTitle) {
  if ('Notification' in window && Notification.permission === 'granted') {
    const title = itemTitle ? `${senderUsername} (${itemTitle})` : senderUsername;
    const notification = new Notification('New Message from ' + title, {
      body: messageText,
      icon: '/favicon.ico',
      tag: `conversation-${conversationId}`,
      requireInteraction: false,
      data: { conversationId }
    });

    notification.onclick = () => {
      window.focus();
      router.push(`/messages/${conversationId}`);
      notification.close();
    };

    setTimeout(() => {
      notification.close();
    }, 5000);
  }
}

onMounted(() => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
  
  connectWebSocket();
});

watch(
  () => $auth.isLoggedIn,
  (loggedIn) => {
    if (loggedIn) {
      connectWebSocket();
    }
  }
);

onUnmounted(() => {
  WebSocketService.removeMessageHandler(handleWebSocketMessage);
  wsHandlerRegistered = false;
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