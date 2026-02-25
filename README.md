# EcoSpot-PI 🌿

EcoSpot is a modern web application designed to promote environmental awareness and community engagement. Built with **Symfony 6.4**, it features a comprehensive blog system, NGO tools, and AI-powered features.

---

## ✨ Recently Added Features (Last Week: Feb 16 - Feb 23)

### 👨‍💻 Contributed by LumosDhia
#### **Core Infrastructure, AI & System-Wide UX**
*   **AI SEO Optimizer**: Integrated **OpenRouter API** (`AiSeoService`) to automatically generate high-quality SEO titles, descriptions, and keywords for articles.
*   **Creative AI Editor**: Tool to generate multiple catchy title ideas for blog posts using **OpenRouter API**.
*   **AI Reader**: Accessible feature for listening to article content with pause/resume capabilities using the **Web Speech Synthesis API**.
*   **Image Integration**: **Unsplash API** integration in `UnsplashImageService` for professional photo searching.
*   **Engagement Tools**: Core systems for **Article Reactions** (Likes/Dislikes), **Views Counter**, and **Reading Time** calculations.
*   **Technical SEO**: Automatic **SEO-friendly Slugs** using **StofDoctrineExtensions (Gedmo)** and terminal commands (`app:generate-seo-missing`) for metadata management.
*   **System-Wide UX & Localization**:
    - **Trilingual Support**: Full system translation for **English, French, and Arabic**.
    - **Relative Time**: "Time-ago" hybrid display for comments and articles using **KnpTimeBundle**.
    - **Advanced Pagination**: Integrated **KnpPaginatorBundle** for smooth content listing and listing management.

---

### 👨‍💻 Contributed by Ghassen
#### **Smart Ticketing & Biometrics Integration**
*   **Smart Ticketing AI**: AI-powered detection of **Priority & Difficulty** for community tickets using **OpenRouter API**.
*   **Automated Instructions**: Generation of task-specific "consignes" via `AiTicketTaskService` (Powered by **OpenRouter**).
*   **Biometric Support**: Integrated the **Face Recognition service** startup logic and critical integration bug fixes between the Symfony backend and the **Python Face-API Microservice**.
*   **Admin Dashboard UX**: Major styling and layout improvements for the NGO and Admin ticketing sections using **Vanilla CSS**.

---

### 👩‍💻 Contributed by Wiem
#### **Face Recognition & User Security**
*   **Biometric Authentication**: Core implementation of **Face Recognition login** using **face-api.js** (TensorFlow.js) and local pre-trained models.
*   **Face Enrollment**: User-facing interface and logic to enroll biometric data for secure access.
*   **Secure Password Reset**: Full "Forgot Password" workflow with email tokens using **Symfony Mailer (SMTP)**.
*   **UX Intelligence**: Integrated **City Suggestions** and location auto-complete during user registration via the **Open-Meteo Geocoding API**.

---

### 👨‍💻 Contributed by Aziz
#### **Smart Event Management**
*   **Geolocation "Nearby" Events**: Feature to detect and list events closest to a user's address using the **OpenStreetMap Nominatim API**.
*   **Participation System**: Real-time capacity management (seats matching) for green community events.
*   **Automated Emailers**: Real-time **Email Confirmations** via **Symfony Mailer** when joining an event.
*   **Event Management**: Enhanced CRUD interface and refinements for organizing community events.

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

## 📸 Face Recognition Service

EcoSpot uses a Python-based microservice for face enrollment and login. This service must be running for these features to work.

### 📋 Prerequisites

- **Python 3.8+**
- Install dependencies:
  ```bash
  pip install -r face_service/requirements.txt
  ```

### 🏃 Starting the Service

You can start the service using the provided PowerShell script:
```powershell
.\start_face_service.ps1
```

Or manually:
```bash
python face_service/main.py
```
The service runs on [http://127.0.0.1:8001](http://127.0.0.1:8001).

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
