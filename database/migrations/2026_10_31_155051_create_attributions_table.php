<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignId('livreur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('attribuee_par')->nullable()->constrained('utilisateurs')->nullOnDelete(); // null = automatique
            $table->string('statut', 20)->default('proposee');         // StatutAttribution
            $table->timestampTz('proposee_le')->useCurrent();
            $table->timestampTz('repondue_le')->nullable();
            $table->timestampTz('expire_le')->nullable();
            $table->timestampsTz();

            $table->index(['commande_id', 'statut']);
            $table->index(['livreur_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributions');
    }
};
