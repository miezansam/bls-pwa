<?php

declare(strict_types=1);

use App\Actions\ConnecterParOtpAction;
use App\Actions\DemanderCodeOtpAction;
use App\Actions\InscrireClientAction;
use App\Enums\ObjectifOtp;
use App\Models\CodeOtp;
use App\Models\Utilisateur;
use App\Services\Sms\EnvoyeurSmsJournal;

const TEL = '+2250700000001';

function demanderConnexion(string $telephone = TEL): string
{
    return app(DemanderCodeOtpAction::class)->executer($telephone, ObjectifOtp::Connexion);
}

function clientExistant(array $surcharges = []): Utilisateur
{
    return Utilisateur::factory()->client()->create(array_merge(['telephone' => TEL], $surcharges));
}

test('un code à 6 chiffres est envoyé par SMS et seule son empreinte est stockée', function () {
    $sms = smsFictif();

    $telephone = app(DemanderCodeOtpAction::class)->executer('07 00 00 00 01', ObjectifOtp::Inscription, '127.0.0.1');

    expect($telephone)->toBe(TEL);
    expect($sms->messages)->toHaveCount(1);
    expect($sms->messages[0]['telephone'])->toBe(TEL);

    $code = $sms->dernierCode();
    expect($code)->toMatch('/^\d{6}$/');

    $otp = CodeOtp::query()->firstOrFail();
    expect($otp->telephone)->toBe(TEL);
    expect($otp->adresse_ip)->toBe('127.0.0.1');
    expect($otp->tentatives)->toBe(0);
    expect($otp->expire_le->isFuture())->toBeTrue();
    expect($otp->code_hash)->toHaveLength(64);
    expect($otp->code_hash)->not->toBe($code);
});

test('un numéro invalide est refusé sans envoyer de SMS', function () {
    $sms = smsFictif();

    expect(motifDeLEchec(fn () => app(DemanderCodeOtpAction::class)->executer('12345', ObjectifOtp::Inscription)))
        ->toBe('telephone_invalide');
    expect($sms->messages)->toBeEmpty();
});

test('redemander un code trop vite est refusé, puis accepté après 60 secondes', function () {
    smsFictif();
    $demander = app(DemanderCodeOtpAction::class);
    $demander->executer(TEL, ObjectifOtp::Inscription);

    expect(motifDeLEchec(fn () => $demander->executer(TEL, ObjectifOtp::Inscription)))->toBe('trop_de_demandes');

    $this->travel(61)->seconds();
    $demander->executer(TEL, ObjectifOtp::Inscription);

    expect(CodeOtp::count())->toBe(2);
});

test('au plus 5 codes par heure et par numéro', function () {
    smsFictif();
    $demander = app(DemanderCodeOtpAction::class);

    for ($i = 0; $i < 5; $i++) {
        $demander->executer(TEL, ObjectifOtp::Inscription);
        $this->travel(61)->seconds();
    }

    expect(motifDeLEchec(fn () => $demander->executer(TEL, ObjectifOtp::Inscription)))->toBe('limite_horaire');
});

test('un nouveau code annule le précédent', function () {
    $sms = smsFictif();
    clientExistant();

    demanderConnexion();
    $ancien = $sms->dernierCode();

    $this->travel(61)->seconds();
    demanderConnexion();
    $nouveau = $sms->dernierCode();

    $connecter = app(ConnecterParOtpAction::class);

    // Le cas, très rare, où les deux codes tirés sont identiques ne prouve rien : on l'écarte
    if ($ancien !== $nouveau) {
        expect(motifDeLEchec(fn () => $connecter->executer(TEL, $ancien)))->toBe('code_incorrect');
    }

    expect($connecter->executer(TEL, $nouveau)->telephone)->toBe(TEL);
});

test('un SMS qui ne part pas n\'enregistre aucun code', function () {
    $sms = smsFictif();
    $sms->echouer = true;

    expect(motifDeLEchec(fn () => app(DemanderCodeOtpAction::class)->executer(TEL, ObjectifOtp::Inscription)))
        ->toBe('sms_indisponible');
    expect(CodeOtp::count())->toBe(0);
});

test('un client existant se connecte avec le bon code, même saisi avec un espace', function () {
    $sms = smsFictif();
    $client = clientExistant(['telephone_verifie_le' => null]);

    demanderConnexion();
    $code = $sms->dernierCode();

    $utilisateur = app(ConnecterParOtpAction::class)->executer('07 00 00 00 01', substr($code, 0, 3) . ' ' . substr($code, 3));

    expect($utilisateur->is($client))->toBeTrue();
    expect($utilisateur->fresh()->telephone_verifie_le)->not->toBeNull();
    expect($utilisateur->fresh()->derniere_connexion_le)->not->toBeNull();
    expect(CodeOtp::firstOrFail()->utilise_le)->not->toBeNull();
});

test('un livreur se connecte avec son numéro', function () {
    $sms = smsFictif();
    $livreur = Utilisateur::factory()->livreur()->create(['telephone' => TEL]);

    demanderConnexion();

    expect(app(ConnecterParOtpAction::class)->executer(TEL, $sms->dernierCode())->is($livreur))->toBeTrue();
});

test('un code ne sert qu\'une fois', function () {
    $sms = smsFictif();
    clientExistant();
    demanderConnexion();
    $code = $sms->dernierCode();
    $connecter = app(ConnecterParOtpAction::class);

    $connecter->executer(TEL, $code);

    expect(motifDeLEchec(fn () => $connecter->executer(TEL, $code)))->toBe('code_expire');
});

