<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Enums\SituacaoProcessamento;

/**
 * Situação de uma pré-postagem assíncrona (`GET /prepostagem/v1/prepostagens/assincrona/{recibo}`).
 *
 * `PROCESSADO` não basta para pedir o rótulo: a pré-postagem passa alguns minutos no status 7 (PENDENTE)
 * antes do 2 (PREPOSTADO). Confira `prontaParaRotulo()`.
 */
final readonly class ProcessamentoAssincrono extends Resposta
{
    /**
     * @param list<string>             $mensagens
     * @param list<FalhaProcessamento> $falhas
     * @param list<PrePostagemCriada>  $prePostagens
     * @param array<mixed>             $bruto
     */
    public function __construct(
        public ?SituacaoProcessamento $situacao,
        public array $mensagens = [],
        public array $falhas = [],
        public array $prePostagens = [],
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    public function processado(): bool
    {
        return $this->situacao === SituacaoProcessamento::Processado;
    }

    /** Processado e com todas as pré-postagens no status 2 (PREPOSTADO). */
    public function prontaParaRotulo(): bool
    {
        if (!$this->processado() || $this->prePostagens === []) {
            return false;
        }
        foreach ($this->prePostagens as $prePostagem) {
            if (!$prePostagem->podeImprimirRotulo()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Todas as mensagens de falha (gerais e por item).
     *
     * @return list<string>
     */
    public function todasAsMensagens(): array
    {
        $mensagens = $this->mensagens;
        foreach ($this->falhas as $falha) {
            foreach ($falha->mensagens as $mensagem) {
                $mensagens[] = $falha->ordem !== null ? sprintf('[%s] %s', $falha->ordem, $mensagem) : $mensagem;
            }
        }

        return $mensagens;
    }
}
