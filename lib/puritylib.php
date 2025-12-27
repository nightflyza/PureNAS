<?php

function parseConfig($file) {
    $config = array();
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) or $line[0] === '#') {
            continue;
        }
        if (preg_match('/^([A-Z_]+)="?([^"]+)"?$/', $line, $matches)) {
            $config[$matches[1]] = $matches[2];
        }
    }
    return($config);
}

function getActiveSubscribers($family, $table, $set) {
    $cmd = 'nft list set '.$family.' '.$table.' '.$set.' 2>/dev/null';
    $output = shell_exec($cmd);
    if (empty($output)) {
        return(array());
    }
    preg_match_all('/\b(\d+\.\d+\.\d+\.\d+)\b/', $output, $matches);
    return(array_unique($matches[1]));
}

function calculateHash($ip) {
    $parts = explode('.', $ip);
    if (count($parts) < 4) {
        return(0);
    }
    $c = (int)$parts[2];
    $d = (int)$parts[3];
    $hash = ($c << 8) | $d;
    if ($hash === 0) {
        $hash = 1;
    }
    return($hash);
}

function getAllTcClasses($dev1, $dev2) {
    $classes = array();
    $devices = array($dev1, $dev2);
    foreach ($devices as $dev) {
        $cmd = 'tc -j class show dev '.$dev.' 2>/dev/null';
        $output = shell_exec($cmd);
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
                $classes[] = $parsed;
            }
        }
    }
    return($classes);
}

function getAllTcQdiscs($dev1, $dev2) {
    $qdiscs = array();
    $devices = array($dev1, $dev2);
    foreach ($devices as $dev) {
        $cmd = 'tc -j qdisc show dev '.$dev.' 2>/dev/null';
        $output = shell_exec($cmd);
        if (empty($output)) {
            continue;
        }
        $data = json_decode($output, true);
        if (is_array($data)) {
            foreach ($data as $qdisc) {
                $qdisc['dev'] = $dev;
                $qdiscs[] = $qdisc;
            }
        }
    }
    return($qdiscs);
}

function formatRate($rateBytes) {
    if (!is_numeric($rateBytes) or $rateBytes <= 0) {
        return 'unlimited';
    }
    
    $rateBits = $rateBytes * 8;
    
    if ($rateBits >= 1000000000) {
        return round($rateBits / 1000000000, 2).'Gbit';
    } elseif ($rateBits >= 1000000) {
        return round($rateBits / 1000000, 2).'Mbit';
    } elseif ($rateBits >= 1000) {
        return round($rateBits / 1000, 0).'Kbit';
    } else {
        return $rateBits.'bit';
    }
}

function formatBytes($bytes) {
    if (!is_numeric($bytes) or $bytes <= 0) {
        return '0B';
    }
    
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2).'GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2).'MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2).'KB';
    } else {
        return $bytes.'B';
    }
}

