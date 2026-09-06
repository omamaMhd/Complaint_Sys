# 🖥️ Government Complaint System - Backend

This repository contains the robust backend architecture for a comprehensive digital Government Complaint System. The system empowers citizens to submit and track complaints via a mobile app, while providing government employees and administrators with a powerful web dashboard for management and oversight. 

The backend engineering heavily prioritizes **non-functional requirements** such as security, performance, scalability, concurrency control, and transparent tracing, adhering to modern software engineering best practices.

---

## 🛠️ Tech Stack
- **Language & Framework:** PHP 8.2 + Laravel 11
- **Authentication & Security:** Laravel Sanctum (API Token Management), Spatie Laravel Permission (Role-Based Access Control - RBAC)
- **Data Architecture:** Eloquent ORM + Repository Pattern (for clean separation of data access logic)
- **Concurrency & Performance:** Redis (via Predis) for Rate Limiting, caching, and preventing race conditions
- **Real-time Notifications:** Firebase Cloud Messaging (FCM) via `kreait/laravel-firebase`
- **Reporting:** `barryvdh/laravel-dompdf` for generating administrative PDF reports
- **Asynchronous Processing:** Laravel Queues & Jobs (for non-blocking notification dispatch and background tasks)

---

## 🏗️ Software Architecture
The system is built upon a strict **Layered Architecture** to ensure Separation of Concerns (SoC), maintainability, and future scalability:
1. **Presentation Layer:** API Controllers responsible for request handling, strict data validation, and returning standardized JSON responses.
2. **Business Logic Layer:** Dedicated Service classes encapsulating complex rules (e.g., complaint workflow transitions, concurrency checks, and authorization logic).
3. **Data Access Layer:** Repository Pattern implementation to abstract and centralize database queries.
4. **Aspect-Oriented Programming (AOP):** Custom implementation of `TraceAspect` and `TraceContext` to elegantly intercept and handle cross-cutting concerns like Logging, Performance Monitoring, and Audit Trailing, keeping the core business logic clean and focused.

---

## ⚡ Key Backend Features Implemented

### 1. Advanced Authentication & Security
- **OTP Verification:** Secure account creation and login flows requiring One-Time Password validation before granting system access.
- **Brute-Force Protection:** Redis-based rate limiting to block rapid, repeated login attempts, temporarily locking accounts and triggering security alerts after a threshold of failed attempts.
- **Strict Access Control (RBAC):** Granular permissions ensuring citizens can only view their own complaints, and government employees are strictly isolated to manage complaints within their specific department.

### 2. Concurrency Control & Conflict Prevention
- Implemented a robust locking mechanism to prevent race conditions. When an employee opens a complaint for processing, it is flagged as "Reserved". Any concurrent modification attempts by other users are safely rejected, ensuring data integrity.

### 3. Audit Trail & Versioning
- Every state change, note addition, or attachment update generates an immutable, timestamped record in the complaint’s history. This fulfills the strict transparency and tracing requirements without requiring full system rollbacks.

### 4. Asynchronous Real-Time Notifications
- Integrated FCM with Laravel Queues to dispatch instant push notifications to citizens upon complaint submission, status updates, or when additional information is requested by an employee, ensuring a non-blocking, high-performance user experience.

### 5. Administrative Oversight & Reporting
- Comprehensive Admin APIs for user management, role assignment, and system performance monitoring.
- Secure export functionality for statistical data and complaint logs into CSV and PDF formats for administrative analysis.

---

## 🚀 Local Backend Setup

To run the backend locally, ensure you have PHP 8.2+, Composer, and a database (MySQL/PostgreSQL) or Redis installed.

```bash
# 1. Clone the repository
git clone https://github.com/omamaMhd/Complaint_Sys.git
cd Complaint_Sys

# 2. Install PHP dependencies
composer install

# 3. Set up environment variables
cp .env.example .env
php artisan key:generate

# 4. Configure your database in the .env file, then run migrations and seeders
php artisan migrate --seed

# 5. Start the development server and the queue worker
php artisan serve
php artisan queue:work


```
## Contributors

- **omamaMhd** (Omama Mohamad)
- **doaanassan2002**
