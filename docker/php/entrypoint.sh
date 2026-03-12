#!/usr/bin/env sh
set -e

if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    echo "Lancement des migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # Chargement des fixtures si demandé
    if [ "$LOAD_FIXTURES" = "true" ]; then
        echo "Chargement des fixtures..."
        php bin/console doctrine:fixtures:load --no-interaction
        echo "Fixtures chargées !"
    fi

    if [ "$APP_ENV" = 'prod' ]; then
        php bin/console cache:clear --no-warmup
        php bin/console cache:warmup
    fi
fi

exec "$@"