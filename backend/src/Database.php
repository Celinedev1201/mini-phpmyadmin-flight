<?php

declare(strict_types=1);

final class Database
{
    public static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

        return new PDO(
            $dsn,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // Préserve les BIGINT et DECIMAL lors du passage par JSON/JavaScript.
                PDO::ATTR_STRINGIFY_FETCHES => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode =
                    'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
            ]
        );
    }
}