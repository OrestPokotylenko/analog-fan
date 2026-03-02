import { loadStripe } from '@stripe/stripe-js';
import axios from './axiosConfig';

// Start fetching the Stripe config + SDK immediately on import.
// The heavy Stripe.js download runs in the background so it's
// already cached by the time the checkout page mounts.
let _stripeReady = null;

function preload() {
  if (!_stripeReady) {
    _stripeReady = axios
      .get('/payment/config')
      .then(({ data }) => loadStripe(data.publishableKey))
      .catch((err) => {
        console.error('Stripe preload failed:', err);
        _stripeReady = null; // allow retry
        return null;
      });
  }
  return _stripeReady;
}

// Kick off immediately when this module is first imported
preload();

class PaymentService {
  constructor() {
    this.stripe = null;
  }

  /**
   * Ensure the Stripe SDK is ready.
   * Resolves instantly if preload already finished.
   */
  async initialize() {
    if (this.stripe) return this.stripe;
    this.stripe = await preload();
    if (!this.stripe) throw new Error('Failed to load Stripe');
    return this.stripe;
  }

  /**
   * Create Elements in deferred-intent mode (no PaymentIntent needed).
   * This lets the payment form render instantly without a backend call.
   */
  async createDeferredElements(amount, currency = 'eur') {
    if (!this.stripe) {
      await this.initialize();
    }

    const elements = this.stripe.elements({
      mode: 'payment',
      amount: Math.round(amount * 100), // cents
      currency,
      paymentMethodTypes: ['ideal'],
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
        googlePay: 'never',
        applePay: 'never',
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

  /**
   * Create a PaymentIntent on the backend (called at submission time).
   */
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

  /**
   * Submit elements, create the intent, then confirm payment.
   * This is the "deferred intent" flow — the PaymentIntent is created
   * only when the user actually clicks "Place Order".
   */
  async confirmDeferredPayment(elements, amount, billingDetails, orderId = null) {
    if (!this.stripe) {
      throw new Error('Stripe not initialized');
    }

    // 1. Validate the payment form
    const { error: submitError } = await elements.submit();
    if (submitError) throw submitError;

    // 2. Create the PaymentIntent on the backend (server-to-Stripe call)
    const { clientSecret } = await this.createPaymentIntent(amount, orderId);

    // 3. Confirm with the already-collected payment details
    const { error, paymentIntent } = await this.stripe.confirmPayment({
      elements,
      clientSecret,
      confirmParams: {
        return_url: `${window.location.origin}/order-confirmation`,
        payment_method_data: {
          billing_details: billingDetails,
        },
      },
      redirect: 'if_required',
    });

    if (error) throw error;

    return {
      success: true,
      paymentIntentId: paymentIntent?.id,
    };
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
}

const paymentService = new PaymentService();

// Expose preload as an instance method so ShoppingCart can call it
paymentService.preload = preload;

export default paymentService;
