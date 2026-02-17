<?php

namespace App\External;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SellerMailer {
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

    public function sendShippingLabel($orderData, $shipmentData, $pdfContent)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            $this->mail->setFrom($this->mail->Username, 'Analog-fan System');
            $this->mail->addAddress($this->mail->Username); // Send to yourself (seller)

            $this->mail->isHTML(true);
            $this->mail->Subject = "📦 New Shipping Label - Order #{$orderData['order_number']}";
            $this->mail->Body = $this->getTemplate($orderData, $shipmentData);
            
            // Attach PDF label
            $this->mail->addStringAttachment(
                $pdfContent, 
                "label-{$shipmentData['tracking_number']}.pdf",
                'base64',
                'application/pdf'
            );

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("SellerMailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    private function getTemplate($order, $shipment)
    {
        $trackingNumber = $shipment['tracking_number'] ?? 'N/A';
        $carrier = $shipment['carrier'] ?? 'Carrier';
        $trackingUrl = $shipment['tracking_url'] ?? '#';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Shipping Label</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); max-width: 100%;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">📦 New Shipping Label Created</h1>
                            <p style="margin: 10px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Order #{$order['order_number']}</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #1f2937; font-size: 16px; font-weight: 600;">
                                Action Required: Print and attach label to package
                            </p>
                            
                            <p style="margin: 0 0 25px; color: #4b5563; font-size: 14px; line-height: 1.6;">
                                A new shipping label has been generated. The PDF label is attached to this email. 
                                Please print it and attach it to the package before handing it to the carrier.
                            </p>
                            
                            <!-- Shipment Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px; color: #1f2937; font-size: 16px; font-weight: 600;">Shipment Details</h3>
                                        <table width="100%" cellpadding="5" cellspacing="0">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Tracking Number:</td>
                                                <td style="color: #1f2937; font-size: 14px; font-weight: 600; text-align: right;">{$trackingNumber}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Carrier:</td>
                                                <td style="color: #1f2937; font-size: 14px; text-align: right;">{$carrier}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Order:</td>
                                                <td style="color: #1f2937; font-size: 14px; text-align: right;">#{$order['order_number']}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Customer Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 10px; color: #1e40af; font-size: 16px; font-weight: 600;">📍 Delivery Address</h3>
                                        <p style="margin: 0; color: #1e3a8a; font-size: 14px; line-height: 1.8;">
                                            <strong>{$order['email']}</strong><br>
                                            {$order['street']} {$order['house_number']}<br>
                                            {$order['zip_code']} {$order['city']}<br>
                                            {$order['country']}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Track Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 25px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$trackingUrl}" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 8px; font-size: 14px; font-weight: 600;">
                                            View Tracking →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Instructions -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                                <tr>
                                    <td>
                                        <h4 style="margin: 0 0 10px; color: #1f2937; font-size: 14px; font-weight: 600;">📋 Next Steps:</h4>
                                        <ol style="margin: 0; padding-left: 20px; color: #4b5563; font-size: 13px; line-height: 1.8;">
                                            <li>Open the attached PDF label</li>
                                            <li>Print the label on A4 paper or label paper</li>
                                            <li>Attach the label securely to the package</li>
                                            <li>Hand over the package to {$carrier}</li>
                                        </ol>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                This is an automated notification from Analog-fan Shipping System
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
