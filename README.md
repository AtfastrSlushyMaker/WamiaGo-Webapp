# WamiaGo Webapp

---

<div align="center">
  <a href="https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp">
    <img src="https://i.imgur.com/759dC4H.png" alt="WamiaGo Logo" width="500">
  </a>
</div>

---

## Table of Contents

- [About The Project](#about-the-project)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Architecture Overview](#architecture-overview)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Docker Services](#docker-services)
  - [Environment Configuration](#environment-configuration)
- [Usage](#usage)
  - [Accessing the Application](#accessing-the-application)
  - [User Authentication](#user-authentication)
  - [Core Features](#core-features)
  - [API Endpoints](#api-endpoints)
  - [Development](#development)
  - [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## About The Project

WamiaGo Webapp is a comprehensive Symfony 6.4-based web application designed to integrate multiple transportation services including bicycle rentals, ride-sharing, and driver services. The project features advanced security with facial recognition authentication, two-factor authentication (2FA), and a modern React-powered frontend with real-time capabilities.

The application serves as a unified platform for:
- **Electric Bicycle Rentals** - Complete bike sharing system with station management
- **Ride Sharing Services** - Driver and passenger matching with booking system
- **Transportation Management** - Vehicle fleet and trip management
- **Advanced Authentication** - Facial recognition and TOTP-based 2FA security

---

## Features

### 🚴‍♂️ **Bicycle Rental System**
- Complete bike sharing platform with station management
- Real-time bicycle availability tracking
- Rental history and usage analytics
- QR code-based bicycle unlocking

### 🚗 **Ride Sharing & Transportation**
- Driver registration and vehicle management
- Trip booking and management system
- Real-time trip tracking and status updates
- Driver-passenger matching algorithms

### 🔐 **Advanced Security Features**
- **Facial Recognition Authentication** - AI-powered face verification using dlib and OpenCV
- **Two-Factor Authentication (2FA)** - TOTP with Google Authenticator support
- **Backup Codes** - Emergency access codes for account recovery
- **QR Code Generation** - Secure 2FA setup with QR codes

### 🎨 **Modern User Interface**
- React 19.1.0 frontend with Bootstrap 5.3.5 styling
- Symfony UX components (Turbo, Stimulus, Chart.js)
- Real-time updates and interactive dashboards
- Responsive design for mobile and desktop

### 📊 **Analytics & Monitoring**
- Chart.js integration for data visualization
- Comprehensive admin dashboard
- Usage statistics and reporting
- Performance monitoring

---

## Technology Stack

### Backend Framework
- **[Symfony 6.4](https://symfony.com)** - Modern PHP framework (PHP 8.1+ required)
- **[Doctrine ORM](https://www.doctrine-project.org/)** - Database abstraction and ORM

### Key Symfony Bundles
- **Security & Authentication**
  - `scheb/2fa-bundle` - Two-factor authentication
  - `scheb/2fa-totp` - Time-based one-time passwords
  - `scheb/2fa-backup-code` - Emergency backup codes
  - `scheb/2fa-qr-code` - QR code generation for 2FA setup

- **User Experience & Frontend**
  - `symfony/ux-turbo` - Real-time page updates
  - `symfony/stimulus-bundle` - JavaScript framework integration
  - `symfony/ux-chartjs` - Chart.js integration for data visualization

- **Communication Services**
  - `twilio/sdk` - SMS and communication services
  - `symfony/google-mailer` - Google mail integration
  - `symfony/mailgun-mailer` - Mailgun email service

- **Utilities**
  - `endroid/qr-code-bundle` - QR code generation
  - `knplabs/knp-paginator-bundle` - Pagination support
  - `doctrine/doctrine-fixtures-bundle` - Database fixtures

### Frontend Technologies
- **[React 19.1.0](https://react.dev/)** - Modern JavaScript library
- **[Bootstrap 5.3.5](https://getbootstrap.com/)** - CSS framework
- **[Chart.js 4.4.9](https://www.chartjs.org/)** - Data visualization
- **[Stimulus](https://stimulus.hotwired.dev/)** - JavaScript framework

### Facial Recognition Service
- **[Python 3.x](https://python.org/)** - Core language
- **[Flask](https://flask.palletsprojects.com/)** - Lightweight web framework
- **[dlib](http://dlib.net/)** - Machine learning and face recognition
- **[OpenCV](https://opencv.org/)** - Computer vision library
- **[scikit-learn](https://scikit-learn.org/)** - Machine learning algorithms
- **[NumPy](https://numpy.org/)** - Numerical computing

### Database & Infrastructure
- **[MySQL](https://www.mysql.com/)** - Primary database
- **[Docker](https://www.docker.com/)** - Containerization
- **[Apache HTTP Server](https://httpd.apache.org/)** - Web server
- **[phpMyAdmin](https://www.phpmyadmin.net/)** - Database administration

---

## Architecture Overview

### Database Schema
The application uses a comprehensive entity relationship model:

- **User Management**: `User` entity with profile management and 2FA settings
- **Bicycle System**: `Bicycle`, `BicycleStation`, `BicycleRental` entities
- **Transportation**: `Driver`, `Vehicle`, `Trip`, `Booking` entities
- **Location Services**: GPS tracking and station management
- **Security**: 2FA tokens, backup codes, and session management

### Microservices
- **Main Webapp** (Symfony) - Core application logic
- **Facial Recognition Service** (Python/Flask) - AI-powered authentication
- **Database Service** (MySQL) - Data persistence
- **Admin Interface** (phpMyAdmin) - Database management

### Security Architecture
- Multi-layer authentication (password + 2FA + facial recognition)
- CSRF protection and secure session management
- Role-based access control (RBAC)
- API endpoint protection and rate limiting
---

## Getting Started

WamiaGo Webapp can be set up using Docker (recommended) or manual installation. The Docker setup includes all required services including the facial recognition AI service.

### Prerequisites

#### For Docker Setup (Recommended)
- [Docker](https://docs.docker.com/get-docker/) (20.10+ recommended)
- [Docker Compose](https://docs.docker.com/compose/install/) (2.0+ recommended)

#### For Manual Setup
- [PHP 8.1+](https://www.php.net/downloads.php) with extensions: `pdo_mysql`, `gd`, `intl`, `curl`, `zip`
- [Composer](https://getcomposer.org) (2.0+ recommended)
- [Node.js](https://nodejs.org/) (18+ recommended) with npm
- [MySQL 8.0+](https://dev.mysql.com/downloads/) or [MariaDB 10.6+](https://mariadb.org/download/)
- [Python 3.8+](https://python.org/) with pip (for facial recognition service)

### Installation

#### Option 1: Docker Setup (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp.git
   cd WamiaGo-Webapp
   ```

2. **Start all services with Docker Compose:**
   ```bash
   docker-compose up -d
   ```

3. **Install PHP dependencies:**
   ```bash
   docker-compose exec webapp composer install
   ```

4. **Install Node.js dependencies and build assets:**
   ```bash
   docker-compose exec webapp npm install
   docker-compose exec webapp npm run build
   ```

5. **Set up the database:**
   ```bash
   docker-compose exec webapp php bin/console doctrine:database:create
   docker-compose exec webapp php bin/console doctrine:migrations:migrate
   docker-compose exec webapp php bin/console doctrine:fixtures:load
   ```

#### Option 2: Manual Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp.git
   cd WamiaGo-Webapp
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

4. **Configure environment variables:**
   ```bash
   cp .env .env.local
   # Edit .env.local with your database credentials and other settings
   ```

5. **Set up the database:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. **Build frontend assets:**
   ```bash
   npm run build
   ```

7. **Set up facial recognition service:**
   ```bash
   cd facial-recognition
   pip install -r requirements.txt
   python app.py &
   cd ..
   ```

8. **Start the Symfony development server:**
   ```bash
   symfony serve
   # or php -S localhost:8000 -t public/
   ```

### Docker Services

The Docker setup includes the following services:

- **webapp** (port 80) - Main Symfony application
- **mysql** (port 3306) - MySQL database server
- **phpmyadmin** (port 8080) - Database administration interface
- **facial-recognition** (port 5000) - Python Flask AI service for facial authentication

### Environment Configuration

Key environment variables to configure in `.env.local`:

```env
# Database
DATABASE_URL="mysql://username:password@127.0.0.1:3306/wamia_go"

# Mailer (choose one)
MAILER_DSN=gmail://username:password@default
# or
MAILER_DSN=mailgun://key:domain@default

# Twilio (for SMS services)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token

# Facial Recognition Service
FACIAL_RECOGNITION_URL=http://localhost:5000

# App Secret (generate with: php bin/console secrets:generate-keys)
APP_SECRET=your_app_secret
```

---

## Usage

### Accessing the Application

1. **Main Application**: `http://localhost` (Docker) or `http://localhost:8000` (manual)
2. **Database Admin**: `http://localhost:8080` (phpMyAdmin)
3. **Facial Recognition API**: `http://localhost:5000` (Flask service)

### User Authentication

The application supports multiple authentication methods:

1. **Standard Login** - Username/email and password
2. **Two-Factor Authentication (2FA)** - TOTP using Google Authenticator or similar apps
3. **Facial Recognition** - AI-powered face verification for enhanced security

#### Setting up 2FA
1. Log in to your account
2. Navigate to **Security Settings**
3. Scan the QR code with your authenticator app
4. Enter the verification code to enable 2FA
5. Save your backup codes in a secure location

### Core Features

#### Bicycle Rental System
- **Browse Stations**: View available bicycle stations and bike counts
- **Rent Bicycle**: Select and rent available bicycles with QR code scanning
- **Return Bicycle**: Return bikes to any station with automated processing
- **View History**: Track your rental history and usage statistics

#### Transportation Services
- **Book Rides**: Request rides with driver matching
- **Manage Trips**: View upcoming and completed trips
- **Driver Dashboard**: For registered drivers to manage their services
- **Vehicle Management**: Add and manage vehicles in the fleet

#### Admin Features
- **User Management**: Manage user accounts and permissions
- **Station Management**: Add/edit bicycle stations and monitor status
- **Analytics Dashboard**: View usage statistics and system performance
- **Content Management**: Manage application content and settings

### API Endpoints

#### Authentication Endpoints
```
POST /api/auth/login          - User authentication
POST /api/auth/2fa/verify     - Verify 2FA code
POST /api/auth/facial         - Facial recognition authentication
POST /api/auth/logout         - User logout
```

#### Bicycle Rental Endpoints
```
GET    /api/bicycles               - List available bicycles
GET    /api/bicycle-stations       - List bicycle stations
POST   /api/bicycle-rentals        - Create new rental
PUT    /api/bicycle-rentals/{id}   - Update rental (return bike)
GET    /api/bicycle-rentals/user   - User's rental history
```

#### Transportation Endpoints
```
GET    /api/trips                  - List user trips
POST   /api/trips                  - Create new trip booking
GET    /api/drivers                - List available drivers
POST   /api/drivers/register       - Register as driver
GET    /api/vehicles               - List vehicles
```

#### Admin Endpoints
```
GET    /admin/dashboard            - Admin dashboard
GET    /admin/users                - User management
GET    /admin/bicycle-rentals      - Rental management
GET    /admin/stations             - Station management
GET    /admin/analytics            - System analytics
```

### Development

#### Running Tests
```bash
# PHP Unit Tests
php bin/phpunit

# JavaScript Tests
npm test

# Facial Recognition Service Tests
cd facial-recognition
python -m pytest tests/
```

#### Database Operations
```bash
# Create migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Load sample data
php bin/console doctrine:fixtures:load

# Clear cache
php bin/console cache:clear
```

#### Asset Management
```bash
# Watch for changes (development)
npm run watch

# Build for production
npm run build

# Analyze bundle
npm run analyze
```

### Troubleshooting

#### Common Issues

1. **Facial Recognition Not Working**
   - Ensure Python service is running on port 5000
   - Check camera permissions in browser
   - Verify dlib and OpenCV installation

2. **2FA Issues**
   - Time synchronization between server and authenticator app
   - Use backup codes if primary 2FA fails
   - Check that TOTP secret is properly configured

3. **Database Connection Issues**
   - Verify MySQL service is running
   - Check database credentials in `.env.local`
   - Ensure database exists and migrations are applied

4. **Asset Loading Issues**
   - Run `npm run build` to compile assets
   - Clear Symfony cache with `php bin/console cache:clear`
   - Check file permissions on `public/build/` directory

---

## Contributing

We welcome contributions to WamiaGo Webapp! Here's how you can help:

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Follow the installation instructions above
4. Make your changes and test thoroughly
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Coding Standards
- Follow PSR-12 coding standards for PHP
- Use ESLint configuration for JavaScript/React code
- Write unit tests for new features
- Update documentation as needed

### Areas for Contribution
- 🔐 Security enhancements and vulnerability fixes
- 🚴‍♂️ Bicycle rental system improvements
- 🤖 AI/ML facial recognition accuracy improvements
- 🎨 UI/UX enhancements and mobile responsiveness
- 📊 Analytics and reporting features
- 🌍 Internationalization and localization
- 📱 Mobile app development (React Native)
- ⚡ Performance optimizations

---

## License

Distributed under the MIT License. See `LICENSE` for more information.

---

## Contact

For any inquiries, please reach out at [**wamiago.contact@gmail.com**](mailto:wamiago.contact@gmail.com).

**Project Repository**: [https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp)
