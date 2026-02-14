# Changelog

- subscribers_show: optional 'help'; FLAG X when MAC in ARP differs from static binding (S).
- FW_MACFIX: physical interface uses inet firewall input (fixed_clients/fixed_ips); bridge unchanged.
- actions: subscriber_arp/unarp renamed to subscriber_mac/unmac; subscriber_arp/unarp kept as legacy wrappers.
- purenas.conf: FW_MACFIX — optional. Enforce IP+MAC on user gateway; unbound hosts allowed. Default NO.
- purenas.conf: DISABLE_ARPFIX — optional. Turn off static ARP for subscribers. Default NO.
