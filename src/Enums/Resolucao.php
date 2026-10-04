<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Resolução da impressora térmica. */
enum Resolucao: int
{
    /** 8 pontos por milímetro (padrão). */
    case Dpi203 = 203;

    /** 12 pontos por milímetro. */
    case Dpi300 = 300;

    /** Fator de escala em relação aos templates, escritos em 203 dpi. */
    public function fator(): float
    {
        return match ($this) {
            self::Dpi203 => 1.0,
            self::Dpi300 => 1.5,
        };
    }

    public function pontosPorMilimetro(): int
    {
        return match ($this) {
            self::Dpi203 => 8,
            self::Dpi300 => 12,
        };
    }
}
