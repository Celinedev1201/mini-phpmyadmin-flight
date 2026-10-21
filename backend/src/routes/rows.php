<?php

// Reçoit les données d’une ligne à ajouter
Flight::route(
    'POST /api/databases/@database/tables/@table/rows',
    function (string $database, string $table): void {
        $data = Flight::request()->data;

        $validName = '/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/';

        if (
            !preg_match($validName, $database)
            || !preg_match($validName, $table)
        ) {
            Flight::json(['message' => 'Nom de base ou de table invalide.'], 400);
            return;
        }

        if (in_array(strtolower($database), [
            'mysql',
            'sys',
            'information_schema',
            'performance_schema',
        ], true)) {
            Flight::json(['message' => 'Base système protégée.'], 403);
            return;
        }

        $values = $data->values;

        if (!is_array($values) || $values === []) {
            Flight::json([
                'message' => 'Veuillez fournir les valeurs de la ligne.',
            ], 400);

            return;
        }

        foreach ($values as $column => $value) {
            if (!is_string($column) || !preg_match($validName, $column)) {
                Flight::json(['message' => 'Nom de colonne invalide.'], 400);
                return;
            }

            if (!is_scalar($value) && $value !== null) {
                Flight::json(['message' => 'Valeur de colonne invalide.'], 400);
                return;
            }
        }

        try {
            $pdo = Database::connect();

            // Vérifie les colonnes réellement présentes dans la table
            $statement = $pdo->prepare(
                'SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :database
           AND TABLE_NAME = :table
           AND EXTRA NOT LIKE :automatic
           AND GENERATION_EXPRESSION = :expression'
            );

            $statement->execute([
                'database' => $database,
                'table' => $table,
                'automatic' => '%auto_increment%',
                'expression' => '',
            ]);

            $allowedColumns = $statement->fetchAll(PDO::FETCH_COLUMN);
            $columns = array_keys($values);

            if (array_diff($columns, $allowedColumns) !== []) {
                Flight::json([
                    'message' => 'Une colonne est inexistante ou non modifiable.',
                ], 400);
                return;
            }

            // Prépare les noms des colonnes et les emplacements des valeurs
            $columnList = implode(', ', array_map(
                fn(string $column): string => "`$column`",
                $columns
            ));

            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $statement = $pdo->prepare(
                "INSERT INTO `$database`.`$table` ($columnList)
         VALUES ($placeholders)"
            );

            $statement->execute(array_values($values));

            Flight::json([
                'message' => 'Ligne ajoutée avec succès',
                'id' => $pdo->lastInsertId(),
            ], 201);
        } catch (PDOException $exception) {
            Flight::json([
                'message' => 'Insertion impossible. Vérifiez les valeurs et les contraintes de la table.',
            ], 400);
        }
    }
);

// Récupère les 50 premières lignes d’une table
Flight::route(
    'GET /api/databases/@database/tables/@table/rows',
    function (string $database, string $table): void {
        $validName = '/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/';

        if (
            !preg_match($validName, $database)
            || !preg_match($validName, $table)
        ) {
            Flight::json(['message' => 'Nom invalide.'], 400);
            return;
        }

        if (in_array(strtolower($database), [
            'mysql',
            'sys',
            'information_schema',
            'performance_schema',
        ], true)) {
            Flight::json(['message' => 'Base système protégée.'], 403);
            return;
        }

        try {
            $pdo = Database::connect();

            $statement = $pdo->query(
                "SELECT * FROM `$database`.`$table` LIMIT 50"
            );

            Flight::json([
                'rows' => $statement->fetchAll(PDO::FETCH_ASSOC),
            ]);
        } catch (PDOException $exception) {
            Flight::json([
                'message' => 'Impossible de récupérer les lignes.',
            ], 500);
        }
    }
);
