# FC Scorpio — Football Club Website

A full-stack football club website built with **PHP 8 + MySQL**, no framework and no build step.
Fans can follow the squad and fixtures, book match tickets, and buy merchandise. Club staff get an admin panel to run it all.

## Features

**Fan site**
- Home page with a live countdown to the next match, form guide, top performers and store highlights
- Squad page grouped by position, with appearances, goals and assists
- Fixtures & results with scores and W/D/L badges
- Match tickets by section (Premium / Standard / Economy) with live seat availability
- Printable e-ticket with a booking reference
- Fan store with categories, search, a cart and cash-on-delivery checkout
- "My account" page with bookings and order history
- Fully responsive (mobile, tablet and desktop)

**Admin panel** (`/admin`)
- Dashboard: revenue, ticket sell-through per match, stock alerts, recent activity
- Manage players (with photo upload), fixtures and results, ticket pricing and capacity
- Manage products (with image upload), process store orders (cancelling puts the stock back)
- Ticket sales report with filters, and member management (promote a member to admin)

## Security
- **PDO prepared statements** everywhere, so no SQL injection
- **CSRF tokens** on every form, and all output escaped with `htmlspecialchars` (XSS protection)
- `password_hash` / `password_verify`, session ID regenerated on login, HttpOnly cookies
- Role-based access: every admin page calls `require_admin()`. Public sign-up can only create fans.
- Uploads checked by real MIME type and size, then saved under random names; scripts can't run from `uploads/`
- Stock and seat purchases run in **transactions with row locks** (`SELECT … FOR UPDATE`), so two buyers can't take the last item or seat
- Deletes use POST, not links, and are guarded: a match with sold tickets can't be deleted, and a product with past orders is hidden instead of deleted

## Setup (XAMPP)
1. Copy the `football_club` folder into `C:\xampp\htdocs\`
2. Start **Apache** and **MySQL** in the XAMPP Control Panel
3. Open **phpMyAdmin**, go to **Import** and choose `database/schema.sql`. This creates `football_db` with demo data.
4. Visit **http://localhost/football_club/**

If your MySQL uses a password or a different port, edit `config/config.php`.

### Demo accounts
| Role  | Email                 | Password    |
|-------|-----------------------|-------------|
| Admin | admin@fcscorpio.com   | `Admin@123` |
| Fan   | fan@fcscorpio.com     | `Fan@12345` |

Fixture dates in the demo data are relative to the day you import, so there are always upcoming matches.
Re-import `schema.sql` at any time to reset everything.

## Project structure
```
football_club/
├── index.php, about.php, squad.php, fixtures.php     Public pages
├── tickets.php, ticket.php                           Booking + e-ticket
├── shop.php, product.php, cart.php                   Store, cart & checkout
├── login.php, register.php, logout.php, account.php  Accounts
├── admin/                                            Admin panel (one file per section)
├── includes/      bootstrap, db (PDO), auth + CSRF, helpers, uploads, icons
├── partials/      shared header, footer, admin layout, reusable cards
├── assets/        css/app.css (design system), js/app.js, fonts, images
├── uploads/       product & player images
├── config/        config.php (database credentials)
└── database/      schema.sql (tables + demo data)
```

## What changed from v1
| v1 | v2 |
|----|----|
| SQL built by string concatenation (injectable) | Prepared statements everywhere |
| Admin pages had no login check | Every admin page is protected by role |
| Anyone could register as admin | Sign-up creates fans only; admins are promoted in the panel |
| Separate `members` and `users` tables | One `users` table with a `role` column |
| Tickets were one flat table | Fixtures → ticket sections → bookings (normalised) |
| "Buy" just reduced stock, with no order record | Real orders with line items, statuses and history |
| Delete via GET links | POST + CSRF + confirmation |
| Navbar and DB code copy-pasted into every file | Shared layout partials + a single DB helper |
| Inline styles, fixed widths | Design system with CSS variables, fully responsive |
| 25 MB of images | ~1.5 MB optimised images, self-hosted fonts (works offline) |

## Tech
PHP 8.1+ (PDO), MySQL 5.7+ / MariaDB 10.4+, vanilla CSS and JavaScript. No external dependencies.
