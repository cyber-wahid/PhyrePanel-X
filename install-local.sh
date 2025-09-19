#!/bin/bash

# PhyrePanel Local Installation Script
# This script installs PhyrePanel directly from the project directory

echo "=========================================="
echo "PhyrePanel Local Installation Script"
echo "=========================================="

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root. Exiting..."
   exit 1
fi

# Check if we're in the right directory
if [[ ! -f "installers/install.sh" ]]; then
    echo "Error: Please run this script from the PhyrePanel project root directory"
    echo "Current directory: $(pwd)"
    echo "Expected files: installers/install.sh"
    exit 1
fi

# Detect OS
OS_ID=$(cat /etc/os-release | grep -w "ID" | cut -d "=" -f 2 | tr -d '"')
OS_ID_LIKE=$(cat /etc/os-release | grep -w "ID_LIKE" | cut -d "=" -f 2 | tr -d '"')
DISTRO_VERSION=$(cat /etc/os-release | grep -w "VERSION_ID" | cut -d "=" -f 2 | tr -d '"')
DISTRO_NAME=$(cat /etc/os-release | grep -w "NAME" | cut -d "=" -f 2 | tr -d '"')
DISTRO_NAME=$(echo $DISTRO_NAME | tr '[:upper:]' '[:lower:]' | sed 's/gnu\/linux//g')
DISTRO_NAME=${DISTRO_NAME// /-}
DISTRO_NAME=${DISTRO_NAME%-}  # Remove trailing dash

echo "Detected OS: $OS_ID $DISTRO_VERSION"
echo "Distribution: $DISTRO_NAME"

# Map Debian 13 to Debian 12 for compatibility
if [[ "$OS_ID" == "debian" ]] && [[ "$DISTRO_VERSION" == "13" ]]; then
    echo "Debian 13 detected, mapping to Debian 12 installer for compatibility..."
    DISTRO_VERSION="12"
fi

# Map other distributions
if [[ "$OS_ID" == "centos" ]] && [[ "$DISTRO_NAME" == *"stream"* ]]; then
    DISTRO_NAME="centos-stream"
elif [[ "$OS_ID" == "rocky" ]]; then
    DISTRO_NAME="rocky"
elif [[ "$OS_ID" == "alma" ]]; then
    DISTRO_NAME="alma"
elif [[ "$OS_ID" == "rhel" ]]; then
    DISTRO_NAME="rhel"
elif [[ "$OS_ID" == "fedora" ]]; then
    DISTRO_NAME="fedora"
fi

echo "Using installer: $DISTRO_NAME-$DISTRO_VERSION"

# Check if the installer exists
INSTALLER_PATH="installers/${DISTRO_NAME}-${DISTRO_VERSION}/install.sh"

if [[ -f "$INSTALLER_PATH" ]]; then
    echo "Found installer: $INSTALLER_PATH"
    echo "Starting installation..."
    chmod +x "$INSTALLER_PATH"
    exec "$INSTALLER_PATH" "$@"
else
    echo "Error: Installer not found at $INSTALLER_PATH"
    echo "Available installers:"
    ls -la installers/*/install.sh 2>/dev/null || echo "No installers found"
    echo ""
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
