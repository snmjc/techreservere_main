# Live Venue Verification

Use this checklist when `Manage Facilities > Venue` fails on the live site.

## Deploy

```powershell
git pull
docker compose -f compose.prod.yml down
docker compose -f compose.prod.yml build --no-cache backend frontend
docker compose -f compose.prod.yml up -d
docker compose -f compose.prod.yml exec backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f compose.prod.yml exec backend php bin/console cache:clear --env=prod
```

## Verify database schema

```powershell
docker compose -f compose.prod.yml exec database psql -U techreserve_user -d techreserve -c "\d+ venues"
```

Expected venue fields:
- `availability_date`
- `operational_status`
- `availability_status`
- `image_url` with type `text`

## Verify frontend assets

Open the live page with DevTools open and `Disable cache` enabled, then hard refresh.

The `ManageFacilities-*.js` and `equipmentApi-*.js` filenames should change after a new deploy.

## If venue create still fails

Capture the backend log immediately after clicking `Save Venue`:

```powershell
docker compose -f compose.prod.yml logs --tail=150 backend
```

The venue controller now logs:
- request action
- payload size
- whether an image was included
- image length
- venue name
- status/date context

This should make production `500` failures diagnosable without guessing.
