<?php

namespace App\Helpers;

class PackageManager
{
    private $packageManager;
    private $osInfo;
    
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
     * Get the install command for packages
     */
    public function getInstallCommand($packages, $options = [])
    {
        $packages = is_array($packages) ? implode(' ', $packages) : $packages;
        $defaultOptions = $this->getDefaultOptions();
        $options = array_merge($defaultOptions, $options);
        
        switch ($this->packageManager) {
            case 'apt':
                return "apt-get install {$options['flags']} {$packages}";
            case 'yum':
            case 'dnf':
                return "{$this->packageManager} install {$options['flags']} {$packages}";
            case 'pacman':
                return "pacman -S {$options['flags']} {$packages}";
            default:
                throw new \Exception('Unsupported package manager: ' . $this->packageManager);
        }
    }
    
    /**
     * Get the update command
     */
    public function getUpdateCommand()
    {
        switch ($this->packageManager) {
            case 'apt':
                return 'apt-get update';
            case 'yum':
            case 'dnf':
                return "{$this->packageManager} update";
            case 'pacman':
                return 'pacman -Sy';
            default:
                throw new \Exception('Unsupported package manager: ' . $this->packageManager);
        }
    }
    
    /**
     * Get the upgrade command
     */
    public function getUpgradeCommand()
    {
        switch ($this->packageManager) {
            case 'apt':
                return 'apt-get upgrade -y';
            case 'yum':
            case 'dnf':
                return "{$this->packageManager} upgrade -y";
            case 'pacman':
                return 'pacman -Su --noconfirm';
            default:
                throw new \Exception('Unsupported package manager: ' . $this->packageManager);
        }
    }
    
    /**
     * Get the autoremove command
     */
    public function getAutoremoveCommand()
    {
        switch ($this->packageManager) {
            case 'apt':
                return 'apt-get autoremove -y';
            case 'yum':
            case 'dnf':
                return "{$this->packageManager} autoremove -y";
            case 'pacman':
                return 'pacman -Rns $(pacman -Qtdq) 2>/dev/null || true';
            default:
                throw new \Exception('Unsupported package manager: ' . $this->packageManager);
        }
    }
    
    /**
     * Get default options for package manager
     */
    private function getDefaultOptions()
    {
        switch ($this->packageManager) {
            case 'apt':
                return ['flags' => '-yq'];
            case 'yum':
            case 'dnf':
                return ['flags' => '-y'];
            case 'pacman':
                return ['flags' => '--noconfirm'];
            default:
                return ['flags' => ''];
        }
    }
    
    /**
     * Get package name mapping for different package managers
     */
    public function getPackageName($package, $version = null)
    {
        $mapping = $this->getPackageMapping();
        
        if (isset($mapping[$package])) {
            $packageName = $mapping[$package];
            if ($version && isset($packageName['version'])) {
                return str_replace('{version}', $version, $packageName['version']);
            }
            return $packageName['name'];
        }
        
        return $package;
    }
    
