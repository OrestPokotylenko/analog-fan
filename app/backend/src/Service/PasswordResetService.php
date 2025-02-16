<?php

require_once(__DIR__ . '/Mailer.php');
require_once(__DIR__ . '/../Model/UserModel.php');
require_once(__DIR__ . '/../Model/LinkModel.php');
require_once(__DIR__ . '/../DTO/LinkDTO.php');

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
        $subject = "Reset Password";
        $message = "Click the link below to reset your password: $resetLink";

        return $this->mailer->sendEmail($email, $subject, $message);
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