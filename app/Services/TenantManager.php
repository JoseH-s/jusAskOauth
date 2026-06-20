<?php

namespace App\Services;

use App\Models\Empresa;

/**
 * Guarda a empresa (tenant) ativa durante o request. Registrado como
 * singleton no AppServiceProvider e consultado pelo trait BelongsToTenant
 * para aplicar o isolamento multi-tenant.
 */
class TenantManager
{
    private ?Empresa $empresa = null;

    public function set(Empresa $empresa): void
    {
        $this->empresa = $empresa;
    }

    public function forget(): void
    {
        $this->empresa = null;
    }

    public function check(): bool
    {
        return $this->empresa !== null;
    }

    public function empresa(): ?Empresa
    {
        return $this->empresa;
    }

    public function id(): ?int
    {
        return $this->empresa?->id;
    }

    public function tenant(): ?string
    {
        return $this->empresa?->tenant;
    }
}
