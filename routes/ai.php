<?php

use App\Mcp\Servers\ProcessosServer;
use Laravel\Mcp\Facades\Mcp;

// Servidor local (stdio) — usado pelo Claude Code via `php artisan mcp:start jus-ask`.
Mcp::local('jus-ask', ProcessosServer::class);

// Servidor web (HTTP) — autenticado pelo token MCP por empresa (Authorization: Bearer).
Mcp::web('/mcp/jus-ask', ProcessosServer::class)
    ->middleware(['mcp.token']);
