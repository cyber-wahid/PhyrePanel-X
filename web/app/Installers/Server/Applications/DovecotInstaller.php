<?php

namespace App\Installers\Server\Applications;

use App\Helpers\PackageManager;

class DovecotInstaller
{
    public $rubyVersions = [];

    public $logFilePath = '/var/log/phyre/dovecot-installer.log';

    public function setLogFilePath($path)
    {
        $this->logFilePath = $path;
    }

    public function install()
    {
        $packageManager = new PackageManager();
        $commands = [];
        $commands[] = 'echo "Installing dovecot..."';

        // Install email packages
        if ($packageManager->isDebianBased()) {
            $commands[] = $packageManager->getInstallCommand(['telnet', 'exim4', 'dovecot-core', 'dovecot-imapd', 'dovecot-pop3d', 'dovecot-lmtpd']);
        } elseif ($packageManager->isRHELBased()) {
            $commands[] = $packageManager->getInstallCommand(['telnet', 'exim', 'dovecot', 'dovecot-imap', 'dovecot-pop3', 'dovecot-lmtp']);
        }



        // /var/lib/roundcube
       // wget https://github.com/roundcube/roundcubemail/releases/download/1.6.0/roundcubemail-1.6.0-complete.tar.gz
       // $commands[] = 'apt-get install -yq roundcube roundcube-core roundcube-mysql roundcube-plugins';

        $shellFileContent = '';
        foreach ($commands as $command) {
            $shellFileContent .= $command . PHP_EOL;
        }

        $shellFileContent .= 'echo "All packages installed successfully!"' . PHP_EOL;
        $shellFileContent .= 'echo "DONE!"' . PHP_EOL;
        $shellFileContent .= 'rm -f /tmp/dovecot-installer.sh';

        file_put_contents('/tmp/dovecot-installer.sh', $shellFileContent);

        shell_exec('bash /tmp/dovecot-installer.sh >> ' . $this->logFilePath . ' &');

    }
}
