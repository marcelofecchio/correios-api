<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Enums\SituacaoRotulo;

/**
 * Resultado de `rotulo()->obter()`: o PDF, ou o que fazer na próxima tentativa.
 *
 * - `Pronto`: `arquivo` traz o PDF;
 * - `EmGeracao` (PPN-291): guarde `recibo` e passe-o na próxima tentativa;
 * - `Pendente` (PPN-288): descarte o recibo (`recibo` é null) e tente de novo sem ele.
 */
final readonly class TentativaRotulo
{
    private function __construct(
        public SituacaoRotulo $situacao,
        public ?ArquivoRotulo $arquivo = null,
        public ?ReciboRotulo $recibo = null,
    ) {}

    public static function comArquivo(ArquivoRotulo $arquivo, ReciboRotulo $recibo): self
    {
        return new self(SituacaoRotulo::Pronto, $arquivo, $recibo);
    }

    public static function emGeracao(ReciboRotulo $recibo): self
    {
        return new self(SituacaoRotulo::EmGeracao, recibo: $recibo);
    }

    public static function pendente(): self
    {
        return new self(SituacaoRotulo::Pendente);
    }

    public function pronto(): bool
    {
        return $this->situacao === SituacaoRotulo::Pronto;
    }

    /** Recibo para a próxima tentativa: o mesmo no PPN-291, null no PPN-288 (peça outro). */
    public function reciboParaProximaTentativa(): ?ReciboRotulo
    {
        return $this->situacao === SituacaoRotulo::EmGeracao ? $this->recibo : null;
    }
}
