<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone', 20)->unique();           // format E.164 : +2250700000000
            $table->timestampTz('telephone_verifie_le')->nullable();
            $table->string('email')->nullable()->unique();       // utile pour la connexion Filament (admin)
            $table->string('password')->nullable();              // clients et livreurs : OTP
            $table->string('avatar_chemin')->nullable();
            $table->string('langue', 5)->default('fr');
            $table->boolean('est_actif')->default(true);
            $table->timestampTz('derniere_connexion_le')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('est_actif');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();   // nom imposé par Laravel
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('utilisateurs');
    }
};
