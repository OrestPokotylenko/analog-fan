<template>
  <div 
    class="conversation-item"
    :class="{ active: isActive }"
    @click="$emit('select', conversation)"
  >
    <div class="conversation-info">
      <div class="conversation-header">
        <h3 class="other-user">{{ otherUsername }}</h3>
        <span class="time">{{ formattedTime }}</span>
      </div>
      <p class="item-title">📦 {{ conversation.itemTitle }}</p>
      <p class="last-message">{{ conversation.lastMessage || 'No messages yet' }}</p>
    </div>
    <span v-if="conversation.unreadCount > 0" class="unread-badge">
      {{ conversation.unreadCount }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  conversation: {
    type: Object,
    required: true
  },
  currentUserId: {
    type: Number,
    required: true
  },
  isActive: {
    type: Boolean,
    default: false
  }
});

defineEmits(['select']);

const otherUsername = computed(() => {
  return props.conversation.buyerId === props.currentUserId
    ? props.conversation.sellerUsername
    : props.conversation.buyerUsername;
});

const formattedTime = computed(() => {
  if (!props.conversation.lastMessageTime) return '';
  
  let date;
  if (typeof props.conversation.lastMessageTime === 'string') {
    date = new Date(props.conversation.lastMessageTime.replace(' ', 'T') + 'Z');
  } else {
    date = new Date(props.conversation.lastMessageTime);
  }
  
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
</script>

<style scoped>
.conversation-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

.conversation-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.conversation-item.active {
  background: rgba(233, 69, 96, 0.1);
  border-color: rgba(233, 69, 96, 0.3);
}

.conversation-info {
  flex: 1;
  min-width: 0;
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.other-user {
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.time {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.75rem;
  flex-shrink: 0;
  margin-left: 0.5rem;
}

.item-title {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  margin: 0.25rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.last-message {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.875rem;
  margin: 0.25rem 0 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: #e94560;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  min-width: 20px;
  text-align: center;
  flex-shrink: 0;
}
</style>
