# 🚀 Deployment Guide — Free & Paid Options

## Free Options (For Development/Testing)

### 1. Oracle Cloud Free Tier (BEST FREE OPTION)
- **2 VMs** with 1GB RAM each (Always Free)
- **200GB** storage
- **Ubuntu 22.04** pre-installed
- **Full root access**
- **Docker works perfectly**

**How to deploy:**
```bash
# 1. Sign up at cloud.oracle.com
# 2. Create VM Instance (Ubuntu 22.04)
# 3. SSH into your VM
ssh ubuntu@your-ip -i your-key.pem

# 4. Install Docker
sudo apt update
sudo apt install -y docker.io docker-compose
sudo usermod -aG docker $USER
newgrp docker

# 5. Clone your project
git clone https://github.com/your-repo/doctorchat.git
cd doctorchat

# 6. Set up environment
cp .env.example .env
# Edit .env with your settings

# 7. Run with Docker
docker-compose up -d

# 8. Access your app
# http://your-server-ip:8000
```

### 2. Google Cloud Free Tier
- **1 e2-micro** instance (30 days free trial)
- $300 free credit for 90 days
- Docker works perfectly

### 3. AWS Free Tier
- **t2.micro** instance (12 months free)
- 750 hours/month
- Docker works perfectly

### 4. Railway.app (Easiest)
- Free tier: $5 credit/month
- Docker support
- Auto-deploy from GitHub
- Custom domains

### 5. Render.com
- Free tier available
- Docker support
- Auto-deploy from GitHub

---

## Paid Options (For Production)

### 1. DigitalOcean ($4-12/month)
- **Droplets**: Starting at $4/month
- 1GB RAM, 25GB SSD
- Docker pre-installed option
- Easy management

### 2. Vultr ($2.50-5/month)
- **Cloud Compute**: Starting at $2.50/month
- 1GB RAM, 25GB SSD
- High performance

### 3. Hetzner (€3.79/month)
- **Cloud Servers**: Starting at €3.79/month
- 2GB RAM, 40GB SSD
- Best value for money

### 4. Linode/Akamai ($5/month)
- **Nanode**: 1GB RAM, 25GB SSD
- Good performance

---

## Recommended Setup for Your Project

### Development (Free):
```
Oracle Cloud Free Tier
├── Laravel App (port 8000)
├── MySQL (port 3306)
├── Redis (port 6379)
├── RAG Server (port 5000)
└── Qdrant (port 6333)
```

### Production (Paid - $5-10/month):
```
DigitalOcean/Vultr Droplet
├── Laravel App (port 8000)
├── MySQL (port 3306)
├── Redis (port 6379)
├── RAG Server (port 5000)
└── Qdrant (port 6333)
```

---

## Docker Compose for Production

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    build: .
    restart: always
    ports:
      - "80:8000"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=db
      - DB_DATABASE=doctorchat
      - DB_USERNAME=root
      - DB_PASSWORD=${DB_PASSWORD}
    depends_on:
      - db
      - redis
      - rag-server
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx.prod.conf:/etc/nginx/conf.d/default.conf
      - ./storage:/var/www/storage
    depends_on:
      - app
    networks:
      - app-network

  db:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: doctorchat
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - app-network

  redis:
    image: redis:alpine
    restart: always
    networks:
      - app-network

  queue:
    build: .
    restart: always
    command: php artisan queue:work redis --sleep=3 --tries=3
    depends_on:
      - app
      - redis
    networks:
      - app-network

  qdrant:
    image: qdrant/qdrant:latest
    restart: always
    ports:
      - "6333:6333"
    volumes:
      - qdrant_data:/qdrant/storage
    networks:
      - app-network

  rag-server:
    build: ./rag-service
    restart: always
    ports:
      - "5000:5000"
    environment:
      - QDRANT_HOST=qdrant
      - QDRANT_PORT=6333
    depends_on:
      - qdrant
    networks:
      - app-network

volumes:
  db_data:
  qdrant_data:

networks:
  app-network:
    driver: bridge
```

---

## SSL/HTTPS Setup (Free)

### Let's Encrypt (Free SSL)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com

# Auto-renew
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## Quick Start Commands

### Development:
```bash
docker-compose up -d
# Access: http://localhost:8000
```

### Production:
```bash
docker-compose -f docker-compose.prod.yml up -d
# Access: http://your-server-ip
```

### With SSL:
```bash
docker-compose -f docker-compose.prod.yml up -d
sudo certbot --nginx -d yourdomain.com
```

---

## Cost Breakdown

| Option | Cost | RAM | Storage | Best For |
|--------|------|-----|---------|----------|
| Oracle Free | $0 | 1GB | 200GB | Development |
| Railway | $0-5 | 512MB | 1GB | Testing |
| DigitalOcean | $4/mo | 1GB | 25GB | Small Production |
| Vultr | $2.50/mo | 1GB | 25GB | Budget Production |
| Hetzner | €3.79/mo | 2GB | 40GB | Best Value |

---

## My Recommendation

**Start with Oracle Cloud Free Tier:**
- ✅ Completely free
- ✅ Full root access
- ✅ Docker works perfectly
- ✅ Good enough for 30 users
- ✅ Upgrade later if needed

**When you grow (100+ users):**
- Switch to DigitalOcean/Hetzner ($4-5/month)
- Or use Railway for easy management
