<?php

namespace App\Installers\Server\Applications;

use App\Helpers\PackageManager;

class NodeJsInstaller
{
    public $nodejsVersions = [];

    public $logFilePath = '/var/log/phyre/nodejs-installer.log';

    public function setNodeJsVersions($versions)
    {
        $this->nodejsVersions = $versions;
    }

    public function setLogFilePath($path)
    {
        $this->logFilePath = $path;
    }

    public function install()
    {
        $packageManager = new PackageManager();
        $commands = [];
        
        if ($packageManager->isDebianBased()) {
            $commands[] = 'export DEBIAN_FRONTEND=noninteractive';
            $commands[] = $packageManager->getInstallCommand('npm');
            $commands[] = 'curl -sL https://deb.nodesource.com/setup_20.x -o /tmp/nodesource_setup.sh';
            $commands[] = 'bash /tmp/nodesource_setup.sh';
            $commands[] = $packageManager->getInstallCommand('nodejs');

            // Install Apache Passenger
            $commands[] = 'curl https://oss-binaries.phusionpassenger.com/auto-software-signing-gpg-key.txt | gpg --dearmor | sudo tee /etc/apt/trusted.gpg.d/phusion.gpg >/dev/null';
            $commands[] = "sudo sh -c 'echo deb https://oss-binaries.phusionpassenger.com/apt/passenger jammy main > /etc/apt/sources.list.d/passenger.list'";
            $commands[] = $packageManager->getUpdateCommand();
            $commands[] = $packageManager->getInstallCommand('libapache2-mod-passenger');
            $commands[] = 'sudo a2enmod passenger';
            $commands[] = 'sudo service apache2 restart';
        } elseif ($packageManager->isRHELBased()) {
            $commands[] = $packageManager->getInstallCommand(['npm', 'nodejs']);
            
            // Install Apache Passenger for RHEL-based systems
            $commands[] = 'curl https://oss-binaries.phusionpassenger.com/auto-software-signing-gpg-key.txt | gpg --dearmor | sudo tee /etc/pki/rpm-gpg/phusion.gpg >/dev/null';
            $commands[] = "sudo sh -c 'echo [passenger] > /etc/yum.repos.d/passenger.repo'";
            $commands[] = "sudo sh -c 'echo name=passenger >> /etc/yum.repos.d/passenger.repo'";
            $commands[] = "sudo sh -c 'echo baseurl=https://oss-binaries.phusionpassenger.com/yum/passenger/el/\$releasever/\$basearch >> /etc/yum.repos.d/passenger.repo'";
            $commands[] = "sudo sh -c 'echo enabled=1 >> /etc/yum.repos.d/passenger.repo'";
            $commands[] = "sudo sh -c 'echo gpgcheck=1 >> /etc/yum.repos.d/passenger.repo'";
            $commands[] = "sudo sh -c 'echo gpgkey=file:///etc/pki/rpm-gpg/phusion.gpg >> /etc/yum.repos.d/passenger.repo'";
            $commands[] = $packageManager->getUpdateCommand();
            $commands[] = $packageManager->getInstallCommand('mod_passenger');
            $commands[] = 'echo "LoadModule passenger_module modules/mod_passenger.so" >> /etc/httpd/conf.modules.d/00-passenger.conf';
            $commands[] = $packageManager->getRestartServiceCommand('httpd');
        }

        $shellFileContent = '';
        foreach ($commands as $command) {
            $shellFileContent .= $command . PHP_EOL;
        }
        $shellFileContent .= 'echo "All packages installed successfully!"' . PHP_EOL;
        $shellFileContent .= 'echo "DONE!"' . PHP_EOL;
        $shellFileContent .= 'rm -f /tmp/nodejs-installer.sh';

        file_put_contents('/tmp/nodejs-installer.sh', $shellFileContent);

        shell_exec('bash /tmp/nodejs-installer.sh >> ' . $this->logFilePath . ' &');

    }
}
