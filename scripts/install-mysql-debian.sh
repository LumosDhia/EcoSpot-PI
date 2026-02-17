#!/usr/bin/env bash
# Install MariaDB on Debian and create ecospot database + user.
# Run with: sudo ./scripts/install-mysql-debian.sh

set -e

echo "=== Installing MariaDB server on Debian ==="
apt-get update
apt-get install -y mariadb-server

echo ""
echo "=== Starting MariaDB ==="
systemctl start mariadb
systemctl enable mariadb

echo ""
echo "=== Creating ecospot database and user ==="
# On Debian/Ubuntu, root often uses auth_socket; use sudo mysql
# If root has a password, run: mysql -u root -p
mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS ecospot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ecospot'@'localhost' IDENTIFIED BY 'jkjk';
GRANT ALL PRIVILEGES ON ecospot.* TO 'ecospot'@'localhost';
FLUSH PRIVILEGES;
SQL

echo ""
echo "=== MariaDB is ready ==="
echo "  Database: ecospot"
echo "  User: ecospot"
echo "  Password: jkjk"
echo ""
echo "From the project root, create schema:"
echo "  php bin/console doctrine:schema:create"
echo ""
