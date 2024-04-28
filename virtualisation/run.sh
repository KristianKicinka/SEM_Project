#!/bin/bash
##
# @file run.sh
# @author Kristián Kičinka (xkicin02)
#
# @copyright Copyright (c) 2024
#

# Start Docker Compose
echo "Starting Docker Compose..."
docker-compose up -d

# Create database structure
echo "Creating database structure..."
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"php artisan migrate:fresh;\""

# Create network interfaces
echo "Importing data to database..."
docker exec -it hashapp_web bash -c "sudo -u www-data bash -c \"mysql -h 0.0.0.0 -P 3306 -u sem_project --password=password -D sem_project < ./installationFiles/sem_project_db_data.sql\""