function getAllTcFilters($dev1, $dev2) {
    $filters = array();
    $devices = array($dev1, $dev2);
    foreach ($devices as $dev) {
        $cmd = 'tc -s -p filter show dev '.$dev.' 2>/dev/null';
        $output = shell_exec($cmd);
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

function formatRateDisplay($rateStr) {
    if ($rateStr === 'unlimited') {
        return $rateStr;
    }
    if (preg_match('/^(\d+\.?\d*)Mbit$/', $rateStr, $matches)) {
        return (int)floatval($matches[1]).' Mbit';
    }
    if (preg_match('/^(\d+\.?\d*)([KMGT]bit)$/', $rateStr, $matches)) {
        return $matches[1].' '.$matches[2];
    }
    if (preg_match('/^(\d+\.?\d*)bit$/', $rateStr, $matches)) {
        return $matches[1].' bit';
    }
    return $rateStr;
}

function getAllArpEntries() {
    $arpEntries = array();
    $cmd = 'ip neigh show 2>/dev/null';
    $output = shell_exec($cmd);
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

function formatArpFlag($flag) {
    if (empty($flag)) {
        return '-';
    }
    return '['.$flag.']';
}

function formatRates($downRate, $upRate) {
    $downFormatted = formatRateDisplay($downRate);
    $upFormatted = formatRateDisplay($upRate);
    return $downFormatted.' / '.$upFormatted;
}

function renderHeader($extensive) {
    if ($extensive) {
        printf("%-15s %-10s %-18s %-6s %-10s %-12s %-8s %-8s %-8s %-8s %-12s %-10s %-24s %-10s\n",
            'IP', 'STATE', 'MAC', 'FLAG', 'CLASSID', 'HEX', 'O3', 'O4', 'TABLE_ID', 'BUCKET', 'FILTER_ID', 'HANDLE', 'RATES', 'HITS');
        printf("%-15s %-10s %-18s %-6s %-10s %-12s %-8s %-8s %-8s %-8s %-12s %-10s %-24s %-10s\n",
            '---', '-----', '---', '----', '-------', '---', '---', '---', '--------', '------', '----------', '------', '----------------------', '----');
    } else {
        printf("%-15s %-10s %-18s %-6s %-10s %-10s %-24s %-10s\n",
            'IP', 'STATE', 'MAC', 'FLAG', 'CLASSID', 'HANDLE', 'RATES', 'HITS');
        printf("%-15s %-10s %-18s %-6s %-10s %-10s %-24s %-10s\n",
            '---', '-----', '---', '----', '-------', '------', '----------------------', '----');
    }
}

function parseNetwork($network) {
    if (preg_match('/^(\d+\.\d+\.\d+\.\d+)\/(\d+)$/', $network, $matches)) {
        $ip = $matches[1];
        $prefix = (int)$matches[2];
        
        $parts = explode('.', $ip);
        $ipLong = ((int)$parts[0] << 24) | ((int)$parts[1] << 16) | ((int)$parts[2] << 8) | (int)$parts[3];
        
        $mask = (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
        $networkLong = $ipLong & $mask;
        $broadcastLong = $networkLong | (~$mask & 0xFFFFFFFF);
        
        return(array(
            'network' => $networkLong,
            'broadcast' => $broadcastLong,
            'prefix' => $prefix
        ));
    }
    return(null);
}

function generateSequentialIPs($networkInfo, $sampleSubscribersCount) {
    $network = $networkInfo['network'];
    $broadcast = $networkInfo['broadcast'];
    
    $networkOctet1 = ($network >> 24) & 0xFF;
    $networkOctet2 = ($network >> 16) & 0xFF;
    $networkOctet3 = ($network >> 8) & 0xFF;
    $networkOctet4 = $network & 0xFF;
    
    $broadcastOctet1 = ($broadcast >> 24) & 0xFF;
    $broadcastOctet2 = ($broadcast >> 16) & 0xFF;
    $broadcastOctet3 = ($broadcast >> 8) & 0xFF;
    $broadcastOctet4 = $broadcast & 0xFF;
    
    $ips = array();
    $octet1 = $networkOctet1;
    $octet2 = $networkOctet2;
    $octet3 = $networkOctet3;
    $octet4 = max(2, $networkOctet4 + 1);
    
    while (count($ips) < $sampleSubscribersCount) {
        if ($octet1 > $broadcastOctet1) {
            break;
        }
        if ($octet1 === $broadcastOctet1 and $octet2 > $broadcastOctet2) {
            break;
        }
        if ($octet1 === $broadcastOctet1 and $octet2 === $broadcastOctet2 and $octet3 > $broadcastOctet3) {
            break;
        }
        if ($octet1 === $broadcastOctet1 and $octet2 === $broadcastOctet2 and $octet3 === $broadcastOctet3 and $octet4 > min(254, $broadcastOctet4 - 1)) {
            break;
        }
        
        $ipLong = ($octet1 << 24) | ($octet2 << 16) | ($octet3 << 8) | $octet4;
        if ($ipLong >= $network and $ipLong <= $broadcast) {
            $ips[] = $octet1.'.'.$octet2.'.'.$octet3.'.'.$octet4;
        }
        
        $octet4++;
        if ($octet4 > 254) {
            $octet4 = 2;
            $octet3++;
            if ($octet3 > 255) {
                $octet3 = 0;
                $octet2++;
                if ($octet2 > 255) {
                    $octet2 = 0;
                    $octet1++;
                }
            }
        }
    }
    
    if (count($ips) < $sampleSubscribersCount) {
        return(null);
    }
    
    return($ips);
}

function renderSubscriberRow($ip, $state, $mac, $flag, $hash, $extensive, $classesByHash, $ifbIf, $filtersByIP) {
    $macDisplay = isset($mac) ? $mac : '-';
    $flagDisplay = formatArpFlag($flag);
    
    if ($hash === null) {
        if ($extensive) {
            printf("%-15s %-10s %-18s %-6s %-10s %-12s %-8s %-8s %-8s %-8s %-12s %-10s %-24s %-10s\n",
                $ip, $state, $macDisplay, $flagDisplay, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A / N/A', '-');
        } else {
            printf("%-15s %-10s %-18s %-6s %-10s %-10s %-24s %-10s\n",
                $ip, $state, $macDisplay, $flagDisplay, 'N/A', 'N/A', 'N/A / N/A', '-');
        }
        return;
    }
    
    $hashHex = dechex($hash);
    $classid = '1:'.$hash;
    $classidHex = '1:0x'.$hashHex;
    
    $parts = explode('.', $ip);
    if (count($parts) < 4) {
        $o3 = 'N/A';
        $o4 = 'N/A';
    } else {
        $o3 = $parts[2];
        $o4 = $parts[3];
    }
    
    $tableId = sprintf('%02x', ((int)$o3) + 1);
    $bucketHex = sprintf('%x', (int)$o4);
    $filterId = ($hash + 1) & 4095;
    $filterIdHex = sprintf('%x', $filterId);
    $fullHandle = $tableId.':'.$bucketHex.':'.$filterIdHex;
    
    $downRate = 'unlimited';
    $upRate = 'unlimited';
    
    if (isset($classesByHash[$hash])) {
        foreach ($classesByHash[$hash] as $class) {
            $dev = isset($class['dev']) ? $class['dev'] : '';
            $rate = isset($class['rate']) ? $class['rate'] : null;
            
            if ($rate !== null and $rate !== 'N/A' and is_numeric($rate)) {
                $formattedRate = formatRate($rate);
                if ($dev === $ifbIf) {
                    $upRate = $formattedRate;
                } else {
                    $downRate = $formattedRate;
                }
            }
        }
    }
    
    $rates = formatRates($downRate, $upRate);
    
    $hitsCount = '-';
    if ($rates !== 'unlimited / unlimited' and isset($filtersByIP[$ip])) {
        foreach ($filtersByIP[$ip] as $filter) {
            $details = isset($filter['details']) ? $filter['details'] : '';
            if (preg_match('/success (\d+)/', $details, $matches)) {
                $hitsCount = $matches[1];
                break;
            }
        }
        if ($hitsCount === '-') {
            $hitsCount = '0';
        }
    }
    
    if ($extensive) {
        printf("%-15s %-10s %-18s %-6s %-10s %-12s %-8s %-8s %-8s %-8s %-12s %-10s %-24s %-10s\n",
            $ip, $state, $macDisplay, $flagDisplay, $classid, $classidHex, $o3, $o4, $tableId, $bucketHex, $filterIdHex, $fullHandle, $rates, $hitsCount);
    } else {
        printf("%-15s %-10s %-18s %-6s %-10s %-10s %-24s %-10s\n",
            $ip, $state, $macDisplay, $flagDisplay, $classid, $fullHandle, $rates, $hitsCount);
    }
}