<template>
  <div>
    <h1>User List</h1>
    <ul>
      <li v-for="user in users" :key="user.userId">{{ user.username }}, {{ user.email }}</li>
    </ul>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      users: []
    };
  },
  created() {
    this.fetchUsers();
  },
  methods: {
    async fetchUsers() {
      try {
        const response = await axios.get('http://localhost/api/users');
        this.users = response.data;
        console.log(this.users);
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    }
  }
};
</script>