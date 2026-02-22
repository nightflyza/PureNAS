# PureNAS Configuration Reference

This document describes all available configuration options in `purenas.conf`.

## Network Interfaces

### `LAN_IF`

Specifies the internal physical interface name. Use the interface name as shown by `ip link` or `ifconfig`.

**Format**: Interface name (e.g., `eth0`, `enp0s3`, `ens33`)

**Example**: `LAN_IF="eth0"`

### `WAN_IF`

Specifies the external physical interface name. Use the interface name as shown by `ip link` or `ifconfig`.

**Format**: Interface name (e.g., `eth1`, `enp0s8`, `ens34`)

**Example**: `WAN_IF="eth1"`

### `IF_SPEED`

Specifies the physical interface speed. Used for bandwidth calculations and shaping. Common values are `1gbit` or `10gbit`.

**Format**: Speed with unit suffix (e.g., `1gbit`, `10gbit`)

**Example**: `IF_SPEED="10gbit"`

## VLAN and Bridge Configuration

### `VLAN_BRIDGE_ENABLED`

Enable or disable VLAN bridge configuration. Set to `YES` to enable VLAN bridge functionality.

**Format**: `YES` or `NO`

### `VLAN_FROM`

Starting VLAN ID for implicit VLAN generation. This value is only used when `VLAN_COUNT` is greater than 0. VLANs will be generated starting from this ID.

**Format**: Integer (1-4094)

**Example**: `VLAN_FROM=101` (with `VLAN_COUNT=20` generates VLANs 101-120)

### `VLAN_COUNT`

Number of VLANs to generate automatically from the starting VLAN ID (`VLAN_FROM`). Set to `0` to disable implicit VLAN generation.

**Format**: Integer (0 or greater)

**Example**: `VLAN_COUNT=20` (generates 20 VLANs starting from `VLAN_FROM`)

### `EXPLICT_VLANS`

Explicit VLAN IDs to create and add to the bridge. Use this when you need specific VLANs that don't follow the sequential pattern. Multiple VLAN IDs can be specified. Leave empty if not needed.

**Format**: Space-separated list of VLAN IDs

**Example**: `EXPLICT_VLANS="200 201 202 500"`

### `BRIDGE_NAME`

Name of the bridge interface to create and use for user network interactions.

**Format**: Bridge interface name

**Example**: `BRIDGE_NAME="br0"`

### `BRIDGE_MAC`

MAC address to assign to the bridge interface. Leave empty to use the system-generated MAC address.

**Format**: MAC address in format `XX:XX:XX:XX:XX:XX` or empty string

**Example**: `BRIDGE_MAC="00:11:22:33:44:55"` or `BRIDGE_MAC=""`

## User Network Configuration

### `USER_NET`

Network range for the user network in CIDR notation. This defines the IP address pool available for subscribers. Multiple networks can be specified as a space-separated list, and all will be used for firewall rules. **Important**: With `SHAPER_ENABLED`, only the first network is used for shaper hash table initialization (as /16). With `SPLIT_SHAPER_ENABLED`, all networks are supported for shaping.

**Format**: Space-separated list of network addresses in CIDR notation (IP/MASK)

**Example**: `USER_NET="172.16.0.0/17"` or `USER_NET="172.16.0.0/24 172.16.14.0/24"`

### `USER_GATEWAY_IP`

Gateway IP address for the user network in CIDR notation. This IP will be used as the default gateway for subscribers. Multiple gateway IPs can be specified and will all be assigned to the interface.

**Format**: Space-separated list of IP addresses with subnet masks (IP/MASK)

**Example**: `USER_GATEWAY_IP="172.16.0.1/17"` or `USER_GATEWAY_IP="172.16.0.1/24 172.16.1.1/24"`

### `FORCE_ASSIGN_GATEWAY_IP`

Force assignment of gateway IP addresses to user-connected interfaces. When enabled, the gateway IP will be automatically assigned to the appropriate interface.

**Format**: `YES` or `NO`

## WAN and Routing Configuration

### `WAN_IP`

Forced WAN interface IP configuration. Use this to manually configure IP addresses on the WAN interface. Multiple IP addresses can be assigned. Leave empty to use existing interface configuration.

**Format**: Space-separated list of IP addresses with subnet masks (IP/MASK)

**Example**: `WAN_IP="203.0.113.1/24 203.0.113.2/24"`

### `WAN_DEFAULT_ROUTE`

