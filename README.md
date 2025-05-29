# WamiaGo Webapp

<div align="center">
  <a href="https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp">
    <img src="https://i.imgur.com/759dC4H.png" alt="WamiaGo Logo" width="500">
  </a>
  
  <p><strong>Advanced Transportation Management Platform with AI-Powered Security</strong></p>
  
  [![Symfony](https://img.shields.io/badge/Symfony-6.4-000000.svg?style=flat&logo=symfony)](https://symfony.com)
  [![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4.svg?style=flat&logo=php)](https://php.net)
  [![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg?style=flat&logo=bootstrap)](https://getbootstrap.com)
  [![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg?style=flat&logo=docker)](https://docker.com)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
</div>

## Table of Contents

- [🎯 About The Project](#-about-the-project)
- [✨ Key Features](#-key-features)
- [🏗️ Technology Stack](#️-technology-stack)
- [🔧 Architecture Overview](#-architecture-overview)
- [🚀 Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Docker Installation (Recommended)](#docker-installation-recommended)
  - [Manual Installation](#manual-installation)
  - [Environment Configuration](#environment-configuration)
- [📖 Usage Guide](#-usage-guide)
  - [Authentication System](#authentication-system)
  - [Bicycle Rental System](#bicycle-rental-system)
  - [Transportation Services](#transportation-services)
  - [Admin Dashboard](#admin-dashboard)
- [🔌 API Documentation](#-api-documentation)
- [🧪 Development](#-development)
- [🐛 Troubleshooting](#-troubleshooting)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)
- [📞 Contact](#-contact)

---

## 🎯 About The Project

**WamiaGo Webapp** is a comprehensive transportation management platform built with **Symfony 6.4** and modern web technologies. It integrates multiple transportation services including bicycle rentals, ride-sharing, and driver management with advanced security features powered by AI facial recognition and two-factor authentication.

### What Makes WamiaGo Special?

🔐 **AI-Powered Security** - Facial recognition authentication using Python/Flask with dlib and OpenCV  
🚴‍♂️ **Smart Bike Sharing** - Complete bicycle rental system with station management and QR codes  
🚗 **Ride Management** - Driver-passenger matching with real-time trip tracking  
📱 **Modern UI/UX** - Symfony UX frontend with Bootstrap 5 and real-time updates  
🐳 **Docker Ready** - Complete containerized setup with all microservices

---

## ✨ Key Features

### 🔒 Advanced Security Features
- **🤖 Facial Recognition Authentication** - AI-powered face verification using Python Flask service
- **🔐 Two-Factor Authentication (2FA)** - TOTP support with Google Authenticator integration  
- **📱 QR Code Generation** - Secure 2FA setup and bicycle unlocking
- **🔑 Backup Codes** - Emergency access codes for account recovery
- **🛡️ Session Management** - Secure session handling with CSRF protection

### 🚴‍♂️ Bicycle Rental System
- **📍 Station Management** - Real-time bicycle availability tracking
- **📱 QR Code Scanning** - Easy bicycle unlocking and return process
- **📊 Usage Analytics** - Rental history and usage statistics
- **🗺️ Interactive Maps** - Station locations with live bike counts
- **⚡ Real-time Updates** - Live status updates using Symfony UX Turbo

### 🚗 Transportation Services  
- **👨‍✈️ Driver Registration** - Complete driver onboarding and verification
- **🚙 Vehicle Management** - Fleet management with vehicle tracking
- **📅 Trip Booking** - Advanced booking system with real-time matching
- **🗺️ Route Optimization** - Smart routing and trip management
- **💳 Payment Integration** - Secure payment processing

### 📊 Analytics & Monitoring
- **📈 Chart.js Integration** - Beautiful data visualizations
- **📋 Admin Dashboard** - Comprehensive management interface
- **📊 Usage Reports** - Detailed analytics and reporting
- **🔍 Real-time Monitoring** - System performance tracking

---

## 🏗️ Technology Stack

### Backend Framework
- **[Symfony 6.4](https://symfony.com)** - Modern PHP framework (PHP 8.1+ required)
- **[Doctrine ORM](https://www.doctrine-project.org/)** - Database abstraction and object mapping
- **[Twig](https://twig.symfony.com/)** - Template engine for PHP

### Key Symfony Bundles

#### 🔐 Security & Authentication
```php
scheb/2fa-bundle          # Two-factor authentication framework
scheb/2fa-totp           # Time-based one-time passwords  
scheb/2fa-backup-code    # Emergency backup codes
scheb/2fa-qr-code        # QR code generation for 2FA setup
```

#### 🎨 User Experience & Frontend
```php
symfony/ux-turbo         # Real-time page updates without full refresh
symfony/stimulus-bundle  # JavaScript framework integration
symfony/ux-chartjs      # Chart.js integration for data visualization
symfony/asset-mapper    # Modern asset management
```

#### 📧 Communication Services
```php
twilio/sdk              # SMS and communication services
symfony/google-mailer   # Google Gmail integration
symfony/mailgun-mailer  # Mailgun email service
symfony/mailer          # Email abstraction layer
```

#### 🛠️ Utilities & Tools
```php
endroid/qr-code-bundle       # QR code generation
knplabs/knp-paginator-bundle # Advanced pagination
doctrine/doctrine-fixtures-bundle # Database seeding
```

### Frontend Technologies
- **[Symfony UX](https://ux.symfony.com/)** - Modern JavaScript integration for Symfony
- **[Stimulus](https://stimulus.hotwired.dev/)** - Modest JavaScript framework for progressive enhancement
- **[Turbo](https://turbo.hotwired.dev/)** - Fast navigation and real-time updates
- **[Bootstrap 5.3.5](https://getbootstrap.com/)** - Responsive CSS framework
- **[Chart.js 4.4.9](https://www.chartjs.org/)** - Beautiful and responsive charts
- **[Vanilla JavaScript ES6+](https://developer.mozilla.org/en-US/docs/Web/JavaScript)** - Modern JavaScript implementation

### AI Facial Recognition Service
- **[Python 3.8+](https://python.org/)** - Core programming language
- **[Flask](https://flask.palletsprojects.com/)** - Lightweight WSGI web application framework
- **[dlib](http://dlib.net/)** - Modern C++ toolkit with machine learning algorithms
- **[OpenCV](https://opencv.org/)** - Open source computer vision library
- **[scikit-learn](https://scikit-learn.org/)** - Machine learning library for Python
- **[NumPy](https://numpy.org/)** - Fundamental package for scientific computing

### Database & Infrastructure
- **[MySQL 8.0+](https://www.mysql.com/)** - Relational database management system
- **[Docker & Docker Compose](https://www.docker.com/)** - Containerization platform
- **[Apache HTTP Server](https://httpd.apache.org/)** - Web server
- **[phpMyAdmin](https://www.phpmyadmin.net/)** - MySQL administration tool

---

## 🔧 Architecture Overview

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Symfony UX + JS │    │  Symfony Backend │    │  Python AI Service │
│   (Port 80/8000) │◄──►│   (Apache/PHP)   │◄──►│   (Port 5000)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                        │                        │
         └────────────────────────┼────────────────────────┘
                                  │
                      ┌─────────────────┐    ┌─────────────────┐
                      │  MySQL Database │    │   phpMyAdmin    │
                      │   (Port 3306)   │    │   (Port 8080)   │
                      └─────────────────┘    └─────────────────┘
```

### Database Schema

#### Core Entities
- **👤 User Management**: User profiles, authentication, and 2FA settings
- **🚴‍♂️ Bicycle System**: Bicycle, BicycleStation, BicycleRental entities  
- **🚗 Transportation**: Driver, Vehicle, Trip, Booking entities
- **📍 Location Services**: GPS coordinates and station management
- **🔐 Security**: 2FA tokens, backup codes, and session data

### Microservices Architecture
- **🌐 Main Webapp** (Symfony) - Core application logic and API
- **🤖 Facial Recognition Service** (Python/Flask) - AI-powered authentication  
- **🗄️ Database Service** (MySQL) - Data persistence layer
- **⚙️ Admin Interface** (phpMyAdmin) - Database management tools

---

## 🚀 Getting Started

WamiaGo Webapp supports both Docker (recommended) and manual installation. The Docker setup provides a complete development environment with all microservices pre-configured.

### Prerequisites

#### For Docker Setup (Recommended)
- **[Docker](https://docs.docker.com/get-docker/)** (20.10+ recommended)
- **[Docker Compose](https://docs.docker.com/compose/install/)** (2.0+ recommended)
- **Git** for cloning the repository

#### For Manual Setup
- **[PHP 8.1+](https://www.php.net/downloads.php)** with extensions: `pdo_mysql`, `gd`, `intl`, `curl`, `zip`
- **[Composer](https://getcomposer.org)** (2.0+ recommended)  
- **[Node.js 18+](https://nodejs.org/)** with npm/yarn
- **[MySQL 8.0+](https://dev.mysql.com/downloads/)** or **[MariaDB 10.6+](https://mariadb.org/download/)**
- **[Python 3.8+](https://python.org/)** with pip (for facial recognition service)

### Docker Installation (Recommended)

1. **Clone the repository**
   ```powershell
   git clone https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp.git
   cd WamiaGo-Webapp
   ```

2. **Start all services**
   ```powershell
   docker-compose up -d
   ```

3. **Install dependencies and set up the application**
   ```powershell
   # Install PHP dependencies
   docker-compose exec webapp composer install
   
   # Install Node.js dependencies
   docker-compose exec webapp npm install
   
   # Build frontend assets
   docker-compose exec webapp npm run build
   ```

4. **Initialize the database**
   ```powershell
   # Create database
   docker-compose exec webapp php bin/console doctrine:database:create
   
   # Run migrations
   docker-compose exec webapp php bin/console doctrine:migrations:migrate --no-interaction
   
   # Load sample data (optional)
   docker-compose exec webapp php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Access the application**
   - 🌐 **Main App**: http://localhost
   - 🗄️ **phpMyAdmin**: http://localhost:8080
   - 🤖 **AI Service**: http://localhost:5000

### Manual Installation

1. **Clone and setup**
   ```powershell
   git clone https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp.git
   cd WamiaGo-Webapp
   ```

2. **Install PHP dependencies**
   ```powershell
   composer install
   ```

3. **Install Node.js dependencies**
   ```powershell
   npm install
   ```

4. **Configure environment**
   ```powershell
   cp .env .env.local
   # Edit .env.local with your configuration
   ```

5. **Setup database**
   ```powershell
   # Create database manually in MySQL
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. **Build assets**
   ```powershell
   npm run build
   ```

7. **Start services**
   ```powershell
   # Start facial recognition service
   cd facial-recognition
   pip install -r requirements.txt
   python app.py &
   cd ..
   
   # Start Symfony development server
   symfony serve
   ```

### Environment Configuration

Configure your environment variables in `.env.local`:

```env
###> Application Settings ###
APP_ENV=dev
APP_SECRET=your_32_character_secret_key_here
APP_DEBUG=1
###< Application Settings ###

###> Database Configuration ###
DATABASE_URL="mysql://username:password@127.0.0.1:3306/wamia_go?serverVersion=8.0&charset=utf8mb4"
###< Database Configuration ###

###> Email Configuration ###
# Gmail
MAILER_DSN=gmail://username:password@default
# or Mailgun
MAILER_DSN=mailgun://key:domain@default
# or SMTP
MAILER_DSN=smtp://user:pass@smtp.example.com:587
###< Email Configuration ###

###> Twilio SMS (Optional) ###
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
###< Twilio SMS ###

###> Facial Recognition Service ###
FACIAL_RECOGNITION_URL=http://localhost:5000
###< Facial Recognition Service ###

###> 2FA Configuration ###
SCHEB_2FA_ISSUER="WamiaGo Webapp"
###< 2FA Configuration ###
```

---

## 📖 Usage Guide

### Authentication System

#### Standard Login
1. Navigate to `/login`
2. Enter your email/username and password
3. Complete 2FA verification if enabled
4. Optionally use facial recognition for additional security

#### Setting Up Two-Factor Authentication
1. Log in to your account
2. Go to **Profile → Security Settings**
3. Click **"Enable 2FA"**
4. Scan the QR code with Google Authenticator or similar app
5. Enter the verification code to confirm setup
6. **Save your backup codes** in a secure location

#### Facial Recognition Setup
1. Ensure camera permissions are granted
2. Navigate to **Profile → Security Settings**
3. Click **"Setup Facial Recognition"**
4. Follow the on-screen instructions to capture your face data
5. Test the recognition system

### Bicycle Rental System

#### Renting a Bicycle
1. **Find a Station**: Browse available stations on the map
2. **Select a Bike**: Choose from available bicycles at the station
3. **Scan QR Code**: Use the mobile app or scan the bike's QR code
4. **Start Riding**: The bike will unlock automatically
5. **Track Usage**: Monitor your ride time and route

#### Returning a Bicycle
1. **Find Return Station**: Locate any available station
2. **Dock the Bike**: Securely dock the bicycle
3. **Confirm Return**: The system will automatically detect and confirm return
4. **View Summary**: Check your ride summary and charges

#### Managing Rentals
- **📊 Rental History**: View all past rentals with details
- **💳 Payment Methods**: Manage payment options
- **⭐ Rate Experience**: Provide feedback on your rides

### Transportation Services

#### For Passengers
1. **Book a Ride**: Select pickup and destination points
2. **Choose Vehicle Type**: Select from available vehicle options  
3. **Track Driver**: Real-time tracking of assigned driver
4. **Complete Trip**: Rate driver and complete payment

#### For Drivers
1. **Register as Driver**: Complete driver verification process
2. **Add Vehicle**: Register and verify your vehicle
3. **Go Online**: Start accepting ride requests
4. **Manage Trips**: Handle bookings and navigate to destinations

### Admin Dashboard

Access the admin panel at `/admin` (requires admin privileges):

- **👥 User Management**: View, edit, and manage user accounts
- **🚴‍♂️ Bicycle Management**: Monitor bike fleet and station status
- **🚗 Vehicle Fleet**: Manage drivers and vehicles
- **📊 Analytics**: View comprehensive usage statistics
- **⚙️ System Settings**: Configure application parameters

---

## 🔌 API Documentation

### Authentication Endpoints
```http
POST   /api/auth/login              # User authentication
POST   /api/auth/2fa/verify         # Verify 2FA token
POST   /api/auth/facial             # Facial recognition login
POST   /api/auth/logout             # User logout
GET    /api/auth/user               # Get current user info
```

### Bicycle Rental API
```http
GET    /api/bicycles                # List available bicycles
GET    /api/bicycle-stations        # List all stations with availability
POST   /api/bicycle-rentals         # Start new rental
PUT    /api/bicycle-rentals/{id}    # End rental (return bike)
GET    /api/bicycle-rentals/user    # User's rental history
GET    /api/bicycle-rentals/{id}    # Get specific rental details
```

### Transportation API
```http
GET    /api/trips                   # List user trips
POST   /api/trips                   # Create trip booking
PUT    /api/trips/{id}              # Update trip status
DELETE /api/trips/{id}              # Cancel trip
GET    /api/drivers                 # List available drivers
POST   /api/drivers/register        # Driver registration
GET    /api/vehicles                # List vehicles
```

### Admin API
```http
GET    /api/admin/dashboard         # Dashboard statistics
GET    /api/admin/users             # User management
GET    /api/admin/bicycle-rentals   # Rental management
GET    /api/admin/stations          # Station management
GET    /api/admin/analytics         # System analytics
POST   /api/admin/stations          # Create new station
PUT    /api/admin/stations/{id}     # Update station
```

### Facial Recognition API
```http
POST   /api/facial/register         # Register face data
POST   /api/facial/verify           # Verify face against stored data
POST   /api/facial/update           # Update face data
DELETE /api/facial/delete           # Remove face data
```

---

## 🧪 Development

### Running Tests
```powershell
# PHP Unit Tests
php bin/phpunit

# PHP Unit Tests with coverage
php bin/phpunit --coverage-html coverage

# JavaScript Tests
npm test

# Facial Recognition Service Tests
cd facial-recognition
python -m pytest tests/ -v
cd ..
```

### Development Commands
```powershell
# Database operations
php bin/console doctrine:migrations:generate  # Create new migration
php bin/console doctrine:schema:update --dump-sql  # Preview schema changes
php bin/console cache:clear                   # Clear application cache

# Asset management
npm run dev          # Build assets for development
npm run watch        # Watch for changes and rebuild
npm run build        # Build assets for production
npm run analyze      # Analyze bundle size

# Code quality
composer cs-check    # Check code style
composer cs-fix      # Fix code style issues
composer phpstan     # Static analysis
```

### Development Environment
```powershell
# Start development with file watching
docker-compose -f compose.yaml -f compose.override.yaml up -d

# View logs
docker-compose logs -f webapp
docker-compose logs -f facial-recognition

# Access container shell
docker-compose exec webapp bash
docker-compose exec mysql mysql -u root -p
```

---

## 🐛 Troubleshooting

### Common Issues

#### 🤖 Facial Recognition Service Issues
```powershell
# Check if service is running
docker-compose ps facial-recognition

# View service logs
docker-compose logs facial-recognition

# Restart the service
docker-compose restart facial-recognition
```

**Solutions:**
- Ensure camera permissions are granted in browser
- Check Python dependencies: `pip install -r facial-recognition/requirements.txt`
- Verify OpenCV installation: `python -c "import cv2; print(cv2.__version__)"`

#### 🔐 2FA Authentication Problems
**Symptoms:** Invalid TOTP codes, backup codes not working

**Solutions:**
- Check time synchronization between server and device
- Regenerate 2FA secret if persistent issues
- Use backup codes for emergency access
- Verify TOTP issuer configuration in `.env.local`

#### 🗄️ Database Connection Issues
```powershell
# Test database connection
php bin/console doctrine:query:sql "SELECT 1"

# Check database exists
php bin/console doctrine:database:create --if-not-exists
```

**Solutions:**
- Verify MySQL service is running: `docker-compose ps mysql`
- Check database credentials in `.env.local`
- Ensure database exists and migrations are applied

#### 🎨 Asset Loading Problems
```powershell
# Clear and rebuild assets
rm -rf public/build
npm run build
php bin/console cache:clear
```

**Solutions:**
- Check file permissions on `public/build/` directory
- Verify Node.js dependencies: `npm install`
- Ensure Symfony Asset Mapper is configured correctly

#### 🐳 Docker Issues
```powershell
# Reset Docker environment
docker-compose down -v
docker-compose up -d --build

# Check container status
docker-compose ps
docker-compose logs
```

### Performance Optimization

```powershell
# Enable PHP OPcache in production
echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Optimize Composer autoloader
composer dump-autoload --optimize

# Enable Symfony cache preloading
php bin/console cache:warmup --env=prod
```

---

## 🤝 Contributing

We welcome contributions to make WamiaGo even better! Here's how you can help:

### Development Workflow
1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/amazing-feature`
3. **Follow** our coding standards and best practices
4. **Test** your changes thoroughly
5. **Commit** your changes: `git commit -m 'Add amazing feature'`
6. **Push** to the branch: `git push origin feature/amazing-feature`
7. **Open** a Pull Request with a detailed description

### Coding Standards
- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use ESLint configuration provided
- **CSS**: Follow BEM methodology where applicable
- **Git**: Use conventional commit messages
- **Testing**: Write unit tests for new features

### Areas for Contribution

🔐 **Security Enhancements**
- Multi-factor authentication improvements
- OAuth2 integration (Google, Facebook, etc.)
- Advanced fraud detection

🚴‍♂️ **Bicycle System Features**  
- Electric bike integration
- Maintenance scheduling
- Route optimization
- Mobile app development

🤖 **AI/ML Improvements**
- Enhanced facial recognition accuracy
- Predictive analytics for bike demand
- Smart routing algorithms
- User behavior analysis

🎨 **UI/UX Enhancements**
- Mobile-first responsive design
- Accessibility improvements (WCAG compliance)
- Progressive Web App (PWA) features
- Dark mode support

📊 **Analytics & Reporting**
- Advanced dashboard widgets
- Real-time monitoring
- Custom report generation
- Data export capabilities

🌍 **Internationalization**
- Multi-language support
- Currency localization
- Regional compliance features

### Code Review Process
1. All changes require code review
2. Automated tests must pass
3. Security review for authentication/authorization changes
4. Performance impact assessment for core features

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

### Third-Party Licenses
- Symfony Framework: MIT License
- Bootstrap: MIT License
- Chart.js: MIT License
- dlib: Boost Software License
- OpenCV: Apache 2.0 License

---

## 📞 Contact

**WamiaGo Development Team**

📧 **Email**: [wamiago@gmail.com](mailto:wamiago@gmail.com)  
🔗 **Repository**: [github.com/AtfastrSlushyMaker/WamiaGo-Webapp](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp)  
🌐 **Documentation**: [Coming Soon]  
💬 **Discussions**: [GitHub Discussions](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp/discussions)

### Support & Community
- 🐛 **Bug Reports**: [Create an Issue](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp/issues)
- 💡 **Feature Requests**: [Feature Request Template](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp/issues/new?template=feature_request.md)
- 📚 **Documentation**: [Wiki](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp/wiki)
- ❓ **Questions**: [Q&A Discussions](https://github.com/AtfastrSlushyMaker/WamiaGo-Webapp/discussions/categories/q-a)

---

<div align="center">
  <strong>Made with ❤️ by the WamiaGo Team</strong><br>
  <sub>Building the future of smart transportation</sub>
</div>
