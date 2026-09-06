# MCARE Teammate Laptop Setup Guide

This guide takes a teammate from the GitHub repository to a working MCARE development system on a Windows laptop. It covers the local database, Laravel and Vite, Google OAuth, ngrok, email and admin 2FA, queues, PayMongo test webhooks, and official-document rendering.

## Setup target

- Repository: `https://github.com/delulArs/mcare_hub.git`
- Branch: `added-user-profile-something-HAHAHA`
- Reference commit when this guide was updated: `96ee904`
- Framework: Laravel 12
- PHP: 8.2 or newer
- Node.js: 20.19 or newer, or 22.12 or newer
- Recommended local operating system: Windows 10 or 11 with PowerShell

The branch is the source of truth. A newer commit is expected after another team push, so the teammate does not need to remain on `96ee904`.

Never send or commit a developer's `.env`, Google client secret, ngrok authtoken, Gmail app password, or PayMongo secret. Each teammate must create a local `.env` and receive access through the relevant provider's secure team controls.

## 1. Get access to the GitHub repository

If the repository is private, the repository owner must first add the teammate:

1. Open the repository on GitHub.
2. Go to **Settings -> Collaborators**.
3. Add the teammate's GitHub account.
4. The teammate accepts the invitation from GitHub.
5. On GitHub, use the branch selector and confirm that `added-user-profile-something-HAHAHA` is available.

