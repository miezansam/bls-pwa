<?php

declare(strict_types=1);

use App\Support\Telephone;

test('les formats de saisie courants donnent le même numéro international', function (string $saisie) {
    expect(Telephone::normaliser($saisie))->toBe('+2250700000000');
})->with([
    '07 00 00 00 00',
    '0700000000',
    '+225 07 00 00 00 00',
    '+2250700000000',
    '00225 07 00 00 00 00',
    '225 0700000000',
    '07.00.00.00.00',
    '(07) 00-00-00-00',
    ' 07 00 00 00 00 ',
]);

test('un numéro invalide est refusé', function (string $saisie) {
    expect(Telephone::normaliser($saisie))->toBeNull();
})->with([
    '',
    'abc',
    '0700',
    '+33612345678',
    '700000000',
    '07000000000',
    '+225700000000',
    '+22507000000000',
]);

test('le numéro est présenté de façon lisible', function () {
    expect(Telephone::formater('+2250700000000'))->toBe('+225 07 00 00 00 00');
});
