<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('centre_latitude', 10, 7)->nullable();
            $table->decimal('centre_longitude', 10, 7)->nullable();
            $table->jsonb('polygone')->nullable();               // GeoJSON optionnel
            $table->boolean('est_active')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
