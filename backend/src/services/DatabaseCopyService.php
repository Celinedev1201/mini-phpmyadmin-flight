<?php

final class DatabaseCopyService
{
    public static function copy(string $database, string $target): array
    {
        PostgresValidation::database($target);
        $admin = PostgresDatabase::connect();
        $exists = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
        $exists->execute([$target]);
        if ($exists->fetchColumn()) throw new HttpError('La base PostgreSQL cible existe déjà.', 409);
        $tables = DatabaseCopySnapshot::read($database);
        $quotedTarget = DatabaseCopySnapshot::identifier($target);
        $created = false;
        $pdo = null;
        $currentTable = '';
        $total = 0;
        try {
            // CREATE DATABASE reste hors de la transaction des tables.
            $admin->exec("CREATE DATABASE $quotedTarget TEMPLATE template0 ENCODING 'UTF8'");
            $created = true;
            $pdo = PostgresDatabase::connect($target);
            $pdo->beginTransaction();
            foreach ($tables as $table) {
                $currentTable = $table['table'];
                $qualified = 'public.' . DatabaseCopySnapshot::identifier($currentTable);
                $pdo->exec("CREATE TABLE $qualified (" . implode(', ', $table['definitions']) . ')');
                $names = array_column($table['columns'], 'COLUMN_NAME');
                $insert = $pdo->prepare("INSERT INTO $qualified (" . implode(', ', array_map([DatabaseCopySnapshot::class, 'identifier'], $names)) . ') VALUES (' . implode(', ', array_fill(0, count($names), '?')) . ')');
                foreach ($table['rows'] as $row) {
                    foreach ($row as $value) {
                        if (is_string($value) && str_contains($value, "\0")) {
                            throw new RuntimeException('Caractère NUL non représentable.');
                        }
                    }
                    $insert->execute(array_map(fn ($name) => $row[$name], $names));
                    $total++;
                }
                // Préserve le prochain numéro MySQL, même si la table est vide.
                foreach ($table['columns'] as $column) {
                    if (strtolower(trim($column['EXTRA'])) !== 'auto_increment') continue;
                    $name = DatabaseCopySnapshot::identifier($column['COLUMN_NAME']);
                    $maximum = strtolower($column['COLUMN_TYPE']) === 'int unsigned' ? 4294967295 : 2147483647;
                    $sequence = $pdo->prepare("SELECT setval(pg_get_serial_sequence(?, ?), LEAST($maximum, GREATEST(?::bigint, COALESCE(MAX($name)::bigint + 1, 1))), GREATEST(?::bigint, COALESCE(MAX($name)::bigint + 1, 1)) > $maximum) FROM $qualified");
                    $sequence->execute([$qualified, $column['COLUMN_NAME'], $table['next'] ?? 1, $table['next'] ?? 1]);
                }
                $insert = $sequence = null;
            }
            $pdo->commit();
        } catch (Throwable $error) {
            try {
                if ($pdo !== null && $pdo->inTransaction()) $pdo->rollBack();
            } catch (Throwable $rollback) {
                // La fermeture libère aussi une transaction interrompue.
            }
            $insert = $sequence = $pdo = null;
            if ($created) {
                try {
                    $admin->exec("DROP DATABASE $quotedTarget");
                } catch (Throwable $cleanup) {
                    throw new HttpError("Échec de copie. Nettoyage impossible : la nouvelle base $target subsiste ; suppression manuelle nécessaire.", 500);
                }
            }
            if (!$created && $error instanceof PDOException && $error->getCode() === '42P04') {
                throw new HttpError('La base PostgreSQL cible existe déjà.', 409);
            }
            throw new HttpError(($currentTable ? "Table $currentTable : " : '') . 'Échec de copie vers PostgreSQL. La nouvelle destination a été supprimée si elle avait été créée.', 422);
        }
        return ['database' => $target, 'tables' => count($tables), 'rows' => $total,
            'message' => "$target : " . count($tables) . " table(s), $total ligne(s) copiée(s)."];
    }
}
