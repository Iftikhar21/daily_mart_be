---

# 🛒 Daily Mart Backend (Laravel API)

This is the **Laravel Backend API** for the **Daily Mart** system — an application that connects couriers (**kurir**), officers (**petugas**), and registered users (**pelanggan**) through a RESTful API used by the **React Native mobile app**.

---

## ⚙️ Requirements

Before starting, make sure you have installed:

* [PHP 8.1+](https://www.php.net/)
* [Composer](https://getcomposer.org/)
* [MySQL](https://www.mysql.com/)
* [Laravel 10+](https://laravel.com/)
* [Git](https://git-scm.com/)

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/daily_mart_be.git
cd daily_mart_be
```

---

### 2. Install dependencies

```bash
composer install
```

---

### 3. Configure environment

Copy the example environment file:

```bash
cp .env.example .env
```

Then edit the `.env` file to match your local configuration:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=daily_mart
DB_USERNAME=root
DB_PASSWORD=
```

Generate the Laravel app key:

```bash
php artisan key:generate
```

---

### 4. Run migrations and seed data

```bash
php artisan migrate
php artisan db:seed
```

> The `db:seed` command will populate default data (roles, users, etc.) into the database.

---

### 5. Run the Laravel server

Run the backend server using your **local IP address** (get it using `ipconfig` on Windows or `ifconfig` on macOS/Linux):

```bash
php artisan serve --host=192.168.xxx.xxx --port=8000
```

Example:

```bash
php artisan serve --host=192.168.112.171 --port=8000
```

---

## 🔗 API Base URL

Once the server is running, your API will be available at:

```
http://192.168.xxx.xxx:8000/api
```

Example:

```
http://192.168.112.171:8000/api
```

---

## 📱 Mobile Integration

This backend is designed to connect with the **Daily Mart React Native app**.
Make sure the mobile app’s `BASE_URL` in `src/config/api.ts` matches your backend IP:

```ts
export const BASE_URL = 'http://192.168.112.171:8000/api';
```

> ⚠️ Both your **mobile device** and **backend server** must be on the same Wi-Fi network.

---

## 🧩 Roles and Access

| Role                  | Description                                                |
| --------------------- | ---------------------------------------------------------- |
| **Admin**             | Manages users, couriers, officers, and all system data     |
| **Kurir (Courier)**   | Handles delivery tasks                                     |
| **Petugas (Officer)** | Handles operational data                                   |
| **User (Pelanggan)**  | Registers through the mobile app and manages their profile |

---

## 🛠 Useful Commands

| Command                                                | Description                    |
| ------------------------------------------------------ | ------------------------------ |
| `composer install`                                     | Install dependencies           |
| `php artisan key:generate`                             | Generate Laravel app key       |
| `php artisan migrate`                                  | Run all migrations             |
| `php artisan db:seed`                                  | Seed database with sample data |
| `php artisan serve --host=192.168.xxx.xxx --port=8000` | Run local server               |
| `php artisan route:list`                               | Check all API routes           |

---

## 🧰 Tech Stack

* **Framework:** Laravel 10
* **Database:** MySQL
* **Auth:** Laravel Sanctum
* **Language:** PHP
* **Architecture:** RESTful API

---

## ⚡ Notes

* Use `php artisan optimize:clear` if you face caching issues.
* Make sure MySQL service is running before migration.
* Ensure backend and mobile app are on the same network for API communication.

---

## 🏁 Summary

| Step                 | Command                                                |
| -------------------- | ------------------------------------------------------ |
| Install dependencies | `composer install`                                     |
| Setup environment    | `cp .env.example .env`                                 |
| Migrate database     | `php artisan migrate`                                  |
| Seed default data    | `php artisan db:seed`                                  |
| Run server           | `php artisan serve --host=192.168.xxx.xxx --port=8000` |

---