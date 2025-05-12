# Library Management System

A web-based library management system built with PHP and MySQL that allows users to browse, reserve, and manage books.

## Features

- User Authentication (Admin and Regular Users)
- Book Management (Add, Edit, Delete)
- Book Reservation System
- User Management
- Admin Dashboard
- Book Categories
- Image Upload for Book Covers

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache/Nginx)
- XAMPP/WAMP/MAMP (for local development)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/zarr14/BookRequestApp
```

2. Import the database:
   - Open phpMyAdmin
   - Create a new database named `utilisateur`
   - Import the `database_setup.sql` file

3. Configure the database connection:
   - Open the PHP files
   - Update the database credentials in the connection string:
     ```php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $dbname = "utilisateur";
     ```

4. Start your local server and access the application

## Default Admin Credentials

- Email: admin@admin.com
- Password: admin123

## Directory Structure

- `admin.php` - Admin dashboard
- `home.php` - Landing page and authentication
- `user.php` - User dashboard
- `handle_book.php` - Book management
- `handle_reservation.php` - Reservation management
- `database_setup.sql` - Database schema
- `uploads/` - Book cover images

## Security Features

- Password Hashing
- Session Management
- Role-based Access Control
- Input Validation
- Prepared SQL Statements

## Technologies Used

- PHP
- MySQL
- Bootstrap 5
- Font Awesome
- HTML5
- CSS3
- JavaScript 
