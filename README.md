# 🛒 ShopEase — Online Shopping Management System

A full-featured e-commerce web application built as a Web Programming course project using **HTML**, **CSS**, **JavaScript**, **PHP**, and **MySQL**.

---

## 📌 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Folder Structure](#folder-structure)
- [Database Design](#database-design)
- [Installation & Setup](#installation--setup)
- [Default Login Credentials](#default-login-credentials)
- [Screenshots](#screenshots)
- [System Flow](#system-flow)
- [Security](#security)
- [Course Information](#course-information)

---

## Project Overview

**ShopEase** is a complete web-based shopping platform that allows customers to browse products, manage a shopping cart, and place orders — while giving administrators full control over products, categories, orders, and users through a dedicated admin panel.

The system is divided into two major sections:

| Section | Description |
|---------|-------------|
| **Customer Side** | Browse products, search, filter by category, manage cart, checkout, view order history |
| **Admin Side** | Dashboard with stats, product CRUD, category management, order status updates, user management |

---

## Features

### Customer / User Side
- ✅ User Registration with JavaScript validation
- ✅ Secure Login with session management
- ✅ Home page with featured products and categories
- ✅ Product listing with search and category filter
- ✅ Product details page
- ✅ Shopping cart (database-stored, persists across sessions)
- ✅ Checkout with shipping information
- ✅ Order placement and confirmation
- ✅ Order history with real-time status tracking

### Admin Side
- ✅ Separate secure admin login
- ✅ Dashboard with total users, products, orders, and revenue
- ✅ Add, edit, and delete products with image upload
- ✅ Category management (add, update, delete)
- ✅ Order management with status updates (Pending → Approved → Shipped → Delivered)
- ✅ User management (view and delete accounts)

---

## Tech Stack

| Technology | Usage |
|------------|-------|
| **HTML5** | Page structure and semantic markup |
| **CSS3** | Styling, responsive layout, animations |
| **JavaScript (ES6)** | Form validation, dynamic cart, search |
| **PHP 8+** | Server-side logic, sessions, file uploads |
| **MySQL** | Relational database for all data storage |
| **Font Awesome 6** | Icons throughout the UI |

---

## Folder Structure

```
ecommerce/
│
├── admin/
│   ├── dashboard.php          # Admin control panel with stats
│   ├── add_product.php        # Add new product with image upload
│   ├── edit_product.php       # Edit existing product
│   ├── manage_products.php    # View and delete products
│   ├── manage_orders.php      # View and update order status
│   ├── manage_categories.php  # Add, edit, delete categories
│   ├── manage_users.php       # View and delete users
│   └── admin_login.php        # Separate admin login page
│
├── css/
│   └── style.css              # Main stylesheet (responsive)
│
├── js/
│   └── script.js              # Validation, cart, search logic
│
├── images/
│   └── default.jpg            # Default product image
│
├── uploads/
│   └── (product images saved here)
│
├── includes/
│   ├── db.php                 # Database connection
│   ├── header.php             # Shared navbar
│   └── footer.php             # Shared footer
│
├── index.php                  # Home page
├── login.php                  # User login
├── register.php               # User registration
├── products.php               # Product listing + search + filter
├── product_details.php        # Single product details
├── cart.php                   # Shopping cart
├── checkout.php               # Checkout and order placement
├── orders.php                 # User order history
├── logout.php                 # Session destroy and redirect
└── database.sql               # Full database schema + sample data
```

---

## Database Design

The system uses **5 relational tables**:

```
users ──────────────┐
                    │
categories ─────┐   │
                │   │
             products
                │   │
                │   ├── cart (user_id + product_id)
                │   │
                └── orders ── order_items
```

### Table Summary

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | Customer and admin accounts | user_id, name, email, password, role |
| `categories` | Product categories | category_id, name |
| `products` | All product listings | product_id, category_id, name, price, stock, image |
| `cart` | Per-user cart items | cart_id, user_id, product_id, quantity |
| `orders` | Placed orders | order_id, user_id, total_price, shipping details, status |
| `order_items` | Products inside each order | item_id, order_id, product_id, quantity, unit_price |

---

## Installation & Setup

### Requirements

- [XAMPP](https://www.apachefriends.org/) or any local server with PHP 8+ and MySQL
- A modern web browser

### Steps

**1. Clone or download the project**
```
Place the ecommerce/ folder inside:
C:/xampp/htdocs/ecommerce
```

**2. Start XAMPP**
- Start **Apache** and **MySQL** from the XAMPP Control Panel

**3. Import the database**
- Open your browser and go to: `http://localhost/phpmyadmin`
- Click **New** and create a database named `ecommerce_db`
- Select the database, click the **Import** tab
- Choose `database.sql` from the project root and click **Go**

**4. Configure the database connection**

Open `includes/db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // your MySQL username
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'ecommerce_db');
```

**5. Create the uploads folder**

Make sure the `uploads/` folder exists and has write permission.  
On Windows with XAMPP this works by default.

**6. Run the project**

Open your browser and visit:
```
http://localhost/ecommerce/
```

---

## Default Login Credentials

> These accounts are inserted automatically when you import `database.sql`

### Admin Account
| Field | Value |
|-------|-------|
| Email | admin@shop.com |
| Password | admin123 |
| Panel | http://localhost/ecommerce/admin/dashboard.php |

### Customer Account
| Field | Value |
|-------|-------|
| Email | ali@example.com |
| Password | user123 |

---

## System Flow

```
User Visits Website
        ↓
Register / Login
        ↓
Browse Products (Search / Filter by Category)
        ↓
View Product Details
        ↓
Add to Cart → Update Cart
        ↓
Checkout (Enter Shipping Info)
        ↓
Order Stored in Database
        ↓
Admin Views Order → Updates Status
        ↓
Customer Views Order Status in Order History
```

---

## Security

| Threat | Protection Used |
|--------|----------------|
| SQL Injection | Prepared statements (`mysqli_prepare`) throughout |
| Password exposure | `password_hash()` with bcrypt, verified via `password_verify()` |
| Unauthorized access | Session-based authentication on every protected page |
| XSS attacks | `htmlspecialchars()` on all user-generated output |
| Direct file access | Session role checks on all admin pages |
| Malicious file uploads | MIME type + extension validation on image uploads |

---

## Course Information

| Field | Details |
|-------|---------|
| **Course** | Web Programming |
| **Project Title** | Online Shopping Management System |
| **Technologies** | HTML, CSS, JavaScript, PHP, MySQL |
| **Project Type** | Full-Stack Web Application |

---

## Order Status Lifecycle

```
Pending  →  Approved  →  Shipped  →  Delivered
                                  ↘
                               Cancelled
```

Status is updated by the admin from the order management panel and is immediately visible to the customer in their order history.

---

> Built with ❤️ as a Web Programming course project.
