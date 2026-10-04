<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;
use MarceloFecchio\Correios\Utilitarios\Documento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/** Emitente ou destinatário da NF-e, com os dados impressos no DANFE simplificado. */
final readonly class ParticipanteDanfe
{
    public string $nome;
    public ?string $documento;
    public string $uf;
    public ?string $inscricaoEstadual;
    public ?string $cep;

    /**
     * @param ?string $documento CPF ou CNPJ (com ou sem máscara); nulo para destinatário estrangeiro
     */
    public function __construct(
        string $nome,
        ?string $documento,
        string $uf,
        ?string $inscricaoEstadual = null,
        public ?string $logradouro = null,
        public ?string $numero = null,
        public ?string $complemento = null,
        public ?string $bairro = null,
        public ?string $cidade = null,
        ?string $cep = null,
    ) {
        $this->nome = Texto::limpar($nome);
        if ($this->nome === '') {
            throw ErroValidacao::campo('danfe.nome', 'o nome do emitente/destinatário é obrigatório.');
        }

        $this->documento = $documento !== null && trim($documento) !== '' ? Documento::normalizar($documento, 'danfe.documento') : null;

        $uf = strtoupper(trim($uf));
        if (preg_match('/^[A-Z]{2}$/', $uf) !== 1) {
            throw ErroValidacao::campo('danfe.uf', sprintf('UF "%s" inválida.', $uf));
        }
        $this->uf = $uf;

        $ie = Texto::limpar((string) $inscricaoEstadual);
        $this->inscricaoEstadual = $ie !== '' ? $ie : null;

        $this->cep = $cep !== null && trim($cep) !== '' ? Cep::normalizar($cep, 'danfe.cep') : null;
    }

    /** `Logradouro, número, complemento` (só as partes preenchidas). */
    public function enderecoLinha1(): string
    {
        return Texto::juntar(', ', $this->logradouro, $this->numero, $this->complemento);
    }

    /** `Cidade/UF`. */
    public function cidadeUf(): string
    {
        return $this->cidade !== null && trim($this->cidade) !== '' ? trim($this->cidade) . '/' . $this->uf : $this->uf;
    }

    public function cepFormatado(): ?string
    {
        return $this->cep !== null ? Cep::formatar($this->cep) : null;
    }
}
