# 🏥 DoctorChat SaaS — System Design Document

## 📋 Overview

منصة SaaS مخصصة للأطباء بتقدم给他们 AI-powered Chatbot مبني على RAG و Fine-tuning. الدكتور بيرفع ملفات PDF (أبحاث، نوتات، كتب) والسيستم بيتعلم عليها عشان يقدر يرد على أسئلة المرضى أو يساعد الدكتور في التشخيص.

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Blade + Livewire)                  │
│  Landing Page │ Login/Register │ Dashboard │ Admin Panel │ Chat UI  │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     LARAVEL BACKEND (REST API)                      │
│                                                                     │
│  ┌──────────┐  ┌───────────┐  ┌──────────┐  ┌───────────────────┐  │
│  │ Auth &   │  │ Subscription│  │ Payment  │  │  Chat & Message   │  │
│  │ Roles    │  │ Management │  │ (Paymob) │  │  Management       │  │
│  └──────────┘  └───────────┘  └──────────┘  └───────────────────┘  │
│                                                                     │
│  ┌──────────┐  ┌───────────┐  ┌──────────┐  ┌───────────────────┐  │
│  │ File     │  │ Guide/    │  │ Admin    │  │  Notification     │  │
│  │ Upload   │  │ Docs Mgmt │  │ Stats    │  │  System           │  │
│  └──────────┘  └───────────┘  └──────────┘  └───────────────────┘  │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
┌─────────────────┐ ┌──────────┐ ┌─────────────────────────┐
│   MySQL DB      │ │  Redis   │ │  RAG Service (Docker)    │
│   (Primary)     │ │  Cache & │ │  ┌───────────────────┐   │
│                 │ │  Queue   │ │  │ FastAPI / Flask    │   │
│                 │ │          │ │  │ Fine-tuned Model  │   │
│                 │ │          │ │  │ Vector DB (Qdrant)│   │
│                 │ │          │ │  │ PDF Processor     │   │
│                 │ │          │ │  └───────────────────┘   │
└─────────────────┘ └──────────┘ └─────────────────────────┘
```

---

## 👥 User Roles & Permissions

### 1. Doctor (User)
| Permission | Description |
|------------|-------------|
| ✅ Register/Login | إنشاء حساب وتسجيل دخول |
| ✅ Upload PDF | رفع ملف PDF واحد (لو رفع واحد جديد يتمسح القديم) |
| ✅ Chat with Bot | محادثة الـ Chatbot المخصص |
| ✅ View Chat History | عرض سجل المحادثات |
| ✅ Manage Subscription | عرض حالة الاشتراك |
| ❌ Manage Users | مش هيقدر يدير مستخدمين |
| ❌ Manage Guide | مش هيقدر يعدل الدليل |

### 2. Admin
| Permission | Description |
|------------|-------------|
| ✅ All Doctor Permissions | كل صلاحيات الدكتور |
| ✅ Manage Users | إضافة / تعديل / حذف المستخدمين |
| ✅ Manage Subscriptions | إضافة / تعديل / حذف اشتراكات + تاريخ البداية والنهاية |
| ✅ Manage Guide/Documentation | إضافة / تعديل / حذف صفحات الدليل |
| ✅ View Statistics | عدد المستخدمين النشطين، إحصائيات الموقع |
| ✅ Manage Files | عرض وإدارة الملفات المرفوعة |
| ✅ Admin Chatbot | Chatbot خاص بالادمن |
| ✅ Site Settings | إعدادات الموقع العامة |

---

## 📊 Database Schema (ERD)

```sql
-- ========================================
-- USERS TABLE
-- ========================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor') DEFAULT 'doctor',
    avatar VARCHAR(500) NULL,
    phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- SUBSCRIPTION PLANS TABLE
-- ========================================
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,           -- مثلاً: "Basic", "Pro", "Enterprise"
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,        -- بالجنيه المصري
    duration_days INT NOT NULL,            -- مدة الاشتراك بالأيام
    features JSON NULL,                    -- مميزات الخطة
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- USER SUBSCRIPTIONS TABLE
-- ========================================
CREATE TABLE user_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    payment_reference VARCHAR(255) NULL,   -- Paymob reference
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
);

