<?php

/**
 * WealthMetre Alert Logger
 * Writes all alert events to a daily log file for debugging and audit.
 */

class AlertLogger
{
    /**
     * Log an alert event.
     *
     * @param string $event     e.g. 'alert_sent', 'email_error', 'whatsapp_error'
     * @param array  $leadData
     * @param string $note      Additional context or error message
     */
    public static function log(string $event, array $leadData, string $note = ''): void
    {
        $logPath = ALERT_LOG_PATH;
        $logDir  = dirname($logPath);

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'event'       => $event,
            'source'      => $leadData['source']    ?? 'Unknown',
            'lead_id'     => $leadData['id']         ?? $leadData['session_id'] ?? null,
            'lender_name' => $leadData['name']       ?? $leadData['full_name'] ?? null,
            'mobile'      => $leadData['mobile']     ?? $leadData['phone'] ?? null,
            'product'     => $leadData['loan_type']  ?? $leadData['product'] ?? null,
            'note'        => $note,
        ];

        // In debug mode, include full lead data
        if (defined('ALERT_DEBUG_MODE') && ALERT_DEBUG_MODE) {
            $entry['full_data'] = $leadData;
        }

        $line = '[' . $entry['timestamp'] . '] '
            . strtoupper($event) . ' | '
            . 'Source: ' . $entry['source'] . ' | '
            . 'Lead: ' . $entry['lender_name'] . ' | '
            . 'Mobile: ' . $entry['mobile'] . ' | '
            . 'Product: ' . $entry['product']
            . (ALERT_DEBUG_MODE ? ' | ' . json_encode($entry) : '')
            . PHP_EOL;

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
