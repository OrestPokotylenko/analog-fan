import { createApp, reactive } from 'vue';
import App from './App.vue';
import router from './router';
import { setupAxiosInterceptors } from './services/axiosConfig';
import { isTokenExpired, clearAuthState } from './services/authHelpers';

const token = localStorage.getItem('jwtToken');
const user = JSON.parse(localStorage.getItem('user'));

// Check if token is expired on app startup
if (token && isTokenExpired(token)) {
  clearAuthState();
}

const app = createApp(App);

app.config.globalProperties.$auth = reactive({
  isLoggedIn: !!localStorage.getItem('jwtToken'), // Re-check after potential clearing
  token: localStorage.getItem('jwtToken'),
  user: JSON.parse(localStorage.getItem('user') || 'null')
});

// Setup axios interceptors with auth and router
setupAxiosInterceptors(app.config.globalProperties.$auth, router);

app.provide('$auth', app.config.globalProperties.$auth);
app.use(router).mount('#app');