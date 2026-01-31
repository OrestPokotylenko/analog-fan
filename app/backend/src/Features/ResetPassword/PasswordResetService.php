<?php

namespace App\Features\ResetPassword;

use App\External\Mailer;
use App\Features\User\UserModel;

class PasswordResetService {
    private $mailer;
    private $userModel;
    private $linkModel;

    public function __construct() {
        $this->mailer = new Mailer();
        $this->userModel = new UserModel();
        $this->linkModel = new LinkModel();
    }

    public function requestPasswordReset($email) {
        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(50));
        $linkDto = new LinkDTO($user->userId, $token, 0);
        $this->linkModel->addLink($linkDto);

        $resetLink = 'http://localhost:5173/reset-password?token=' . $token;
        $this->sendPasswordResetEmail($user->email, $resetLink);

        return true;
    }

    private function sendPasswordResetEmail($email, $resetLink) {
        $subject = "Reset Your Password - Analog Fan";
        $message = $this->getStyledEmailTemplate($resetLink);

        return $this->mailer->sendEmail($email, $subject, $message);
    }

    private function getStyledEmailTemplate($resetLink) {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%); min-height: 100vh;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: #ffffff; font-size: 2em; font-weight: 800; letter-spacing: 0.5px; margin: 0;">Analog Fan</h1>
            <p style="color: #b0b0b0; font-size: 0.9em; margin: 10px 0 0 0;">Your vintage marketplace</p>
        </div>

        <!-- Main Card -->
        <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 1px solid rgba(233, 69, 96, 0.2); border-radius: 16px; padding: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <!-- Content -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #ffffff; font-size: 1.8em; font-weight: 700; margin: 0 0 15px 0;">Reset Your Password</h2>
                <p style="color: #b0b0b0; font-size: 1em; line-height: 1.6; margin: 0;">We received a request to reset your password. Click the button below to create a new password.</p>
            </div>

            <!-- Button -->
            <div style="text-align: center; margin: 35px 0;">
                <a href="' . $resetLink . '" style="display: inline-block; background: linear-gradient(135deg, #e94560 0%, #ff6b7a 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 1em; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);">Reset Password</a>
            </div>

            <!-- Info -->
            <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid rgba(233, 69, 96, 0.1);">
                <p style="color: #b0b0b0; font-size: 0.85em; line-height: 1.5; margin: 0;">If you didn\'t request this password reset, you can safely ignore this email. This link will expire in 24 hours.</p>
            </div>

            <!-- Link fallback -->
            <div style="margin-top: 25px; padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px; border: 1px solid rgba(233, 69, 96, 0.1);">
                <p style="color: #b0b0b0; font-size: 0.75em; margin: 0 0 8px 0;">If the button doesn\'t work, copy and paste this link:</p>
                <a href="' . $resetLink . '" style="color: #e94560; word-break: break-all; font-size: 0.8em; text-decoration: none;">' . $resetLink . '</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #b0b0b0; font-size: 0.8em; margin: 0;">© 2026 Analog Fan. All rights reserved.</p>
            <p style="color: #808080; font-size: 0.75em; margin: 10px 0 0 0;">This email was sent to you because a password reset was requested for your account.</p>
        </div>
    </div>
</body>
</html>
        ';
    }

    public function resetPassword($token, $newPassword) {
        $userId = $this->linkModel->getUserIdByToken($token);

        if (!$userId) {
            return false;
        }

        $this->userModel->resetPassword($userId, $newPassword);
        $this->linkModel->expireLink($token);

        return true;
    }
}