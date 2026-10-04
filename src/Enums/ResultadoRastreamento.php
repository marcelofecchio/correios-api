<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Parâmetro `resultado` da API Rastro. */
enum ResultadoRastreamento: string
{
    case Todos = 'T';
    case Primeiro = 'P';
    case Ultimo = 'U';
}
