# EcoSpot-PI 🌿

EcoSpot is a modern web application designed to promote environmental awareness and community engagement. Built with **Symfony 6.4**, it features a comprehensive blog system, NGO tools, and AI-powered features.

---

## 🚀 Getting Started

Follow these instructions to get the project up and running on your local machine for development and testing purposes.

### 📋 Prerequisites

Ensure you have the following installed on your system:

*   **PHP:** 8.1 or higher
    *   Required extensions: `ctype`, `iconv`, `dom`, `pdo_mysql`, `intl`
*   **Composer:** Latest version
*   **Database:** MariaDB (10.5.0+) or MySQL
*   **Symfony CLI:** (Optional but highly recommended) [Install Symfony CLI](https://symfony.com/download)

---

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/LumosDhia/EcoSpot-PI.git
cd EcoSpot-PI
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup the Database

Run the following commands to create the database and apply the schema:

```bash
# Create the database
php bin/console doctrine:database:create

# Run migrations to create tables
php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Create Initial Users

EcoSpot comes with a built-in command to create default Admin and NGO users for testing:

```bash
php bin/console app:create-users
```

**Default Credentials:**
*   **Admin:** `admin@ecospot.local` / `admin123`
*   **NGO:** `ngo@ecospot.local` / `ngo123`

---

## 🏃 Running the Application

### Using Symfony CLI (Recommended)

```bash
symfony server:start -d
```
The application will be available at [http://127.0.0.1:8000](http://127.0.0.1:8000).

### Using PHP Built-in Server

```bash
php -S localhost:8000 -t public
```
The application will be available at [http://localhost:8000](http://localhost:8000).

---

## 🏗️ Technical Stack

*   **Backend:** Symfony 6.4 (PHP 8.1+)
*   **Database:** Doctrine ORM (MySQL / MariaDB)
*   **Frontend:** Twig, Asset Mapper, Stimulus, Turbo
*   **Translation:** Symfony Translation (EN, FR, AR supported)
*   **Rich Text Editor:** TinyMCE
*   **AI Integration:** OpenRouter (AI SEO & Reader)
*   **Security:** Cloudflare Turnstile (Captcha)

---

## 🧪 Testing

To run the unit and functional tests, you first need to set up the test database:

```bash
# Create the test database
php bin/console doctrine:database:create --env=test

# Apply migrations to the test database
php bin/console doctrine:migrations:migrate --no-interaction --env=test

# Run the tests
php bin/phpunit
```

---

## 📄 License

This project is proprietary. All rights reserved.
