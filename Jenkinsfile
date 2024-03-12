pipeline {
    agent any
    
    stages {
        stage('Build and run Docker Compose') {
            steps {
                // Clonar o repositório do exemplo de aplicativo de votação
                git 'https://github.com/thonyzimm/teste-app.git'
                
                // Acessar o diretório clonado
                dir('example-voting-app-main') {
                    // Executar o Docker Compose
                    sh 'docker-compose up -d'
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