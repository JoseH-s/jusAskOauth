<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use BelongsToTenant;

    protected $table = 'posts';

    protected $fillable = [
        'site_id',
        'empresa_id',
        'tenant',
        'titulo',
        'slug',
        'conteudo',
        'publicado',
        'publicado_em',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
            'publicado_em' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
