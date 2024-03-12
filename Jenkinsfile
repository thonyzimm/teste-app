pipeline {
    agent any
    
    stages {
        stage('Build and run Docker Compose') {
            steps {
                // Executar o Docker Compose no diretório específico
                dir('/root/example-voting-app-main') {
                    sh 'docker compose up'
                }
            }
        }
        
        stage('Test application') {
            steps {
                // Esperar um tempo para que a aplicação seja iniciada
                // Você pode ajustar esse tempo conforme necessário
                sleep(time: 60, unit: 'SECONDS')
                
                // Testar a aplicação (por exemplo, com cURL)
                sh 'curl http://localhost:5000'
            }
        }
    }
}