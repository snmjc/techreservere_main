# TechReserve Supabase Final Setup Guide

Your Supabase project is ready! Follow these steps to complete the setup.

## Step 1: Apply Database Schema to Supabase

1. Open https://supabase.com in your browser
2. Log in with your account
3. Click on your **techreserve** project
4. In the left sidebar, click **SQL Editor**
5. Click **New Query** button
6. Copy all the SQL code from `supabase_schema.sql` file in your project
7. Paste it into the SQL editor
8. Click the **Run** button (or press Ctrl+Enter)
9. Wait for the query to complete - you should see "Success" message

### Verify Tables Were Created
- Go to **Table Editor** in the left sidebar
- You should see these 8 tables:
  - accounts
  - venues
  - equipment
  - reservations
  - release_returns
  - notifications
  - audit_logs
  - tasks

## Step 2: Replace Your .env File

1. In your project root directory (`c:\Users\msean\Documents\techreservere_main\`)
2. Delete the current `.env` file
3. Rename `.env.new` to `.env`

Your `.env` file now contains:
```
POSTGRES_DB=postgres
POSTGRES_USER=postgres
POSTGRES_PASSWORD=Mojica!Sean17
POSTGRES_HOST=vwvoefadwrvsadrpceot.supabase.co
POSTGRES_EXTERNAL_PORT=5432
PGADMIN_DEFAULT_EMAIL=admin@techreserve.com
PGADMIN_DEFAULT_PASSWORD=admin
BACKEND_EXTERNAL_PORT=8000
FRONTEND_EXTERNAL_PORT=5173
VITE_SUPABASE_URL=https://vwvoefadwrvsadrpceot.supabase.co
VITE_SUPABASE_ANON_KEY=sb_publishable_PArx9Gqcv7nB9XdpZlXAag_WIH0ivab
VITE_API_BASE_URL=http://localhost:8000
```

## Step 3: Start Your Application

Open PowerShell/Command Prompt and run:

```bash
cd c:\Users\msean\Documents\techreservere_main
docker compose up -d
```

This will start:
- Backend (Symfony) on http://localhost:8000
- Frontend (Vue) on http://localhost:5173
- pgAdmin on http://localhost:5050

## Step 4: Verify Everything Works

1. **Check Backend**: Open http://localhost:8000 in browser
2. **Check Frontend**: Open http://localhost:5173 in browser
3. **Check pgAdmin**: Open http://localhost:5050 (admin@techreserve.com / admin)

## Access Your Database from Other PCs

Now you can access your database from any PC:

### Option 1: Using Supabase Dashboard
- Go to https://supabase.com
- Log in and open your project
- Use **Table Editor** to view/edit data
- Use **SQL Editor** to run queries

### Option 2: Using pgAdmin (Local)
- Open http://localhost:5050
- Email: admin@techreserve.com
- Password: admin
- Add connection to Supabase:
  - Host: vwvoefadwrvsadrpceot.supabase.co
  - Port: 5432
  - Database: postgres
  - Username: postgres
  - Password: Mojica!Sean17

### Option 3: Using Your Application
- Your backend automatically connects to Supabase
- Your frontend can query Supabase using the client in `src/lib/supabaseClient.js`

## Troubleshooting

### Docker won't start
```bash
# Check if containers are already running
docker ps

# Stop existing containers
docker compose down

# Start fresh
docker compose up -d
```

### Can't connect to Supabase
- Verify host: `vwvoefadwrvsadrpceot.supabase.co`
- Verify password: `Mojica!Sean17`
- Check Supabase project is active (not paused)

### Tables not appearing in Supabase
- Go to SQL Editor
- Run: `SELECT * FROM information_schema.tables WHERE table_schema = 'public';`
- If no tables appear, re-run the schema SQL

## Summary

✅ Supabase project created
✅ Database schema created
✅ Frontend packages installed
✅ Environment variables configured
✅ Docker compose updated for Supabase

Your TechReserve application is now connected to Supabase and can be accessed from any PC!
