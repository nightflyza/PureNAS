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

/**
 * Gets active subscribers for REST API.
 *
 * @param string $family
 * @param string $table
 * @param string $set
 *
 * @return array
 */
function apiGetActiveSubscribers($family, $table, $set) {
    $cmd = 'nft list set '.$family.' '.$table.' '.$set.' 2>/dev/null';
    $output = sudoRun($cmd);
    if (empty($output)) {
        return(array());
    }
    preg_match_all('/\b(\d+\.\d+\.\d+\.\d+)\b/', $output, $matches);
    return(array_unique($matches[1]));
}

/**
 * Gets all traffic control classes for REST API.
 *
 * @param string $dev1
 * @param string $dev2
 *
 * @return array
 */
function apiGetAllTcClasses($dev1, $dev2) {
    $classes = array();
    $devices = array($dev1, $dev2);
    foreach ($devices as $dev) {
        $cmd = 'tc -s -j class show dev '.$dev.' 2>/dev/null';
        $output = sudoRun($cmd);
        if (empty($output)) {
            continue;
        }
        $data = json_decode($output, true);
        if (is_array($data)) {
            foreach ($data as $class) {
                $originalHandle = '';
                if (isset($class['handle'])) {
                    $originalHandle = $class['handle'];
                } elseif (isset($class['classid'])) {
                    $originalHandle = $class['classid'];
                }
                
                $classid = $originalHandle;
                if (isset($class['leaf']) and preg_match('/^0x([0-9a-f]+)$/i', $class['leaf'], $matches)) {
                    $classid = '1:0x'.$matches[1];
                }
                
                $parsed = array(
                    'dev' => $dev,
                    'classid' => $classid,
                    'original_handle' => $originalHandle,
                    'parent' => isset($class['parent']) ? $class['parent'] : '',
                );
                if (isset($class['htb'])) {
                    $htb = $class['htb'];
                    $parsed['rate'] = isset($htb['rate']) ? $htb['rate'] : 'N/A';
                    $parsed['ceil'] = isset($htb['ceil']) ? $htb['ceil'] : 'N/A';
                    $parsed['quantum'] = isset($htb['quantum']) ? $htb['quantum'] : 'N/A';
                    $parsed['burst'] = isset($htb['burst']) ? $htb['burst'] : 'N/A';
                    $parsed['cburst'] = isset($htb['cburst']) ? $htb['cburst'] : 'N/A';
                } elseif (isset($class['rate'])) {
                    $parsed['rate'] = $class['rate'];
                    $parsed['ceil'] = isset($class['ceil']) ? $class['ceil'] : 'N/A';
                    $parsed['burst'] = isset($class['burst']) ? $class['burst'] : 'N/A';
                    $parsed['cburst'] = isset($class['cburst']) ? $class['cburst'] : 'N/A';
                } else {
                    $parsed['rate'] = 'N/A';
                    $parsed['ceil'] = 'N/A';
                    $parsed['burst'] = 'N/A';
                    $parsed['cburst'] = 'N/A';
                }
                if (isset($class['stats'])) {
                    $stats = $class['stats'];
                    if (isset($stats['dropped'])) {
                        $parsed['dropped'] = (int)$stats['dropped'];
                    }
                    if (isset($stats['overlimits'])) {
                        $parsed['overlimits'] = (int)$stats['overlimits'];
                    }
                } else {
                    if (isset($class['dropped'])) {
                        $parsed['dropped'] = (int)$class['dropped'];
                    }
                    if (isset($class['overlimits'])) {
                        $parsed['overlimits'] = (int)$class['overlimits'];
                    }
                }
                $classes[] = $parsed;
            }
        }
    }
    return($classes);
}

/**
 * Gets all traffic control filters for REST API.
 *
 * @param string $dev1
 * @param string $dev2
 *
 * @return array
 */
