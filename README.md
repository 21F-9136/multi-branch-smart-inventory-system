🚀 Multi-Branch Smart Inventory & Order Management System

Enterprise-level Multi-Branch Smart Inventory & Order Management System built using Laravel (Backend) and Vue 3 (Frontend).

This project was developed as part of a Full Stack Engineering Technical Assignment to demonstrate backend-focused engineering thinking including:

Clean Architecture

Service Layer Implementation

Database Integrity

Concurrency Handling

Role-Based Access Control

Transaction Management

Structured Frontend Design

🏗 1. System Architecture Overview

The system follows a Layered Modular Monolith Architecture:

Client (Vue 3 SPA)
        ↓
API Layer (Laravel REST API + Sanctum)
        ↓
Application Layer (Thin Controllers + Service Layer)
        ↓
Domain Layer (Eloquent Models)
        ↓
Database Layer (MySQL - InnoDB)
        ↓
Optional Cache Layer (File / Redis Ready)
Backend Principles

Thin Controllers

Fat Service Layer

Transaction-based Order Processing

Row-level locking for concurrency

Composite indexing for performance

Strict role-based route protection

🧩 2. Technology Stack
Backend

Laravel (Latest Version)

MySQL (InnoDB)

Laravel Sanctum

RESTful API Design

Database Transactions

Frontend

Vue 3 (Composition API)

Component-Based Architecture

API-based SPA approach

Clean State Management

👥 3. Role-Based Access Control

Implemented roles:

Super Admin

Branch Manager

Sales User

Access is restricted via:

Custom Role Middleware

Route Protection

Controller Authorization

Branch-based data filtering

🗄 4. Database Schema Overview

Main Entities:

roles

users

branches

products

inventories

stock_movements

orders

order_items

Key Design Decisions

SKU is unique

Inventory stored per branch per product

Orders follow Header + Line pattern

Reserved quantity column prevents overselling

Composite indexes added for reporting performance

🔐 5. Concurrency Handling Strategy (Critical Section)

To prevent overselling:

Order creation is wrapped inside a database transaction

Inventory rows are locked using lockForUpdate()

Stock validation occurs after acquiring row-level lock

If stock is insufficient → transaction rolls back

Reserved quantity system prevents double selling

Negative inventory is strictly prevented

This ensures:

ACID compliance

No race conditions

No overselling

Safe concurrent order processing

📦 6. Inventory Module

Supports:

Add Stock

Adjust Stock

Transfer Between Branches

Stock Movement History

Low Stock Indicator

Inventory can never go negative.

🛒 7. Order Processing

Multi-product order creation

Automatic subtotal calculation

Tax calculation

Grand total calculation

Secure stock deduction

Transaction-safe implementation

📊 8. Reporting Dashboard

Per branch dashboard includes:

Total Sales (Today)

Total Sales (This Month)

Total Orders Count

Top 5 Selling Products

Low Stock Products

Optimized using indexing for performance.

📂 9. Folder Structure Explanation
app/
 ├── Http/Controllers/Api
 ├── Services
 ├── Models
 ├── Middleware
database/
 ├── migrations
 ├── seeders
resources/js/
 ├── views
 ├── components
 ├── router

Architecture ensures:

Business logic inside Services

Controllers remain clean

Reusable Vue components

Clear separation of concerns

⚙ 10. Installation Guide
1. Clone Repository
git clone https://github.com/21F-9136/multi-branch-smart-inventory-system.git
2. Install Dependencies
composer install
npm install
3. Setup Environment
cp .env.example .env
php artisan key:generate

Update database credentials inside .env.

4. Run Migrations & Seeders
php artisan migrate --seed
5. Run Server
php artisan serve
npm run dev
🔑 11. Sample Login Credentials

(After seeding)

Super Admin:

email: admin@example.com
password: password

Branch Manager:

email: manager@example.com
password: password

Sales User:

email: sales@example.com
password: password
🛡 12. Security Measures

Role-based route protection

Input validation

Mass assignment protection

Proper HTTP status codes

Transaction-based operations

No sensitive data committed (.env ignored)

📈 13. Scalability Considerations

For production scaling:

Add Redis for caching dashboards

Introduce read replicas for reporting

Use queue system for heavy operations

Horizontal scaling with load balancer

Separate reporting DB for analytics

⚠ 14. Known Limitations

No distributed lock implementation (single DB instance)

No advanced analytics engine

UI kept functional over decorative

🧠 Engineering Focus

This project emphasizes:

Data Integrity

Concurrency Control

Clean Architecture

Backend-Centric System Thinking

Production-Ready Engineering Practices

📌 Submission

GitHub Repository:
https://github.com/21F-9136/multi-branch-smart-inventory-system

🏁 Conclusion

This system demonstrates backend-focused full stack engineering with structured architecture, transaction safety, and scalable design thinking.
