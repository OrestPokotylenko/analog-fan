import { createApp, reactive } from 'vue';
import App from './App.vue';
import router from './router';

const token = localStorage.getItem('jwtToken');
const user = JSON.parse(localStorage.getItem('user'));

const app = createApp(App);

app.config.globalProperties.$auth = reactive({
  isLoggedIn: !!token,
  token,
  user
});

app.provide('$auth', app.config.globalProperties.$auth);
app.use(router).mount('#app');