Forced IP address of the default route gateway. Use this to override the system's default gateway. Leave empty to use the system default route.

**Format**: IP address or empty string

**Example**: `WAN_DEFAULT_ROUTE="203.0.113.254"`

### `SYSTEM_DNS_RESOLVERS`

Override system DNS resolvers. Multiple DNS servers can be specified and will be added to `/etc/resolv.conf`. Leave empty to not override system DNS settings.

**Format**: Space-separated list of IP addresses

**Example**: `SYSTEM_DNS_RESOLVERS="8.8.8.8 8.8.4.4"`

### `STATIC_ROUTES`

Static routes configuration. Define custom routes for specific network destinations. Multiple routes can be specified.

**Format**: Space-separated list of routes in format `destination:gateway`

**Example**: `STATIC_ROUTES="10.0.0.0/8:192.168.1.1 172.16.0.0/12:192.168.1.2"`

## Firewall Configuration

### `PROTECTED_PORTS`

Ports on this host that are protected from all incoming traffic except from safe zones. Only IPs/networks listed in `SAFE_ZONES` can access these ports. Multiple ports can be specified.

**Format**: Space-separated list of port numbers

**Example**: `PROTECTED_PORTS="22 161 9999 5555"` or `PROTECTED_PORTS="22 80 443"`

### `SAFE_ZONES`

Network ranges or IP addresses that are allowed to access protected ports. These sources bypass the protection rules for ports listed in `PROTECTED_PORTS`. Multiple zones can be specified.

**Format**: Space-separated list of IP addresses or CIDR networks

**Example**: `SAFE_ZONES="127.0.0.1 192.168.0.0/24 192.168.3.0/24"`

## Access Control

### `ALLOWED_ALWAYS`

IP addresses that subscribers are allowed to access without any restrictions at any time, regardless of their active status or other firewall rules. Multiple IP addresses can be specified.

**Format**: Space-separated list of IP addresses

**Example**: `ALLOWED_ALWAYS="91.230.25.72 91.230.25.94"` or `ALLOWED_ALWAYS="8.8.8.8 1.1.1.1"`

### `ALLOWED_ONLY_DNS`

Only these DNS servers are allowed to be used for DNS resolution if set by any subscribers. All other DNS servers will be blocked. Multiple DNS servers can be specified.

**Format**: Space-separated list of IP addresses

**Example**: `ALLOWED_ONLY_DNS="8.8.8.8 1.1.1.1"` or `ALLOWED_ONLY_DNS="8.8.8.8 8.8.4.4 1.1.1.1"`

### `SPOOF_ANY_DNS`

Redirect DNS traffic from user networks to this IP.

- **When `ALLOWED_ONLY_DNS` is set:** Only requests whose destination is *not* in `ALLOWED_ONLY_DNS` are redirected to `SPOOF_ANY_DNS`. Typically set `SPOOF_ANY_DNS` to one of the allowed DNS IPs so all client DNS is forced through it.
- **When `ALLOWED_ONLY_DNS` is empty:** All DNS traffic from user networks (UDP/TCP port 53) is redirected to this IP.

Leave empty to disable DNS redirect.

**Format**: IP address or space-separated list (first IP used)

**Example**: `SPOOF_ANY_DNS="8.8.8.8"` or `SPOOF_ANY_DNS=""`

### `BANNED_HOSTS`

IP addresses or CIDR prefixes that are blocked without any exceptions. All traffic to/from these will be dropped regardless of other rules. Multiple entries can be specified.

**Format**: Space-separated list of IPv4 addresses or CIDRs (e.g. `/24`, `/16`)

**Example**: `BANNED_HOSTS="1.2.3.4"` or `BANNED_HOSTS="10.0.0.100 192.168.1.0/24"`

### `BANNED_ICMP`

IP addresses or CIDR prefixes for which only ICMP traffic is blocked (to and from). Other traffic is unaffected. Multiple entries can be specified. Also manageable via `actions/ban_icmp` and `actions/unban_icmp`.

**Format**: Space-separated list of IPv4 addresses or CIDRs

**Example**: `BANNED_ICMP=""` or `BANNED_ICMP="1.2.3.4 10.0.0.0/24"`

## Port Blocking

### `BLOCKED_INCOMING_PORTS`

Ports for which all incoming traffic to the user network is denied. Subscribers cannot receive connections on these ports. Multiple ports can be specified.

