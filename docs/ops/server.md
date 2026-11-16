# Server setup and deploys

PLAN M22, decision A12: one Hetzner Cloud VPS in Singapore behind Cloudflare, no paid panel.
Staging is a second, smaller VPS with fake data only. Everything below that needs an account,
a domain or a secret is done by a maintainer (O1, H9); the repository holds only the scripts.

## What runs where

| Piece | Where | Set up by |
|---|---|---|
| Nginx → PHP-FPM 8.4 (pool `work-atlas`, user `deploy`) | VPS | `ops/provision.sh`, `ops/nginx/` |
| PostgreSQL 18 (+ `pg_trgm`), Redis (localhost only) | VPS | `ops/provision.sh` |
| Horizon (queues, every AI call), Inertia SSR server | VPS, supervisor | `ops/supervisor/work-atlas.conf` |
| Scheduler, nightly backup | VPS, cron | `ops/cron/work-atlas` |
| Backups (`pg_dump`, encrypted with `age`) | Cloudflare R2 | `ops/backup/work-atlas-backup` |
| DNS, TLS, cache for `/build/*` only | Cloudflare | by hand (below) |
| Error monitoring (personal data scrubbed) | Sentry, free plan | `.env` (`SENTRY_LARAVEL_DSN`) |
| Analytics | Umami, self-hosted, own database | `.env` (`UMAMI_*`) |
| Mail for sign-in and password reset | Amazon SES | `.env` (`MAIL_MAILER=ses`, `AWS_*`) |
| Uptime | external check on `https://<domain>/up` | by hand |

`/up` answers 200 only when the database and Redis answer (`App\Support\Monitoring\CheckHealth`).

## First setup (maintainer)

1. **Accounts (H9)** in the project's name: Hetzner, Cloudflare (+ R2), Amazon SES, Sentry.
2. **VPS:** Ubuntu 24.04, Singapore, your SSH key for root. Then, on the server:

   ```bash
   git clone https://github.com/JayedHasan232/work-atlas.git /root/work-atlas
   DOMAIN=<domain> /root/work-atlas/ops/provision.sh
   ```

   The script is idempotent: pull and run it again after any change in `ops/`. It turns off password
   and root SSH login, so log in as `deploy` (or with `sudo` from your own admin user) afterwards.
3. **Database password:** `sudo -u postgres psql -c "alter role work_atlas password '<new password>'"`.
4. **Cloudflare:** DNS A/AAAA records to the VPS (proxied), SSL/TLS mode **Full (strict)**, an Origin
   CA certificate saved as `/etc/ssl/cloudflare/origin.pem` and `origin.key` (mode 600), then
   `nginx -t && systemctl reload nginx`. One cache rule: cache everything under `/build/`; no other
   page caching (pages carry personal state and `noindex` headers). Optionally limit ports 80/443 in
   the firewall to Cloudflare's ranges.
5. **The app's `.env`** at `/var/www/work-atlas/shared/.env` (owner `deploy`, mode 600), from
   `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, a new `APP_KEY`
   (`php artisan key:generate --show` on any machine), database, Redis, `QUEUE_CONNECTION=redis`,
   SES, Sentry, Umami. Never commit it.
6. **SES:** verify the domain, add the DKIM records SES gives you, an SPF record
   (`v=spf1 include:amazonses.com -all`) and DMARC (`v=DMARC1; p=quarantine; rua=mailto:<project email>`).
   Ask AWS to leave the SES sandbox before launch.
7. **Backups:** see [restore.md](restore.md) for the keys and the R2 bucket.
8. **GitHub (deploys):** environments `production` and `staging`, each with the secrets
   `DEPLOY_SSH_KEY` (a key only for deploys, added to `/home/deploy/.ssh/authorized_keys`),
   `DEPLOY_KNOWN_HOSTS` (`ssh-keyscan <server>`), `DEPLOY_HOST` and `SMOKE_URL`. Add required
   reviewers to `production` if you want a human click. Then set the repository variable
   `DEPLOY_ENABLED` to `true`.
9. **First deploy:** run the *Deploy* workflow by hand, then on the server
   `sudo supervisorctl update` once so Horizon and SSR start.
10. **Check:** `curl https://<domain>/up`, a page renders with SSR, and a test exception reaches
    Sentry: `php artisan sentry:test` on the server (its report contains no personal data).
11. **Uptime check:** any external monitor on `https://<domain>/up` every few minutes.

## Deploys

A push to `main` runs CI; when CI passes, the *Deploy* workflow (`.github/workflows/deploy.yml`) runs
`dep deploy production` (`deploy.php`): new release directory, `composer install --no-dev`,
`npm ci && npm run build:ssr`, `artisan optimize`, **migrations**, switch the `current` symlink,
reload PHP-FPM, restart Horizon and SSR, check `/up`, then the SSR smoke test against `SMOKE_URL`.
Five releases are kept. To roll back: `vendor/bin/dep rollback production` from a checkout with the
same SSH access (migrations are not rolled back; write a new migration instead).

Staging: run the *Deploy* workflow by hand with `stage: staging` (any branch).

## Staging

Same script with `DOMAIN=staging.<domain>`, a 2 vCPU VPS. In its `.env`: `APP_ENV=staging`,
`STAGING_BASIC_AUTH_USER` and `STAGING_BASIC_AUTH_PASSWORD` (without both, nobody gets in),
no Umami, its own Sentry environment (`SENTRY_ENVIRONMENT=staging`). Every response is `noindex`.
Fake data only, never a copy of production:

```bash
php artisan migrate --force && php artisan db:seed --class=StagingSeeder --force
```

The seeder makes twelve "Example occupation N (staging)" pages with made-up Wave 1 values and
refuses to run in production. Put the basic-auth user and password in the staging `SMOKE_URL`
(`https://user:password@staging.<domain>/`).
