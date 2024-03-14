#!/bin/bash

# Atualizar repositórios
sudo apt update

# Instalar pacotes básicos de desenvolvimento
sudo apt -y install git cmake g++ libtool

# Instalar dependências do servidor
sudo apt -y install libboost-date-time-dev libboost-system-dev libboost-filesystem-dev libboost-iostreams-dev libcrypto++-dev liblua5.2-dev libluajit-5.1-dev libmariadb-dev-compat libpugixml-dev

# Instalar MySQL Server
sudo apt -y install mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Instalar Apache
sudo apt -y install apache2
sudo systemctl start apache2
sudo systemctl enable apache2

# Instalar PHP
sudo apt -y install php php-mysql

# Instalar PHPMyAdmin
sudo apt -y install phpmyadmin

# Reiniciar serviços
sudo systemctl restart apache2
sudo systemctl restart mysql