-- ========================================
-- PAYMENT TRANSACTIONS TABLE
-- ========================================
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EGP',
    payment_method VARCHAR(50) DEFAULT 'paymob',
    paymob_order_id VARCHAR(255) NULL,
    paymob_transaction_id VARCHAR(255) NULL,
    paymob_hmac VARCHAR(500) NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    metadata JSON NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL
);

-- ========================================
-- DOCTOR PDF FILES TABLE
-- ========================================
CREATE TABLE doctor_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,    -- بالبايت
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    status ENUM('uploaded', 'processing', 'ready', 'failed') DEFAULT 'uploaded',
    rag_document_id VARCHAR(255) NULL,     -- ID من الـ RAG service
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- CHAT SESSIONS TABLE
-- ========================================
CREATE TABLE chat_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) DEFAULT 'New Chat',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- CHAT MESSAGES TABLE
-- ========================================
CREATE TABLE chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    tokens_used INT UNSIGNED NULL,
    response_time_ms INT UNSIGNED NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
);

-- ========================================
-- GUIDE / DOCUMENTATION PAGES TABLE
-- ========================================
CREATE TABLE guide_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,              -- HTML أو Markdown
    category VARCHAR(100) NULL,
    sort_order INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    meta_description TEXT NULL,
    meta_keywords VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- SITE SETTINGS TABLE
-- ========================================
CREATE TABLE site_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NULL,
    type ENUM('text', 'textarea', 'image', 'boolean', 'json') DEFAULT 'text',
    group_name VARCHAR(100) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- ACTIVITY LOG TABLE (للإحصائيات)
-- ========================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- NOTIFICATIONS TABLE
-- ========================================
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔄 Workflow Diagrams

### 1. Doctor Registration & Subscription Flow
```
Doctor Visits Landing Page
        │
        ▼
   Click "Get Started"
        │
        ▼
   Register (Name, Email, Password, Phone)
        │
        ▼
   Email Verification
        │
        ▼
   Choose Subscription Plan
        │
        ▼
   Pay via Paymob (Visa)
        │
        ├──── Payment Failed ──→ Show Error + Retry
        │
        ▼ Payment Success
   Webhook Confirmed
        │
        ▼
   Subscription Activated
        │
        ▼
   Redirect to Dashboard
        │
        ▼
   Upload PDF (First Time)
        │
        ▼
   File Sent to RAG Service (via API/Docker)
        │
        ▼
   RAG Processes & Indexes PDF
        │
        ▼
   Chatbot Ready! 🎉
```

### 2. Chat Flow
```
Doctor Opens Chat
        │
        ▼
   Loads Chat History (if any)
        │
        ├──── New Chat ──→ Create New Session
        │
        ▼
   Doctor Types Message
        │
        ▼
   Message Sent to Laravel API
        │
        ▼
   Laravel Sends to RAG Service (HTTP API)
        │
        ▼
   RAG Retrieves Relevant Chunks from Vector DB
        │
        ▼
   LLM Generates Response (with context)
        │
        ▼
   Response Saved to DB
        │
        ▼
   Displayed to Doctor
```

### 3. PDF Upload & Processing Flow
```
Doctor Clicks "Upload New PDF"
        │
        ▼
   Select PDF File (Max 50MB)
        │
        ▼
   Upload to Laravel (Validation)
        │
        ▼
   Check: Does user have existing file?
        │
        ├──── Yes ──→ Delete Old File from Storage
        │              Delete Old Document from RAG
        │
        ▼
   Save New File to Storage
        │
        ▼
   Create Record in doctor_files (status: uploaded)
        │
        ▼
   Send File to RAG Service API
        │
        ▼
   Update Status: processing
        │
        ▼
   RAG Service:
   ├── Extract Text from PDF
   ├── Chunk Text
   ├── Generate Embeddings
   └── Store in Vector DB (Qdrant)
        │
        ▼
   RAG Callbacks Laravel: Status = ready
        │
        ▼
   Doctor Notified: "Your chatbot is ready!"
```

