<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('codes_otp', function (Blueprint $table) {
            $table->id();
            $table->string('telephone', 20);
            $table->string('code_hash');
            $table->unsignedSmallInteger('tentatives')->default(0);
            $table->string('adresse_ip', 45)->nullable();
            $table->timestampTz('expire_le');
            $table->timestampTz('utilise_le')->nullable();
            $table->timestampsTz();

            $table->index(['telephone', 'created_at']);
            $table->index('expire_le');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_otp');
    }
};
