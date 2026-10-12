<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios;

/**
 * PDF de etiqueta, venha do rótulo oficial dos Correios (`Respostas\ArquivoRotulo`) ou da conversão do ZPL do
 * componente (`Etiquetas\EtiquetaPdf`). A aplicação pode tipar com esta interface e trocar a origem sem mudar o resto.
 */
interface ArquivoPdf
{
    public function conteudoBinario(): string;

    public function conteudoBase64(): string;

    public function tamanhoBytes(): int;

    /** Grava o PDF. Lança `ErroConfiguracao` se não conseguir. */
    public function salvar(string $caminho): void;
}
