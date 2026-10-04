<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EnvioCorreiosService;
use Illuminate\Http\JsonResponse;
use MarceloFecchio\Correios\Excecoes\CorreiosException;
use MarceloFecchio\Correios\Laravel\Facades\Correios;

/** Exemplo de controller usando a Facade opcional. */
final class RastreamentoController
{
    public function __construct(private readonly EnvioCorreiosService $envio) {}

    public function show(string $codigo): JsonResponse
    {
        try {
            $rastreamento = Correios::rastro()->rastrear($this->envio->token(), $codigo);
        } catch (CorreiosException $e) {
            return new JsonResponse(['erro' => $e->getMessage()], 422);
        }

        return new JsonResponse([
            'codigo' => $rastreamento->codigoObjeto,
            'eventos' => array_map(static fn($evento): array => [
                'data' => $evento->dataHora?->format(DATE_ATOM),
                'descricao' => $evento->descricao,
                'local' => trim(($evento->cidade ?? '') . '/' . ($evento->uf ?? ''), '/'),
            ], $rastreamento->eventos),
        ]);
    }
}
