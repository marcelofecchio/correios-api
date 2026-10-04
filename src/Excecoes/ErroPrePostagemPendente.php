<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

/**
 * PPN-288: o rótulo foi pedido enquanto a pré-postagem estava PENDENTE (status 7).
 *
 * O recibo usado fica inválido para sempre. Aguarde o status 2 (PREPOSTADO) e solicite o rótulo de novo.
 */
class ErroPrePostagemPendente extends ErroApi {}
