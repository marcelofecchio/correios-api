<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/**
 * Erro devolvido pela API (4xx/5xx), inclusive regras de negócio com HTTP 400 ou 422.
 *
 * `codigoErro` traz o código no início da mensagem, quando houver (ex.: `PPN-288`, `ERP-049`).
 */
class ErroApi extends RuntimeException implements CorreiosException
{
    use DetalhesRespostaErro;
}
