# Multi-Branch Smart Inventory & Order Management System

A backend-focused full-stack project that simulates how a real multi-branch inventory and order management system could be built in a production environment.

The goal of this project was not just to build CRUD features, but to demonstrate **engineering practices used in real systems**, such as transaction safety, concurrency handling, modular architecture, and scalable database design.

The application is built using **Laravel for the backend** and **Vue 3 for the frontend**, following a clean layered architecture that separates business logic from controllers and data access.

---

# System Overview

The system allows organizations with multiple branches to manage:

* Products and SKUs
* Inventory per branch
* Stock movements
* Order processing
* Role-based user access
* Operational reporting dashboards

Each branch operates independently while still being managed centrally by administrators.

---

# Architecture

The project follows a **Layered Modular Monolith Architecture**, which keeps the codebase maintainable while still allowing future scalability.

Client (Vue 3 SPA)
↓
Laravel API Layer (REST + Sanctum)
↓
Application Layer (Controllers + Services)
↓
Domain Layer (Eloquent Models)
↓
Database Layer (MySQL InnoDB)

Key architectural principles used:

* Thin Controllers
* Business Logic inside Service Classes
* Transaction-safe operations
* Strict separation of concerns
* Role-based authorization

---

# Technology Stack

### Backend

* Laravel (latest version)
* MySQL (InnoDB engine)
* Laravel Sanctum authentication
* RESTful API design
* Database transactions

### Frontend

* Vue 3 (Composition API)
* Component-based UI
* API-driven SPA architecture
* Modular and reusable components

---

# Role Based Access Control

The system defines three roles:

**Super Admin**

* Manage all branches
* Manage users
* Access all system modules

**Branch Manager**

* Manage inventory within a specific branch
* View branch reports
* Manage branch orders

**Sales User**

* Create and manage orders
* View product listings
* Limited operational access

Access is enforced through middleware and route-level authorization.

---

# Database Design

The database schema is designed to support multi-branch operations while maintaining strict data integrity.

Core entities include:

* roles
* users
* branches
* products
* inventories
* stock_movements
* orders
* order_items

Important design decisions:

* SKU values are unique per product
* Inventory is stored per **branch + product**
* Orders follow a **header / line item structure**
* Composite indexes improve reporting performance
* Inventory constraints prevent negative stock

---

# Concurrency & Data Integrity

Handling concurrent order requests safely was one of the main engineering goals of this project.

Order processing is implemented using **database transactions and row-level locking**.

Key steps during order creation:

1. A database transaction is started
2. Inventory rows are locked using `lockForUpdate()`
3. Stock availability is validated
4. Order and order items are created
5. Inventory quantities are updated
6. The transaction commits

If stock is insufficient, the transaction is rolled back.

This ensures:

* ACID-compliant operations
* No race conditions
* No overselling
* Consistent inventory state

---

# Inventory Management

The inventory module supports:

* Adding stock
* Adjusting stock levels
* Transferring stock between branches
* Viewing stock movement history
* Identifying low stock items

Inventory levels are strictly controlled and **cannot become negative**.

---

# Order Processing

Orders support multiple products within a single transaction.

Features include:

* Multi-item order creation
* Automatic subtotal calculation
* Tax and total computation
* Secure inventory deduction
* Transaction-safe processing

---

# Reporting Dashboard

Each branch has access to an operational dashboard showing:

* Total sales today
* Total sales for the month
* Total order count
* Top selling products
* Low stock alerts

Queries are optimized using indexes to maintain performance as data grows.

---

# Project Structure

Backend structure:

app/

* Http/Controllers
* Services
* Models
* Middleware

database/

* migrations
* seeders

Frontend structure:

resources/js/

* views
* components
* router

This structure keeps controllers lightweight and moves business logic into reusable service classes.

---

# Installation

### Clone the repository

git clone https://github.com/Aisha-Zahid/multi-branch-smart-inventory-system.git

### Install dependencies

composer install
npm install

### Configure environment

cp .env.example .env

Update your database credentials in `.env`.

Generate application key:

php artisan key:generate

### Run migrations and seeders

php artisan migrate --seed

### Start the development servers

php artisan serve
npm run dev

---

# Demo Credentials

After running seeders, the following accounts are available:

Super Admin
email: [admin@erp.com](mailto:admin@example.com)
password: 123456

Branch Manager
email: [manager@erp.com](mailto:manager@example.com)
password: 123456

Sales User
email: [sales@erp.com](mailto:sales@example.com)
password: 123456

---

# Security Considerations

* Role-based route protection
* Input validation
* Mass assignment protection
* Transaction-safe data operations
* Sensitive environment variables excluded from repository

---

# Scalability Considerations

Although built as a modular monolith, the system is designed with scalability in mind.

Possible production improvements include:

* Redis caching for dashboards
* Queue workers for background tasks
* Read replicas for heavy reporting
* Horizontal scaling behind a load balancer
* Dedicated analytics database

---

# Known Limitations

* Designed for a single database instance
* No distributed locking mechanism
* Reporting module is basic and operational-focused

---

# Engineering Focus

This project focuses on backend engineering principles such as:

* Data integrity
* Concurrency control
* Transaction safety
* Clean architecture
* Maintainable system design

---

# Author

Aisha Zahid
Computer Scientist | Software Developer 
