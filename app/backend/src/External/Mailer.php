<?php

namespace App\External;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['MAIL_HOST'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['MAIL_USERNAME'];
        $this->mail->Password = $_ENV['MAIL_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $_ENV['MAIL_PORT'];
    }

    public function sendEmail($to, $subject, $body)
    {
        try {
            $this->mail->setFrom($this->mail->Username, 'Analog-fan');
            $this->mail->addAddress($to);

            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    public function sendOrderConfirmation($orderData)
    {
        $subject = "Order Confirmation - Order #{$orderData['order_number']}";
        $body = $this->getOrderConfirmationTemplate($orderData);
        
        return $this->sendEmail($orderData['email'], $subject, $body);
    }

    private function getOrderConfirmationTemplate($order)
    {
        $orderDetailsUrl = $_ENV['FRONTEND_URL'] . '/orders/' . $order['id'];
        $totalAmount = number_format($order['total_amount'], 2);
        $subtotal = number_format($order['subtotal'], 2);
        $taxAmount = number_format($order['tax_amount'], 2);
        $shippingCost = number_format($order['shipping_cost'], 2);
        
        // Format date
        $orderDate = date('F j, Y', strtotime($order['created_at'] ?? 'now'));
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden; max-width: 100%;">
                    
                    <!-- Header with gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">✅ Order Confirmed!</h1>
                            <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 16px;">Thank you for your purchase</p>
                        </td>
                    </tr>
                    
                    <!-- Order Info Section -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;">
                                            Hi there! 👋
                                        </p>
                                        <p style="margin: 0 0 25px; color: #4b5563; font-size: 16px; line-height: 1.6;">
                                            We're excited to let you know that we've received your order and it's being processed. 
                                            We'll send you updates about your shipment as soon as it's on its way!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Order Details Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 15px; color: #1f2937; font-size: 20px; font-weight: 600;">📦 Order Details</h2>
                                        
                                        <table role="presentation" width="100%" cellpadding="5" cellspacing="0">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px; padding: 5px 0;">Order Number:</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; font-weight: 600; padding: 5px 0;">#{$order['order_number']}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px; padding: 5px 0;">Order Date:</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; padding: 5px 0;">$orderDate</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px; padding: 5px 0;">Payment Method:</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; padding: 5px 0;">{$order['payment_method']}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Shipping Address Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 15px; color: #1f2937; font-size: 20px; font-weight: 600;">📍 Shipping Address</h2>
                                        <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.8;">
                                            {$order['street']} {$order['house_number']}<br>
                                            {$order['zip_code']} {$order['city']}<br>
                                            {$order['country']}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Order Summary -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 15px; color: #1f2937; font-size: 20px; font-weight: 600;">💰 Order Summary</h2>
                                        
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #4b5563; font-size: 14px; padding: 8px 0;">Subtotal:</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; padding: 8px 0;">€{$subtotal}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #4b5563; font-size: 14px; padding: 8px 0;">Tax (21%):</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; padding: 8px 0;">€{$taxAmount}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #4b5563; font-size: 14px; padding: 8px 0;">Shipping:</td>
                                                <td align="right" style="color: #1f2937; font-size: 14px; padding: 8px 0;">€{$shippingCost}</td>
                                            </tr>
                                            <tr style="border-top: 2px solid #e94560;">
                                                <td style="color: #e94560; font-size: 18px; font-weight: 700; padding: 15px 0 0;">Total:</td>
                                                <td align="right" style="color: #e94560; font-size: 18px; font-weight: 700; padding: 15px 0 0;">€{$totalAmount}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$orderDetailsUrl}" style="display: inline-block; background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%); color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(233, 69, 96, 0.4);">
                                            View Order Details →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- What's Next Section -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 25px; margin-top: 25px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px; color: #1f2937; font-size: 18px; font-weight: 600;">📬 What's Next?</h3>
                                        <ul style="margin: 0; padding-left: 20px; color: #4b5563; font-size: 14px; line-height: 1.8;">
                                            <li style="margin-bottom: 8px;">We'll send you updates about your order via email</li>
                                            <li style="margin-bottom: 8px;">Your order will be prepared and shipped soon</li>
                                            <li style="margin-bottom: 8px;">Expected delivery: 3-5 business days</li>
                                            <li style="margin-bottom: 8px;">You can track your order status anytime online</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px; color: #6b7280; font-size: 14px;">
                                Questions? Contact us anytime at <a href="mailto:support@analog-fan.com" style="color: #e94560; text-decoration: none;">support@analog-fan.com</a>
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                © 2026 Analog-fan. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}