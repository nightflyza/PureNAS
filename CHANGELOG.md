# Changelog

- purenas.conf: SPOOF_ANY_DNS - redirect DNS from users to one IP.
- REST API: better subscribers management behavior.
- ip_ban/ip_unban: support IP and CIDR; banned_hosts set uses interval.
- subscribers_show: optional 'help'; FLAG X when MAC in ARP differs from static binding (S).
- actions: subscriber_arp/unarp renamed to subscriber_mac/unmac; legacy wrappers kept.
- purenas.conf: FW_MACFIX — optional IP+MAC on user gateway. Default NO.
- purenas.conf: DISABLE_ARPFIX — optional; turn off static ARP for subscribers. Default NO.
