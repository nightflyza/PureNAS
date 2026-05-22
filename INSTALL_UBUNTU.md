# PureNAS on Ubuntu

> [!WARNING]
> **This guide is not tested and needs volunteers!**
>
> The Ubuntu installation instructions below have not been verified on a production system.
> If you have experience running PureNAS on Ubuntu, please help us validate, fix, and improve
> these steps. Contributions, bug reports, and feedback are very welcome.

This document collects all Ubuntu-specific installation steps for PureNAS.
For the main installation guide, configuration and usage, see [README.md](README.md).

## Initial setup

### Ubuntu Server 25.10

```
sudo bash
apt install -y ethtool net-tools conntrack tcpdump htop mtr-tiny curl
apt install -y git expat libexpat1-dev build-essential softflowd snmpd snmp
apt install -y php8.4-cli php8.4-mysqli php8.4-mbstring php8.4-bcmath php8.4-curl
apt install -y autoconf libtool pkg-config libpcap-dev libnfnetlink-dev libbpf-dev libdbus-1-dev libvirt-dev libxml2-dev uuid-dev
apt install -y build-essential libncurses-dev libssl-dev bc flex bison dwarves rsync libelf-dev clang
```

### Clone latest PureNAS snapshot

```
git clone https://github.com/nightflyza/PureNAS.git /etc/PureNAS
```

After this step, follow the [Quick Start](README.md#quick-start), [Automatic start at boot](README.md#automatic-start-at-boot)
and [Kernel parameters tuning](README.md#kernel-parameters-tuning) sections from the main README.

## rscriptd setup

### Ubuntu 25.10
```
export CC=/usr/bin/clang
export CXX=/usr/bin/clang++
export CXXFLAGS=-std=c++11
wget http://ubilling.net.ua/stg/stg-2.409.tar.gz
tar zxvf stg-2.409.tar.gz
cd stg-2.409/projects/rscriptd/
./build 
/usr/bin/gmake install
```

```
cp -R /etc/PureNAS/dist/rscriptd/* /etc/rscriptd/
```

After that set Ubilling database connect parameters in `/etc/rscriptd/dbconfig.conf`
and rscriptd secret key in `/etc/rscriptd/rscriptd.conf`.

## Further steps

The rest of the setup (host-sflow agent, REST API, core update, etc.) is identical to Debian and
is described in the main [README.md](README.md).
