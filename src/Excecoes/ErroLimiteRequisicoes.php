<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/** HTTP 429: limite de requisições excedido (a API Token aceita 3 por segundo). */
class ErroLimiteRequisicoes extends RuntimeException implements CorreiosException
{
    use DetalhesRespostaErro;
}
