# Chula Tiffins

A complete restaurant management system built for **Chula Tiffins**, a local tiffin service delivering fresh lunch to college students near the gate, with pickup from the counter as an alternative.

Customers prepay into a wallet, order daily from a published menu, and pick up or receive delivery. Admins manage menus, orders, inventory, purchases, expenses, and reports — all from a mobile-friendly interface designed to run on a local Windows server with no cloud dependencies.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Local Setup](#local-setup)
- [How the System Works](#how-the-system-works)
- [Module Structure](#module-structure)
- [Daily Operations](#daily-operations)
- [Scheduled Tasks](#scheduled-tasks)
- [Backups](#backups)
- [Security](#security)
- [Deployment Notes](#deployment-notes)
- [License](#license)

---

## Features

### For Customers

- **Mobile-first registration** — sign up with an 8-digit mobile number, no email required
- **Prepaid wallet** — top up via bank QR code or transfer, upload the payment screenshot
- **Daily menu** — see what's cooking today, with veg/non-veg indicators and prices
- **One-tap ordering** — select items, confirm, wallet is debited instantly
- **Order history** — receipts for every order, with refund tracking if cancelled
- **Multi-bank payment** — scan QR from Bank of Bhutan, BNB, T-Bank, DPNB, BDB, or others

### For Admins

- **Real-time dashboard** — today's orders, revenue, pending prep, low stock alerts, low wallet balance
- **Menu catalog** — add/edit dishes with photos, set prices, mark active/inactive
- **Daily & weekly menu publisher** — toggle items available per day, auto-enforces max-2-mains-per-day rule
- **Order workflow** — pending → confirmed → preparing → ready → delivered/picked up
- **Kitchen prep board** — tablet-friendly view with auto-refresh, one-tap status advancement
- **Payment verification** — review customer screenshots, approve or reject, auto-credit wallets
- **Inventory** — stock in/out/waste/physical count with full movement history
- **Suppliers & purchasing** — purchase orders, partial deliveries, goods receipts
- **Expenses** — categories, receipt uploads, void protection (never delete financial records)
- **Reports** — sales, top items, top customers, expense breakdown, wallet activity, stock value
- **CSV exports** — download any report for external analysis
- **Audit log** — full history of every change with user, timestamp, and field-level diffs
- **Settings UI** — no more terminal commands to change restaurant name, mobile, bank QRs, or cut-off times

### Security & Resilience

- **Roles & permissions matrix** — Admin, Manager, Kitchen Staff, Delivery Staff, Customer
- **Rate limiting** — on login, registration, password reset, order placement, top-ups
- **Session hardening** — httpOnly, SameSite=Lax, encrypted payload
- **Security headers** — CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Permissions-Policy
- **Automated backups** — nightly DB dumps, weekly files, off-site copy routine
- **Audit logging** — every model change recorded with user and timestamp

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.4 |
| Database | MariaDB 12.3 |
| Frontend | Livewire 3, Alpine.js, Tailwind CSS 4 |
| Auth | Laravel Fortify with mobile-or-email login |
| Roles | Spatie Laravel Permission |
| Audit | Spatie Activitylog v5 |
| Media | Spatie Laravel Medialibrary |
| Backups | Spatie Laravel Backup |
| Exports | Maatwebsite Excel 4 |
| Build | Vite |

---

## Requirements

- **PHP** 8.3+ with extensions: `fileinfo`, `gd`, `zip`, `mbstring`, `openssl`, `pdo_mysql`, `curl`
- **Composer** 2.6+
- **Node.js** 20+ and npm
- **MariaDB** 11+ (tested on 12.3)
- **Windows** 10+ (Linux/macOS work too — see notes)

---

## Local Setup

### 1. Clone the Repository

```bash
git clone https://github.com/gajrajtrai/tiffin.git
cd chula-tiffins