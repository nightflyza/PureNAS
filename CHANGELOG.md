# Changelog

## 0.0.2
- Changed VLAN interface naming convention (PR#1)
- core_update action script now allows update to main brainch or to release tags
- purenas.conf: new BYPASS_NAT_ENABLED and BYPASS_NAT_NETS options implemented (PR#2)

## 0.0.1
- purenas.conf: SFLOW_COLLECTOR option now optionally can accep multiple sflow collectors IPs.
- purenas.conf: SPOOF_ANY_DNS - redirect DNS from users to one IP.
- purenas.conf: FW_MACFIX - optional IP+MAC binding using firewall on user gateway. Default NO.
- purenas.conf: DISABLE_ARPFIX - optional; turn off static ARP for subscribers. Default NO.
- purenas.conf: BANNED_ICMP - block ICMP to/from listed IPs or CIDRs.
- REST API: better subscribers management behavior.
- Actions renamed: ip_ban/ip_unban => ban_ip/unban_ip, icmp_ban/icmp_unban => ban_icmp/unban_icmp.
- subscribers_show: optional 'help'; FLAG X when MAC in ARP differs from static binding (S).
- actions: subscriber_arp/unarp removed; use subscriber_mac/unmac instead.

