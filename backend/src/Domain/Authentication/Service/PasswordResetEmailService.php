<?php

namespace App\Domain\Authentication\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetEmailService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function sendResetCode(array $account, string $emailAddress, string $code): bool
    {
        try {
            $recipientName = trim((string)$account['first_name'] . ' ' . (string)$account['last_name']);
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@techreserve.feutech.edu.ph')
                ->to($emailAddress)
                ->subject('Your TechReserve password reset code')
                ->html($this->buildPasswordResetEmailHtml($recipientName !== '' ? $recipientName : $emailAddress, $code));

            $this->mailer->send($email);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function buildPasswordResetEmailHtml(string $recipientName, string $code): string
    {
        return sprintf(
            '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.5;">
                <h2 style="color:#007a4d;">TechReserve Password Reset</h2>
                <p>Hello %s,</p>
                <p>Use this code to reset your TechReserve password:</p>
                <p style="font-size:28px;font-weight:800;letter-spacing:4px;color:#111827;">%s</p>
                <p>This code expires in 15 minutes.</p>
                <p>If you did not request this, you can ignore this email.</p>
            </div>',
            htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
        );
    }
}
