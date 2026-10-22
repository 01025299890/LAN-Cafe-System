# ☕ Electro Cafe System

**An offline-first POS and digital menu system for cafes and restaurants, built with Laravel.**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

---

## About the project

Electro Cafe is a POS and menu management system designed for cafes and restaurants. It follows an **offline-first** approach: everything runs over the local network (LAN) with no internet connection required, so the cafe keeps working even when the internet is down.

It connects customers, kitchen staff, waiters, and management through one simple interface.

---

## Features

- 🌐 **Offline-first LAN architecture:** runs on a local static IP with no internet dependency.
- 📱 **Digital menu for customers:** a responsive interface to browse items, customize orders, and place requests from their own device.
- 👨‍🍳 **Kitchen Order Display (KOD):** a live order queue for kitchen staff.
- 🔒 **Role-based access control:** custom middleware (`ChickChief`) that restricts each route to the right role.
- ⚡ **Dynamic UI:** built with Laravel Livewire and Volt, so screens update without full page reloads.

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Livewire, Volt, Tailwind CSS |
| Database | MySQL |
| Web server | Nginx / Apache (XAMPP), optionally run as a Windows service with NSSM |

---

## Getting started

### Prerequisites

- [PHP >= 8.2](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [Node.js and NPM](https://nodejs.org/)
- [MySQL](https://www.mysql.com/)

### Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/YOUR_USERNAME/cafe-system.git
   cd cafe-system
   ```

2. **Install dependencies**

   ```bash
   composer install
   npm install
   ```

3. **Set up the environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Then open `.env` and set your database credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

4. **Run the migrations**

   ```bash
   php artisan migrate
   ```

5. **Build the frontend assets**

   ```bash
   npm run build
   ```

6. **Start the server**

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

   Using `--host=0.0.0.0` lets other devices on the same network reach the app.

---

## Running on the local network

To use the system from customers' phones and the kitchen screen:

1. Give the server machine a **static local IP** (for example `192.168.1.10`).
2. Allow the chosen port (for example `8000`) through the machine's firewall.
3. Connect all devices to the same Wi-Fi or LAN.
4. Open `http://192.168.1.10:8000` from any device.

For a permanent setup on Windows, you can run the app with XAMPP or register it as a background service using NSSM so it starts automatically with the machine.

