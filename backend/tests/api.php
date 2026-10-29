<?php

$path = $argv[1] ?? '/api/databases';
$key = $argv[2] ?? 'databases';

$baseUrl = getenv('TEST_API_URL') ?: 'http://127.0.0.1:8000';
$url = $baseUrl . $path;

// Permet de lire la réponse même si le serveur renvoie une erreur HTTP.
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    fwrite(STDERR, "ÉCHEC : impossible de joindre l’API.\n");
    exit(1);
}

$statusLine = $http_response_header[0] ?? '';

if (!preg_match('/^HTTP\/\S+\s+200\b/', $statusLine)) {
    fwrite(STDERR, "ÉCHEC : HTTP 200 attendu, reçu : $statusLine\n");
    exit(1);
}

try {
    $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, "ÉCHEC : la réponse n’est pas un JSON valide.\n");
    exit(1);
}

if (
    !is_array($data)
    || !isset($data[$key])
    || !is_array($data[$key])
    || !array_is_list($data[$key])
) {
    fwrite(STDERR, "ÉCHEC : une liste « $key » est attendue.\n");
    exit(1);
}

echo "RÉUSSI : $path répond en HTTP 200 avec une liste « $key ».\n";