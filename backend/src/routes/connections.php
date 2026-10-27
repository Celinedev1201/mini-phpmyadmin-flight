<?php

// Reçoit les informations de connexion et renvoie le résultat du test, sans les enregistrer.
Flight::route('POST /api/connections/test', function (): void {
    Api::run(function (): array {
        $data = Api::body();

        return ConnectionTest::test($data);
    });
});