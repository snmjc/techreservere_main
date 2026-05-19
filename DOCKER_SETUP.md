# TechReserve - Docker Desktop Setup Guide

This guide walks you through setting up the TechReserve development environment using Docker Desktop. The stack includes:

| Service      | Container Name          | Port          | Description                        |
|--------------|-------------------------|---------------|------------------------------------|
| **Database** | techreserve_database    | `5432`        | PostgreSQL 15 database             |
| **Frontend** | techreserve_frontend    | `5173`        | Vue.js (Vite) development server   |
| **Backend**  | techreserve_backend     | `8000`        | PHP 8.4 (Symfony) API server       |
| **PGAdmin**  | techreserve_pgadmin     | `5050`        | PostgreSQL database management UI  |

---

## Prerequisites

1. **Docker Desktop** installed and running  
   - Download: [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)
   - After installation, ensure Docker Desktop is **running** (check the system tray icon)

2. **Git** installed to clone the repository

---

## Step 1: Clone the Repository

```bash
git clone <repository-url>
cd techreservere_main
```

---

## Step 2: Create the `.env` File

Copy `example.env` to `.env` in the project root, then adjust values as needed:

```bash
copy example.env .env
```

Your `.env` should contain variables like:

```env
# === Ports ===
FRONTEND_EXTERNAL_PORT=5173
BACKEND_EXTERNAL_PORT=8000
PGADMIN_EXTERNAL_PORT=5050
POSTGRES_EXTERNAL_PORT=5432

# === PostgreSQL Database ===
POSTGRES_HOST=database
POSTGRES_DB=techreserve
POSTGRES_USER=techreserve_user
POSTGRES_PASSWORD=techreserve_pass

# === PGAdmin ===
PGADMIN_DEFAULT_EMAIL=admin@techreserve.com
PGADMIN_DEFAULT_PASSWORD=admin

# === Clerk Authentication ===
VITE_CLERK_PUBLISHABLE_KEY=pk_test_your_clerk_key_here
```

> **Note:** Replace `VITE_CLERK_PUBLISHABLE_KEY` with your actual Clerk publishable key from the [Clerk Dashboard](https://dashboard.clerk.com/).

---

## Step 3: Build and Start All Containers

Open a terminal in the project root and run:

```bash
docker-compose up --build
```

This will:
- Build the **Frontend** image (Node 20 + Vue/Vite)
- Build the **Backend** image (PHP 8.4 + Symfony)
- Pull the **PGAdmin** image (`dpage/pgadmin4:latest`)
- Start all three services

> **First run** may take several minutes as it downloads base images and installs dependencies.

---

## Step 4: Verify Services Are Running

Once the containers are up, open Docker Desktop and you should see the **TechReserve** stack with three containers running:

| Service   | URL                                  | Status  |
|-----------|--------------------------------------|---------|
| Frontend  | [http://localhost:5173](http://localhost:5173) | Running |
| Backend   | [http://localhost:8000](http://localhost:8000) | Running |
| PGAdmin   | [http://localhost:5050](http://localhost:5050) | Running |

### Verify in Docker Desktop:
1. Open **Docker Desktop**
2. Go to the **Containers** tab
3. You should see the `TechReserve` group with:
   - `techreserve_frontend` — Running
   - `techreserve_backend` — Running
   - `techreserve_pgadmin` — Running

---

## Step 5: Access the Services

### Frontend (Vue.js)
- **URL:** [http://localhost:5173](http://localhost:5173)
- The Vite dev server with hot-reload enabled
- Changes to files in `./frontend` are synced automatically

### Backend (Symfony API)
- **URL:** [http://localhost:8000](http://localhost:8000)
- PHP built-in server serving the Symfony application
- Changes to files in `./backend` are synced automatically

### PGAdmin (Database Management)
- **URL:** [http://localhost:5050](http://localhost:5050)
- **Email:** `admin@techreserve.com` (or your `PGADMIN_DEFAULT_EMAIL`)
- **Password:** `admin` (or your `PGADMIN_DEFAULT_PASSWORD`)

#### Connecting PGAdmin to the Database:
1. Open [http://localhost:5050](http://localhost:5050)
2. Right-click **Servers** → **Register** → **Server...**
3. Fill in:
   - **Name:** `TechReserve DB`
   - **Connection tab:**
     - **Host:** `database`
     - **Port:** `5432`
     - **Username:** `techreserve_user` (your `POSTGRES_USER`)
     - **Password:** `techreserve_pass` (your `POSTGRES_PASSWORD`)
     - **Database:** `techreserve` (your `POSTGRES_DB`)
4. Click **Save**

---

## Daily Development Workflow

### Start containers (with file watching):
```bash
docker-compose up --watch
```
This enables live file syncing — changes you make locally are reflected in the containers immediately.

### Stop containers:
```bash
docker-compose down
```

### Restart a specific service:
```bash
docker-compose restart frontend
docker-compose restart backend
docker-compose restart pgadmin
```

### View logs for a specific service:
```bash
docker-compose logs -f frontend
docker-compose logs -f backend
docker-compose logs -f pgadmin
```

---

## Installing Dependencies

### Frontend (npm packages):
```bash
docker-compose exec frontend npm install <package-name>
```

### Backend (Composer packages):
```bash
docker-compose exec backend composer require <package-name>
```

---

## Rebuilding After Major Changes

If you change `Dockerfile`, `package.json`, or `composer.json`:

```bash
docker-compose up --build
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Port already in use | Change the port in `.env` (e.g., `FRONTEND_EXTERNAL_PORT=5174`) |
| Frontend not loading | Run `docker-compose logs -f frontend` to check for errors |
| Backend 500 error | Run `docker-compose exec backend php bin/console cache:clear` |
| PGAdmin can't connect to DB | Ensure database container is running; use `database` as the host (not `localhost`) |
| Containers won't start | Run `docker-compose down -v` then `docker-compose up --build` |
| Slow file changes (Windows) | Ensure WSL 2 backend is enabled in Docker Desktop settings |

---

## Resetting Everything

To completely reset all containers, volumes, and start fresh:

```bash
docker-compose down -v
docker-compose up --build
```

> **Warning:** The `-v` flag removes all volumes including `pgadmin_data`, so any saved PGAdmin server configurations will be lost.
