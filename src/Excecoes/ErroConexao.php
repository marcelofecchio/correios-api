<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/** Falha de rede ou tempo esgotado. A exceção original do cliente PSR-18 fica em `getPrevious()`. */
class ErroConexao extends RuntimeException implements CorreiosException {}
