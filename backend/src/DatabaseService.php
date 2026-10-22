<?php

final class DatabaseService
{
    public static function list(): array
    {
        $names = Database::connect()->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        $names = array_values(array_filter($names, fn($name) =>
            !in_array(strtolower($name), SqlValidation::SYSTEM_DATABASES, true)
        ));
        return ['databases' => $names, 'count' => count($names), 'status' => 'success'];
    }

    public static function create(array $data): array
    {
        $name = SqlValidation::database($data['name'] ?? null);
        Database::connect()->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        return ['message' => 'Base créée avec succès', 'name' => $name];
    }

    public static function delete(string $database, array $data): array
    {
        SqlValidation::database($database);
        SqlValidation::confirmed($data, $database, true);
        Database::connect()->exec("DROP DATABASE `$database`");
        return ['message' => 'Base supprimée avec succès'];
    }
}
