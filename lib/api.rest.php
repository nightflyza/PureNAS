<?php

/**
 * Renders API response.
 *
 * @param array $data
 *
 * @return void
 */
function apiResponse($data=array()) {
    header('Content-Type: application/json');
    print(json_encode($data));
}

/**
 * Runs command with sudo if not running as root.
 *
 * @param string $command
 *
 * @return string
 */
function sudoRun($command) {
    if (posix_geteuid() !== 0) {
        $command = 'sudo '.$command;
    }
    return(shell_exec($command));
}

/**
 * Returns array of all active subscribers
 *
 * @return array
 */
function getAllActiveSubscribers() {
    global $config;
    $result=array();
    $output = sudoRun('nft list set '.$config['NFT_FAMILY'].' '.$config['NFT_TABLE'].' '.$config['NFT_ACTIVE_SET'].' 2>/dev/null');
    preg_match_all('/\b(\d+\.\d+\.\d+\.\d+)\b/', $output, $matches);
    if (!empty($matches[1])) {
        $result = array_unique($matches[1]);
    }
    return($result);
}
