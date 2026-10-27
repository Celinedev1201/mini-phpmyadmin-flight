<?php

// Une erreur métier possède un message affichable et un statut HTTP.
final class HttpError extends RuntimeException
{
    // Crée une erreur avec un message lisible et le code HTTP à renvoyer au navigateur.
    public function __construct(string $message, public int $status = 400)
    {
        parent::__construct($message);
    }
}

final class Api
{
    // Lit les données JSON envoyées par le formulaire et refuse un format incorrect.
    public static function body(): array
    {
        try {
            $body = json_decode(Flight::request()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new HttpError('Corps JSON invalide.');
        }
        if (!is_array($body) || ($body !== [] && array_is_list($body))) {
            throw new HttpError('Un objet JSON est attendu.');
        }
        return $body;
    }

    // Les détails SQL restent côté serveur ; aucun secret ne rejoint la réponse.
    // Exécute l’action demandée et transforme son résultat ou son erreur en réponse JSON.
    public static function run(callable $action, int $status = 200): void
    {
        try {
            Flight::json($action(), $status);
        } catch (HttpError $exception) {
            Flight::json(['message' => $exception->getMessage()], $exception->status);
        } catch (PDOException $exception) {
            $sqlState = (string) $exception->getCode();
            if (str_starts_with($sqlState, '08') || $sqlState === '28P01') {
                error_log('Connexion SQL indisponible : SQLSTATE ' . $sqlState);
                Flight::json(['message' => 'Connexion à la base indisponible. Vérifiez le serveur et sa configuration.'], 503);
                return;
            }
            $code = (int) ($exception->errorInfo[1] ?? 0);
            $status = 422;
            $message = 'Opération refusée. Vérifiez les valeurs, les types et les contraintes de la table.';
            if (in_array($code, [1007, 1050, 1060, 1062], true)) {
                $status = 409;
                $message = 'Ce nom ou cette clé existe déjà.';
            } elseif (in_array($code, [1008, 1049, 1051, 1054, 1091, 1146], true)) {
                $status = 404;
                $message = 'La base, la table ou la colonne n’existe plus. Actualisez la liste.';
            } elseif (in_array($code, [1045, 2002, 2006], true)) {
                $status = 503;
                $message = 'Connexion MySQL indisponible.';
            }
            Flight::json(['message' => $message], $status);
        }
    }
}
