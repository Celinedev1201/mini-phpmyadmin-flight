<?php

// Valide et lit toute la source sous un verrou de lecture MySQL.
final class DatabaseCopySnapshot
{
    public static function read(string $database): array
    {
        SqlValidation::database($database);
        $pdo = Database::connect();
        $locked = false;
        try {
            // Stabilise aussi les métadonnées ; nécessite RELOAD ou FLUSH_TABLES.
            $pdo->exec('FLUSH TABLES WITH READ LOCK');
            $locked = true;
            $query = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $query->execute([$database]);
            if (!$query->fetchColumn()) throw new HttpError('Base MySQL introuvable.', 404);
            $query = $pdo->prepare('SELECT TABLE_NAME, TABLE_TYPE, ENGINE, AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME');
            $query->execute([$database]);
            $tables = [];
            foreach ($query->fetchAll() as $info) {
                $table = $info['TABLE_NAME'];
                try {
                    self::identifier($table);
                    if ($info['TABLE_TYPE'] !== 'BASE TABLE') throw new HttpError('Vue non prise en charge.', 422);
                    if ($info['ENGINE'] !== 'InnoDB') throw new HttpError('Seul le moteur InnoDB est pris en charge.', 422);
                    foreach ([
                        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE <> 'PRIMARY KEY'" => 'Contrainte ou clé étrangère non prise en charge.',
                        "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME <> 'PRIMARY'" => 'Index secondaire non pris en charge.',
                        'SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = ? AND EVENT_OBJECT_TABLE = ?' => 'Déclencheur non pris en charge.',
                    ] as $sql => $reason) {
                        $check = $pdo->prepare($sql);
                        $check->execute([$database, $table]);
                        if ($check->fetchColumn() > 0) throw new HttpError($reason, 422);
                    }
                    $index = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY' AND SUB_PART IS NOT NULL");
                    $index->execute([$database, $table]);
                    if ($index->fetchColumn() > 0) throw new HttpError('Clé primaire sur préfixe non prise en charge.', 422);
                    $columnsQuery = $pdo->prepare('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, EXTRA, COLUMN_DEFAULT, GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
                    $columnsQuery->execute([$database, $table]);
                    $columns = $columnsQuery->fetchAll();
                    $definitions = array_map([PostgresColumnMapper::class, 'definition'], $columns);
                    $keyQuery = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION");
                    $keyQuery->execute([$database, $table]);
                    $keys = $keyQuery->fetchAll(PDO::FETCH_COLUMN);
                    if ($keys) $definitions[] = 'PRIMARY KEY (' . implode(', ', array_map([self::class, 'identifier'], $keys)) . ')';
                    $rows = $pdo->query("SELECT * FROM `$database`.`$table` LIMIT 1001")->fetchAll();
                    if (count($rows) > 1000) throw new HttpError('Limite de 1 000 lignes par table dépassée.', 422);
                    $tables[] = compact('table', 'columns', 'definitions', 'rows') + ['next' => $info['AUTO_INCREMENT']];
                } catch (HttpError $error) {
                    throw new HttpError("Table $table : " . $error->getMessage(), 422);
                }
            }
            return $tables;
        } finally {
            if ($locked) $pdo->exec('UNLOCK TABLES');
        }
    }

    public static function identifier(string $name): string
    {
        SqlValidation::name($name);
        if (strlen($name) > 63) throw new HttpError('Nom trop long pour PostgreSQL (63 octets maximum).', 422);
        return '"' . $name . '"';
    }
}
