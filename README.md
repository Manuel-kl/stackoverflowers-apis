# Kenic - Domain Management System

A Laravel based domain management system for handling domain registrations, renewals, and management with integration to WHMCS, payment gateways, and domain registrars.

## Features

- Domain registration and management
- WHMCS integration for client management (https://www.whmcs.com/)
- Paystack payment gateway integration
- Google Mail API integration
- SMS notifications through Tiaraconnect APIs
- Cart and checkout system

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP 8.2 or higher**

- **Composer** (latest version)
- **Database** (MySQL 8.0+, MariaDB 10.5+, or SQLite)
- **Web Server** Apache/Nginx

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Manuel-kl/stackoverflowers-apis.git
cd stackoverflowers-apis
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the environment file and configure it:

```bash
cp .env.example .env
```

**Important**: Ensure you provide the following required API keys and credentials for the application to function properly:

- **WHMCS Integration** - For client management and billing (https://www.whmcs.com/)
  - `WHMCS_IDENTIFIER`
  - `WHMCS_SECRET`
  - `WHMCS_URL`

- **SMS Service (Tiaraconnect)** - For notifications and OTP
  - `SMS_API_ENDPOINT`
  - `SMS_API_KEY`
  - `SMS_FROM`

- **Paystack Payment Gateway** - For payment processing
  - `PAYSTACK_SECRET_KEY`

Edit the `.env` file with your configuration:

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Database Setup

Create your database and run migrations:

```bash
php artisan migrate
```

### 6. Storage and Permissions

Create storage link and set permissions:

```bash
php artisan storage:link
```

### 7. Queue Setup

The queue worker is required for syncing orders and user data with WHMCS. Run the queue worker:

```bash
php artisan queue:work
```

## Development

### Starting the Development Server

```bash
# Laravel Server
php artisan serve

# Queue Worker
php artisan queue:listen

# Log Monitoring (Optional)
php artisan pail
```

## API Documentation

### Authentication Endpoints

- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout

### User Management

- `GET /api/user` - Get user profile
- `POST /api/user/details` - Update user details
- `GET /api/user/whmcs-details` - Check WHMCS user details
- `GET /api/user/domains` - Get user domains
- `DELETE /api/user/delete` - Delete user account

### OTP & Password Management

- `POST /api/otp/send` - Send verification code
- `POST /api/otp/verify` - Verify OTP code
- `POST /api/password/change` - Change password

### Domain Management

- `GET /api/domains/search` - Domain availability check
- `POST /api/domains/register` - Register a new domain
- `POST /api/domains/renew` - Renew a domain
- `GET /api/domains/epp-code` - Get domain EPP code
- `POST /api/domains/update-lock-status` - Update domain lock status
- `GET /api/domains/nameservers` - Get domain nameservers
- `POST /api/domains/nameservers` - Update domain nameservers

### Cart & Orders

- `GET /api/cart` - Get cart items
- `POST /api/cart/add` - Add item to cart
- `DELETE /api/cart/remove/{id}` - Remove item from cart
- `POST /api/orders` - Create order
- `GET /api/orders` - List user orders

### Payment

- `POST /api/orders/{order}/pay` - Pay for an order
- `GET /api/payments/query` - Query transaction status

### Support & Tickets

- `GET /api/tickets` - List tickets
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PUT /api/tickets/{id}` - Update ticket
- `DELETE /api/tickets/{id}` - Delete ticket
- `POST /api/tickets/{id}/reply` - Reply to ticket