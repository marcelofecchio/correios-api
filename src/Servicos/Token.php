<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Http\ClienteHttp;
use MarceloFecchio\Correios\Http\Requisicao;
use MarceloFecchio\Correios\Mapeadores\Leitor;
use MarceloFecchio\Correios\Mapeadores\TokenMapeador;
use MarceloFecchio\Correios\Token as TokenAutorizacao;

/**
 * API Token: gera o token por cartão de postagem. O componente **não guarda** o token.
 *
 * A API responde HTTP 201, aceita no máximo 3 requisições por segundo e devolve o **mesmo** token até os
 * 30 minutos finais da validade. Gere o token uma vez, grave-o na sessão/cache e reutilize-o.
 */
final class Token
{
    public function __construct(
        private readonly Configuracao $configuracao,
        private readonly ClienteHttp $http,
    ) {}

    /** `POST /token/v1/autentica/cartaopostagem`. */
    public function gerar(): TokenAutorizacao
    {
        $config = $this->configuracao;
        $dicaUsuario = $config->ambiente === Ambiente::Homologacao
            ? 'Em homologação o usuário deve ser o CNPJ com 14 dígitos (sem pontuação) e o código de acesso deve ser o gerado em ' . Ambiente::Homologacao->urlPortal() . '.'
            : 'Confira o login do Meu Correios e o código de acesso gerado em ' . Ambiente::Producao->urlPortal() . '.';

        $resposta = $this->http->executar(new Requisicao(
            metodo: 'POST',
            caminho: '/token/v1/autentica/cartaopostagem',
            operacao: 'gerar token',
            autorizacao: 'Basic ' . base64_encode($config->usuario . ':' . $config->codigoAcesso),
            json: ['numero' => $config->cartaoPostagem],
            sucesso: [200, 201],
            dicas: [
                401 => $dicaUsuario,
                429 => 'A API Token aceita no máximo 3 requisições por segundo: reutilize o token já gerado (guarde-o na sessão ou no cache) em vez de gerar outro.',
            ],
        ));

        $dados = Leitor::de($resposta->json());
        $ambienteResposta = TokenMapeador::ambienteInformado($dados);
        if ($ambienteResposta !== null && $ambienteResposta !== $config->ambiente->value) {
            throw new ErroConfiguracao(sprintf(
                'O token foi emitido para o ambiente %s, mas a configuração é %s. Confira as credenciais e a urlBase.',
                $ambienteResposta,
                $config->ambiente->value,
            ));
        }

        return TokenMapeador::deApi($dados, $config->ambiente);
    }
}
