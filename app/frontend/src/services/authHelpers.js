export function isTokenExpired(token) {
  if (!token) return true;
  
  try {
    // JWT tokens have three parts separated by dots
    const parts = token.split('.');
    if (parts.length !== 3) return true;
    
    // Decode the payload (second part) — handle Unicode-safe base64
    const base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    const payload = JSON.parse(jsonPayload);
    
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
