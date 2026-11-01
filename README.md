<div align="center">

<a href="https://dareljamila.com">
  <img src="docs/screenshots/home-en.jpg" alt="Dar El Jamila — Modest Fashion Storefront" width="100%">
</a>

<br>

# 🌸 Dar El Jamila &nbsp;·&nbsp; دار الجميلة

### A luxury, bilingual modest-fashion e-commerce platform — storefront, back office, and operations, built from scratch on Laravel.

<p>
  <em>Abayas, kaftans, isdal sets and evening wear — presented with the care of a couture house,<br>
  engineered with the discipline of an enterprise system.</em>
</p>

<p>
  <a href="https://dareljamila.com"><img src="https://img.shields.io/badge/🌐_Visit_Live_Store-dareljamila.com-6B1A2E?style=for-the-badge&labelColor=3D0F1B" alt="Visit the live store"></a>
</p>

<p>
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=flat-square&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/PWA-Installable-5A0FC8?style=flat-square&logo=pwa&logoColor=white" alt="PWA">
</p>
<p>
  <img src="https://img.shields.io/badge/Tests-874_passing-2EA44F?style=flat-square" alt="874 tests">
  <img src="https://img.shields.io/badge/i18n-العربية_|_English-E8C39E?style=flat-square" alt="Arabic and English">
  <img src="https://img.shields.io/badge/RTL-First--class-6B1A2E?style=flat-square" alt="RTL">
  <img src="https://img.shields.io/badge/Hosting-Shared--hosting_ready-1F6FEB?style=flat-square" alt="Shared hosting ready">
</p>

<p>
  <a href="#-at-a-glance">At a Glance</a> •
  <a href="#-gallery">Gallery</a> •
  <a href="#-features">Features</a> •
  <a href="#-architecture">Architecture</a> •
  <a href="#-getting-started">Getting Started</a> •
  <a href="#-deployment">Deployment</a> •
  <a href="#-author">Author</a>
</p>

</div>

---

## 💎 About the Project

