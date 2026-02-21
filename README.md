# Symfony API Setup

This project is a Symfony 7.4 API with JWT auth, user management, module management, and RBAC (role/module/permission) support.

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
```bash
scoop install symfony-cli
```
or
```bash
choco install symfony-cli
```

## Environment

Update `.env` (or better, `.env.local`):

```env
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

```bash
composer install
```

## JWT Keys

Generate JWT keys:

```bash
php bin/console lexik:jwt:generate-keypair
```

## Database

Create DB and run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Important Migration Note

RBAC migration (`Version20260221091500`) creates a foreign key to `users.id`, so `users` must use `InnoDB`.

For fresh databases, this is already handled by migrations.  
If you have an older DB where `users` is `MyISAM`, run:

```bash
php bin/console doctrine:query:sql "ALTER TABLE users ENGINE=InnoDB"
```

## Seed Users

Run fixtures (creates users for roles: `super_admin`, `admin`, `editor`):

```bash
php bin/console doctrine:fixtures:load
```

Default seeded password:

```text
Password@123
```

## Run Server

Using Symfony CLI:
```bash
symfony serve --port=8020
```

or:
```bash
symfony server:start
```

or:
```bash
php -S 127.0.0.1:8000 -t public
```

## API Endpoints

All `/api/*` endpoints except login/signup/forgot-password/reset-password require:

```http
Authorization: Bearer <TOKEN>
```

### Auth

```http
POST /api/login
POST /api/signup
POST /api/logout
POST /api/forgot-password
POST /api/reset-password
```

### Users

```http
GET    /api/users?page=1&perPage=10&search=&sortedField=id&sortedBy=asc
POST   /api/users
GET    /api/users/{uuid}
PATCH  /api/users/{uuid}
DELETE /api/users/{uuid}
PATCH  /api/users/{uuid}/toggle-status
```

### Modules

```http
GET    /api/module?page=1&perPage=10&search=&status=&sortedField=id&sortedBy=desc
POST   /api/module
GET    /api/module/getList
GET    /api/module/{uuid}
GET    /api/module/{uuid}/edit
PATCH  /api/module/{uuid}
DELETE /api/module/{uuid}
PATCH  /api/module/{uuid}/active
```

Example module create payload:

```json
{
  "name": "User Management",
  "url": "/users",
  "icon": "users",
  "seq_no": 1,
  "is_sub_module": "N",
  "status": "active",
  "is_permission": "Y"
}
```

### RBAC - User Permissions

```http
POST /api/user-permissions/toggle
GET  /api/user-permissions/getAll/{uuid}
GET  /api/user-permissions/access/{uuid}
GET  /api/user-permissions/sidebar-menu
```

Toggle payload:

```json
{
  "userUuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "modulePermissionId": 1,
  "isChecked": true
}
```

## Postman

Collection file:

```text
postman_collection.json
```

Included folders:
- `Auth`
- `Users`
- `Modules`
- `RBAC - User Permissions`

Collection variables:
- `base_url`
- `token`
- `user_uuid`
- `target_user_uuid`
- `module_uuid`
- `module_permission_id`

## Testing

This project includes unit and integration tests using PHPUnit.

### Test Structure

Integration tests (`tests/Integration/`):
- `AuthControllerTest.php`
- `UserControllerTest.php`

Unit tests (`tests/Unit/Service/`):
- `AuthServiceTest.php`
- `UserServiceTest.php`

### Test Configuration

Tests use a separate MySQL database (`symfony_api_test`) configured in `.env.test`:

```env
APP_ENV=test
APP_SECRET=test
DATABASE_URL="mysql://root:@127.0.0.1:3306/symfony_api_test?serverVersion=8.0&charset=utf8mb4"
```

### Running Tests

```bash
php bin/phpunit
php bin/phpunit --testsuite Integration
php bin/phpunit --testsuite Unit
php bin/phpunit tests/Integration/AuthControllerTest.php
php bin/phpunit --filter testLoginSuccess
```

### Test Database Setup

The test DB is initialized/reset during tests.  
Manual setup:

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
```

## Useful Commands

```bash
php bin/console cache:clear
php bin/console debug:router
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
php bin/console make:migration
```

## Notes

- `forgot-password` returns reset token in response for testing; remove in production.
- `logout` records `last_logout_at`; JWT remains valid until expiry.
- Tests use a separate test DB (`.env.test`).
- RBAC tables: `modules`, `permissions`, `module_permissions`, `role_modules`, `user_permissions`.
