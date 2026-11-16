# HOME TASTE FOODS – Deploy with Supabase + GitHub + Render

This project runs as a PHP 8.1 Apache Docker web service and connects to Supabase PostgreSQL using PDO. The existing application interface and PHP source are unchanged.

## 1. Create the Supabase database

1. Create a project at https://supabase.com.
2. Open **SQL Editor** → **New query**.
3. Open `DATABASE FILE/supabase_schema.sql` from this project, copy its full contents, paste into the SQL Editor, and run it.
4. Do **not** run `DATABASE FILE/rposystem.sql`: that is the original MySQL dump, not the PostgreSQL schema for Supabase. Do not run `upgrade_to_ordering.sql` for a fresh Supabase database.
5. The schema creates the application tables and default Admin/Cashier accounts. Products can be added from the Admin interface after deployment.

## 2. Copy the Supabase connection string

1. In Supabase, open **Connect** (or **Project Settings → Database**).
2. Select **Session pooler** and copy its URI connection string.
3. Use the session pooler (normally port `5432`) for this app. The PHP compatibility layer uses PDO prepared statements; Supabase transaction pooler mode (`6543`) does not support all session/prepared-statement behavior this application relies on.
4. Replace the password placeholder with your database password. If the password contains reserved URL characters, URL-encode them. Keep this connection string private.

## 3. Push the project to GitHub

Extract the ZIP. Open a terminal in the `RestaurantPOS` folder (the folder containing `Dockerfile` and `render.yaml`), then run:

```bash
git init
git add .
git commit -m "Deploy HOME TASTE FOODS POS"
```

Create an empty GitHub repository, then run (replace the URL with your repository URL):

```bash
git remote add origin https://github.com/YOUR_USERNAME/home-taste-foods.git
git branch -M main
git push -u origin main
```

## 4. Deploy on Render

1. Sign in at https://render.com.
2. Choose **New → Blueprint** and connect the GitHub repository. Render will read `render.yaml`. Alternatively choose **New → Web Service**, connect the repository, and select **Docker** as the runtime.
3. Confirm the Dockerfile path is `./Dockerfile` and the Docker build context is `.`.
4. In the Render service environment settings, add:

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `DATABASE_URL` | The full Supabase Session Pooler URI copied above |

5. Deploy and wait for the build to finish. Open the Render-provided `onrender.com` URL.

## 5. First login

| Role | Email | Password |
|---|---|---|
| Admin | `admin@mail.com` | `codeastro.com` |
| Cashier | `cashier@mail.com` | `codeastro.com` |

Change these default passwords after the first login.

## Important notes

- Never put the Supabase database password in PHP source code or commit it to GitHub. Set it only as the Render `DATABASE_URL` secret/environment variable.
- Render free web services may spin down after inactivity, so the first request after a quiet period can take longer.
- Files written to the container (for example uploaded product images) are not guaranteed to persist across redeploys on a free web service. For permanent product images, use Supabase Storage or another persistent object store.
- If deployment fails, open Render → your service → **Logs** and share the error text. Do not share your database password or full connection string.
