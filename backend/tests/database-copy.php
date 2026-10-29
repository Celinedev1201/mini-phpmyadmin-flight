<?php

foreach (['http/Api', 'database/Database', 'database/PostgresDatabase',
    'validation/SqlValidation', 'validation/PostgresValidation',
    'services/PostgresColumnMapper', 'services/DatabaseCopySnapshot',
    'services/DatabaseCopyService'] as $file) {
    require dirname(__DIR__) . '/src/' . $file . '.php';
}

function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function refused(callable $action, string $reason): void
{
    try { $action(); } catch (HttpError $error) {
        verify(str_contains($error->getMessage(), $reason), 'Motif incorrect : ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Refus attendu : ' . $reason);
}

$suffix = bin2hex(random_bytes(5));
$source = 'copy_source_' . $suffix;
$target = 'copy_target_' . $suffix;
$failure = 'copy_failure_' . $suffix;
$sourceCreated = $targetCreated = $failureCreated = false;
$pg = null;
$exitCode = 0;
try {
    $mysql = Database::connect();
    $admin = PostgresDatabase::connect();
    $mysql->exec("CREATE DATABASE `$source` CHARACTER SET utf8mb4");
    $sourceCreated = true;
    $mysql->exec("CREATE TABLE `$source`.produits (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(80) NOT NULL, note TEXT NULL, prix DECIMAL(10,2) NOT NULL, jour DATE NULL, quantite INT NOT NULL) ENGINE=InnoDB");
    $mysql->exec("CREATE TABLE `$source`.vide (id INT AUTO_INCREMENT PRIMARY KEY, contenu TEXT NULL) ENGINE=InnoDB AUTO_INCREMENT=17");
    $mysql->exec("INSERT INTO `$source`.produits VALUES (3, 'Café été', NULL, 12.30, '2026-10-29', -2), (8, 'Crème', 'déjà', 0.01, NULL, 0)");
    $mysql->exec("ALTER TABLE `$source`.produits AUTO_INCREMENT=23");
    $result = DatabaseCopyService::copy($source, $target);
    $targetCreated = true;
    verify($result['tables'] === 2 && $result['rows'] === 2 && $result['database'] === $target, 'Totaux incorrects.');
    $pg = PostgresDatabase::connect($target);
    $pg->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    foreach (['produits', 'vide'] as $table) {
        verify($mysql->query("SELECT * FROM `$source`.`$table` ORDER BY id")->fetchAll() === $pg->query("SELECT * FROM public.$table ORDER BY id")->fetchAll(), "Données différentes : $table");
        $sourceColumns = $mysql->query("SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$source' AND TABLE_NAME = '$table' ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_NUM);
        $targetColumns = $pg->query("SELECT column_name, is_nullable FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_NUM);
        verify($sourceColumns === $targetColumns, "Ordre/noms/NULL incorrects : $table");
        verify($pg->query("SELECT a.attname FROM pg_index i JOIN pg_attribute a ON a.attrelid=i.indrelid AND a.attnum=ANY(i.indkey) WHERE i.indrelid='public.$table'::regclass AND i.indisprimary")->fetchAll(PDO::FETCH_COLUMN) === ['id'], 'Clé primaire absente.');
    }
    $types = $pg->query("SELECT column_name, data_type, character_maximum_length, numeric_precision, numeric_scale, is_identity FROM information_schema.columns WHERE table_name='produits' ORDER BY ordinal_position")->fetchAll();
    verify(array_column($types, 'data_type') === ['bigint', 'character varying', 'text', 'numeric', 'date', 'integer'], 'Types incorrects.');
    verify($types[0]['is_identity'] === 'YES' && $types[1]['character_maximum_length'] === '80' && $types[3]['numeric_precision'] === '10' && $types[3]['numeric_scale'] === '2', 'Paramètres de types incorrects.');
    verify($pg->query("INSERT INTO vide DEFAULT VALUES RETURNING id")->fetchColumn() === '17', 'Compteur de table vide incorrect.');
    verify($pg->query("INSERT INTO produits(nom, prix, quantite) VALUES ('Suite', 1, 1) RETURNING id")->fetchColumn() === '23', 'Compteur incorrect.');
    foreach (['-1', '4294967296'] as $invalid) {
        try {
            $pg->exec("INSERT INTO produits(id, nom, prix, quantite) VALUES ($invalid, 'Interdit', 1, 1)");
            throw new RuntimeException('Plage unsigned non contrôlée.');
        } catch (PDOException $error) { verify($error->getCode() === '23514', 'Contrainte unsigned incorrecte.'); }
    }
    refused(fn () => DatabaseCopyService::copy($source, $target), 'existe déjà');
    verify($pg->query('SELECT COUNT(*) FROM produits')->fetchColumn() === '3', 'Destination existante modifiée.');
    $pg = null;
    $exists = function (string $name) use ($admin): bool {
        $q = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
        $q->execute([$name]);
        return (bool) $q->fetchColumn();
    };
    $cases = [
        ['CREATE VIEW `%s`.refus AS SELECT id FROM `%s`.produits', 'DROP VIEW `%s`.refus', 'Table refus : Vue'],
        ['CREATE TABLE `%s`.refus (id BIGINT) ENGINE=InnoDB', 'DROP TABLE `%s`.refus', 'Table refus : Type'],
        ['CREATE TABLE `%s`.refus (id INT DEFAULT 7) ENGINE=InnoDB', 'DROP TABLE `%s`.refus', 'Table refus : Valeur par défaut'],
        ['CREATE TABLE `%s`.refus (id INT, INDEX(id)) ENGINE=InnoDB', 'DROP TABLE `%s`.refus', 'Table refus : Index'],
        ['CREATE TABLE `%s`.refus (id INT, x INT AS (id+1)) ENGINE=InnoDB', 'DROP TABLE `%s`.refus', 'Table refus : Les colonnes calculées'],
        ['CREATE TABLE `%s`.refus (id INT) ENGINE=MyISAM', 'DROP TABLE `%s`.refus', 'Table refus : Seul le moteur'],
        ['CREATE TABLE `%s`.refus (id INT UNSIGNED, FOREIGN KEY(id) REFERENCES `%s`.produits(id)) ENGINE=InnoDB', 'DROP TABLE `%s`.refus', 'Table refus : Contrainte'],
        ['CREATE TRIGGER `%s`.refus BEFORE INSERT ON `%s`.produits FOR EACH ROW SET NEW.nom = NEW.nom', 'DROP TRIGGER `%s`.refus', 'Table produits : Déclencheur'],
    ];
    foreach ($cases as [$create, $drop, $reason]) {
        $mysql->exec(sprintf($create, $source, $source));
        try {
            refused(fn () => DatabaseCopyService::copy($source, $failure), $reason);
            verify(!$exists($failure), 'Destination créée malgré le refus.');
        } finally { $mysql->exec(sprintf($drop, $source)); }
    }
    $long = str_repeat('a', 64);
    $mysql->exec("CREATE TABLE `$source`.`$long` (id INT) ENGINE=InnoDB");
    refused(fn () => DatabaseCopyService::copy($source, $failure), '63 octets');
    $mysql->exec("DROP TABLE `$source`.`$long`");
    $mysql->exec("CREATE TABLE `$source`.refus (id INT) ENGINE=InnoDB");
    $insert = $mysql->prepare("INSERT INTO `$source`.refus VALUES (?)");
    for ($i = 0; $i < 1001; $i++) $insert->execute([$i]);
    refused(fn () => DatabaseCopyService::copy($source, $failure), '1 000 lignes');
    $mysql->exec("DROP TABLE `$source`.refus");
    // NUL accepté par MySQL mais refusé par PostgreSQL, après création des tables.
    $mysql->exec("CREATE TABLE `$source`.zz_echec (note TEXT) ENGINE=InnoDB");
    $insert = $mysql->prepare("INSERT INTO `$source`.zz_echec VALUES (?)");
    $insert->execute(["échec\0"]);
    refused(function () use ($source, $failure, &$failureCreated) {
        DatabaseCopyService::copy($source, $failure);
        $failureCreated = true;
    }, 'Table zz_echec : Échec');
    verify(!$exists($failure), 'Base partiellement copiée restante.');
    verify($mysql->query("SELECT COUNT(*) FROM `$source`.produits")->fetchColumn() === '2', 'Source modifiée.');
    echo "RÉUSSI : copie complète, structure, données, identités, refus et annulation/nettoyage.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'ÉCHEC copie : ' . $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    $pg = null;
    try {
        if ($failureCreated) $admin->exec("DROP DATABASE \"$failure\"");
        if ($targetCreated) $admin->exec("DROP DATABASE \"$target\"");
        if ($sourceCreated) $mysql->exec("DROP DATABASE `$source`");
    } catch (Throwable $error) {
        fwrite(STDERR, "ÉCHEC nettoyage des ressources de test $source / $target.\n");
        $exitCode = 1;
    }
}
exit($exitCode);
