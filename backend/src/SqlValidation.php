<?php

final class SqlValidation
{
    public const SYSTEM_DATABASES = ['mysql', 'sys', 'information_schema', 'performance_schema'];
    public const TYPES = [
        'VARCHAR' => 'VARCHAR(255)', 'TEXT' => 'TEXT', 'INT' => 'INT',
        'DECIMAL' => 'DECIMAL(10,2)', 'DATE' => 'DATE',
    ];

    public static function validName(mixed $name): bool
    {
        return is_string($name) && preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $name) === 1;
    }

    public static function name(mixed $name): string
    {
        if (!self::validName($name)) {
            throw new HttpError('Nom invalide : 1 à 64 caractères, lettres, chiffres et underscores ; commencer par une lettre.');
        }
        return $name;
    }

    public static function database(mixed $name): string
    {
        self::name($name);
        if (in_array(strtolower($name), self::SYSTEM_DATABASES, true)) {
            throw new HttpError('Base système protégée.', 403);
        }
        return $name;
    }

    public static function type(mixed $type): string
    {
        if (!is_string($type) || !isset(self::TYPES[$type])) {
            throw new HttpError('Type non autorisé.');
        }
        return self::TYPES[$type];
    }

    public static function confirmed(array $data, string $name, bool $explicitName = false): void
    {
        if (($data['confirmed'] ?? null) !== true) {
            throw new HttpError('Confirmation de suppression obligatoire.');
        }
        if ($explicitName && ($data['name'] ?? null) !== $name) {
            throw new HttpError('Recopiez exactement le nom de la base pour la supprimer.');
        }
    }

    // Vérifie que les valeurs visent des colonnes existantes et modifiables.
    public static function rowValues(SchemaService $schema, mixed $values, bool $inserting): array
    {
        if (!is_array($values) || $values === []) {
            throw new HttpError('Veuillez fournir les valeurs de la ligne.');
        }
        $allowed = [];
        foreach ($schema->columns() as $column) {
            if ($column['GENERATION_EXPRESSION'] !== ''
                || ($inserting && str_contains($column['EXTRA'], 'auto_increment'))) {
                continue;
            }
            $allowed[] = $column['COLUMN_NAME'];
        }
        foreach ($values as $name => $value) {
            SqlValidation::name($name);
            if (!in_array($name, $allowed, true)) {
                throw new HttpError('Une colonne est inexistante ou non modifiable.');
            }
            if (!is_scalar($value) && $value !== null) {
                throw new HttpError('Valeur de colonne invalide.');
            }
        }
        return $values;
    }
}
