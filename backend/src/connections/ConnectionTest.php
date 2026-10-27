<?php

/*Ce que fait cette classe :
- Elle vérifie les informations reçues, même si le formulaire possède déjà des champs obligatoires. Une requête peut arriver sans passer par ton formulaire.
- Le DSN décrit la destination : moteur, serveur, port et base. L’utilisateur et le mot de passe sont transmis séparément à PDO.
- new PDO() ouvre la connexion, puis SELECT 1 vérifie que le serveur accepte une requête. Cette requête ne modifie aucune donnée.
- catch transforme l’échec en message affichable, sans renvoyer les détails techniques ni le mot de passe.*/

final class ConnectionTest
{
    // Vérifie les informations reçues puis essaie une connexion et une requête sans modifier les données.
    // Le tableau reçu contient le moteur, le serveur, le port et les identifiants. Après validation, PDO ouvre une
    // connexion et SELECT 1 vérifie qu’elle répond. Le catch renvoie un message lisible sans enregistrer la
    // connexion.
    public static function test(array $data): array
    {
        $engine = $data['engine'] ?? '';

        if (!in_array($engine, ['mysql', 'postgres'], true)) {
            throw new HttpError('Choisissez MySQL ou PostgreSQL.');
        }

        foreach (['host', 'database', 'username', 'password'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                throw new HttpError('Les informations de connexion sont incomplètes.');
            }
        }

        $host = trim($data['host']);
        $database = trim($data['database']);
        $username = trim($data['username']);
        $password = $data['password'];

        if (!preg_match('/^[a-zA-Z0-9_.:-]+$/D', $host)) {
            throw new HttpError('Adresse du serveur invalide.');
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]{0,63}$/D', $database)) {
            throw new HttpError('Nom de base invalide.');
        }

        if ($username === '') {
            throw new HttpError('Indiquez un utilisateur.');
        }

        $portValue = $data['port'] ?? null;

        if (!is_string($portValue) && !is_int($portValue)) {
            throw new HttpError('Port invalide.');
        }

        $port = filter_var($portValue, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            throw new HttpError('Le port doit être compris entre 1 et 65535.');
        }

        // Cette adresse indique à PDO quel moteur, serveur, port et base contacter.
        $dsn = $engine === 'mysql'
            ? "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4"
            : "pgsql:host=$host;port=$port;dbname=$database;connect_timeout=5";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // Cette petite requête vérifie que la connexion fonctionne sans changer de données.
            $pdo->query('SELECT 1');
        } catch (PDOException $exception) {
            error_log('Test de connexion refusé : SQLSTATE ' . $exception->getCode());

            throw new HttpError(
                'Connexion impossible. Vérifiez le serveur, la base et les identifiants.',
                503
            );
        }

        return ['message' => 'Connexion à la base réussie.'];
    }
}
