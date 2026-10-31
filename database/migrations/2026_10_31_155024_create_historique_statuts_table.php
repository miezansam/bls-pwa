<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('historique_statuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('ancien_statut', 30)->nullable();
            $table->string('nouveau_statut', 30);
            $table->foreignId('auteur_id')->nullable()->constrained('utilisateurs')->nullOnDelete(); // null = système
            $table->string('note')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['commande_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_statuts');
    }
};
