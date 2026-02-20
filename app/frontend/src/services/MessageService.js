import axios from './axiosConfig';

const MessageService = {
  /**
   * Get or create a conversation
   */
  async getOrCreateConversation(itemId, buyerId, sellerId) {
    try {
      const response = await axios.post('/conversations', {
        itemId,
        buyerId,
        sellerId
      });
      return response.data;
    } catch (error) {
      console.error('Failed to get/create conversation:', error);
      throw error;
    }
  },

  /**
   * Get all conversations for a user
   */
  async getUserConversations(userId) {
    try {
      const response = await axios.get(`/conversations/user/${userId}`);
      return response.data;
    } catch (error) {
      console.error('Failed to fetch conversations:', error);
      throw error;
    }
  },

  /**
   * Get a specific conversation
   */
  async getConversation(conversationId) {
    try {
      const response = await axios.get(`/conversations/${conversationId}`);
      return response.data;
    } catch (error) {
      console.error('Failed to fetch conversation:', error);
      throw error;
    }
  },

  /**
   * Get messages for a conversation
   */
  async getConversationMessages(conversationId) {
    try {
      const response = await axios.get(`/conversations/${conversationId}/messages`);
      return response.data;
    } catch (error) {
      console.error('Failed to fetch messages:', error);
      throw error;
    }
  },

  /**
   * Send a message
   */
  async sendMessage(conversationId, senderId, messageText) {
    try {
      const response = await axios.post('/messages', {
        conversationId,
        senderId,
        messageText
      });
      return response.data;
    } catch (error) {
      console.error('Failed to send message:', error);
      throw error;
    }
  },

  /**
   * Mark messages as read
   */
  async markMessagesAsRead(conversationId, userId) {
    try {
      const response = await axios.put(`/conversations/${conversationId}/read`, {
        userId
      });
      return response.data;
    } catch (error) {
      console.error('Failed to mark messages as read:', error);
      throw error;
    }
  },

  /**
   * Get unread message count for a user
   */
  async getUnreadCount(userId) {
    try {
      const response = await axios.get(`/conversations/user/${userId}/unread-count`);
      return response.data.count || 0;
    } catch (error) {
      console.error('Failed to fetch unread count:', error);
      return 0;
    }
  }
};

export default MessageService;
