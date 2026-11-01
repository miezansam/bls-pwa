<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            // Prix de livraison vers cette zone. NULL = zone non desservie.
            $table->unsignedInteger('frais_livraison')->nullable();
            // Les villages ne sont pas livrés la nuit.
            $table->boolean('accepte_livraison_nuit')->default(true);
        });

        Schema::create('options_tarifaires', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();                        // CodeOption
            $table->string('regle', 20)->default('minimum');             // RegleOption
            $table->unsignedInteger('montant');                          // FCFA
            $table->unsignedTinyInteger('heure_debut')->nullable();      // nuit : 0 à 23
            $table->unsignedTinyInteger('heure_fin')->nullable();        // nuit : 0 à 23
            $table->unsignedSmallInteger('nombre_minimum')->nullable();  // repas multiples
            $table->boolean('est_active')->default(true);
            $table->timestampsTz();
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->unsignedSmallInteger('nombre_repas')->default(1);
            $table->jsonb('detail_prix')->nullable();                    // pourquoi ce prix
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['frais_livraison', 'accepte_livraison_nuit']);
        });

        Schema::dropIfExists('options_tarifaires');

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('frais_livraison');
        });
    }
};
