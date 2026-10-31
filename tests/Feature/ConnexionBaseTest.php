<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('utilise bien PostgreSQL et la base de test', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
    expect(DB::connection()->getDatabaseName())->toBe('bls_pwa_test');
});