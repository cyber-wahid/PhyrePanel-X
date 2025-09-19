#!/bin/bash

# Check if the user is root
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root. Exiting..."
   exit 1
fi

# Check if the user is running a 64-bit system
if [[ $(uname -m) != "x86_64" ]]; then
    echo "This script must be run on a 64-bit system. Exiting..."
    exit 1
fi

# Check if the user is running a supported shell
if [[ $(echo $SHELL) != "/bin/bash" ]]; then
    echo "This script must be run on a system running Bash. Exiting..."
    exit 1
fi

# Check if the user is running a supported OS
if [[ $(uname -s) != "Linux" ]]; then
    echo "This script must be run on a Linux system. Exiting..."
    exit 1
fi

# Check if the user is running a supported distro
OS_ID=$(cat /etc/os-release | grep -w "ID" | cut -d "=" -f 2 | tr -d '"')
OS_ID_LIKE=$(cat /etc/os-release | grep -w "ID_LIKE" | cut -d "=" -f 2 | tr -d '"')

# Check if it's a supported OS
SUPPORTED=false

# Check for Debian-based systems
if [[ "$OS_ID_LIKE" == "debian" ]] || [[ "$OS_ID" == "ubuntu" ]] || [[ "$OS_ID" == "debian" ]]; then
    SUPPORTED=true
fi

# Check for RHEL-based systems
if [[ "$OS_ID" == "rhel" ]] || [[ "$OS_ID" == "centos" ]] || [[ "$OS_ID" == "rocky" ]] || [[ "$OS_ID" == "alma" ]] || [[ "$OS_ID" == "fedora" ]]; then
    SUPPORTED=true
fi

# Check for RHEL-like systems
if [[ "$OS_ID_LIKE" == *"rhel"* ]] || [[ "$OS_ID_LIKE" == *"fedora"* ]]; then
    SUPPORTED=true
fi

if [[ "$SUPPORTED" != "true" ]]; then
    echo "This script must be run on a supported Linux distribution."
    echo "Supported distributions:"
    echo "  - Ubuntu 20.04, 22.04, 24.04"
    echo "  - Debian 11, 12"
    echo "  - CentOS 7, 8, 9"
    echo "  - RHEL 7, 8, 9"
    echo "  - Rocky Linux 8, 9"
    echo "  - AlmaLinux 8, 9"
    echo "  - Fedora 38, 39, 40"
    echo "Current OS: $OS_ID ($OS_ID_LIKE)"
    echo "Exiting..."
    exit 1
fi

# Check if the user is running a supported distro version
DISTRO_VERSION=$(cat /etc/os-release | grep -w "VERSION_ID" | cut -d "=" -f 2)
DISTRO_VERSION=${DISTRO_VERSION//\"/} # Remove quotes from version string

DISTRO_NAME=$(cat /etc/os-release | grep -w "NAME" | cut -d "=" -f 2)
DISTRO_NAME=${DISTRO_NAME//\"/} # Remove quotes from name string
# Lowercase the distro name
DISTRO_NAME=$(echo $DISTRO_NAME | tr '[:upper:]' '[:lower:]')
# replace spaces
DISTRO_NAME=${DISTRO_NAME// /-}

# Handle special cases for RHEL-based systems
if [[ "$OS_ID" == "centos" ]] && [[ $(echo "$DISTRO_VERSION" | cut -d. -f1) -ge 8 ]]; then
    # CentOS 8+ uses stream naming
    DISTRO_NAME="centos-stream"
    DISTRO_VERSION=$(echo "$DISTRO_VERSION" | cut -d. -f1)
fi

# Handle Rocky Linux
if [[ "$OS_ID" == "rocky" ]]; then
    DISTRO_NAME="rocky"
fi

# Handle AlmaLinux
if [[ "$OS_ID" == "alma" ]]; then
    DISTRO_NAME="alma"
fi

# Handle RHEL
if [[ "$OS_ID" == "rhel" ]]; then
    DISTRO_NAME="rhel"
fi

# Handle Fedora
if [[ "$OS_ID" == "fedora" ]]; then
    DISTRO_NAME="fedora"
fi

INSTALLER_URL="https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/master/installers/${DISTRO_NAME}-${DISTRO_VERSION}/install.sh"

INSTALLER_CONTENT=$(wget ${INSTALLER_URL} 2>&1)
if [[ "$INSTALLER_CONTENT" =~ 404\ Not\ Found ]]; then
    echo "PhyrePanel not supporting this version of distribution"
    echo "Distro: ${DISTRO_NAME} Version: ${DISTRO_VERSION}"
    echo "Exiting..."
    exit 1
fi

# Check is PHYRE is already installed
if [ -d "/usr/local/phyre" ]; then
    echo "PhyrePanel is already installed. Exiting..."
    exit 0
fi

wget $INSTALLER_URL -O ./phyre-installer.sh
chmod +x ./phyre-installer.sh
bash ./phyre-installer.sh
