<?php

namespace App\Helpers;

class OSDetector
{
    private $osInfo;
    private $packageManager;
    
    public function __construct()
    {
        $this->detectOS();
        $this->detectPackageManager();
    }
    
    /**
     * Detect the operating system
     */
    private function detectOS()
    {
        if (!file_exists('/etc/os-release')) {
            throw new \Exception('Cannot detect operating system - /etc/os-release not found');
        }
        
        $osRelease = file_get_contents('/etc/os-release');
        $lines = explode("\n", $osRelease);
        
        $this->osInfo = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $this->osInfo[trim($key)] = trim($value, '"');
            }
        }
    }
    
    /**
     * Detect the package manager based on OS
     */
    private function detectPackageManager()
    {
        $id = strtolower($this->osInfo['ID'] ?? '');
        $idLike = strtolower($this->osInfo['ID_LIKE'] ?? '');
        
        // Check for RHEL-based systems
        if (in_array($id, ['rhel', 'centos', 'rocky', 'alma', 'fedora']) || 
            strpos($idLike, 'rhel') !== false || 
            strpos($idLike, 'fedora') !== false) {
            
            // Check if dnf is available (preferred on newer systems)
            if ($this->commandExists('dnf')) {
                $this->packageManager = 'dnf';
            } elseif ($this->commandExists('yum')) {
                $this->packageManager = 'yum';
            } else {
                throw new \Exception('Neither dnf nor yum package manager found on RHEL-based system');
            }
        }
        // Check for Debian-based systems
        elseif (in_array($id, ['ubuntu', 'debian']) || 
                 strpos($idLike, 'debian') !== false) {
            $this->packageManager = 'apt';
        }
        // Check for Arch-based systems
        elseif (in_array($id, ['arch', 'manjaro']) || 
                 strpos($idLike, 'arch') !== false) {
            $this->packageManager = 'pacman';
        }
        else {
            throw new \Exception('Unsupported operating system: ' . $id);
        }
    }
    
    /**
     * Check if a command exists
     */
    private function commandExists($command)
    {
        $return = shell_exec("which $command 2>/dev/null");
        return !empty(trim($return));
    }
    
    /**
     * Get the detected package manager
     */
    public function getPackageManager()
    {
        return $this->packageManager;
    }
    
    /**
     * Get OS information
     */
    public function getOSInfo()
    {
        return $this->osInfo;
    }
    
    /**
     * Get OS ID
     */
    public function getOSId()
    {
        return strtolower($this->osInfo['ID'] ?? '');
    }
    
    /**
     * Get OS version
     */
    public function getOSVersion()
    {
        return $this->osInfo['VERSION_ID'] ?? '';
    }
    
    /**
     * Get OS name
     */
    public function getOSName()
    {
        return $this->osInfo['NAME'] ?? '';
    }
    
    /**
     * Get OS pretty name
     */
    public function getOSPrettyName()
    {
        return $this->osInfo['PRETTY_NAME'] ?? '';
    }
    
    /**
     * Check if the system is RHEL-based
     */
    public function isRHELBased()
    {
        return in_array($this->packageManager, ['yum', 'dnf']);
    }
    
    /**
     * Check if the system is Debian-based
     */
    public function isDebianBased()
    {
        return $this->packageManager === 'apt';
    }
    
    /**
     * Check if the system is Arch-based
     */
    public function isArchBased()
    {
        return $this->packageManager === 'pacman';
    }
    
    /**
     * Get the installer directory name for the current OS
     */
    public function getInstallerDirName()
    {
        $id = $this->getOSId();
        $version = $this->getOSVersion();
        
        // Handle special cases
        if ($id === 'centos') {
            // CentOS 8+ uses stream naming
            if (version_compare($version, '8.0', '>=')) {
                return 'centos-stream-' . explode('.', $version)[0];
            }
            return 'centos-' . $version;
        }
        
        if ($id === 'rhel') {
            return 'rhel-' . $version;
        }
        
        if ($id === 'rocky') {
            return 'rocky-' . $version;
        }
        
        if ($id === 'alma') {
            return 'alma-' . $version;
        }
        
        if ($id === 'fedora') {
            return 'fedora-' . $version;
        }
        
        if ($id === 'ubuntu') {
            return 'ubuntu-' . $version;
        }
        
        if ($id === 'debian') {
            return 'debian-' . $version;
        }
        
        return strtolower($id) . '-' . $version;
    }
    
    /**
     * Check if the OS is supported
     */
    public function isSupported()
    {
        try {
            $this->detectPackageManager();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get supported OS list
     */
    public static function getSupportedOS()
    {
        return [
            'ubuntu' => ['20.04', '22.04', '24.04'],
            'debian' => ['11', '12'],
            'centos' => ['7', '8', '9'],
            'rhel' => ['7', '8', '9'],
            'rocky' => ['8', '9'],
            'alma' => ['8', '9'],
            'fedora' => ['38', '39', '40']
        ];
    }
}
