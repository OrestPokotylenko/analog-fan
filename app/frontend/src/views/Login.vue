<template>
  <Header />
  <div class="center flex-grow-1">
    <LoginForm />
  </div>
</template>

<script>
import Header from '../components/Header.vue';
import LoginForm from '../components/LoginForm.vue';

export default {
  components: {
    Header,
    LoginForm
  }
};
</script>

<style></style>