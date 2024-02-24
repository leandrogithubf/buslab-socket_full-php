# Imagem Base Ubuntu 18.04
FROM ubuntu:18.04 as base_socketfull_image

# Variaveis Ambiente
ENV SITE=localhost
ENV FOLDER=./.devcontainer

# Instalando pacotes bases do Ubuntu
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive
RUN apt-get install -y software-properties-common curl

# Configurar Timezone para America/Sao_Paulo
ENV TZ=America/Sao_Paulo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Adicionando repositório PHP
RUN apt-get update && add-apt-repository ppa:ondrej/php

# Instalando Extensões do PHP
RUN apt-get update && \
    apt-get install -y --no-install-recommends \        
        php7.4 \
        php7.4-apcu \
        php7.4-bcmath \
        php7.4-calendar \
        php7.4-cli \
        php7.4-common \
        php7.4-curl \
        php7.4-dev \
        php7.4-gd \
        php7.4-intl \
        php7.4-json \
        php7.4-mbstring \
        php7.4-mysql \
        php7.4-mysqli \
        php7.4-opcache \
        php7.4-pgsql \
        php7.4-readline \
        php7.4-sqlite3 \
        php7.4-xml \
        php7.4-xmlwriter \
        php7.4-xmlreader \
        php7.4-zip \
        php7.4-redis \
        php7.4-soap \
        php7.4-pdo \
        php7.4-sockets \
        php7.4-ftp \
        php7.4-fpm \
        libapache2-mod-php7.4 \
        libpq-dev  

# Instalando editor Nano
RUN apt-get update && \
    apt-get install -y nano

# Instalando GIT
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git

# Limpando dados
RUN apt clean
RUN rm -rf /var/lib/apt/lists/*

###################################################################

FROM base_socketfull_image as depencies_socketfull_image

# Pasta raiz do projeto
WORKDIR /var/www/socket-full

# Instalando Composer 1.6
RUN curl -sS https://getcomposer.org/installer | php -- --version=1.6.0 --install-dir=/usr/local/bin --filename=composer

COPY composer.json .
COPY composer.lock .

# Instalando Dependências PHP do projeto
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --no-scripts --optimize-autoloader

###################################################################

FROM depencies_socketfull_image as project_socketfull_image

# Pasta raiz do projeto
WORKDIR /var/www/socket-full

# Copia as configurações do PHP-FPM do servidor da Digital Ocean para este novo servidor
COPY $FOLDER/docker/fpm/  /etc/php/7.4/fpm

# Copia todo o projeto para o container
COPY . .

# Permissões
RUN chown -R www-data:www-data /etc/php/7.4/fpm
RUN chown -R www-data:www-data /var/www/socket-full
RUN chmod +x /var/www/socket-full/

# Comando de recomendação BuslabTeam
RUN php vendor/bin/doctrine orm:generate-proxies --no-interaction

EXPOSE 80 3001 3306

ENTRYPOINT  /bin/bash service php7.4-fpm start && sh /var/www/socket-full/.files/socket.sh >> /var/log/socket.log & sh /var/www/socket-full/.files/listener-realtime.sh >> /var/log/listener-realtime.log & sh /var/www/socket-full/.files/listener-filesystem.sh >> /var/log/listener-filesystem.log & sh /var/www/socket-full/.files/listener-database.sh >> /var/log/listener-database.log & sh /var/www/socket-full/.files/listener-alert.sh >> /var/log/listener-alert.log
