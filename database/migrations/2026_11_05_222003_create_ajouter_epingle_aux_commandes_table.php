<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->boolean('retrait_epingle')->default(false);
            $table->boolean('livraison_epingle')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['retrait_epingle', 'livraison_epingle']);
        });
    }
};
