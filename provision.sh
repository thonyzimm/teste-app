#!/bin/bash

# Atualiza o sistema
sudo yum -y update

# Instala o repositório EPEL e outras dependências
sudo yum install -y epel-release wget vim

# Baixa o JDK 17 (substitua pela URL correta, caso necessário)
wget --no-check-certificate -O /tmp/jdk-17_linux-x64_bin.rpm https://download.oracle.com/java/17/latest/jdk-17_linux-x64_bin.rpm

# Instala o JDK 17 a partir do arquivo RPM baixado
sudo yum -y install /tmp/jdk-17_linux-x64_bin.rpm

# Limpa o arquivo RPM baixado (opcional)
rm -f /tmp/jdk-17_linux-x64_bin.rpm

# Adiciona repositório do Jenkins e instala o Jenkins
sudo wget -O /etc/yum.repos.d/jenkins.repo \
    https://pkg.jenkins.io/redhat-stable/jenkins.repo
sudo rpm --import https://pkg.jenkins.io/redhat-stable/jenkins.io-2023.key
sudo yum install jenkins -y
sudo yum install fontconfig -y


# Configura e inicia o serviço do Jenkins
sudo systemctl daemon-reload
sudo systemctl enable jenkins.service
sudo systemctl start jenkins.service

# Instalação do Docker
sudo yum install -y yum-utils
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo yum install -y docker-ce docker-ce-cli containerd.io
sudo systemctl start docker
sudo systemctl enable docker

# Instalação do Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/1.25.5/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose
sudo systemctl daemon-reload
sudo systemctl restart docker
sudo usermod -aG docker jenkins
