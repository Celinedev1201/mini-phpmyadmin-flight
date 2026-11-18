<?php

require_once __DIR__ . '/../src/validation/SqlValidation.php';

$cases = [
    'Une lettre seule' => ['A', false],
    'Lettres, chiffres et underscore' => ['safebase_2026', true],
    'Maximum de 64 caractères' => [str_repeat('a', 64), true],
    '65 caractères' => [str_repeat('a', 65), false],
    'Nom vide' => ['', false],
    'Commence par un chiffre' => ['1safebase', false],
    'Contient un tiret' => ['safe-base', false],
    'Se termine par un saut de ligne' => ["safebase\n", false],
    'Valeur non textuelle' => [123, false],
];

$failures = 0;

foreach ($cases as $label => [$input, $expected]) {
    $actual = SqlValidation::validName($input);

    if ($actual !== $expected) {
        fwrite(STDERR, "ÉCHEC : $label\n");
        $failures++;
        continue;
    }

    echo "RÉUSSI : $label\n";
}

echo count($cases) . " cas vérifiés, $failures échec(s).\n";
exit($failures === 0 ? 0 : 1);