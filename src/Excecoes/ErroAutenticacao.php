<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/** HTTP 401 ou 403: credenciais erradas, token inválido ou expirado, ou sem permissão para a API. */
class ErroAutenticacao extends RuntimeException implements CorreiosException
{
    use DetalhesRespostaErro;
}
