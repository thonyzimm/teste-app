pipeline {
    agent any
    stages {
        stage('build da imagem docker'){
            steps{
            sh 'docker compose up'
        }
    }
    stage('subir docker compose vote app'){
            steps{
            sh 'docker swarm init'
        }
    }
    stage('sleep para subida de containers'){
            steps{
            sh 'sleep 20'
        }
    }
    stage('teste app'){
            steps{
            sh 'docker stack deploy --compose-file docker-stack.yml vote'
        }
    }
}    
}

