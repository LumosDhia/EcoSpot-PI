#!/bin/bash

echo "Starting MariaDB service..."
sudo service mariadb start

echo "Starting Face Recognition Service..."
cd face_service || exit
# Run face service in the background and redirect output to a log file
python3 main.py > ../face_service_out.log 2>&1 &
cd ..

echo "Starting Symfony Server..."
symfony server:start -d

echo "Project, database, and face recognition service are now running."