The teammate needs Git before cloning. Install [Git for Windows](https://git-scm.com/download/win), then open a new PowerShell window and verify it:

```powershell
git --version
```

### Fresh clone

In PowerShell:

```powershell
Set-Location "$env:USERPROFILE\Documents"

git clone --branch "added-user-profile-something-HAHAHA" --single-branch https://github.com/delulArs/mcare_hub.git

Set-Location .\mcare_hub
git branch --show-current
git log -1 --oneline
git status --short --branch
```

The branch command must print:

```text
added-user-profile-something-HAHAHA
```

For a private repository, Git Credential Manager may open a browser so the teammate can sign in to GitHub. Use the teammate's account; do not share a GitHub password or personal access token.

### If the repository is already cloned

First inspect local work:

```powershell
Set-Location "$env:USERPROFILE\Documents\mcare_hub"
git status
```

If modified or untracked project files are important, commit them on the correct branch or save them safely before pulling. Do not discard a teammate's work just to update the checkout.

When the working tree is safe:

```powershell
git fetch --prune origin
git switch "added-user-profile-something-HAHAHA"
git pull --ff-only origin "added-user-profile-something-HAHAHA"
git log -1 --oneline
```

## 2. Install and verify the required software

Install these tools:

1. Git for Windows
2. PHP 8.2 or newer; XAMPP's PHP is acceptable
3. Composer 2
4. Node.js 20.19+ or 22.12+
5. Google Chrome or Microsoft Edge
6. MySQL/MariaDB through XAMPP only if using the MySQL option
7. ngrok only if Google OAuth, remote/mobile access, or PayMongo webhooks will use a public URL

Verify the command-line tools:

```powershell
git --version
php --version
composer --version
node --version
npm --version
where.exe php
php --ini
```

`where.exe php` and `php --ini` help confirm which PHP installation and `php.ini` Composer and Artisan are using. This is important when XAMPP and another PHP installation both exist.

MCARE needs common PHP extensions such as OpenSSL, Fileinfo, DOM/XML, ZIP, and the database driver for the chosen database. Check them with:

```powershell
php -m | Select-String -Pattern 'openssl|fileinfo|dom|xmlreader|zip|pdo|pdo_mysql|pdo_sqlite|sqlite3'
```

- SQLite requires `pdo_sqlite` and `sqlite3`.
- MySQL/MariaDB requires `pdo_mysql`.
- If an extension is missing in XAMPP, enable its `extension=...` line in the `php.ini` reported by `php --ini`, save the file, and open a new terminal.

## 3. Install PHP and frontend dependencies

Run these commands from the cloned `mcare_hub` directory:

```powershell
composer install
npm ci
```

Use `npm ci` for a clean clone because the repository includes `package-lock.json`. Puppeteer's browser dependency can make the first install take longer than usual.

Confirm that the installed PHP packages satisfy the laptop's PHP environment:

```powershell
composer check-platform-reqs --no-dev
```

Every item should report `success`.

## 4. Create the local `.env`

Create `.env` from the committed safe example. Do not overwrite an existing `.env`:

```powershell
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}

php artisan key:generate
```

Open `.env` in a text editor. For the first local-only run, use this base configuration and leave unrelated values from `.env.example` in place:

```dotenv
APP_NAME=MCARE
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
TRUSTED_PROXIES=127.0.0.1,::1
APP_TIMEZONE=Asia/Manila

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=660

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="MCARE"

TWO_FACTOR_ENABLED=true
TWO_FACTOR_ROLES=admin
TWO_FACTOR_TTL=10
TWO_FACTOR_MAX_ATTEMPTS=5

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

PAYMONGO_PUBLIC_KEY=
PAYMONGO_SECRET_KEY=
PAYMONGO_WEBHOOK_SECRET=
PAYMONGO_LIVE_MODE=false
PAYMONGO_PAYMENT_METHODS=gcash,card,qrph
PAYMONGO_WEBHOOK_TOLERANCE=300
```

`DB_QUEUE_RETRY_AFTER=660` is intentionally longer than the recommended 600-second queue-worker timeout used later for official documents.

For the easiest local admin login, `TWO_FACTOR_ENABLED=false` may be used temporarily. Keeping it `true` tests the complete admin flow; with `MAIL_MAILER=log`, the six-digit code is written to `storage/logs/laravel.log` rather than sent to an inbox.

Verify that Git ignores the local secret file:

```powershell
git check-ignore .env
git status --short
```

The first command should print `.env`, and `.env` must not appear as a file to commit.

## 5. Configure one local database

Choose either SQLite or MySQL. Do not configure both.

### Option A: SQLite for the easiest setup

SQLite is recommended for a teammate who needs a working local copy without sharing a database server.

In `.env`, use:

```dotenv
DB_CONNECTION=sqlite
```

Leave `DB_DATABASE` absent or commented so Laravel uses `database/database.sqlite`. Create the file without overwriting an existing database:

```powershell
if (-not (Test-Path -LiteralPath 'database\database.sqlite')) {
    New-Item -ItemType File -Path 'database\database.sqlite' | Out-Null
}
```

### Option B: XAMPP MySQL/MariaDB

1. Start **MySQL** in the XAMPP Control Panel. Apache is not required when using `php artisan serve`.
2. Open `http://localhost/phpmyadmin` if Apache is running, or use another MySQL client.
3. Create an empty database named `mcare_db` with `utf8mb4` encoding.
4. Use the following local values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mcare_db
DB_USERNAME=root
DB_PASSWORD=
```

The blank password is only for a default local XAMPP installation. Use the teammate's actual local MySQL credentials if they changed them.

Do not import another developer's private database merely to start the system. The repository migrations and seeders create a clean local development database.

## 6. Initialize and verify MCARE

Run:

```powershell
php artisan optimize:clear
php artisan migrate --seed
npm run build
php artisan about
php artisan migrate:status
```

`php artisan migrate --seed` creates the application tables, database-backed sessions, cache, queue tables, role permissions, demo accounts, a demo batch, and sample Career Hub entries.

Run the automated test suite before the teammate begins changing source code:

```powershell
php artisan test
```

The tests use an in-memory SQLite database configured by `phpunit.xml`; they do not erase the teammate's normal local database.

Never use `php artisan migrate:fresh`, `php artisan db:wipe`, or delete the database after the laptop contains important local records.

## 7. Run the local system

Use separate visible PowerShell terminals so each long-running process is easy to monitor and stop.

### Terminal 1: Laravel web server

```powershell
Set-Location "$env:USERPROFILE\Documents\mcare_hub"
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000`.

### Terminal 2: Queue worker

The queue is required for verification emails, announcements, LMS notifications, payment notifications, official-document generation, and batch exports.

```powershell
Set-Location "$env:USERPROFILE\Documents\mcare_hub"
php artisan queue:work --queue=mail,default --tries=3 --timeout=600
```

After pulling new PHP code or changing queue-related configuration, restart the worker:

```powershell
php artisan queue:restart
```

Then start `queue:work` again if the terminal has exited.

### Terminal 3: Vite only while editing frontend files

`npm run build` is enough for normal review. When actively changing CSS or JavaScript, run:

```powershell
Set-Location "$env:USERPROFILE\Documents\mcare_hub"
npm run dev
```

Stop any of these visible processes with `Ctrl+C` in its terminal.

## 8. Expose the laptop with ngrok

Use ngrok when a teammate needs a public HTTPS address for a phone, a remote reviewer, Google OAuth over a public domain, or a PayMongo webhook.

1. Create the teammate's own [ngrok account](https://dashboard.ngrok.com/signup).
2. Install ngrok using the [official Windows instructions](https://ngrok.com/download/windows).
3. Copy the teammate's authtoken from the ngrok dashboard.
4. Add it once on that laptop, replacing the placeholder including the angle brackets:

```powershell
ngrok config add-authtoken "<TEAMMATE_NGROK_AUTHTOKEN>"
```

Do not send or commit the authtoken.

Keep Laravel running on port 8000. In another terminal, start a dynamic tunnel:

```powershell
ngrok http 8000
```

Copy the exact HTTPS forwarding address shown by ngrok, for example:

```text
https://example-name.ngrok-free.app
```

If the teammate has an assigned static ngrok domain, start it with:

```powershell
ngrok http 8000 --url https://your-assigned-domain.ngrok-free.app
```

Update `.env` to use that exact HTTPS base URL:

```dotenv
APP_URL=https://example-name.ngrok-free.app
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Then clear cached settings and restart Laravel and the queue worker:

```powershell
php artisan optimize:clear
php artisan queue:restart
```

Use the ngrok URL consistently during that browser session. Do not begin Google login on `127.0.0.1` and finish it on the ngrok host; changing hosts can invalidate the stateful OAuth session.

When returning to local HTTP-only use, restore:

```dotenv
APP_URL=http://127.0.0.1:8000
TRUSTED_PROXIES=127.0.0.1,::1
SESSION_SECURE_COOKIE=false
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Run `php artisan optimize:clear` and restart Laravel again. Do not expose real applicant documents, production credentials, or live payment mode through a temporary development tunnel.

## 9. Configure Google OAuth

MCARE uses Laravel Socialite's server-side, stateful OAuth flow. Google OAuth is intended for applicants, trainees, and graduates. Admin and trainer accounts are deliberately required to use email/password and the configured staff challenge.

Choose the URL before configuring Google:

- Local-only: `http://127.0.0.1:8000`
- Public test: the exact HTTPS ngrok URL from Section 8

### Google Cloud setup

1. Open the [Google Auth Platform](https://console.cloud.google.com/auth/overview) and create or select the team's development project.
2. Under **Branding**, configure the application name `MCARE`, support email, and developer contact.
3. Under **Audience**, use **External** when ordinary Google accounts will test it. Keep the app in testing while developing.
4. Add each teammate/tester's Google email under **Test users** while the app is in testing.
5. Under **Data Access**, the normal `openid`, email, and profile scopes are enough for MCARE login. Do not add unrelated sensitive scopes.
6. Under **Clients**, create an OAuth client with application type **Web application**.
7. Add the selected base URL as an authorized JavaScript origin.
8. Add the complete callback as an authorized redirect URI.

For local-only testing, enter exactly:

```text
Authorized JavaScript origin:
http://127.0.0.1:8000

Authorized redirect URI:
http://127.0.0.1:8000/auth/google/callback
```

For ngrok testing, enter exactly:

```text
Authorized JavaScript origin:
https://example-name.ngrok-free.app

Authorized redirect URI:
https://example-name.ngrok-free.app/auth/google/callback
```

Google requires the redirect URI to match exactly, including `http` versus `https`, hostname, port, path, and trailing slash. MCARE also requires the configured callback host to match `APP_URL` and the path to be `/auth/google/callback`.

Copy the Web application's client ID and client secret into the teammate's local `.env`:

```dotenv
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Apply the change:

```powershell
php artisan optimize:clear
php artisan route:list --path=auth/google
```

Restart Laravel, open the same base URL configured in `APP_URL`, and select **Sign in with Google**. Test with a Google account listed under Google Cloud's test users.

If a dynamic ngrok URL changes, update all three places before trying again:

1. `APP_URL` in `.env`
2. The authorized Google redirect URI and origin
3. Any external webhook URL that uses the old domain

The OAuth client secret belongs only in `.env`. Do not download or place Google's client-secret JSON inside the repository.

## 10. Configure email and admin 2FA

### Safe local mode

The default local setup writes email to Laravel's log:

```dotenv
MAIL_MAILER=log
TWO_FACTOR_ENABLED=true
```

Watch the log in another terminal while signing in as the admin:

```powershell
Get-Content -LiteralPath 'storage\logs\laravel.log' -Wait
```

The admin six-digit code is in the rendered mail entry. Treat the local log as sensitive.

### Gmail SMTP test delivery

Use a dedicated development sender with Google 2-Step Verification and an app password. Do not use the normal Google account password.

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=team-development-sender@gmail.com
MAIL_PASSWORD=the-google-app-password
MAIL_FROM_ADDRESS=team-development-sender@gmail.com
MAIL_FROM_NAME="MCARE"

TWO_FACTOR_ENABLED=true
```

Port 587 uses `MAIL_SCHEME=smtp`; Laravel/Symfony Mailer negotiates STARTTLS. Do not set `MAIL_SCHEME=tls`.

After changing mail values:

```powershell
php artisan optimize:clear
php artisan queue:restart
```

Start the queue worker again and test admin 2FA plus one queued notification. See [docs/EMAIL_2FA_SETUP.md](docs/EMAIL_2FA_SETUP.md) for the security details.

## 11. Configure PayMongo test mode only when needed

MCARE can run without PayMongo keys; online checkout remains unavailable while onsite payment and the rest of the local system continue to work.

Only configure this section when the team intentionally tests online payments. Use PayMongo test credentials, never live credentials:

```dotenv
PAYMONGO_SECRET_KEY=sk_test_your_key
PAYMONGO_WEBHOOK_SECRET=whsk_your_test_endpoint_secret
PAYMONGO_LIVE_MODE=false
PAYMONGO_PAYMENT_METHODS=gcash,card,qrph
PAYMONGO_WEBHOOK_TOLERANCE=300
```

`PAYMONGO_PUBLIC_KEY` is not required by MCARE's current server-created Hosted Checkout flow.

In the PayMongo test dashboard, create a webhook endpoint using the active public ngrok URL:

```text
https://example-name.ngrok-free.app/api/paymongo/webhook
```

Subscribe it to:

```text
checkout_session.payment.paid
```

Copy that endpoint's separate webhook signing secret into `PAYMONGO_WEBHOOK_SECRET`. Do not use the PayMongo API secret as the webhook signing secret.

Apply and verify:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list --path=paymongo
```

Keep the Laravel server and ngrok running for the webhook. A browser return from checkout is not proof of payment; MCARE marks the payment paid only after it verifies PayMongo's signed webhook. Follow [docs/PAYMONGO_SECURE_SETUP.md](docs/PAYMONGO_SECURE_SETUP.md) for the complete test procedure.

## 12. Official COTC/TOR document rendering

MCARE generates COTC and TOR PDFs in PHP when Node.js or Chrome is missing, including shared hosting. Keep the default private document settings:

```dotenv
OFFICIAL_DOCUMENT_PDF_ENGINE=auto
OFFICIAL_DOCUMENT_DISK=local
OFFICIAL_DOCUMENT_TEMPLATE_VERSION=1.0
OFFICIAL_DOCUMENT_EXPORT_EXPIRY_HOURS=24
```

The queue worker from Section 7 must be running. Chromium rendering is optional. On Windows, Microsoft Edge or Google Chrome is detected automatically if you set `OFFICIAL_DOCUMENT_PDF_ENGINE=browsershot`. If necessary:

```dotenv
OFFICIAL_DOCUMENT_PDF_ENGINE=browsershot
BROWSERSHOT_CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"
BROWSERSHOT_NODE_BINARY="C:\Program Files\nodejs\node.exe"
BROWSERSHOT_NPM_BINARY="C:\Program Files\nodejs\npm.cmd"
```

Run `php artisan optimize:clear` and restart the queue worker afterward. See [docs/TRAINING_RECORDS_AND_OFFICIAL_DOCUMENTS.md](docs/TRAINING_RECORDS_AND_OFFICIAL_DOCUMENTS.md) for the workflow and release rules.

## 13. Seeded local demo accounts

All seeded accounts use the local demo password `password123`.

| Portal | Email |
| --- | --- |
| Administrator | `admin@mcare.com` |
| Trainer | `trainer@mcare.com` |
| Trainee | `trainee@mcare.com` |
| Applicant | `applicant@mcare.com` |
| Graduate/Alumni | `alumni@mcare.com` |

Use `http://127.0.0.1:8000/login` for local access or the same `/login` path under the active ngrok domain.

These accounts and the password are for local development only. Never seed or retain them in a production database.

## 14. Pull future GitHub updates safely

Before pulling, stop the visible Laravel/Vite worker processes with `Ctrl+C` and inspect the teammate's work:

```powershell
git status
```

Commit important work on its branch or store it safely. When the checkout is ready:

```powershell
git fetch --prune origin
git switch "added-user-profile-something-HAHAHA"
git pull --ff-only origin "added-user-profile-something-HAHAHA"

composer install
npm ci
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test
php artisan queue:restart
```

The local `.env` and SQLite database are Git-ignored and should remain on the teammate's laptop. Review `.env.example` after major pulls in case the application adds new environment variables.

## 15. End-to-end verification checklist

With Laravel and the queue worker running, verify:

- [ ] `git branch --show-current` prints `added-user-profile-something-HAHAHA`.
- [ ] `composer check-platform-reqs --no-dev` reports success.
- [ ] `php artisan migrate:status` shows the migrations as run.
- [ ] `npm run build` completes and creates the Vite manifest.
- [ ] `php artisan test` passes.
- [ ] `http://127.0.0.1:8000/up` returns a healthy response in local mode.
- [ ] The landing page and `/login` load with styling.
- [ ] Each seeded role reaches its intended portal.
- [ ] The queue worker processes `mail` and `default` jobs.
- [ ] Admin 2FA produces a code in the log or the configured test mailbox.
- [ ] Google sign-in returns to the exact configured callback without losing the session.
- [ ] The ngrok HTTPS URL loads assets without mixed-content errors.
- [ ] If PayMongo is enabled, the signed test webhook changes a payment from pending to paid.
- [ ] If official documents are tested, the queue generates the PDF successfully.
- [ ] `.env`, databases, logs, uploaded documents, and secret keys are absent from `git status`.

Quick HTTP check from PowerShell:

```powershell
(Invoke-WebRequest -Uri 'http://127.0.0.1:8000/up').StatusCode
```

Expected result: `200`.

## 16. Common problems

### `composer install` reports a missing PHP extension

Run `php --ini`, enable the named extension in that `php.ini`, then open a new terminal. Confirm Composer and Artisan use the same PHP shown by `where.exe php`.

### `could not find driver`

- SQLite: enable `pdo_sqlite` and `sqlite3`.
- MySQL: enable `pdo_mysql` and start MySQL in XAMPP.

### `No application encryption key has been specified`

```powershell
php artisan key:generate
php artisan optimize:clear
```

### Missing Vite manifest or unstyled page

```powershell
npm ci
npm run build
```

### `SQLSTATE` table-not-found error

```powershell
php artisan migrate
php artisan migrate:status
```

Do not respond by running `migrate:fresh` on a database with important records.

### Port 8000 is already in use

Either stop the existing process or use another port:

```powershell
php artisan serve --host=127.0.0.1 --port=8011
```

If using 8011, update `APP_URL`, the Google origin and callback, and the ngrok local port so all URLs agree.

### Google `redirect_uri_mismatch`

Compare the URI in the error with Google Cloud and `.env` character by character. Scheme, host, port, path, and trailing slash must match. Run `php artisan optimize:clear` and restart Laravel after correcting it.

### Google `access_denied` while the OAuth app is in testing

Add that Google email to the project's OAuth test users, then retry.

### Google login fails with an invalid or lost OAuth state

Use one hostname from start to finish, make sure the database migrations have created the `sessions` table, keep the same `APP_KEY`, clear browser cookies for the old host if necessary, and retry from the landing page.

### ngrok page loads but assets or generated links use HTTP

Confirm:

```dotenv
APP_URL=https://the-current-ngrok-domain
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
```

Then run `php artisan optimize:clear` and restart Laravel.

### Emails or notifications remain pending

Make sure the queue worker is running. Inspect:

```powershell
php artisan queue:failed
```

With `MAIL_MAILER=log`, inspect `storage/logs/laravel.log`. With SMTP, recheck the sender address and app password.

### Official document generation fails

Confirm that the queue worker uses `--timeout=600`, `DB_QUEUE_RETRY_AFTER` is greater than 600, and Chrome/Edge or Puppeteer's headless browser is installed. Restart the worker after changing `.env`.

## Official references

- [GitHub: Getting changes from a remote repository](https://docs.github.com/en/get-started/using-git/getting-changes-from-a-remote-repository)
- [Google: OAuth 2.0 for web-server applications](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Google: Create a web OAuth client and configure consent](https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid)
- [ngrok: Windows installation and authtoken setup](https://ngrok.com/download/windows)
