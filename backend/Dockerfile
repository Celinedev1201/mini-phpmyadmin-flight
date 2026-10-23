# PHP 8.3 sur Debian Bookworm
FROM php:8.3-cli-bookworm

# Outils de développement et de planification
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip curl ca-certificates gnupg cron util-linux \
    && rm -rf /var/lib/apt/lists/*

# Clé de signature officielle MySQL
RUN curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2025 \
        -o /tmp/mysql-key.asc \
    && gpg --batch --dearmor \
        -o /usr/share/keyrings/mysql.gpg /tmp/mysql-key.asc \
    && rm /tmp/mysql-key.asc

# Dépôt MySQL 8.4
RUN printf '%s\n' \
    'deb [signed-by=/usr/share/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian/ bookworm mysql-8.4-lts' \
    > /etc/apt/sources.list.d/mysql.list

# Clé de signature officielle PostgreSQL
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
        -o /usr/share/keyrings/postgresql.asc

# Dépôt PostgreSQL pour Debian Bookworm
RUN printf '%s\n' \
    'deb [signed-by=/usr/share/keyrings/postgresql.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main' \
    > /etc/apt/sources.list.d/postgresql.list

# Clients de sauvegarde et bibliothèque nécessaire au pilote PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        mysql-community-client postgresql-client-17 libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Utilise explicitement les outils PostgreSQL 17
ENV PATH="/usr/lib/postgresql/17/bin:${PATH}"

# Gestionnaire des dépendances PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

# Démarrage de l’API FlightPHP
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]