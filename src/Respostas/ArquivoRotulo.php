<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Utilitarios\Arquivo;

/**
 * PDF do rótulo oficial. Um recibo com várias pré-postagens devolve um único PDF (A4, etiquetas lado a lado).
 *
 * `respostaBruta()` não guarda o Base64 (que pode ter dezenas de KB): o campo `dados` é substituído pelo tamanho.
 */
final readonly class ArquivoRotulo extends Resposta
{
    private string $binario;

    /** @param array<mixed> $bruto */
    public function __construct(
        public string $nome,
        private string $base64,
        array $bruto = [],
    ) {
        $binario = base64_decode($base64, true);
        if ($binario === false || !str_starts_with($binario, '%PDF')) {
            throw new ErroApi(
                'O conteúdo do rótulo não é um PDF válido (o Base64 decodificado não começa com %PDF).',
                200,
                [],
                null,
                null,
                $bruto,
            );
        }
        $this->binario = $binario;

        if (isset($bruto['dados'])) {
            $bruto['dados'] = sprintf('[%d caracteres em Base64]', strlen($base64));
        }
        parent::__construct($bruto);
    }

    public function conteudoBase64(): string
    {
        return $this->base64;
    }

    public function conteudoBinario(): string
    {
        return $this->binario;
    }

    public function tamanhoBytes(): int
    {
        return strlen($this->binario);
    }

    /** Grava o PDF. Lança `ErroConfiguracao` se não conseguir. */
    public function salvar(string $caminho): void
    {
        Arquivo::gravar($caminho, $this->binario, 'o rótulo');
    }
}
