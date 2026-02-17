#!/usr/bin/env bash
# Run this on your machine (where MySQL is running on 3306) to test MySQL setup.
set -e
cd "$(dirname "$0")/.."

echo "=== Testing MySQL/MariaDB connection for EcoSpot ==="
echo ""

echo "1. Checking DATABASE_URL..."
if grep -q 'mysql://' .env 2>/dev/null; then
  echo "   OK .env uses MySQL/MariaDB"
else
  echo "   WARNING: .env does not use MySQL/MariaDB. Check DATABASE_URL in .env"
  exit 1
fi

echo ""
echo "2. Creating database (if not exists)..."
php bin/console doctrine:database:create --if-not-exists

echo ""
echo "3. Creating schema (tables)..."
php bin/console doctrine:schema:create --no-interaction

echo ""
echo "4. Testing query..."
php bin/console doctrine:query:sql "SELECT 1 AS ok"

echo ""
echo "=== Database test passed. Run: php -S 127.0.0.1:8000 -t public ==="
