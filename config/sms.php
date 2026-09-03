<?php
/**
 * OJAMS \u2014 PhilSMS Gateway Module
 * Handles SMS notifications for application submissions, status updates, and interview schedules.
 */

require_once __DIR__ . "/config.php";

/**
 * Normalizes a Philippine mobile number into international 639XXXXXXXXX format.
 * Accepts formats like: 09557997409, +639557997409, 639557997409, 9557997409
 *
 * @param string $phone
 * @return string|null Formatted number (e.g., "639557997409") or null if invalid
 */
function formatPhPhoneNumber(string $phone): ?string {
    $digits = preg_replace("/\\D+/", "", $phone);

    if (empty($digits)) {
        return null;
    }

    if (strlen($digits) === 11 && substr($digits, 0, 2) === "09") {
        return "63" . substr($digits, 1);
    }

    if (strlen($digits) === 10 && substr($digits, 0, 1) === "9") {
        return "63" . $digits;
    }

    if (strlen($digits) === 12 && substr($digits, 0, 3) === "639") {
        return $digits;
    }

    return null;
}

/**
 * Sends an SMS message using the PhilSMS API
 */
function sendSms(string $phone, string $message, ?string $senderId = null): bool {
    $token = PHILSMS_API_TOKEN;
    if (empty($token)) {
        error_log("OJAMS PhilSMS: PHILSMS_API_TOKEN is not configured in .env");
        return false;
    }

    $formattedNumber = formatPhPhoneNumber($phone);
    if (!$formattedNumber) {
        error_log("OJAMS PhilSMS: Invalid Philippine phone number [{$phone}]");
        return false;
    }

    $url = PHILSMS_API_URL;
    $sender = !empty($senderId) ? $senderId : PHILSMS_SENDER_ID;

    $payload = [
        "recipient" => $formattedNumber,
        "sender_id" => $sender,
        "type"      => "plain",
        "message"   => $message,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
        "Accept: application/json",
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("OJAMS PhilSMS cURL Error to [{$formattedNumber}]: {$curlError}");
        return false;
    }

    $data = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($data["status"]) && $data["status"] === "success") {
        return true;
    }

    $errMsg = $data["message"] ?? $response;
    error_log("OJAMS PhilSMS API Error (HTTP {$httpCode}) to [{$formattedNumber}]: {$errMsg}");
    return false;
}

/**
 * Sends SMS notification upon successful job application submission
 */
function sendApplicationSubmittedSms(string $phone, string $applicantName, string $jobTitle, string $company): bool {
    if (empty(trim($phone))) {
        return false;
    }

    $message = "OJAMS: Hi {$applicantName}, your application for {$jobTitle} at {$company} has been received. We will notify you once reviewed. Thank you!";
    return sendSms($phone, $message);
}

/**
 * Sends SMS notification for application status change (Approved / Rejected) and interview scheduling
 */
function sendApplicationStatusSms(string $phone, string $applicantName, string $jobTitle, string $company, string $status, ?string $interviewDate = null, ?string $interviewNotes = null): bool {
    if (empty(trim($phone))) {
        return false;
    }

    if ($status === "Approved") {
        if (!empty($interviewDate)) {
            $formattedDate = date("M d, Y h:i A", strtotime($interviewDate));
            $message = "OJAMS: Congratulations {$applicantName}! Your application for {$jobTitle} at {$company} is APPROVED. Interview scheduled on {$formattedDate}.";
            if (!empty($interviewNotes)) {
                $cleanNotes = trim(preg_replace("/\\s+/", " ", $interviewNotes));
                $message .= " Notes: {$cleanNotes}.";
            }
            $message .= " Check portal for details.";
        } else {
            $message = "OJAMS: Congratulations {$applicantName}! Your application for {$jobTitle} at {$company} has been APPROVED. The hiring team will contact you soon.";
        }
    } else {
        $message = "OJAMS: Hi {$applicantName}, thank you for applying for {$jobTitle} at {$company}. We regret to inform you that your application was not selected. Keep applying on OJAMS!";
    }

    return sendSms($phone, $message);
}
