pipeline {
    agent any

    stages {
        stage('Build and Deploy with Docker Swarm') {
            steps {
                script {
                    // Permitir que o usuário do Jenkins execute docker stack deploy sem senha
                    sh 'echo "jenkins ALL=(ALL) NOPASSWD: /usr/bin/docker stack deploy --compose-file docker-stack.yml vote" | sudo tee -a /etc/sudoers'
                    
                    // Inicializar o Docker Swarm (se ainda não estiver inicializado)
                    sh 'sudo docker swarm init || true'
                    
                    // Executar deploy com Docker Stack
                    sh 'sudo docker stack deploy --compose-file docker-stack.yml vote'
                }
            }
        }
    }

    post {
        always {
            // Limpar o workspace
            cleanWs()
        }
    }
}