# 🏛️ AmunGuide API

> A Smart, Full-Featured Engine for Tourism Management & Trip Planning

[![Laravel Version](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![Database](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Deployment](https://img.shields.io/badge/Railway-Deployed-131415?style=for-the-badge&logo=railway&logoColor=white)](https://railway.app)

**🌐 Base URL:** `http://amun-guide.up.railway.app`

**AmunGuide API** is an advanced, decoupled RESTful API built with **Laravel 10**, serving as the core backend for a comprehensive tourism platform in Egypt. It supports web and mobile applications through a flexible, secure, and scalable environment for managing tourist attractions, tours, group bookings, digital payments, and an AI-powered system for automated trip planning and user behavior analysis.

---

## 🚀 Core Features

### 1. Multi-Role Authentication & Identity Management
- **Advanced Role System:** Strict user classification across `Tourist / Guide / Admin` roles, enforced via dedicated Middleware guards.
- **Secure Authentication:** **Laravel Sanctum** for issuing and managing Bearer Tokens.
- **Account Management:** Full profile updates, image uploads, and password recovery via custom notifications.

### 2. Places & Tours Hub
- **Places:** A comprehensive tourist directory supporting place categorization, metadata management, and multimedia (Images & Media).
- **Tours:** A dedicated system for guides to create full tour packages, set pricing, schedules, and link tours to detailed itineraries.
- **Advanced Search & Filtering:** A smart search engine allowing users to filter results by compound criteria such as price, rating, and type.

### 3. Custom Travel Itineraries
- **Plans Engine:** Enables tourists to create personalized travel plans and daily schedules (`Plan Items`), linked to specific tourist attractions.

### 4. AI Chatbot & Advanced RAG Integration
- **Context-Aware Conversations:** Smart conversation tracking via a flexible data model supporting multiple contexts: `image_generation`, `travel_plan`, `info_request`, `place_inquiry`, `tour_inquiry`.
- **RAG Data Feed:** Real-time aggregation of user data, behaviors, and tourism preferences, delivered to the AI engine for accurate, personalized responses.
- **AI-Generated Images:** Automated tracking and storage of images generated during conversations with secure URLs.

### 5. Polymorphic Social Interaction
- **Polymorphic Comments:** A unified comment system serving tours, places, and plans in a single table via `morphs`.
- **Polymorphic Likes:** A fast Toggle feature protected against duplicate likes (`409 Conflict`).

### 6. User Behavior Analytics Engine
- **Activity Tracking:** Real-time logging of visits, searches (by keyword or filter criteria), likes, comments, and plan creation.
- **Complex Data Processing:** An internal smart engine handling multi-layered JSON data, stripping structural noise (IP, User Agent) to deliver a clean user behavior timeline.
- **Global Admin Trends:** Provides admins with a comprehensive view of active users, trending places, and cumulative engagement analytics.

### 7. Secure Bookings & Payments
- **Booking Lifecycle:** Full management from request creation, availability check, to status updates `Pending / Confirmed / Cancelled`.
- **Payments Logging:** Secure documentation of digital payment operations linked to bookings, retaining transaction data, reference numbers, and payment statuses.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend Framework** | Laravel 10 (PHP 8.2) |
| **Database** | MySQL 8.0 with Polymorphic Relations & optimized Indexes |
| **Authentication** | Laravel Sanctum |
| **API Standardization** | Laravel API Resources |
| **Data Validation** | Custom Form Requests per Endpoint |
| **Deployment** | Nixpacks + Docker Ready (Railway) |

---

## 📂 Directory Structure

```
peter1girgis/AmunGuide/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Admin/                     # Admin dashboard & user management
│   │   │   ├── AnalysisController.php     # Analytics engine & activity tracking
│   │   │   ├── CommentsController.php     # Polymorphic comments system
│   │   │   ├── ConversationController.php # Chatbot conversations & image generation
│   │   │   ├── LikesController.php        # Unified polymorphic likes
│   │   │   ├── PaymentController.php      # Payment processing & logging
│   │   │   ├── PlaceController.php        # Tourist places & media management
│   │   │   ├── PlanController.php         # Trip planning & itineraries engine
│   │   │   ├── RagMessageController.php   # AI message processing & RAG feed
│   │   │   └── TourBookingController.php  # Tour group booking management
│   │   ├── Middleware/                    # Role guards (Admin, Tourist, Guide)
│   │   ├── Requests/                      # Form Requests per endpoint
│   │   └── Resources/                     # API output transformation layer
│   ├── Models/                            # Eloquent models & relationships
│   └── Notifications/                     # Notifications & password recovery
├── config/                                # CORS, Database, Cache, Sanctum configs
├── database/
│   ├── migrations/                        # 19 migrations for full schema
│   └── seeders/                           # Initial seed data for testing
├── routes/
│   ├── api.php                            # 87 API routes organized by access level
│   └── web.php
├── nixpacks.toml                          # Cloud runtime configuration
├── Procfile                               # Process definitions for hosting platforms
└── railway-entrypoint.sh                  # Auto-setup script on each deployment
```

---

## 🗺️ API Endpoints

### 🔐 Auth Module

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/register` | POST | Public | Create a new account (Tourist / Guide) |
| `/api/v1/login` | POST | Public | Login and issue a Bearer Token |
| `/api/v1/logout` | POST | Authenticated | Revoke current token and logout |
| `/api/v1/forgot-password` | POST | Public | Send a password reset link |

### 🏛️ Places Module

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/places` | GET | Public | Fetch paginated tourist places |
| `/api/v1/places/{id}` | GET | Public | Get full details of a tourist place |
| `/api/v1/places/filter` | GET | Public | Filter places by type & geographic category |
| `/api/v1/places` | POST | Admin Only | Add a new tourist place |

### 🗺️ Tours & Bookings Module

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/tours` | GET | Public | Browse all available tours |
| `/api/v1/tours/popular` | GET | Public | Get the most popular tours |
| `/api/v1/tours` | POST | Guide Only | Create a tour linked to an itinerary |
| `/api/v1/bookings` | POST | Tourist Only | Request a tour booking |
| `/api/v1/bookings/{id}` | PUT | Authenticated | Update booking status |

### 🤖 AI Chatbot & RAG Module

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/conversations` | POST | Authenticated | Start a new chatbot conversation |
| `/api/v1/conversations` | GET | Authenticated | Retrieve user conversation history |
| `/api/v1/conversations/{id}/messages` | POST | Authenticated | Send a message and receive AI response |
| `/api/v1/rag/data` | GET | Authenticated | Compile user behavioral profile for RAG |

### 📈 Analytics Module

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/analysis/my-data` | GET | Authenticated | Full timeline of user interactions |
| `/api/v1/analysis/global-trends` | GET | Admin Only | Platform-wide stats & trending places |

### 💬 Polymorphic Likes & Comments

| Endpoint | Method | Access | Description |
|---|---|---|---|
| `/api/v1/likes/toggle` | POST | Authenticated | Add or remove a like from Tour / Place / Plan |
| `/api/v1/comments` | POST | Authenticated | Add a comment on a tour or place |
| `/api/v1/{type}/{id}/comments` | POST | Authenticated | Direct route for adding a comment |

---

## ⚙️ Local Setup

```bash
# 1. Set up environment file
cp .env.example .env

# 2. Install dependencies
composer install

# 3. Generate application key
php artisan key:generate

# 4. Configure database in .env, then run migrations and seeders
php artisan migrate --seed

# 5. Start local development server
php artisan serve
```

---

## ☁️ Deployment

The project is fully configured for direct deployment on **Railway** via:

- **`nixpacks.toml`** — Automatically builds the runtime environment, installs `php82` and extensions like `pdo_mysql`, and enables production performance caching.
- **`railway-entrypoint.sh`** — Generates cache, optimizes routes via `route:cache`, and secures storage directories automatically on every deployment without manual intervention.
