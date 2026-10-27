<?php

final class DatabaseService
{
    // Liste les bases MySQL en retirant les bases internes du serveur.
    // SHOW DATABASES demande les noms au serveur. array_filter() retire les bases internes protégées. array_values()
    // remet les résultats dans une liste, renvoyée avec son nombre de bases.
    public static function list(): array
    {
        $names = Database::connect()->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        $names = array_values(array_filter($names, fn($name) =>
            !in_array(strtolower($name), SqlValidation::SYSTEM_DATABASES, true)
        ));
        return ['databases' => $names, 'count' => count($names), 'status' => 'success'];
    }
    // Vérifie le nom demandé puis crée une base MySQL vide.
    // Le nom vient du formulaire. La validation refuse les noms invalides et les bases système. CREATE DATABASE crée
    // une base vide en UTF8, puis la fonction renvoie un message et son nom.
    public static function create(array $data): array
    {
        $name = SqlValidation::database($data['name'] ?? null);
        Database::connect()->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        return ['message' => 'Base créée avec succès', 'name' => $name];
    }
    // Vérifie le nom et la confirmation avant de supprimer toute la base MySQL.
    // Le tableau data contient la confirmation et le nom recopié. Les deux sont vérifiés avant DROP DATABASE, qui
    // supprime la base entière. Le résultat contient le message de réussite.
    public static function delete(string $database, array $data): array
    {
        SqlValidation::database($database);
        SqlValidation::confirmed($data, $database, true);
        Database::connect()->exec("DROP DATABASE `$database`");
        return ['message' => 'Base supprimée avec succès'];
    }
}
