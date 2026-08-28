<?php
/**
 * OJAMS — Gmail SMTP Mailer Module
 * Powered by PHPMailer
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Returns a configured PHPMailer instance
 */
function getMailer(): ?PHPMailer {
    if (empty(SMTP_USER) || empty(SMTP_PASS)) {
        error_log('OJAMS Mailer: SMTP_USER or SMTP_PASS is not configured in .env');
        return null;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Sender
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        return $mail;
    } catch (Exception $e) {
        error_log("OJAMS Mailer Initialization Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Sends a generic HTML email
 */
function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool {
    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("OJAMS Mailer: Invalid recipient email {$toEmail}");
        return false;
    }

    $mail = getMailer();
    if (!$mail) {
        return false;
    }

    try {
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags(str_replace(['<br>', '<br/>', '</p>'], ["\n", "\n", "\n\n"], $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OJAMS Mailer Send Error to [{$toEmail}]: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generates email wrapper layout
 */
function getEmailTemplate(string $title, string $contentHtml, string $badgeText = '', string $badgeColor = '#4f46e5'): string {
    $appName = APP_NAME;
    $year    = date('Y');
    $badgeHtml = $badgeText ? "<div style=\"display:inline-block; background-color:{$badgeColor}; color:#ffffff; font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;\">{$badgeText}</div>" : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b; line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding:30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" max-width="600" style="max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); border:1px solid #e2e8f0;" cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding:30px 30px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:24px; font-weight:800; letter-spacing:-0.5px;">💼 {$appName}</h1>
                            <p style="color:#c7d2fe; margin:5px 0 0 0; font-size:13px;">Online Job Application & Monitoring System in the Itawes District</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:35px 30px;">
                            {$badgeHtml}
                            <h2 style="color:#0f172a; margin:0 0 16px 0; font-size:20px; font-weight:700;">{$title}</h2>
                            {$contentHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:20px 30px; text-align:center; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b;">
                            <p style="margin:0 0 6px 0;">This is an automated notification from <strong>{$appName}</strong>. Please do not reply directly to this email.</p>
                            <p style="margin:0;">&copy; {$year} {$appName} &bull; All rights reserved.</p>
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
 * Sends notification when application status changes (Approved / Rejected)
 * Supports interview scheduling details for approved applications.
 */
function sendApplicationStatusEmail(string $toEmail, string $applicantName, string $jobTitle, string $company, string $status, ?string $interviewDate = null, ?string $interviewNotes = null): bool {
    $isApproved = ($status === 'Approved');
    $badgeText  = $isApproved ? 'Application Approved 🎉' : 'Application Status Update';
    $badgeColor = $isApproved ? '#16a34a' : '#dc2626';
    $subject    = $isApproved
        ? "Congratulations! Your application for {$jobTitle} has been Approved"
        : "Update regarding your application for {$jobTitle}";

    $portalUrl = BASE_URL . '/pages/user/my-applications.php';

    if ($isApproved) {
        $interviewSection = '';
        if (!empty($interviewDate)) {
            $formattedDate = date('F j, Y \a\t g:i A', strtotime($interviewDate));
            $notesHtml = !empty($interviewNotes) 
                ? "<p style=\"margin:8px 0 0 0; color:#14532d; font-size:14px;\"><strong>Instructions / Venue:</strong> " . nl2br(htmlspecialchars($interviewNotes)) . "</p>" 
                : '';

            $interviewSection = <<<HTML
        <div style="background-color:#ecfdf5; border-left:4px solid #10b981; padding:18px 20px; margin:20px 0; border-radius:6px; border:1px solid #a7f3d0;">
            <h4 style="margin:0 0 10px 0; color:#065f46; font-size:16px; display:flex; align-items:center;">
                📅 Scheduled Interview Details
            </h4>
            <p style="margin:0 0 6px 0; color:#064e3b; font-size:15px;">
                <strong>Date & Time:</strong> <span style="background-color:#d1fae5; padding:2px 8px; border-radius:4px; font-weight:700; color:#065f46;">{$formattedDate}</span>
            </p>
            {$notesHtml}
            <p style="margin:10px 0 0 0; color:#047857; font-size:13px; font-style:italic;">
                Please be ready at least 10 minutes before the scheduled time and prepare any required credentials.
            </p>
        </div>
HTML;
        } else {
            $interviewSection = <<<HTML
        <div style="background-color:#f0fdf4; border-left:4px solid #16a34a; padding:15px; margin:20px 0; border-radius:4px;">
            <h4 style="margin:0 0 6px 0; color:#166534; font-size:15px;">Next Steps:</h4>
            <p style="margin:0; color:#14532d; font-size:14px;">The hiring team or human resources department will be in contact with you shortly regarding the next stages of the recruitment process.</p>
        </div>
HTML;
        }

        $content = <<<HTML
        <p>Dear <strong>{$applicantName}</strong>,</p>
        <p>Great news! We are pleased to inform you that your application for the position of <strong>{$jobTitle}</strong> at <strong>{$company}</strong> has been <span style="color:#16a34a; font-weight:700;">APPROVED</span>.</p>
        
        {$interviewSection}

        <p>You can check your application timeline and interview updates anytime on your OJAMS portal.</p>
        
        <div style="text-align:center; margin:30px 0 10px 0;">
            <a href="{$portalUrl}" style="background-color:#4f46e5; color:#ffffff; padding:12px 28px; font-weight:700; text-decoration:none; border-radius:8px; display:inline-block;">View My Applications</a>
        </div>
HTML;
    } else {
        $content = <<<HTML
        <p>Dear <strong>{$applicantName}</strong>,</p>
        <p>Thank you for taking the time to apply for the position of <strong>{$jobTitle}</strong> at <strong>{$company}</strong>.</p>
        <p>After careful evaluation of all candidates, we regret to inform you that we will not be moving forward with your application at this time.</p>
        
        <div style="background-color:#f8fafc; border-left:4px solid #94a3b8; padding:15px; margin:20px 0; border-radius:4px;">
            <p style="margin:0; color:#475569; font-size:14px;">We encourage you to continue browsing other job openings on OJAMS that match your skills and experience.</p>
        </div>

        <div style="text-align:center; margin:30px 0 10px 0;">
            <a href="{$portalUrl}" style="background-color:#4f46e5; color:#ffffff; padding:12px 28px; font-weight:700; text-decoration:none; border-radius:8px; display:inline-block;">Browse Other Jobs</a>
        </div>
HTML;
    }

    $html = getEmailTemplate("Application Status: {$status}", $content, $badgeText, $badgeColor);
    return sendEmail($toEmail, $applicantName, $subject, $html);
}

/**
 * Sends confirmation when an applicant submits a job application
 */
function sendApplicationSubmittedEmail(string $toEmail, string $applicantName, string $jobTitle, string $company): bool {
    $subject   = "Application Received: {$jobTitle} at {$company}";
    $portalUrl = BASE_URL . '/pages/user/my-applications.php';
    $currDate  = date('F j, Y');

    $content = <<<HTML
    <p>Dear <strong>{$applicantName}</strong>,</p>
    <p>Your job application for <strong>{$jobTitle}</strong> at <strong>{$company}</strong> has been successfully submitted and received.</p>
    
    <div style="background-color:#f8fafc; border-left:4px solid #4f46e5; padding:15px; margin:20px 0; border-radius:4px;">
        <h4 style="margin:0 0 6px 0; color:#1e1b4b; font-size:14px;">Application Details:</h4>
        <ul style="margin:0; padding-left:20px; color:#475569; font-size:14px;">
            <li><strong>Position:</strong> {$jobTitle}</li>
            <li><strong>Company:</strong> {$company}</li>
            <li><strong>Status:</strong> <span style="color:#d97706; font-weight:600;">Pending Review</span></li>
            <li><strong>Date:</strong> {$currDate}</li>
        </ul>
    </div>

    <p>Our recruitment officers will review your credentials and you will be notified by email once your application status changes.</p>

    <div style="text-align:center; margin:30px 0 10px 0;">
        <a href="{$portalUrl}" style="background-color:#4f46e5; color:#ffffff; padding:12px 28px; font-weight:700; text-decoration:none; border-radius:8px; display:inline-block;">Track Application</a>
    </div>
HTML;

    $html = getEmailTemplate("Application Submitted", $content, "Submission Confirmed", "#4f46e5");
    return sendEmail($toEmail, $applicantName, $subject, $html);
}

/**
 * Sends notification when a Staff account is approved by an Admin
 */
function sendStaffAccountApprovedEmail(string $toEmail, string $staffName): bool {
    $subject  = "Your OJAMS Staff Account Has Been Approved!";
    $loginUrl = BASE_URL . '/login.php';

    $content = <<<HTML
    <p>Dear <strong>{$staffName}</strong>,</p>
    <p>Your registration as a <strong>Staff Officer</strong> in OJAMS has been reviewed and <span style="color:#16a34a; font-weight:700;">APPROVED</span> by the system administrator.</p>
    
    <div style="background-color:#f0fdf4; border-left:4px solid #16a34a; padding:15px; margin:20px 0; border-radius:4px;">
        <p style="margin:0; color:#166534; font-size:14px;">You can now log in to the Staff Portal to manage job listings, review applicant submissions, and evaluate resumes.</p>
    </div>

    <div style="text-align:center; margin:30px 0 10px 0;">
        <a href="{$loginUrl}" style="background-color:#4f46e5; color:#ffffff; padding:12px 28px; font-weight:700; text-decoration:none; border-radius:8px; display:inline-block;">Log In to Staff Portal</a>
    </div>
HTML;

    $html = getEmailTemplate("Staff Account Approved", $content, "Account Active", "#16a34a");
    return sendEmail($toEmail, $staffName, $subject, $html);
}
