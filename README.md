# 🌸 Dar El-Jamila

> **Enterprise-grade luxury fashion e-commerce platform** — built on Laravel, engineered for bilingual (Arabic/English) markets, and hardened for real production operation on constrained shared hosting.

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.8-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/License-MIT-4CAF50?style=for-the-badge" alt="MIT License">
  <img src="https://img.shields.io/badge/Tests-317%20passing-2ea44f?style=for-the-badge" alt="317 tests passing">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=flat-square&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/MySQL-Ready-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/i18n-AR%20%2F%20EN-orange?style=flat-square" alt="Arabic / English">
  <img src="https://img.shields.io/badge/RTL-Supported-blueviolet?style=flat-square" alt="RTL Supported">
</p>

---

> [!NOTE]
> This README is generated from a direct audit of the current codebase — every feature listed below corresponds to real, working code (controllers, models, jobs, migrations, or views) that exists in this repository today, not aspirational documentation.

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Technology Stack](#-technology-stack)
- [Feature Highlights](#-feature-highlights)
- [Storefront Features](#-storefront-features)
- [Admin Dashboard](#-admin-dashboard)
- [Roles & Permissions](#-roles--permissions)
- [Authentication System](#-authentication-system)
- [Product Catalog & Variants](#-product-catalog--variants)
- [Orders, Checkout & Invoicing](#-orders-checkout--invoicing)
- [Email, Notifications & Background Jobs](#-email-notifications--background-jobs)
- [Localization (Arabic / English)](#-localization-arabic--english)
- [Architecture](#-architecture)
- [Project Structure](#-project-structure)
- [Installation](#-installation)
- [Production Deployment (Shared Hosting)](#-production-deployment-shared-hosting)
- [Testing](#-testing)
- [Screenshots](#-screenshots)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🏢 Overview

**Dar El-Jamila** is a full-featured, bilingual luxury fashion e-commerce platform. It is not a starter kit — it is a complete storefront + back-office system covering catalog management, variant-based inventory, cart abandonment recovery, coupon/discount logic, customer relationship tooling, PDF invoicing, a granular role/permission system, and a production-hardened email/queue pipeline, all built natively on Laravel with no SaaS commerce dependency (no Shopify, no WooCommerce, no third-party cart engine).

The application is designed and actively operated on **constrained shared hosting** (no persistent daemon, no `proc_open`), which shaped several architectural decisions documented throughout this README — most notably the invoice-generation engine and the queue-worker strategy.

---

## 🧰 Technology Stack

| Layer | Technology | Notes |
|---|---|---|
| **Framework** | Laravel `^13.8` | PHP `^8.3` required |
| **Frontend rendering** | Blade | Server-rendered, component-based (`resources/views/components`) |
| **Styling** | Tailwind CSS 3 (`@tailwindcss/forms`) | Custom `dj-*` design system classes layered on top |
| **Interactivity** | Alpine.js 3 | Dropdowns, wizards, drag-and-drop UI, live previews |
| **Build tooling** | Vite 8 + `laravel-vite-plugin` | Multi-entry build: `app.js`, `admin.js`, `admin-products.js`, `app.css` |
| **Charts** | Chart.js | Admin dashboard analytics |
| **Drag & drop** | SortableJS | Product image/media reordering |
| **Database** | MySQL (SQLite for local/testing) | 60 migrations, fully versioned schema |
| **Auth** | Laravel's native auth scaffolding (Breeze-derived) + custom OTP layer | Session-based, no SPA/token auth |
| **Social login** | Laravel Socialite `^5.28` | Provider-agnostic (Google enabled by default) |
| **Roles & permissions** | Spatie `laravel-permission` `^8.3` | 4-tier role hierarchy, ~75 granular permission slugs |
| **PDF invoices** | `mpdf/mpdf` `^8.3` (primary) · `barryvdh/laravel-dompdf` `^3.1` (rollback) | No headless-Chrome/`proc_open` dependency in production |
| **Queues** | Database queue driver | Cron-driven `queue:work --stop-when-empty`, no persistent worker |
| **Scheduler** | Laravel Scheduler (`routes/console.php`) | Drives cart reminders & scheduled product publishing |
| **Testing** | PHPUnit `^12.5` | 317 automated tests, Feature + Unit + Console coverage |
| **Code style** | Laravel Pint | `composer.json` dev dependency |

---

## ✨ Feature Highlights

| | |
|---|---|
| 🎨 **Luxury Fashion Theme** | Bespoke storefront design, not a generic template |
| 🌍 **Full Arabic & English** | Every user-facing string translated, RTL-aware layouts, locale-aware PDFs and emails |
| 🔐 **4-Tier Auth & Access Control** | Super Admin / Admin / Employee / Customer, with per-employee permission grants |
| 🔑 **Google Sign-In** | Socialite-based OAuth, provider-agnostic architecture |
| 📱 **OTP Email Verification** | Time-limited one-time-passcode flow, independent of Laravel's native verified-email flow |
| 📦 **Variant-Based Product Catalog** | Options (Size/Color/etc.) → Values → generated Variants, each with its own price/stock/SKU |
| 🛒 **Cart & Checkout** | Guest + authenticated checkout, coupon engine, structured shipping methods, geolocation capture |
| 💌 **Abandoned Cart Recovery** | Automatic detection + throttled reminder emails with configurable cadence |
| ❤️ **Wishlist** | Save-for-later with one-click move-to-cart |
| ⭐ **Product Reviews** | Moderated (pending/approved/rejected), image attachments, "helpful" voting |
| 📝 **Blog + Moderated Comments** | Full blog engine with a comment moderation workflow mirroring the reviews system |
| 📄 **PDF Invoices** | Bilingual, RTL-correct PDF generation with a resilient status lifecycle (pending → processing → generated → emailed → failed) |
| 📧 **19 Branded Transactional Emails** | Order, invoice, review, comment, cart, newsletter, admin-alert, and auth emails — all luxury-tier HTML templates |
| 🔔 **In-App Notifications** | Database-backed notifications for both customers and admins |
| ⚡ **Production-Safe Queue Design** | Idempotent, retry-safe jobs built for cron-driven `queue:work`, not a persistent daemon |
| 🏷️ **Coupons & Discounts** | Percentage/fixed coupons with usage limits and expiry |
| 🖼️ **Media Management** | Drag-and-drop image reordering, cover-image selection, per-variant imagery |
| 🧾 **Full Audit Logging** | `ActivityLog` records every sensitive admin mutation |
| 📊 **Admin Analytics Dashboard** | Chart.js-powered sales/order/customer insights |
| 🧑‍💼 **Customer CRM Tools** | Admin notes, cart visibility, wishlist visibility, disable/enable accounts |
| 🎯 **Demo Data Engine** | One command (`demo:import`) seeds a full realistic catalog with real photography |

---

## 🛍️ Storefront Features

- **Home, Shop, Product Detail, About, Services, Contact, Blog** — all fully bilingual, responsive public pages (`app/Http/Controllers/HomeController.php`, `ShopController.php`, `BlogController.php`, `PageController.php`, `ContactController.php`).
- **Shopping Cart** (`CartController.php`) — session/DB-backed cart with per-line quantity updates, coupon apply/remove, and live stock validation.
- **Checkout** (`CheckoutController.php`) — structured shipping method selection, optional customer geolocation capture, stock is locked (`lockForUpdate`) and decremented atomically inside a DB transaction to prevent overselling under concurrent checkouts.
- **Wishlist** (`WishlistController.php`) — add/remove/move-to-cart, guarded against out-of-stock moves.
- **Reviews** (`ReviewController.php`, `Account/ReviewController.php`) — customers submit reviews with optional images; reviews are moderated before appearing publicly; "mark helpful" voting is public.
- **Blog Comments** (`BlogCommentController.php`, `Account/BlogCommentController.php`) — same moderated-content pattern as reviews, reusable and consistent across both features.
- **Newsletter** (`NewsletterController.php`) — subscription capture with a branded welcome email.
- **Customer Account Area** (`Account/*`) — order history, invoice download, address book, review history, comment history.
- **Signed Invoice Downloads** — invoice download links use Laravel signed URLs, guests included, without exposing the underlying storage path.

---

## 🖥️ Admin Dashboard

A dedicated `/admin` back office, built with its own layout, sidebar, and CSS design system (`dj-admin-*`), fully independent from the storefront theme.

| Module | Capabilities |
|---|---|
| **Dashboard** | Sales/order/customer analytics via Chart.js |
| **Products** | Full CRUD, autosave, SEO fields, image drag-and-drop, bulk actions, creation wizard, status workflow (draft/scheduled/published) |
| **Product Options & Variants** | Define options (e.g. Size, Color) → values → auto-generate variant matrix, bulk edit/delete variants |
| **Categories** | CRUD with SEO fields |
| **Coupons** | CRUD, usage tracking |
| **Blog** | Post CRUD with author/category/SEO metadata |
| **Orders** | List/filter, status timeline updates (with automatic stock restock on cancellation), invoice access |
| **Customers** | Profile view, order history, cart visibility, wishlist visibility, admin notes, disable/enable, manual reminder send |
| **Carts** | Live abandoned-cart visibility, bulk/individual reminder dispatch |
| **Reviews** | Approve / reject / feature / unfeature / delete |
| **Blog Comments** | Approve / reject / delete |
| **Contact Messages** | Inbox with read-state tracking |
| **Newsletter** | Subscriber list + CSV export |
| **Settings** | Site-wide configurable settings (key/value store) |
| **Notifications** | In-app admin notification center |
| **Users, Roles & Permissions** | Super-Admin-only: create/edit staff accounts, assign roles, grant per-employee permission overrides, force-logout, password reset |
| **Email Preview** | Renders any transactional email template in-browser for design QA |

---

## 🔐 Roles & Permissions

A four-tier hierarchy built on `spatie/laravel-permission`, with **~75 granular permission slugs** grouped by domain (`config/permission_groups.php`) and preset permission bundles for common employee profiles (`config/permission_presets.php`):

```
super_admin   →  full, unconditional access to everything (including Users/Roles/Permissions screens)
admin         →  full operational access (mirrors super_admin, minus staff-account management)
employee      →  access is 100% configurable per account via granted permission checkboxes
customer      →  storefront-only account
```

- **`SuperAdminMiddleware`** gates `/admin/users`, `/admin/roles`, `/admin/permissions`.
- **`AdminMiddleware`** gates the general `/admin/*` area (admin/super_admin/employee may enter; visibility inside is then narrowed by permission).
- **`EnsureAdminPermission`** (`admin.permission:<slug>`) gates individual non-resource routes (Settings, Newsletter, Contact Messages, Notifications, Cart reminders).
- **Policies** (`ProductPolicy`, `CategoryPolicy`, `CouponPolicy`, `ReviewPolicy`, `BlogPostPolicy`, `BlogCommentPolicy`, `OrderPolicy`, `AddressPolicy`) enforce per-ability checks for every model action.
- Every sensitive mutation (role change, permission grant, account disable, force-logout) is written to `ActivityLog`.
- Self-protection guards prevent an admin from demoting/deleting their own account or deleting the last remaining Super Admin.

---

## 🔑 Authentication System

| Feature | Implementation |
|---|---|
| Registration | `RegisteredUserController` |
| Login / Logout | `AuthenticatedSessionController` |
| Forgot / Reset Password | `PasswordResetLinkController`, `NewPasswordController` — branded reset email via `ResetPassword::toMailUsing()` |
| Password confirmation | `ConfirmablePasswordController` |
| Email verification (native Laravel) | `EmailVerificationPromptController`, `VerifyEmailController` |
| **OTP email verification** | `OtpVerificationController` + `OtpService` + `EmailVerificationOtp` model — a separate, time-limited one-time-passcode layer with resend throttling |
| **Google Sign-In** | `SocialAuthController` + `SocialAuthenticator` service — provider-agnostic (`OAUTH_PROVIDERS` env-driven), find-or-link-or-create account matching, login-alert notification on new-device sign-in |
| Login alert | `LoginAlertNotification` / `LoginAlertMail` — notifies a user of a new sign-in |
| Route protection | `RequireVerifiedIfAuthenticated` middleware ensures checkout can't be reached with an unverified session |
| Rate limiting | `throttle` middleware applied to register/login/checkout/OTP endpoints |

---

## 🧵 Product Catalog & Variants

The catalog runs on a proper **Options → Values → Variants** engine, not a single flat "size" field:

- `Product` → has many `ProductOption` (e.g. "Size", "Color")
- `ProductOption` → has many `ProductOptionValue` (e.g. "M", "Red"), each optionally carrying its own image
- `ProductVariant` → the Cartesian combination of selected option values, each with independent **price, stock, and SKU**
- `VariantSkuGenerator` service — deterministic SKU generation
- `ProductDuplicator` / `ProductDeleter` services — safe clone/delete with cascading relations
- Bulk variant actions (`ProductVariantBulkActionController`) and bulk product actions (`ProductBulkActionController`)
- Legacy `ProductSize` model retained alongside the variant engine, with a dedicated migration command (`products:migrate-sizes-to-variants`) to backfill old data into the new structure without data loss
- `StockAlertService` — low-stock / out-of-stock threshold detection, triggers `ProductLowStock` / `ProductOutOfStock` notifications and emails
- Product status workflow: draft → scheduled → published, driven by `products:publish-scheduled` (cron/scheduler)

---

## 🧾 Orders, Checkout & Invoicing

- **Order placement** is fully transactional: stock rows are row-locked (`lockForUpdate`) and decremented inside `DB::transaction()`, with a `RuntimeException` short-circuit on insufficient stock — no overselling under concurrent checkout.
- **Structured shipping**: shipping methods are real DB-backed records (`ShippingMethod`) with fee, delivery-time range, and an always-available fallback so checkout never dead-ends on an empty shipping list.
- **Order status workflow** (`OrderStatusHistory`) with automatic stock restock on cancellation.
- **Invoice generation** (`InvoicePdfService`) is the single source of truth for PDF creation:
  - Primary engine: **mPDF** (`config('invoice.pdf_engine')`, default `mpdf`) — chosen because dompdf was found to reverse Arabic (RTL) text specifically in the production shared-hosting environment.
  - **dompdf** remains available as a manual, config-driven rollback engine only — never an automatic runtime fallback.
  - Invoices carry an explicit status lifecycle — `pending → processing → generated → emailed`, with `failed` as a terminal state and a stored `failed_reason` — so a failure is always visible and never leaves the customer looking at an infinite "still preparing" message.
  - PDF files are written atomically (temp file → validate non-empty + `%PDF` signature → atomic move) and are **never** stored in a public directory; downloads are served through signed, policy-gated routes only.
- **Invoice delivery** (`GenerateAndSendInvoice` job) is a production-hardened queue job:
  - Dispatched `->afterCommit()`, guaranteeing the order is durably saved before any PDF/email work begins.
  - `$tries = 3`, `$timeout = 45`, `$backoff = [60, 300, 900]`.
  - Fully idempotent — a retry never regenerates a valid existing PDF and never re-sends an already-delivered invoice email.
  - Genuinely throws on failure (nothing is silently swallowed), so Laravel's own retry/backoff/`failed_jobs` machinery applies; a `failed()` hook persists the final failure state onto the invoice record.
- **Customer email resolution** always goes through `Order::resolveCustomerEmail()` — never falls back to an admin/support address.
- **Payments** (`Payment` model) — payment method + payment status tracked per order, with dedicated `PaymentSuccessMail` / `PaymentFailedMail`.

---

## 📬 Email, Notifications & Background Jobs

### Mailables (19)
Order confirmation, invoice-ready, order status update, order cancellation, payment success/failure, OTP verification, login alert, abandoned-cart reminder, wishlist reminder, product back-in-stock, product low/out-of-stock alerts, new-review-submitted, review-status-update, new-blog-comment-submitted, blog-comment-status-update, new-contact-message, new-customer-registered, newsletter welcome, admin-user-welcome — every one rebranded to a consistent luxury-tier HTML design (product cards, order summary cards, branded headers).

### Notifications (18, database + mail channels)
Customer-facing: `OrderPlaced`, `OrderStatusUpdated`, `OrderCancelled`, `ReviewStatusUpdated`, `BlogCommentStatusUpdated`, `AbandonedCartReminderNotification`, `LoginAlertNotification`, `OtpVerificationNotification`, `ProductLowStock`, `ProductOutOfStock`.
Admin-facing (deliberately separate classes from the customer ones — never the same notification sent to both audiences): `NewOrderPlaced`, `NewCustomerRegistered`, `NewProductReviewSubmitted`, `NewBlogCommentSubmitted`, `NewContactMessage`, `NewsletterSubscribed`, `CartAbandonedAdminNotification`, `CartConvertedAdminNotification`, `CartReminderFailedAdminNotification`, `InvoiceGenerationFailedAdminNotification`.

### Jobs & Scheduled Commands
| Job / Command | Purpose | Trigger |
|---|---|---|
| `GenerateAndSendInvoice` | PDF generation + delivery, see above | Dispatched post-checkout |
| `SendAbandonedCartReminderJob` | Per-customer reminder dispatch | Queued by `carts:send-reminders` |
| `carts:send-reminders` | Flip inactive carts to abandoned, dispatch reminders (throttled: configurable interval, max reminder count, high-value-cart detection) | Scheduler, every 30 minutes |
| `products:publish-scheduled` | Publish products whose `scheduled_publish_at` has passed | Scheduler, every 5 minutes |

> [!IMPORTANT]
> **Production runs on shared hosting with no persistent daemon and `proc_open` disabled.** The queue worker (`queue:work database --stop-when-empty`) runs via a **direct hosting-provider cron job every minute**, not through `Schedule::command('queue:work')` — Laravel's Scheduler executes commands through Symfony Process, which requires `proc_open`. This distinction is load-bearing for anyone deploying this app to similarly constrained hosting.

---

## 🌍 Localization (Arabic / English)

- **24 translation domains** per locale (`lang/en/*.php`, `lang/ar/*.php`): `admin`, `auth`, `blog`, `blog_comments`, `carts`, `categories`, `coupons`, `customers`, `emails`, `general`, `invoice`, `messages`, `newsletter`, `orders`, `pagination`, `passwords`, `permissions`, `product_options`, `products`, `reviews`, `roles`, `settings`, `users`, `validation` — plus `en.json` / `ar.json` for inline strings.
- **`SetLocale` middleware** applied globally on the `web` group; `/lang/{locale}` route for instant switching.
- **RTL-aware Blade templates** — layouts, invoice PDFs, and emails all render correctly mirrored in Arabic, including explicit `dir="ltr"` overrides for numeric/tabular content inside RTL documents.
- **Locale-aware everything downstream**: invoices, transactional emails, and order snapshots all render in the locale the order was placed in (`orders.locale` column) — not the admin's current session locale.
- Product, category, and blog content are fully bilingual at the schema level (`name_ar`/`name_en` column pairs, not a single-locale field with a translation package bolted on).

---

## 🏗️ Architecture

```
Request → Route (web.php / admin.php / auth.php)
        → Middleware (auth, verified, admin, admin.permission, super_admin, locale)
        → Form Request (validation)
        → Controller
        → Policy (authorization)
        → Service Layer (business logic: CartService, InvoicePdfService, SocialAuthenticator, StockAlertService, ...)
        → Eloquent Model
        → Database
```

- **Controller layer** — thin; delegates business logic to the Service layer rather than embedding it inline.
- **Service layer** (`app/Services`) — the authoritative implementation for cross-cutting concerns: cart logic, invoice PDF generation, OTP issuance, social-auth account resolution, stock alerts, image uploads, SKU generation, demo-data import.
- **Job layer** (`app/Jobs`) — queued, retry-safe, idempotent units of work, designed around the database queue driver and cron-driven `queue:work`, not a persistent worker process.
- **Mail layer** (`app/Mail`) — one Mailable per transactional scenario, each a `ShouldQueue` implementation queued through the same database queue.
- **Notification layer** (`app/Notifications`) — database + mail dual-channel, deliberately separated into distinct customer-facing and admin-facing classes.
- **Policy layer** (`app/Policies`) — one policy per authorizable model, each with a `before()` super-admin/admin shortcut plus granular per-ability permission checks for employees.
- **Middleware layer** — role/permission gating (`AdminMiddleware`, `SuperAdminMiddleware`, `EnsureAdminPermission`), locale resolution (`SetLocale`), verified-session enforcement (`RequireVerifiedIfAuthenticated`).
- **Storage layer** — `local` disk for invoices (signed-route access only, never public), public disk for catalog/media imagery.
- **Localization layer** — middleware-resolved locale, per-model bilingual columns, per-order locale snapshot, fully translated UI and transactional content.

---

## 📁 Project Structure

```
app/
├── Console/Commands/        # demo:import, app:make-super-admin, carts:send-reminders, products:publish-scheduled, ...
├── Http/
│   ├── Controllers/         # Storefront controllers (root level)
│   │   ├── Admin/           # Back-office controllers (products, orders, customers, roles, settings, ...)
│   │   ├── Account/         # Customer self-service controllers (orders, reviews, addresses, comments)
│   │   └── Auth/            # Login, register, OTP, password reset, social auth
│   ├── Middleware/          # Admin/SuperAdmin/Permission gating, locale resolution
│   └── Requests/            # Form Request validation classes
├── Jobs/                    # GenerateAndSendInvoice, SendAbandonedCartReminderJob
├── Mail/                    # 19 branded transactional Mailables
├── Models/                  # 31 Eloquent models
├── Notifications/           # 18 customer/admin database+mail notifications
├── Policies/                # Per-model authorization
├── Providers/                # AppServiceProvider
├── Services/                 # Business-logic layer
└── helpers.php               # Global helper functions (autoloaded via composer.json "files")

config/
├── admin_sidebar.php         # Dynamic, permission-aware admin nav
├── invoice.php                # PDF engine selection (mpdf default, dompdf rollback)
├── cart.php                   # Abandoned-cart thresholds & reminder cadence
├── permission_groups.php      # ~75 permission slugs grouped for the Permissions UI
├── permission_presets.php     # One-click employee permission bundles
└── primary_super_admin.php    # Self-healing primary Super Admin account config

database/
├── migrations/                # 60 versioned migrations
├── seeders/                   # Role/Permission seeding, demo catalog seeders, shipping/settings defaults
└── factories/

lang/
├── en/                         # 24 translation files
└── ar/                         # 24 matching translation files (full parity)

resources/views/
├── admin/                      # Back-office Blade views (own layout + design system)
├── account/                    # Customer self-service views
├── auth/                       # Standalone-page auth flow (login, register, OTP, password reset)
├── emails/                     # Branded transactional email templates
├── invoices/                   # PDF invoice templates (dompdf + mpdf variants)
├── shop/, cart/, checkout/, blog/, wishlist/, pages/, contact/
└── components/                 # Shared Blade components

routes/
├── web.php                     # Public storefront + authenticated customer routes
├── admin.php                   # /admin back-office routes (permission-gated)
├── auth.php                    # Authentication routes
└── console.php                 # Scheduled command definitions

tests/
├── Feature/                    # End-to-end feature coverage (Admin/, Auth/, Console/ subfolders)
└── Unit/
```

---

## 🚀 Installation

### Requirements
- PHP `^8.3` with standard extensions (`pdo`, `mbstring`, `intl`, `gd`)
- Composer 2.x
- Node.js 18+ / npm
- MySQL 8+ (or SQLite for local development)

### Local Setup

```bash
# 1. Clone the repository
git clone <repository-url> dar-el-jamila
cd dar-el-jamila

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Environment configuration
cp .env.example .env
php artisan key:generate

# 5. Configure your database connection in .env, then run migrations
php artisan migrate

# 6. Seed roles, permissions, the Primary Super Admin, shipping methods,
#    settings, categories, products, coupons, and blog posts
php artisan db:seed

# 7. (Optional) Populate a full realistic demo catalog with real photography
#    Requires a free key from https://www.pexels.com/api/ set as PEXELS_API_KEY
php artisan demo:import

# 8. Build frontend assets
npm run build          # production build
# or
npm run dev             # dev server with HMR

# 9. Serve the app, the queue worker, and logs together
composer dev
```

`composer dev` runs `php artisan serve`, `php artisan queue:listen`, `php artisan pail` (live logs), and `npm run dev` concurrently — the complete local development stack in one command.

### Creating the first Super Admin

```bash
php artisan app:make-super-admin you@example.com --name="Your Name"
```

Safe to re-run against an existing account — promotes it to `super_admin` rather than duplicating a user. This is the sanctioned path in every environment, including production; there is no hardcoded seeder credential.

---

## ☁️ Production Deployment (Shared Hosting)

This application is actively deployed on **shared hosting with no persistent process and `proc_open` disabled** — a common constraint on budget/business-tier hosting providers. The deployment model below reflects that reality rather than assuming a VPS/container environment.

```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover
mkdir -p storage/app/mpdf-temp && chmod -R 775 storage/app/mpdf-temp
chmod -R 775 storage bootstrap/cache
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

> [!WARNING]
> Never run `migrate:fresh`, `db:wipe`, `queue:flush`, or `chmod -R 777` against production. Never rely on `Schedule::command('queue:work')` — configure the queue worker as a **direct cron job** instead.

### Required cron entries (configure in your hosting control panel — not `schedule:run`)

```cron
* * * * *  php /path/to/artisan queue:work database --stop-when-empty --max-time=50 --timeout=45 --tries=3
*/5 * * * * php /path/to/artisan products:publish-scheduled
*/30 * * * * php /path/to/artisan carts:send-reminders
```

The queue worker uses `--stop-when-empty` and runs once per minute rather than as a persistent daemon — the only pattern compatible with hosts that disable long-running PHP processes.

---

## 🧪 Testing

```bash
php artisan test
```

**317 tests / 1,031 assertions**, covering Feature (storefront, checkout, admin CRUD, auth, roles/permissions, invoice generation, email content), Console (scheduled commands), and Unit layers. `composer test` clears config cache first to avoid stale-config false negatives.

---

## 📸 Screenshots

> Screenshots to be added — replace the placeholders below with actual captures before publishing.

| | |
|---|---|
| **Home** | `docs/screenshots/home.png` |
| **Shop / Product Listing** | `docs/screenshots/shop.png` |
| **Product Details** | `docs/screenshots/product-details.png` |
| **Shopping Cart** | `docs/screenshots/cart.png` |
| **Checkout** | `docs/screenshots/checkout.png` |
| **Customer Dashboard** | `docs/screenshots/account-dashboard.png` |
| **Admin Dashboard** | `docs/screenshots/admin-dashboard.png` |
| **Invoice PDF (Arabic)** | `docs/screenshots/invoice-pdf-ar.png` |
| **Login** | `docs/screenshots/login.png` |
| **Register** | `docs/screenshots/register.png` |
| **Google Login** | `docs/screenshots/google-login.png` |
| **OTP Verification** | `docs/screenshots/otp-verify.png` |
| **Wishlist** | `docs/screenshots/wishlist.png` |
| **Blog** | `docs/screenshots/blog.png` |
| **Order History** | `docs/screenshots/orders.png` |
| **Admin Roles & Permissions** | `docs/screenshots/roles-permissions.png` |
| **Settings** | `docs/screenshots/settings.png` |

```markdown
![Home](docs/screenshots/home.png)
![Admin Dashboard](docs/screenshots/admin-dashboard.png)
```

---

## 🗺️ Roadmap

- [ ] Online payment gateway integration (card/wallet processors)
- [ ] Native mobile application (iOS/Android)
- [ ] Multi-vendor marketplace mode
- [ ] Customer loyalty & rewards program
- [ ] AI-driven product recommendations
- [ ] Advanced analytics & reporting dashboard
- [ ] Multi-warehouse inventory management
- [ ] ERP / accounting system integration
- [ ] Public REST API for headless storefronts

---

## 🤝 Contributing

Contributions are welcome. Please follow this workflow:

1. Fork the repository and create a feature branch (`git checkout -b feature/your-feature`).
2. Run `composer install && npm install` and confirm `php artisan test` passes before your changes.
3. Follow the existing code style — run `./vendor/bin/pint` before committing.
4. Keep changes scoped: one feature or fix per pull request.
5. Ensure both `lang/en` and `lang/ar` are updated for any new user-facing string — this project maintains full bilingual parity.
6. Add or update tests for any behavioral change; the test suite must not regress.
7. Open a pull request with a clear description of what changed and why.

> [!TIP]
> Before touching invoice generation, the queue system, or any Mailable/Notification, read the inline comments in `app/Jobs/GenerateAndSendInvoice.php` and `app/Services/InvoicePdfService.php` — the design choices there (idempotency, throw-on-failure, `afterCommit()` dispatch) resolve specific production incidents and are not accidental.

---

## 📄 License

This project is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2026 Dar El-Jamila

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

<p align="center">Built with Laravel · Crafted for a bilingual, luxury retail experience</p>
