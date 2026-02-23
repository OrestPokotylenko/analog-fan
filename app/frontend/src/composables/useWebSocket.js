import { onMounted } from 'vue';
import WebSocketService from '../services/WebSocketService';

export function useWebSocket() {
  onMounted(() => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.userId && !WebSocketService.isConnected()) {
      WebSocketService.connect(user.userId);
    }
  });

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
