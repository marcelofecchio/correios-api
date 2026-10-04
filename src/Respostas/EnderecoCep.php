<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Dto\Endereco;

/** Endereço devolvido pela API CEP (`GET /cep/v2/enderecos/{cep}`). */
final readonly class EnderecoCep extends Resposta
{
    /**
     * @param ?int         $tipoCep 1 localidade · 2 logradouro · 3 promocional · 4 caixa postal comunitária · 5 grande usuário · 6 unidade operacional
     * @param array<mixed> $bruto
     */
    public function __construct(
        public string $cep,
        public ?string $uf,
        public ?string $cidade,
        public ?string $logradouro = null,
        public ?string $bairro = null,
        public ?string $complemento = null,
        public ?int $tipoCep = null,
        public ?string $codigoIbge = null,
        public ?string $nome = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /**
     * Monta o `Endereco` da pré-postagem com o número e o complemento informados pelo usuário.
     *
     * CEPs de localidade (`tipoCep = 1`) podem vir sem logradouro e bairro: informe-os nos parâmetros.
     */
    public function paraEndereco(string $numero, ?string $complemento = null, ?string $logradouro = null, ?string $bairro = null): Endereco
    {
        return new Endereco(
            cep: $this->cep,
            logradouro: $logradouro ?? (string) $this->logradouro,
            numero: $numero,
            bairro: $bairro ?? (string) $this->bairro,
            cidade: (string) $this->cidade,
            uf: (string) $this->uf,
            complemento: $complemento,
        );
    }
}
