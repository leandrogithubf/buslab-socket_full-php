#!/bin/bash

envsubst < /var/www/buslab_socketfull/.container/docker/create-env.sh

sh /var/www/buslab_socketfull/.container/docker/create-env.sh

php /var/www/buslab_socketfull/vendor/bin/doctrine orm:generate-proxies --no-interaction

service php7.4-fpm start

sh /var/www/buslab_socketfull/.files/socket.sh >> /var/log/socket.log & sh /var/www/buslab_socketfull/.files/listener-realtime.sh >> /var/log/listener-realtime.log & sh /var/www/buslab_socketfull/.files/listener-filesystem.sh >> /var/log/listener-filesystem.log & sh /var/www/buslab_socketfull/.files/listener-database.sh >> /var/log/listener-database.log & sh /var/www/buslab_socketfull/.files/listener-alert.sh >> /var/log/listener-alert.log