# Bloom Express System Overview

This guide describes how the current Bloom Express application is organized, how a request moves through it, and which technologies provide its interface and backend.

## In brief

Bloom Express is a PHP web application built on the LavaLust MVC framework. PHP handles page requests, business logic, and database access. MySQL stores the shop's data. The browser displays PHP-rendered HTML, CSS styles it, and Vue 3 adds selected interactive features.

This is a **PHP-rendered, multi-page application**, not a Vue single-page application. Vue does not replace PHP or the existing routes.

## Technologies

| Area | Technology | Purpose |
|---|---|---|
| Web framework | LavaLust, written in PHP | Routes requests, calls controllers and models, and renders views |
| Backend language | PHP | Implements page and admin logic and reads or changes data |
| Database | MySQL with PDO MySQL | Stores products, categories, carts, orders, inventory, and related records |
| Page templates | PHP views and HTML | Generates the pages on the server |
| Frontend styling | CSS | Styles the customer and admin interfaces |
| Frontend interactivity | Vue 3.5.13 | Adds the shop filters, responsive menus, and form-submit feedback |
| Development environment | Laragon or PHP's built-in web server | Runs the app locally; Laragon can also provide Apache and MySQL |

Vue and the application JavaScript are included in the shared customer and admin layouts. Vue is loaded from a CDN, so Vue-powered features need an internet connection. The server-rendered pages do not depend on Vue to display their main content. There is no Node/npm build step in the current setup.

## How a page request works

1. The browser requests a URL, such as `/shop` or `/admin/products`.
2. The web server sends the request to `public/index.php`, the application's public entry point.
3. LavaLust loads the application configuration and matches the URL against `app/config/routes.php`.
4. The matching controller calls one or more models when it needs data.
5. A model queries MySQL through the framework's database library.
6. The controller passes data to a PHP view. The view renders HTML on the server.
7. The browser loads the shared CSS and JavaScript. Vue enhances supported interactions on the page.

For example, `/shop` maps to `ShopController::index`. That controller gets active products and categories from the models, then renders `app/views/pages/shop.php`. The shop view includes the product data as JSON for Vue's search and stock filters, while retaining a PHP-rendered fallback catalog.

## Frontend and interface

### Customer-facing pages

The customer-facing pages use the shared layout in `app/views/layouts/header.php` and `app/views/layouts/footer.php`. The homepage, shop, product, cart, checkout, account, and informational pages are PHP templates under `app/views/pages/`.

The main styles are:

- `public/assets/css/style.css` — existing shared styles and responsive layout rules.
- `public/assets/css/redesign.css` — the visual refresh layered on top of the original styles.

Vue features are initialized by `public/assets/js/app.js`:

- Shop catalog search by product or category.
- An in-stock-only shop filter.
- A responsive customer navigation menu.
- A mobile menu for the admin sidebar.
- Disabling a valid form's submit button after submission to reduce accidental double-submits.

The homepage's featured bouquets and occasion cards are still populated by PHP before Vue starts. The redesign changes presentation; it does not change the underlying shop routes or product logic.

### Admin interface

Most admin pages use `app/views/layouts/admin_header.php` and `app/views/layouts/admin_footer.php`; their PHP view files are under `app/views/admin/`. The separate admin login template is `app/views/admin/login.php`.

The admin area includes dashboard summaries, product management, orders, deliveries, inventory, staff, and reports. Product management includes server-side validation, CSRF checks on relevant POST actions, and create, edit, archive/restore, and guarded delete flows.

### What Vue does not do

Vue currently enhances parts of the existing interface. It does not own all page rendering, replace the controllers, or provide the database connection. PHP remains responsible for producing the page data and processing routed requests.

## Backend structure

| Location | Responsibility |
|---|---|
| `public/index.php` | Web entry point; loads LavaLust |
| `app/config/routes.php` | Maps customer and admin URLs to controller methods |
| `app/controllers/` | Handles requests, coordinates models, and chooses views |
| `app/models/` | Reads and updates application data |
| `app/views/pages/` | Customer page templates |
| `app/views/admin/` | Admin page templates |
| `app/views/layouts/` | Shared customer and admin page shells |
| `app/config/` | Application, database, route, and framework configuration |
| `app/migrations/` | Framework migration definitions |
| `database/bloom_express.sql` | Database schema and sample data for the shop |
| `scheme/` | LavaLust framework code |
| `runtime/` | Runtime files such as logs and cache |

### Request and feature examples

- `HomeController` loads categories and featured products for the homepage.
- `ShopController` loads active products, filters by occasion, and loads product details.
- `CartController` reads cart items and calculates a subtotal for display.
- `OrderController` looks up an order number for tracking and loads order history.
- `AdminController` gathers dashboard data and handles admin product-management actions.

Routes are listed in `app/config/routes.php`. Customer routes include `/`, `/shop`, `/shop/occasion/{slug}`, `/product/{id}`, `/cart`, `/checkout`, `/track`, and `/account/orders`. Admin routes are grouped under `/admin`.

## Database

The default connection is configured in `app/config/database.php`:

- Driver: MySQL
- Host: `localhost`
- Port: `3306`
- Database: `bloom_express`
- Username: `root`
- Password: empty by default

These are Laragon-style local defaults. Environment variables such as `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` can override them.

The SQL file `database/bloom_express.sql` creates the database and its tables and provides sample data. Tables include roles, users, categories, products, add-ons, carts, cart items, orders, order items, deliveries, payments, stock logs, and notifications.

To use database-backed pages, start MySQL and import the SQL file into MySQL. A database connection error generally means the MySQL service is stopped, the configured connection values do not match the local server, or the database has not been imported.

## Run locally

### With Laragon

1. Open Laragon and start Apache and MySQL.
2. Create/import the `bloom_express` database from `database/bloom_express.sql` if it has not been set up yet.
3. Open the project at the URL configured in `app/config/config.php` (currently `http://localhost:8000/`).

### With PHP's built-in server

From the project root, run:

```powershell
php -S 127.0.0.1:8000 -t public
```

Then visit `http://127.0.0.1:8000/`. PHP's built-in server serves the application, but it does not start MySQL. Start MySQL separately in Laragon if the app needs database access. Press `Ctrl+C` in the server terminal to stop the PHP server.

## Current implementation notes

- Customer pages and most admin pages are PHP-rendered HTML, with Vue used for selected browser interactions.
- The login and registration customer pages are currently presentation templates; their forms do not submit to authentication handlers.
- The checkout page is a placeholder: it does not submit an order or process a payment.
- Cart display and order history use the application's current demo-customer helper rather than a complete customer login flow.
- The checkout template notes that map pinning, delivery fees, and PayMongo payments are later-phase work.
- Database-backed pages require a running MySQL service and a populated database.

These notes describe the current code, not a guarantee that every displayed workflow is complete.
