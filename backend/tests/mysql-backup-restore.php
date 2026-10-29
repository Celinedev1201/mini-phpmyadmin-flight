<?php

require dirname(__DIR__) . '/src/database/Database.php';
require dirname(__DIR__) . '/src/http/Api.php';
require dirname(__DIR__) . '/src/validation/SqlValidation.php';
require dirname(__DIR__) . '/src/services/BackupService.php';
require dirname(__DIR__) . '/src/backups/BackupCatalog.php';
require dirname(__DIR__) . '/src/services/RestoreService.php';

$suffix = bin2hex(random_bytes(4));
$source = 'safebase_test_mysql_' . $suffix;
$target = 'safebase_test_restore_' . $suffix;
$sourceCreated = false;
$targetRestored = false;
$restoreAttempted = false;
$backupPath = null;
$exitCode = 0;

echo "TEST MYSQL : sauvegarde, restauration et données\n\n";

try {
    $pdo = Database::connect();

    $pdo->exec("CREATE DATABASE `$source` CHARACTER SET utf8mb4");
    $sourceCreated = true;

    $pdo->exec(
        "CREATE TABLE `$source`.`produits` (
            id INT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL
        )"
    );
    $statement = $pdo->prepare(
        "INSERT INTO `$source`.`produits` (id, nom) VALUES (?, ?)"
    );
    $statement->execute([1, 'Produit témoin']);
    echo "RÉUSSI : base source et donnée témoin créées.\n";

    $backup = BackupService::create($source);
    $backupPath = '/var/backups/safebase/mysql/'
        . $source . '/' . $backup['filename'];

    if (!is_file($backupPath) || filesize($backupPath) === 0) {
        throw new RuntimeException('Sauvegarde absente ou vide.');
    }
    echo "RÉUSSI : sauvegarde SQL non vide créée.\n";

    // Restaure dans une seconde base, sans modifier la source.
    $restoreAttempted = true;
    RestoreService::restore($source, $backup['filename'], $target);
    $targetRestored = true;

    $rows = $pdo->query(
        "SELECT id, nom FROM `$target`.`produits` ORDER BY id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $expected = [['id' => '1', 'nom' => 'Produit témoin']];

    if ($rows !== $expected) {
        throw new RuntimeException('Les données restaurées sont incorrectes.');
    }
    echo "RÉUSSI : table restaurée avec exactement la donnée attendue.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "ÉCHEC : " . $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    // Supprime seulement les bases dont la création a été confirmée.
    foreach ([$source => $sourceCreated, $target => $targetRestored] as $base => $created) {
        if (!$created) {
            continue;
        }
        try {
            $pdo->exec("DROP DATABASE `$base`");
            echo "Nettoyage : $base supprimée.\n";
        } catch (Throwable $exception) {
            fwrite(STDERR, "ÉCHEC du nettoyage : $base\n");
            $exitCode = 1;
        }
    }

    if ($restoreAttempted && !$targetRestored) {
        fwrite(STDERR, "À vérifier : une base cible peut subsister : $target\n");
    }

    if ($backupPath !== null && is_file($backupPath)) {
        if (!unlink($backupPath) || !rmdir(dirname($backupPath))) {
            fwrite(STDERR, "ÉCHEC du nettoyage du fichier ou dossier de test.\n");
            $exitCode = 1;
        } else {
            echo "Nettoyage : sauvegarde de test supprimée.\n";
        }
    }
}

exit($exitCode);