# Image PHP utilisée pour le backend
FROM php:8.3-cli

# Outils nécessaires à Composer et connexion PHP à MySQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Récupère Composer depuis son image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dossier principal du backend dans le conteneur
WORKDIR /var/www/html

# Port utilisé par le serveur PHP
EXPOSE 8000

# Lance l’API FlightPHP
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]