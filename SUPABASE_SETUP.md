# Supabase Setup Guide for TechReserve

This guide will help you set up Supabase to access your database from any PC.

## Step 1: Create a Supabase Project

1. Go to https://supabase.com
2. Sign up or log in with your account
3. Click "New Project"
4. Fill in the project details:
   - **Project Name**: `techreserve` (or your preferred name)
   - **Database Password**: Create a strong password (save this!)
   - **Region**: Choose a region close to you
5. Click "Create new project" and wait for initialization (2-3 minutes)

## Step 2: Get Your Connection Details

Once your project is ready:

1. Go to **Settings** (bottom left) → **Database**
2. Under "Connection string", select **URI** tab
3. Copy the connection string (looks like: `postgresql://postgres:password@host:5432/postgres`)
4. Extract these values:
   - **Host**: The domain part (e.g., `db.xxxxx.supabase.co`)
   - **Port**: `5432`
   - **Database**: `postgres`
   - **Username**: `postgres`
   - **Password**: Mojica!Sean17

## Step 3: Update Your .env File

Edit your `.env` file in the project root and add/update:

```env
POSTGRES_DB=postgres
POSTGRES_USER=postgres
POSTGRES_PASSWORD=Mojica!Sean17
POSTGRES_HOST=your_supabase_host_here
POSTGRES_EXTERNAL_PORT=5432
PGADMIN_DEFAULT_EMAIL=admin@techreserve.com
PGADMIN_DEFAULT_PASSWORD=admin
BACKEND_EXTERNAL_PORT=8000
FRONTEND_EXTERNAL_PORT=5173
```

Replace:
- `your_supabase_password_here` with your Supabase database password
- `your_supabase_host_here` with your Supabase host (e.g., `db.xxxxx.supabase.co`)

## Step 4: Migrate Your Database Schema

If you have existing tables in your local database, you need to migrate them to Supabase:

### Option A: Using Supabase SQL Editor (Recommended for small databases)

1. In Supabase dashboard, go to **SQL Editor**
2. Click "New Query"
3. Export your current database schema:
   ```bash
   # From your local machine
   pg_dump -U postgres -d techreserve_db --schema-only > schema.sql
   ```
4. Copy the contents of `schema.sql` and paste into Supabase SQL Editor
5. Click "Run"

### Option B: Using pg_dump and psql

```bash
# Export schema and data from local database
pg_dump -U postgres -d techreserve_db > backup.sql

# Import to Supabase (replace with your Supabase connection details)
psql -h your_supabase_host -U postgres -d postgres < backup.sql
```

## Step 5: Start Your Application

```bash
# From project root
docker compose up -d
```

Your application will now connect to Supabase instead of the local database.

## Step 6: Access from Other PCs

From any other PC:

1. Clone your repository (or pull latest changes)
2. Copy the `.env` file with Supabase credentials
3. Run `docker compose up -d`
4. Your app will connect to the same Supabase database

## Accessing Supabase from Other PCs

You can also directly access Supabase:

1. Go to https://supabase.com and log in
2. Select your project
3. Use the **SQL Editor** or **Table Editor** to view/modify data
4. Use pgAdmin (if running locally) at `http://localhost:5050`

## Troubleshooting

### Connection Refused
- Check your Supabase host is correct
- Verify your password is correct
- Ensure Supabase project is active (not paused)

### Schema Not Found
- Run migrations again using SQL Editor
- Check that tables were created successfully

### Slow Connection
- Choose a Supabase region closer to you
- Check your internet connection

## Notes

- Supabase free tier includes 500MB storage and 2GB bandwidth
- For production, consider upgrading to a paid plan
- Always keep your `.env` file secure and don't commit it to git
- Supabase automatically handles backups
