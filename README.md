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
- [Features](#-feature-highlights)
- [Feature Matrix](#-feature-matrix)
- [Storefront Features](#-storefront-features)
- [Admin Dashboard](#-admin-dashboard)
- [Roles & Permissions](#-roles--permissions)
- [Authentication System](#-authentication-system)
- [Product Catalog & Variants](#-product-catalog--variants)
- [Orders, Checkout & Invoicing](#-orders-checkout--invoicing)
- [Email, Notifications & Background Jobs](#-email-notifications--background-jobs)
- [Queue System](#-queue-system)
- [Cron Jobs](#-cron-jobs)
- [Localization (Arabic / English)](#-localization-arabic--english)
- [Architecture](#-architecture)
- [Folder Structure](#-project-structure)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Environment Variables](#-environment-variables)
- [Deployment](#-production-deployment-shared-hosting)
- [Testing](#-testing)
- [Troubleshooting](#-troubleshooting)
- [Screenshots](#-screenshots)
- [Changelog](#-changelog)
- [Roadmap](#-roadmap)
- [Credits](#-credits)
- [Security Notes](#-security-notes)
- [Performance](#-performance)
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

## 🧩 Feature Matrix

A module-by-module implementation status — every ✅ below corresponds to real controllers, models, jobs, or views in this repository, not planned work (planned work is tracked separately in [Roadmap](#-roadmap)).

| Module | Status | Description |
|---|:---:|---|
| Authentication | ✅ | Email/password login, registration, logout, password confirmation |
| Google Sign-In | ✅ | Socialite-based OAuth, provider-agnostic (extensible beyond Google) |
| OTP Email Verification | ✅ | Time-limited one-time passcode with resend throttling |
| Roles & Permissions | ✅ | Super Admin / Admin / Employee / Customer, ~75 granular permission slugs |
| Product Management | ✅ | Full CRUD, autosave, SEO fields, draft/scheduled/published workflow |
| Product Variants | ✅ | Options → Values → Variants engine (size, color, custom attributes) |
| Categories | ✅ | CRUD with SEO metadata |
| Shopping Cart | ✅ | Persistent, session/DB-backed, live stock validation |
| Wishlist | ✅ | Save-for-later with one-click move-to-cart |
| Coupons | ✅ | Percentage/fixed discounts, usage limits, expiry |
| Checkout | ✅ | Transactional stock locking, structured shipping, geolocation capture |
| Orders | ✅ | Status timeline, automatic stock restock on cancellation |
| Payments | ✅ | Payment method/status tracking, success/failure emails |
| PDF Invoices | ✅ | Bilingual, RTL-correct, atomic generation with a resilient status lifecycle |
| Invoice Emails | ✅ | Automatic, idempotent, retry-safe delivery via the queue |
| Product Reviews | ✅ | Moderated, image attachments, "helpful" voting |
| Blog | ✅ | Full post CRUD with author/category/SEO metadata |
| Blog Comments | ✅ | Moderated comment workflow mirroring reviews |
| Newsletter | ✅ | Subscription capture + branded welcome email |
| Contact Form | ✅ | Admin inbox with read-state tracking |
| Abandoned Cart Recovery | ✅ | Auto-detection + throttled, capped reminder emails |
| Queue System | ✅ | Database queue driver, cron-driven `queue:work` |
| Scheduler | ✅ | Scheduled product publishing + cart reminders |
| Localization | ✅ | Full Arabic & English parity across 24 translation domains |
| RTL Support | ✅ | Mirrored layouts, invoices, and emails |
| Responsive Design | ✅ | Mobile / tablet / desktop |
| Admin Dashboard | ✅ | Full back-office panel with Chart.js analytics |
| Customer Dashboard | ✅ | Order history, invoices, reviews, comments, addresses |
| Activity Logging | ✅ | Audit trail for sensitive admin actions |
| Demo Data Import | ✅ | One-command realistic catalog seeding with real photography |
| Signed Invoice Downloads | ✅ | Signed-URL access, never a public storage path |
| Admin Email Preview | ✅ | In-browser transactional template QA tool |
| Payment Gateway Integration | 🔜 | Planned — see [Roadmap](#-roadmap) |
| Mobile Application | 🔜 | Planned — see [Roadmap](#-roadmap) |
| Multi-Vendor Marketplace | 🔜 | Planned — see [Roadmap](#-roadmap) |

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

## ⚡ Queue System

The queue is `QUEUE_CONNECTION=database` — the `jobs` and `failed_jobs` tables, no Redis/SQS dependency. It is deliberately designed to be operated by a **cron-driven, non-persistent worker** rather than a long-running daemon, because the production host disables `proc_open` and forbids background processes.

**Design principles applied to every queued job:**

- **Dispatched `->afterCommit()`** — a job never fires until the DB transaction that created its data has actually committed (see `CheckoutController::store()` dispatching `GenerateAndSendInvoice`).
- **Bounded retries with backoff** — `GenerateAndSendInvoice` uses `$tries = 3`, `$timeout = 45`, `$backoff = [60, 300, 900]`; failures are never retried forever, and `$timeout` is kept comfortably under the cron worker's own time budget (see [Cron Jobs](#-cron-jobs)) so one slow job can't starve every job queued behind it.
- **Idempotent by construction** — a retried or manually redispatched job never regenerates an already-valid invoice PDF and never re-sends an already-delivered email; state is checked (`Invoice::isDownloadable()`, `status === STATUS_EMAILED`) before any side effect runs.
- **Never silently swallows failure** — a genuine failure is allowed to throw, so Laravel's own retry/backoff and `failed_jobs` bookkeeping engage; a `failed(Throwable $e)` hook persists the terminal failure state onto the relevant record (e.g. the `invoices.status`/`failed_reason` columns) so failure is visible to both operators (`queue:failed`) and the customer-facing UI — never an infinite "still processing" message.

| Job | Purpose | Queue behavior |
|---|---|---|
| `GenerateAndSendInvoice` | Generates the PDF invoice and emails it to the customer | 3 tries, 45s timeout, exponential-ish backoff, fully idempotent |
| `SendAbandonedCartReminderJob` | Sends one reminder email/notification for one abandoned cart | Dispatched per-cart by `carts:send-reminders`, respects `CART_MAX_REMINDERS` |

### Operating the queue

```bash
# Drain everything currently queued, then stop — never runs forever
php artisan queue:work database --stop-when-empty --timeout=45 --tries=3

# Inspect failures without guessing
php artisan queue:failed

# Retry a specific failed job by its ID (never queue:flush without reviewing first)
php artisan queue:retry <id>
```

> [!WARNING]
> Never run `Schedule::command('queue:work')->everyMinute()` on hosts where `proc_open` is disabled — Laravel's Scheduler shells out through Symfony Process to invoke it, which fails outright. Always run the worker as a **direct cron entry** instead (see [Cron Jobs](#-cron-jobs)).

---

## ⏰ Cron Jobs

Three scheduled tasks keep the application running without a persistent process. On hosts that support `proc_open`, all three can be driven by Laravel's Scheduler (`routes/console.php` already defines the latter two); on constrained shared hosting, **all three must instead be configured as direct cron jobs** in the hosting control panel.

| Task | Frequency | Purpose | Command | Expected Result |
|---|---|---|---|---|
| **Queue Worker** | Every minute | Processes anything currently in `jobs` (invoice generation/email, cart reminders) | `php artisan queue:work database --stop-when-empty --max-time=50 --timeout=45 --tries=3` | Picks up pending jobs, runs each once, exits when the queue is empty — never lingers as a daemon |
| **Scheduled Product Publishing** | Every 5 minutes | Flips any product whose `scheduled_publish_at` has passed to published | `php artisan products:publish-scheduled` | Products scheduled for the future go live automatically, without an admin manually clicking "publish" |
| **Abandoned Cart Reminders** | Every 30 minutes | Flips inactive carts to `abandoned`, dispatches throttled reminder emails | `php artisan carts:send-reminders` | Customers who left items in their cart receive up to `CART_MAX_REMINDERS` reminders, spaced by `CART_REMINDER_INTERVAL_HOURS` |

```cron
* * * * *   php /path/to/artisan queue:work database --stop-when-empty --max-time=50 --timeout=45 --tries=3
*/5 * * * *  php /path/to/artisan products:publish-scheduled
*/30 * * * * php /path/to/artisan carts:send-reminders
```

> [!NOTE]
> `--stop-when-empty` is what makes the once-a-minute cron pattern safe: the worker processes whatever is waiting and then exits cleanly, rather than staying resident and colliding with the next minute's cron invocation.

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

## ⚙️ Configuration

Application behavior is tuned through dedicated config files rather than scattered magic numbers — each one maps to a real `.env` override:

| Config file | Controls |
|---|---|
| `config/invoice.php` | PDF engine selection (`mpdf` default, `dompdf` manual rollback) and the mPDF temp directory |
| `config/cart.php` | Abandoned-cart detection window, reminder cadence, max reminders, high-value threshold, cart expiry |
| `config/permission_groups.php` | The ~75 permission slugs, grouped for the admin Permissions UI |
| `config/permission_presets.php` | One-click permission bundles for common employee profiles |
| `config/primary_super_admin.php` | The email of the protected Primary Super Admin account (immune to demotion/deletion/disabling by anyone) |
| `config/admin_sidebar.php` | Dynamic, permission-aware admin navigation — a sidebar item only renders if the logged-in staff member holds its permission |
| `config/services.php` | OAuth provider whitelist (`oauth_providers`), Google credentials, Browsershot paths (legacy/optional), Pexels API key |
| `config/queue.php`, `config/cache.php`, `config/session.php`, `config/database.php` | Standard Laravel connection config — all driven by `.env`, none hardcoded |

> [!TIP]
> `InvoicePdfService` is the **only** class that reads `config('invoice.pdf_engine')` — no controller, job, or Mailable should ever instantiate a PDF renderer directly. Keep it that way when extending invoice generation.

---

## 🔑 Environment Variables

Copy `.env.example` to `.env` and set at minimum `APP_KEY`, your database credentials, and a real `MAIL_MAILER`. Every variable below is read somewhere in the codebase — nothing here is speculative.

| Variable | Purpose | Default |
|---|---|---|
| `APP_NAME`, `APP_ENV`, `APP_URL`, `APP_DEBUG` | Core app identity — `APP_DEBUG` must be `false` in production | — |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Default UI locale (this app ships defaulting to `ar`) and fallback | `ar` / `en` |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database connection | `sqlite` locally |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | All three default to `database` — no Redis/Memcached required | `database` |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP transport for all 19 Mailables | `log` (writes to the log instead of sending) |
| `OAUTH_PROVIDERS` | Comma-separated whitelist of enabled Socialite providers | `google` |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_CALLBACK_URL` | Google OAuth credentials — **must** be a public HTTPS URL in production, never `localhost` | — |
| `INVOICE_PDF_ENGINE` | `mpdf` (default, RTL-correct) or `dompdf` (manual rollback only) | `mpdf` |
| `CART_ABANDONED_AFTER_MINUTES`, `CART_REMINDER_INTERVAL_HOURS`, `CART_MAX_REMINDERS`, `CART_HIGH_VALUE_THRESHOLD`, `CART_EXPIRES_AFTER_DAYS` | Abandoned-cart recovery tuning | `60` / `4` / `3` / `2000` / `30` |
| `PRIMARY_SUPER_ADMIN_EMAIL` | The one account `PrimarySuperAdminSeeder` guarantees always exists with full access | *(set your own — never leave the code default in production)* |
| `PEXELS_API_KEY` | Only needed to run `php artisan demo:import` (downloads real demo catalog photos) | — |
| `BROWSERSHOT_CHROME_PATH`, `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_NPM_BINARY` | Legacy/optional — only relevant if the headless-Chrome PDF path is deliberately re-enabled; **leave unset in production**, since shared hosting cannot run `proc_open`/Puppeteer | — |
| `AWS_*` | Only needed if `FILESYSTEM_DISK` is switched to `s3` | — |

> [!WARNING]
> `.env` must never be committed. It is already covered by `.gitignore` — verify that with `git check-ignore .env` before your first commit on a new clone.

---

## ☁️ Production Deployment (Shared Hosting)

This application is actively deployed on **Hostinger Business Shared Hosting**, and more broadly on any host with **no persistent process and `proc_open` disabled** — a common constraint on budget/business-tier shared hosting. The deployment model below reflects that reality rather than assuming a VPS/container environment with root access.

### Requirements

| Requirement | Notes |
|---|---|
| PHP `^8.3` | Confirm the real CLI binary path — Hostinger often aliases it (`which php`, `php -v`), don't assume `/usr/bin/php` without checking |
| Composer 2.x | Usually available via hPanel's "Composer" tool, or upload `composer.phar` |
| Node.js 18+ / npm | Only needed **at build time** to produce `public/build/*` — assets are built once and committed/uploaded, Node is not required at runtime |
| MySQL 8+ | Create the database and a dedicated user via hPanel before first deploy |
| SSH access | Required for artisan commands (`migrate`, cache commands, permissions) — enable it in hPanel if not already |
| Git | Either `git clone`/`git pull` over SSH, or upload a build archive if Git isn't available on the plan |

### Hostinger-Specific Constraints

> [!IMPORTANT]
> Two facts drive most of the deployment/queue architecture on this host:
> 1. **No persistent daemon is allowed** — a `queue:work` process left running will eventually be killed, so the queue worker must run as a short-lived, cron-triggered process (`--stop-when-empty`), never `supervisor`/`systemd`.
> 2. **`proc_open` is disabled** — this breaks Symfony Process, which Laravel's Scheduler (`schedule:run`) uses internally to execute scheduled Artisan commands. As a direct consequence, **none of this app's scheduled work can go through `schedule:run` on this host** — every recurring task (queue worker, product publishing, cart reminders) must be its **own direct cron entry** instead. See [Cron Jobs](#-cron-jobs).

### Deployment Steps

```bash
# 1. Pull the latest code
git pull origin main

# 2. Install PHP dependencies (production-only, no dev tooling, no post-install scripts
#    until package:discover is run explicitly below)
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover --ansi

# 3. Build frontend assets — run locally/in CI and upload public/build/, or run
#    directly on the server if Node is available there
npm install
npm run build

# 4. Environment — copy .env once, then edit in place; never overwrite it on
#    subsequent deploys
cp .env.example .env   # first deploy only
php artisan key:generate   # first deploy only

# 5. Storage & permissions — mPDF needs its own writable temp directory;
#    775, never 777
mkdir -p storage/app/mpdf-temp
chmod -R 775 storage bootstrap/cache storage/app/mpdf-temp

# 6. Public storage symlink (product/category/blog images served from
#    storage/app/public) — only needed once per environment
php artisan storage:link

# 7. Database — additive only, never destructive
php artisan migrate --force

# 8. Rebuild all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Pick up code changes in any job already mid-flight
php artisan queue:restart
```

> [!WARNING]
> Never run `migrate:fresh`, `db:wipe`, `queue:flush`, or `chmod -R 777` against production. Never rely on `Schedule::command('queue:work')`/`schedule:run` for the queue worker on this host — configure it as a **direct cron job** instead (below).

### Production Commands Reference

```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## 🧪 Testing

```bash
php artisan test
```

**317 tests / 1,031 assertions**, covering Feature (storefront, checkout, admin CRUD, auth, roles/permissions, invoice generation, email content), Console (scheduled commands), and Unit layers. `composer test` clears config cache first to avoid stale-config false negatives.

---

## 🩺 Troubleshooting

Real, previously-encountered failure modes and their actual root causes — not generic Laravel FAQ filler.

### Google Login redirects to `localhost` in production

- **Symptoms**: Clicking "Sign in with Google" in production redirects back to `http://localhost` (or a local dev URL) and fails.
- **Root cause**: `GOOGLE_CALLBACK_URL` (or `APP_URL`) in the production `.env` still holds a local-development value. `SocialAuthController::redirect()` deliberately **detects and refuses** this case rather than sending the user into a flow guaranteed to strand them — this is a config problem, not a code bug.
- **Solution**: Set `GOOGLE_CALLBACK_URL` in production `.env` to the real public HTTPS callback (`https://yourdomain.com/auth/google/callback`), matching exactly what's registered in the Google Cloud Console OAuth client, then `php artisan config:cache`.

### "Your sign-in session expired" / Google session state mismatch

- **Symptoms**: Google login fails immediately after the redirect back from Google, even with a correctly configured callback URL.
- **Root cause**: `InvalidStateException` from Socialite — the session cookie present when the OAuth redirect started didn't come back on the callback request. Almost always a session cookie domain/`SESSION_DOMAIN`/HTTPS-detection mismatch (e.g. the app is reachable at both `www.` and bare-domain URLs, or sits behind a proxy that isn't setting `X-Forwarded-Proto`).
- **Solution**: Ensure `SESSION_DOMAIN` matches your actual domain (or is left `null` for a single-domain setup), `SESSION_SECURE_COOKIE`/HTTPS detection is correct behind any reverse proxy, and users reach the app via one canonical URL, not both `www` and non-`www`.

### Queue jobs stuck / never processing

- **Symptoms**: Orders placed but invoices never generate, cart reminders never send; `jobs` table keeps growing.
- **Root cause**: On shared hosting, there is no persistent worker — if the cron entry for `queue:work` was never configured (or was configured via `schedule:run`, which fails silently where `proc_open` is disabled), nothing ever drains the queue.
- **Solution**: Confirm a direct cron entry exists for `php artisan queue:work database --stop-when-empty --max-time=50 --timeout=45 --tries=3` running every minute (see [Cron Jobs](#-cron-jobs)) — **not** `schedule:run`. Manually drain once with the same command to confirm it works, then check `php artisan queue:failed`.

### Failed jobs / `failed_jobs` table has entries

- **Symptoms**: `php artisan queue:failed` lists entries for `GenerateAndSendInvoice` or `SendAbandonedCartReminderJob`.
- **Root cause**: Real failures now surface here by design (see [Queue System](#-queue-system)) — this table existing with entries is the system working correctly, not a bug in itself. Common underlying causes: unwritable `storage/app/mpdf-temp`, an unresolvable customer email, or an SMTP auth failure.
- **Solution**: Read the `exception`/`failed_reason` column, fix the underlying cause, then `php artisan queue:retry <id>`. Never `queue:flush` without reading the failures first.

### Invoice PDF not generating / Arabic text renders reversed or garbled

- **Symptoms**: Invoice stuck showing "still being prepared," or an Arabic invoice PDF shows mirrored/reversed characters (e.g. `ةروتاف` instead of `فاتورة`).
- **Root cause**: dompdf has a known RTL bidi-rendering bug that reverses Arabic text — this reproduced specifically in the production shared-hosting environment even though the same template rendered correctly on local dev.
- **Solution**: Confirm `INVOICE_PDF_ENGINE` is unset or explicitly `mpdf` (the default) — `mpdf` renders Arabic RTL correctly and needs no `proc_open`/headless Chrome. `dompdf` should only ever be used as a temporary, deliberate rollback.

### mPDF / storage permission errors

- **Symptoms**: Invoice generation fails with a "temp directory not writable" or similar filesystem error.
- **Root cause**: `storage/app/mpdf-temp` doesn't exist yet, or isn't writable by the PHP process user.
- **Solution**: `mkdir -p storage/app/mpdf-temp && chmod -R 775 storage/app/mpdf-temp storage bootstrap/cache`. Never `chmod -R 777` — it's unnecessary and a real security downgrade.

### Missing `public/storage` symlink (product images 404)

- **Symptoms**: Product/category/blog images 404 in the browser despite existing in `storage/app/public`.
- **Root cause**: `php artisan storage:link` was never run on this environment (it doesn't run automatically, and the symlink isn't part of the Git repo).
- **Solution**: `php artisan storage:link` once per environment. If the host disallows symlinks entirely, serve the `public` disk through a signed/proxied route instead.

### Mail not sending / SMTP errors

- **Symptoms**: No transactional emails arrive; `MAIL_MAILER=log` silently writes to the log instead (this is a *correct*, intentional local-dev default, not a bug).
- **Root cause in production**: Usually incorrect `MAIL_HOST`/`MAIL_PORT`/`MAIL_USERNAME`/`MAIL_PASSWORD`, or the mail provider requiring a specific `MAIL_ENCRYPTION`/port combination (465 vs 587).
- **Solution**: Confirm `MAIL_MAILER` is `smtp` (not `log`) in production, verify credentials with your mail provider's exact host/port/encryption requirements, then `php artisan config:cache` and send a real test (e.g. trigger the newsletter welcome email).

### Vite build assets missing (`Unable to locate file in Vite manifest`)

- **Symptoms**: A blank/broken page in production, with a Vite manifest error in the logs.
- **Root cause**: `npm run build` was never run for this deploy, or `public/build/` wasn't uploaded/committed alongside the PHP code.
- **Solution**: Run `npm install && npm run build` (locally, in CI, or on the server if Node is available there) and ensure `public/build/` is present on the server before serving traffic.

### Cache/config issues after deploy (old code still running)

- **Symptoms**: Code changes deployed via `git pull` don't appear to take effect.
- **Root cause**: `config:cache`/`route:cache`/`view:cache` from a previous deploy are stale, or weren't rebuilt after the new pull.
- **Solution**: Always run `php artisan optimize:clear` **before** rebuilding caches on every deploy — this is already the last step in [Deployment](#-production-deployment-shared-hosting), skipping it is the most common cause of "my fix isn't showing up."

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

## 📝 Changelog

This project has no formal version tags — development has proceeded as a continuous stream of dated feature milestones, reconstructed here from the migration history and commit log rather than invented semantic versions.

### 2026-07-06 — Foundation
- Initial schema: users, roles/permissions tables, categories, products, product images & sizes, shipping methods
- Orders, order items, order status histories, payments, invoices, coupons, wishlists
- Contact messages, newsletter subscribers, addresses, settings, activity log

### 2026-07-07 — Catalog & Auth Refinement
- Product image URLs, order payment method tracking, stock tracking on orders
- Email verification OTP layer introduced (`email_verification_otps`)

### 2026-07-08 — Reviews, Blog Comments, Product Variants, Cart Tracking
- Full review system upgrade (moderation workflow, review images, "helpful" voting)
- Blog comment system, mirroring the review moderation pattern
- Product Options → Option Values → Variants engine introduced (schema + admin builder)
- Cart & cart item persistence, cart reminders, customer notes
- Product status workflow (draft / scheduled / published)

### 2026-07-09 — SEO & Variant Defaults
- SEO fields added to products
- Variant default selection on products

### 2026-07-10 — Social Authentication & Roles
- Google Sign-In (Socialite-based, provider-agnostic) with login-alert notifications
- Full 4-tier Roles & Permissions system: Super Admin / Admin / Employee / Customer, ~75 permission slugs

### 2026-07-11 — Demo Catalog Engine
- `demo:import` command: realistic catalog seeding (brands, categories, collections, products, blog posts) with real downloaded photography
- Cart reminder source tracking

### 2026-07-12 — Checkout Hardening & Localization Audit
- Order locale snapshotting (invoices/emails render in the locale the order was placed in)
- Full Arabic/English localization audit across admin tables, dates, and notification snapshots

### 2026-07-13 — Catalog Expansion & Invoice Engine Migration
- Brands, Collections, and Banners introduced
- Category and blog post SEO metadata
- Google login / OTP / email routing production fixes
- **Invoice engine migrated from dompdf to mPDF** — root-caused a production-only Arabic RTL text-reversal bug specific to the shared-hosting environment
- Invoice PDF layout fixes (footer overflow, gradient-background rendering, RTL table collapse)

### 2026-07-14 — Payment Standardization & Queue Hardening
- Standardized `payment_status` + `payment_method` across orders
- Structured shipping schema + optional customer geolocation capture
- **Invoice status lifecycle** (`pending → processing → generated → emailed → failed`) added to eliminate the "stuck on preparing forever" failure mode
- Queue job hardening: `->afterCommit()` dispatch, bounded retries with backoff, full idempotency, genuine failure propagation into `failed_jobs`
- Enterprise-grade project documentation (this README)

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

## 🙏 Credits

Built on genuinely used, production-verified dependencies only — every package below appears in `composer.json` or `package.json` and is actively exercised by the codebase.

**Backend**

| Package | Role |
|---|---|
| [Laravel](https://laravel.com) `^13.8` | Application framework |
| [PHP](https://php.net) `^8.3` | Runtime |
| [Laravel Socialite](https://laravel.com/docs/socialite) `^5.28` | Google OAuth (provider-agnostic) |
| [Spatie `laravel-permission`](https://spatie.be/docs/laravel-permission) `^8.3` | Role & permission engine |
| [mPDF](https://mpdf.github.io/) `^8.3` | Primary invoice PDF engine (Arabic RTL-correct) |
| [Barryvdh `laravel-dompdf`](https://github.com/barryvdh/laravel-dompdf) `^3.1` | Manual invoice-engine rollback |
| [Laravel Tinker](https://github.com/laravel/tinker) `^3.0` | REPL / one-off production diagnostics |
| [Laravel Pint](https://laravel.com/docs/pint) | Code style |
| [Laravel Pail](https://github.com/laravel/pail) | Live log tailing during local dev |
| [PHPUnit](https://phpunit.de/) `^12.5` | Test suite (317 tests) |
| [Mockery](https://github.com/mockery/mockery) | Test doubles |

**Frontend**

| Package | Role |
|---|---|
| [Vite](https://vitejs.dev) `^8.0` + `laravel-vite-plugin` | Asset bundling |
| [Tailwind CSS](https://tailwindcss.com) `^3` + `@tailwindcss/forms` | Utility-first styling, layered under the custom `dj-*` design system |
| [Alpine.js](https://alpinejs.dev) `^3.4` | Lightweight interactivity (dropdowns, wizards, live previews) |
| [Chart.js](https://www.chartjs.org/) | Admin dashboard analytics |
| [SortableJS](https://sortablejs.github.io/Sortable/) | Drag-and-drop product image/media reordering |

**Data & Fonts**

- [Pexels API](https://www.pexels.com/api/) — real, royalty-free photography for the demo catalog (`demo:import`)
- [Cairo](https://fonts.google.com/specimen/Cairo) typeface — used in the Arabic invoice PDF template for correct, legible RTL rendering

---

## 🔒 Security Notes

- **Never commit `.env`** — it holds database credentials, `APP_KEY`, SMTP credentials, and OAuth secrets. Already `.gitignore`d; verify with `git check-ignore .env` on any new clone.
- **Rotate OAuth secrets** if `GOOGLE_CLIENT_SECRET` is ever exposed (a leaked commit, a shared screenshot, a support ticket) — regenerate it in Google Cloud Console and update `.env` immediately.
- **The queue worker and cron entries run as the hosting account's PHP user** — never widen file permissions beyond `775` to work around a permission error; find and fix the actual ownership/group mismatch instead.
- **SMTP credentials** belong only in `.env`, never in a Mailable/Notification class or a config file committed to Git.
- **Invoices are never stored in a public directory** — they live on the `local` disk and are served exclusively through signed, policy-gated routes (`OrderController::invoice()`), so a guessed URL can never leak another customer's invoice.
- **The Primary Super Admin account** (`PRIMARY_SUPER_ADMIN_EMAIL`) is deliberately protected against demotion, permission removal, disabling, and deletion by anyone — including other Super Admins — enforced server-side in `UserController`/`UpdateUserRequest`, not just hidden in the UI.
- **Every sensitive admin mutation is audit-logged** (`ActivityLog`) — role changes, permission grants, account disable/enable, force-logout.
- **Production caches must be rebuilt after every deploy** (`config:cache`, `route:cache`, `view:cache`) — a stale `config:cache` can silently keep serving an old `.env` value, including a rotated secret.
- **Rate limiting** (`throttle` middleware) is applied to registration, login, checkout, and OTP endpoints to blunt credential-stuffing and brute-force attempts.

---

## ⚡ Performance

- **Queue-offloaded work** — PDF generation, email delivery, and cart-reminder dispatch never block the request/response cycle; they're queued and processed by the cron-driven worker (see [Queue System](#-queue-system)).
- **Production cache layers** — `config:cache`, `route:cache`, and `view:cache` are mandatory deploy steps, eliminating per-request filesystem scanning and Blade recompilation.
- **Row-level locking, not table locking** — checkout stock decrements use `lockForUpdate()` scoped to the specific `ProductSize`/variant row, so concurrent checkouts for *different* products never contend with each other.
- **Eager loading** — relationship access in hot paths (order details, invoice generation, product listings) uses `loadMissing()`/`with()` rather than triggering N+1 lazy loads.
- **Vite production builds** — hashed, cache-busted, minified assets (`npm run build`) replace the unbundled dev-server output in production.
- **Database-backed cache/session/queue** — no extra infrastructure (Redis/Memcached) required to get real caching and a real queue, which matters directly on resource-capped shared hosting.
- **Atomic, validated file writes** — invoice PDFs are written to a temp path, validated (non-empty, `%PDF` signature), then atomically moved into place — never a partially-written file served to a customer.
- **Idempotent jobs avoid duplicate work** — a retried invoice job skips PDF regeneration entirely if a valid file already exists, rather than re-rendering and re-uploading unnecessarily.

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

## ❤️ Built with Laravel

Designed and developed with care for scalability, maintainability, and performance — engineered to run reliably even on constrained, budget shared hosting, without compromising on production-grade correctness.

<p align="center">
  <img src="https://img.shields.io/badge/Made_with-Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Made with Laravel">
  <img src="https://img.shields.io/badge/Bilingual-AR%20%2F%20EN-orange?style=flat-square" alt="Bilingual AR/EN">
  <img src="https://img.shields.io/badge/Tests-317%20passing-2ea44f?style=flat-square" alt="317 tests passing">
</p>

<p align="center">© 2026 Dar El-Jamila. All rights reserved.</p>
