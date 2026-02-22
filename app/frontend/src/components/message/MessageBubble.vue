<template>
  <div 
    class="message"
    :class="{ sent: isSent, received: !isSent }"
  >
    <div class="message-bubble">
      <p class="message-text">{{ message.messageText }}</p>
      <span class="message-time">{{ formattedTime }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: {
    type: Object,
    required: true
  },
  currentUserId: {
    type: Number,
    required: true
  }
});

const isSent = computed(() => {
  return props.message.senderId === props.currentUserId;
});

const formattedTime = computed(() => {
  if (!props.message.createdAt) return '';
  
  let date;
  if (typeof props.message.createdAt === 'string') {
    date = new Date(props.message.createdAt.replace(' ', 'T') + 'Z');
  } else {
    date = new Date(props.message.createdAt);
  }
  
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: false
  });
});
</script>

<style scoped>
.message {
  display: flex;
  margin-bottom: 1rem;
  animation: messageSlide 0.3s ease;
}

.message.sent {
  justify-content: flex-end;
}

.message.received {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 70%;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  word-wrap: break-word;
}

.message.sent .message-bubble {
  background: linear-gradient(135deg, #e94560 0%, #d63850 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.message.received .message-bubble {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border-bottom-left-radius: 4px;
}

.message-text {
  margin: 0 0 0.5rem 0;
  font-size: 0.9375rem;
  line-height: 1.5;
}

.message-time {
  font-size: 0.75rem;
  opacity: 0.7;
}

@keyframes messageSlide {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .message-bubble {
    max-width: 85%;
  }
}
</style>
