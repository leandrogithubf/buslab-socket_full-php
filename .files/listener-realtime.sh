until php /var/www/buslab_socketfull/bin/listener-realtime; do
    echo "Listener real-time crashed with exit code $?. Respawning.." >&2
    sleep 1
done
