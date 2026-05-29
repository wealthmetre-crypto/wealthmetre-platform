<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$widgetId = defined('MSG91_WIDGET_ID') ? MSG91_WIDGET_ID : '';
$widgetToken = defined('MSG91_WIDGET_TOKEN') ? MSG91_WIDGET_TOKEN : '';

if ($widgetId === '' || $widgetToken === '') {
    jsonResponse([
        'success' => false,
        'message' => 'MSG91 widget config missing',
        'widget_id_length' => strlen($widgetId),
        'widget_token_length' => strlen($widgetToken),
    ], 500);
}

jsonResponse([
    'success' => true,
    'widget_id' => $widgetId,
    'widget_token' => $widgetToken,
    'widget_id_length' => strlen($widgetId),
    'widget_token_length' => strlen($widgetToken),
]);