**Dar El Jamila** is a complete, production-running e-commerce platform for a modest-fashion design house. It is not a theme on top of Shopify or WooCommerce — every layer, from the product-variant engine to the PDF invoices to the role-based back office, is custom-built on Laravel and running live at **[dareljamila.com](https://dareljamila.com)**.

It was designed around three convictions:

| | |
|---|---|
| 🎨 **Luxury is a detail game** | A bespoke burgundy-and-gold design system, hand-tuned Arabic and Latin typography, and motion that feels considered — on every page, every email, every invoice. |
| 🌍 **Arabic is not an afterthought** | Full Arabic / English parity across the UI, emails, PDFs and admin, with true RTL layouts — not a mirrored LTR site. |
| ⚙️ **Production is the real test** | Built and hardened to run reliably on constrained shared hosting: no daemons, no `proc_open`, no Redis — and still transactional, idempotent and observable. |

<div dir="rtl">

> **دار الجميلة** منصة تجارة إلكترونية متكاملة لدار أزياء محتشمة فاخرة — واجهة متجر أنيقة، ولوحة تحكم احترافية، ونظام طلبات وفواتير وإشعارات كامل، بدعم حقيقي للغتين العربية والإنجليزية واتجاه الكتابة من اليمين لليسار. مبنية بالكامل من الصفر باستخدام Laravel، وتعمل الآن على **[dareljamila.com](https://dareljamila.com)**.

</div>

---

## 📊 At a Glance

<div align="center">

| 🧩 Models | 🗄️ Migrations | 🎛️ Controllers | 🧠 Services | 📧 Emails | 🔔 Notifications | 🖼️ Blade Views | 🌐 Translation Files | ✅ Tests |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **39** | **75** | **68** | **18** | **20** | **20** | **191** | **34 × 2** | **874** |

</div>

---

## 🖼️ Gallery

### The Storefront

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/home-ar.jpg" alt="Arabic homepage"><p align="center"><sub><b>Homepage — Arabic (RTL)</b></sub></p></td>
    <td width="50%"><img src="docs/screenshots/shop.jpg" alt="Shop"><p align="center"><sub><b>Shop — categories, filters & sizes</b></sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/product.jpg" alt="Product page"><p align="center"><sub><b>Product — variants, size guide, back-in-stock alerts</b></sub></p></td>
    <td><img src="docs/screenshots/blog.jpg" alt="Blog"><p align="center"><sub><b>Blog — “Tales of Elegance”</b></sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/track.jpg" alt="Order tracking"><p align="center"><sub><b>Guest order tracking</b></sub></p></td>
    <td><img src="docs/screenshots/faq.jpg" alt="FAQ"><p align="center"><sub><b>FAQ — managed from the admin</b></sub></p></td>
  </tr>
</table>

### Authentication

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/login.jpg" alt="Login English"><p align="center"><sub><b>Sign in — English, with Google Sign-In</b></sub></p></td>
    <td width="50%"><img src="docs/screenshots/login-ar.jpg" alt="Login Arabic"><p align="center"><sub><b>تسجيل الدخول — Arabic</b></sub></p></td>
  </tr>
</table>

### Mobile-First

<p align="center">
  <img src="docs/screenshots/m-home.jpg" alt="Mobile home" width="23%">
  &nbsp;
  <img src="docs/screenshots/m-shop.jpg" alt="Mobile shop" width="23%">
  &nbsp;
  <img src="docs/screenshots/m-product.jpg" alt="Mobile product" width="23%">
  &nbsp;
  <img src="docs/screenshots/m-admin.jpg" alt="Mobile admin" width="23%">
</p>
<p align="center"><sub>Home (AR) · Shop · Product · Admin dashboard (AR) — every screen is fully responsive, admin included.</sub></p>

### The Back Office

<p align="center">
  <img src="docs/screenshots/admin-dashboard.jpg" alt="Admin dashboard" width="100%">
  <sub><b>Action-first dashboard — “Needs Your Attention” on top, analytics below</b></sub>
</p>

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/admin-dashboard-ar.jpg" alt="Admin dashboard Arabic"><p align="center"><sub><b>The same dashboard, in Arabic</b></sub></p></td>
    <td width="50%"><img src="docs/screenshots/admin-products.jpg" alt="Products"><p align="center"><sub><b>Product management</b></sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/admin-product-create.jpg" alt="Product wizard"><p align="center"><sub><b>Product creation wizard — bilingual, autosaving</b></sub></p></td>
    <td><img src="docs/screenshots/admin-orders.jpg" alt="Orders"><p align="center"><sub><b>Orders & status workflow</b></sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/admin-inventory.jpg" alt="Inventory"><p align="center"><sub><b>Inventory — per-size stock, inline editing</b></sub></p></td>
    <td><img src="docs/screenshots/admin-reviews.jpg" alt="Reviews"><p align="center"><sub><b>Review moderation</b></sub></p></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/admin-customers.jpg" alt="Customers"><p align="center"><sub><b>Customers CRM</b></sub></p></td>
    <td><img src="docs/screenshots/admin-email.jpg" alt="Email templates"><p align="center"><sub><b>Email template preview studio</b></sub></p></td>
  </tr>
</table>

---

## ✨ Features

### 🛍️ Storefront

- **Curated catalog** — categories, brands, collections, live search, filters by size / price / availability.
- **Variant engine** — Options → Values → Variants, each variant with its own price, stock and SKU.
- **Smart product pages** — size guide, “You May Also Like” (manual + automatic), reviews with photos and *helpful* votes, WhatsApp “Ask about this product”.
- **Back-in-stock alerts** — subscribe to a sold-out size and get notified the moment it returns.
- **Cart, wishlist & checkout** — coupons, structured shipping methods, optional geolocation, stock locked transactionally so nothing is ever oversold.
- **Order tracking** — for customers *and* guests, plus order change / return requests.
- **Content** — blog with moderated comments, FAQ, services, about, contact, return policy — all editable from the admin.
- **Installable PWA** — manifest, service worker, offline page and Web Push notifications.
- **SEO** — `sitemap.xml`, canonical URLs, JSON-LD structured data, per-product / category / post meta.

### 🖥️ Admin Dashboard

- **Needs Attention panel** — pending orders, return requests, stock alerts, unread messages, pending reviews — the work first, the charts second.
- **Product management** — draft / scheduled / published workflow, autosave, creation wizard, drag-and-drop media, bulk actions, keyboard shortcuts.
- **Sales** — orders, carts (live abandoned-cart view with reminders), customers CRM with notes, coupons, shipping methods.
- **Marketing & content** — hero banners, testimonials, FAQs, services, blog, newsletter with CSV export.
- **Reports** — sales (CSV export), products, customers, inventory, wishlist analytics.
- **Activity log** — a read-only audit trail of every sensitive admin action.
- **Email preview studio** — render every transactional email in the browser before it ever reaches a customer.

### 🔐 Accounts & Security

- **Four-tier roles** — Super Admin / Admin / Employee / Customer, with **~75 granular permissions** and one-click presets for employees.
- **A protected Primary Super Admin** that no one can demote, disable or delete.
- **Google Sign-In** (provider-agnostic Socialite layer), **OTP email verification**, new-login alerts, branded password reset.
- Policies on every model, rate limiting on every sensitive endpoint, signed invoice URLs, XSS-safe user links.

### 🧾 Orders, Invoices & Email

- **Bilingual PDF invoices** with correct Arabic shaping and RTL tables, a full status lifecycle (`pending → processing → generated → emailed → failed`) and atomic, validated file writes.
- **20 luxury-styled transactional emails** — order confirmation, invoice ready, status updates, payments, OTP, cart and wishlist reminders, back-in-stock, review & comment moderation, newsletter, staff welcome and more.
- **Customer & admin notifications** kept as separate classes — never the same message sent to both audiences.
- **Locale snapshots** — every email and invoice renders in the language the order was placed in.

<details>
<summary><b>📋 Full module matrix</b></summary>

<br>

| Module | | Module | |
|---|:---:|---|:---:|
| Authentication & OTP | ✅ | PDF invoices (AR/EN) | ✅ |
| Google Sign-In | ✅ | Transactional emails (20) | ✅ |
| Roles & permissions | ✅ | In-app & Web Push notifications | ✅ |
| Products & variants | ✅ | Abandoned-cart recovery | ✅ |
| Categories, brands, collections | ✅ | Back-in-stock alerts | ✅ |
| Inventory & stock alerts | ✅ | Reviews & testimonials | ✅ |
| Cart, wishlist, coupons | ✅ | Blog & moderated comments | ✅ |
| Checkout & shipping methods | ✅ | FAQ, services, hero banners | ✅ |
| Orders, tracking, returns | ✅ | Newsletter | ✅ |
| Customers CRM | ✅ | Reports & CSV export | ✅ |
| Activity log | ✅ | SEO & sitemap | ✅ |
| PWA & offline mode | ✅ | Demo catalog importer | ✅ |
| Online payment gateway | 🔜 | Native mobile app | 🔜 |

</details>

---

## 🧰 Tech Stack

<table>
  <tr><td><b>Backend</b></td><td>Laravel 13 · PHP 8.3 · MySQL 8 · Spatie Permission · Laravel Socialite · mPDF</td></tr>
  <tr><td><b>Frontend</b></td><td>Blade components · Tailwind CSS 3 · Alpine.js 3 · Vite 8 · Chart.js 4 · SortableJS</td></tr>
  <tr><td><b>Infrastructure</b></td><td>Database queue · cron-driven workers · Laravel Scheduler · Service Worker / Web Push</td></tr>
  <tr><td><b>Quality</b></td><td>PHPUnit 12 (874 tests) · Laravel Pint · Puppeteer for visual and offline checks</td></tr>
</table>

---

## 🏗️ Architecture

```mermaid
flowchart LR
    U([Customer / Staff]) --> R[Routes<br/>web · admin · auth]
    R --> M[Middleware<br/>locale · auth · verified<br/>admin · permission]
    M --> FR[Form Requests]
    FR --> C[Thin Controllers]
    C --> P{Policies}
    P --> S[Service Layer<br/>Cart · Invoice · OTP<br/>Stock · Push · Social]
    S --> E[(Eloquent / MySQL)]
    S -. afterCommit .-> Q[[Database Queue]]
    Q --> J[Jobs<br/>Invoices · Reminders]
    J --> MAIL[Mail & Notifications]
    CRON((Cron)) --> Q
    CRON --> SCH[Scheduled Commands]
```

**Design principles**

- **Thin controllers, rich services** — business rules live in `app/Services`, one place to read and test.
- **Transactions where money moves** — checkout row-locks stock (`lockForUpdate`) inside `DB::transaction()`.
- **Idempotent, retry-safe jobs** — dispatched `afterCommit()`, bounded retries with backoff, never re-send a delivered email.
- **Fail loudly, never silently** — real failures reach `failed_jobs` and are persisted on the record the customer sees.
- **Bilingual at the schema level** — `name_ar` / `name_en` columns, not a translation package bolted on.

<details>
<summary><b>📁 Project structure</b></summary>

```
app/
├── Console/Commands/     demo:import · carts:send-reminders · products:publish-scheduled · back-in-stock:prune …
├── Http/
│   ├── Controllers/      Storefront · Admin/ · Account/ · Auth/
│   ├── Middleware/       Admin · SuperAdmin · Permission · Locale
│   └── Requests/         Form-request validation
├── Jobs/                 GenerateAndSendInvoice · SendAbandonedCartReminderJob
├── Mail/                 20 branded Mailables
├── Models/               39 Eloquent models
├── Notifications/        Customer & admin notifications
├── Policies/             Per-model authorization
└── Services/             Business-logic layer

config/                   admin_sidebar · cart · invoice · permission_groups · permission_presets …
database/                 75 migrations · seeders · factories
lang/{ar,en}/             34 translation files per language, full parity
resources/views/          admin · account · auth · emails · invoices · shop · blog · components …
routes/                   web.php · admin.php · auth.php · console.php
tests/                    Feature · Unit
```

</details>

---

## 🚀 Getting Started

**Requirements:** PHP 8.3+ · Composer 2 · Node.js 18+ · MySQL 8 (or SQLite)

```bash
# 1 — Clone
git clone https://github.com/m77mdabdo/Dar_El-.git dar-el-jamila
cd dar-el-jamila

# 2 — Dependencies
composer install
npm install

# 3 — Environment
cp .env.example .env
php artisan key:generate

# 4 — Database (set your DB credentials in .env first)
php artisan migrate --seed
php artisan storage:link

# 5 — Optional: a full demo catalog with real photography (needs PEXELS_API_KEY)
php artisan demo:import

# 6 — Your first Super Admin
php artisan app:make-super-admin you@example.com --name="Your Name"

# 7 — Run everything (server · queue · logs · Vite) in one command
composer dev
```

Then open **http://localhost:8000** — the storefront defaults to Arabic; switch languages from the header.

<details>
<summary><b>🔑 Key environment variables</b></summary>

<br>

| Variable | Purpose |
|---|---|
| `APP_URL`, `APP_LOCALE` | Public URL and default language (`ar`) |
| `DB_*` | Database connection |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | All `database` — no Redis required |
| `MAIL_*` | SMTP for all transactional emails |
| `OAUTH_PROVIDERS`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_CALLBACK_URL` | Google Sign-In |
| `INVOICE_PDF_ENGINE` | `mpdf` (default, RTL-correct) or `dompdf` (rollback) |
| `CART_ABANDONED_AFTER_MINUTES`, `CART_REMINDER_INTERVAL_HOURS`, `CART_MAX_REMINDERS` | Abandoned-cart recovery tuning |
| `PRIMARY_SUPER_ADMIN_EMAIL` | The protected owner account |
| `WEBPUSH_VAPID_PUBLIC_KEY`, `WEBPUSH_VAPID_PRIVATE_KEY` | Web Push — generate with `php artisan webpush:vapid` |
| `PEXELS_API_KEY` | Only for `demo:import` |

</details>

---

## ☁️ Deployment

Dar El Jamila runs in production on **shared hosting** — no persistent processes and no `proc_open`. That constraint shaped the architecture: queues are drained by short-lived cron workers, and every recurring task is its own cron entry rather than going through `schedule:run`.

```cron
* * * * *     php artisan queue:work database --stop-when-empty --max-time=50 --timeout=45 --tries=3
*/5 * * * *   php artisan products:publish-scheduled
*/30 * * * *  php artisan carts:send-reminders
0 3 * * *     php artisan back-in-stock:prune
```

A single script handles each release:

```bash
bash scripts/deploy.sh
```

<details>
<summary><b>Manual release steps</b></summary>

```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

> Root-level static files (favicons, `robots.txt`, `sw.js`, verification files) must also be present in the domain’s real web root.

</details>

---

## 🧪 Testing

```bash
php artisan test
```

**874 tests** covering the storefront, checkout and stock locking, every admin module, authentication and OTP, roles and permissions, invoice generation, email content and addressing, scheduled commands and the PWA.

---

## 🗺️ Roadmap

- [ ] Online payment gateway (cards & wallets)
- [ ] Native iOS / Android app
- [ ] Loyalty & rewards program
- [ ] Multi-warehouse inventory
- [ ] Public REST API for headless storefronts

---

## 👤 Author

<div align="center">

### Mohamed Abdo

**Full-Stack Developer** — design, architecture, development and deployment of Dar El Jamila.

<p>
  <a href="https://github.com/m77mdabdo"><img src="https://img.shields.io/badge/GitHub-m77mdabdo-181717?style=for-the-badge&logo=github" alt="GitHub"></a>
  <a href="https://dareljamila.com"><img src="https://img.shields.io/badge/Live-dareljamila.com-6B1A2E?style=for-the-badge&logo=googlechrome&logoColor=white" alt="Live site"></a>
</p>

</div>

---

## 📄 License

Released under the **MIT License** — © 2026 Mohamed Abdo.

<div align="center">

<br>

<sub>Crafted with care in Laravel · صُنع بعناية</sub>

<br><br>

<a href="https://dareljamila.com"><b>dareljamila.com</b></a>

</div>
