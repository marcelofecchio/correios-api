<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/** Template ZPL ausente, inválido ou com marcador sem valor. */
class ErroTemplate extends RuntimeException implements CorreiosException {}
