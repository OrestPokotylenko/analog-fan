import axios from './axiosConfig';

class OrderService {
  async createOrder(orderData) {
    try {
      const response = await axios.post('/orders', orderData);
      return response.data;
    } catch (error) {
      console.error('OrderService.createOrder failed:', error);
      throw error;
    }
  }

  async getUserOrders(userId) {
    try {
      const response = await axios.get(`/orders/user/${userId}`);
      return response.data;
    } catch (error) {
      console.error('OrderService.getUserOrders failed:', error);
      throw error;
    }
  }

  async getOrderById(orderId) {
    try {
      const response = await axios.get(`/orders/${orderId}`);
      return response.data;
    } catch (error) {
      console.error('OrderService.getOrderById failed:', error);
      throw error;
    }
  }

  async updateOrderStatus(orderId, statusData) {
    try {
      const response = await axios.put(`/orders/${orderId}`, statusData);
      return response.data;
    } catch (error) {
      console.error('OrderService.updateOrderStatus failed:', error);
      throw error;
    }
  }

  async getOrderItems(orderId) {
    try {
      const response = await axios.get(`/order-items/order/${orderId}`);
      return response.data;
    } catch (error) {
      console.error('OrderService.getOrderItems failed:', error);
      throw error;
    }
  }

  async getOrderStatuses() {
    try {
      const response = await axios.get('/orders/statuses');
      return response.data;
    } catch (error) {
      console.error('OrderService.getOrderStatuses failed:', error);
      throw error;
    }
  }

  async getAllOrders() {
    try {
      const response = await axios.get('/orders');
      return response.data;
    } catch (error) {
      console.error('OrderService.getAllOrders failed:', error);
      throw error;
    }
  }

  async deleteOrder(orderId) {
    try {
      const response = await axios.delete(`/orders/${orderId}`);
      return response.data;
    } catch (error) {
      console.error('OrderService.deleteOrder failed:', error);
      throw error;
    }
  }

  async createOrderItem(orderItemData) {
    try {
      const response = await axios.post('/order-items', orderItemData);
      return response.data;
    } catch (error) {
      console.error('OrderService.createOrderItem failed:', error);
      throw error;
    }
  }

  async cancelOrder(orderId) {
    try {
      const response = await axios.put(`/orders/${orderId}`, {
        order_status: 'cancelled'
      });
      return response.data;
    } catch (error) {
      console.error('OrderService.cancelOrder failed:', error);
      throw error;
    }
  }

  async refundOrder(orderId) {
    try {
      const response = await axios.put(`/orders/${orderId}`, {
        payment_status: 'refunded'
      });
      return response.data;
    } catch (error) {
      console.error('OrderService.refundOrder failed:', error);
      throw error;
    }
  }
}

export default new OrderService();
