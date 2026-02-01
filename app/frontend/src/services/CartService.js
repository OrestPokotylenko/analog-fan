import axios from './axiosConfig';

class CartService {
  async getOrCreateCart(userId) {
    try {
      const response = await axios.get(`/shopping-cart/user/${userId}`);
      return response.data;
    } catch (error) {
      if (error.response?.status === 404 || error.response?.status === 500) {
        try {
          const createResponse = await axios.post('/shopping-cart', { userId });
          return createResponse.data;
        } catch (createError) {
          throw createError;
        }
      }
      throw error;
    }
  }

  async getCartItems(userId) {
    try {
      const cart = await this.getOrCreateCart(userId);
      if (!cart || !cart.cartId) {
        return [];
      }
      const response = await axios.get(`/shopping-cart/${cart.cartId}/items`);
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      return [];
    }
  }

  async addItem(userId, itemId, quantity) {
    try {
      const cart = await this.getOrCreateCart(userId);
      const response = await axios.post(`/shopping-cart/${cart.cartId}/items`, {
        itemId,
        quantity
      });
      window.dispatchEvent(new Event('cart-updated'));
      return true;
    } catch (error) {
      return false;
    }
  }

  async updateQuantity(cartItemId, quantity) {
    try {
      await axios.put(`/shopping-cart/items/${cartItemId}`, { quantity });
      window.dispatchEvent(new Event('cart-updated'));
      return true;
    } catch (error) {
      return false;
    }
  }

  async removeItem(cartId, cartItemId) {
    try {
      await axios.delete(`/shopping-cart/${cartId}/items/${cartItemId}`);
      window.dispatchEvent(new Event('cart-updated'));
      return true;
    } catch (error) {
      return false;
    }
  }

  async clearCart(cartId) {
    try {
      await axios.delete(`/shopping-cart/${cartId}/items`);
      window.dispatchEvent(new Event('cart-updated'));
      return true;
    } catch (error) {
      return false;
    }
  }

  async getCartCount(userId) {
    try {
      const items = await this.getCartItems(userId);
      return items.length;
    } catch (error) {
      return 0;
    }
  }
}

export default new CartService();
