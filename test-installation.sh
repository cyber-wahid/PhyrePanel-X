#!/bin/bash

echo "=== PhyrePanel Installation Test ==="
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root. Please run with sudo."
   exit 1
fi

echo "✅ Running as root"

# Check OS detection
echo ""
echo "=== OS Detection Test ==="
if [ -f /etc/os-release ]; then
    echo "✅ /etc/os-release found"
    echo "OS Information:"
    cat /etc/os-release | grep -E "^(ID|VERSION_ID|NAME)="
    
    OS_ID=$(cat /etc/os-release | grep -w "ID" | cut -d "=" -f 2 | tr -d '"')
    OS_ID_LIKE=$(cat /etc/os-release | grep -w "ID_LIKE" | cut -d "=" -f 2 | tr -d '"')
    
    echo ""
    echo "Detected OS ID: $OS_ID"
    echo "Detected OS ID_LIKE: $OS_ID_LIKE"
    
    # Test OS support
    SUPPORTED=false
    
    # Check for Debian-based systems
    if [[ "$OS_ID_LIKE" == "debian" ]] || [[ "$OS_ID" == "ubuntu" ]] || [[ "$OS_ID" == "debian" ]]; then
        SUPPORTED=true
        echo "✅ Debian-based system detected"
    fi
    
    # Check for RHEL-based systems
    if [[ "$OS_ID" == "rhel" ]] || [[ "$OS_ID" == "centos" ]] || [[ "$OS_ID" == "rocky" ]] || [[ "$OS_ID" == "alma" ]] || [[ "$OS_ID" == "fedora" ]]; then
        SUPPORTED=true
        echo "✅ RHEL-based system detected"
    fi
    
    # Check for RHEL-like systems
    if [[ "$OS_ID_LIKE" == *"rhel"* ]] || [[ "$OS_ID_LIKE" == *"fedora"* ]]; then
        SUPPORTED=true
        echo "✅ RHEL-like system detected"
    fi
    
    if [[ "$SUPPORTED" == "true" ]]; then
        echo "✅ OS is supported by PhyrePanel"
    else
        echo "❌ OS is not supported by PhyrePanel"
        echo "Supported distributions:"
        echo "  - Ubuntu 20.04, 22.04, 24.04"
        echo "  - Debian 11, 12"
        echo "  - CentOS 7, 8, 9"
        echo "  - RHEL 7, 8, 9"
        echo "  - Rocky Linux 8, 9"
        echo "  - AlmaLinux 8, 9"
        echo "  - Fedora 38, 39, 40"
        exit 1
    fi
else
    echo "❌ /etc/os-release not found"
    exit 1
fi

# Check package manager
echo ""
echo "=== Package Manager Test ==="
if command -v apt &> /dev/null; then
    echo "✅ apt found"
    PACKAGE_MANAGER="apt"
elif command -v dnf &> /dev/null; then
    echo "✅ dnf found"
    PACKAGE_MANAGER="dnf"
elif command -v yum &> /dev/null; then
    echo "✅ yum found"
    PACKAGE_MANAGER="yum"
else
    echo "❌ No supported package manager found"
    exit 1
fi

echo "Package manager: $PACKAGE_MANAGER"

# Test package manager
echo ""
echo "=== Package Manager Test ==="
if [[ "$PACKAGE_MANAGER" == "apt" ]]; then
    echo "Testing apt update..."
    apt update --dry-run
elif [[ "$PACKAGE_MANAGER" == "dnf" ]]; then
    echo "Testing dnf update..."
    dnf update --dry-run
elif [[ "$PACKAGE_MANAGER" == "yum" ]]; then
    echo "Testing yum update..."
    yum update --dry-run
fi

echo ""
echo "=== System Requirements Test ==="
echo "Architecture: $(uname -m)"
echo "Kernel: $(uname -r)"
echo "Shell: $SHELL"

if [[ $(uname -m) != "x86_64" ]]; then
    echo "❌ This script requires a 64-bit system"
    exit 1
fi

echo "✅ System architecture is supported"

echo ""
echo "=== Test Complete ==="
echo "✅ All tests passed! PhyrePanel should work on this system."
echo ""
echo "To install PhyrePanel, run:"
echo "wget https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/master/installers/install.sh && chmod +x install.sh && ./install.sh"