### 4. Admin Dashboard Flow
```
Admin Login
        │
        ▼
   Dashboard Overview
   ├── Total Active Users
   ├── Revenue Statistics
   ├── Active Subscriptions
   ├── Recent Registrations
   └── System Health
        │
        ▼
   Management Sections:
   ├── 👥 Users
   │   ├── View All Users
   │   ├── Add/Edit/Delete User
   │   ├── Toggle Active/Inactive
   │   └── View User's Subscription & Files
   │
   ├── 💳 Subscriptions
   │   ├── Manage Plans (CRUD)
   │   ├── View All Subscriptions
   │   ├── Manually Assign/Extend Subscription
   │   └── View Payment History
   │
   ├── 📄 Files
   │   ├── View All Uploaded Files
   │   ├── File Status (processing/ready/failed)
   │   └── Delete File (+ RAG cleanup)
   │
   ├── 📖 Guide/Documentation
   │   ├── Add New Guide Page
   │   ├── Edit Existing Pages
   │   ├── Reorder Pages
   │   └── Publish/Unpublish
   │
   ├── 📊 Statistics
   │   ├── User Growth Chart
   │   ├── Revenue Chart
   │   ├── Chat Activity
   │   └── File Upload Stats
   │
   ├── 🤖 Admin Chatbot
   │   └── Same as Doctor's Chat
   │
   └── ⚙️ Settings
       ├── Site Name & Logo
       ├── Payment Settings
       ├── Email Templates
       └── System Configuration
```

---

## 💳 Paymob Integration Flow

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   Doctor     │    │   Laravel    │    │   Paymob     │
│   Browser    │    │   Backend    │    │   API        │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       │  1. Select Plan   │                   │
       │──────────────────>│                   │
       │                   │                   │
       │                   │  2. Create Order  │
       │                   │  (POST /orders)   │
       │                   │──────────────────>│
       │                   │                   │
       │                   │  3. Order Token   │
       │                   │<──────────────────│
       │                   │                   │
       │  4. Redirect to   │                   │
       │  Paymob Checkout  │                   │
       │<──────────────────│                   │
       │                   │                   │
       │  5. Enter Card    │                   │
       │  Details & Pay    │                   │
       │──────────────────────────────────────>│
       │                   │                   │
       │                   │  6. Webhook       │
       │                   │  (Payment Result) │
       │                   │<──────────────────│
       │                   │                   │
       │                   │  7. Verify HMAC   │
       │                   │  + Activate       │
       │                   │  Subscription     │
       │                   │                   │
       │  8. Success!      │                   │
       │<──────────────────│                   │
```

### Paymob API Endpoints Used:
1. **Auth Request** → Get auth token
2. **Order Registration** → Create payment order
3. **Payment Key** → Generate payment key
4. **Webhook** → Receive payment confirmation
5. **Transaction Inquiry** → Verify payment status

---

## 🤖 RAG Service Integration (API Contract)

### Endpoint: Upload Document
```
POST http://rag-service:8000/api/v1/documents/upload

Request:
- Content-Type: multipart/form-data
- Body: { file: PDF, user_id: int }

Response:
{
  "status": "processing",
  "document_id": "doc_abc123",
  "message": "Document uploaded successfully"
}
```

### Endpoint: Chat
```
POST http://rag-service:8000/api/v1/chat

Request:
{
  "user_id": 1,
  "message": "What are the symptoms of diabetes?",
  "session_id": "sess_xyz"
}

Response:
{
  "reply": "The symptoms of diabetes include...",
  "sources": [
    {
      "document_id": "doc_abc123",
      "page": 15,
      "relevance_score": 0.92
    }
  ],
  "tokens_used": 256,
  "response_time_ms": 1200
}
```

### Endpoint: Delete Document
```
DELETE http://rag-service:8000/api/v1/documents/{document_id}

