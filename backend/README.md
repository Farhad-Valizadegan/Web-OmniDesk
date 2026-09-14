# OmniDesk AI Backend

Laravel REST API for authentication, unified conversations, provider connections, and Gmail synchronization.

## Requirements

- PHP 8.3 or newer
- Composer
- PostgreSQL
- PHP `pdo_pgsql` extension

## Installation

Run from the repository root:

    Set-Location backend
    composer install
    Copy-Item .env.example .env
    php artisan key:generate

Configure PostgreSQL in `.env`:

    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=omni_desk_ai
    DB_USERNAME=omni_desk_app
    DB_PASSWORD=your_password

Prepare the database:

    php artisan migrate

Optional development fixtures:

    php artisan db:seed --class=InboxFixtureSeeder

## Gmail Configuration

Configure these values in `.env`:

    FRONTEND_URL=http://127.0.0.1:5173
    GOOGLE_CLIENT_ID=
    GOOGLE_CLIENT_SECRET=
    GOOGLE_REDIRECT_URI=http://localhost:8000/api/integrations/gmail/callback
    GOOGLE_SCOPES="openid,email,profile,https://www.googleapis.com/auth/gmail.readonly"

Never commit credentials or access tokens.

## Development Server

From the `backend` directory:

    php artisan serve

Backend address:

    http://127.0.0.1:8000

## Tests

    php artisan test

## Code Formatting

Windows formatting check:

    vendor\bin\pint.bat --test

Windows automatic formatting:

    vendor\bin\pint.bat

Linux and CI formatting check:

    ./vendor/bin/pint --test

## Continuous Integration

GitHub Actions verifies PostgreSQL migrations, backend tests, Laravel Pint, frontend tests, frontend lint, and the frontend production build.
