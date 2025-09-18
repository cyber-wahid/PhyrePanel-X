<?php

require_once 'web/app/Helpers/PackageManager.php';
require_once 'web/app/Helpers/OSDetector.php';

echo "=== PhyrePanel Package Manager Detection Test ===\n\n";

try {
    $osDetector = new App\Helpers\OSDetector();
    $packageManager = new App\Helpers\PackageManager();
    
    echo "OS Detection Results:\n";
    echo "- OS ID: " . $osDetector->getOSId() . "\n";
    echo "- OS Version: " . $osDetector->getOSVersion() . "\n";
    echo "- OS Name: " . $osDetector->getOSName() . "\n";
    echo "- Package Manager: " . $osDetector->getPackageManager() . "\n";
    echo "- Is RHEL-based: " . ($osDetector->isRHELBased() ? 'Yes' : 'No') . "\n";
    echo "- Is Debian-based: " . ($osDetector->isDebianBased() ? 'Yes' : 'No') . "\n";
    echo "- Is Supported: " . ($osDetector->isSupported() ? 'Yes' : 'No') . "\n";
    echo "- Installer Directory: " . $osDetector->getInstallerDirName() . "\n\n";
    
    echo "Package Manager Commands:\n";
    echo "- Install command: " . $packageManager->getInstallCommand('apache2') . "\n";
    echo "- Update command: " . $packageManager->getUpdateCommand() . "\n";
    echo "- Upgrade command: " . $packageManager->getUpgradeCommand() . "\n";
    echo "- Autoremove command: " . $packageManager->getAutoremoveCommand() . "\n\n";
    
    echo "Package Mapping Examples:\n";
    echo "- apache2 -> " . $packageManager->mapPackageName('apache2') . "\n";
    echo "- mysql-server -> " . $packageManager->mapPackageName('mysql-server') . "\n";
    echo "- php (8.2) -> " . $packageManager->mapPackageName('php', '8.2') . "\n";
    echo "- libapache2-mod-php (8.2) -> " . $packageManager->mapPackageName('libapache2-mod-php', '8.2') . "\n\n";
    
    echo "Service Commands:\n";
    $serviceName = $packageManager->isDebianBased() ? 'apache2' : 'httpd';
    echo "- Start $serviceName: " . $packageManager->getStartServiceCommand($serviceName) . "\n";
    echo "- Restart $serviceName: " . $packageManager->getRestartServiceCommand($serviceName) . "\n";
    echo "- Enable $serviceName: " . $packageManager->getEnableServiceCommand($serviceName) . "\n\n";
    
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "This is expected if running on an unsupported system.\n";
}

echo "\n=== Supported Operating Systems ===\n";
$supportedOS = App\Helpers\OSDetector::getSupportedOS();
foreach ($supportedOS as $os => $versions) {
    echo "- $os: " . implode(', ', $versions) . "\n";
}
