<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'cnpj',
        'oab',
        'tenant',
        'is_pessoa_fisica',
    ];

    protected function casts(): array
    {
        return [
            'is_pessoa_fisica' => 'boolean',
        ];
    }

    public function membros(): HasMany
    {
        return $this->hasMany(Membro::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membros')
            ->withPivot(['papel', 'ativo', 'tenant'])
            ->withTimestamps();
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }

    public function site(): HasOne
    {
        return $this->hasOne(Site::class);
    }
}
