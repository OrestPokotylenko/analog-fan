<template>
    <Header />
    <div class="content justify-content-center">
        <SignupForm />
    </div>
</template>

<script>
import Header from '../components/Header.vue';
import SignupForm from '../components/SignupForm.vue';

export default {
    components: {
        Header,
        SignupForm
    }
};

</script>