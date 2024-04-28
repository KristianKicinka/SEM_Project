#!/bin/bash

# Start Docker Compose
echo "Starting Docker Compose..."
docker-compose up -d

echo "Creating database structure..."
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan migrate;\""
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan migrate:fresh;\""
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan db:seed --class=UserSeeder\""
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan db:seed --class=EmulatorSeeder\""

echo "Importing data to database..."

# Create network interfaces
#RUN mysql -u root -p root sem_project < ./installationFiles/sem_project_db_data.sql
