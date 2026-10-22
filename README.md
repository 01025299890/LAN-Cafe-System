# ☕ LAN Cafe System

> **A Professional, Offline-First Restaurant & Cafe Management POS System**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

---

## 📌 About The Project

**LAN Cafe** is a high-availability POS and Menu management system engineered specifically for cafes and restaurants. Built with an **Offline-First** philosophy, it operates entirely over a local area network (LAN) without relying on an internet connection, guaranteeing zero downtime, maximum speed, and total operational reliability.

The system connects customers, kitchen staff, waiters, and management through a seamless real-time digital interface.

---

## ✨ Key Features

- 🌐 **Offline-First Intranet Architecture:** Operates seamlessly via local static IP addressing over a local network with zero internet dependencies.
- 📱 **Interactive Customer Digital Menu:** Responsive UI for customers to browse items, customize orders, and place requests instantly.
- 👨‍🍳 **Kitchen Order Display (KOD):** Real-time order tracking and queue management for kitchen staff.
- 🔒 **Role-Based Access Control & Middleware:** Custom middleware implementations (`ChickChief`) to ensure secure access routing and prevent unauthorized access.
- ⚡ **Real-Time Responsiveness:** Powered by Laravel Livewire & Volt for dynamic UI components without page reloads.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.2+, Laravel 12
- **Frontend:** Livewire, Volt, Tailwind CSS
- **Database:** MySQL
- **WebServer:** Nginx / Apache (XAMPP / NSSM Service Integration)

---

## 🚀 Getting Started

Follow these instructions to set up the project locally.

### Prerequisites

Ensure you have the following installed:
- [PHP >= 8.2](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [Node.js & NPM](https://nodejs.org/)
- [MySQL Database](https://www.mysql.com/)

### Installation

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/YOUR_USERNAME/cafe-system.git](https://github.com/YOUR_USERNAME/cafe-system.git)
   cd cafe-system
