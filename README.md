# PureNAS

Damn fast Linux-based IPoE NAS/BRAS/BNG implementation

## Roadmap

- DHCP helper
- Detection of unknown/unauthorized subscribers
- REST API
- Radius Client
- Walled garden

## Features

- **Firewall Management**
- **Subscriber Management**
- **Per-IP Bandwidth Limiting**
- **VLAN Support**
- **NAT Support**
- **IP and DNS access restrictions**
- **NetFlow sensor**
- **SNMP server**
- **rscriptd integration**


## Initial setup 

### Debian 13.2 Trixie

```
su -
apt install -y ethtool net-tools conntrack tcpdump htop mtr-tiny
apt install -y git expat libexpat1-dev build-essential softflowd snmpd snmp
apt install -y php8.4-cli php8.4-mysqli php8.4-mbstring php8.4-bcmath php8.4-curl
apt install -y build-essential libncurses-dev libssl-dev bc flex bison dwarves rsync libelf-dev
```


### Ubuntu Server 24.04 (Not tested yet)
```
sudo bash
apt install -y ethtool net-tools conntrack tcpdump htop mtr-tiny
apt install -y git expat libexpat1-dev build-essential softflowd snmpd snmp
apt install -y php8.3-cli php8.3-mysqli php8.3-mbstring php8.3-bcmath php8.3-curl
apt install -y build-essential libncurses-dev libssl-dev bc flex bison dwarves rsync libelf-dev
```

### Clone latest PureNAS snapshot

```
git clone https://github.com/nightflyza/PureNAS.git /etc/PureNAS
```

## Project Structure

```
/etc/PureNAS/
├── init                 # Main initialization script
├── purenas.conf         # Main configuration file
├── dist                 # Some configs and presets to configure services
├── actions/             # Command scripts
    ├── subscriber_allow      # allow subscriber access to internet
    ├── subscriber_disallow   # disallow subscriber access to internet
    ├── subscriber_shape      # Apply bandwidth limits to subscriber
    ├── subscriber_unshape    # Remove bandwidth limits from subscriber
    ├── subscriber_arp        # Make subscriber IP-MAC permanent record
    ├── subscriber_unarp      # Remove subscriber IP-MAC permanent record
    ├── subscribers_show      # List all active subscribers
    ├── ip_ban                # Set an IP address to block list
    ├── ip_unban              # Remove an IP address from block list
    ├── dns_allow             # Allow DNS server
    ├── dns_disallow          # Disallow DNS server
    ├── portinc_block         # Block incoming port to users network
    ├── portinc_unblock       # Unblock incoming port to users network
    ├── portout_block         # Block outgoing port from users network
    ├── portout_unblock       # Unblock outgoing port from users network
    ├── uc                    # Subscribers Tree Debugger
        
```

## Quick Start

1. **Configure** your network settings in `/etc/PureNAS/purenas.conf`:
   - Set your LAN/WAN interfaces
   - Configure user network range and gateway
   - Adjust firewall and shaper settings

2. **Initialize** the system:
   ```bash
   sudo /etc/PureNAS/init
   ```

3. **Manage subscribers**:
   ```bash
   # Add subscriber to allowed list
   /etc/PureNAS/actions/subscriber_allow <IP_ADDRESS>

   # Remove subscriber from allowed list
   /etc/PureNAS/actions/subscriber_disallow <IP_ADDRESS>

   # Shape subscriber bandwidth (download/upload in kbit/s)
   /etc/PureNAS/actions/subscriber_shape <IP_ADDRESS> <DOWNLOAD_KBIT> [UPLOAD_KBIT]

   # Remove bandwidth shaping
   /etc/PureNAS/actions/subscriber_unshape <IP_ADDRESS>

   # Create permanent IP-MAC record for subscriber
   /etc/PureNAS/actions/subscriber_arp <IP_ADDRESS> <MAC_ADDRESS>

   # Remove permanent IP-MAC record for subscriber
   /etc/PureNAS/actions/subscriber_unarp <IP_ADDRESS>

   # View all active subscribers
   /etc/PureNAS/actions/subscribers_show [summary|terse|extensive]

   # Check some subscriber access info
   /etc/PureNAS/actions/uc [IP_ADDRESS]
   ```

4. **Manage firewall rules**:
   ```bash
   # Block/unblock IP addresses
   /etc/PureNAS/actions/ip_ban <IP_ADDRESS>
   /etc/PureNAS/actions/ip_unban <IP_ADDRESS>

   # Allow/disallow DNS servers
   /etc/PureNAS/actions/dns_allow <DNS_IP_ADDRESS>
   /etc/PureNAS/actions/dns_disallow <DNS_IP_ADDRESS>

   # Block/unblock incoming ports to users network
   /etc/PureNAS/actions/portinc_block <PORT>
   /etc/PureNAS/actions/portinc_unblock <PORT>

   # Block/unblock outgoing ports from users network
   /etc/PureNAS/actions/portout_block <PORT>
   /etc/PureNAS/actions/portout_unblock <PORT>
   ```


## Scheme of the tc hash structure

```
[Subscriber IP]
        |
        v
[Hash bucket] ---> [HTB class] ---> [Rate / Ceil] ---> [qdisc]
```

## Automatic start at boot

```
cp -R /etc/PureNAS/dist/purenas.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable purenas.service
```
check startup status

```
journalctl -xeu purenas.service
``` 

## Kernel parameters tuning
```
cp -R /etc/PureNAS/dist/99-nat-tuning.conf /etc/sysctl.d/
sysctl -p /etc/sysctl.d/99-nat-tuning.conf
```

## Kernel rebuild (Required on Debian)

```
KVER="$(uname -r | sed -E 's/^([0-9]+\.[0-9]+\.[0-9]+).*/\1/')"
cd /usr/src
wget https://cdn.kernel.org/pub/linux/kernel/v6.x/linux-${KVER}.tar.xz
tar xf linux-${KVER}.tar.xz
cd linux-${KVER}
cp -v /boot/config-$(uname -r) .config
make olddefconfig
scripts/config --disable CONFIG_HZ_250
scripts/config --disable CONFIG_HZ_300
scripts/config --disable CONFIG_HZ_100
scripts/config --enable  CONFIG_HZ_1000
scripts/config --set-val CONFIG_HZ 1000
make olddefconfig
make -j$(nproc)
make modules_install
make install
update-grub
```

```
reboot
```

check:
```
grep CONFIG_HZ /boot/config-$(uname -r)
```

## rscriptd setup

```
wget http://ubilling.net.ua/stg/stg-2.409.tar.gz
tar zxvf stg-2.409.tar.gz
cd stg-2.409/projects/rscriptd/
./build 
/usr/bin/gmake install
```

```
cp -R /etc/PureNAS/dist/rscriptd/* /etc/rscriptd/
```

after that set Ubilling database connect parameters in /etc/rscriptd/dbconfig.conf 
and rscriptd secret key in /etc/rscriptd/rscriptd.conf


## PureNAS core update

Just run action 
```
 /etc/PureNAS/actions/core_update
```

it upgrades and removes everything except your purenas.conf main config file.


## Links 

- Based on [original UBRsciptdDebianNAS project](https://github.com/pautiina/UBRsciptdDebianNAS)
