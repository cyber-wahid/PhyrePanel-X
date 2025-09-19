# RHEL Support Documentation

PhyrePanel now supports RHEL-based Linux distributions in addition to the existing Debian-based support. This document provides information about supported distributions, installation requirements, and differences between package managers.

## Supported RHEL-based Distributions

### CentOS
- **CentOS 7** - Full support
- **CentOS 8** - Full support (as CentOS Stream 8)
- **CentOS Stream 9** - Full support

### Red Hat Enterprise Linux (RHEL)
- **RHEL 7** - Full support
- **RHEL 8** - Full support
- **RHEL 9** - Full support

### Rocky Linux
- **Rocky Linux 8** - Full support
- **Rocky Linux 9** - Full support

### AlmaLinux
- **AlmaLinux 8** - Full support
- **AlmaLinux 9** - Full support

### Fedora
- **Fedora 38** - Full support
- **Fedora 39** - Full support
- **Fedora 40** - Full support

## Package Manager Support

PhyrePanel automatically detects the package manager available on your system:

- **dnf** - Preferred on newer RHEL-based systems (RHEL 8+, CentOS 8+, Rocky Linux 8+, AlmaLinux 8+)
- **yum** - Fallback for older systems (RHEL 7, CentOS 7)

## Installation

The installation process is identical for all supported distributions:

```bash
wget https://raw.githubusercontent.com/PhyreApps/PhyrePanel/main/installers/install.sh && chmod +x install.sh && ./install.sh
```

The installer will automatically:
1. Detect your operating system
2. Select the appropriate package manager
3. Install the correct packages for your distribution
4. Configure services appropriately

## Package Mapping

PhyrePanel automatically maps package names between different distributions:

| Debian/Ubuntu | RHEL/CentOS/Rocky/Alma | Description |
|---------------|------------------------|-------------|
| apache2 | httpd | Web server |
| libapache2-mod-php | php | PHP Apache module |
| libapache2-mod-ruid2 | mod_ruid2 | Apache RUID2 module |
| libapache2-mod-passenger | mod_passenger | Apache Passenger module |
| mysql-server | mysql-server | MySQL server |
| mysql-client | mysql | MySQL client |
| dovecot-core | dovecot | Dovecot IMAP/POP3 server |
| dovecot-imapd | dovecot-imap | Dovecot IMAP |
| dovecot-pop3d | dovecot-pop3 | Dovecot POP3 |
| dovecot-lmtpd | dovecot-lmtp | Dovecot LMTP |
| exim4 | exim | Exim mail server |
| libonig-dev | oniguruma-devel | Oniguruma development files |
| libzip-dev | libzip-devel | libzip development files |
| libcurl4-openssl-dev | libcurl-devel | libcurl development files |
| libsodium23 | libsodium | libsodium library |
| libpq5 | postgresql-libs | PostgreSQL client library |
| libssl-dev | openssl-devel | OpenSSL development files |
| zlib1g-dev | zlib-devel | zlib development files |

## Service Management

PhyrePanel uses systemd for service management on all supported distributions:

- **Apache**: `systemctl start/stop/restart/enable httpd` (RHEL) or `apache2` (Debian)
- **MySQL**: `systemctl start/stop/restart/enable mysqld`
- **Dovecot**: `systemctl start/stop/restart/enable dovecot`

## Apache Configuration Differences

### Debian/Ubuntu
- Configuration files in `/etc/apache2/`
- Modules enabled with `a2enmod`
- Sites enabled with `a2ensite`

### RHEL-based
- Configuration files in `/etc/httpd/`
- Modules loaded in `/etc/httpd/conf.modules.d/`
- Sites configured in `/etc/httpd/conf.d/`

## PHP Installation

PHP installation differs between distributions:

### Debian/Ubuntu
- Uses PPA repositories for latest PHP versions
- Multiple PHP versions can be installed simultaneously
- Modules enabled with `a2enmod`

### RHEL-based
- Uses default repositories or EPEL
- Single PHP version per installation
- Modules loaded via Apache configuration files

## Repository Management

### Debian/Ubuntu
- Uses `add-apt-repository` for additional repositories
- Package lists updated with `apt-get update`

### RHEL-based
- Uses `dnf config-manager` or manual repository files
- Package lists updated with `dnf update` or `yum update`

## Troubleshooting

### Common Issues

1. **Package not found**: Ensure EPEL repository is installed
   ```bash
   dnf install epel-release
   ```

2. **Service not starting**: Check systemd status
   ```bash
   systemctl status httpd
   journalctl -u httpd
   ```

3. **Apache modules not loading**: Check module configuration files in `/etc/httpd/conf.modules.d/`

4. **PHP not working**: Verify PHP module is loaded in Apache configuration

### Log Files

- **Installation logs**: `/var/log/phyre/`
- **Apache logs**: `/var/log/httpd/` (RHEL) or `/var/log/apache2/` (Debian)
- **MySQL logs**: `/var/log/mysqld.log`
- **System logs**: `journalctl -u service-name`

## Migration from Debian-based Systems

If you're migrating from a Debian-based system to RHEL-based:

1. Backup your PhyrePanel configuration
2. Install PhyrePanel on the new RHEL system
3. Restore your configuration
4. Update any custom Apache configurations to use RHEL paths

## Development

### Adding New RHEL Distributions

To add support for a new RHEL-based distribution:

1. Create installer directory: `installers/distro-version/`
2. Add distribution detection in `installers/install.sh`
3. Create installation scripts with appropriate package names
4. Update package mappings in `web/app/Helpers/PackageManager.php`

### Testing

Test on different distributions using Docker:

```bash
# CentOS Stream 9
docker run -it centos:stream9 bash

# Rocky Linux 9
docker run -it rockylinux:9 bash

# AlmaLinux 9
docker run -it almalinux:9 bash
```

## Support

For issues specific to RHEL-based distributions:

1. Check this documentation
2. Search existing GitHub issues
3. Create a new issue with:
   - Distribution and version
   - Package manager (dnf/yum)
   - Error logs
   - Steps to reproduce

## Contributing

Contributions to improve RHEL support are welcome:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test on multiple RHEL distributions
5. Submit a pull request

Please ensure your changes work on both Debian-based and RHEL-based systems.