Response:
{
  "status": "deleted",
  "message": "Document removed successfully"
}
```

### Endpoint: Health Check
```
GET http://rag-service:8000/api/v1/health

Response:
{
  "status": "healthy",
  "vector_db": "connected",
  "model_loaded": true
}
```

---

## 🛠️ Technology Stack

### Backend
| Component | Technology | Why |
|-----------|-----------|-----|
| Framework | **Laravel 11** | Elegant, secure, great ecosystem |
| Database | **MySQL 8** | Reliable, well-supported |
| Cache/Queue | **Redis** | Fast caching + queue processing |
| Authentication | **Laravel Breeze + Sanctum** | Simple auth + API tokens |
| File Storage | **Local / S3** | PDF storage |
| Real-time | **Laravel Reverb (WebSockets)** | Live chat updates |
| Queue | **Redis Queue** | Background file processing |

### Frontend
| Component | Technology | Why |
|-----------|-----------|-----|
| Templating | **Blade + Livewire** | Reactive without heavy JS |
| CSS | **Tailwind CSS** | Fast, modern styling |
| JavaScript | **Alpine.js** | Lightweight interactivity |
| Charts | **Chart.js** | Admin dashboard statistics |
| Icons | **Heroicons** | Consistent, beautiful icons |

### Infrastructure
| Component | Technology | Why |
|-----------|-----------|-----|
| Containerization | **Docker + Docker Compose** | Consistent dev/prod environments |
| Reverse Proxy | **Nginx** | High-performance web server |
| RAG Service | **Python FastAPI** (队友's part) | ML/NLP ecosystem |
| Vector DB | **Qdrant** (队友's part) | Fast vector similarity search |
| Process Manager | **Supervisor** | Queue workers management |

### Payment
| Component | Technology | Why |
|-----------|-----------|-----|
| Payment Gateway | **Paymob** | Local Egyptian payment support |
| Card Processing | **Paymob Direct Payment API** | Visa/Mastercard support |

---

## 📁 Project Structure

```
doctorchat/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── LoginController.php
│   │   │   │   └── VerificationController.php
│   │   │   ├── Doctor/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ChatController.php
│   │   │   │   ├── ChatSessionController.php
│   │   │   │   ├── FileController.php
│   │   │   │   └── SubscriptionController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── SubscriptionPlanController.php
│   │   │   │   ├── UserSubscriptionController.php
│   │   │   │   ├── FileController.php
│   │   │   │   ├── GuideController.php
│   │   │   │   ├── SettingsController.php
│   │   │   │   ├── StatisticsController.php
│   │   │   │   └── ChatController.php
│   │   │   ├── LandingController.php
│   │   │   ├── GuideController.php
│   │   │   └── Payment/
│   │   │       ├── PaymobController.php
│   │   │       └── WebhookController.php
│   │   ├── Middleware/
│   │   │   ├── AdminMiddleware.php
│   │   │   ├── DoctorMiddleware.php
│   │   │   ├── ActiveSubscriptionMiddleware.php
│   │   │   └── VerifiedEmailMiddleware.php
│   │   └── Requests/
│   │       ├── RegisterRequest.php
│   │       ├── UploadFileRequest.php
│   │       ├── SendMessageRequest.php
│   │       └── Admin/
│   │           ├── StoreUserRequest.php
│   │           ├── StorePlanRequest.php
│   │           └── StoreGuidePageRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── SubscriptionPlan.php
│   │   ├── UserSubscription.php
│   │   ├── PaymentTransaction.php
│   │   ├── DoctorFile.php
│   │   ├── ChatSession.php
│   │   ├── ChatMessage.php
│   │   ├── GuidePage.php
│   │   ├── SiteSetting.php
│   │   ├── ActivityLog.php
│   │   └── Notification.php
│   ├── Services/
│   │   ├── PaymobService.php
│   │   ├── RagService.php
│   │   ├── SubscriptionService.php
│   │   └── StatisticsService.php
│   ├── Notifications/
│   │   ├── SubscriptionExpiringNotification.php
│   │   ├── PaymentSuccessNotification.php
│   │   └── FileProcessedNotification.php
│   └── Enums/
│       ├── UserRole.php
│       ├── SubscriptionStatus.php
│       ├── PaymentStatus.php
│       └── FileStatus.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php          # Doctor layout
│       │   ├── admin.blade.php        # Admin layout
│       │   └── guest.blade.php        # Landing/Auth layout
│       ├── landing/
│       │   ├── index.blade.php        # Landing page
│       │   └── guide.blade.php        # Public guide
│       ├── auth/
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   └── verify.blade.php
│       ├── doctor/
│       │   ├── dashboard.blade.php
│       │   ├── chat/
│       │   │   ├── index.blade.php    # Chat list + history
│       │   │   └── show.blade.php     # Active chat
│       │   ├── files/
│       │   │   └── index.blade.php    # Upload & manage files
│       │   └── subscription/
│       │       ├── plans.blade.php    # Choose plan
│       │       └── status.blade.php   # Current subscription
│       ├── admin/
│       │   ├── dashboard.blade.php    # Overview + stats
│       │   ├── users/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── plans/
│       │   │   ├── index.blade.php
│       │   │   └── form.blade.php
│       │   ├── subscriptions/
│       │   │   └── index.blade.php
│       │   ├── files/
│       │   │   └── index.blade.php
│       │   ├── guide/
│       │   │   ├── index.blade.php
│       │   │   └── form.blade.php
│       │   ├── chat/
│       │   │   └── index.blade.php
│       │   ├── statistics/
│       │   │   └── index.blade.php
│       │   └── settings/
│       │       └── index.blade.php
│       └── components/
│           ├── navbar.blade.php
│           ├── sidebar.blade.php
│           ├── chat-bubble.blade.php
│           ├── stats-card.blade.php
│           └── ...
├── routes/
│   ├── web.php
│   ├── api.php
│   └── channels.php
├── docker/
│   ├── Dockerfile
│   ├── nginx.conf
│   └── supervisord.conf
├── docker-compose.yml
└── .env
```

---

## 🛣️ Routes Design

```php
// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/guide', [GuideController::class, 'index'])->name('guide.index');
Route::get('/guide/{slug}', [GuideController::class, 'show'])->name('guide.show');

