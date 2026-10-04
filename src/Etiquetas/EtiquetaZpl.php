<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Utilitarios\Arquivo;
use Stringable;

/**
 * Resultado da geração: texto ZPL (UTF-8 sem BOM, de `^XA` a `^XZ` + quebra de linha) e metadados.
 *
 * O envio à impressora não faz parte do componente: use `(string) $etiqueta` ou `salvar()`.
 */
final readonly class EtiquetaZpl implements Stringable
{
    /** Número de blocos `^XA … ^XZ`. */
    public int $quantidade;

    public function __construct(
        public string $zpl,
        public Resolucao $resolucao,
    ) {
        $this->quantidade = self::contarBlocos($zpl);
    }

    public static function contarBlocos(string $zpl): int
    {
        return (int) preg_match_all('/\^XA/', $zpl);
    }

    /** Grava o ZPL em arquivo (conveniência). */
    public function salvar(string $caminho): void
    {
        Arquivo::gravar($caminho, $this->zpl, 'o ZPL');
    }

    public function __toString(): string
    {
        return $this->zpl;
    }
}
