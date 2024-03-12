pipeline {
    agent {
        label 'meu-label' // Ou qualquer outra forma de selecionar um agente
        docker {
            image 'docker'
            args '-v /var/run/docker.sock:/var/run/docker.sock' // Monta o socket do Docker
            privileged true // Concede privilégios ao container
        }
    }
    
    stages {
        stage('Build and run Docker Compose') {
            steps {
                // Executar o Docker Compose no diretório específico
                dir('/root/example-voting-app-main') {
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
