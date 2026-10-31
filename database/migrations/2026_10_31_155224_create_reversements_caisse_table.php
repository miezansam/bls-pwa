<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reversements_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livreur_id')->constrained('utilisateurs')->restrictOnDelete();
            $table->foreignId('recu_par')->constrained('utilisateurs')->restrictOnDelete(); // admin
            $table->unsignedInteger('montant');                        // FCFA reversés
            $table->string('note')->nullable();
            $table->timestampTz('reverse_le')->useCurrent();
            $table->timestampsTz();

            $table->index(['livreur_id', 'reverse_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversements_caisse');
    }
};
