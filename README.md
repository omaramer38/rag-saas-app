# 🩺 DoctorChat — Medical AI SaaS Platform

A multi-tenant SaaS platform that enables doctors to upload PDF research papers and chat with an AI assistant trained on their documents using Retrieval-Augmented Generation (RAG).

---

## 📋 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Credentials](#credentials)
- [Environment Variables](#environment-variables)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [RAG Pipeline](#rag-pipeline)
- [Multi-Tenant Isolation](#multi-tenant-isolation)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)
- [Tech Stack](#tech-stack)

---

## 🏗️ Architecture Overview

```
┌──────────────────────────────┐         ┌──────────────────────────────┐
│       Laravel 12 App         │         │      Python RAG Server       │
│       (Port 8000)            │  HTTP   │      (Port 5000)             │
│                              │────────▶│                              │
│ • Auth (Laravel Breeze)      │         │ • Flask REST API             │
│ • Admin Dashboard            │         │ • Cohere embed-v4.0 (1536d) │
│ • Doctor Dashboard           │         │ • Cohere Rerank multilingual │
│ • File Upload                │         │ • Qdrant Vector DB (local)  │
│ • Chat Interface             │         │ • PDF Parsing + OCR          │
│ • Subscription Management    │         │ • Semantic Chunking          │
│ • Paymob Payment Gateway     │         │ • Multi-tenant Collections   │
└──────────────────────────────┘         └──────────────────────────────┘
           │                                        │
           ▼                                        ▼
    ┌──────────────┐                      ┌──────────────────┐
    │    MySQL     │                      │  Qdrant (local)  │
    │  Database    │                      │  data/qdrant_db/ │
    └──────────────┘                      └──────────────────┘
```

### How It Works

1. **Doctor** registers → subscribes via Paymob → gets access
2. **Uploads PDF** → Laravel sends file to Python RAG server
3. RAG server **parses → chunks → embeds → indexes** in Qdrant
4. Doctor **chats** → query is embedded → vector search → reranked → sources returned
5. **Admin** manages users, subscriptions, guide pages, and has their own chatbot

---

## 📦 Prerequisites

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 8.2+ | Laravel backend |
| Python | 3.12+ | RAG server |
| MySQL | 8.0+ | Database |
| Composer | 2.x | PHP packages |
| pip | Latest | Python packages |

### Optional (for Docker)
- Docker Desktop installed and running

---

## 🚀 Quick Start

### Step 1: Clone the Repository

```bash
git clone https://github.com/omaramer38/rag-saas-app.git
cd rag-saas-app
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doctorchat
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4: Database Setup

```bash
# Create the database first
mysql -u root -e "CREATE DATABASE IF NOT EXISTS doctorchat;"

# Run migrations
php artisan migrate

# Seed default data (admin user, demo doctor, plans, guide pages)
php artisan db:seed
```

### Step 5: Install Python Dependencies

```bash
cd rag-service
pip install -r requirements.txt
```

### Step 6: Set Up RAG Environment

Create/edit `rag-service/.env`:

```env
COHERE_API_KEY=your_cohere_api_key_here
```

> **Get a free Cohere API key at:** https://dashboard.cohere.com/api-keys
> 
> You need the **embed-v4.0** and **rerank-multilingual-v3.0** models.
> Free tier is sufficient for development.

### Step 7: Start the Services

Open **two terminals**:

**Terminal 1 — Laravel (Port 8000):**
```bash
# From project root
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — RAG Server (Port 5000):**
```bash
cd rag-service
python multi_tenant_server_local.py
```

### Step 8: Verify Both Services

```bash
# Check Laravel
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/
# Should return: 200

# Check RAG Server
curl -s http://localhost:5000/api/v1/health
# Should return: {"status":"healthy",...}
```

### Step 9: Access the Application

| URL | Description |
|-----|-------------|
| http://127.0.0.1:8000 | Landing Page |
| http://127.0.0.1:8000/login | Login Page |
| http://127.0.0.1:8000/register | Registration Page |
| http://127.0.0.1:8000/admin/dashboard | Admin Dashboard |
| http://127.0.0.1:8000/doctor/dashboard | Doctor Dashboard |

---

## 🔑 Credentials

### Default Accounts (created by seeder)

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| **Admin** | admin@doctorchat.com | `password` | Full admin access |
| **Doctor** | doctor@doctorchat.com | `password` | Demo doctor account |

> ⚠️ **Change these passwords before production deployment!**

### Creating New Users

Doctors register through the website at `/register`. After registration:
1. They are assigned `role = 'doctor'`
2. They need an active subscription to access features
3. Admin can manually assign subscriptions from the admin dashboard

### Creating New Admins

```bash
php artisan tinker
```
```php
App\Models\User::create([
    'name' => 'New Admin',
    'email' => 'newadmin@example.com',
    'password' => Hash::make('secure_password'),
    'role' => 'admin',
    'is_active' => true,
    'email_verified_at' => now(),
]);
```

---

## ⚙️ Environment Variables

### Laravel `.env` (Project Root)

```env
# App
APP_NAME=DoctorChat
APP_ENV=local
APP_URL=http://127.0.0.1:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doctorchat
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# Paymob Payment Gateway
PAYMOB_API_KEY=your_paymob_api_key
PAYMOB_INTEGRATION_ID=your_integration_id
PAYMOB_IFRAME_ID=your_iframe_id
PAYMOB_HMAC_SECRET=your_hmac_secret
PAYMOB_BASE_URL=https://accept.paymob.com/api
```

### RAG Server `rag-service/.env`

```env
# Cohere API (REQUIRED)
COHERE_API_KEY=your_cohere_api_key_here

# Qdrant (auto-configured for local mode)
QDRANT_HOST=localhost
QDRANT_PORT=6333
```

---

## 🗄️ Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts (admin/doctor roles) |
| `subscription_plans` | Available subscription plans |
| `user_subscriptions` | Active subscriptions per user |
| `doctor_files` | Uploaded PDF files + processing metrics |
| `chat_sessions` | Chat conversation sessions |
| `chat_messages` | Individual chat messages with sources |
| `guide_pages` | Documentation/guide pages |
| `payment_transactions` | Paymob payment records |
| `activity_logs` | Admin action audit log |
| `site_settings` | Key-value site configuration |

### User Roles

```php
// Database column: users.role
'admin'  // Full dashboard access, user management, statistics
'doctor' // File upload, chatbot, subscription management
```

### Subscription Flow

```
Doctor registers → Views plans (/doctor/plans) → Subscribes via Paymob
→ Payment webhook confirms → Subscription activated
→ Doctor can upload files and use chatbot
```

---

## 🔌 API Endpoints

### RAG Server (Port 5000)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/health` | Health check |
| GET | `/api/v1/stats` | Collection statistics |
| POST | `/api/v1/documents/upload` | Upload & process PDF |
| DELETE | `/api/v1/documents/{user_id}` | Delete user's documents |
| POST | `/api/v1/chat` | Send chat message |
| GET | `/api/v1/user/{user_id}/metrics` | Retrieval quality metrics |
| GET | `/api/v1/user/{user_id}/stats` | User's collection stats |
| GET | `/api/v1/user/{user_id}/progress` | Upload processing progress |

### Chat Request Example

```bash
curl -X POST http://localhost:5000/api/v1/chat \
  -H "Content-Type: application/json" \
  -d '{"user_id": 3, "message": "What is metformin?", "top_k": 5}'
```

### Chat Response Example

```json
{
  "answer": "Based on the document...",
  "sources": [
    {
      "index": 1,
      "chunk_id": "chk_abc123_0002",
      "content": "Metformin is an oral hypoglycaemic...",
      "score": 0.751,
      "page_start": 4,
      "page_end": 4,
      "chapter": "Section 3.1: Pharmacological Treatment",
      "section": "",
      "document_title": "WHO Guidelines..."
    }
  ],
  "chunks_used": 5,
  "lang": "english",
  "retrieval_info": {
    "collection": "user_3_documents",
    "similarity_threshold": 0.3,
    "top_k": 5
  }
}
```

### Upload Request Example

```bash
curl -X POST http://localhost:5000/api/v1/documents/upload \
  -F "user_id=3" \
  -F "file=@research_paper.pdf"
```

---

## 🧠 RAG Pipeline

### Architecture

```
User Query
    │
    ▼
┌─────────────────────────┐
│  Arabic Detection       │  ← Detect language
│  + Medical Term         │  ← Expand Arabic → English
│    Expansion            │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Cohere embed-v4.0      │  ← 1536-dimension embeddings
│  (Query Embedding)      │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Qdrant Vector Search   │  ← Top 20 candidates from user's collection
│  (Dense Retrieval)      │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Cohere Rerank          │  ← Multilingual reranker (v3.0)
│  multilingual-v3.0      │  ← Reranks top 20 → top 5
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Source Construction    │  ← Format sources with page/section info
└────────────┬────────────┘
             │
             ▼
      Response + Sources
```

### Document Processing Pipeline

```
PDF Upload
    │
    ▼
┌─────────────────────────┐
│  1. Parse PDF           │  ← PyMuPDF + OCR for scanned pages
│     (OCR, tables,       │  ← Extract text, tables, figures
│      figures)           │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  2. Clean Text          │  ← Normalize whitespace, fix encoding
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  3. Build Hierarchy     │  ← Chapter → Section → Subsection
│     + Fix Labels        │  ← Correct misidentified sections
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  4. Semantic Chunking   │  ← 100-600 token chunks
│     + Filtering         │  ← Remove TOC, headers, noise
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  5. Embed               │  ← Cohere embed-v4.0 (1536d)
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  6. Index in Qdrant     │  ← Per-user collection
│     user_{id}_documents │
└─────────────────────────┘
```

### RAG Performance Metrics

| Metric | Value |
|--------|-------|
| Precision@1 | 80-100% |
| MRR | 95% |
| Hit Rate | 100% |
| Latency | 3-4 seconds |
| Embedding Model | Cohere embed-v4.0 |
| Embedding Dimension | 1536 |
| Reranker | Cohere rerank-multilingual-v3.0 |

### Supported Queries

- **English:** "What is metformin?", "What are the types of diabetes?"
- **Arabic:** "ايه هو مرض السكري؟", "ما هو الميتفورمين؟"
- Arabic queries are auto-expanded with English medical terms for better retrieval

---

## 🔒 Multi-Tenant Isolation

Each doctor's data is **completely isolated**:

```
User 3 (Dr. Ahmed)
    ├── Collection: user_3_documents
    ├── Chunks: chk_e1bea238_0001, chk_e1bea238_0002, ...
    ├── Embeddings: 1536-dim vectors
    ├── Chat Sessions: session IDs
    └── Files: uploaded PDFs

User 5 (Dr. Sara)
    ├── Collection: user_5_documents  ← COMPLETELY SEPARATE
    ├── Chunks: chk_xxxxx_0001, ...
    ├── Embeddings: separate vectors
    ├── Chat Sessions: separate sessions
    └── Files: separate PDFs
```

**Key rules:**
- Retrieval NEVER searches another user's vectors
- Uploading a new file replaces only that user's documents
- Chat answers come ONLY from that user's uploaded content
- No global vector collection exists

---

## 📁 Project Structure

```
rag-saas-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                    # Admin controllers
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── SubscriptionPlanController.php
│   │   │   │   ├── UserSubscriptionController.php
│   │   │   │   ├── GuideController.php
│   │   │   │   ├── StatisticsController.php
│   │   │   │   ├── ChatController.php
│   │   │   │   └── SettingsController.php
│   │   │   ├── Auth/                     # Laravel Breeze auth
│   │   │   ├── Doctor/                   # Doctor controllers
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ChatController.php
│   │   │   │   ├── FileController.php
│   │   │   │   └── SubscriptionController.php
│   │   │   └── Payment/                  # Paymob integration
│   │   │       ├── PaymobController.php
│   │   │       └── WebhookController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php       # Admin role check
│   │       ├── DoctorMiddleware.php      # Doctor role check
│   │       └── ActiveSubscriptionMiddleware.php
│   ├── Models/
│   │   ├── User.php                      # User with roles
│   │   ├── SubscriptionPlan.php          # Plans
│   │   ├── UserSubscription.php          # Active subscriptions
│   │   ├── DoctorFile.php                # Uploaded files + metrics
│   │   ├── ChatSession.php               # Chat conversations
│   │   ├── ChatMessage.php               # Messages with sources
│   │   ├── GuidePage.php                 # Guide/documentation
│   │   └── SiteSetting.php               # Key-value settings
│   └── Services/
│       └── RagService.php                # HTTP client to RAG server
│
├── resources/views/
│   ├── admin/                            # Admin dashboard views
│   ├── doctor/                           # Doctor dashboard views
│   │   ├── chat/                         # Chat interface
│   │   ├── files/                        # File upload + metrics
│   │   └── subscription/                 # Plans page
│   ├── layouts/
│   │   ├── app.blade.php                 # Main layout (Tailwind)
│   │   └── guest.blade.php              # Guest layout
│   ├── landing.blade.php                 # Landing page
│   └── guide/                            # Guide pages
│
├── rag-service/                          # Python RAG Server
│   ├── multi_tenant_server_local.py      # Main Flask server (1100 lines)
│   ├── src/rag_system/                   # Friend's RAG code (DO NOT MODIFY)
│   │   ├── config/                       # Configuration
│   │   ├── embeddings/                   # Cohere + FastEmbed
│   │   ├── ingestion/                    # Parser, cleaner, chunker
│   │   ├── retriever/                    # Search, pipeline, evaluator
│   │   └── shared/                       # Shared models
│   ├── gold_dataset.json                 # Evaluation gold standard
│   ├── evaluate.py                       # Retrieval evaluation script
│   ├── requirements.txt                  # Python dependencies
│   ├── .env                              # RAG server env vars
│   └── data/qdrant_db/                   # Local Qdrant database
│
├── routes/
│   ├── web.php                           # All web routes
│   └── auth.php                          # Auth routes (Breeze)
│
├── database/
│   ├── migrations/                       # Database schema
│   └── seeders/
│       ├── AdminSeeder.php               # Creates admin + demo doctor + plans
│       └── DatabaseSeeder.php
│
├── .env                                  # Laravel environment
├── docker-compose.yml                    # Docker setup
└── README.md                             # This file
```

---

## 🐳 Docker Setup (Alternative)

If you prefer Docker:

```bash
docker-compose up -d
```

This starts:
- **Laravel** on port 8000
- **RAG Server** on port 5000
- **Qdrant** on port 6333 (optional, local mode doesn't need it)

> **Note:** The local mode (`multi_tenant_server_local.py`) runs Qdrant in-process and does NOT require Docker for Qdrant. Docker is only needed for the full stack deployment.

---

## 🛠️ Troubleshooting

### "Route [dashboard] not defined"

**Cause:** Auth controllers redirect to `dashboard` route that doesn't exist.

**Fix:** Auth controllers should redirect to the correct route based on user role:
```php
// In RegisteredUserController.php, AuthenticatedSessionController.php, etc.
if ($request->user()->isAdmin()) {
    return redirect(route('admin.dashboard'));
}
return redirect(route('doctor.plans'));
```

### "Too many redirects" / ERR_TOO_MANY_REDIRECTS

**Cause:** `ActiveSubscriptionMiddleware` redirects to plans page, but plans page also requires subscription.

**Fix:** Plans page route must be OUTSIDE the `active.subscription` middleware group:
```php
// Plans page accessible WITHOUT active subscription
Route::middleware(['auth', 'doctor', 'verified'])
    ->get('/doctor/plans', [SubscriptionController::class, 'plans']);
```

### RAG Server returns 404 for chat

**Cause:** Old RAG server running without latest code.

**Fix:**
```bash
# Kill old server
netstat -aon | grep ":5000" | grep "LISTEN"
taskkill //F //PID <PID>

# Restart
cd rag-service
python multi_tenant_server_local.py
```

### "Method Not Allowed" for logout

**Cause:** Logout is a POST route, not GET.

**Fix:** Always use a form with `@csrf` for logout:
```html
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>
```

### Upload succeeds but chat returns "No data"

**Cause:** Collection might be empty or server restarted.

**Fix:**
```bash
# Check collection
curl -s http://localhost:5000/api/v1/user/3/stats

# Re-upload if needed
curl -X POST http://localhost:5000/api/v1/documents/upload \
  -F "user_id=3" -F "file=@your_file.pdf"
```

### Arabic queries return low scores

**Cause:** Reranker uses English-only model.

**Fix:** Ensure `rerank-multilingual-v3.0` is used (already configured). The system auto-expands Arabic queries with English medical terms.

### Cohere API errors

**Cause:** API key invalid or rate limited.

**Fix:**
1. Check `rag-service/.env` has valid `COHERE_API_KEY`
2. Get free key at https://dashboard.cohere.com/api-keys
3. Free tier: 1000 API calls/month

### Server timeout on chat

**Cause:** Cohere API slow or rate limited.

**Fix:** The system has a 30-second timeout. If consistently slow:
1. Check internet connection
2. Try different Cohere model
3. Reduce `top_k` parameter

---

## 🛠️ Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel | 12.x |
| **PHP** | PHP | 8.2+ |
| **Frontend** | Blade + Tailwind CSS + Alpine.js | Latest |
| **Auth** | Laravel Breeze | Latest |
| **RAG Server** | Python + Flask | 3.12+ / 3.x |
| **Embeddings** | Cohere embed-v4.0 | 1536 dimensions |
| **Reranker** | Cohere rerank-multilingual-v3.0 | v3.0 |
| **Vector Database** | Qdrant | Embedded mode |
| **Database** | MySQL | 8.0+ |
| **PDF Parsing** | PyMuPDF + pdfplumber | Latest |
| **OCR** | Tesseract | For scanned pages |
| **Payment** | Paymob | Visa/Mastercard |
| **Caching** | Database (sessions + cache) | Laravel default |

---

## 📊 Subscription Plans (Default)

| Plan | Price (EGP) | Duration | Features |
|------|-------------|----------|----------|
| **Basic** | 99 | 30 days | 1 PDF, 100 chats, email support |
| **Pro** | 199 | 30 days | 3 PDFs, unlimited chat, priority support |
| **Enterprise** | 499 | 30 days | Unlimited everything, API access, team management |

---

## 🔐 Security Notes

- All user data is isolated per-tenant in separate Qdrant collections
- File uploads are stored in `storage/app/private/doctor-files/`
- Admin can manage all users and subscriptions
- Paymob handles payment processing (PCI compliant)
- Session-based authentication with Laravel Breeze

---

## 📄 License

MIT License

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test both Laravel and RAG server
5. Submit a pull request

---

## 📞 Support

For issues or questions:
- Check the [Troubleshooting](#troubleshooting) section
- Open an issue on GitHub
- Contact: support@doctorchat.com
