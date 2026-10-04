<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use Throwable;

/**
 * Interface comum a todas as exceções do componente.
 *
 * Use `catch (CorreiosException $e)` para tratar qualquer erro vindo do componente.
 */
interface CorreiosException extends Throwable {}
