<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->restrictOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->restrictOnDelete(); // payeur
            $table->string('mode', 20);                                // ModePaiement
            $table->string('statut', 20)->default('en_attente');       // StatutPaiement
            $table->unsignedInteger('montant');                        // FCFA
            $table->string('devise', 3)->default('XOF');
            $table->string('telephone_payeur', 20)->nullable();
            $table->string('fournisseur', 30)->nullable();             // cinetpay, paystack...
            $table->string('reference_fournisseur')->nullable();
            $table->jsonb('donnees_fournisseur')->nullable();
            $table->string('motif_echec')->nullable();
            $table->timestampTz('paye_le')->nullable();
            $table->timestampsTz();

            $table->unique(['fournisseur', 'reference_fournisseur']);  // idempotence des webhooks
            $table->index(['commande_id', 'statut']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
