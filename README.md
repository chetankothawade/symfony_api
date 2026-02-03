# Symfony API Setup

This project is a Symfony 7.4 API with JWT auth, custom login/signup, and password reset.

## Requirements

- PHP 8.2+ (project uses 8.3)
- Composer
- MySQL 8+

## Required Packages

Already included in `composer.json`:
- `symfony/framework-bundle`
- `doctrine/doctrine-bundle`
- `doctrine/doctrine-migrations-bundle`
- `lexik/jwt-authentication-bundle`
- `symfony/security-bundle`
- `symfony/validator`
- `symfony/mailer`
- `api-platform/symfony`

Dev:
- `doctrine/doctrine-fixtures-bundle`
- `symfony/maker-bundle`

## Install Symfony CLI (optional but recommended)

Windows:
```
scoop install symfony-cli
```
or
```
choco install symfony-cli
```

## Environment

Update `.env` (or better, `.env.local`):

```
APP_ENV=dev
APP_SECRET=your_secret

DATABASE_URL="mysql://root:@127.0.0.1:3306/symfony_api?serverVersion=8.0&charset=utf8mb4"

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

MAILER_DSN=smtp://localhost:1025
MAILER_FROM=no-reply@example.com

FRONTEND_RESET_URL=http://localhost/reset-password
```

## Install

```
composer install
```

## JWT Keys

Generate JWT keys:

```
php bin/console lexik:jwt:generate-keypair
```

## Database

Create DB schema and run migrations:

```
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Seed Users

Run fixtures (creates 1 user each: `super_admin`, `admin`, `editor`):

```
php bin/console doctrine:fixtures:load
```

Seed users (default password):

```
Password@123
```

## Run Server

Using Symfony CLI:
```
symfony serve --port=8020
```

Or:
```
symfony server:start
```

Or:

```
php -S 127.0.0.1:8000 -t public
```

## API Endpoints

### Login

```
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "Password@123"
}
```

### Signup

```
POST /api/signup
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "Password@123",
  "phone": "9999999999"
}
```

### Logout

```
POST /api/logout
Authorization: Bearer <TOKEN>
```

### Forgot Password

```
POST /api/forgot-password
Content-Type: application/json

{
  "email": "admin@example.com"
}
```

### Reset Password

```
POST /api/reset-password
Content-Type: application/json

{
  "email": "admin@example.com",
  "token": "<TOKEN_FROM_EMAIL>",
  "password": "NewPass123",
  "password_confirmation": "NewPass123"
}
```

## Useful Commands

Clear cache:
```
php bin/console cache:clear
```

List routes:
```
php bin/console debug:router
```

Validate Doctrine schema:
```
php bin/console doctrine:schema:validate
```

Run migrations status:
```
php bin/console doctrine:migrations:status
```

## Notes

- `forgot-password` returns the token in the response for testing. Remove it in production.
- `logout` only records `last_logout_at`. JWT tokens remain valid until they expire.
# symfony_api
