pipeline {
    agent any

    stages {
        stage('Build and Deploy with Kubernetes') {
            steps {
                script {
                    // Criar os deployments e serviços no Kubernetes
                    sh 'kubectl create -f k8s-specifications/'
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