    /**
     * Get package mapping between different package managers
     */
    private function getPackageMapping()
    {
        return [
            // Base packages
            'openssl' => [
                'apt' => ['name' => 'openssl'],
                'yum' => ['name' => 'openssl'],
                'dnf' => ['name' => 'openssl'],
                'pacman' => ['name' => 'openssl']
            ],
            'curl' => [
                'apt' => ['name' => 'curl'],
                'yum' => ['name' => 'curl'],
                'dnf' => ['name' => 'curl'],
                'pacman' => ['name' => 'curl']
            ],
            'wget' => [
                'apt' => ['name' => 'wget'],
                'yum' => ['name' => 'wget'],
                'dnf' => ['name' => 'wget'],
                'pacman' => ['name' => 'wget']
            ],
            'unzip' => [
                'apt' => ['name' => 'unzip'],
                'yum' => ['name' => 'unzip'],
                'dnf' => ['name' => 'unzip'],
                'pacman' => ['name' => 'unzip']
            ],
            'zip' => [
                'apt' => ['name' => 'zip'],
                'yum' => ['name' => 'zip'],
                'dnf' => ['name' => 'zip'],
                'pacman' => ['name' => 'zip']
            ],
            'tar' => [
                'apt' => ['name' => 'tar'],
                'yum' => ['name' => 'tar'],
                'dnf' => ['name' => 'tar'],
                'pacman' => ['name' => 'tar']
            ],
            'jq' => [
                'apt' => ['name' => 'jq'],
                'yum' => ['name' => 'jq'],
                'dnf' => ['name' => 'jq'],
                'pacman' => ['name' => 'jq']
            ],
            'ca-certificates' => [
                'apt' => ['name' => 'ca-certificates'],
                'yum' => ['name' => 'ca-certificates'],
                'dnf' => ['name' => 'ca-certificates'],
                'pacman' => ['name' => 'ca-certificates']
            ],
            'gnupg2' => [
                'apt' => ['name' => 'gnupg2'],
                'yum' => ['name' => 'gnupg2'],
                'dnf' => ['name' => 'gnupg2'],
                'pacman' => ['name' => 'gnupg']
            ],
            'supervisor' => [
                'apt' => ['name' => 'supervisor'],
                'yum' => ['name' => 'supervisor'],
                'dnf' => ['name' => 'supervisor'],
                'pacman' => ['name' => 'supervisor']
            ],
            
            // MySQL packages
            'mysql-common' => [
                'apt' => ['name' => 'mysql-common'],
                'yum' => ['name' => 'mysql-common'],
                'dnf' => ['name' => 'mysql-common'],
                'pacman' => ['name' => 'mysql']
            ],
            'mysql-server' => [
                'apt' => ['name' => 'mysql-server'],
                'yum' => ['name' => 'mysql-server'],
                'dnf' => ['name' => 'mysql-server'],
                'pacman' => ['name' => 'mysql']
            ],
            'mysql-client' => [
                'apt' => ['name' => 'mysql-client'],
                'yum' => ['name' => 'mysql'],
                'dnf' => ['name' => 'mysql'],
                'pacman' => ['name' => 'mysql']
            ],
            
            // Apache packages
            'apache2' => [
                'apt' => ['name' => 'apache2'],
                'yum' => ['name' => 'httpd'],
                'dnf' => ['name' => 'httpd'],
                'pacman' => ['name' => 'apache']
            ],
            'libapache2-mod-ruid2' => [
                'apt' => ['name' => 'libapache2-mod-ruid2'],
                'yum' => ['name' => 'mod_ruid2'],
                'dnf' => ['name' => 'mod_ruid2'],
                'pacman' => ['name' => 'apache-mod_ruid2']
            ],
            'libapache2-mod-php' => [
                'apt' => ['name' => 'libapache2-mod-php'],
                'yum' => ['name' => 'php'],
                'dnf' => ['name' => 'php'],
                'pacman' => ['name' => 'php-apache']
            ],
            
            // Development libraries
            'libonig-dev' => [
                'apt' => ['name' => 'libonig-dev'],
                'yum' => ['name' => 'oniguruma-devel'],
                'dnf' => ['name' => 'oniguruma-devel'],
                'pacman' => ['name' => 'oniguruma']
            ],
            'libzip-dev' => [
                'apt' => ['name' => 'libzip-dev'],
                'yum' => ['name' => 'libzip-devel'],
                'dnf' => ['name' => 'libzip-devel'],
                'pacman' => ['name' => 'libzip']
            ],
            'libcurl4-openssl-dev' => [
                'apt' => ['name' => 'libcurl4-openssl-dev'],
                'yum' => ['name' => 'libcurl-devel'],
                'dnf' => ['name' => 'libcurl-devel'],
                'pacman' => ['name' => 'curl']
            ],
            'libsodium23' => [
                'apt' => ['name' => 'libsodium23'],
                'yum' => ['name' => 'libsodium'],
                'dnf' => ['name' => 'libsodium'],
                'pacman' => ['name' => 'libsodium']
            ],
            'libpq5' => [
                'apt' => ['name' => 'libpq5'],
                'yum' => ['name' => 'postgresql-libs'],
                'dnf' => ['name' => 'postgresql-libs'],
                'pacman' => ['name' => 'postgresql-libs']
            ],
            'libssl-dev' => [
                'apt' => ['name' => 'libssl-dev'],
                'yum' => ['name' => 'openssl-devel'],
                'dnf' => ['name' => 'openssl-devel'],
                'pacman' => ['name' => 'openssl']
            ],
            'zlib1g-dev' => [
                'apt' => ['name' => 'zlib1g-dev'],
                'yum' => ['name' => 'zlib-devel'],
                'dnf' => ['name' => 'zlib-devel'],
                'pacman' => ['name' => 'zlib']
            ],
            
            // PHP packages
            'php' => [
                'apt' => ['name' => 'php{version}', 'version' => 'php{version}'],
                'yum' => ['name' => 'php{version}', 'version' => 'php{version}'],
                'dnf' => ['name' => 'php{version}', 'version' => 'php{version}'],
                'pacman' => ['name' => 'php', 'version' => 'php']
            ],
            'php-cgi' => [
                'apt' => ['name' => 'php{version}-cgi', 'version' => 'php{version}-cgi'],
                'yum' => ['name' => 'php{version}-cgi', 'version' => 'php{version}-cgi'],
                'dnf' => ['name' => 'php{version}-cgi', 'version' => 'php{version}-cgi'],
                'pacman' => ['name' => 'php-cgi', 'version' => 'php-cgi']
            ],
            'libapache2-mod-php' => [
                'apt' => ['name' => 'libapache2-mod-php{version}', 'version' => 'libapache2-mod-php{version}'],
                'yum' => ['name' => 'php{version}', 'version' => 'php{version}'],
                'dnf' => ['name' => 'php{version}', 'version' => 'php{version}'],
                'pacman' => ['name' => 'php-apache', 'version' => 'php-apache']
            ],
            
            // Python packages
            'python' => [
                'apt' => ['name' => 'python{version}', 'version' => 'python{version}'],
                'yum' => ['name' => 'python{version}', 'version' => 'python{version}'],
                'dnf' => ['name' => 'python{version}', 'version' => 'python{version}'],
                'pacman' => ['name' => 'python', 'version' => 'python']
            ],
            'python-dev' => [
                'apt' => ['name' => 'python{version}-dev', 'version' => 'python{version}-dev'],
                'yum' => ['name' => 'python{version}-devel', 'version' => 'python{version}-devel'],
                'dnf' => ['name' => 'python{version}-devel', 'version' => 'python{version}-devel'],
                'pacman' => ['name' => 'python', 'version' => 'python']
            ],
            'python-venv' => [
                'apt' => ['name' => 'python{version}-venv', 'version' => 'python{version}-venv'],
                'yum' => ['name' => 'python{version}-venv', 'version' => 'python{version}-venv'],
                'dnf' => ['name' => 'python{version}-venv', 'version' => 'python{version}-venv'],
                'pacman' => ['name' => 'python', 'version' => 'python']
            ],
            'python-setuptools' => [
                'apt' => ['name' => 'python{version}-setuptools', 'version' => 'python{version}-setuptools'],
                'yum' => ['name' => 'python{version}-setuptools', 'version' => 'python{version}-setuptools'],
                'dnf' => ['name' => 'python{version}-setuptools', 'version' => 'python{version}-setuptools'],
                'pacman' => ['name' => 'python-setuptools', 'version' => 'python-setuptools']
            ],
            'python-wheel' => [
                'apt' => ['name' => 'python{version}-wheel', 'version' => 'python{version}-wheel'],
                'yum' => ['name' => 'python{version}-wheel', 'version' => 'python{version}-wheel'],
                'dnf' => ['name' => 'python{version}-wheel', 'version' => 'python{version}-wheel'],
                'pacman' => ['name' => 'python-wheel', 'version' => 'python-wheel']
            ],
            
            // Ruby packages
            'ruby' => [
                'apt' => ['name' => 'ruby{version}', 'version' => 'ruby{version}'],
                'yum' => ['name' => 'ruby{version}', 'version' => 'ruby{version}'],
                'dnf' => ['name' => 'ruby{version}', 'version' => 'ruby{version}'],
                'pacman' => ['name' => 'ruby', 'version' => 'ruby']
            ],
            'ruby-dev' => [
                'apt' => ['name' => 'ruby{version}-dev', 'version' => 'ruby{version}-dev'],
                'yum' => ['name' => 'ruby{version}-devel', 'version' => 'ruby{version}-devel'],
                'dnf' => ['name' => 'ruby{version}-devel', 'version' => 'ruby{version}-devel'],
                'pacman' => ['name' => 'ruby', 'version' => 'ruby']
            ],
            'ruby-bundler' => [
                'apt' => ['name' => 'ruby{version}-bundler', 'version' => 'ruby{version}-bundler'],
                'yum' => ['name' => 'ruby{version}-bundler', 'version' => 'ruby{version}-bundler'],
                'dnf' => ['name' => 'ruby{version}-bundler', 'version' => 'ruby{version}-bundler'],
                'pacman' => ['name' => 'ruby-bundler', 'version' => 'ruby-bundler']
            ],
            
            // Node.js packages
            'npm' => [
                'apt' => ['name' => 'npm'],
                'yum' => ['name' => 'npm'],
                'dnf' => ['name' => 'npm'],
                'pacman' => ['name' => 'npm']
            ],
            'nodejs' => [
                'apt' => ['name' => 'nodejs'],
                'yum' => ['name' => 'nodejs'],
                'dnf' => ['name' => 'nodejs'],
                'pacman' => ['name' => 'nodejs']
            ],
            
            // Apache modules
            'libapache2-mod-passenger' => [
                'apt' => ['name' => 'libapache2-mod-passenger'],
                'yum' => ['name' => 'mod_passenger'],
                'dnf' => ['name' => 'mod_passenger'],
                'pacman' => ['name' => 'apache-mod_passenger']
            ],
            
            // Email packages
            'dovecot-core' => [
                'apt' => ['name' => 'dovecot-core'],
                'yum' => ['name' => 'dovecot'],
                'dnf' => ['name' => 'dovecot'],
                'pacman' => ['name' => 'dovecot']
            ],
            'dovecot-imapd' => [
                'apt' => ['name' => 'dovecot-imapd'],
                'yum' => ['name' => 'dovecot-imap'],
                'dnf' => ['name' => 'dovecot-imap'],
                'pacman' => ['name' => 'dovecot']
            ],
            'dovecot-pop3d' => [
                'apt' => ['name' => 'dovecot-pop3d'],
                'yum' => ['name' => 'dovecot-pop3'],
                'dnf' => ['name' => 'dovecot-pop3'],
                'pacman' => ['name' => 'dovecot']
            ],
            'dovecot-lmtpd' => [
                'apt' => ['name' => 'dovecot-lmtpd'],
                'yum' => ['name' => 'dovecot-lmtp'],
                'dnf' => ['name' => 'dovecot-lmtp'],
                'pacman' => ['name' => 'dovecot']
            ],
            'postfix' => [
                'apt' => ['name' => 'postfix'],
                'yum' => ['name' => 'postfix'],
                'dnf' => ['name' => 'postfix'],
                'pacman' => ['name' => 'postfix']
            ],
            'exim4' => [
                'apt' => ['name' => 'exim4'],
                'yum' => ['name' => 'exim'],
                'dnf' => ['name' => 'exim'],
                'pacman' => ['name' => 'exim']
            ],
            
            // SSL/TLS packages
            'certbot' => [
                'apt' => ['name' => 'certbot'],
                'yum' => ['name' => 'certbot'],
                'dnf' => ['name' => 'certbot'],
                'pacman' => ['name' => 'certbot']
            ],
            
            // Docker packages
            'docker-ce' => [
                'apt' => ['name' => 'docker-ce'],
                'yum' => ['name' => 'docker-ce'],
                'dnf' => ['name' => 'docker-ce'],
                'pacman' => ['name' => 'docker']
            ],
            'docker-ce-cli' => [
                'apt' => ['name' => 'docker-ce-cli'],
                'yum' => ['name' => 'docker-ce-cli'],
                'dnf' => ['name' => 'docker-ce-cli'],
                'pacman' => ['name' => 'docker']
            ],
            'containerd.io' => [
                'apt' => ['name' => 'containerd.io'],
                'yum' => ['name' => 'containerd.io'],
                'dnf' => ['name' => 'containerd.io'],
                'pacman' => ['name' => 'containerd']
            ],
            'docker-buildx-plugin' => [
                'apt' => ['name' => 'docker-buildx-plugin'],
                'yum' => ['name' => 'docker-buildx-plugin'],
                'dnf' => ['name' => 'docker-buildx-plugin'],
                'pacman' => ['name' => 'docker-buildx']
            ],
            'docker-compose-plugin' => [
                'apt' => ['name' => 'docker-compose-plugin'],
                'yum' => ['name' => 'docker-compose-plugin'],
                'dnf' => ['name' => 'docker-compose-plugin'],
                'pacman' => ['name' => 'docker-compose']
            ]
        ];
    }
    
