# SEM Project - Docker Setup

This directory contains Docker configuration for SEM Project - a platform for automated creation of mobile application fingerprints.

## Quick Start

### After cloning from git (first time)
```bash
cd virtualisation
./build.sh
```

### Access the application
- **Web interface**: http://localhost:8081
- **Database**: localhost:3306
- **Redis**: localhost:6379

## Login credentials

### Administrator
- **Email**: admin@example.com
- **Password**: AdminPass123

### Registered user
- **Email**: user@example.com
- **Password**: UserPass123

## What build.sh does

### Step 1/5: Environment setup
- Creates `.env` file from `.env.example`
- Sets correct database and Redis configurations for Docker
- Creates necessary directories and sets permissions
- Creates default profile pictures
- Generates Laravel APP_KEY

### Step 2/5: Build Docker images
- Base image (PHP/Python/Wireshark)
- Emulator image (x86_64)
- Main application image

### Step 3/5: Start containers
- `docker compose up -d`

### Step 4/5: Database setup
- Create structure
- Import test data

### Step 5/5: Completion
- Display access information

## Quick commands

```bash
# Complete setup (first time)
./build.sh

# Stop
docker compose down

# Restart
docker compose restart

# Check status
docker ps
```

## Docker image structure

### 1. Base Image (`sem_python_wireshark_php8.3:latest`)
- PHP 8.3
- Python 3.11
- Wireshark 4.2.4
- Apache 2.4

### 2. Emulator Image (`sem_emulator_vm:latest`)
- Android SDK
- Android Emulator
- x86_64 architecture

### 3. Main Application Image (`sem_hashapp:latest`)
- Laravel application
- React frontend
- All dependencies

## Troubleshooting

### Check container status
```bash
docker ps
```

### Container logs
```bash
docker logs hashapp_web
docker logs hashapp_db
docker logs hashapp_redis
```

### Restart containers
```bash
docker compose restart
```

### Complete restart
```bash
docker compose down
docker compose up -d
```

## Requirements

- Docker
- Docker Compose
- Sudo access for database setup
- Minimum 8GB RAM for emulators
- x86_64 architecture