<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class CodeOtp extends Model
{
    use MassPrunable;

    protected $table = 'codes_otp';

    protected $fillable = ['telephone', 'code_hash', 'tentatives', 'adresse_ip', 'expire_le', 'utilise_le'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'tentatives' => 'integer',
            'expire_le' => 'datetime',
            'utilise_le' => 'datetime',
        ];
    }

    /** Les codes de plus d'un jour sont supprimés par « model:prune ». */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }
}
