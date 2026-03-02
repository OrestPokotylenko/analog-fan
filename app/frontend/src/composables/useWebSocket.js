import { onMounted, onUnmounted } from 'vue';
import WebSocketService from '../services/WebSocketService';

export function useWebSocket() {
  let messageHandler = null;

  onMounted(() => {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (user && user.userId && !WebSocketService.isConnected()) {
      WebSocketService.connect(user.userId);
    }
  });

  onUnmounted(() => {
    if (messageHandler) {
      WebSocketService.removeMessageHandler(messageHandler);
      messageHandler = null;
    }
  });

  return {
    sendMessage: (conversationId, senderId, messageText) => {
      WebSocketService.sendMessage(conversationId, senderId, messageText);
    },
    onMessage: (handler) => {
      messageHandler = handler;
      WebSocketService.onMessage(handler);
    },
    removeMessageHandler: (handler) => {
      WebSocketService.removeMessageHandler(handler);
      if (messageHandler === handler) messageHandler = null;
    },
    disconnect: () => {
      WebSocketService.disconnect();
    },
    isConnected: () => {
      return WebSocketService.isConnected();
    }
  };
}