**Format**: Space-separated list of port numbers

**Example**: `BLOCKED_INCOMING_PORTS="53 23"` or `BLOCKED_INCOMING_PORTS="135 139 445"`

### `BLOCKED_OUTGOING_PORTS`

Ports for which all outgoing traffic from users to the internet is blocked. Subscribers cannot initiate connections to these ports. Multiple ports can be specified.

**Format**: Space-separated list of port numbers

**Example**: `BLOCKED_OUTGOING_PORTS="25 28316 28016 28015"` or `BLOCKED_OUTGOING_PORTS="25 587"`

## Bandwidth Shaping

### `SHAPER_ENABLED`

Enable or disable bandwidth limiting/shaping for subscribers. When enabled, traffic shaping rules can be applied to individual subscribers. Mutually exclusive with `SPLIT_SHAPER_ENABLED`; when using split shaper, set this to `NO`.

**Format**: `YES` or `NO`

### `SPLIT_SHAPER_ENABLED`

Enable the split (multi-network) shaper model. Supports multiple networks in `USER_NET` (e.g. `10.10.0.0/24 172.16.0.0/24 10.20.0.0/25`). Mutually exclusive with `SHAPER_ENABLED`; when using split shaper, set `SHAPER_ENABLED=NO`. Optional; if not set, defaults to `NO` and the original single-network shaper is used.

**Format**: `YES` or `NO`

### `IFB_IF`

Intermediate Functional Block (IFB) interface name used for ingress traffic shaping. This virtual interface is created automatically for handling incoming traffic shaping.

**Format**: Interface name starting with `ifb`

**Example**: `IFB_IF="ifb0"`

## NAT Configuration

### `NAT_ENABLED`

Enable or disable NAT (Network Address Translation) for the user network. When enabled, subscriber traffic will be translated to the WAN interface IP or NAT pool IPs.

**Format**: `YES` or `NO`

### `POOL_NAT_ENABLED`

Enable NAT using pools of IP addresses instead of the WAN interface IP. Requires `NAT_ENABLED=YES` and `POOL_NAT_NETS` to be configured.

**Format**: `YES` or `NO`

### `POOL_NAT_NETS`

Network pools to use for NAT when `POOL_NAT_ENABLED=YES`. Traffic will be distributed across these IP addresses. Multiple network pools can be specified.

**Format**: Space-separated list of networks in CIDR notation

**Example**: `POOL_NAT_NETS="203.0.113.0/28 198.51.100.0/28"`

## NetFlow Sensor

### `NETFLOW_SENSOR_ENABLED`

Enable or disable NetFlow sensor for traffic monitoring. **Warning**: Enabling NetFlow significantly affects performance.

**Format**: `YES` or `NO`

### `NETFLOW_SAMPLING_RATE`

NetFlow sampling rate. Higher values mean less sampling (more packets analyzed). Lower values reduce CPU usage but provide less detailed data.

**Format**: Integer (typically 100-10000)

**Example**: `NETFLOW_SAMPLING_RATE="100"` (sample 1 in 100 packets) or `NETFLOW_SAMPLING_RATE="1000"` (sample 1 in 1000 packets)

### `NETFLOW_COLLECTOR`

IP address of the NetFlow collector server that will receive the flow data.

**Format**: IP address

**Example**: `NETFLOW_COLLECTOR="192.168.0.223"` or `NETFLOW_COLLECTOR="10.0.0.100"`

### `NETFLOW_PORT`

UDP port for the NetFlow collector. Standard NetFlow port is 2055, but custom ports can be used.

**Format**: Port number (1-65535)

**Example**: `NETFLOW_PORT="42112"` or `NETFLOW_PORT="2055"`

## sFlow Sensor

### `SFLOW_SENSOR_ENABLED`

Enable or disable sFlow sensor for traffic monitoring. sFlow generally provides better performance than NetFlow.

**Format**: `YES` or `NO`

### `SFLOW_SAMPLING_RATE`

sFlow sampling rate. Higher values mean less sampling (more packets analyzed). Lower values reduce CPU usage but provide less detailed data.

**Format**: Integer (typically 100-10000)

**Example**: `SFLOW_SAMPLING_RATE="100"` (sample 1 in 100 packets) or `SFLOW_SAMPLING_RATE="1000"` (sample 1 in 1000 packets)

### `SFLOW_COLLECTOR`

