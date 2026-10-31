<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('mode', 20);                                // ModeTarif
            $table->string('type_commande', 20)->nullable();           // null = tous types
            $table->foreignId('zone_retrait_id')->nullable()->constrained('zones')->cascadeOnDelete();
            $table->foreignId('zone_livraison_id')->nullable()->constrained('zones')->cascadeOnDelete();
            $table->unsignedInteger('frais_forfait')->default(0);      // mode forfait_zone
            $table->unsignedInteger('frais_base')->default(0);         // mode distance : prise en charge
            $table->unsignedInteger('frais_par_km')->default(0);       // mode distance
            $table->unsignedInteger('frais_minimum')->default(0);
            $table->boolean('est_actif')->default(true);
            $table->timestampsTz();

            $table->index(['mode', 'est_actif']);
            $table->index(['zone_retrait_id', 'zone_livraison_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifs');
    }
};
