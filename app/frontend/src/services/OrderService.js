import axios from './axiosConfig';

class OrderService {
  async createOrder(orderData) {
    const response = await axios.post('/orders', orderData);
    return response.data;
  }

  async getUserOrders(userId) {
    const response = await axios.get(`/orders/user/${userId}`);
    return response.data;
  }

  async getOrderById(orderId) {
    const response = await axios.get(`/orders/${orderId}`);
    return response.data;
  }

  async updateOrderStatus(orderId, statusData) {
    const response = await axios.put(`/orders/${orderId}`, statusData);
    return response.data;
  }

  async getOrderItems(orderId) {
    const response = await axios.get(`/order-items/order/${orderId}`);
    return response.data;
  }

  async getOrderStatuses() {
    const response = await axios.get('/orders/statuses');
    return response.data;
  }

  async getAllOrders() {
    const response = await axios.get('/orders');
    return response.data;
  }

  async deleteOrder(orderId) {
    const response = await axios.delete(`/orders/${orderId}`);
    return response.data;
  }

  async createOrderItem(orderItemData) {
    const response = await axios.post('/order-items', orderItemData);
    return response.data;
  }

  async cancelOrder(orderId) {
    const response = await axios.put(`/orders/${orderId}`, {
      order_status: 'cancelled'
    });
    return response.data;
  }

  async refundOrder(orderId) {
    const response = await axios.put(`/orders/${orderId}`, {
      payment_status: 'refunded'
    });
    return response.data;
  }
}

export default new OrderService();
