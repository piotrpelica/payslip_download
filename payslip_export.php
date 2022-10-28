#!/usr/bin/php
<?php

define('SITE', 'https://eservices.accessacloud.com');
define('USERAGENT', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2');
define('USERNAME', 'PUT_YOUR_EMAIL_HERE');
define('PASSWORD', 'PUT_YOUR_PASSWORD_HERE');

function init_curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

    return $ch;
}

function run_curl($ch)
{
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// Clear cookies
file_put_contents('cookies.txt', '');

// Get token
$ch = init_curl('/Global/Account/Login?ReturnUrl=%2F');
$result = run_curl($ch);
$xml = new DOMDocument();
$xml->loadHtml($result);
$xpath = new DomXpath($xml);
$tokenNode = $xpath->query('//input[@name="__RequestVerificationToken"][1]')->item(0);
$token = $tokenNode->attributes->getNamedItem('value')->nodeValue;

// Log in
$ch = init_curl('/Global/Account/LegacyLogin');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'ReturnUrl' => '/',
    'Username' => USERNAME,
    '__RequestVerificationToken' => $token
]));

$result = run_curl($ch);
$xml = new DOMDocument();
$xml->loadHtml($result);
$xpath = new DomXpath($xml);
$tokenNode = $xpath->query('//input[@name="__RequestVerificationToken"][1]')->item(0);
$token = $tokenNode->attributes->getNamedItem('value')->nodeValue;

// Log in again, but with password
$ch = init_curl('/Global/Account/Login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'Username' => USERNAME,
    'Password' => PASSWORD,
    '__RequestVerificationToken' => $token
]));

$result = run_curl($ch);

// Pretend to follow the normal flow
$ch = init_curl('/Global/Account/PostLogin');
$result = run_curl($ch);

$ch = init_curl('/Global/Home/RedirectToMenuItem');
$result = run_curl($ch);

$ch = init_curl('/SS/EEDetails');
$result = run_curl($ch);

if (!strstr($result, 'LogoutButton')) {
    echo "!ERR Unable to log in\n";
    exit;
}

// List payslips
$ch = init_curl('/SS/EEPayslips/Grid_Read');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'sort' => '',
    'group' => '',
    'filter' => ''
]));
$result = run_curl($ch);
$payslips = json_decode($result, true);

if (!is_array($payslips)) {
    echo "!ERR Unable to list payslips\n";
    exit;
}

// Get payslip history
$exportedPayslips = [];
if (($history = file_get_contents('payslipHistory.json'))) {
    $exportedPayslips = json_decode($history, true);
}
$payslipsExportedToday = 0;

// Export payslips
$payslips = $payslips['Data'];
foreach ($payslips as $payslip) {
    $payslipName = explode('T', $payslip['PaymentDate'])[0];

    if (in_array($payslip['Id'], $exportedPayslips)) {
        echo 'Skipping ' . $payslipName . '...' . "\n";
        continue;
    }
    echo 'Processing ' . $payslipName . '...' . "\n";

    $ch = init_curl('/SS/EEPayslips/ExportMultipleAsPdf');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'ids' => ['0' => $payslip['EncryptedId']]
    ]));
    $output = run_curl($ch);

    if (empty($output)) {
        echo "!ERR Unable to download PDF\n";
        continue;
    }

    if (!file_put_contents('payslips/' . $payslipName . '.pdf', $output)) {
        echo "!ERR Unable to save PDF\n";
        continue;
    }

    $exportedPayslips[] = $payslip['Id'];
    $payslipsExportedToday++;
    file_put_contents('payslipHistory.json', json_encode($exportedPayslips));
}

echo "Payslips exported today\t\t$payslipsExportedToday\n";
echo "Payslips exported in total\t" . count($exportedPayslips) . "\n";
echo "\n";