function apiGetAllTcFilters($dev1, $dev2) {
    $filters = array();
    $devices = array($dev1, $dev2);
    foreach ($devices as $dev) {
        $cmd = 'tc -s -p filter show dev '.$dev.' 2>/dev/null';
        $output = sudoRun($cmd);
        if (empty($output)) {
            continue;
        }
        $lines = explode(PHP_EOL, trim($output));
        $currentFilter = null;
        foreach ($lines as $line) {
            if (preg_match('/^filter (.*)$/', $line, $matches)) {
                if ($currentFilter !== null) {
                    $currentFilter['dev'] = $dev;
                    $filters[] = $currentFilter;
                }
                $currentFilter = array(
                    'details' => $matches[1],
                    'full_details' => $line
                );
                if (preg_match('/pref (\d+)/', $line, $m)) {
                    $currentFilter['pref'] = $m[1];
                }
                if (preg_match('/handle ([0-9a-f:]+)/', $line, $m)) {
                    $currentFilter['handle'] = $m[1];
                }
            } elseif ($currentFilter !== null) {
                $currentFilter['full_details'] .= PHP_EOL.$line;
                $currentFilter['details'] .= ' '.trim($line);
            }
        }
        if ($currentFilter !== null) {
            $currentFilter['dev'] = $dev;
            $filters[] = $currentFilter;
        }
    }
    return($filters);
}

/**
 * Gets all ARP entries for REST API.
 *
 * @return array
 */
function apiGetAllArpEntries() {
    $arpEntries = array();
    $cmd = 'ip neigh show 2>/dev/null';
    $output = sudoRun($cmd);
    if (!empty($output)) {
        $lines = explode(PHP_EOL, trim($output));
        foreach ($lines as $line) {
            if (preg_match('/^([\d.]+)\s+/', $line, $ipMatch)) {
                $ip = $ipMatch[1];
                $mac = null;
                $flag = null;
                
                if (preg_match('/\b([0-9a-f]{2}(?::[0-9a-f]{2}){5})\b/i', $line, $macMatch)) {
                    $mac = $macMatch[1];
                }
                
                if (preg_match('/\b(PERMANENT|permanent)\b/i', $line)) {
                    $flag = 'P';
                } elseif (preg_match('/\b(DELAY|delay)\b/i', $line)) {
                    $flag = 'D';
                } elseif (preg_match('/\b(REACHABLE|reachable)\b/i', $line)) {
                    $flag = 'R';
                } elseif (preg_match('/\b(INCOMPLETE|incomplete|FAILED|failed)\b/i', $line)) {
                    $flag = 'I';
                } else {
                    $flag = '';
                }
                
                $arpEntries[$ip] = array(
                    'mac' => $mac,
                    'flag' => $flag
                );
            }
        }
    }
    return($arpEntries);
}

/**
 * Returns array of all subscribers with detailed information
 *
 * @return array
 */
