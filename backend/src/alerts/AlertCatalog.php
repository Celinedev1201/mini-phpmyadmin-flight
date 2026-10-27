<?php

final class AlertCatalog
{
    private const FILE = '/var/backups/safebase/alerts.jsonl';

    public static function latest(): array
    {
        if (!is_file(self::FILE)) {
            return [];
        }

        $handle = fopen(self::FILE, 'r');

        if ($handle === false) {
            throw new HttpError('Impossible de lire les alertes.', 500);
        }

        try {
            // Le verrou partagé évite de lire pendant une écriture.
            if (!flock($handle, LOCK_SH)) {
                throw new HttpError('Impossible de lire les alertes.', 500);
            }

            $alerts = [];

            while (($line = fgets($handle)) !== false) {
                try {
                    $alert = json_decode(
                        $line,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (JsonException $exception) {
                    // Une ligne JSON abîmée ne bloque pas les autres.
                    continue;
                }

                if (!is_array($alert) || !isset(
                    $alert['id'],
                    $alert['date'],
                    $alert['engine'],
                    $alert['operation'],
                    $alert['database'],
                    $alert['message']
                )) {
                    continue;
                }

                $alerts[] = $alert;

                if (count($alerts) > 50) {
                    array_shift($alerts);
                }
            }

            if (!feof($handle)) {
                throw new HttpError('Lecture des alertes interrompue.', 500);
            }

            return array_reverse($alerts);
        } finally {
            // Libère le verrou et ferme le fichier, même après une erreur.
            fclose($handle);
        }
    }
}