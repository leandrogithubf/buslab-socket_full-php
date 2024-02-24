until php /var/www/buslab_socketfull/bin/socket; do
    echo "Socket crashed with exit code $?. Respawning.." >&2
    sleep 1
done