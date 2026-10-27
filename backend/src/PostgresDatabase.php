<?php

// Centralise la connexion PDO au serveur PostgreSQL.
final class PostgresDatabase
{
    // Ouvre une connexion PostgreSQL avec les paramètres de l’environnement.
    public static function connect(): PDO
    {
        $host = getenv('PG_HOST') ?: 'postgres';
        $port = getenv('PG_PORT') ?: '5432';
        $database = getenv('PG_DATABASE') ?: 'postgres';

        $dsn = "pgsql:host=$host;port=$port;dbname=$database";

        return new PDO(
            $dsn,
            getenv('PG_USER') ?: '',
            getenv('PG_PASSWORD') ?: '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}