# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **Ultimate POS** — a multi-tenant Point of Sale and business management system built on Laravel 9. It includes sales, purchases, inventory, accounting, loans (French amortization), and delinquency tracking. It uses the [Nwidart Laravel Modules](https://nwidart.com/laravel-modules/) package with 16+ enabled modules.

## Common Commands

```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Compile frontend assets
npm run dev          # Development build
npm run watch        # Watch mode
npm run prod         # Production build
npm run format       # Format JS/CSS with Prettier

# Laravel artisan
php artisan migrate
php artisan db:seed
php artisan key:generate
php artisan serve

# Scheduled commands (run via scheduler or manually)
php artisan pos:calculateStatusLoan
php artisan pos:CalculateStatusDelinquent
```

## Architecture

### Multi-Tenancy
All core tables are scoped by `business_id`. The authenticated user's business is stored in session and injected via `SetSessionData` middleware. Never query without scoping to the current business.

### Utility Classes (app/Utils/)
Business logic lives in utility classes injected into controllers via the constructor — not in models or controllers directly:
- `TransactionUtil` — core transaction processing, French amortization schedule calculation, payment application
- `ProductUtil` — product pricing, quantities
- `BusinessUtil` — business settings/config
- `ContactUtil` — customer/supplier operations
- `NotificationUtil` — multi-channel notifications (SMS via Twilio, Pusher)
- `ModuleUtil` — module feature flags

### Transaction Model
`Transaction` is the central model that covers all financial operations: `purchase`, `sell`, `expense`, `stock_adjustment`, `opening_stock`, etc. The `type` column distinguishes them.

### Loan & Payment Schedule System
Custom addition on top of the base POS:
- `Loan` model with French amortization interest calculation
- `PaymentSchedule` — monthly installments (capital + interest breakdown)
- `ScheduleVersion` — tracks schedule revisions when capital payments modify the amortization
- `Delay` (SoftDelete) — delinquency tracking with late fees (mora)
- `PaymentApplication` — records how payments are applied across installments
- Loan statuses: `quotation → approved → partial / in-arrears → paid / cancelled`
- Key method: `TransactionUtil::calculateFrenchAmortization()`
- `DelayPaymentController` handles partial/full delinquency resolution with condonation support

### Modules (Modules/)
Each module follows Nwidart's structure with its own controllers, models, routes, views, and migrations. Module-specific features are gated via `ModuleUtil::isModuleInstalled()`.

### Routes
- `routes/web.php` — main web routes, heavily uses `auth` + `setData` middleware group
- Each module has its own `routes/web.php`
- API routes in `routes/api.php` use Passport for authentication

### Frontend
- **AdminLTE** dashboard template with Blade views
- **Vue 2** for reactive components
- **jQuery** + DataTables for listing pages
- **Laravel Mix** compiles to `public/js/` and `public/css/`
- Payment UI logic lives in `public/js/payment.js`
- POS terminal logic in `public/js/pos.js`
- Global utilities in `public/js/functions.js`

### Key Middleware
- `setData` (alias for `IsInstalled`) — required on all authenticated routes; sets session data
- `AdminSidebarMenu` — builds dynamic sidebar based on user permissions/modules
- `Superadmin` — restricts superadmin-only routes

### Authorization
Uses Spatie Permission for role-based access control. Permission names follow the pattern `module.action` (e.g., `sell.view`, `purchase.create`). Check permissions with `auth()->user()->can('permission.name')`.

### PDF Generation
Uses DomPDF (`barryvdh/laravel-dompdf`) for invoices/reports and mPDF for more complex documents.
