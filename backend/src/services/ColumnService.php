<?php

final class ColumnService
{
    private SchemaService $schema;
    // Prépare les informations de la table utilisées par les actions sur ses colonnes.
    // La base et la table reçues servent à créer un SchemaService. Cet objet garde la connexion SQL et les
    // informations de la table pour les autres méthodes.
    public function __construct(string $database, string $table)
    {
        $this->schema = new SchemaService($database, $table);
    }
    // Renvoie les colonnes et précise si leur type peut être modifié par l’application.
    // columns() récupère les informations de chaque colonne. typeRestriction() ajoute une explication si son type
    // est trop particulier pour être modifié. isTable indique si les modifications sont autorisées.
    public function list(): array
    {
        $columns = $this->schema->columns();
        foreach ($columns as &$column) {
            $column['TYPE_RESTRICTION'] = self::typeRestriction($column);
        }
        return ['columns' => $columns, 'isTable' => $this->schema->tableInfo['TABLE_TYPE'] === 'BASE TABLE'];
    }
    // Vérifie le nom et le type puis ajoute une colonne qui accepte les valeurs NULL.
    // Le formulaire fournit name et type. La validation traduit le type choisi en SQL autorisé. ALTER TABLE ajoute
    // la colonne ; NULL signifie qu’une case peut ne pas avoir de valeur.
    public function create(array $data): array
    {
        $this->schema->requireTable();
        $name = SqlValidation::name($data['name'] ?? null);
        $type = SqlValidation::type($data['type'] ?? null);
        $this->schema->pdo->exec("ALTER TABLE {$this->schema->qualified} ADD COLUMN `$name` $type NULL");
        return ['message' => 'Colonne ajoutée avec succès'];
    }
    // Renomme la colonne et change son type si un nouveau type a été choisi.
    // Le nom actuel permet de retrouver les attributs de la colonne. Avec un nouveau type, CHANGE COLUMN modifie le
    // nom et le type ensemble. Sans type choisi, seul RENAME COLUMN est utilisé.
    public function update(string $column, array $data): array
    {
        $this->schema->requireTable();
        $previous = $this->schema->column($column);
        $name = SqlValidation::name($data['name'] ?? null);
        $type = $data['type'] ?? '';
        if ($type !== '') {
            $sqlType = SqlValidation::type($type);
            $definition = self::typeDefinition($this->schema->pdo, $previous, $sqlType);
            // CHANGE renomme et change le type dans une seule opération MySQL.
            $clause = "CHANGE COLUMN `$column` `$name` $definition";
        } else {
            $clause = "RENAME COLUMN `$column` TO `$name`";
        }
        if ($type !== '' || $name !== $column) {
            $this->schema->pdo->exec("ALTER TABLE {$this->schema->qualified} $clause");
        }
        return ['message' => 'Colonne modifiée avec succès'];
    }
    // Vérifie la colonne et la confirmation puis supprime cette colonne de la table.
    // Le nom permet de retrouver la colonne. Après vérification de la confirmation, DROP COLUMN supprime cette
    // colonne et les valeurs qu’elle contenait.
    public function delete(string $column, array $data): array
    {
        $this->schema->requireTable();
        $this->schema->column($column);
        SqlValidation::confirmed($data, $column);
        $this->schema->pdo->exec("ALTER TABLE {$this->schema->qualified} DROP COLUMN `$column`");
        return ['message' => 'Colonne supprimée avec succès'];
    }
    // Explique pourquoi un type ou un attribut particulier empêche le changement de type.
    // La description reçue indique le type SQL et les attributs de la colonne. Si l’application ne sait pas les
    // conserver, la fonction renvoie une explication. Une chaîne vide signifie que le changement est proposé.
    private static function typeRestriction(array $column): string
    {
        if ($column['GENERATION_EXPRESSION'] !== '' || str_contains($column['COLUMN_TYPE'], 'zerofill')
            || !in_array($column['EXTRA'], ['', 'auto_increment'], true)
            || !in_array($column['DATA_TYPE'], ['varchar', 'text', 'int', 'decimal', 'date'], true)) {
            return 'Type complexe ou attribut spécial : seul le renommage est proposé.';
        }
        return '';
    }
    // Prépare le nouveau type SQL en conservant les attributs de la colonne pris en charge.
    // La fonction reçoit la connexion, l’ancienne colonne et le nouveau type SQL. Elle recompose sa définition en
    // gardant les attributs compatibles, comme NULL, la valeur par défaut et le commentaire.
    private static function typeDefinition(PDO $pdo, array $column, string $type): string
    {
        if (self::typeRestriction($column) !== '') {
            throw new HttpError(self::typeRestriction($column), 422);
        }
        $numeric = in_array($type, ['INT', 'DECIMAL(10,2)'], true);
        if ($column['EXTRA'] === 'auto_increment' && $type !== 'INT') {
            throw new HttpError('Une colonne auto-incrémentée doit rester de type INT.', 422);
        }
        $definition = $type;
        if ($numeric && str_contains($column['COLUMN_TYPE'], 'unsigned')) {
            $definition .= ' UNSIGNED';
        }
        if (in_array($type, ['VARCHAR(255)', 'TEXT'], true) && $column['COLLATION_NAME']) {
            $collation = SqlValidation::name($column['COLLATION_NAME']);
            $definition .= " COLLATE `$collation`";
        }
        $definition .= $column['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL';
        if ($column['COLUMN_DEFAULT'] !== null) {
            if ($type === 'TEXT') {
                throw new HttpError('Retirez ou migrez la valeur par défaut avant de convertir en TEXT.', 422);
            }
            $definition .= ' DEFAULT ' . $pdo->quote($column['COLUMN_DEFAULT']);
        } elseif ($column['IS_NULLABLE'] === 'YES') {
            $definition .= ' DEFAULT NULL';
        }
        if ($column['EXTRA'] === 'auto_increment') {
            $definition .= ' AUTO_INCREMENT';
        }
        return $definition . ' COMMENT ' . $pdo->quote($column['COLUMN_COMMENT']);
    }
}
