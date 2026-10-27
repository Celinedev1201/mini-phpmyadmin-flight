<?php

final class AlertLogger
{
    // /var/backups/safebase est le dossier de sauvegardes partagé avec l’ordinateur par Docker.
    private const FILE = '/var/backups/safebase/alerts.jsonl';

    // Les paramètres décrivent le moteur, l’action, la base et le message de l’échec. gmdate('c') ajoute la date
    // UTC. json_encode() transforme ces informations en JSON et PHP_EOL termine la ligne. Le format JSONL garde
    // ainsi une alerte par ligne. FILE_APPEND conserve les anciennes lignes et LOCK_EX évite que deux écritures se
    // mélangent. Le catch journalise un problème d’écriture sans interrompre l’application.
    public static function record(
        string $engine,
        string $operation,
        string $database,
        string $message
    ): void {
        try {
            $directory = dirname(self::FILE);

            if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
                throw new RuntimeException('Dossier des alertes inaccessible.');
            }

            $alert = [
                'id' => bin2hex(random_bytes(8)),
                'date' => gmdate('c'),
                'engine' => $engine,
                'operation' => $operation,
                'database' => $database,
                'message' => $message,
            ];

            $line = json_encode(
                $alert,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ) . PHP_EOL;

            $written = file_put_contents(
                self::FILE,
                $line,
                FILE_APPEND | LOCK_EX
            );

            if ($written === false || $written !== strlen($line)) {
                throw new RuntimeException('Écriture de l’alerte impossible.');
            }
        } catch (Throwable $exception) {
            // Un problème de journalisation ne doit pas masquer l’erreur initiale.
            error_log('Impossible d’enregistrer une alerte SafeBase.');
        }
    }
}