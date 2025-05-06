<script setup>
</script>

<template>
  <router-view />
</template>

<style>
#app {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  max-width: 100%;
  min-height: 100vh;
}

body {
  display: block;
  place-items: unset;
}

.content {
  display: flex;
  flex-direction: column;
  height: 100%;
  margin: 100px 50px;
}
</style>