// ==========================================
// AUTH ROUTES (Guest)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ==========================================
// EMAIL VERIFICATION
// ==========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/verify-email', [VerificationController::class, 'show'])->name('verification.show');
    Route::post('/verify-email', [VerificationController::class, 'verify'])->name('verification.verify');
});

// ==========================================
// DOCTOR ROUTES
// ==========================================
Route::middleware(['auth', 'doctor', 'verified', 'active.subscription'])->prefix('doctor')->name('doctor.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [Doctor\DashboardController::class, 'index'])->name('dashboard');

    // Subscription Plans (when choosing)
    Route::get('/plans', [Doctor\SubscriptionController::class, 'plans'])->name('plans');
    Route::post('/subscribe/{plan}', [Doctor\SubscriptionController::class, 'subscribe'])->name('subscribe');

    // File Management
    Route::get('/files', [Doctor\FileController::class, 'index'])->name('files.index');
    Route::post('/files/upload', [Doctor\FileController::class, 'upload'])->name('files.upload');
    Route::delete('/files/{file}', [Doctor\FileController::class, 'destroy'])->name('files.destroy');

    // Chat
    Route::get('/chat', [Doctor\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [Doctor\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/{session}', [Doctor\ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{session}/rename', [Doctor\ChatController::class, 'rename'])->name('chat.rename');
    Route::delete('/chat/{session}', [Doctor\ChatController::class, 'destroy'])->name('chat.destroy');
});

// ==========================================
// ADMIN ROUTES
// ==========================================
Route::middleware(['auth', 'admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Users Management
    Route::resource('users', Admin\UserController::class);

    // Subscription Plans
    Route::resource('plans', Admin\SubscriptionPlanController::class);

    // User Subscriptions
    Route::get('/subscriptions', [Admin\UserSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{user}/assign', [Admin\UserSubscriptionController::class, 'assign'])->name('subscriptions.assign');

    // Files
    Route::get('/files', [Admin\FileController::class, 'index'])->name('files.index');
    Route::delete('/files/{file}', [Admin\FileController::class, 'destroy'])->name('files.destroy');

    // Guide Management
    Route::resource('guide', Admin\GuideController::class);

    // Statistics
    Route::get('/statistics', [Admin\StatisticsController::class, 'index'])->name('statistics');

    // Admin Chat
    Route::get('/chat', [Admin\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [Admin\ChatController::class, 'sendMessage'])->name('chat.send');

    // Settings
    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});

// ==========================================
// PAYMENT WEBHOOKS
// ==========================================
Route::post('/paymob/webhook', [WebhookController::class, 'handle'])->name('paymob.webhook');
Route::get('/payment/callback', [PaymobController::class, 'callback'])->name('payment.callback');
Route::get('/payment/success', [PaymobController::class, 'success'])->name('payment.success');
Route::get('/payment/fail', [PaymobController::class, 'fail'])->name('payment.fail');
```

---

## 💡 Additional Features & Suggestions

### 🔥 Must-Have Enhancements

1. **Email Notifications**
   - Subscription expiring reminder (before 3 days)
   - Payment confirmation
   - File processing complete
   - Welcome email on registration

2. **Rate Limiting**
   - Limit chat messages per plan (e.g., Basic: 100/month, Pro: unlimited)
   - Limit file upload size per plan

3. **PDF Preview**
   - Let doctors preview their uploaded PDF before confirming

4. **Search in Chat History**
   - Full-text search across all chat sessions

5. **Export Chat**
   - Export chat as PDF or text file

### 🚀 Nice-to-Have Features

6. **Multi-Language Support**
   - Arabic + English (i18n)
   - RTL support for Arabic

7. **Dark Mode**
   - Doctor dashboard dark theme toggle

8. **Mobile Responsive**
   - Fully responsive design (mobile-first)

9. **API for Mobile App**
   - RESTful API versioned (v1) for future mobile app

10. **Analytics Dashboard for Admin**
    - User growth over time (Chart.js)
    - Revenue tracking
    - Most used chatbot features
    - File upload statistics

11. **Referral System**
    - Doctors invite other doctors
    - Discount on subscription

12. **Custom Branding**
    - Doctors can customize chatbot appearance (colors, logo)

13. **Batch PDF Upload**
    - Upload multiple PDFs at once (with plan restriction)

14. **Chatbot Personality**
    - Admin can set chatbot system prompt/personality

15. **Usage Metering**
    - Track API calls, tokens used, response times
    - Show usage stats to each doctor

16. **Audit Log**
    - Track all admin actions
    - Security and compliance

17. **Backup System**
    - Automated database backups
    - File backup to cloud storage

18. **Health Monitoring**
    - Uptime monitoring
    - RAG service health check dashboard
    - Alert system for downtime

### 🔐 Security Enhancements

19. **Two-Factor Authentication (2FA)**
    - For admin accounts at minimum

20. **IP Whitelisting for Admin**
    - Restrict admin access to specific IPs

21. **CSRF Protection on Webhooks**
    - HMAC verification for Paymob webhooks

22. **File Type Validation**
    - Validate PDF files server-side (magic bytes, not just extension)

23. **Encryption at Rest**
    - Encrypt sensitive data (payment info, API keys)

---

## 🐳 Docker Setup

```yaml
# docker-compose.yml
version: '3.8'

services:
  # Laravel App
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: doctorchat-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - doctorchat
    depends_on:
      - db
      - redis

  # Nginx
  nginx:
    image: nginx:alpine
    container_name: doctorchat-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - doctorchat
    depends_on:
      - app

  # MySQL
  db:
    image: mysql:8.0
    container_name: doctorchat-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - doctorchat
    ports:
      - "3306:3306"

  # Redis
  redis:
    image: redis:alpine
    container_name: doctorchat-redis
    restart: unless-stopped
    networks:
      - doctorchat
    ports:
      - "6379:6379"

  # Queue Worker
  queue:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: doctorchat-queue
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
    volumes:
      - .:/var/www
    networks:
      - doctorchat
    depends_on:
      - app
      - redis

  # RAG Service (队友's part)
  rag-service:
    build:
      context: ./rag-service
      dockerfile: Dockerfile
    container_name: doctorchat-rag
    restart: unless-stopped
    ports:
      - "8001:8000"
    environment:
      - VECTOR_DB_URL=http://qdrant:6333
      - MODEL_PATH=/models/fine-tuned
    volumes:
      - rag_data:/app/data
      - model_data:/models
    networks:
      - doctorchat
    depends_on:
      - qdrant

  # Qdrant Vector DB (队友's part)
  qdrant:
    image: qdrant/qdrant:latest
    container_name: doctorchat-qdrant
    restart: unless-stopped
    ports:
      - "6333:6333"
      - "6334:6334"
    volumes:
      - qdrant_data:/qdrant/storage
    networks:
      - doctorchat

volumes:
  db_data:
  rag_data:
  model_data:
  qdrant_data:

networks:
  doctorchat:
    driver: bridge
```

---

## 📐 API Design Summary

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/register` | Register new doctor |
| POST | `/api/v1/login` | Login |
| POST | `/api/v1/logout` | Logout |
| POST | `/api/v1/forgot-password` | Reset password |

### Doctor
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dashboard` | Dashboard data |
| GET | `/api/v1/files` | List files |
| POST | `/api/v1/files/upload` | Upload PDF |
| DELETE | `/api/v1/files/{id}` | Delete file |
| GET | `/api/v1/chat/sessions` | List chat sessions |
| POST | `/api/v1/chat/sessions` | Create new session |
| POST | `/api/v1/chat/send` | Send message |
| GET | `/api/v1/subscription` | Current subscription |

### Admin
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/dashboard` | Admin dashboard |
| GET | `/api/v1/admin/users` | List users |
| POST | `/api/v1/admin/users` | Create user |
| PUT | `/api/v1/admin/users/{id}` | Update user |
| DELETE | `/api/v1/admin/users/{id}` | Delete user |
| CRUD | `/api/v1/admin/plans` | Manage plans |
| GET | `/api/v1/admin/statistics` | Site statistics |
| CRUD | `/api/v1/admin/guide` | Manage guide pages |

---

## 📅 Development Timeline (Suggested)

### Phase 1: Foundation (Week 1-2)
- [x] Project setup (Laravel + Docker)
- [ ] Database migrations
- [ ] Authentication (register, login, verify)
- [ ] Role-based middleware
- [ ] Landing page

### Phase 2: Core Features (Week 3-4)
- [ ] Subscription plans CRUD (Admin)
- [ ] Paymob integration
- [ ] PDF upload system
- [ ] Chat sessions & messages

### Phase 3: RAG Integration (Week 5)
- [ ] RAG service API integration
- [ ] PDF processing pipeline
- [ ] Chat with RAG
- [ ] Real-time updates (Reverb)

### Phase 4: Admin Dashboard (Week 6)
- [ ] Admin dashboard with stats
- [ ] User management
- [ ] Guide management
- [ ] Site settings

### Phase 5: Polish & Deploy (Week 7)
- [ ] Email notifications
- [ ] Testing
- [ ] Performance optimization
- [ ] Production deployment
- [ ] Documentation

---

## 🎯 Environment Variables (.env)

```env
APP_NAME=DoctorChat
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=doctorchat
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

# Paymob
PAYMOB_API_KEY=your_api_key
PAYMOB_integration_id=your_integration_id
PAYMOB_IFrame_ID=your_iframe_id
PAYMOB_HMAC_SECRET=your_hmac_secret
PAYMOB_BASE_URL=https://accept.paymob.com/api

# RAG Service
RAG_SERVICE_URL=http://rag-service:8000
RAG_API_KEY=your_rag_api_key

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

---

*Generated with Codebuff 🤖*
