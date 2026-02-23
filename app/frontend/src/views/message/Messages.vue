<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Header from '../../components/layout/Header.vue';
import ConversationItem from '../../components/message/ConversationItem.vue';
import MessageBubble from '../../components/message/MessageBubble.vue';
import MessageService from '../../services/MessageService';
import WebSocketService from '../../services/WebSocketService';

const route = useRoute();
const router = useRouter();
const user = JSON.parse(localStorage.getItem('user'));

const conversations = ref([]);
const selectedConversationId = ref(null);
const selectedConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const isLoading = ref(false);
const isSending = ref(false);
const sendError = ref('');
const messagesEndRef = ref(null);
const previousMessageCount = ref(0);

// For new conversation (not yet created)
const newConversationData = ref(null);
const isNewConversation = ref(false);

// WebSocket message handler
const handleWebSocketMessage = (data) => {
  if (data.type === 'new_message') {
    const conversationId = data.conversationId;
    
    // If this is the currently selected conversation, add the message
    if (conversationId === selectedConversationId.value) {
      messages.value.push(data.message);
      setTimeout(scrollToBottom, 50);
      
      // Mark as read if we're viewing it
      if (!document.hidden) {
        MessageService.markMessagesAsRead(conversationId, user.userId);
      }
    }
    
    // Refresh conversations to update last message and unread count
    loadConversations();
  }
};

onMounted(async () => {
  if (!user || !user.userId) {
    router.push('/login');
    return;
  }

  // Connect to WebSocket if not already connected
  if (!WebSocketService.isConnected()) {
    WebSocketService.connect(user.userId);
  }
  WebSocketService.onMessage(handleWebSocketMessage);

  await loadConversations();

  // Check if this is a new conversation
  if (route.params.conversationId === 'new' && route.query.itemId && route.query.sellerId) {
    // Setup new conversation mode
    isNewConversation.value = true;
    newConversationData.value = {
      itemId: parseInt(route.query.itemId),
      sellerId: parseInt(route.query.sellerId),
      sellerUsername: route.query.sellerUsername || 'Seller',
      itemTitle: route.query.itemTitle || 'Item'
    };
    
    // Check if a conversation already exists for this item/seller combo
    const existingConversation = conversations.value.find(c => 
      c.itemId === newConversationData.value.itemId && 
      ((c.buyerId === user.userId && c.sellerId === newConversationData.value.sellerId) ||
       (c.sellerId === user.userId && c.buyerId === newConversationData.value.sellerId))
    );
    
    if (existingConversation) {
      // Redirect to existing conversation
      router.replace(`/messages/${existingConversation.conversationId}`);
      return;
    }
  } else if (route.params.conversationId && route.params.conversationId !== 'new') {
    // Load existing conversation
    selectedConversationId.value = parseInt(route.params.conversationId);
    await loadConversation(selectedConversationId.value);
  }
});

onUnmounted(() => {
  WebSocketService.removeMessageHandler(handleWebSocketMessage);
});

watch(() => route.params.conversationId, (newId) => {
  if (newId && newId !== 'new') {
    isNewConversation.value = false;
    newConversationData.value = null;
    selectedConversationId.value = parseInt(newId);
    loadConversation(selectedConversationId.value);
  }
});

async function loadConversations() {
  try {
    conversations.value = await MessageService.getUserConversations(user.userId);
  } catch (error) {
    console.error('Failed to load conversations:', error);
  }
}

async function loadConversation(conversationId) {
  try {
    isLoading.value = true;
    selectedConversation.value = await MessageService.getConversation(conversationId);
    await loadMessages(conversationId);
    
    // Mark messages as read
    await MessageService.markMessagesAsRead(conversationId, user.userId);
    
    // Refresh conversations to update unread count
    await loadConversations();
  } catch (error) {
    console.error('Failed to load conversation:', error);
  } finally {
    isLoading.value = false;
  }
}

