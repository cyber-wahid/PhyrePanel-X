# PhyrePanel RHEL Support - Troubleshooting Guide

## Quick Test Commands

### 1. Test OS Detection
```bash
# Run this on your Linux system to test OS detection
cat /etc/os-release | grep -E "^(ID|VERSION_ID|NAME)="
```

### 2. Test Package Manager Detection
```bash
# Check which package manager is available
which apt dnf yum 2>/dev/null
```

### 3. Test Installation Script
```bash
# Download and test the installation script
wget https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/master/installers/install.sh
chmod +x install.sh
./install.sh
```

## Common Issues and Solutions

### Issue 1: "This script must be run on a supported Linux distribution"

**Cause**: OS detection is failing or your OS is not in the supported list.

**Solution**:
1. Check your OS information:
   ```bash
   cat /etc/os-release
   ```

2. If you're on a supported OS but getting this error, the detection logic might need adjustment.

### Issue 2: "404 Not Found" when downloading installer

**Cause**: The installer URL is not found for your specific OS version.

**Solution**:
1. Check what installer URL is being generated:
   ```bash
   OS_ID=$(cat /etc/os-release | grep -w "ID" | cut -d "=" -f 2 | tr -d '"')
   OS_VERSION=$(cat /etc/os-release | grep -w "VERSION_ID" | cut -d "=" -f 2 | tr -d '"')
   echo "OS: $OS_ID-$OS_VERSION"
   ```

2. Check if the installer exists:
   ```bash
   curl -I https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/master/installers/$OS_ID-$OS_VERSION/install.sh
   ```

### Issue 3: Package installation fails

**Cause**: Package names are different between distributions.

**Solution**:
1. Check if EPEL repository is installed (for RHEL-based systems):
   ```bash
   # For CentOS/RHEL/Rocky/Alma
   dnf install epel-release
   ```

2. Update package lists:
   ```bash
   # For Debian/Ubuntu
   apt update
   
   # For RHEL-based
   dnf update
   ```

### Issue 4: PHP installer fails

**Cause**: PHP installer is not using the correct package manager.

**Solution**:
1. Check if the PackageManager class is working:
   ```bash
   # Test PHP detection
   php -v
   ```

2. Check Apache service:
   ```bash
   # For Debian/Ubuntu
   systemctl status apache2
   
   # For RHEL-based
   systemctl status httpd
   ```

## Manual Installation Steps

If the automatic installer fails, you can install manually:

### For Debian/Ubuntu:
```bash
# Update system
apt update && apt upgrade -y

# Install base packages
apt install -y openssl jq curl wget unzip zip tar mysql-common mysql-server mysql-client lsb-release gnupg2 ca-certificates apt-transport-https software-properties-common supervisor libonig-dev libzip-dev libcurl4-openssl-dev libsodium23 libpq5 apache2 libapache2-mod-ruid2 libapache2-mod-php libssl-dev zlib1g-dev

# Start services
systemctl start mysql
systemctl start apache2
systemctl enable mysql
systemctl enable apache2
```

### For RHEL-based (CentOS/Rocky/Alma):
```bash
# Update system
dnf update -y

# Install EPEL
dnf install -y epel-release

# Install base packages
dnf install -y openssl jq curl wget unzip zip tar mysql-common mysql-server mysql redhat-lsb-core gnupg2 ca-certificates supervisor oniguruma-devel libzip-devel libcurl-devel libsodium postgresql-libs httpd mod_ruid2 php openssl-devel zlib-devel

# Start services
systemctl start mysqld
systemctl start httpd
systemctl enable mysqld
systemctl enable httpd
```

## Testing the Installation

### 1. Test Package Manager Detection
```bash
# Create a test PHP script
cat > test-pm.php << 'EOF'
<?php
require_once 'web/app/Helpers/PackageManager.php';
require_once 'web/app/Helpers/OSDetector.php';

try {
    $osDetector = new App\Helpers\OSDetector();
    $packageManager = new App\Helpers\PackageManager();
    
    echo "OS: " . $osDetector->getOSId() . "\n";
    echo "Version: " . $osDetector->getOSVersion() . "\n";
    echo "Package Manager: " . $osDetector->getPackageManager() . "\n";
    echo "Is RHEL-based: " . ($osDetector->isRHELBased() ? 'Yes' : 'No') . "\n";
    echo "Is Debian-based: " . ($osDetector->isDebianBased() ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
EOF

php test-pm.php
```

### 2. Test Installation Script
```bash
# Download the installer
wget https://raw.githubusercontent.com/cyber-wahid/PhyrePanel-X/master/installers/install.sh
chmod +x install.sh

# Run with debug output
bash -x install.sh
```

## Debug Information

If you're still having issues, please provide:

1. **OS Information**:
   ```bash
   cat /etc/os-release
   uname -a
   ```

2. **Package Manager**:
   ```bash
   which apt dnf yum 2>/dev/null
   ```

3. **Error Messages**: Copy the exact error messages you're seeing

4. **Installation Logs**: If available, share any installation logs

## Contact

If you continue to have issues, please:
1. Check the GitHub repository: https://github.com/cyber-wahid/PhyrePanel-X
2. Create an issue with the debug information above
3. Include the specific error messages and steps to reproduce
