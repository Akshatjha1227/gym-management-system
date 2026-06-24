# ⚡ IronForge Gym Management System

A fully featured gym management web application built with **HTML5 · CSS3 · Bootstrap 5 · JavaScript** on the frontend and **PHP 8 · MySQL** on the backend.

---

## 📁 Project Files

```
ironforge-gym/
├── gym_management.html   # Frontend — complete single-page app
├── gym_backend.php       # Backend — PHP REST API
├── gym_schema.sql        # Database — MySQL schema + seed data
└── README.md             # This file
```

---

## 🚀 Quick Start (Browser Only)

No server required for a demo. Just open `gym_management.html` in any modern browser — data is stored in `localStorage` automatically.

---

## 🖥️ Full Server Setup (PHP + MySQL)

### Requirements

| Tool | Version |
|------|---------|
| PHP | 8.0 or higher |
| MySQL | 5.7 or higher |
| Web Server | Apache / Nginx |
| Browser | Chrome, Firefox, Edge, Safari |

### Step 1 — Import the Database

```bash
mysql -u root -p < gym_schema.sql
```

This creates the `ironforge_gym` database, all 8 tables, and seeds sample data.

### Step 2 — Configure the Backend

Open `gym_backend.php` and update the credentials at the top:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');   // ← change this
define('DB_NAME', 'ironforge_gym');
```

### Step 3 — Place Files on Server

Copy both files to your web root:

```bash
cp gym_backend.php /var/www/html/
cp gym_management.html /var/www/html/
```

### Step 4 — Connect Frontend to Backend

In `gym_management.html`, replace the `DB` localStorage object's methods with `fetch()` calls to the API. Example:

```javascript
// Instead of: const members = DB.get('members');
const res = await fetch('http://localhost/gym_backend.php?resource=members');
const members = await res.json();

// Instead of: DB.set('members', [...]);
await fetch('http://localhost/gym_backend.php?resource=members', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'John', phone: '9999999999', plan_id: 1, join_date: '2025-01-01' })
});
```

---

## 🗄️ Database Schema

```
membership_plans    → Plans (Monthly, Quarterly, Annual)
trainers            → Trainer profiles
members             → Member records (linked to plan + trainer)
attendance          → Daily check-in / check-out log
payments            → Payment receipts (auto-renews membership)
gym_classes         → Weekly class schedule
equipment           → Gym equipment inventory
```

### Entity Relationships

```
membership_plans ──< members >── trainers
members          ──< attendance
members          ──< payments >── membership_plans
trainers         ──< gym_classes
```

---

## 🔌 REST API Reference

Base URL: `http://yourserver.com/gym_backend.php`

All requests and responses use JSON. Pass `resource` as a query parameter.

### Members

| Method | URL | Description |
|--------|-----|-------------|
| GET | `?resource=members` | List all members |
| GET | `?resource=members&status=active` | Filter by status |
| GET | `?resource=members&search=arjun` | Search by name/phone/email |
| POST | `?resource=members` | Create a member |
| PUT | `?resource=members&id=1` | Update a member |
| DELETE | `?resource=members&id=1` | Delete a member |

**POST body example:**
```json
{
  "name": "Arjun Singh",
  "phone": "9876543210",
  "email": "arjun@email.com",
  "gender": "Male",
  "plan_id": 2,
  "join_date": "2025-01-01",
  "trainer_id": 1,
  "health_notes": "None"
}
```

### Attendance

| Method | URL | Description |
|--------|-----|-------------|
| GET | `?resource=attendance&date=2025-01-15` | Get log for a date |
| POST | `?resource=attendance` | Check in a member |
| PUT | `?resource=attendance&id=5` | Check out a member |

**POST body:**
```json
{ "member_id": 3 }
```

### Payments

| Method | URL | Description |
|--------|-----|-------------|
| GET | `?resource=payments` | All payment history |
| POST | `?resource=payments` | Record a payment (auto-renews membership) |

**POST body:**
```json
{
  "member_id": 2,
  "plan_id": 4,
  "amount": 7999,
  "method": "UPI",
  "payment_date": "2025-01-01"
}
```

### Other Resources

| Resource | Methods | Notes |
|----------|---------|-------|
| `plans` | GET, POST, DELETE | Membership plan CRUD |
| `trainers` | GET, POST, DELETE | Trainer management |
| `classes` | GET, POST, DELETE | Class schedule |
| `equipment` | GET, POST, DELETE | Equipment inventory |
| `stats` | GET | Dashboard summary numbers |

---

## 📋 Features

### Dashboard
- Live stats: total members, active members, monthly revenue, today's check-ins
- Recent members table
- Quick check-in search widget
- Today's check-in log

### Members
- Add members with full profile (name, phone, email, gender, DOB, address, health notes, emergency contact)
- Assign membership plan and trainer
- Expiry date auto-calculated from plan duration
- Search and filter by Active / Expired / Pending
- One-click membership renewal
- View detailed member profile in modal

### Attendance
- Record check-in and check-out times
- Daily attendance log with duration tracking
- Today's summary (in gym vs checked out)
- Weekly bar chart overview

### Payments
- Record payments with method (Cash, UPI, Card, Net Banking)
- Auto-generates receipt number
- Automatically renews member's expiry date on payment
- Revenue totals: all-time and current month
- Expired member dues counter

### Membership Plans
- Create custom plans with any duration and price
- List plan features
- See how many members are on each plan
- Delete unused plans

### Trainers
- Full trainer profiles (specialization, experience, certifications, salary)
- See assigned member count per trainer

### Class Schedule
- Weekly calendar grid view
- Add classes with trainer, day, time, capacity
- Upcoming classes sidebar list

### Equipment
- Track gym equipment inventory
- Condition status (Excellent / Good / Fair / Needs Repair)
- Service date tracking

### Reports
- Revenue breakdown by plan (progress bar chart)
- Membership status distribution
- Export Members, Payments, Attendance as CSV
- Print report

---

## 🔐 Security Notes

The PHP backend uses:
- **PDO prepared statements** — prevents SQL injection
- **Input validation** on all required fields
- **ENUM constraints** in MySQL for status fields
- **Duplicate check-in prevention** via unique key on `(member_id, attendance_date)`

For production, also add:
- JWT or session-based authentication
- HTTPS (SSL certificate)
- Rate limiting on the API
- `.env` file for credentials (not hardcoded)

---

## 🎨 Tech Stack

| Layer | Technology |
|-------|-----------|
| Markup | HTML5 |
| Styling | CSS3 + Bootstrap 5.3 |
| Icons | Bootstrap Icons 1.11 |
| Fonts | Bebas Neue + Barlow (Google Fonts) |
| Scripting | Vanilla JavaScript (ES6+) |
| Storage (demo) | localStorage |
| Backend | PHP 8 (PDO) |
| Database | MySQL 5.7+ |

---

## 📝 License

Free to use and modify for personal and commercial projects.

---

> Built with ⚡ for IronForge Gym
