<?php

// Reçoit les informations de connexion et renvoie le résultat du test, sans les enregistrer.
// Flight appelle cette fonction quand le navigateur demande cette adresse avec la méthode indiquée.
Flight::route('POST /api/connections/test', function (): void {
    Api::run(function (): array {
        $data = Api::body();

        return ConnectionTest::test($data);
    });
});