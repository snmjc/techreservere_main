<?php

namespace App\Domain\Account\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AccountAcceptanceEmailService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function shouldUseBrandedMailer(): bool
    {
        $mailerDsn = strtolower(trim((string)($_ENV['MAILER_DSN'] ?? '')));
        return $mailerDsn !== '' && !str_starts_with($mailerDsn, 'null://');
    }

    public function sendAcceptedAccountEmail(array $account, string $loginUrl): array
    {
        $emailAddress = (string)($account['email_address'] ?? '');
        $roleDesignation = (string)($account['role_designation'] ?? '');
        $department = (string)($account['department'] ?? '');
        $accountType = $this->resolveAcceptedEmailAccountType($roleDesignation, $department);
        $recipientName = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
        $recipientName = $recipientName !== '' ? $recipientName : $emailAddress;

        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@techreserve.feutech.edu.ph')
                ->to($emailAddress)
                ->subject($this->buildAcceptedAccountSubject($accountType))
                ->html($this->buildAcceptedAccountEmailHtml($recipientName, $loginUrl, $accountType));

            $this->mailer->send($email);

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $exception) {
            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }

    private function buildAcceptedAccountSubject(string $accountType): string
    {
        return match ($accountType) {
            'Admin' => 'Welcome to TechReserve, Admin! Your Account is Verified and Ready to Use',
            'Employee' => 'Welcome to TechReserve, Employee! Your Account is Verified and Ready to Use',
            default => 'Your TechReserve Account is Verified and Ready to Use!',
        };
    }

    private function buildAcceptedAccountEmailHtml(string $recipientName, string $loginUrl, string $accountType): string
    {
        $name = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $isEmployee = $accountType === 'Employee';
        $isAdmin = $accountType === 'Admin';
        $headline = $isAdmin || $isEmployee
            ? 'Great news! Your account is<br>verified and ready to use.'
            : 'Great news,<br>your account is<br>verified and ready<br>to use!';
        $welcomePill = $isAdmin ? 'WELCOME, ADMIN!' : ($isEmployee ? 'HELLO!' : '');
        $intro = match ($accountType) {
            'Admin' => 'Your administrator account for TechReserve has been successfully verified.',
            'Employee' => 'Your employee account for TechReserve has been successfully verified.',
            default => 'We are happy to inform you that your account in TechReserve has been successfully verified.',
        };
        $body = match ($accountType) {
            'Admin' => 'You can now log in to the system to manage reservations, monitor resources, generate reports, and configure system settings with ease.',
            'Employee' => 'You can now log in to the system to view your tasks, manage reservations, and collaborate with your team.',
            default => 'You can now log in to your account and start reserving equipment or venues with ease.',
        };
        $features = match ($accountType) {
            'Admin' => [
                ['label' => 'Manage Reservations', 'text' => 'Review, approve, and manage all reservation requests.', 'icon' => 'MR'],
                ['label' => 'Monitor Resources', 'text' => 'View availability and usage of equipment and venues.', 'icon' => 'MO'],
                ['label' => 'Analytics & Reports', 'text' => 'Access insights and reports for decision-making.', 'icon' => 'AR'],
                ['label' => 'System Management', 'text' => 'Configure settings and manage administration.', 'icon' => 'SM'],
            ],
            'Employee' => [
                ['label' => 'View Assignments', 'text' => 'See tasks assigned to you and track your progress.', 'icon' => 'VA'],
                ['label' => 'Manage Reservations', 'text' => 'Check reservation details and related schedules.', 'icon' => 'MR'],
                ['label' => 'Stay Updated', 'text' => 'Receive notifications and important announcements.', 'icon' => 'SU'],
                ['label' => 'Work Together', 'text' => 'Coordinate with your team efficiently and effectively.', 'icon' => 'WT'],
            ],
            default => [
                ['label' => 'Reserve Venues', 'text' => '', 'icon' => 'RV'],
                ['label' => 'Reserve Equipment', 'text' => '', 'icon' => 'RE'],
                ['label' => 'Track Your Reservations', 'text' => '', 'icon' => 'TR'],
                ['label' => 'Get Real-time Updates', 'text' => '', 'icon' => 'RU'],
            ],
        };

        $featureCells = '';
        foreach ($features as $feature) {
            $featureCells .= $this->buildAcceptedEmailFeatureCell($feature['label'], $feature['text'], $feature['icon']);
        }

        $heroLabel = $isAdmin ? 'ADMIN VERIFIED' : ($isEmployee ? 'EMPLOYEE VERIFIED' : 'ACCOUNT VERIFIED');
        $heroVisual = '<td width="45%" align="center" style="padding:18px 20px;"><table role="presentation" width="230" cellpadding="0" cellspacing="0" style="width:230px;background:#eef8f1;border:1px solid #d8eadf;"><tr><td align="center" style="padding:24px 12px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" width="68" height="68" style="width:68px;height:68px;background:#07834f;color:#ffffff;font-size:20px;font-weight:900;line-height:68px;">OK</td></tr></table><div style="font-size:12px;font-weight:800;color:#103f2b;margin-top:12px;">' . $heroLabel . '</div></td></tr></table></td>';
        $welcomeRow = $welcomePill !== ''
            ? '<tr><td align="center" style="padding:18px 34px 0;"><span style="display:inline-block;background:#dff3e8;color:#007a4d;padding:6px 18px;border-radius:999px;font-size:11px;font-weight:900;">' . $welcomePill . '</span></td></tr>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechReserve Account Verified</title>
</head>
<body style="margin:0;padding:0;background:#f6f8f7;font-family:Arial,Helvetica,sans-serif;color:#0f1f1a;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8f7;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="width:680px;max-width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 14px 36px rgba(6,56,34,0.14);">
          <tr>
            <td align="center" style="padding:24px 28px;border-bottom:1px solid #e2e8e5;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="width:54px;height:54px;border-radius:16px;background:#007a4d;color:#f6b801;text-align:center;font-size:24px;font-weight:900;">TR</td>
                  <td style="padding-left:12px;text-align:left;">
                    <div style="font-size:25px;font-weight:900;line-height:1;"><span style="color:#007a4d;">Tech</span><span style="color:#f6b801;">Reserve</span></div>
                    <div style="font-size:10px;letter-spacing:.8px;font-weight:800;color:#24332e;margin-top:4px;">EQUIPMENT &amp; VENUE RESERVATION SYSTEM</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="55%" style="padding:34px 34px 22px;">
                    <div style="font-size:29px;line-height:1.1;font-weight:900;color:#073b2a;">{$headline}</div>
                  </td>
                  {$heroVisual}
                </tr>
              </table>
            </td>
          </tr>
          {$welcomeRow}
          <tr>
            <td style="padding:0 34px 10px;">
              <div style="font-size:15px;font-weight:900;color:#007a4d;margin-bottom:10px;">Hello {$name},</div>
              <div style="font-size:13px;line-height:1.7;color:#17221e;">{$intro}</div>
              <div style="font-size:13px;line-height:1.7;color:#17221e;margin-top:2px;">{$body}</div>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:16px 34px 14px;">
              <a href="{$url}" style="display:inline-block;min-width:210px;background:#007a4d;color:#ffffff;text-decoration:none;text-align:center;padding:13px 22px;border-radius:6px;font-size:14px;font-weight:900;">Log in to TechReserve</a>
            </td>
          </tr>
          <tr>
            <td style="padding:6px 34px 26px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8e5;">
                <tr>
                  {$featureCells}
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#00633f;color:#ffffff;padding:20px 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:12px;line-height:1.6;">
                    <div style="color:#f6b801;font-weight:900;">Need help?</div>
                    <div>Contact the System Administrator</div>
                    <div>techreserve@feutech.edu.ph</div>
                  </td>
                  <td align="right" style="font-size:12px;line-height:1.6;">
                    <div>Thank you,</div>
                    <div style="color:#f6b801;font-weight:900;">The TechReserve Team</div>
                    <div>FEU Institute of Technology</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:14px;color:#6b7280;font-size:11px;">This is an automated message. Please do not reply to this email.</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function buildAcceptedEmailFeatureCell(string $label, string $text, string $icon): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $safeIcon = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        $description = $safeText !== '' ? '<div style="font-size:10px;line-height:1.4;color:#52605b;margin-top:5px;">' . $safeText . '</div>' : '';

        return <<<HTML
<td width="25%" align="center" valign="top" style="padding:16px 10px;border-right:1px solid #e2e8e5;">
  <div style="width:46px;height:46px;border-radius:50%;background:#dff3e8;color:#007a4d;line-height:46px;font-size:18px;font-weight:900;margin:0 auto 8px;">{$safeIcon}</div>
  <div style="font-size:11px;line-height:1.25;font-weight:900;color:#10231d;">{$safeLabel}</div>
  {$description}
</td>
HTML;
    }

    private function resolveAcceptedEmailAccountType(string $roleDesignation, string $department): string
    {
        $role = strtoupper($roleDesignation);

        if (str_contains($role, 'ADMIN')) {
            return 'Admin';
        }

        if ($this->isEmployeeAccount($roleDesignation, $department)) {
            return 'Employee';
        }

        return 'User';
    }

    private function isEmployeeAccount(string $roleDesignation, string $department): bool
    {
        $role = strtoupper($roleDesignation);
        $normalizedDepartment = strtolower($department);

        return str_contains($role, 'STAFF')
            || str_contains($role, 'EMPLOYEE')
            || str_contains($normalizedDepartment, 'staff')
            || str_contains($normalizedDepartment, 'employee')
            || str_contains($normalizedDepartment, 'technical')
            || str_contains($normalizedDepartment, 'maintenance')
            || str_contains($normalizedDepartment, 'support');
    }
}
