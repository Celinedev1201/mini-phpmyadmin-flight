# PHP 8.3 sur une base Debian explicite
FROM php:8.3-cli-bookworm

# Outils nécessaires à Composer et au dépôt MySQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip curl ca-certificates gnupg \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Clé de signature officielle des paquets MySQL
RUN curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2025 \
        -o /tmp/mysql-key.asc \
    && gpg --batch --dearmor \
        -o /usr/share/keyrings/mysql.gpg /tmp/mysql-key.asc \
    && rm /tmp/mysql-key.asc

# Dépôt officiel MySQL 8.4 pour Debian Bookworm
RUN printf '%s\n' \
    'deb [signed-by=/usr/share/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian/ bookworm mysql-8.4-lts' \
    > /etc/apt/sources.list.d/mysql.list

# Client MySQL : inclut mysql et mysqldump
RUN apt-get update \
    && apt-get install -y --no-install-recommends mysql-community-client \
    && rm -rf /var/lib/apt/lists/*

# Gestionnaire des dépendances PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

# Démarrage de l’API FlightPHP
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]