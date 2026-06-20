<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aplica isolamento multi-tenant: filtra registros pela empresa ativa
 * e preenche empresa_id + tenant automaticamente na criacao.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app(TenantManager::class);

            if ($tenant->check()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.empresa_id',
                    $tenant->id()
                );
            }
        });

        static::creating(function (Model $model) {
            $tenant = app(TenantManager::class);

            if ($tenant->check()) {
                if (empty($model->empresa_id)) {
                    $model->empresa_id = $tenant->id();
                }
                if (empty($model->tenant)) {
                    $model->tenant = $tenant->tenant();
                }
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
