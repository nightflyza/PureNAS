# Changelog

- REST API: better subscribers management behavior.
- ip_ban/ip_unban now operates with IPs and CIDR.
- subscribers_show: optional 'help'; FLAG X when MAC in ARP differs from static binding (S).
- actions: subscriber_arp/unarp renamed to subscriber_mac/unmac; subscriber_arp/unarp kept as legacy wrappers.
- purenas.conf: FW_MACFIX - optional. Enforce IP+MAC on user gateway; unbound hosts allowed. Default NO.
- purenas.conf: DISABLE_ARPFIX i optional. Turn off static ARP for subscribers. Default NO.
