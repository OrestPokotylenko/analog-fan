export class AuthService {
    logout(url) {
        localStorage.removeItem('jwtToken');
        localStorage.removeItem('user');
        this.$auth.isLoggedIn = false;
        this.$auth.token = null;
        this.$auth.user = null;
        this.$router.push(url || '/login');
      }
}