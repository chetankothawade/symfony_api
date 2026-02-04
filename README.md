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

## Testing

This project includes comprehensive unit and integration tests using PHPUnit.

### Test Structure

**Integration Tests** (`tests/Integration/`)
- `AuthControllerTest.php` - Tests for authentication endpoints
- `UserControllerTest.php` - Tests for user management endpoints

**Unit Tests** (`tests/Unit/Service/`)
- `AuthServiceTest.php` - Tests for authentication business logic
- `UserServiceTest.php` - Tests for user management business logic

### Test Configuration

Tests use a separate MySQL database (`symfony_api_test`) configured in `.env.test`:

```
APP_ENV=test
APP_SECRET=test
DATABASE_URL="mysql://root:@127.0.0.1:3306/symfony_api_test?serverVersion=8.0&charset=utf8mb4"
```

### Running Tests

```bash
# Run all tests
php bin/phpunit

# Run only Integration tests
php bin/phpunit --testsuite Integration

# Run only Unit tests
php bin/phpunit --testsuite Unit

# Run with code coverage
php bin/phpunit --coverage-html coverage/

# Run specific test class
php bin/phpunit tests/Integration/AuthControllerTest.php

# Run specific test method
php bin/phpunit --filter testLoginSuccess
```

### Test Database Setup

The test database is automatically initialized when tests run. The first test execution will:
1. Create the database schema using Doctrine migrations
2. Reset the schema for each test to ensure isolation

To manually prepare the test database:

```bash
# Create test database
php bin/console doctrine:database:create --env=test

# Run migrations
php bin/console doctrine:migrations:migrate --env=test
```

### Test Coverage

**Integration Tests (5 tests - 15 assertions)**
- Login with valid credentials
- Login with invalid password (401)
- Login with inactive user (403)
- List users with pagination
- Create, read, update, delete user + toggle status

**Unit Tests (18 tests - 65 assertions)**
- AuthService: Login, registration, password reset validation
- UserService: CRUD operations, status toggling, UUID lookup

### Test Data

Tests automatically create temporary test data:
- Default password for fixtures: `Password@123`
- Test users are created with roles: `admin`, `editor`, `super_admin`

### Debugging Tests

Enable verbose output:
```bash
php bin/phpunit --debug
```

Run with PSR-3 logging:
```bash
php bin/phpunit --log-junit junit.xml
```

Generate HTML report:
```bash
php bin/phpunit --coverage-html coverage/
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

Create a new migration:
```
php bin/console make:migration
```

## Notes

- `forgot-password` returns the token in the response for testing. Remove it in production.
- `logout` only records `last_logout_at`. JWT tokens remain valid until they expire.
- Tests use a separate test database to avoid affecting production data.
- The `.env.test` file is used automatically when running tests.
