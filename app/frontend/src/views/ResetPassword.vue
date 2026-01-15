<template>
    <Header />
    <div class="content justify-content-center">
      <div v-if="isLoading" class="text-center">
        <p>Loading...</p>
      </div>
      <ResetPasswordForm v-else-if="!hasToken" />
      <CreateNewPasswordForm v-else-if="isTokenValid" />
      <div v-else class="alert alert-danger">Invalid or expired token</div>
    </div>
  </template>
  
  <script>
  import axios from '../services/axiosConfig';
  import Header from '../components/Header.vue';
  import ResetPasswordForm from '../components/ResetPasswordForm.vue';
  import CreateNewPasswordForm from '../components/CreateNewPasswordForm.vue';
  
  export default {
    components: {
      Header,
      ResetPasswordForm,
      CreateNewPasswordForm
    },
    data() {
      return {
        isTokenValid: false,
        hasToken: !!this.$route.query.token,
        isLoading: true
      };
    },
    async created() {
      if (this.hasToken) {
        try {
          const response = await axios.get('/validate-token', {
            params: { token: this.$route.query.token }
          });
  
          if (response.data.isValid) {
            this.isTokenValid = true;
          } else {
            this.$router.push('/login');
          }
        } catch (error) {
          console.error('Error validating token:', error);
          this.$router.push('/login');
        } finally {
          this.isLoading = false;
        }
      } else {
        this.isLoading = false;
      }
    }
  };
  </script>
  
  <style scoped>
  .text-center {
    text-align: center;
    margin-top: 20px;
  }
  </style>