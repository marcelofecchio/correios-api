<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

use MarceloFecchio\Correios\Rastreamento\CatalogoEventos;

/**
 * Situação de um objeto a partir do evento de rastreamento (`codigo` + `tipo`).
 *
 * A tabela padrão é o catálogo oficial de eventos (`CatalogoEventos`, 376 eventos), com os critérios em
 * `doc/referencia/correios-eventos-rastreio.md`. O tipo muda o significado do código (`BDE-1` é entrega, `BDE-20` é
 * "carteiro não atendido"), por isso o catálogo exige os dois.
 *
 * A aplicação pode trocar ou completar a tabela, e as entradas dela têm prioridade. Chaves aceitas: `'BDE-01'`
 * (código e tipo; `'BDE-1'` também vale) ou `'BDE'` (qualquer tipo).
 *
 * ```php
 * $mapa = ['BDE-20' => SituacaoRastreamento::EmTransito];   // ex.: tratar "carteiro não atendido" como andamento
 * $situacao = $rastreamento->situacao($mapa);
 * ```
 */
enum SituacaoRastreamento: string
{
    /** Etiqueta emitida (`FC-82`) ou coleta ainda não feita: o objeto ainda não está com os Correios. */
    case AguardandoPostagem = 'aguardando_postagem';
    case Postado = 'postado';
    case EmTransito = 'em_transito';
    case SaiuParaEntrega = 'saiu_para_entrega';
    case AguardandoRetirada = 'aguardando_retirada';
    case Entregue = 'entregue';

    /** Voltando ao remetente. Ao chegar a ele, `Devolvido`. */
    case EmDevolucao = 'em_devolucao';
    case Devolvido = 'devolvido';

    /** Entrega sem sucesso, retenção, fiscalização, extravio, pagamento pendente: pede atenção. */
    case Problema = 'problema';
    case Cancelado = 'cancelado';

    /** Evento fora do catálogo, sem descrição ou `EST`: a aplicação decide como exibir (a `descricao` continua disponível). */
    case Desconhecida = 'desconhecida';

    /**
     * @param array<string, self> $mapa eventos da aplicação (`'CODIGO-TIPO'` ou `'CODIGO'`), com prioridade sobre o catálogo
     */
    public static function doEvento(?string $codigo, ?string $tipo, array $mapa = []): self
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return self::Desconhecida;
        }

        $mapaNormalizado = [];
        foreach ($mapa as $chave => $situacao) {
            $mapaNormalizado[CatalogoEventos::chave((string) $chave)] = $situacao;
        }

        return $mapaNormalizado[CatalogoEventos::chave($codigo, $tipo)]
            ?? $mapaNormalizado[$codigo]
            ?? CatalogoEventos::situacao($codigo, $tipo)
            ?? self::Desconhecida;
    }

    /** Situação final: o objeto não deve mais mudar (entregue, devolvido ou cancelado). */
    public function finalizada(): bool
    {
        return in_array($this, [self::Entregue, self::Devolvido, self::Cancelado], true);
    }

    /** O objeto já está com os Correios (houve postagem ou coleta). */
    public function emPosseDosCorreios(): bool
    {
        return !in_array($this, [self::AguardandoPostagem, self::Cancelado, self::Desconhecida], true);
    }
}
