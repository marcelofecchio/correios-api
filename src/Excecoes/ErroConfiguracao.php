<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

use RuntimeException;

/** Configuração ausente ou inválida (credenciais, ambiente, serviços, templates, arquivos). */
class ErroConfiguracao extends RuntimeException implements CorreiosException {}
