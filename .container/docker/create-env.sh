#!/bin/bash

touch /var/www/$ROOT_NAME/.env

echo "APP_ENV=$APP_ENV" >> /var/www/$ROOT_NAME/.env

echo "SOCKET_HOST=$SOCKET_HOST" >> /var/www/$ROOT_NAME/.env
echo "SOCKET_PORT=$SOCKET_PORT" >> /var/www/$ROOT_NAME/.env

echo "DATABASE_HOST=$DATABASE_HOST" >> /var/www/$ROOT_NAME/.env
echo "DATABASE_PORT=$DATABASE_PORT" >> /var/www/$ROOT_NAME/.env
echo "DATABASE_USER=$DATABASE_USER" >> /var/www/$ROOT_NAME/.env
echo "DATABASE_PASSWORD=$DATABASE_PASSWORD" >> /var/www/$ROOT_NAME/.env
echo "DATABASE_NAME=$DATABASE_NAME" >> /var/www/$ROOT_NAME/.env

echo "REDIS_SCHEME=$REDIS_SCHEME" >> /var/www/$ROOT_NAME/.env
echo "REDIS_HOST=$REDIS_HOST" >> /var/www/$ROOT_NAME/.env
echo "REDIS_USERNAME=$REDIS_USERNAME" >> /var/www/$ROOT_NAME/.env
echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> /var/www/$ROOT_NAME/.env
echo "REDIS_PORT=$REDIS_PORT" >> /var/www/$ROOT_NAME/.env

echo "SOCKETIO_HOST=$SOCKETIO_HOST" >> /var/www/$ROOT_NAME/.env
echo "SOCKETIO_PORT=$SOCKETIO_PORT" >> /var/www/$ROOT_NAME/.env

echo "LANG=$LANG" >> /var/www/$ROOT_NAME/.env

echo "DO_SPACE_REGION=$DO_SPACE_REGION" >> /var/www/$ROOT_NAME/.env
echo "DO_SPACE_NAME=$DO_SPACE_NAME" >> /var/www/$ROOT_NAME/.env
echo "DO_SPACE_KEY=$DO_SPACE_KEY" >> /var/www/$ROOT_NAME/.env
echo "DO_SPACE_SECRET=$DO_SPACE_SECRET" >> /var/www/$ROOT_NAME/.env





