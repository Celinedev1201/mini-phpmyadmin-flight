<?php

Flight::route('POST /api/connections/test', function (): void {
    Api::run(function (): array {
        $data = Api::body();

        return ConnectionTest::test($data);
    });
});