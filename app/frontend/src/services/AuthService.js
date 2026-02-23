import { clearAuthState } from './authHelpers';

export function logout(auth, router, redirectUrl = '/login') {
  clearAuthState(auth);
  router.push(redirectUrl);
}
