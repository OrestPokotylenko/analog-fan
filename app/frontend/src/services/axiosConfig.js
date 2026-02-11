import axios from 'axios';
import { isTokenExpired, clearAuthState } from './authHelpers';

const axiosInstance = axios.create({
  baseURL: 'http://localhost/api',
  validateStatus: function (status) {
    // Don't throw errors for 409 Conflict - we'll handle it gracefully
    return (status >= 200 && status < 300) || status === 409;
  }
});

export function setupAxiosInterceptors(auth, router) {
  // Request interceptor to add JWT token and check expiration
  axiosInstance.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('jwtToken');
      
      // Check if token is expired before making request
      if (token && isTokenExpired(token)) {
        clearAuthState(auth);
        router.push('/login');
        return Promise.reject(new Error('Token expired'));
      }
      
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  // Response interceptor to handle 401 errors
  axiosInstance.interceptors.response.use(
    (response) => {
      return response;
    },
    (error) => {
      if (error.response && error.response.status === 401) {
        // Clear authentication state
        clearAuthState(auth);
        
        // Redirect to login page
        router.push('/login');
      }

      return Promise.reject(error);
    }
  );
}

export default axiosInstance;
