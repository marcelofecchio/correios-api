<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade opcional para `MarceloFecchio\Correios\Correios`.
 *
 * @method static \MarceloFecchio\Correios\Servicos\Token token()
 * @method static \MarceloFecchio\Correios\Servicos\Contrato contrato()
 * @method static \MarceloFecchio\Correios\Servicos\Cep cep()
 * @method static \MarceloFecchio\Correios\Servicos\Preco preco()
 * @method static \MarceloFecchio\Correios\Servicos\Prazo prazo()
 * @method static \MarceloFecchio\Correios\Servicos\PrePostagem prePostagem()
 * @method static \MarceloFecchio\Correios\Servicos\Rotulo rotulo()
 * @method static \MarceloFecchio\Correios\Servicos\Dce dce()
 * @method static \MarceloFecchio\Correios\Servicos\Rastro rastro()
 * @method static \MarceloFecchio\Correios\Etiquetas\GeradorEtiquetas etiquetas()
 *
 * @see \MarceloFecchio\Correios\Correios
 */
final class Correios extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MarceloFecchio\Correios\Correios::class;
    }
}
