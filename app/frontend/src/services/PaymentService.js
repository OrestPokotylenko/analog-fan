import { loadStripe } from '@stripe/stripe-js';
import axios from './axiosConfig';

class PaymentService {
  constructor() {
    this.stripePromise = null;
    this.stripe = null;
  }

  async initialize() {
    if (this.stripe) return this.stripe;

    try {
      const response = await axios.get('/payment/config');
      const { publishableKey } = response.data;
      
      this.stripePromise = loadStripe(publishableKey);
      this.stripe = await this.stripePromise;
      
      return this.stripe;
    } catch (error) {
      console.error('Failed to initialize Stripe:', error);
      throw error;
    }
  }

  async createPaymentIntent(amount, orderId = null) {
    try {
      const response = await axios.post('/payment/create-intent', {
        amount,
        currency: 'eur',
        order_id: orderId,
      });
      
      return response.data;
    } catch (error) {
      console.error('Failed to create payment intent:', error);
      throw error;
    }
  }

  async createCheckoutSession(clientSecret) {
    if (!this.stripe) {
      await this.initialize();
    }

    const elements = this.stripe.elements({ 
      clientSecret,
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: '#667eea',
        },
      },
    });
    
    const paymentElement = elements.create('payment', {
      layout: {
        type: 'tabs',
        defaultCollapsed: false,
      },
      wallets: {
        googlePay: 'auto',
      },
      fields: {
        billingDetails: {
          name: 'never',
          email: 'never',
          phone: 'never',
          address: 'never',
        },
      },
    });
    
    return { elements, paymentElement };
  }

  async confirmPayment(elements, returnUrl) {
    if (!this.stripe) {
      throw new Error('Stripe not initialized');
    }

    const { error } = await this.stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: returnUrl,
      },
    });

    if (error) {
      throw error;
    }
  }

  async getPaymentStatus(paymentIntentId) {
    try {
      const response = await axios.get(`/payment/status/${paymentIntentId}`);
      return response.data.status;
    } catch (error) {
      console.error('Failed to get payment status:', error);
      throw error;
    }
  }

  async processPaymentWithElements(elements, billingDetails) {
    if (!this.stripe) {
      throw new Error('Stripe not initialized');
    }

    const { error: submitError } = await elements.submit();
    if (submitError) {
      throw submitError;
    }

    const { error, paymentIntent } = await this.stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: `${window.location.origin}/order-confirmation`,
        payment_method_data: {
          billing_details: billingDetails,
        },
      },
      redirect: 'if_required',
    });

    if (error) {
      throw error;
    }

    return { 
      success: true, 
      paymentIntentId: paymentIntent?.id 
    };
  }
}

export default new PaymentService();
