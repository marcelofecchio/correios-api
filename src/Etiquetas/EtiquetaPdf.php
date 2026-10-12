<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\ArquivoPdf;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Arquivo;

/** PDF gerado a partir de uma `EtiquetaZpl` por um `ConversorPdf`. */
final readonly class EtiquetaPdf implements ArquivoPdf
{
    /**
     * @param int       $quantidade etiquetas no PDF
     * @param Resolucao $resolucao  resolução do ZPL de origem
     */
    public function __construct(
        private string $conteudo,
        public int $quantidade,
        public Resolucao $resolucao,
    ) {
        if (!str_starts_with($conteudo, '%PDF')) {
            throw ErroValidacao::campo('conteudo', 'não é um PDF (não começa com %PDF).');
        }
    }

    public function conteudoBinario(): string
    {
        return $this->conteudo;
    }

    public function conteudoBase64(): string
    {
        return base64_encode($this->conteudo);
    }

    public function tamanhoBytes(): int
    {
        return strlen($this->conteudo);
    }

    public function salvar(string $caminho): void
    {
        Arquivo::gravar($caminho, $this->conteudo, 'o PDF da etiqueta');
    }
}
