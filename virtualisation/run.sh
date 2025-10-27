#!/bin/bash
##
# @file run.sh
# @author Kristián Kičinka (xkicin02)
#
# @copyright Copyright (c) 2024
#

echo "Building and setting up SEM Project from git repository..."

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "Error: docker-compose.yml not found. Please run this script from the virtualisation directory."
    exit 1
fi

# Go to project root
cd ..

echo "Step 1/5: Setting up environment..."

# Check if .env exists, if not copy from .env.example
if [ ! -f ".env" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    
    # Generate APP_KEY
    echo "Generating APP_KEY..."
    php artisan key:generate
    
    # Update APP_URL for Docker
    sed -i 's|APP_URL=http://localhost|APP_URL=http://localhost:8081|' .env
    sed -i 's|VITE_APP_URL=http://localhost|VITE_APP_URL=http://localhost:8081|' .env
    
    echo ".env file created and configured for Docker"
else
    echo ".env file already exists"
fi

# Create necessary directories
echo "Creating necessary directories..."
mkdir -p storage/app/public/profile_photos
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

# Set permissions
echo "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create default profile photos if they don't exist
if [ ! -f "public/profile_photos/admin_default.svg" ]; then
    echo "Creating default profile photos..."
    mkdir -p public/profile_photos
    
    cat > public/profile_photos/admin_default.svg << 'EOF'
<svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="adminGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#4a90e2;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#357abd;stop-opacity:1" />
    </linearGradient>
  </defs>
  <circle cx="75" cy="75" r="75" fill="url(#adminGrad)"/>
  <circle cx="75" cy="60" r="25" fill="white" opacity="0.9"/>
  <path d="M 30 120 Q 75 100 120 120 L 120 150 L 30 150 Z" fill="white" opacity="0.9"/>
  <text x="75" y="140" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="#357abd">ADMIN</text>
</svg>
EOF

    cat > public/profile_photos/user_default.svg << 'EOF'
<svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="userGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#28a745;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#1e7e34;stop-opacity:1" />
    </linearGradient>
  </defs>
  <circle cx="75" cy="75" r="75" fill="url(#userGrad)"/>
  <circle cx="75" cy="60" r="25" fill="white" opacity="0.9"/>
  <path d="M 30 120 Q 75 100 120 120 L 120 150 L 30 150 Z" fill="white" opacity="0.9"/>
  <text x="75" y="140" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="#1e7e34">USER</text>
</svg>
EOF
    
    echo "Default profile photos created"
else
    echo "Default profile photos already exist"
fi

echo ""
echo "Step 2/5: Building Docker images..."

# Go back to virtualisation directory
cd virtualisation

# Build base PHP/Python/Wireshark image
echo "Building base PHP/Python/Wireshark image..."
docker build -f Dockerfile_php_python_wireshark -t sem_python_wireshark_php8.3:latest .

if [ $? -ne 0 ]; then
    echo "Error: Failed to build base image"
    exit 1
fi

# Build emulator image for x86_64
echo "Building emulator image for x86_64..."
docker build -f Dockerfile_emulators_x86-64 -t sem_emulator_vm:latest .

if [ $? -ne 0 ]; then
    echo "Error: Failed to build emulator image"
    exit 1
fi

# Build main hashapp image
echo "Building main hashapp image..."
cd ..
docker build -f virtualisation/Dockerfile_hashapp -t sem_hashapp:latest .

if [ $? -ne 0 ]; then
    echo "Error: Failed to build hashapp image"
    exit 1
fi

cd virtualisation

echo ""
echo "Step 3/5: Starting Docker containers..."

# Start Docker Compose
echo "Starting Docker Compose..."
docker compose up -d

if [ $? -ne 0 ]; then
    echo "Error: Failed to start Docker containers"
    exit 1
fi

echo ""
echo "Step 4/5: Setting up database..."

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 15

# Wait for database connection to be available
echo "Checking database connection..."
for i in {1..30}; do
    if docker exec hashapp_web bash -c "mysql -h 0.0.0.0 -P 3306 -u root --password=root --ssl=0 -e 'SELECT 1;'" >/dev/null 2>&1; then
        echo "Database connection successful"
        break
    fi
    echo "Waiting for database... (attempt $i/30)"
    sleep 2
done

# Create database structure
echo "Creating database structure..."
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan migrate:fresh;\""

if [ $? -ne 0 ]; then
    echo "Error: Failed to create database structure"
    exit 1
fi

# Import database data
echo "Importing data to database..."
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"mysql -h 0.0.0.0 -P 3306 -u root --password=root -D sem_project --ssl=0 < /var/www/html/virtualisation/installationFiles/sem_project_db_data.sql\""

if [ $? -ne 0 ]; then
    echo "Error: Failed to import database data"
    exit 1
fi

echo ""
echo " Step 5/5: Build completed successfully!"
echo ""
echo " Application is now running at: http://localhost:8081"
echo ""
echo " Default login credentials:"
echo "   Admin: admin@example.com / AdminPass123"
echo "   User:  user@example.com / UserPass123"
echo ""
echo " Available services:"
echo "   Web:       http://localhost:8081"
echo "   phpMyAdmin: http://localhost:8082"
echo "   DB:        localhost:3306"
echo "   Redis:     localhost:6379"
echo ""
echo " To stop the application: docker compose down"
echo " To restart: docker compose restart"