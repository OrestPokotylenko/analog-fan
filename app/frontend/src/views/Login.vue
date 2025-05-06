<template>
  <Header />
  <div class="content justify-content-center">
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