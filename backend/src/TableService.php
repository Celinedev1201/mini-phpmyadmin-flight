<?php

final class TableService
{
    public static function list(string $database): array
    {
        SqlValidation::database($database);
        $pdo = Database::connect();
        $exists = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $exists->execute([$database]);
        if (!$exists->fetchColumn()) {
            throw new HttpError('Base introuvable.', 404);
        }
        $statement = $pdo->prepare(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
        );
        $statement->execute([$database]);
        return ['database' => $database, 'tables' => $statement->fetchAll(PDO::FETCH_COLUMN)];
    }

    public static function create(string $database, array $data): array
    {
        SqlValidation::database($database);
        $name = SqlValidation::name($data['name'] ?? null);
        Database::connect()->exec("CREATE TABLE `$database`.`$name` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return ['message' => 'Table créée avec succès', 'name' => $name];
    }

    public static function rename(string $database, string $table, array $data): array
    {
        $schema = new SchemaService($database, $table);
        $schema->requireTable();
        $name = SqlValidation::name($data['name'] ?? null);
        $schema->pdo->exec("RENAME TABLE {$schema->qualified} TO `$database`.`$name`");
        return ['message' => 'Table renommée avec succès', 'name' => $name];
    }

    public static function delete(string $database, string $table, array $data): array
    {
        $schema = new SchemaService($database, $table);
        $schema->requireTable();
        SqlValidation::confirmed($data, $table);
        $schema->pdo->exec("DROP TABLE {$schema->qualified}");
        return ['message' => 'Table supprimée avec succès'];
    }
}
