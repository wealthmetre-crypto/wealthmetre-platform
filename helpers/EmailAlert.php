<?php
if (!function_exists('sendEmailAlert')):
function sendEmailAlert(array $d): array
{
    $to      = defined('ALERT_EMAIL_TO')   ? ALERT_EMAIL_TO   : 'info@wealthmetre.com';
    $from    = defined('ALERT_EMAIL_FROM') ? ALERT_EMAIL_FROM : 'alerts@wealthmetre.com';
    $name    = $d['name']      ?? 'Unknown';
    $mobile  = $d['mobile']    ?? $d['phone'] ?? 'N/A';
    $city    = $d['city']      ?? 'N/A';
    $product = $d['loan_type'] ?? $d['product'] ?? 'N/A';
    $source  = $d['source']    ?? 'Website';
    $time    = $d['alert_time']?? date('d M Y, h:i A');

    $subject = "New Lead | {$source} | {$product} | {$name}";

    $body = "NEW LEAD - WealthMetre\n\n"
          . "Name:    {$name}\n"
          . "Mobile:  {$mobile}\n"
          . "City:    {$city}\n"
          . "Product: {$product}\n"
          . "Source:  {$source}\n"
          . "Time:    {$time}\n\n"
          . "Dashboard: https://wealthmetre.com/admin/leads.php";

    $headers = "From: WealthMetre <{$from}>\r\n"
             . "Reply-To: {$from}\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    $ok = @mail($to, $subject, $body, $headers);

    error_log('[EmailAlert] mail() result: ' . ($ok ? 'sent' : 'failed') . ' to ' . $to);

    return ['success' => $ok, 'method' => 'mail'];
}
endif;