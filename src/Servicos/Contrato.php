<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\ContratoMapeador;
use MarceloFecchio\Correios\Respostas\PaginaServicosContrato;
use MarceloFecchio\Correios\Respostas\ServicoContrato;
use MarceloFecchio\Correios\Token;

/**
 * API Meu Contrato: serviços habilitados no cartão de postagem. CNPJ, contrato e cartão vêm da configuração
 * (o contrato também pode vir do objeto Token).
 */
final class Contrato extends Servico
{
    /** `GET /meucontrato/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos`. */
    public function listarServicos(Token|string $token, int $pagina = 0, int $tamanho = 50): PaginaServicosContrato
    {
        if ($pagina < 0 || $tamanho < 1) {
            throw ErroValidacao::campo('page', 'a página começa em 0 e o tamanho deve ser positivo.');
        }

        $resposta = $this->chamar('GET', $this->caminho($token), 'listar serviços do cartão', $token, [
            'page' => $pagina,
            'size' => $tamanho,
        ]);

        return ContratoMapeador::pagina($this->leitor($resposta), $pagina, $tamanho);
    }

    /**
     * Percorre todas as páginas e devolve todos os serviços do cartão.
     *
     * @return list<ServicoContrato>
     */
    public function listarTodosServicos(Token|string $token, int $tamanhoPagina = 50): array
    {
        $servicos = [];
        $pagina = 0;
        do {
            $resultado = $this->listarServicos($token, $pagina, $tamanhoPagina);
            array_push($servicos, ...$resultado->servicos);
            $pagina++;
        } while ($resultado->temProxima() && $resultado->servicos !== [] && $pagina < 1000);

        return $servicos;
    }

    /** `GET .../cartoes/{nuCartaoPostagem}/servicos/{coServico}` (apelido ou código). */
    public function consultarServico(Token|string $token, string $servico): ServicoContrato
    {
        $codigo = $this->configuracao->resolverServico($servico);
        $resposta = $this->chamar('GET', $this->caminho($token) . '/' . self::segmento($codigo), 'consultar serviço do cartão', $token);
        $dados = $this->leitor($resposta);

        $encontrado = ContratoMapeador::servico($dados);
        if ($encontrado === null) {
            foreach ($dados->lista('itens') as $item) {
                $encontrado = ContratoMapeador::servico($item);
                if ($encontrado !== null) {
                    break;
                }
            }
        }

        return $encontrado ?? throw new ErroApi(
            sprintf('O serviço %s não foi encontrado na resposta da API Meu Contrato.', $codigo),
            $resposta->status,
            [],
            null,
            null,
            $dados->bruto(),
        );
    }

    private function caminho(Token|string $token): string
    {
        $contrato = $this->contrato($token) ?? throw new ErroConfiguracao(
            'Informe o número do contrato na configuração ou passe o objeto Token (que traz o contrato do cartão).',
        );

        return sprintf(
            '/meucontrato/v1/empresas/%s/contratos/%s/cartoes/%s/servicos',
            self::segmento($this->configuracao->cnpj),
            self::segmento($contrato),
            self::segmento($this->configuracao->cartaoPostagem),
        );
    }
}
