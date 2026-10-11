<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/**
 * Situação de um objeto a partir do evento de rastreamento (`codigo` + `tipo`).
 *
 * A tabela oficial de eventos **não faz parte da especificação** da API Rastro. A tabela padrão traz só os eventos
 * confirmados no exemplo do manual de integração V2.4 (`PO-01`, `RO-01`, `OEC-03`, `BDE-01`). O significado de um
 * código muda com o `tipo` (ex.: `BDE` com outro tipo não é entrega), por isso a tabela padrão exige os dois.
 *
 * A aplicação completa a tabela com os eventos que já viu, e as entradas dela têm prioridade. Chaves aceitas:
 * `'BDE-01'` (código e tipo; `'BDE-1'` também vale) ou `'BDE'` (qualquer tipo).
 *
 * ```php
 * $mapa = ['LDI-01' => SituacaoRastreamento::AguardandoRetirada, 'BDE-20' => SituacaoRastreamento::Problema];
 * $situacao = $rastreamento->situacao($mapa);
 * ```
 */
enum SituacaoRastreamento: string
{
    case Postado = 'postado';
    case EmTransito = 'em_transito';
    case SaiuParaEntrega = 'saiu_para_entrega';
    case Entregue = 'entregue';
    case AguardandoRetirada = 'aguardando_retirada';
    case Devolvido = 'devolvido';
    case Problema = 'problema';
    case Cancelado = 'cancelado';

    /** Evento fora da tabela: a aplicação decide como exibir (a `descricao` do evento continua disponível). */
    case Desconhecida = 'desconhecida';

    /** Eventos confirmados na documentação (manual de integração V2.4, exemplo da API Rastro). */
    private const array TABELA_PADRAO = [
        'PO-1' => self::Postado,
        'RO-1' => self::EmTransito,
        'OEC-3' => self::SaiuParaEntrega,
        'BDE-1' => self::Entregue,
    ];

    /**
     * @param array<string, self> $mapa eventos da aplicação (`'CODIGO-TIPO'` ou `'CODIGO'`), com prioridade sobre a tabela padrão
     */
    public static function doEvento(?string $codigo, ?string $tipo, array $mapa = []): self
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return self::Desconhecida;
        }

        $mapaNormalizado = [];
        foreach ($mapa as $chave => $situacao) {
            $mapaNormalizado[self::chave(...explode('-', (string) $chave, 2))] = $situacao;
        }

        $completa = self::chave($codigo, $tipo);

        return $mapaNormalizado[$completa] ?? $mapaNormalizado[$codigo] ?? self::TABELA_PADRAO[$completa] ?? self::Desconhecida;
    }

    /** Situação final: o objeto não deve mais mudar (entregue, devolvido ou cancelado). */
    public function finalizada(): bool
    {
        return in_array($this, [self::Entregue, self::Devolvido, self::Cancelado], true);
    }

    /** `BDE-01` e `BDE-1` geram a mesma chave; sem tipo, só o código. */
    private static function chave(string $codigo, ?string $tipo = null): string
    {
        $codigo = strtoupper(trim($codigo));
        $tipo = trim((string) $tipo);
        if ($tipo === '') {
            return $codigo;
        }

        return $codigo . '-' . (ctype_digit($tipo) ? (string) (int) $tipo : strtoupper($tipo));
    }
}
