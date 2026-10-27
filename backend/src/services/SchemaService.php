<?php

// Métadonnées partagées par les services de colonnes et de lignes.
final class SchemaService
{
    public PDO $pdo;
    public string $qualified;
    public array $tableInfo;
    // Vérifie les noms, ouvre la connexion et récupère les informations de la table.
    // Les noms de base et de table sont vérifiés avant la connexion. La requête consulte information_schema, le
    // catalogue de MySQL. Si la table n’existe pas, une erreur 404 arrête la demande.
    public function __construct(public string $database, public string $table)
    {
        SqlValidation::database($database);
        SqlValidation::name($table);
        $this->pdo = Database::connect();
        $this->qualified = "`$database`.`$table`";
        $statement = $this->pdo->prepare(
            'SELECT TABLE_TYPE, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $statement->execute([$database, $table]);
        $this->tableInfo = $statement->fetch() ?: [];
        if ($this->tableInfo === []) {
            throw new HttpError('Table introuvable.', 404);
        }
    }
    // Lit la description des colonnes dans le catalogue interne de MySQL.
    // La requête lit les noms, les types et les attributs des colonnes. ORDER BY conserve leur ordre réel dans la
    // table. fetchAll() renvoie un tableau contenant toutes leurs descriptions.
    public function columns(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA,
                    COLUMN_DEFAULT, COLUMN_COMMENT, COLLATION_NAME, GENERATION_EXPRESSION
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION'
        );
        $statement->execute([$this->database, $this->table]);
        return $statement->fetchAll();
    }
    // Cherche une colonne par son nom et renvoie une erreur si elle est introuvable.
    // La fonction reçoit un nom, le vérifie puis parcourt les colonnes de la table. Elle renvoie la description
    // correspondante ou une erreur 404 si le nom est absent.
    public function column(string $name): array
    {
        SqlValidation::name($name);
        foreach ($this->columns() as $column) {
            if ($column['COLUMN_NAME'] === $name) {
                return $column;
            }
        }
        throw new HttpError('Colonne introuvable.', 404);
    }
    // Refuse les modifications sur une vue, qui reste consultable seulement.
    // Une vue présente le résultat d’une requête plutôt qu’une table ordinaire. Cette vérification bloque les
    // actions de modification sur les vues pour les laisser en lecture seule.
    public function requireTable(): void
    {
        if ($this->tableInfo['TABLE_TYPE'] !== 'BASE TABLE') {
            throw new HttpError('Les vues sont consultables uniquement.', 403);
        }
    }
    // Trouve les colonnes qui identifient une ligne et explique les éventuelles limites de modification.
    // La clé primaire peut contenir une ou plusieurs colonnes. La fonction les retrouve et contrôle si leurs types
    // et le moteur permettent les actions prévues. Elle renvoie la clé et une explication si les actions doivent
    // être bloquées.
    public function primaryKey(): array
    {
        $statement = $this->pdo->prepare(
            "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION"
        );
        $statement->execute([$this->database, $this->table]);
        $primaryKey = $statement->fetchAll(PDO::FETCH_COLUMN);
        $reason = '';
        if ($this->tableInfo['TABLE_TYPE'] !== 'BASE TABLE') {
            $reason = 'Les vues sont consultables uniquement.';
        } elseif ($primaryKey === []) {
            $reason = 'Aucune clé primaire : modification et suppression des lignes désactivées.';
        } elseif ($this->tableInfo['ENGINE'] !== 'InnoDB') {
            $reason = 'Ces actions nécessitent une table InnoDB pour garantir la transaction.';
        }
        foreach ($this->columns() as $column) {
            if (!in_array($column['COLUMN_NAME'], $primaryKey, true)) {
                continue;
            }
            if (!SqlValidation::validName($column['COLUMN_NAME'])
                || str_contains($column['EXTRA'], 'INVISIBLE')
                || !in_array($column['DATA_TYPE'], ['int', 'tinyint', 'smallint', 'mediumint',
                    'bigint', 'decimal', 'char', 'varchar', 'date', 'datetime', 'timestamp', 'time', 'year'], true)) {
                $reason = 'La clé primaire utilise un nom, un type ou un attribut non pris en charge.';
            }
        }
        return ['primaryKey' => $primaryKey, 'actionDisabledReason' => $reason];
    }
    // Vérifie que toutes les valeurs nécessaires pour identifier une ligne ont été fournies.
    // La fonction attend toutes les colonnes de la clé primaire, sans colonne supplémentaire. Elle vérifie aussi le
    // type de chaque valeur. La clé renvoyée permet ensuite de viser la bonne ligne.
    public function validateKey(mixed $key): array
    {
        $metadata = $this->primaryKey();
        if ($metadata['actionDisabledReason'] !== '') {
            throw new HttpError($metadata['actionDisabledReason'], 422);
        }
        if (!is_array($key) || count($key) !== count($metadata['primaryKey'])
            || array_diff($metadata['primaryKey'], array_keys($key)) !== []) {
            throw new HttpError('Fournissez exactement toutes les colonnes de la clé primaire.');
        }
        foreach ($key as $value) {
            if (!is_string($value) && !is_int($value)) {
                throw new HttpError('Valeur de clé primaire invalide.');
            }
        }
        return $key;
    }
}
