<?php

final class TableService
{
    // Vérifie que la base existe puis renvoie les noms de ses tables et de ses vues.
    // La requête sur information_schema vérifie d’abord que la base existe. Une seconde requête lit les tables et
    // les vues dans l’ordre alphabétique. fetchAll() les rassemble dans le résultat.
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
    // Crée une table avec une colonne id qui numérote automatiquement les nouvelles lignes.
    // Le nom de la base et celui de la table sont vérifiés avant CREATE TABLE. La colonne id est une clé primaire :
    // son numéro augmente automatiquement à chaque ajout de ligne.
    public static function create(string $database, array $data): array
    {
        SqlValidation::database($database);
        $name = SqlValidation::name($data['name'] ?? null);
        Database::connect()->exec("CREATE TABLE `$database`.`$name` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return ['message' => 'Table créée avec succès', 'name' => $name];
    }
    // Vérifie que la table est modifiable puis lui donne le nouveau nom demandé.
    // SchemaService retrouve la table actuelle et refuse les vues. Le nouveau nom est vérifié, puis RENAME TABLE
    // applique le changement. Le nom renvoyé permet au frontend d’actualiser sa sélection.
    public static function rename(string $database, string $table, array $data): array
    {
        $schema = new SchemaService($database, $table);
        $schema->requireTable();
        $name = SqlValidation::name($data['name'] ?? null);
        $schema->pdo->exec("RENAME TABLE {$schema->qualified} TO `$database`.`$name`");
        return ['message' => 'Table renommée avec succès', 'name' => $name];
    }
    // Supprime la table après avoir vérifié son existence et la confirmation reçue.
    // SchemaService vérifie la table et confirmed() contrôle l’accord de suppression. DROP TABLE retire la table et
    // ses données, puis la fonction renvoie le message à afficher.
    public static function delete(string $database, string $table, array $data): array
    {
        $schema = new SchemaService($database, $table);
        $schema->requireTable();
        SqlValidation::confirmed($data, $table);
        $schema->pdo->exec("DROP TABLE {$schema->qualified}");
        return ['message' => 'Table supprimée avec succès'];
    }
}
