# Deploy Bloom Express on Render

This project can run on Render as a Docker web service with a separate MySQL service. Render is a good fit because the project already uses Apache/PHP in Docker and requires MySQL.

> **Demo/staging only for now.** The current customer login and registration forms do not authenticate users, checkout does not place orders or process payments, and admin routes do not have authentication middleware configured. Do not use this deployment for real customer data, real orders, payments, or public administration until those workflows are implemented and reviewed.

## What the deployment configuration does

- `render.yaml` defines the PHP web service and prompts for database connection values without storing credentials in the repository.
- `Dockerfile` installs PHP's MySQL extension, serves the app from `public/`, enables Apache rewrites, and makes only `runtime/` writable by Apache.
- `docker-entrypoint.sh` configures Apache to listen on Render's `PORT` (default `10000`).
- The application uses `RENDER_EXTERNAL_URL` for absolute links when `APP_BASE_URL` is not explicitly set.
- Render generates `APP_KEY`; production mode enables secure, HTTP-only cookies and error logging.

## 1. Put the project in a GitHub repository

Render deploys from a Git repository. This workspace is not currently a Git repository, so it must first be uploaded to GitHub:

1. Create a **private** GitHub repository.
2. In VS Code, use **Source Control → Initialize Repository**, then publish/push the project to that private repository.
3. Keep `.env` and all real credentials out of Git. `.gitignore` already excludes `.env`; `.dockerignore` keeps it out of the Docker image too.
4. Do not commit production database passwords, payment keys, or real customer data.

## 2. Create the MySQL service

Render's MySQL example runs as a private service. Follow Render's [MySQL deployment guide](https://render.com/docs/deploy-mysql):

1. Use the `render-examples/mysql` template to create a **private service**.
2. Set `MYSQL_DATABASE` to `bloom_express`.
3. Set `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_ROOT_PASSWORD` to strong values. Keep them private.
4. Attach a persistent disk mounted at **`/var/lib/mysql`**. Without persistent storage, database data can be lost when the service is replaced.
5. Deploy the service and note its private hostname (for example, `mysql-your-service`) and port `3306`. Use the private hostname, not a public URL.

The MySQL service and web service should be in the same Render region so they can communicate over Render's private network. The MySQL service and persistent disk may incur charges; check Render's current pricing before deploying.

## 3. Import the starter database

The file `database/bloom_express.sql` creates the schema and sample records. It includes `DROP TABLE IF EXISTS` statements, so import it only into a new, empty database. Importing it again will erase and recreate those tables.

One option is to temporarily deploy [Adminer on Render](https://render.com/docs/deploy-adminer), connect it to the private MySQL hostname, and import `database/bloom_express.sql` from your computer. Because the SQL file creates the database, use the MySQL root account for this one-time import; do not use root for the web app. Adminer is publicly reachable, so keep it only for setup and delete the Adminer service immediately after importing. Never reuse demo credentials for real accounts.

## 4. Deploy the web service

1. In Render, choose **New → Blueprint** and connect the private GitHub repository.
2. Render reads the root `render.yaml`. Review the proposed `bloom-express` Docker web service and deploy it.
3. When prompted for unsynced variables, provide:
   - `DB_HOST`: the private MySQL service hostname (host only; no `http://` and no `:3306`)
   - `DB_USER`: the `MYSQL_USER` configured for MySQL
   - `DB_PASSWORD`: the matching `MYSQL_PASSWORD`
4. The Blueprint sets `DB_NAME=bloom_express`, `DB_PORT=3306`, `DB_DRIVER=mysql`, and `APP_ENV=production`. Render generates a random `APP_KEY`.
5. Wait for the build and deploy to finish. The app's `GET /` health check loads the homepage, which also verifies the database connection.
6. Open the `onrender.com` URL shown on the service page.

The app's database configuration already reads `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` from the environment. `RENDER_EXTERNAL_URL` supplies the production base URL automatically; set `APP_BASE_URL` only if you need to override it.

## Updating the site

Push changes to the repository branch connected to Render. Render will rebuild the Docker image and deploy it. Do not re-import the starter SQL file to update a live database.

## Before a real public launch

- Implement and enforce customer and admin authentication and authorization.
- Implement and test registration, login, cart updates, order creation, and payment processing.
- Replace demo accounts/passwords and sample data; use unique, strong credentials.
- Review CSRF protections, session settings, error handling, privacy requirements, backups, and database access.
- Confirm current Render service and disk pricing and configure regular database backups.

## References

- [Render Docker deploys](https://render.com/docs/docker)
- [Render web services and port binding](https://render.com/docs/web-services#port-binding)
- [Render environment variables](https://render.com/docs/configure-environment-variables)
- [Render MySQL deployment guide](https://render.com/docs/deploy-mysql)