test('un code expire au bout de 5 minutes', function () {
    $sms = smsFictif();
    clientExistant();
    demanderConnexion();
    $code = $sms->dernierCode();

    $this->travel(6)->minutes();

    expect(motifDeLEchec(fn () => app(ConnecterParOtpAction::class)->executer(TEL, $code)))->toBe('code_expire');
});

test('un mauvais code est compté, puis la connexion est bloquée après 5 essais', function () {
    $sms = smsFictif();
    clientExistant();
    demanderConnexion();
    $bon = $sms->dernierCode();
    $mauvais = $bon === '000000' ? '111111' : '000000';
    $connecter = app(ConnecterParOtpAction::class);

    for ($essai = 1; $essai <= 5; $essai++) {
        expect(motifDeLEchec(fn () => $connecter->executer(TEL, $mauvais)))->toBe('code_incorrect');
        expect(CodeOtp::firstOrFail()->tentatives)->toBe($essai);
    }

    // Même avec le bon code, le blocage reste
    expect(motifDeLEchec(fn () => $connecter->executer(TEL, $bon)))->toBe('code_bloque');
});

test('un code n\'est valable que pour le numéro qui l\'a demandé', function () {
    $sms = smsFictif();
    clientExistant();
    Utilisateur::factory()->client()->create(['telephone' => '+2250700000002']);
    demanderConnexion();

    expect(motifDeLEchec(fn () => app(ConnecterParOtpAction::class)->executer('+2250700000002', $sms->dernierCode())))
        ->toBe('code_expire');
});

test('la connexion demande un compte existant et n\'envoie pas de SMS sinon', function () {
    $sms = smsFictif();

    expect(motifDeLEchec(fn () => demanderConnexion('0700000009')))->toBe('compte_introuvable');
    expect($sms->messages)->toBeEmpty();
});

test('un compte désactivé ne peut pas demander de code', function () {
    $sms = smsFictif();
    clientExistant(['est_actif' => false]);

    expect(motifDeLEchec(fn () => demanderConnexion()))->toBe('compte_desactive');
    expect($sms->messages)->toBeEmpty();
});

test('un compte désactivé entre la demande et la saisie du code est refusé, et le code reste intact', function () {
    $sms = smsFictif();
    $client = clientExistant();
    demanderConnexion();
    $client->update(['est_actif' => false]);

    expect(motifDeLEchec(fn () => app(ConnecterParOtpAction::class)->executer(TEL, $sms->dernierCode())))
        ->toBe('compte_desactive');

    $otp = CodeOtp::firstOrFail();
    expect($otp->utilise_le)->toBeNull();
    expect($otp->tentatives)->toBe(0);
});

test('un compte sans rôle client ni livreur n\'utilise pas l\'application', function () {
    $sms = smsFictif();
    Utilisateur::factory()->admin()->create(['telephone' => TEL]);

    expect(motifDeLEchec(fn () => demanderConnexion()))->toBe('espace_non_autorise');
    expect($sms->messages)->toBeEmpty();
});

test('un nouveau client est créé avec le bon code', function () {
    $sms = smsFictif();
    app(DemanderCodeOtpAction::class)->executer('07 00 00 00 01', ObjectifOtp::Inscription);

    $client = app(InscrireClientAction::class)->executer('  Aya   Kouassi ', '07 00 00 00 01', $sms->dernierCode());

    expect($client->nom)->toBe('Aya Kouassi');
    expect($client->telephone)->toBe(TEL);
    expect($client->est_actif)->toBeTrue();
    expect($client->telephone_verifie_le)->not->toBeNull();
    expect($client->hasRole('client'))->toBeTrue();
});

test('l\'inscription refuse un numéro déjà utilisé, même celui d\'un compte supprimé', function () {
    smsFictif();
    $inscrire = fn () => app(DemanderCodeOtpAction::class)->executer(TEL, ObjectifOtp::Inscription);

    $client = clientExistant();
    expect(motifDeLEchec($inscrire))->toBe('compte_existant');

    $client->delete();
    expect(motifDeLEchec($inscrire))->toBe('compte_existant');
});

test('un nom invalide est refusé sans consommer le code', function () {
    $sms = smsFictif();
    app(DemanderCodeOtpAction::class)->executer(TEL, ObjectifOtp::Inscription);
    $code = $sms->dernierCode();
    $inscrire = app(InscrireClientAction::class);

    expect(motifDeLEchec(fn () => $inscrire->executer('A', TEL, $code)))->toBe('nom_invalide');

    $otp = CodeOtp::firstOrFail();
    expect($otp->utilise_le)->toBeNull();
    expect($otp->tentatives)->toBe(0);

    expect($inscrire->executer('Aya Kouassi', TEL, $code)->nom)->toBe('Aya Kouassi');
});

test('les codes de plus d\'un jour sont purgés', function () {
    $ancien = now()->subDays(2);

    CodeOtp::query()->insert([
        ['telephone' => TEL, 'code_hash' => str_repeat('a', 64), 'tentatives' => 0, 'expire_le' => $ancien, 'created_at' => $ancien, 'updated_at' => $ancien],
        ['telephone' => TEL, 'code_hash' => str_repeat('b', 64), 'tentatives' => 0, 'expire_le' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect((new CodeOtp())->pruneAll())->toBe(1);
    expect(CodeOtp::count())->toBe(1);
});

test('le mode journal est refusé hors développement', function () {
    $this->app->detectEnvironment(fn () => 'production');

    expect(fn () => (new EnvoyeurSmsJournal())->envoyer(TEL, 'test'))->toThrow(RuntimeException::class);
});
