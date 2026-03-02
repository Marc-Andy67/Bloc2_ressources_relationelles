#!/usr/bin/env sh
set -e

# Premier test pour démarrer FPM
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    # Migrer la base de données automatiquement lors de l'initialisation du pod/container
    echo "Lancement des migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    
    # Nettoyage et préparation du cache si nécessaire
    if [ "$APP_ENV" = 'prod' ]; then
        php bin/console cache:clear --no-warmup
        php bin/console cache:warmup
    fi
fi

exec "$@"
