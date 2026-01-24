<?php

$scriptDir = dirname(__FILE__);
$configFile = $scriptDir . '/../../purenas.conf';
$libFile = $scriptDir . '/../../lib/puritylib.php';


require_once($libFile);
$config = parseConfig($configFile);
require_once($scriptDir . '/../../lib/api.ubrouting.php');

$requiredOptions=array('REST_API_ENABLED', 'REST_API_KEY', 'REST_API_ALLOWED_IPS');

foreach ($requiredOptions as $option) {
    if (!isset($config[$option])) {
        die('Error: '.$option.' not found in config');
    }
}

// check if REST API is enabled
if ($config['REST_API_ENABLED'] !== 'YES') {
    die('Error: REST API is not enabled');
}

// check if remote IP is in allowed IPs
if (!empty($config['REST_API_ALLOWED_IPS'])) {
    $allowedIps = explode(' ', $config['REST_API_ALLOWED_IPS']);
    $allowedIps = array_map('trim', $allowedIps);
    $remoteIP=$_SERVER['REMOTE_ADDR'];
    if (!in_array($remoteIP, $allowedIps)) {
        die('Error: Unauthorized access');
    }
}

if (!empty($config['REST_API_KEY'])) {
    $apiKey=ubRouting::get('key','safe');
    if ($apiKey !== $config['REST_API_KEY']) {
        die('Error: Invalid API key');
    }
}