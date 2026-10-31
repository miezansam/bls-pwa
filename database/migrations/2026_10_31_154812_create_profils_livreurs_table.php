<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('profils_livreurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->unique()->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('statut', 20)->default('hors_ligne');       // StatutLivreur
            $table->string('type_vehicule', 20)->default('moto');      // TypeVehicule
            $table->string('plaque', 20)->nullable();
            $table->boolean('est_valide')->default(false);             // validé par l'admin
            $table->decimal('derniere_latitude', 10, 7)->nullable();
            $table->decimal('derniere_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('derniere_precision_m')->nullable();
            $table->timestampTz('derniere_position_le')->nullable();
            $table->integer('solde_caisse')->default(0);               // espèces détenues, à reverser (FCFA)
            $table->timestampsTz();

            $table->index(['statut', 'est_valide']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_livreurs');
    }
};