IP address(es) of the sFlow collector server(s) that will receive the flow data. Multiple collectors can be specified as a space-separated list.

**Format**: IP address, or space-separated list of IP addresses

**Example**: `SFLOW_COLLECTOR="192.168.0.223"` or `SFLOW_COLLECTOR="192.168.0.25 192.168.0.27"`

### `SFLOW_PORT`

UDP port for the sFlow collector. Standard sFlow port is 6343.

**Format**: Port number (1-65535)

**Example**: `SFLOW_PORT="6343"`

## SNMP Configuration

### `SNMP_ENABLED`

Enable or disable SNMP server for network monitoring and management.

**Format**: `YES` or `NO`

### `SNMP_COMMUNITY`

SNMP community string for authentication. This acts as a password for SNMP access. **Important**: Change this from the default value for security.

**Format**: String (community name)

**Example**: `SNMP_COMMUNITY="public"` or `SNMP_COMMUNITY="my_secure_community"`

## rscriptd Configuration

### `RSCRIPTD_ENABLED`

Enable automatic rscriptd start at initialization. rscriptd is used for integration with Ubilling and Stargazer.

**Format**: `YES` or `NO`

## Kernel Modules

### `MODULES_LOAD`

Kernel modules to load automatically during initialization. These modules are required for various PureNAS features like connection tracking, NAT, and protocol helpers. Multiple modules can be specified.

**Format**: Space-separated list of kernel module names

**Example**: `MODULES_LOAD="nf_conntrack nf_conntrack_ftp nf_nat_ftp nf_conntrack_sip nf_conntrack_pptp nf_conntrack_netlink"`

Common modules:
- `nf_conntrack` - Connection tracking
- `nf_conntrack_ftp` - FTP connection tracking
- `nf_nat_ftp` - FTP NAT support
- `nf_conntrack_sip` - SIP protocol tracking
- `nf_conntrack_pptp` - PPTP protocol tracking
- `nf_conntrack_netlink` - Netlink connection tracking

## Interface Tuning

### `INTERFACES_TUNEUPS`

Enable or disable automatic interface tuning. When enabled, PureNAS will automatically optimize interface settings based on other configuration parameters.

**Format**: `YES` or `NO`

### `INTEFACES_RING_PARAMS`

Ring buffer parameters for interfaces. These control the size of receive and transmit ring buffers, affecting packet buffering capacity.

**Format**: `rx <size> tx <size>` where size is the buffer size

**Example**: `INTEFACES_RING_PARAMS="rx 4096 tx 4096"` or `INTEFACES_RING_PARAMS="rx 8192 tx 8192"`

### `INTERFACES_OFFLOADS`

Interface offload settings. These control hardware offloading features that can improve performance. Common settings include GRO (Generic Receive Offload), GSO (Generic Segmentation Offload), and TSO (TCP Segmentation Offload).

**Format**: Space-separated list of `feature on/off` pairs

**Example**: `INTERFACES_OFFLOADS="gro on gso on tso on rxvlan off txvlan off hw-tc-offload off"`

Available features:
- `gro` - Generic Receive Offload
- `gso` - Generic Segmentation Offload
- `tso` - TCP Segmentation Offload
- `rxvlan` - Receive VLAN offload
- `txvlan` - Transmit VLAN offload
- `hw-tc-offload` - Hardware traffic control offload

### `INTERFACES_TXQUEUE_LENGTH`

Transmit queue length for interfaces. This controls how many packets can be queued for transmission before dropping.

**Format**: Integer

**Example**: `INTERFACES_TXQUEUE_LENGTH="10000"` or `INTERFACES_TXQUEUE_LENGTH="5000"`

## Performance Tuning

### `IRQ_AFFINITY_ENABLED`

Enable automatic IRQ affinity configuration. This distributes interrupt processing across CPU cores to improve performance on multi-core systems.

**Format**: `YES` or `NO`

### `RPS_ENABLED`

Enable Receive Packet Steering (RPS) configuration. RPS distributes incoming packet processing across CPU cores, improving performance on multi-core systems.

**Format**: `YES` or `NO`

## Subscriber Management

### `DEFAULT_USER_BLOCK`

Default policy for user network: block all traffic for inactive subscribers. When set to `YES`, subscribers must be explicitly allowed before they can access the internet.

**Format**: `YES` or `NO`

### `ISOLATION_ENABLED`

Enable traffic isolation between users. When enabled, subscribers cannot communicate with each other directly, only through the gateway.

