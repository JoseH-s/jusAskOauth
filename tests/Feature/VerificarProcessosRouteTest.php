<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerificarProcessosRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sem_token_retorna_401(): void
    {
        $this->getJson('/processos/verificar')->assertStatus(401);
    }

    public function test_com_token_correto_dispara_verificacao(): void
    {
        Http::fake();

        // PROCESSOS_VERIFICACAO_TOKEN é definido no phpunit.xml.
        $this->getJson('/processos/verificar?token=test-cron-token')
            ->assertOk()
            ->assertJson(['atualizados' => 0]);
    }
}
