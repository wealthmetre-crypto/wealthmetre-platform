<?php
require_once __DIR__ . '/config/alert_config.php';
require_once __DIR__ . '/helpers/LeadAlert.php';

$testLead = [
    'id'             => 'TEST-001',
    'name'           => 'Saurabh Sharma',
    'mobile'         => '9999999999',
    'city'           => 'Jaipur',
    'loan_type'      => 'LAP',
    'loan_amount'    => 7500000,
    'property_value' => 15000000,
    'monthly_income' => 75000,
    'cibil_score'    => '720',
    'employment_type'=> 'Self Employed',
    'property_type'  => 'Residential',
];

$result = sendLeadAlert($testLead, 'Diva AI');

echo '<pre>';
print_r($result);
echo '</pre>';