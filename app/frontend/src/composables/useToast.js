import { ref } from 'vue';

export function useToast() {
  const toastVisible = ref(false);
  const toastMessage = ref('');
  let timer = null;

  function showToast(message, duration = 3000) {
    toastMessage.value = message;
    toastVisible.value = true;

    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
      toastVisible.value = false;
      timer = null;
    }, duration);
  }

  return { toastVisible, toastMessage, showToast };
}