async function loadMessages(conversationId) {
  try {
    const newMessages = await MessageService.getConversationMessages(conversationId);
    messages.value = newMessages;
    previousMessageCount.value = newMessages.length;
    
    // Scroll to bottom after loading messages
    setTimeout(scrollToBottom, 100);
    
    // Mark messages as read if user is viewing this conversation
    if (!document.hidden && conversationId === selectedConversationId.value) {
      await MessageService.markMessagesAsRead(conversationId, user.userId);
    }
  } catch (error) {
    console.error('Failed to load messages:', error);
  }
}

function selectConversation(conversation) {
  selectedConversationId.value = conversation.conversationId;
  router.push(`/messages/${conversation.conversationId}`);
}

function goBackToConversations() {
  // Clear selected conversation state
  selectedConversationId.value = null;
  selectedConversation.value = null;
  messages.value = [];
  isNewConversation.value = false;
  newConversationData.value = null;
  router.push('/messages');
}

async function sendMessage() {
  if (!newMessage.value.trim() || isSending.value) return;

  try {
    isSending.value = true;
    
    // If this is a new conversation, create it first
    if (isNewConversation.value && newConversationData.value) {
      const conversation = await MessageService.getOrCreateConversation(
        newConversationData.value.itemId,
        user.userId,
        newConversationData.value.sellerId
      );
      
      // Now send the message via WebSocket
      WebSocketService.sendMessage(
        conversation.conversationId,
        user.userId,
        newMessage.value.trim()
      );
      
      // Update state and navigate to the conversation
      isNewConversation.value = false;
      newConversationData.value = null;
      newMessage.value = '';
      
      // Reload conversations and navigate to the new conversation
      await loadConversations();
      router.replace(`/messages/${conversation.conversationId}`);
    } else {
      // Send message via WebSocket
      WebSocketService.sendMessage(
        selectedConversationId.value,
        user.userId,
        newMessage.value.trim()
      );
      
      newMessage.value = '';
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    sendError.value = 'Failed to send message. Make sure you are connected.';
    setTimeout(() => { sendError.value = ''; }, 4000);
  } finally {
    isSending.value = false;
  }
}

function scrollToBottom() {
  if (messagesEndRef.value) {
    messagesEndRef.value.scrollIntoView({ behavior: 'smooth' });
  }
}

function formatTime(timestamp) {
  if (!timestamp) return '';
  
  // Handle both string and date formats, and ensure UTC conversion
  let date;
  if (typeof timestamp === 'string') {
    // If timestamp doesn't end with Z, treat as UTC
    date = timestamp.endsWith('Z') ? new Date(timestamp) : new Date(timestamp + 'Z');
  } else {
    date = new Date(timestamp);
  }
  
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (hours < 24) return `${hours}h ago`;
  if (days < 7) return `${days}d ago`;
  
  return date.toLocaleDateString();
}

function getOtherUser(conversation) {
  if (conversation.buyerId === user.userId) {
    return {
      id: conversation.sellerId,
      username: conversation.sellerUsername
    };
  } else {
    return {
      id: conversation.buyerId,
      username: conversation.buyerUsername
    };
  }
}

const selectedConversationUser = computed(() => {
  if (isNewConversation.value && newConversationData.value) {
    return {
      id: newConversationData.value.sellerId,
      username: newConversationData.value.sellerUsername
    };
  }
  if (!selectedConversation.value) return null;
  return getOtherUser(selectedConversation.value);
});
</script>

<template>
  <Header />
  <div class="messages-page">
    <div class="messages-container">
      <!-- Left Sidebar - Conversations List -->
      <div class="conversations-sidebar" :class="{ 'hide-on-mobile': selectedConversationId || isNewConversation }">
        <div class="sidebar-header">
          <h2>💬 Messages</h2>
        </div>
        <div class="conversations-list">
          <ConversationItem 
            v-for="conversation in conversations" 
            :key="conversation.conversationId"
            :conversation="conversation"
            :current-user-id="user.userId"
            :is-active="conversation.conversationId === selectedConversationId"
            @select="selectConversation"
          />
          <div v-if="conversations.length === 0" class="empty-state">
            <p>No conversations yet</p>
            <p class="hint">Start by contacting a seller from an item page</p>
          </div>
        </div>
      </div>

      <!-- Right Side - Chat View -->
      <div class="chat-section" :class="{ 'show-on-mobile': selectedConversationId || isNewConversation }">
        <div v-if="!selectedConversationId && !isNewConversation" class="no-conversation-selected">
          <div class="empty-chat">
            <span class="empty-icon">💬</span>
            <p>Select a conversation to start messaging</p>
          </div>
        </div>

        <div v-else class="chat-container">
          <!-- Chat Header -->
          <div class="chat-header">
            <button class="back-button" @click="goBackToConversations">
              ← Back
            </button>
            <div class="chat-user-info">
              <h3>{{ selectedConversationUser?.username }}</h3>
              <p class="chat-item-info">
                About: 
                <RouterLink 
                  v-if="isNewConversation ? newConversationData?.itemId : selectedConversation?.itemId"
                  :to="`/item/${isNewConversation ? newConversationData?.itemId : selectedConversation?.itemId}`"
                  class="item-link"
                  target="_blank"
                >
                  {{ isNewConversation ? newConversationData?.itemTitle : selectedConversation?.itemTitle }}
                </RouterLink>
                <span v-else>{{ isNewConversation ? newConversationData?.itemTitle : selectedConversation?.itemTitle }}</span>
              </p>
            </div>
          </div>

          <!-- Messages List -->
          <div class="messages-list" v-if="!isLoading">
            <MessageBubble 
              v-for="message in messages" 
              :key="message.messageId"
              :message="message"
              :current-user-id="user.userId"
            />
            <div v-if="messages.length === 0 || isNewConversation" class="no-messages">
              <p>No messages yet. Start the conversation!</p>
            </div>
            <div ref="messagesEndRef"></div>
          </div>

          <div v-else class="loading-messages">
            <p>Loading messages...</p>
          </div>

          <!-- Message Input -->
          <div class="message-input-container">
            <p v-if="sendError" class="send-error">{{ sendError }}</p>
            <form @submit.prevent="sendMessage" class="message-form">
              <input 
                v-model="newMessage" 
                type="text" 
                class="message-input"
                placeholder="Type a message..."
                :disabled="isSending"
                @keyup.enter="sendMessage"
              />
              <button 
                type="submit" 
                class="send-btn"
                :disabled="!newMessage.trim() || isSending"
              >
                {{ isSending ? '⏳' : '📤' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.messages-page {
  padding-top: 61px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
}

.messages-container {
  display: flex;
  height: calc(100vh - 61px);
  width: 100%;
}

/* Left Sidebar - Conversations */
.conversations-sidebar {
  width: 360px;
  background: #16213e;
  border-right: 1px solid #0f3460;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 20px;
  background: #0f3460;
  border-bottom: 1px solid #16213e;
}

.sidebar-header h2 {
  color: #ffffff;
  font-size: 1.4em;
  margin: 0;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #0f3460;
  cursor: pointer;
  transition: background 0.2s;
}

.conversation-item:hover {
  background: #0f3460;
}

.conversation-item.active {
  background: #0f3460;
  border-left: 3px solid #e94560;
}

.conversation-info {
  flex: 1;
  min-width: 0;
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.other-user {
  color: #ffffff;
  font-size: 1em;
  margin: 0;
  font-weight: 600;
}

.time {
  color: #8892b0;
  font-size: 0.75em;
}

.item-title {
  color: #8892b0;
  font-size: 0.85em;
  margin: 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.last-message {
  color: #b0b0b0;
  font-size: 0.9em;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: #e94560;
  color: #ffffff;
  border-radius: 50%;
  padding: 4px 8px;
  font-size: 0.75em;
  font-weight: bold;
  min-width: 20px;
  text-align: center;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
  color: #8892b0;
}

.empty-state p {
  margin: 8px 0;
}

.empty-state .hint {
  font-size: 0.9em;
  color: #606770;
}

/* Right Side - Chat */
.chat-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #0f0f1e;
}

.no-conversation-selected {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-chat {
  text-align: center;
  color: #8892b0;
}

.empty-icon {
  font-size: 4em;
  display: block;
  margin-bottom: 16px;
}

.empty-chat p {
  font-size: 1.1em;
}

.chat-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-header {
  padding: 20px 24px;
  background: #16213e;
  border-bottom: 1px solid #0f3460;
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-button {
  display: none;
  background: none;
  border: none;
  color: #e94560;
  font-size: 1.2em;
  font-weight: 600;
  cursor: pointer;
  padding: 8px 12px;
  transition: all 0.2s;
  border-radius: 6px;
}

.back-button:hover {
  background: rgba(233, 69, 96, 0.1);
}

.chat-user-info h3 {
  color: #ffffff;
  font-size: 1.2em;
  margin: 0 0 4px 0;
}

.chat-item-info {
  color: #8892b0;
  font-size: 0.9em;
  margin: 0;
}

.item-link {
  color: #e94560;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
  border-bottom: 1px solid transparent;
}

.item-link:hover {
  color: #ff6b7a;
  border-bottom-color: #ff6b7a;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message {
  display: flex;
}

.message.sent {
  justify-content: flex-end;
}

.message.received {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 60%;
  padding: 12px 16px;
  border-radius: 12px;
  word-wrap: break-word;
}

.message.sent .message-bubble {
  background: #e94560;
  color: #ffffff;
  border-bottom-right-radius: 4px;
}

.message.received .message-bubble {
  background: #16213e;
  color: #ffffff;
  border-bottom-left-radius: 4px;
}

.message-text {
  margin: 0 0 4px 0;
  line-height: 1.4;
}

.message-time {
  font-size: 0.75em;
  opacity: 0.7;
}

.no-messages {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8892b0;
  text-align: center;
}

.loading-messages {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8892b0;
}

.message-input-container {
  padding: 16px 20px;
  background: #16213e;
  border-top: 1px solid #0f3460;
}

.send-error {
  color: #ff6b7a;
  font-size: 0.85em;
  margin: 0 0 8px 0;
}

.message-form {
  display: flex;
  gap: 12px;
}

.message-input {
  flex: 1;
  padding: 12px 16px;
  background: #0f0f1e;
  border: 1px solid #0f3460;
  border-radius: 24px;
  color: #ffffff;
  font-size: 1em;
  outline: none;
  transition: border-color 0.2s;
}

.message-input:focus {
  border-color: #e94560;
}

.message-input::placeholder {
  color: #606770;
}

.send-btn {
  padding: 12px 20px;
  background: #e94560;
  border: none;
  border-radius: 24px;
  color: #ffffff;
  font-size: 1.2em;
  cursor: pointer;
  transition: all 0.2s;
}

.send-btn:hover:not(:disabled) {
  background: #d63851;
  transform: scale(1.05);
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Scrollbar Styling */
.conversations-list::-webkit-scrollbar,
.messages-list::-webkit-scrollbar {
  width: 8px;
}

.conversations-list::-webkit-scrollbar-track,
.messages-list::-webkit-scrollbar-track {
  background: #0f0f1e;
}

.conversations-list::-webkit-scrollbar-thumb,
.messages-list::-webkit-scrollbar-thumb {
  background: #0f3460;
  border-radius: 4px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.messages-list::-webkit-scrollbar-thumb:hover {
  background: #16213e;
}

/* Responsive Design */
@media (max-width: 768px) {
  .messages-container {
    flex-direction: row;
  }

  .conversations-sidebar {
    width: 100%;
    border-right: none;
    transition: transform 0.3s ease;
  }

  .conversations-sidebar.hide-on-mobile {
    display: none;
  }

  .chat-section {
    display: none;
    width: 100%;
  }

  .chat-section.show-on-mobile {
    display: flex;
  }

  .back-button {
    display: block;
  }

  .message-bubble {
    max-width: 80%;
  }

  .chat-header {
    padding: 16px;
  }

  .chat-user-info h3 {
    font-size: 1em;
  }

  .chat-item-info {
    font-size: 0.8em;
  }
}
</style>
