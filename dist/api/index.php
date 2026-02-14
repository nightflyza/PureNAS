<?php

$scriptDir = dirname(__FILE__);
$configFile = $scriptDir . '/../../purenas.conf';
$libFile = $scriptDir . '/../../lib/puritylib.php';


require_once($libFile);
$config = parseConfig($configFile);
require_once($scriptDir . '/../../lib/api.ubrouting.php');
require_once($scriptDir . '/../../lib/api.rest.php');


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
        $response=array('error' => 'Unauthorized access');
        apiResponse($response);
        die();
    }
}

// API key validation if set
if (!empty($config['REST_API_KEY'])) {
    $apiKey=ubRouting::get('key','safe');
    if ($apiKey !== $config['REST_API_KEY']) {
        $response=array('error' => 'Invalid API key');
        apiResponse($response);
        die();
    }
}

// Handle different API actions
$action = ubRouting::get('subscriber', 'safe');
$ipAction = ubRouting::get('ip', 'safe');
$systemAction = ubRouting::get('system', 'safe');

// Handle subscriber actions
if ($action === 'getall') {
    $allSubscribers = getAllSubscribersDetailed();
    apiResponse($allSubscribers);
    exit();
}

if ($action === 'allow') {
    $ip = ubRouting::get('ip', 'safe');
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/subscriber_allow', array($ip));
    apiResponse($result);
    exit();
}

if ($action === 'disallow') {
    $ip = ubRouting::get('ip', 'safe');
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/subscriber_disallow', array($ip));
    apiResponse($result);
    exit();
}

if ($action === 'shape') {
    $ip = ubRouting::get('ip', 'safe');
    $download = ubRouting::get('download', 'int');
    $upload = ubRouting::get('upload', 'int');
    
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    if (empty($download) or $download <= 0) {
        apiResponse(array('error' => 'Download speed in kbit/s is required and must be greater than 0'));
        exit();
    }
    
    $args = array($ip, $download);
    if (!empty($upload) and $upload > 0) {
        $args[] = $upload;
    }
    
    $result = apiExecuteAction('actions/subscriber_shape', $args);
    apiResponse($result);
    exit();
}

if ($action === 'unshape') {
    $ip = ubRouting::get('ip', 'safe');
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/subscriber_unshape', array($ip));
    apiResponse($result);
    exit();
}

if ($action === 'mac') {
    $ip = ubRouting::get('ip', 'safe');
    $mac = ubRouting::get('mac', 'safe');
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    if (empty($mac)) {
        apiResponse(array('error' => 'MAC address is required'));
        exit();
    }
    if (!apiValidateMAC($mac)) {
        apiResponse(array('error' => 'Invalid MAC address format'));
        exit();
    }
    $result = apiExecuteAction('actions/subscriber_mac', array($ip, $mac));
    apiResponse($result);
    exit();
}

if ($action === 'unmac') {
    $ip = ubRouting::get('ip', 'safe');
    if (empty($ip)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($ip)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/subscriber_unmac', array($ip));
    apiResponse($result);
    exit();
}

// Handle system info action
if ($systemAction === 'info') {
    $result = apiGetSystemInfo();
    apiResponse($result);
    exit();
}

// Handle IP ban/unban actions
if ($ipAction === 'ban') {
    $addr = ubRouting::get('addr', 'safe');
    if (empty($addr)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($addr)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/ip_ban', array($addr));
    apiResponse($result);
    exit();
}

if ($ipAction === 'unban') {
    $addr = ubRouting::get('addr', 'safe');
    if (empty($addr)) {
        apiResponse(array('error' => 'IP address is required'));
        exit();
    }
    if (!apiValidateIP($addr)) {
        apiResponse(array('error' => 'Invalid IP address format'));
        exit();
    }
    $result = apiExecuteAction('actions/ip_unban', array($addr));
    apiResponse($result);
    exit();
}

// Default: render error with notice of not specified action

$apiResponse = array('error' => 'Not specified action');
apiResponse($apiResponse);
