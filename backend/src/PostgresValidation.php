<?php

// Valide les noms acceptés par notre application pour PostgreSQL.
final class PostgresValidation
{
    public static function database(string $name): void
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,62}$/D', $name)) {
            throw new HttpError(
                'Nom invalide : commencer par une lettre, puis lettres, chiffres ou underscores. Maximum 63 caractères.',
                400
            );
        }

        if (in_array(strtolower($name), [
            'postgres',
            'template0',
            'template1',
        ], true)) {
            throw new HttpError('Base système PostgreSQL protégée.', 403);
        }
    }
}