<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use BelongsToTenant;

    protected $table = 'sites';

    protected $fillable = [
        'empresa_id',
        'tenant',
        'titulo',
        'slug',
        'descricao',
        'publicado',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