    /**
     * Get the correct package name for the current package manager
     */
    public function mapPackageName($package, $version = null)
    {
        $mapping = $this->getPackageMapping();
        
        if (isset($mapping[$package])) {
            $packageInfo = $mapping[$package];
            if (isset($packageInfo[$this->packageManager])) {
                $packageName = $packageInfo[$this->packageManager];
                if ($version && isset($packageName['version'])) {
                    return str_replace('{version}', $version, $packageName['version']);
                }
                return $packageName['name'];
            }
        }
        
        return $package;
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
     * Get the service management command
     */
    public function getServiceCommand($action, $service)
    {
        switch ($this->packageManager) {
            case 'apt':
                return "systemctl $action $service";
            case 'yum':
            case 'dnf':
                return "systemctl $action $service";
            case 'pacman':
                return "systemctl $action $service";
            default:
                return "systemctl $action $service";
        }
    }
    
    /**
     * Get the enable service command
     */
    public function getEnableServiceCommand($service)
    {
        return $this->getServiceCommand('enable', $service);
    }
    
    /**
     * Get the start service command
     */
    public function getStartServiceCommand($service)
    {
        return $this->getServiceCommand('start', $service);
    }
    
    /**
     * Get the restart service command
     */
    public function getRestartServiceCommand($service)
    {
        return $this->getServiceCommand('restart', $service);
    }
}
