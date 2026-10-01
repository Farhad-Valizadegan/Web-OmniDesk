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

## Local Telegram validation

The Telegram sample endpoint is a single-account development spike, not a
multi-user connector. It is unavailable outside `APP_ENV=local` and disabled
unless `TELEGRAM_SAMPLE_OWNER_USER_ID` names the application user who owns the
configured TDLib session. Only that user can access the sample. Set this value
in your local `.env` after verifying session ownership; never share one session
across users. Use an absolute `TELEGRAM_TDLIB_RUNTIME` path outside `public/`.
Multi-user Telegram support still requires separate authenticated sessions.

Telegram photo/video previews use the same authenticated owner check. Media is
downloaded into the private TDLib runtime and streamed through the API; no local
file paths or bearer tokens are placed in media URLs. Photos load when visible,
and videos download when requested. Previews are limited to 100 MB per file;
unsupported codecs and larger files can be opened in Telegram.

## Local CAPTCHA settings

For local development only, set `CAPTCHA_ENABLED=false` in the backend `.env`
and `VITE_CAPTCHA_ENABLED=false` in the frontend's local environment file.
Production always enforces CAPTCHA, regardless of these development switches.

## Login email codes

After the password and CAPTCHA succeed, OmniDesk emails a six-digit code before
creating an authenticated session. Codes expire after 10 minutes, work once,
allow five guesses, and can be resent after 60 seconds. Configure a real mail
transport in `.env`; the default `log` transport is deliberately rejected for
login codes because it does not deliver email. For example, with an SMTP service:

    MAIL_MAILER=smtp
    MAIL_HOST=your-smtp-host
    MAIL_PORT=587
    MAIL_USERNAME=your-smtp-username
    MAIL_PASSWORD=your-smtp-password
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=your-verified-sender@example.com

Keep mail credentials only in the local `.env` or deployment secret store.

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
