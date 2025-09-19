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

# Detect OS information
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
    echo "  - Debian 11, 12, 13"
    echo "  - CentOS 7, 8, 9"
    echo "  - RHEL 7, 8, 9"
    echo "  - Rocky Linux 8, 9"
    echo "  - AlmaLinux 8, 9"
    echo "  - Fedora 38, 39, 40"
    echo "Current OS: $OS_ID ($OS_ID_LIKE)"
    echo "Exiting..."
    exit 1
fi

# Get distribution information
DISTRO_VERSION=$(cat /etc/os-release | grep -w "VERSION_ID" | cut -d "=" -f 2)
DISTRO_VERSION=${DISTRO_VERSION//\"/} # Remove quotes from version string

DISTRO_NAME=$(cat /etc/os-release | grep -w "NAME" | cut -d "=" -f 2)
DISTRO_NAME=${DISTRO_NAME//\"/} # Remove quotes from name string
# Lowercase the distro name
DISTRO_NAME=$(echo $DISTRO_NAME | tr '[:upper:]' '[:lower:]')
# replace spaces
DISTRO_NAME=${DISTRO_NAME// /-}

# Map Debian 13 to Debian 12 for compatibility
if [[ "$OS_ID" == "debian" ]] && [[ "$DISTRO_VERSION" == "13" ]]; then
    echo "Debian 13 detected, mapping to Debian 12 installer for compatibility..."
    DISTRO_VERSION="12"
fi

# Map CentOS Stream to centos-stream
if [[ "$OS_ID" == "centos" ]] && [[ "$DISTRO_NAME" == *"stream"* ]]; then
    DISTRO_NAME="centos-stream"
fi

# Map Rocky Linux to rocky
if [[ "$OS_ID" == "rocky" ]]; then
    DISTRO_NAME="rocky"
fi

# Map AlmaLinux to alma
if [[ "$OS_ID" == "alma" ]]; then
    DISTRO_NAME="alma"
fi

# Map RHEL to rhel
if [[ "$OS_ID" == "rhel" ]]; then
    DISTRO_NAME="rhel"
fi

# Map Fedora to fedora
if [[ "$OS_ID" == "fedora" ]]; then
    DISTRO_NAME="fedora"
fi

echo "Detected OS: $OS_ID $DISTRO_VERSION"
echo "Using installer: $DISTRO_NAME-$DISTRO_VERSION"

# Updated installer URL to use our forked repository
INSTALLER_URL="https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/main/installers/${DISTRO_NAME}-${DISTRO_VERSION}/install.sh"

echo "Downloading installer from: $INSTALLER_URL"

# Download and execute the appropriate installer
wget -O /tmp/phyre-installer.sh "$INSTALLER_URL"
if [[ $? -eq 0 ]]; then
    chmod +x /tmp/phyre-installer.sh
    exec /tmp/phyre-installer.sh "$@"
else
    echo "Failed to download installer for $DISTRO_NAME $DISTRO_VERSION"
    echo "Please check if this distribution is supported."
    echo "Supported distributions:"
    echo "  - Ubuntu 20.04, 22.04, 24.04"
    echo "  - Debian 11, 12, 13"
    echo "  - CentOS 7, 8, 9"
    echo "  - RHEL 7, 8, 9"
    echo "  - Rocky Linux 8, 9"
    echo "  - AlmaLinux 8, 9"
    echo "  - Fedora 38, 39, 40"
    exit 1
fi