<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('positions_livreurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livreur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('precision_m')->nullable();
            $table->unsignedSmallInteger('vitesse_kmh')->nullable();
            $table->unsignedSmallInteger('cap')->nullable();           // 0 à 359
            $table->timestampTz('enregistree_le');                     // heure du téléphone
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['livreur_id', 'enregistree_le']);
            $table->index(['commande_id', 'enregistree_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions_livreurs');
    }
};
