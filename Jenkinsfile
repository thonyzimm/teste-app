pipeline {
    agent any
    
    environment {
        DOCKER_COMPOSE_VERSION = '1.29.2'
    }

    stages {
        stage('Checkout') {
            steps {
                git 'https://github.com/thonyzimm/teste-app.git'
            }
        }
        
        stage('Build and Run Docker Compose') {
            steps {
                script {
                    sh "docker-compose up -d"
                }
            }
        }
    }

    post {
        always {
            // Cleanup
            cleanWs()
        }
    }
}