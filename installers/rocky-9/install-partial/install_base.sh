#!/bin/bash

INSTALL_DIR="/phyre/install"

# Update package manager
dnf update -y

# Install EPEL repository
dnf install -y epel-release

# Install base dependencies
dnf install -y ca-certificates

mkdir -p $INSTALL_DIR

cd $INSTALL_DIR

DEPENDENCIES_LIST=(
    "openssl"
    "jq"
    "curl"
    "wget"
    "unzip"
    "zip"
    "tar"
    "mysql-common"
    "mysql-server"
    "mysql"
    "redhat-lsb-core"
    "gnupg2"
    "ca-certificates"
    "supervisor"
    "oniguruma-devel"
    "libzip-devel"
    "libcurl-devel"
    "libsodium"
    "postgresql-libs"
    "httpd"
    "mod_ruid2"
    "php"
    "openssl-devel"
    "zlib-devel"
)

# Install dependencies
for DEPENDENCY in "${DEPENDENCIES_LIST[@]}"; do
    dnf install -y $DEPENDENCY
done

# Start and enable MySQL
systemctl start mysqld
systemctl enable mysqld

# Start and enable Apache
systemctl start httpd
systemctl enable httpd

mkdir -p /usr/local/phyre/ssl

wget https://raw.githubusercontent.com/PhyreApps/PhyrePanel/refs/heads/main/web/server/ssl/phyre.crt -O /usr/local/phyre/ssl/phyre.crt
wget https://raw.githubusercontent.com/PhyreApps/PhyrePanel/refs/heads/main/web/server/ssl/phyre.key -O /usr/local/phyre/ssl/phyre.key

chmod 644 /usr/local/phyre/ssl/phyre.crt
chmod 600 /usr/local/phyre/ssl/phyre.key

wget https://raw.githubusercontent.com/PhyreApps/PhyrePanel/main/installers/rocky-9/greeting.sh -O /etc/profile.d/phyre-greeting.sh

# Install PHYRE PHP (using RPM packages for RHEL-based systems)
wget https://github.com/PhyreApps/PhyrePanelPHP/raw/main/compilators/rhel/php/dist/phyre-php-8.2.0-rocky-9.rpm
rpm -i phyre-php-8.2.0-rocky-9.rpm

# Install PHYRE NGINX (using RPM packages for RHEL-based systems)
wget https://github.com/PhyreApps/PhyrePanelNGINX/raw/main/compilators/rhel/nginx/dist/phyre-nginx-1.24.0-rocky-9.rpm
rpm -i phyre-nginx-1.24.0-rocky-9.rpm

PHYRE_PHP=/usr/local/phyre/php/bin/php

ln -s $PHYRE_PHP /usr/bin/phyre-php

curl -s https://phyrepanel.com/api/phyre-installation-log -X POST -H "Content-Type: application/json" -d '{"os": "rocky-9"}'
