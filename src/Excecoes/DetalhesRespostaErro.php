<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use Throwable;

/**
 * Dados da resposta de erro da API, comuns a `ErroApi`, `ErroAutenticacao` e `ErroLimiteRequisicoes`.
 *
 * @phpstan-require-extends \Exception
 */
trait DetalhesRespostaErro
{
    /**
     * @param list<string>              $mensagens     mensagens de `msgs` ou `mensagem`
     * @param array<mixed>|string       $respostaBruta JSON decodificado ou o texto bruto (ex.: página HTML)
     */
    public function __construct(
        string $mensagem,
        public readonly int $statusHttp,
        public readonly array $mensagens = [],
        public readonly ?string $codigoErro = null,
        public readonly ?string $causa = null,
        public readonly array|string $respostaBruta = [],
        ?Throwable $anterior = null,
    ) {
        parent::__construct($mensagem, $statusHttp, $anterior);
    }
}
