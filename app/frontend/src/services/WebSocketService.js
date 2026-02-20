class WebSocketService {
  constructor() {
    this.ws = null;
    this.url = 'ws://localhost:8081';
    this.reconnectInterval = 3000;
    this.reconnectTimer = null;
    this.messageHandlers = [];
    this.isAuthenticated = false;
    this.userId = null;
  }

  connect(userId) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      console.log('WebSocket already connected');
      return;
    }

    this.userId = userId;
    this.ws = new WebSocket(this.url);

    this.ws.onopen = () => {
      console.log('WebSocket connected');
      this.authenticate();
      if (this.reconnectTimer) {
        clearTimeout(this.reconnectTimer);
        this.reconnectTimer = null;
      }
    };

    this.ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        this.handleMessage(data);
      } catch (error) {
        console.error('Failed to parse WebSocket message:', error);
      }
    };

    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    this.ws.onclose = () => {
      console.log('WebSocket disconnected');
      this.isAuthenticated = false;
      this.scheduleReconnect();
    };
  }

  authenticate() {
    if (this.ws && this.ws.readyState === WebSocket.OPEN && this.userId) {
      this.ws.send(JSON.stringify({
        type: 'auth',
        userId: this.userId
      }));
      this.isAuthenticated = true;
      console.log('WebSocket authenticated for user:', this.userId);
    }
  }

  scheduleReconnect() {
    if (this.reconnectTimer) {
      return;
    }
    console.log('Scheduling reconnect in', this.reconnectInterval, 'ms');
    this.reconnectTimer = setTimeout(() => {
      if (this.userId) {
        console.log('Attempting to reconnect...');
        this.connect(this.userId);
      }
    }, this.reconnectInterval);
  }

  sendMessage(conversationId, senderId, messageText) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({
        type: 'send_message',
        conversationId,
        senderId,
        messageText
      }));
    } else {
      console.error('WebSocket not connected');
      throw new Error('WebSocket not connected');
    }
  }

  onMessage(handler) {
    this.messageHandlers.push(handler);
  }

  removeMessageHandler(handler) {
    this.messageHandlers = this.messageHandlers.filter(h => h !== handler);
  }

  handleMessage(data) {
    this.messageHandlers.forEach(handler => {
      try {
        handler(data);
      } catch (error) {
        console.error('Error in message handler:', error);
      }
    });
  }

  disconnect() {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
    this.isAuthenticated = false;
    this.messageHandlers = [];
  }

  isConnected() {
    return this.ws && this.ws.readyState === WebSocket.OPEN && this.isAuthenticated;
  }
}

// Create singleton instance
const webSocketService = new WebSocketService();

export default webSocketService;
