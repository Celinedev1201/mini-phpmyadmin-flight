<?php

Flight::route('GET /api/alerts', function (): void {
    Api::run(function (): array {
        return [
            'alerts' => AlertCatalog::latest(),
        ];
    });
});