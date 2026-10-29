<?php

// Centralise les connexions au serveur PostgreSQL.
final class PostgresDatabase
{
    public static function connect(?string $database = null): PDO
    {
        $host = getenv('PG_HOST') ?: 'postgres';
        $port = getenv('PG_PORT') ?: '5432';

        // Sans argument, les appels existants gardent leur base habituelle.
        if ($database === null) {
            $database = getenv('PG_DATABASE') ?: 'postgres';
        } else {
            PostgresValidation::database($database);
        }

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