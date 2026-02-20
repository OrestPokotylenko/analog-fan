import { ref, onMounted, onUnmounted } from 'vue';
import WebSocketService from '../services/WebSocketService';
import { useRouter } from 'vue-router';

export function useWebSocket() {
  const router = useRouter();
  const user = JSON.parse(localStorage.getItem('user'));
  
  // Initialize WebSocket connection if user is logged in
  if (user && user.userId) {
    if (!WebSocketService.isConnected()) {
      WebSocketService.connect(user.userId);
    }
  }

  return {
    sendMessage: (conversationId, senderId, messageText) => {
      WebSocketService.sendMessage(conversationId, senderId, messageText);
    },
    onMessage: (handler) => {
      WebSocketService.onMessage(handler);
    },
    removeMessageHandler: (handler) => {
      WebSocketService.removeMessageHandler(handler);
    },
    disconnect: () => {
      WebSocketService.disconnect();
    },
    isConnected: () => {
      return WebSocketService.isConnected();
    }
  };
}
