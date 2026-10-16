<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Enums\SituacaoProcessamento;
use MarceloFecchio\Correios\Excecoes\ErroContribuinteIcms;

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
     * A DC-e foi recusada porque o remetente é contribuinte do ICMS (Rejeição 812 nas mensagens de falha ou no
     * `motivoCancelamento` de alguma pré-postagem). Envie de novo com a chave da NF-e.
     */
    public function recusadaPorContribuinteIcms(): bool
    {
        if (ErroContribuinteIcms::reconheceAlguma($this->todasAsMensagens())) {
            return true;
        }
        foreach ($this->prePostagens as $prePostagem) {
            if ($prePostagem->recusadaPorContribuinteIcms()) {
                return true;
            }
        }

        return false;
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
