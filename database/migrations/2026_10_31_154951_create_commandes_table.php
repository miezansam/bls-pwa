<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();                 // ex : BLS-261101-0042
            $table->foreignId('client_id')->constrained('utilisateurs')->restrictOnDelete();
            $table->foreignId('livreur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();

            $table->string('type', 20);                                // TypeCommande
            $table->string('statut', 30)->default('en_attente');       // StatutCommande

            // Retrait (copie figée de l'adresse au moment de la commande)
            $table->foreignId('retrait_adresse_id')->nullable()->constrained('adresses')->nullOnDelete();
            $table->foreignId('retrait_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->decimal('retrait_latitude', 10, 7);
            $table->decimal('retrait_longitude', 10, 7);
            $table->string('retrait_repere');
            $table->string('retrait_contact_nom')->nullable();
            $table->string('retrait_contact_telephone', 20);

            // Livraison (copie figée)
            $table->foreignId('livraison_adresse_id')->nullable()->constrained('adresses')->nullOnDelete();
            $table->foreignId('livraison_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->decimal('livraison_latitude', 10, 7);
            $table->decimal('livraison_longitude', 10, 7);
            $table->string('livraison_repere');
            $table->string('livraison_contact_nom')->nullable();
            $table->string('livraison_contact_telephone', 20);

            $table->text('description');
            $table->string('photo_chemin')->nullable();
            $table->text('note_livreur')->nullable();

            // Estimation et prix (FCFA)
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('duree_estimee_s')->nullable();
            $table->unsignedInteger('frais_livraison');
            $table->unsignedInteger('montant_articles')->default(0);   // repas avancé par le livreur
            $table->unsignedInteger('montant_total');                  // frais_livraison + montant_articles

            // Paiement
            $table->string('mode_paiement', 20);                       // ModePaiement
            $table->string('statut_paiement', 20)->default('en_attente'); // StatutPaiement

            // Code de livraison à 4 chiffres
            $table->string('code_livraison', 4);
            $table->unsignedSmallInteger('code_livraison_tentatives')->default(0);

            // Jalons
            $table->timestampTz('attribuee_le')->nullable();
            $table->timestampTz('acceptee_le')->nullable();
            $table->timestampTz('recuperee_le')->nullable();
            $table->timestampTz('livree_le')->nullable();
            $table->timestampTz('annulee_le')->nullable();
            $table->foreignId('annulee_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->string('motif_annulation')->nullable();

            $table->timestampsTz();

            $table->index('statut');
            $table->index(['client_id', 'created_at']);
            $table->index(['livreur_id', 'statut']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
