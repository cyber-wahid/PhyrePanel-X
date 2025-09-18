<?php

namespace App\Installers\Server\Applications;

use App\SupportedApplicationTypes;
use App\Helpers\PackageManager;

class PHPInstaller
{
    public $phpVersions = [];
    public $phpModules = [];
    public $logFilePath = '/var/log/phyre/php-installer.log';

    public function setPHPVersions($versions)
    {
        $this->phpVersions = $versions;
    }

    public function setPHPModules($modules)
    {
        $this->phpModules = $modules;
    }

    public function setLogFilePath($path)
    {
        $this->logFilePath = $path;
    }

    public function addReposCommands()
    {
        $packageManager = new PackageManager();
        $commands = [];
        
        if ($packageManager->isDebianBased()) {
            $commands[] = 'apt-get install -yq sudo';
            $commands[] = 'add-apt-repository -y ppa:ondrej/php';
            $commands[] = 'add-apt-repository -y ppa:ondrej/apache2';
            $commands[] = 'apt-get update -yq';
        } elseif ($packageManager->isRHELBased()) {
            $commands[] = 'dnf install -y sudo';
            // For RHEL-based systems, we'll use the default repositories
            // Additional repositories can be added here if needed
        }

        return $commands;
    }

    public function commands()
    {
        $packageManager = new PackageManager();
        $commands = [];
        $commands[] = 'echo "Starting PHP Installation..."';
        
        if ($packageManager->isDebianBased()) {
            $commands[] = 'export DEBIAN_FRONTEND=noninteractive';
        }

        $commands = array_merge($commands, $this->addReposCommands());

        $dependenciesListApache = [
            'apache2',
            'apache2-suexec-custom'
        ];

        // Map package names for different systems
        if ($packageManager->isRHELBased()) {
            $dependenciesListApache = [
                'httpd',
                'httpd-tools'
            ];
        }

        $dependenciesApache = implode(' ', $dependenciesListApache);
        $commands[] = $packageManager->getInstallCommand($dependenciesApache);

        if (!empty($this->phpVersions)) {
            foreach ($this->phpVersions as $phpVersion) {
                $phpPackage = $packageManager->mapPackageName('php', $phpVersion);
                $phpCgiPackage = $packageManager->mapPackageName('php-cgi', $phpVersion);
                $apacheModPackage = $packageManager->mapPackageName('libapache2-mod-php', $phpVersion);

                $commands[] = $packageManager->getInstallCommand($phpPackage);
                $commands[] = $packageManager->getInstallCommand($phpCgiPackage);
                
                if (!empty($this->phpModules)) {
                    foreach ($this->phpModules as $module) {
                        $modulePackage = "php{$phpVersion}-{$module}";
                        if ($packageManager->isRHELBased()) {
                            $modulePackage = "php-{$module}";
                        }
                        $commands[] = $packageManager->getInstallCommand($modulePackage);
                    }
                }
                $commands[] = $packageManager->getInstallCommand($apacheModPackage);
            }
        }

        $phpVersions = array_keys(SupportedApplicationTypes::getPHPVersions());
        $lastPHPVersion = end($phpVersions);

        if ($packageManager->isDebianBased()) {
            foreach ($phpVersions as $phpVersion) {
                if ($phpVersion == $lastPHPVersion) {
                    $commands[] = 'a2enmod php' . $phpVersion;
                } else {
                    $commands[] = 'a2dismod php' . $phpVersion;
                }
            }

            $commands[] = 'a2enmod cgi';
            $commands[] = 'a2enmod deflate';
            $commands[] = 'a2enmod expires';
            $commands[] = 'a2enmod mime';
            $commands[] = 'a2enmod rewrite';
            $commands[] = 'a2enmod env';
            $commands[] = 'a2enmod ssl';
            $commands[] = 'a2enmod actions';
            $commands[] = 'a2enmod headers';
            $commands[] = 'a2enmod suexec';
            $commands[] = 'a2enmod proxy';
            $commands[] = 'a2enmod proxy_http';
        } elseif ($packageManager->isRHELBased()) {
            // For RHEL-based systems, Apache modules are enabled differently
            $commands[] = 'echo "LoadModule php_module modules/libphp.so" >> /etc/httpd/conf.modules.d/00-php.conf';
            $commands[] = 'echo "LoadModule rewrite_module modules/mod_rewrite.so" >> /etc/httpd/conf.modules.d/00-rewrite.conf';
            $commands[] = 'echo "LoadModule ssl_module modules/mod_ssl.so" >> /etc/httpd/conf.modules.d/00-ssl.conf';
            $commands[] = 'echo "LoadModule headers_module modules/mod_headers.so" >> /etc/httpd/conf.modules.d/00-headers.conf';
            $commands[] = 'echo "LoadModule proxy_module modules/mod_proxy.so" >> /etc/httpd/conf.modules.d/00-proxy.conf';
            $commands[] = 'echo "LoadModule proxy_http_module modules/mod_proxy_http.so" >> /etc/httpd/conf.modules.d/00-proxy.conf';
        }

        // For Fast CGI
//        $commands[] = 'a2enmod fcgid';
//        $commands[] = 'a2enmod alias';
//        $commands[] = 'a2enmod proxy_fcgi';
//        $commands[] = 'a2enmod setenvif';

        // $commands[] = 'ufw allow in "Apache Full"';

        if ($packageManager->isDebianBased()) {
            $commands[] = 'wget http://security.ubuntu.com/ubuntu/pool/universe/liba/libapache2-mod-ruid2/libapache2-mod-ruid2_0.9.8-3_amd64.deb';
            $commands[] = 'dpkg -i libapache2-mod-ruid2_0.9.8-3_amd64.deb';
        } elseif ($packageManager->isRHELBased()) {
            // For RHEL-based systems, mod_ruid2 is available in EPEL
            $commands[] = $packageManager->getInstallCommand('mod_ruid2');
        }

        $serviceName = $packageManager->isDebianBased() ? 'apache2' : 'httpd';
        $commands[] = $packageManager->getRestartServiceCommand($serviceName);
        $commands[] = 'phyre-php /usr/local/phyre/web/artisan phyre:run-repair';
        $commands[] = $packageManager->getAutoremoveCommand();

        return $commands;
    }

    public function install()
    {
        // Clear log file
        file_put_contents($this->logFilePath, '');

        $shellFileContent = 'phyre-php /usr/local/phyre/web/artisan phyre:install-apache' . PHP_EOL;

        $shellFileContent .= 'echo "All packages installed successfully!"' . PHP_EOL;
        $shellFileContent .= 'echo "DONE!"' . PHP_EOL;
        $shellFileContent .= 'rm -f /tmp/php-installer.sh';

        file_put_contents('/tmp/php-installer.sh', $shellFileContent);
        shell_exec('chmod +x /tmp/php-installer.sh');

        shell_exec('sudo bash /tmp/php-installer.sh >> ' . $this->logFilePath . ' &');

    }
}
