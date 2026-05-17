<?php

namespace App\Http\Controllers;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send invitation email
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $required = ['recipientEmail', 'recipientName', 'inviterName', 'inviterOrganization', 'invitationLink', 'organizationName', 'supportEmail', 'liveChatUrl'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse(['success' => false, 'error' => "Missing required field: $field"], 400);
                }
            }

            $htmlContent = $this->generateInvitationEmailHtml($data);

            $email = new Email();
            $email->to($data['recipientEmail'])
                ->subject("You're invited to {$data['organizationName']}")
                ->from('noreply@techreserve.com')
                ->html($htmlContent);
            
            $this->mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Invitation email sent successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send account approval email
     */
    public function sendApproval(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $required = ['recipientEmail', 'recipientName', 'loginUrl', 'organizationName', 'supportEmail'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse(['success' => false, 'error' => "Missing required field: $field"], 400);
                }
            }

            $htmlContent = $this->generateApprovalEmailHtml($data);

            $email = new \Symfony\Component\Mime\Email();
            $email->to($data['recipientEmail'])
                ->subject('Your Account Has Been Approved!')
                ->from('noreply@techreserve.com')
                ->html($htmlContent);
            
            $this->mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Approval email sent successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send account rejection email
     */
    public function sendRejection(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $required = ['recipientEmail', 'recipientName', 'organizationName', 'supportEmail'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse(['success' => false, 'error' => "Missing required field: $field"], 400);
                }
            }

            $htmlContent = $this->generateRejectionEmailHtml($data);

            $email = new Email();
            $email->to($data['recipientEmail'])
                ->subject('Update on Your Account Application')
                ->from('noreply@techreserve.com')
                ->html($htmlContent);
            
            $this->mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Rejection email sent successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate invitation email HTML
     */
    private function generateInvitationEmailHtml($data)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { color: #1a6e3a; font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 8px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 15px; }
        .message { font-size: 14px; line-height: 1.6; margin-bottom: 20px; color: #666; }
        .button { display: inline-block; background-color: #1a6e3a; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 20px 0; }
        .footer { font-size: 12px; color: #999; margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
        .contact-info { font-size: 13px; color: #666; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">◆ {$data['organizationName']}</div>
        </div>

        <div class="content">
            <div class="greeting">Hi, {$data['recipientName']}!</div>
            
            <div class="message">
                <strong>{$data['inviterName']}</strong> with <strong>{$data['inviterOrganization']}</strong> has invited you to use <strong>{$data['organizationName']}</strong> to collaborate with them. Click the button below to set up your account and get started:
            </div>

            <a href="{$data['invitationLink']}" class="button">Set up account</a>

            <div class="message">
                If you have any questions for {$data['inviterName']}, reply to this email and it will be sent to them. Alternatively, <a href="mailto:{$data['supportEmail']}">contact our support team</a> anytime or join us on <a href="{$data['liveChatUrl']}">live chat</a> during business hours.
            </div>

            <div class="message">
                Welcome aboard,<br>
                The {$data['organizationName']} Team
            </div>

            <div class="message">
                <strong>P.S.</strong> Need help getting started? Check out our <a href="#">help documentation</a>
            </div>
        </div>

        <div class="footer">
            <div>© 2026 {$data['organizationName']}. All rights reserved.</div>
            <div class="contact-info">1234 Street Rd, Suite 1234, City, State, ZIP Code</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Generate approval email HTML
     */
    private function generateApprovalEmailHtml($data)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { color: #1a6e3a; font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 8px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 15px; }
        .message { font-size: 14px; line-height: 1.6; margin-bottom: 20px; color: #666; }
        .success-badge { display: inline-block; background-color: #1a6e3a; color: white; padding: 8px 16px; border-radius: 4px; margin: 10px 0; font-weight: 600; }
        .button { display: inline-block; background-color: #1a6e3a; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 20px 0; }
        .footer { font-size: 12px; color: #999; margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">◆ {$data['organizationName']}</div>
        </div>

        <div class="content">
            <div class="greeting">Hi, {$data['recipientName']}!</div>
            
            <div class="message">
                Great news! Your account has been approved and is ready to use.
            </div>

            <div style="text-align: center;">
                <div class="success-badge">✓ Account Approved</div>
            </div>

            <div class="message">
                You can now log in to {$data['organizationName']} and start using the system. Click the button below to access your account:
            </div>

            <a href="{$data['loginUrl']}" class="button">Log in to your account</a>

            <div class="message">
                If you have any questions or need assistance, please don't hesitate to reach out to our support team at <a href="mailto:{$data['supportEmail']}">{$data['supportEmail']}</a>.
            </div>

            <div class="message">
                Welcome to {$data['organizationName']}!<br>
                The Support Team
            </div>
        </div>

        <div class="footer">
            <div>© 2026 {$data['organizationName']}. All rights reserved.</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Generate rejection email HTML
     */
    private function generateRejectionEmailHtml($data)
    {
        $reasonText = $data['rejectionReason'] ? "Reason: {$data['rejectionReason']}" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { color: #1a6e3a; font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 8px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 15px; }
        .message { font-size: 14px; line-height: 1.6; margin-bottom: 20px; color: #666; }
        .footer { font-size: 12px; color: #999; margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">◆ {$data['organizationName']}</div>
        </div>

        <div class="content">
            <div class="greeting">Hi, {$data['recipientName']}!</div>
            
            <div class="message">
                Thank you for your interest in {$data['organizationName']}. After reviewing your application, we regret to inform you that we are unable to approve your account at this time.
            </div>

            {$reasonText}

            <div class="message">
                If you have any questions or would like more information, please feel free to contact us at <a href="mailto:{$data['supportEmail']}">{$data['supportEmail']}</a>.
            </div>

            <div class="message">
                Thank you for your understanding.<br>
                The {$data['organizationName']} Team
            </div>
        </div>

        <div class="footer">
            <div>© 2026 {$data['organizationName']}. All rights reserved.</div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