function getAllSubscribersDetailed() {
    global $config;
    
    $requiredOptions = array('VLAN_BRIDGE_ENABLED', 'BRIDGE_NAME', 'LAN_IF', 'IFB_IF', 'NFT_FAMILY', 'NFT_TABLE', 'NFT_ACTIVE_SET', 'NFT_INACTIVE_SET');
    
    foreach ($requiredOptions as $option) {
        if (!isset($config[$option])) {
            return (array('error' => $option.' not found in config'));
        }
    }
    
    $vlanBridgeEnabled = $config['VLAN_BRIDGE_ENABLED'];
    if ($vlanBridgeEnabled === 'YES') {
        $userGwIf = $config['BRIDGE_NAME'];
    } else {
        $userGwIf = $config['LAN_IF'];
    }
    
    $ifbIf = $config['IFB_IF'];
    $nftFamily = $config['NFT_FAMILY'];
    $nftTable = $config['NFT_TABLE'];
    $nftSet = $config['NFT_ACTIVE_SET'];
    $nftInactiveSet = $config['NFT_INACTIVE_SET'];
    
    $activeIPs = apiGetActiveSubscribers($nftFamily, $nftTable, $nftSet);
    $inactiveIPs = apiGetActiveSubscribers($nftFamily, $nftTable, $nftInactiveSet);
    $allClasses = apiGetAllTcClasses($userGwIf, $ifbIf);
    $allFilters = apiGetAllTcFilters($userGwIf, $ifbIf);
    
    $classesByHash = array();
    $filtersByIP = array();
    
    foreach ($allClasses as $class) {
        $classid = isset($class['classid']) ? $class['classid'] : '';
        $hash = null;
        
        if (preg_match('/^1:0x([0-9a-f]+)$/i', $classid, $matches)) {
            $hash = hexdec($matches[1]);
        } elseif (preg_match('/^1:(\d+)$/', $classid, $matches)) {
            $hash = (int)$matches[1];
        }
        
        if ($hash !== null) {
            if (!isset($classesByHash[$hash])) {
                $classesByHash[$hash] = array();
            }
            $classesByHash[$hash][] = $class;
        }
    }
    
    foreach ($allFilters as $filter) {
        $dev = isset($filter['dev']) ? $filter['dev'] : '';
        $details = isset($filter['details']) ? $filter['details'] : '';
        if ($dev === $ifbIf and preg_match('/match ip src ([\d.]+)\/32/i', $details, $matches)) {
            $ip = $matches[1];
            if (!isset($filtersByIP[$ip])) {
                $filtersByIP[$ip] = array();
            }
            $filtersByIP[$ip][] = $filter;
        }
    }
    
    $ipToHash = array();
    $ipToState = array();
    
    foreach ($activeIPs as $ip) {
        $ipToHash[$ip] = calculateHash($ip);
        $ipToState[$ip] = 'ACTIVE';
    }
    
    foreach ($inactiveIPs as $ip) {
        if (!isset($ipToHash[$ip])) {
            $ipToHash[$ip] = calculateHash($ip);
        }
        $ipToState[$ip] = 'INACTIVE';
    }
    
    $allIPs = array_unique(array_merge($activeIPs, $inactiveIPs));
    sort($allIPs);
    
    $arpEntries = apiGetAllArpEntries();
    
    $result = array();
    foreach ($allIPs as $ip) {
        $hash = isset($ipToHash[$ip]) ? $ipToHash[$ip] : null;
        $state = isset($ipToState[$ip]) ? $ipToState[$ip] : 'UNKNOWN';
        
        $mac = null;
        if (isset($arpEntries[$ip])) {
            $mac = $arpEntries[$ip]['mac'];
        }
        
        $subscriberData = getSubscriberData($ip, $state, $mac, $hash, $classesByHash, $ifbIf, $filtersByIP);
        $result[$ip] = $subscriberData;
    }
    
    return ($result);
}


/**
 * Gets subscriber data for REST API.
 *
 * @param string $ip
 * @param string $state
 * @param string|null $mac
 * @param int|null $hash
 * @param array $classesByHash
 * @param string $ifbIf
 * @param array $filtersByIP
 *
 * @return array
 */
function getSubscriberData($ip, $state, $mac, $hash, $classesByHash, $ifbIf, $filtersByIP) {
    $result = array(
        'ip' => $ip,
        'state' => $state,
        'mac' => isset($mac) ? $mac : null,
        'ratedown' => 'unlimited',
        'rateup' => 'unlimited',
        'hits' => null
    );
    
    if ($hash === null) {
        return ($result);
    }
    
    $downRate = 'unlimited';
    $upRate = 'unlimited';
    
    if (isset($classesByHash[$hash])) {
        foreach ($classesByHash[$hash] as $class) {
            $dev = isset($class['dev']) ? $class['dev'] : '';
            $rate = isset($class['rate']) ? $class['rate'] : null;
            
            if ($rate !== null and $rate !== 'N/A' and is_numeric($rate)) {
                $formattedRate = formatRate($rate);
                $displayRate = formatRateDisplay($formattedRate);
                
                if ($dev === $ifbIf) {
                    $upRate = $displayRate;
                } else {
                    $downRate = $displayRate;
                }
            }
        }
    }
    
    $result['ratedown'] = $downRate;
    $result['rateup'] = $upRate;
    
    $hitsCount = null;
    if (($downRate !== 'unlimited' or $upRate !== 'unlimited') and isset($filtersByIP[$ip])) {
        foreach ($filtersByIP[$ip] as $filter) {
            $details = isset($filter['details']) ? $filter['details'] : '';
            if (preg_match('/success (\d+)/', $details, $matches)) {
                $hitsCount = (int)$matches[1];
                break;
            }
        }
        if ($hitsCount === null) {
            $hitsCount = 0;
        }
    }
    $result['hits'] = $hitsCount;
    
    return ($result);
}

