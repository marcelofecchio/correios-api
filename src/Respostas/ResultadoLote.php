<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resultado de um lote de Preço ou Prazo. HTTP 206 indica que parte dos itens falhou: confira item a item.
 *
 * @template T of ResultadoPreco|ResultadoPrazo
 */
final readonly class ResultadoLote extends Resposta
{
    /**
     * @param list<ItemLote<T>> $itens
     * @param array<mixed>      $bruto
     */
    public function __construct(
        public string $idLote,
        public int $statusHttp,
        public array $itens,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /** Verdadeiro com HTTP 206 (lote com resultados diferentes). */
    public function parcial(): bool
    {
        return $this->statusHttp === 206;
    }

    public function todosComSucesso(): bool
    {
        foreach ($this->itens as $item) {
            if (!$item->sucesso()) {
                return false;
            }
        }

        return true;
    }

    /** @return ItemLote<T>|null */
    public function porRequisicao(string $nuRequisicao): ?ItemLote
    {
        foreach ($this->itens as $item) {
            if ($item->nuRequisicao === $nuRequisicao) {
                return $item;
            }
        }

        return null;
    }

    /** @return list<ItemLote<T>> */
    public function comErro(): array
    {
        return array_values(array_filter($this->itens, static fn(ItemLote $item): bool => !$item->sucesso()));
    }
}
