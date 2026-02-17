# EcoSpot database – fresh setup

Use this when you want a **new database** (database `eco`, user `eco`, password `spot`).

## 1. Create database and user (as MySQL root)

Log in as **root** (you’ll be asked for the **MySQL root** password – the one you set when you installed MariaDB):

```bash
mysql -u root -p
```

At the prompt **Enter password:** type your **root** password, then run:

```sql
CREATE DATABASE IF NOT EXISTS eco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'eco'@'localhost' IDENTIFIED BY 'spot';
GRANT ALL PRIVILEGES ON eco.* TO 'eco'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 2. Load the full schema

Log in as the **eco** user. When prompted **Enter password:** type **spot** (the password for user `eco`):

```bash
mysql -u eco -p eco < migrations/schema_full.sql
```

So:
- **Password for step 1** (`mysql -u root -p`): your **MySQL root** password.
- **Password for step 2** (`mysql -u eco -p`): **spot** (password for user `eco`).

## 3. .env

`.env` should contain:

```env
DATABASE_URL="mysql://eco:spot@127.0.0.1:3306/eco?serverVersion=mariadb-10.11.2&charset=utf8mb4"
```

## 4. Create default app users

```bash
php bin/console app:create-users
```

Logins: `admin@admin.com` / `admin`, `ngo@ngo.com` / `ngo`, `user@user.com` / `user`.

---

**Do not run** `doctrine:migrations:migrate` after using `schema_full.sql`.
