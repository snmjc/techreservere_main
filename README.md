# TechReserve

## Run With Docker

1. Copy the development environment file if needed:

   ```powershell
   Copy-Item .env.dev.example .env.dev
   ```

2. Update the Clerk values in `.env.dev` when authentication needs to work against a real Clerk app.

3. Start the full local stack:

   ```powershell
   npm run docker:up
   ```

   Or run Docker Compose directly:

   ```powershell
   docker compose --env-file .env.dev up --build
   ```

4. Open the services:

   - Frontend: http://localhost:5173
   - Backend: http://localhost:8000
   - Backend health check: http://localhost:8000/health
   - pgAdmin: http://localhost:5050
   - PostgreSQL: localhost:55432

The ports come from `.env.dev`, so change `FRONTEND_EXTERNAL_PORT`, `BACKEND_EXTERNAL_PORT`, `PGADMIN_EXTERNAL_PORT`, or `POSTGRES_EXTERNAL_PORT` there if a port is already in use.

## Useful Docker Commands

```powershell
npm run docker:ps
npm run docker:logs
npm run docker:down
```

The backend container runs Composer install when `vendor` is missing and applies Doctrine migrations on boot in development. Rebuild the backend container after backend code changes, or use Docker Compose watch:

```powershell
docker compose --env-file .env.dev watch
```

The frontend container runs Vite in dev mode with file polling enabled for bind-mounted Windows files.
