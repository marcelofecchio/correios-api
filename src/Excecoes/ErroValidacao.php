<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use InvalidArgumentException;

/** Dados de entrada inválidos, detectados antes de chamar a API. */
class ErroValidacao extends InvalidArgumentException implements CorreiosException
{
    public function __construct(string $mensagem, public readonly ?string $campo = null)
    {
        parent::__construct($mensagem);
    }

    /** Cria o erro com o nome do campo no início da mensagem. */
    public static function campo(string $campo, string $mensagem): self
    {
        return new self(sprintf('Campo "%s": %s', $campo, $mensagem), $campo);
    }
}
