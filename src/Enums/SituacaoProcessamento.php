<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `situacao` da consulta da pré-postagem assíncrona. */
enum SituacaoProcessamento: string
{
    case Processado = 'PROCESSADO';
    case Falha = 'FALHA';
    case NaoLocalizado = 'NAO_LOCALIZADO';
}
