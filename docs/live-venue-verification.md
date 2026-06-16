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

Print the expected current local asset names:

```powershell
Get-ChildItem frontend\dist\assets | Where-Object { $_.Name -like 'index-*.js' -or $_.Name -like 'ManageFacilities-*.js' -or $_.Name -like 'equipmentApi-*.js' } | Select-Object -ExpandProperty Name
```

Print the live HTML entry bundle:

```powershell
curl.exe -sL https://techreserve.farahkenawy.codes/admin/manage-facilities | Select-String -Pattern 'index-[^\" ]*js'
```

Print the live `ManageFacilities` and `equipmentApi` chunk names referenced by that entry bundle:

```powershell
$tmp = Join-Path $PWD 'live-index.js'
curl.exe -sL https://techreserve.farahkenawy.codes/assets/<LIVE-INDEX-HASH>.js -o $tmp
rg -o "ManageFacilities-[A-Za-z0-9_-]+\\.js|equipmentApi-[A-Za-z0-9_-]+\\.js" $tmp
Remove-Item $tmp -Force
```

The live asset names should match the ones in `frontend/dist/assets` from the build you just deployed.

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

## Optional backend smoke test

Run a direct venue create/delete smoke test against the real backend container:

```powershell
docker compose -f compose.prod.yml exec backend php bin/console app:venue:create-smoke-test
```

If this passes while the browser still fails, the remaining issue is almost certainly in the live frontend build or request payload path rather than the DB write path itself.
