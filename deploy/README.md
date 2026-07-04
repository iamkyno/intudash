# Deployment & Processing Setup

IntuDash relies on three long-lived processes in production. Without them,
campaigns and reminders will **not** send.

## 1. Web server
Nginx + PHP-FPM (or Apache) serving `public/`. Nothing special required.

## 2. Queue worker (sends campaigns)
`SendCampaignJob` is pushed onto the database queue when a scheduled campaign
is due. A worker must be running to process it.

Use the supervisor config in this folder:

```bash
sudo cp deploy/intudash-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start intudash-worker:*
```

After each deploy, `deploy.sh` calls `php artisan queue:restart` so workers
reload the new code gracefully.

## 3. Scheduler (finds due work every minute)
Add a single cron entry that runs Laravel's scheduler:

```cron
* * * * * cd /var/www/intudash && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler triggers, every minute:
- `campaigns:send-scheduled` — claims due campaigns and dispatches `SendCampaignJob`
- `reminders:dispatch` — sends due booking reminders

Both use `withoutOverlapping()` so a slow run never double-fires.

## Deploying new code

```bash
git pull
./deploy.sh
```

`deploy.sh` installs prod dependencies, migrates, rebuilds config/route/view
caches, and restarts the workers.

## Recommended production `.env`

```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database      # or redis if available
CACHE_STORE=database           # or redis
SESSION_DRIVER=database
```

If Redis is available, point `QUEUE_CONNECTION`, `CACHE_STORE`, and
`SESSION_DRIVER` at it for a further speed-up — no code changes needed.
