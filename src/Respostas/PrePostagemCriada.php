<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/** Pré-postagem criada (resposta da criação síncrona ou item da consulta assíncrona). */
final readonly class PrePostagemCriada extends Resposta
{
    /**
     * @param ?bool        $objetoPerigoso campo `ehObjetoDgr`: os Correios identificaram o objeto como perigoso
     *                                     (Dangerous Goods Regulations); null se a resposta não trouxer o campo
     * @param array<mixed> $bruto
     */
    public function __construct(
        public string $id,
        public ?CodigoRastreamento $codigoObjeto,
        public ?StatusPrePostagem $status,
        public ?DateTimeImmutable $prazoPostagem = null,
        public ?string $codigoServico = null,
        public ?string $descricaoServico = null,
        public ?string $motivoCancelamento = null,
        public ?bool $objetoPerigoso = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /** O rótulo só pode ser solicitado com o status 2 (PREPOSTADO). */
    public function podeImprimirRotulo(): bool
    {
        return $this->status?->podeImprimirRotulo() ?? false;
    }
}