**Format**: `YES` or `NO`

### `ZERO_SPEED_OVERRIDE`

Override zero speed with this value (in kbit/s). This value is used when a subscriber's speed is not set or is zero. The value is specified in kilobits per second.

**Format**: Integer (speed in kbit/s)

**Example**: `ZERO_SPEED_OVERRIDE="1310720"` (1280 Mbit/s) or `ZERO_SPEED_OVERRIDE="1024000"` (1000 Mbit/s)

### `DISABLE_ARPFIX`

Disable use of static ARP  for subscriber IP-MAC binding.

**Format**: `YES` or `NO` (default: `NO`)

**Example**: `DISABLE_ARPFIX="YES"`

### `FW_MACFIX`

When set to `YES`, enables IP+MAC enforcement via nftables on the user gateway interface.

**Format**: `YES` or `NO` (default: `NO`)

**Example**: `FW_MACFIX="YES"`

## Unauthorized Client Detection

Detection runs only on the LAN (user gateway) interface. When enabled, clients that are not in the allowed list and that send traffic matching the chosen trigger are recorded for a limited time. Authorizing a client with `subscriber_allow` also removes that client from the detection set.

### `UNAUTH_DETECTION_ENABLED`

Enable or disable unauthorized client detection on the LAN interface.

**Format**: `YES` or `NO`

### `UNAUTH_DETECTION_TRIGGER_MODE`

Which traffic triggers detection: **DNS** (port 53), **PORTS** (use `UNAUTH_DETECTION_PORTS`), **ANY** (any traffic), or **DHCP** (UDP 67/68).

**Format**: `DNS`, `PORTS`, `ANY`, or `DHCP`

### `UNAUTH_DETECTION_PORTS`

Destination ports that trigger detection when mode is **PORTS**. Ignored for other modes.

**Format**: Space-separated port numbers (e.g. `53 80 443`)

### `UNAUTH_DETECTION_TIMEOUT`

How long (in seconds) a detected client stays in the detection set before expiring.

**Format**: Integer (seconds). Example: `1800` (30 minutes).

### `UNAUTH_DETECTION_MAX_ENTRIES`

Maximum number of IPs that can be stored in the detection set at once.

**Format**: Integer (e.g. `10000`).

## nftables Configuration

These parameters are used for nftables configuration. **You should not modify these** unless you understand the implications and need to customize the firewall table structure.

### `NFT_FAMILY`

nftables address family. Defines which address families the firewall rules apply to.

**Format**: Address family name (typically `inet` for IPv4 and IPv6)

### `NFT_TABLE`

nftables table name. The name of the firewall table where rules are stored.

**Format**: Table name

### `NFT_ACTIVE_SET`

nftables set name for active/allowed clients. This set contains IP addresses of subscribers who are allowed to access the internet.

**Format**: Set name

### `NFT_INACTIVE_SET`

nftables set name for inactive/disabled clients. This set contains IP addresses of subscribers who are blocked from accessing the internet.

**Format**: Set name

## Initialization Hooks

### `RUN_BEFORE`

Commands or script paths to execute before PureNAS initialization. Use this to run custom setup scripts or commands that need to execute before the main initialization process. Multiple commands or scripts can be specified.

**Format**: Space-separated list of commands or script paths. Commands with spaces should be enclosed in single quotes.

**Examples**:
- `RUN_BEFORE="/path/to/pre-init.sh"`
- `RUN_BEFORE="/path/to/pre-init.sh /scripts/custom-setup.sh"`
- `RUN_BEFORE="'cpupower frequency-set -g performance' 'cat /proc/cpuinfo | grep MHz'"`

### `RUN_AFTER`

Commands or script paths to execute after PureNAS initialization. Use this to run custom scripts or commands that need to execute after the main initialization process completes. Multiple commands or scripts can be specified.

**Format**: Space-separated list of commands or script paths. Commands with spaces should be enclosed in single quotes.

**Example**: `RUN_AFTER="/path/to/post-init.sh /scripts/custom-cleanup.sh"`

### REST_API_ENABLED

Enables HTTP REST API

**Format**: `YES` or `NO`

### REST_API_KEY

Sets REST API auth key. Disabled if empty.

**Format**: `somesecretkey`

### REST_API_ALLOWED_IPS

Sets list of hosts IPs which is allowed to interract wit API. Disabled if empty.

**Format**: Space-separated list of IP addresses