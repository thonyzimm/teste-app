pipeline {
    agent any
    
    stages {
        stage('Build and Deploy') {
            steps {
                // Comando para iniciar os contêineres do Docker Compose em segundo plano
                sh 'docker-compose up -d'
            }
        }
        stage('Test Application') {
            steps {
                // Esperar alguns segundos para os contêineres inicializarem
                script {
                    sleep(time: 60, unit: 'SECONDS')
                }
                // Testar a aplicação acessando http://localhost:5000
                script {
                    def response = sh(script: 'curl -s -o /dev/null -w "%{http_code}" http://localhost:5000', returnStatus: true)
                    if (response == 200) {
                        echo 'Teste da aplicação bem-sucedido. A aplicação está funcionando corretamente.'
                    } else {
                        error 'Falha no teste da aplicação. O status do servidor foi: ' + response
                    }
                }
            }
        }
    }
}
