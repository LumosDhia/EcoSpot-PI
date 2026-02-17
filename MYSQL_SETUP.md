# MySQL / MariaDB setup for EcoSpot

The app uses **one database only** (ecospot). All data (users, articles, tickets, events, etc.) lives in the database; nothing is hardcoded. The first admin is created from environment variables when the database has no users.

---

## Debian: install MySQL and create database

Run as root (or with sudo):

```bash
# Install MySQL server
sudo apt-get update
sudo apt-get install -y mysql-server

# Start and enable
sudo systemctl start mysql
sudo systemctl enable mysql

# Create database and user (no password prompt if root uses auth_socket)
sudo mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS ecospot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ecospot'@'localhost' IDENTIFIED BY 'jkjk';
GRANT ALL PRIVILEGES ON ecospot.* TO 'ecospot'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Then from the project root:

```bash
php bin/console doctrine:schema:create
./bin/test-mysql.sh   # optional: verify connection
```

Or use the script (from repo root):

```bash
sudo ./scripts/install-mysql-debian.sh
php bin/console doctrine:schema:create
```

---

In `.env` set:

```env
DATABASE_URL="mysql://ecospot:jkjk@127.0.0.1:3306/ecospot?serverVersion=mariadb-10.6&charset=utf8mb4"

# First admin (used only when no users exist in DB). Then create others via Registration or Admin → Users.
APP_BOOTSTRAP_ADMIN_EMAIL=admin@example.com
APP_BOOTSTRAP_ADMIN_PASSWORD=your-secure-password
# optional: APP_BOOTSTRAP_ADMIN_FIRSTNAME=Admin  APP_BOOTSTRAP_ADMIN_LASTNAME=User
```

## 1. Create database and user (MySQL shell or phpMyAdmin)

```sql
CREATE DATABASE IF NOT EXISTS ecospot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ecospot'@'localhost' IDENTIFIED BY 'jkjk';
GRANT ALL ON ecospot.* TO 'ecospot'@'localhost';
FLUSH PRIVILEGES;
```

Or use your own database name, user and password—then set them in `.env` or `.env.local`:

```env
DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/DATABASE_NAME?serverVersion=8.0&charset=utf8mb4"
```

- **MySQL 5.7**: use `serverVersion=5.7`
- **MariaDB 10.11**: use `serverVersion=10.11`

## 2. Create schema

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:create
```

Or load the full cohesive schema (see `migrations/schema_full.sql`):

```bash
mysql -u ecospot -p ecospot < migrations/schema_full.sql
```

Then create the first admin from env (only when no users exist):

```bash
php bin/console app:create-users
```

Or use migrations (after generating them):

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

## 3. Run the app

```bash
php -S 127.0.0.1:8000 -t public
```

Tests still use SQLite (see `.env.test`) so you don’t need MySQL for `composer test`.

---

## Quick test (MySQL running on 3306)

From the project root, run:

```bash
chmod +x bin/test-mysql.sh
./bin/test-mysql.sh
```

Or manually:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:create
php bin/console doctrine:query:sql "SELECT 1"
```

If you use **root** with no password (e.g. XAMPP), set in `.env.local`:

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/ecospot?serverVersion=8.0&charset=utf8mb4"
```

Then create the database in MySQL: `CREATE DATABASE ecospot;`
