# AI Call Center - Full Server Deployment Guide (Fresh Server)

This guide covers the full setup of the AI Call Center system on a fresh **Ubuntu 22.04 / 24.04** server.

---

## 1. System Update & Prerequisites
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget unzip zip software-properties-common build-essential
```

## 2. Install PHP 8.3 & Extensions
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-redis php8.3-intl php8.3-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 3. Install MySQL & Redis
```bash
sudo apt install -y mysql-server redis-server
sudo systemctl enable --now mysql
sudo systemctl enable --now redis-server

# Secure MySQL and create database
# sudo mysql_secure_installation
# sudo mysql -u root -e "CREATE DATABASE laravel; CREATE USER 'aiuser'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON laravel.* TO 'aiuser'@'localhost'; FLUSH PRIVILEGES;"
```

## 4. Install Node.js & PM2
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm install -g pm2
```

## 5. Install Python 3 & Dependencies (for Bridge)
```bash
sudo apt install -y python3-pip python3-venv
```

## 6. Project Setup
```bash
cd /var/www
git clone https://github.com/mahadiarif/ai-call-center.git
cd ai-call-center/ai-call-center-repo

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Laravel Install
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Build Assets
npm install
npm run build
```

## 7. Python Bridge Configuration
```bash
cd middleware
python3 -m venv venv
source venv/bin/activate
pip install websockets numpy google-auth google-api-python-client google-auth-oauthlib aiomysql redis uvicorn fastapi pydantic python-dotenv
```

## 8. Run Services with PM2
```bash
# Bridge API (FastAPI)
pm2 start "uvicorn bridge_api:app --host 127.0.0.1 --port 8001" --name bridge_api

# Gemini Asterisk Bridge
pm2 start "python3 gemini_asterisk_bridge.py" --name gemini_bridge

pm2 save
pm2 startup
```

## 9. Asterisk Setup (Brief)
Install Asterisk and configure `extensions.conf` to point to the AudioSocket:
```ini
[ivr-ai]
exten => s,1,Answer()
 same => n,AudioSocket(uuid,127.0.0.1:9092)
 same => n,Hangup()
```

---
**Note:** Ensure port `9092` (AudioSocket) and `8001` (Bridge API) are open in your firewall.
