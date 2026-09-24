<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Resend;

class ResendMailService
{
    /**
     * Send 6-Digit Email Verification Code via Resend API (or fallback to standard Mail).
     */
    public function sendVerificationCodeEmail(User $user, string $code): bool
    {
        $appName = config('app.name', 'E-Blog');
        $fromAddress = config('mail.from.address', 'no-reply@eka-dev.cloud');
        $fromName = config('mail.from.name', $appName);
        $resendApiKey = config('services.resend.key');

        $htmlContent = $this->buildVerificationCodeHtml($user->name, $code, $appName);

        // Tier 1: Send directly via Resend SDK if API key is provided
        if (! empty($resendApiKey)) {
            try {
                $resend = Resend::client($resendApiKey);
                $response = $resend->emails->send([
                    'from' => "{$fromName} <{$fromAddress}>",
                    'to' => [$user->email],
                    'subject' => "{$code} is your {$appName} verification code",
                    'html' => $htmlContent,
                ]);

                Log::info("Verification code email sent via Resend to {$user->email}. Resend ID: " . ($response->id ?? 'unknown'));
                return true;
            } catch (\Throwable $e) {
                Log::warning("Resend API failed to send verification email to {$user->email}: " . $e->getMessage() . ". Falling back to default mailer.");
            }
        }

        // Tier 2: Fallback to Laravel Mail
        try {
            Mail::html($htmlContent, function ($message) use ($user, $fromAddress, $fromName, $appName, $code) {
                $message->to($user->email, $user->name)
                    ->from($fromAddress, $fromName)
                    ->subject("{$code} is your {$appName} verification code");
            });

            Log::info("Verification code email sent via fallback mailer to {$user->email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send verification code email via fallback mailer to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Password Reset Email via Resend API (or fallback to standard Mail).
     */
    public function sendPasswordResetEmail(User $user, string $token): bool
    {
        $resetUrl = url("/reset-password/{$token}?email=".urlencode($user->email));
        $appName = config('app.name', 'E-Blog');
        $fromAddress = config('mail.from.address', 'no-reply@eka-dev.cloud');
        $fromName = config('mail.from.name', $appName);
        $resendApiKey = config('services.resend.key');

        $htmlContent = $this->buildPasswordResetHtml($user->name, $resetUrl, $appName);

        // Tier 1: Send directly via Resend SDK if API key is provided
        if (! empty($resendApiKey)) {
            try {
                $resend = Resend::client($resendApiKey);
                $response = $resend->emails->send([
                    'from' => "{$fromName} <{$fromAddress}>",
                    'to' => [$user->email],
                    'subject' => "Reset Your {$appName} Password",
                    'html' => $htmlContent,
                ]);

                Log::info("Password reset email sent via Resend to {$user->email}. Resend ID: " . ($response->id ?? 'unknown'));
                return true;
            } catch (\Throwable $e) {
                Log::warning("Resend API failed to send reset email to {$user->email}: " . $e->getMessage() . ". Falling back to default mailer.");
            }
        }

        // Tier 2: Fallback to Laravel Mail (e.g. smtp, log, array during testing)
        try {
            Mail::html($htmlContent, function ($message) use ($user, $fromAddress, $fromName, $appName) {
                $message->to($user->email, $user->name)
                    ->from($fromAddress, $fromName)
                    ->subject("Reset Your {$appName} Password");
            });

            Log::info("Password reset email sent via fallback mailer to {$user->email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send password reset email via fallback mailer to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build responsive HTML email template for password reset.
     */
    protected function buildPasswordResetHtml(string $userName, string $resetUrl, string $appName): string
    {
        $safeName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0B0F19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #E2E8F0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0B0F19; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 520px; background-color: #111827; border: 1px solid #1F2937; border-radius: 24px; padding: 40px 32px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; border-radius: 14px; background: linear-gradient(135deg, #F59E0B, #D97706); color: #000; font-size: 22px; font-weight: 800; text-align: center;">
                                E
                            </div>
                            <h1 style="margin: 16px 0 0; font-size: 22px; font-weight: 700; color: #F9FAFB; letter-spacing: -0.02em;">
                                {$appName}
                            </h1>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="border-top: 1px solid #1F2937; padding-top: 24px;">
                            <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #F3F4F6;">
                                Reset Your Password
                            </h2>
                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #9CA3AF;">
                                Hello <strong style="color: #F3F4F6;">{$safeName}</strong>,
                            </p>
                            <p style="margin: 0 0 28px; font-size: 14px; line-height: 1.6; color: #9CA3AF;">
                                We received a request to reset the password for your {$appName} account. Click the button below to choose a new password. This link is valid for <strong style="color: #F3F4F6;">60 minutes</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- Reset Button -->
                    <tr>
                        <td align="center" style="padding-bottom: 28px;">
                            <a href="{$safeUrl}" target="_blank" style="display: inline-block; background-color: #F59E0B; color: #030712; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 12px; box-shadow: 0 4px 14px 0 rgba(245, 158, 11, 0.39);">
                                Reset Password
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 24px;">
                            <p style="margin: 0; font-size: 11px; color: #4B5563;">
                                &copy; {$year} {$appName}. All rights reserved.
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

    /**
     * Build responsive HTML email template for 6-digit verification code.
     */
    protected function buildVerificationCodeHtml(string $userName, string $code, string $appName): string
    {
        $safeName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0B0F19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #E2E8F0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0B0F19; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 520px; background-color: #111827; border: 1px solid #1F2937; border-radius: 24px; padding: 40px 32px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; border-radius: 14px; background: linear-gradient(135deg, #F59E0B, #D97706); color: #000; font-size: 22px; font-weight: 800; text-align: center;">
                                E
                            </div>
                            <h1 style="margin: 16px 0 0; font-size: 22px; font-weight: 700; color: #F9FAFB; letter-spacing: -0.02em;">
                                {$appName}
                            </h1>
                        </td>
                    </tr>

                    <!-- Divider & Body -->
                    <tr>
                        <td style="border-top: 1px solid #1F2937; padding-top: 24px;">
                            <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #F3F4F6;">
                                Confirm Your Email Address
                            </h2>
                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #9CA3AF;">
                                Hello <strong style="color: #F3F4F6;">{$safeName}</strong>,
                            </p>
                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #9CA3AF;">
                                Thank you for creating your account on {$appName}. Please use the 6-digit verification code below to confirm your email and activate your account.
                            </p>
                        </td>
                    </tr>

                    <!-- 6-Digit Code Box -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <div style="background-color: #0B0F19; border: 1px solid #374151; border-radius: 16px; padding: 18px 24px; text-align: center; display: inline-block; min-width: 240px;">
                                <span style="font-family: 'SF Mono', Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #F59E0B; display: block; margin-right: -10px;">
                                    {$safeCode}
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Expiration Notice -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <p style="margin: 0; font-size: 13px; color: #9CA3AF;">
                                This verification code will expire in <strong style="color: #F3F4F6;">15 minutes</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- Security Notice -->
                    <tr>
                        <td style="border-top: 1px solid #1F2937; padding-top: 20px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #6B7280;">
                                If you did not create an account on {$appName}, please ignore this email. No account will be activated without this verification code.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 24px;">
                            <p style="margin: 0; font-size: 11px; color: #4B5563;">
                                &copy; {$year} {$appName}. All rights reserved.
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
