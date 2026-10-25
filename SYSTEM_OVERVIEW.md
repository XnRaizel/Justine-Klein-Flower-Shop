# Bloom Express System Overview

This guide describes how the current Bloom Express application is organized, how a request moves through it, and which technologies provide its interface and backend.

## In brief

Bloom Express uses a React storefront built with Vite, a PHP/LavaLust API, and MySQL. PHP serves the React app shell and handles business logic and database access; React renders the shop and admin interfaces in the browser.

The browser app is a **React single-page application**. PHP remains responsible for server-side routing, authentication, validation, orders, and database access.

## Technologies

| Area | Technology | Purpose |
|---|---|---|
| Web framework | LavaLust, written in PHP | Routes requests, calls controllers and models, and renders views |
| Backend language | PHP | Implements page and admin logic and reads or changes data |
| Database | MySQL with PDO MySQL | Stores products, categories, carts, orders, inventory, and related records |
| Page templates | React, JSX, and HTML | Renders customer and admin interfaces in the browser |
| Frontend styling | CSS | Styles the customer and admin interfaces |
| Frontend build | Vite | Builds the React app into `public/assets/react/` |
| Development environment | Laragon or PHP's built-in web server | Runs the app locally; Laragon can also provide Apache and MySQL |

Run `npm ci` and `npm run build` from the project root after changing the frontend. The Docker build performs this step automatically.

## How a page request works

1. The browser requests a URL, such as `/shop` or `/admin/products`.
2. The web server sends the request to `public/index.php`, the application's public entry point.
3. LavaLust loads the application configuration and matches the URL against `app/config/routes.php`.
4. The matching controller calls one or more models when it needs data.
5. A model queries MySQL through the framework's database library.
6. `ReactController` serves the app shell and the browser loads the compiled React assets.
7. React calls same-origin endpoints in `app/config/routes.php`; `ApiController` validates requests and coordinates the models.

For example, `/shop` loads the React shop page, which requests the active products and categories from the catalog API.

## Frontend and interface

### Customer-facing pages

The customer-facing pages are React components in `src/main.jsx`. The homepage uses the original flower-shop logo and banner under `public/assets/images/`.

The main styles are:

- `src/style.css` — storefront and admin styling, compiled to `public/assets/react/assets/main.css`.

The React app handles catalog search and filtering, navigation, cart, account, checkout, and admin interactions. PHP endpoints remain the source of truth for the data and order operations.

### Staff and admin workspaces

Staff accounts sign in to `/staff`, with a focused workspace for orders, customer messages, deliveries, and inventory. Admin accounts sign in to `/admin`, where they have those same operational tools plus product management, staff accounts, and sales reports. Admins can designate an existing staff account as a rider from the Staff page. Riders sign in to `/rider` and can see only deliveries assigned to them. Customers cannot access these workspaces, and role restrictions are enforced by the API as well as navigation.

The admin product-management tools include server-side validation, CSRF checks on relevant POST actions, and create, edit, archive/restore, and guarded delete flows. Inventory is sorted by stock quantity, highest first.

### Deposits and payments

Checkout requires a 50% deposit. Customers may pay cash at the shop (staff confirms receipt in Admin → Orders) or use PayMongo Hosted Checkout when the server credentials and webhook secret are configured. The remaining balance is due in cash on delivery. Customers can cancel pending, confirmed, or preparing orders, but not orders already out for delivery or delivered. If they cancel after paying a deposit, staff handles any refund manually. PayMongo payment confirmation comes only from the signed webhook, not the browser redirect.

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
3. Build the frontend with `npm ci` and `npm run build`.
4. Open the project at the URL configured in `app/config/config.php` (currently `http://localhost:8000/`).

### With PHP's built-in server

From the project root, build the frontend and run the PHP server:

```powershell
npm ci
npm run build
php -S 127.0.0.1:8000 -t public public\index.php
```

Then visit `http://127.0.0.1:8000/`. The front controller routes app pages and APIs while allowing built frontend assets to be served directly. PHP's built-in server does not start MySQL. Start MySQL separately in Laragon if the app needs database access. Press `Ctrl+C` in the server terminal to stop the PHP server.

## Current implementation notes

- The storefront and admin pages are rendered by React; PHP serves the app shell and same-origin API.
- Catalog and accepted custom orders require a 50% deposit before staff can start preparation. Staff can confirm cash deposits, advance orders through preparation and dispatch, and assign a rider before dispatch. At handoff, the assigned rider enters the six-digit PIN shown to the signed-in customer in My Orders, records the recipient's name, and confirms any cash-on-delivery balance collected. This records the rider and delivery time and marks the order delivered. Staff, admins, and the customer can review the delivery confirmation; the PIN is hidden from staff and public order tracking and is cleared after successful confirmation.
- The remaining balance is collected in cash on delivery. The rider can record the collection during PIN-verified handoff; staff can also record a collection in Orders if it was received separately. Cancelled orders with paid deposits can have manual partial or full refunds recorded; recording a refund does not transfer money. Customers can review order status history when tracking an order.
- Order and delivery statuses use color-coded badges in customer, staff, admin, and rider views, with the status text retained for clarity.
- PayMongo checkout and its signed `checkout_session.payment.paid` webhook at `/webhooks/paymongo` remain optional and are not required for the cash workflow. Configure `PAYMONGO_SECRET_KEY` and `PAYMONGO_WEBHOOK_SECRET` privately only when beginning payment integration.
- Customer cart and order history are scoped to the signed-in account. Staff and admin order operations require a staff or admin account.
- Customer order history and staff/admin operational lists show the newest records first. Customers can hide and restore their own delivered or cancelled orders; staff/admin can archive and restore delivered or cancelled orders and closed deliveries. These controls only change list visibility, retain audit/payment/report data, and do not delete records. Riders see only their active assigned deliveries; a confirmed delivery disappears from their list.
- Delivery is currently free; map pinning and distance-based delivery fees remain deferred integration work.
- Existing databases need migrations applied in order, including `database/migrations/20261025_order_fulfillment.sql`, `database/migrations/20261025_rider_confirmations.sql`, and `database/migrations/20261025_order_archive_visibility.sql`, before using the corresponding order and delivery features.
- Database-backed pages require a running MySQL service and a populated database.

The order, delivery, cash payment, and manual refund steps are internal workflows; external payment, mapping, email, and SMS integrations remain unconfigured.
