# Backups and restore runbook

PLAN M22: a nightly encrypted `pg_dump` to Cloudflare R2, and a restore test every month, logged
below. A backup nobody has restored is not a backup.

## How backups work

- `/usr/local/bin/work-atlas-backup` (from `ops/backup/`) runs as `postgres` at 20:30 UTC (02:30 in
  Dhaka) from `/etc/cron.d/work-atlas`; output goes to `/var/log/work-atlas/backup.log`.
- `pg_dump --format=custom` → encrypted with [`age`](https://age-encryption.org) to the project's
  **public** key → streamed to R2 as `db/<database>-<UTC time>.dump.age`. It fails loudly when the
  upload is missing or tiny, and deletes backups older than `RETENTION_DAYS` (default 35).
- The **private** key never touches the server or R2. Two maintainers keep a copy offline (password
  manager). Losing both copies makes every backup unreadable.

## One-time setup (maintainer)

1. Make the key pair on your own machine: `age-keygen -o work-atlas-backup.agekey`. Store the file in
   the password manager; note the public key (`age1…`) it prints.
2. In Cloudflare R2: a private bucket `work-atlas-backups` and an API token with read and write on
   that bucket only.
3. On the server, as root:

   ```bash
   cat > /etc/work-atlas/backup.env <<'ENV'
   DB_NAME=work_atlas
   AGE_RECIPIENT=age1...
   R2_REMOTE=r2:work-atlas-backups
   RETENTION_DAYS=35
   ENV
   rclone config --config /etc/work-atlas/rclone.conf   # remote "r2", provider Cloudflare, the token above
   chown root:postgres /etc/work-atlas/backup.env /etc/work-atlas/rclone.conf
   chmod 640 /etc/work-atlas/backup.env /etc/work-atlas/rclone.conf
   sudo -u postgres /usr/local/bin/work-atlas-backup     # first backup, by hand
   ```

## Restore test (monthly)

On staging or your own machine, **never on production**, with rclone set up for the same bucket:

```bash
ops/backup/work-atlas-restore-test --identity /path/to/work-atlas-backup.agekey
```

It downloads the newest backup, decrypts it, restores it into a throwaway database, prints how long
that took and the row counts of the main tables, then drops the database. Compare the counts with
production (`select count(*) …`), then add a line to the log below. No personal data in the log.

## Real restore (production is lost or broken)

1. Put the site in maintenance: `php artisan down` (or stop Nginx if the server is gone).
2. New server if needed: `ops/provision.sh`, `.env` from the password manager, deploy `main`.
3. Download and decrypt the chosen backup:
   `rclone copyto r2:work-atlas-backups/db/<file> backup.dump.age` then
   `age --decrypt --identity work-atlas-backup.agekey --output backup.dump backup.dump.age`.
4. Restore into an empty database owned by the app role:
   `pg_restore --no-owner --no-privileges --exit-on-error --role=work_atlas --dbname=work_atlas backup.dump`.
5. `php artisan migrate --force` (for migrations newer than the backup), `php artisan up`, check
   `/up` and a few pages. Delete the decrypted files.
6. Write down what happened and how long it took in the log below.

## Restore-test log

| Date (UTC) | Backup file | Restored on | Minutes | Counts match production | By |
|---|---|---|---|---|---|
| — | — | — | — | — | *(first test after the production setup; blocked by O1/H9)* |
