# Supabase Schema Setup for TechReserve

This guide explains how to create all the necessary database tables in your Supabase project.

## Tables Created

1. **accounts** - User accounts with roles and authentication
2. **venues** - Venue/location information
3. **equipment** - Equipment inventory and availability
4. **reservations** - Reservation requests from borrowers
5. **release_returns** - Equipment release and return transactions
6. **notifications** - User notifications
7. **audit_logs** - Activity logging for compliance
8. **tasks** - Tasks related to reservations

## How to Apply the Schema

### Option 1: Using Supabase SQL Editor (Recommended)

1. Go to https://supabase.com and log in
2. Open your TechReserve project
3. Click **SQL Editor** in the left sidebar
4. Click **New Query**
5. Open the file `supabase_schema.sql` from your project root
6. Copy all the SQL content
7. Paste it into the SQL Editor
8. Click **Run** button
9. Wait for the query to complete (you should see "Success" message)

### Option 2: Using psql Command Line

```bash
# Connect to your Supabase database and run the schema
psql -h vwvoefadwrvsadrpceot.supabase.co -U postgres -d postgres < supabase_schema.sql
```

When prompted, enter your Supabase password: `Mojica!Sean17`

### Option 3: Using pgAdmin (if running locally)

1. Open pgAdmin at http://localhost:5050
2. Connect to your Supabase database
3. Right-click on your database → **Query Tool**
4. Open `supabase_schema.sql` and paste the content
5. Execute the query

## Verify the Schema

After running the SQL, verify all tables were created:

1. In Supabase dashboard, go to **Table Editor**
2. You should see all 8 tables listed:
   - accounts
   - venues
   - equipment
   - reservations
   - release_returns
   - notifications
   - audit_logs
   - tasks

## Table Relationships

```
accounts (1) ──────→ (many) reservations
accounts (1) ──────→ (many) notifications
accounts (1) ──────→ (many) audit_logs
accounts (1) ──────→ (many) tasks
accounts (1) ──────→ (many) release_returns

venues (1) ──────→ (many) reservations

reservations (1) ──────→ (many) release_returns
reservations (1) ──────→ (many) tasks
```

## Key Features

- **Foreign Keys**: Maintain data integrity between related tables
- **Indexes**: Optimized queries for common search patterns
- **JSONB Columns**: Flexible storage for equipment lists and documents
- **Timestamps**: Automatic tracking of creation and updates
- **Unique Constraints**: Prevent duplicate entries (email, reservation code)

## Next Steps

1. Run the schema creation SQL
2. Verify all tables appear in Supabase Table Editor
3. Your backend will automatically work with these tables
4. Start your application with `docker compose up -d`

## Troubleshooting

### "Table already exists" error
- The tables may already exist from a previous run
- This is safe to ignore, or drop tables first with:
```sql
DROP TABLE IF EXISTS tasks CASCADE;
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS release_returns CASCADE;
DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS equipment CASCADE;
DROP TABLE IF EXISTS venues CASCADE;
DROP TABLE IF EXISTS accounts CASCADE;
```

### Foreign key constraint errors
- Ensure tables are created in the correct order (accounts first)
- The schema file handles this automatically

### Connection refused
- Verify your Supabase host is correct: `vwvoefadwrvsadrpceot.supabase.co`
- Check your password is correct: `Mojica!Sean17`
- Ensure your Supabase project is active (not paused)
