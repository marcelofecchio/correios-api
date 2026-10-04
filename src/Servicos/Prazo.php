<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\ConsultaDataPrevista;
use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\PrazoMapeador;
use MarceloFecchio\Correios\Respostas\DataPrevista;
use MarceloFecchio\Correios\Respostas\ItemLote;
use MarceloFecchio\Correios\Respostas\ResultadoLote;
use MarceloFecchio\Correios\Respostas\ResultadoPrazo;
use MarceloFecchio\Correios\Token;

/** API Prazo. */
final class Prazo extends Servico
{
    /** `GET /prazo/v1/nacional/{coProduto}`. */
    public function calcular(Token|string $token, ConsultaPrazo $consulta): ResultadoPrazo
    {
        $codigo = $this->configuracao->resolverServico($consulta->servico);
        $resposta = $this->chamar('GET', '/prazo/v1/nacional/' . self::segmento($codigo), 'calcular prazo', $token, PrazoMapeador::query($consulta));

        return PrazoMapeador::resultado($this->leitor($resposta));
    }

    /**
     * `POST /prazo/v1/nacional`. Itens correlacionados pelo `nuRequisicao` (numerado a partir de 1 quando
     * não informado). HTTP 206 também é aceito, com o resultado item a item.
     *
     * @param array<ConsultaPrazo> $consultas
     *
     * @return ResultadoLote<ResultadoPrazo>
     */
    public function calcularLote(Token|string $token, array $consultas, ?string $idLote = null): ResultadoLote
    {
        if ($consultas === []) {
            throw ErroValidacao::campo('parametrosPrazo', 'informe ao menos uma consulta.');
        }

        $idLote ??= 'lote-' . bin2hex(random_bytes(6));
        $parametros = [];
        $numeros = [];
        foreach (array_values($consultas) as $indice => $consulta) {
            $numero = $consulta->nuRequisicao ?? (string) ($indice + 1);
            $numeros[] = $numero;
            $parametros[] = PrazoMapeador::itemLote($consulta, $this->configuracao->resolverServico($consulta->servico), $numero);
        }
        if (count(array_unique($numeros)) !== count($numeros)) {
            throw ErroValidacao::campo('nuRequisicao', 'os números de requisição do lote devem ser únicos.');
        }

        $resposta = $this->chamar('POST', '/prazo/v1/nacional', 'calcular prazos em lote', $token, json: [
            'idLote' => $idLote,
            'parametrosPrazo' => $parametros,
        ], sucesso: [200, 206]);

        $itens = [];
        foreach ($this->leitor($resposta)->itensRaiz() as $dados) {
            $resultado = PrazoMapeador::resultado($dados);
            $itens[] = new ItemLote($resultado->nuRequisicao, $resultado);
        }

        return new ResultadoLote($idLote, $resposta->status, $itens, $resposta->jsonArray());
    }

    /** `GET /prazo/v1/data-prevista`. */
    public function dataPrevista(Token|string $token, ConsultaDataPrevista $consulta): DataPrevista
    {
        $codigo = $consulta->servico !== null ? $this->configuracao->resolverServico($consulta->servico) : null;
        $resposta = $this->chamar('GET', '/prazo/v1/data-prevista', 'calcular data prevista', $token, PrazoMapeador::queryDataPrevista($consulta, $codigo));

        return PrazoMapeador::dataPrevista($this->leitor($resposta));
    }
}
