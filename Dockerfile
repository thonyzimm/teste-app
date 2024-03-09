FROM centos:7

RUN yum install -y epel-release && \
    yum install -y java-1.8.0-openjdk wget && \
    wget -O /etc/yum.repos.d/jenkins.repo http://pkg.jenkins.io/redhat-stable/jenkins.repo && \
    rpm --import http://pkg.jenkins.io/redhat-stable/jenkins.io.key && \
    yum install -y jenkins && \
    yum clean all

RUN wget https://github.com/jenkinsci/plugin-installation-manager-tool/releases/download/2.10.0/jenkins-plugin-manager-2.10.0.jar

COPY plugins.txt /root/.jenkins/plugins/

RUN java -jar jenkins-plugin-manager-*.jar --war /usr/lib/jenkins/jenkins.war --plugin-download-directory /root/.jenkins/plugins --plugin-file /root/.jenkins/plugins/plugins.txt

COPY jenkins-casc.yaml /usr/local/jenkins-casc.yaml

ENV CASC_JENKINS_CONFIG /usr/local/jenkins-casc.yaml

CMD java -Djenkins.install.runSetupWizard=false -jar /usr/lib/jenkins/jenkins.war
