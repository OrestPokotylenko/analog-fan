import { ref } from 'vue';

export function useToast() {
  const toastVisible = ref(false);
  const toastMessage = ref('');

  function showToast(message, duration = 3000) {
    toastMessage.value = message;
    toastVisible.value = true;
    setTimeout(() => {
      toastVisible.value = false;
    }, duration);
  }

  return { toastVisible, toastMessage, showToast };
}
