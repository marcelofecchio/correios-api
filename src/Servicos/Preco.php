<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use DateTimeImmutable;
use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\PrecoMapeador;
use MarceloFecchio\Correios\Respostas\ItemLote;
use MarceloFecchio\Correios\Respostas\ResultadoLote;
use MarceloFecchio\Correios\Respostas\ResultadoPreco;
use MarceloFecchio\Correios\Respostas\ServicoAdicionalDisponivel;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

/**
 * API Preço. O contrato e a DR vêm da configuração ou do objeto Token; a API exige a DR quando o contrato
 * é informado.
 *
 * Atenção: alguns serviços exigem adicionais na cotação (ex.: o SEDEX REVERSO exige o 016 — erro `ERP-049`).
 * Descubra os obrigatórios com `servicosAdicionais()` e não os replique automaticamente na pré-postagem.
 */
final class Preco extends Servico
{
    private const int LIMITE_LOTE = 5;

    /** `GET /preco/v1/nacional/{coProduto}`. */
    public function calcular(Token|string $token, ConsultaPreco $consulta): ResultadoPreco
    {
        $codigo = $this->configuracao->resolverServico($consulta->servico);
        [$contrato, $dr] = $this->contratoEDr($token);

        $resposta = $this->chamar(
            'GET',
            '/preco/v1/nacional/' . self::segmento($codigo),
            'calcular preço',
            $token,
            PrecoMapeador::parametros($consulta, $codigo, $contrato, $dr),
        );

        return PrecoMapeador::resultado($this->leitor($resposta));
    }

    /**
     * `POST /preco/v1/nacional` (até 5 consultas). Cada item é correlacionado pelo `nuRequisicao`
     * (numerado a partir de 1 quando não informado). HTTP 206 = parte dos itens falhou.
     *
     * @param array<ConsultaPreco> $consultas
     *
     * @return ResultadoLote<ResultadoPreco>
     */
    public function calcularLote(Token|string $token, array $consultas, ?string $idLote = null): ResultadoLote
    {
        if ($consultas === [] || count($consultas) > self::LIMITE_LOTE) {
            throw ErroValidacao::campo('parametrosProduto', sprintf('o lote de preços aceita de 1 a %d consultas.', self::LIMITE_LOTE));
        }

        [$contrato, $dr] = $this->contratoEDr($token);
        $idLote ??= self::novoIdLote();
        $parametros = [];
        $numeros = [];
        foreach (array_values($consultas) as $indice => $consulta) {
            $item = PrecoMapeador::parametros($consulta, $this->configuracao->resolverServico($consulta->servico), $contrato, $dr);
            $item['nuRequisicao'] = $consulta->nuRequisicao ?? (string) ($indice + 1);
            $numeros[] = (string) $item['nuRequisicao'];
            $parametros[] = $item;
        }
        if (count(array_unique($numeros)) !== count($numeros)) {
            throw ErroValidacao::campo('nuRequisicao', 'os números de requisição do lote devem ser únicos.');
        }

        $resposta = $this->chamar('POST', '/preco/v1/nacional', 'calcular preços em lote', $token, json: [
            'idLote' => $idLote,
            'parametrosProduto' => $parametros,
        ], sucesso: [200, 206]);

        $itens = [];
        foreach ($this->leitor($resposta)->itensRaiz() as $dados) {
            $resultado = PrecoMapeador::resultado($dados);
            $itens[] = new ItemLote($resultado->nuRequisicao, $resultado);
        }

        return new ResultadoLote($idLote, $resposta->status, $itens, $resposta->jsonArray());
    }

    /**
     * `GET /preco/v1/servicos-adicionais/{coProduto}`: adicionais aceitos pelo serviço, com a obrigatoriedade
     * (`obrigatorio()` quando `sgIndicador = O`).
     *
     * @return list<ServicoAdicionalDisponivel>
     */
    public function servicosAdicionais(Token|string $token, string $servico, ?int $valorDeclaradoCentavos = null, ?DateTimeImmutable $dataEvento = null): array
    {
        $codigo = $this->configuracao->resolverServico($servico);
        $resposta = $this->chamar('GET', '/preco/v1/servicos-adicionais/' . self::segmento($codigo), 'consultar serviços adicionais', $token, [
            'vlDeclarado' => $valorDeclaradoCentavos !== null ? Dinheiro::formatarApi($valorDeclaradoCentavos) : null,
            'dtEvento' => $dataEvento !== null ? Datas::formatar($dataEvento, 'Y-m-d') : null,
            'sgPais' => 'BR',
        ]);

        $adicionais = [];
        foreach ($this->leitor($resposta)->itensRaiz() as $dados) {
            $adicional = PrecoMapeador::servicoAdicional($dados);
            if ($adicional !== null) {
                $adicionais[] = $adicional;
            }
        }

        return $adicionais;
    }

    /** @return array{0: ?string, 1: ?int} */
    private function contratoEDr(Token|string $token): array
    {
        $contrato = $this->contrato($token);
        $dr = $this->diretoriaRegional($token);
        if ($contrato !== null && $dr === null) {
            throw new ErroConfiguracao('A API Preço exige a diretoria regional (nuDR) quando o contrato é informado: configure "diretoria_regional" ou passe o objeto Token.');
        }

        return [$contrato, $dr];
    }

    private static function novoIdLote(): string
    {
        return 'lote-' . bin2hex(random_bytes(6));
    }
}
