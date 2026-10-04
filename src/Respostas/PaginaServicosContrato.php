<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Uma página da lista de serviços do cartão. `pagina` começa em 0. */
final readonly class PaginaServicosContrato extends Resposta
{
    /**
     * @param list<ServicoContrato> $servicos
     * @param array<mixed>          $bruto
     */
    public function __construct(
        public array $servicos,
        public int $pagina,
        public int $tamanho,
        public int $totalItens,
        public int $totalPaginas,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    public function temProxima(): bool
    {
        return $this->pagina < $this->totalPaginas - 1;
    }
}
