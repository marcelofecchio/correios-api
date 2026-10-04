<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Respostas\PrecoServicoAdicional;
use MarceloFecchio\Correios\Respostas\ResultadoPreco;
use MarceloFecchio\Correios\Respostas\ServicoAdicionalDisponivel;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

/**
 * API Preço. Respostas trazem valores com vírgula decimal em texto (`"19,92"`); requisições recebem
 * o valor declarado com ponto (`"250.00"`) e a data no formato `DD-MM-YYYY`.
 *
 * @internal
 */
final class PrecoMapeador
{
    private function __construct() {}

    /**
     * Parâmetros de uma consulta (query do GET ou item de `parametrosProduto` do lote).
     *
     * @return array<string, string|int|list<string>>
     */
    public static function parametros(ConsultaPreco $consulta, string $codigoServico, ?string $contrato, ?int $dr): array
    {
        $volume = $consulta->volume;
        $parametros = [
            'coProduto' => $codigoServico,
            'nuRequisicao' => $consulta->nuRequisicao,
            'nuContrato' => $contrato,
            'nuDR' => $contrato !== null ? $dr : null,
            'cepOrigem' => $consulta->cepOrigem,
            'cepDestino' => $consulta->cepDestino,
            'psObjeto' => (string) $volume->pesoGramas,
            'tpObjeto' => $volume->formato->value,
            'comprimento' => self::medida($volume->comprimento),
            'largura' => self::medida($volume->largura),
            'altura' => self::medida($volume->altura),
            'diametro' => self::medida($volume->diametro),
            'servicosAdicionais' => $consulta->servicosAdicionais !== [] ? $consulta->servicosAdicionais : null,
            'vlDeclarado' => $consulta->valorDeclaradoCentavos !== null ? Dinheiro::formatarApi($consulta->valorDeclaradoCentavos) : null,
            'dtEvento' => $consulta->dataEvento !== null ? Datas::formatar($consulta->dataEvento, 'd-m-Y') : null,
        ];

        return array_filter($parametros, static fn(mixed $valor): bool => $valor !== null);
    }

    public static function resultado(Leitor $dados): ResultadoPreco
    {
        $adicionais = [];
        foreach ($dados->lista('servicoAdicional') as $item) {
            $codigo = $item->texto('coServAdicional');
            if ($codigo !== null) {
                $adicionais[] = new PrecoServicoAdicional($codigo, $item->centavos('pcServicoAdicional'), $item->texto('tpServAdicional'));
            }
        }

        return new ResultadoPreco(
            codigoServico: $dados->texto('coProduto'),
            precoFinalCentavos: $dados->centavos('pcFinal'),
            precoProdutoCentavos: $dados->centavos('pcProduto'),
            precoServicosAdicionaisCentavos: $dados->centavos('pcTotalServicosAdicionais'),
            precoBaseCentavos: $dados->centavos('pcBase'),
            pesoCobradoGramas: $dados->inteiro('psCobrado'),
            cobradoPorPesoCubico: $dados->booleano('inPesoCubico'),
            adicionais: $adicionais,
            nuRequisicao: $dados->texto('nuRequisicao'),
            erro: $dados->texto('txErro'),
            bruto: $dados->bruto(),
        );
    }

    public static function servicoAdicional(Leitor $dados): ?ServicoAdicionalDisponivel
    {
        $codigo = $dados->texto('codigo');
        if ($codigo === null) {
            return null;
        }

        $requeridos = [];
        foreach ($dados->lista('requeridos') as $item) {
            if (($co = $item->texto('coRequerido')) !== null) {
                $requeridos[] = $co;
            }
        }
        $excludentes = [];
        foreach ($dados->lista('excludentes') as $item) {
            if (($co = $item->texto('coExcludente')) !== null) {
                $excludentes[] = $co;
            }
        }

        return new ServicoAdicionalDisponivel(
            codigo: $codigo,
            nome: $dados->texto('nome'),
            sigla: $dados->texto('sigla'),
            tipo: $dados->texto('tipo'),
            codigoIndicador: $dados->texto('coIndicador'),
            siglaIndicador: $dados->texto('sgIndicador'),
            nomeIndicador: $dados->texto('noIndicador'),
            precoCentavos: $dados->centavos('preco'),
            valorMinimoDeclaradoCentavos: $dados->centavos('vlMinDeclarado'),
            valorMaximoDeclaradoCentavos: $dados->centavos('vlMaxDeclarado'),
            mensagem: $dados->texto('mensagem'),
            requeridos: $requeridos,
            excludentes: $excludentes,
            inicioVigencia: $dados->data('dtIniVigencia'),
            fimVigencia: $dados->data('dtFimVigencia'),
            bruto: $dados->bruto(),
        );
    }

    private static function medida(?int $valor): ?string
    {
        return $valor !== null ? (string) $valor : null;
    }
}
