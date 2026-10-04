<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `statusAtual` da pré-postagem. */
enum StatusPrePostagem: int
{
    case Preatendido = 1;
    case Prepostado = 2;
    case Postado = 3;
    case Expirado = 4;
    case Cancelado = 5;
    case Estornado = 6;
    case Pendente = 7;

    /** O rótulo só pode ser solicitado com o status 2 (PREPOSTADO). */
    public function podeImprimirRotulo(): bool
    {
        return $this === self::Prepostado;
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Preatendido => 'PREATENDIDO',
            self::Prepostado => 'PREPOSTADO',
            self::Postado => 'POSTADO',
            self::Expirado => 'EXPIRADO',
            self::Cancelado => 'CANCELADO',
            self::Estornado => 'ESTORNADO',
            self::Pendente => 'PENDENTE',
        };
    }
}
