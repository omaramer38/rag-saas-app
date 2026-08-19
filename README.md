# 🩺 DoctorChat - RAG SaaS Application

A medical AI SaaS platform with Retrieval-Augmented Generation (RAG) for doctors to upload PDF research papers and chat with an AI assistant trained on their documents.

## 🏗️ Architecture

```
┌─────────────────────┐     ┌─────────────────────┐
│   Laravel 12 App    │────▶│   Python RAG Server  │
│   (Port 8000)       │     │   (Port 5000)        │
│                     │     │                      │
│ • Auth (Breeze)     │     │ • Flask API          │
│ • Admin Dashboard   │     │ • FastEmbed          │
│ • Doctor Dashboard  │     │ • Qdrant (embedded)  │
│ • File Upload       │     │ • PDF Parsing        │
│ • Chat Interface    │     │ • Vector Search      │
│ • Paymob Payment    │     │ • Multi-tenant       │
└─────────────────────┘     └─────────────────────┘
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Python 3.12+
- MySQL
- Composer
- pip (Python package manager)

### 1. Clone & Install PHP Dependencies
```bash
git clone https://github.com/omaramer38/rag-saas-app.git
cd rag-saas-app
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doctorchat
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 4. Install Python Dependencies
```bash
cd rag-service
pip install -r requirements.txt
```

### 5. Start Services

**Terminal 1 - Laravel:**
```bash
cd ..  # back to project root
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 - RAG Server:**
```bash
cd rag-service
python multi_tenant_server_local.py
```

### 6. Access the App
- **Landing Page:** http://127.0.0.1:8000
- **Admin Dashboard:** http://127.0.0.1:8000/admin/dashboard
- **Doctor Dashboard:** http://127.0.0.1:8000/doctor/dashboard

### Default Credentials
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@doctorchat.com | password |
| Doctor | doctor@doctorchat.com | password |

## 📁 Project Structure

```
doctorchat/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # Admin controllers
│   │   ├── Auth/           # Authentication
│   │   ├── Doctor/         # Doctor controllers
│   │   └── Payment/        # Paymob integration
│   ├── Models/             # Eloquent models
│   ├── Enums/              # Status enums
│   ├── Middleware/          # Custom middleware
│   └── Services/
│       └── RagService.php  # RAG API client
├── resources/views/        # Blade templates
│   ├── admin/              # Admin dashboard views
│   ├── doctor/             # Doctor dashboard views
│   └── layouts/            # Layout templates
├── rag-service/            # Python RAG server
│   ├── multi_tenant_server_local.py  # Main server
│   ├── src/rag_system/     # RAG pipeline code
│   └── requirements.txt    # Python dependencies
├── routes/                 # Laravel routes
├── database/               # Migrations & seeders
└── docker-compose.yml      # Docker setup
```

## 🔧 Key Features

### For Doctors
- 📤 **Upload PDF** - Drag & drop research papers
- 📊 **Processing Metrics** - Real-time progress + quality report
- 🎯 **Retrieval Metrics** - Recall, Precision, F1, nDCG scores
- 💬 **AI Chat** - Ask questions about uploaded documents
- 📎 **Source Citations** - Every answer shows source with page numbers

### For Admin
- 👥 **User Management** - Add/edit/delete doctors
- 💳 **Subscription Plans** - Create and manage pricing
- 📋 **Assign Subscriptions** - Manually assign plans to doctors
- 📖 **Guide Management** - Create documentation pages
- 🤖 **Admin Chat** - Admin also has AI chatbot
- 📊 **Dashboard Stats** - Users, subscriptions, revenue

### Technical
- 🔐 **Multi-tenant Isolation** - Each doctor's data is separate
- 🚀 **Background Processing** - File processing doesn't block UI
- 💾 **Server-side Caching** - Redis/File cache for performance
- 🌐 **Browser Caching** - HTTP headers + Service Worker
- 📱 **Responsive Design** - Works on all devices

## 🐳 Docker Setup

```bash
docker-compose up -d
```

This starts:
- Laravel app on port 8000
- RAG server on port 5000
- Qdrant on port 6333

## 📊 RAG Pipeline

1. **Parse PDF** - OCR, tables, figures extraction
2. **Clean Text** - Normalize whitespace, fix hyphenation
3. **Build Hierarchy** - Chapter → Section → Subsection
4. **Semantic Chunking** - 100-600 token chunks
5. **Embedding** - FastEmbed (bge-small-en-v1.5, 384d)
6. **Vector Indexing** - Qdrant collections per user
7. **Search + Rerank** - Cosine similarity with dedup

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade, Tailwind CSS, Alpine.js |
| RAG Server | Python, Flask, FastEmbed |
| Vector DB | Qdrant (embedded mode) |
| Database | MySQL |
| Payment | Paymob |
| Auth | Laravel Breeze |

## 📄 License

MIT License
