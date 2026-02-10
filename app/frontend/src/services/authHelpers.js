export function isTokenExpired(token) {
  if (!token) return true;
  
  try {
    // JWT tokens have three parts separated by dots
    const parts = token.split('.');
    if (parts.length !== 3) return true;
    
    // Decode the payload (second part)
    const payload = JSON.parse(atob(parts[1]));
    
    // Check if token has expiration
    if (!payload.exp) return false;
    
    // Check if token is expired (exp is in seconds, Date.now() is in milliseconds)
    const currentTime = Math.floor(Date.now() / 1000);
    return payload.exp < currentTime;
  } catch (error) {
    console.error('Error parsing JWT token:', error);
    return true; // If we can't parse it, treat it as expired
  }
}

export function clearAuthState(auth) {
  localStorage.removeItem('jwtToken');
  localStorage.removeItem('user');
  if (auth) {
    auth.isLoggedIn = false;
    auth.token = null;
    auth.user = null;
  }
}
