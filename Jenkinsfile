pipeline {
    agent any

    stages {
        stage('Build and Deploy') {
            steps {
                // Etapa para acessar o diretório do projeto
                dir('/vagrant/example-voting-app-main') {
                    // Executar o comando docker-compose up -d
                    sh 'docker-compose up -d'

                    // Esperar alguns segundos para a aplicação inicializar
                    sleep(time: 60, unit: 'SECONDS')
                }
            }
        }
        stage('Test Application') {
            steps {
                // Etapa para testar a aplicação acessando http://localhost:5000
                script {
                    def response = sh(script: 'curl -s -o /dev/null -w "%{http_code}" http://localhost:5000', returnStdout: true).trim()
                    if (response == '200') {
                        echo 'Aplicação está respondendo corretamente.'
                    } else {
                        error 'Falha ao testar a aplicação. Status code: ' + response
                    }
                }
            }
        }
    }
}
