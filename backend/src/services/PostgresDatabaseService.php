<?php

// Gère la suppression sans toucher aux sauvegardes conservées sur disque.
final class PostgresDatabaseService
{
    // Vérifie les protections et la confirmation puis supprime la base sans effacer ses sauvegardes.
    public static function delete(string $database, array $data): array
    {
        PostgresValidation::database($database);
        SqlValidation::confirmed($data, $database, true);

        // Cette base sert aux connexions de l’application et du scheduler.
        if ($database === (getenv('PG_DATABASE') ?: 'postgres')) {
            throw new HttpError(
                'Cette base est utilisée par la configuration PostgreSQL. Changez d’abord POSTGRES_DB et la base des sauvegardes automatiques.',
                409
            );
        }

        $pdo = PostgresDatabase::connect();
        $statement = $pdo->prepare('SELECT datistemplate FROM pg_database WHERE datname = ?');
        $statement->execute([$database]);
        $metadata = $statement->fetch();
        if ($metadata === false) {
            throw new HttpError('Cette base PostgreSQL n’existe plus. Actualisez la liste.', 404);
        }
        if ($metadata['datistemplate']) {
            throw new HttpError('Base modèle PostgreSQL protégée.', 403);
        }

        try {
            // Aucun FORCE : les connexions actives ne sont pas interrompues.
            $pdo->exec("DROP DATABASE \"$database\"");
        } catch (PDOException $exception) {
            $code = (string) $exception->getCode();
            if ($code === '55006') {
                throw new HttpError('Cette base est utilisée. Fermez ses connexions avant de la supprimer.', 409);
            }
            if ($code === '42501') {
                throw new HttpError('Le compte PostgreSQL n’a pas le droit de supprimer cette base.', 403);
            }
            if ($code === '3D000') {
                throw new HttpError('Cette base PostgreSQL n’existe plus. Actualisez la liste.', 404);
            }
            error_log('Suppression PostgreSQL ' . $database . ' : SQLSTATE ' . $code);
            throw new HttpError('Impossible de supprimer cette base PostgreSQL.', 500);
        }

        return ['message' => 'Base PostgreSQL supprimée. Les sauvegardes sont conservées.'];
    }
}
