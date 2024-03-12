pipeline {
    agent any
    stages {
        stage('build da imagem docker'){
            steps{
            sh 'docker build -t devops/app .'
        }
    }
    stage('subir docker compose vote app'){
            steps{
            sh 'docker-compose up-- build -d'
        }
    }
    stage('sleep para subida de containers'){
            steps{
            sh 'sleep 20'
        }
    }
    stage('teste app'){
            steps{
            sh 'testeapp.sh'
        }
    }
}    
}
