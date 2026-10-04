<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Excecoes\CorreiosException;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroAutenticacao;
use MarceloFecchio\Correios\Excecoes\ErroConexao;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroLimiteRequisicoes;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use Psr\Log\AbstractLogger;

final class LoggerMemoria extends AbstractLogger
{
    /** @var list<array{nivel: mixed, mensagem: string, contexto: array<mixed>}> */
    public array $registros = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->registros[] = ['nivel' => $level, 'mensagem' => (string) $message, 'contexto' => $context];
    }

    public function tudo(): string
    {
        return (string) json_encode($this->registros, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

describe('Token', function (): void {
    it('gera o token com Basic usuario:codigoAcesso e corpo {"numero": cartão}, aceitando HTTP 201', function (): void {
        $cliente = clienteMock(respostaFixture(201, 'token_201.json'));
        $token = correiosTeste($cliente)->token()->gerar();
        $requisicao = ultimaRequisicao($cliente);

        expect($requisicao->getMethod())->toBe('POST')
            ->and((string) $requisicao->getUri())->toBe('https://apihom.correios.com.br/token/v1/autentica/cartaopostagem')
            ->and($requisicao->getHeaderLine('Authorization'))->toBe('Basic ' . base64_encode(CNPJ_TESTE . ':codigo-de-acesso-ficticio'))
            ->and($requisicao->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and($requisicao->getHeaderLine('Accept'))->toBe('application/json')
            ->and(corpoJson($requisicao))->toBe(['numero' => CARTAO_TESTE])
            ->and($token->valor)->toBe('eyJhbGciOiJSUzUxMiJ9.token-ficticio.assinatura')
            ->and($token->ambiente)->toBe(Ambiente::Homologacao)
            ->and($token->contrato)->toBe(CONTRATO_TESTE)
            ->and($token->diretoriaRegional)->toBe(20)
            ->and($token->cartaoPostagem)->toBe(CARTAO_TESTE)
            ->and($token->cnpj)->toBe(CNPJ_TESTE)
            ->and($token->apis)->toContain(587)
            ->and($token->expiraEm->format('Y-m-d H:i:s P'))->toBe('2026-10-05 10:26:51 -03:00')
            ->and($token->emitidoEm->getTimezone()->getName())->toBe('America/Sao_Paulo');
    });

    it('aceita também HTTP 200', function (): void {
        expect(correiosTeste(clienteMock(respostaFixture(200, 'token_201.json')))->token()->gerar()->valor)->not->toBeEmpty();
    });

    it('lança ErroConfiguracao quando o token é de outro ambiente', function (): void {
        $corpo = json_decode(arquivoFixture('respostas/token_201.json'), true);
        $corpo['ambiente'] = 'PRODUCAO';

        correiosTeste(clienteMock(respostaJson(201, $corpo)))->token()->gerar();
    })->throws(ErroConfiguracao::class, 'PRODUCAO');

    it('transforma HTTP 429 em ErroLimiteRequisicoes orientando a reutilizar o token', function (): void {
        correiosTeste(clienteMock(respostaJson(429, ['msgs' => ['Too Many Requests']])))->token()->gerar();
    })->throws(ErroLimiteRequisicoes::class, 'reutilize o token');

    it('lembra que em homologação o usuário é o CNPJ no HTTP 401', function (): void {
        try {
            correiosTeste(clienteMock(respostaJson(401, ['msgs' => ['Não autenticado']])))->token()->gerar();
            $this->fail('Deveria lançar ErroAutenticacao');
        } catch (ErroAutenticacao $e) {
            expect($e->getMessage())->toContain('CNPJ')
                ->and($e->getMessage())->not->toContain('codigo-de-acesso-ficticio')
                ->and($e->statusHttp)->toBe(401)
                ->and($e->mensagens)->toBe(['Não autenticado']);
        }
    });

    it('em produção a dica do 401 fala do login do Meu Correios', function (): void {
        $config = configuracaoTeste(['ambiente' => Ambiente::Producao, 'usuario' => 'loja.login']);

        expect(fn() => correiosTeste(clienteMock(respostaJson(401, [])), $config)->token()->gerar())
            ->toThrow(ErroAutenticacao::class, 'Meu Correios');
    });

    it('lança ErroApi se a resposta não tiver token', function (): void {
        correiosTeste(clienteMock(respostaJson(201, ['ambiente' => 'HOMOLOGACAO'])))->token()->gerar();
    })->throws(ErroApi::class, 'token');
});

describe('Camada HTTP e erros', function (): void {
    it('lê o formato MessageResponse (msgs, causa) e guarda a resposta bruta', function (): void {
        try {
            correiosTeste(clienteMock(respostaFixture(400, 'erro_message_response_400.json')))->cep()->consultar(tokenTeste(), '01001000');
            $this->fail('Deveria lançar ErroApi');
        } catch (ErroApi $e) {
            expect($e)->toBeInstanceOf(CorreiosException::class)
                ->and($e->statusHttp)->toBe(400)
                ->and($e->mensagens)->toBe(['CEP de destino inválido.'])
                ->and($e->causa)->toContain('ApiNegocioRuntimeException')
                ->and($e->codigoErro)->toBeNull()
                ->and($e->respostaBruta)->toBeArray()
                ->and($e->getMessage())->toContain('consultar CEP');
        }
    });

    it('lê o formato {"mensagem": "PPN-288: ..."} e lança ErroPrePostagemPendente', function (): void {
        try {
            correiosTeste(clienteMock(respostaFixture(400, 'erro_ppn288.json')))->rotulo()->baixar(tokenTeste(), '0bf495ce');
            $this->fail('Deveria lançar ErroPrePostagemPendente');
        } catch (ErroPrePostagemPendente $e) {
            expect($e)->toBeInstanceOf(ErroApi::class)
                ->and($e->codigoErro)->toBe('PPN-288')
                ->and($e->getMessage())->toContain('solicite o rótulo de novo');
        }
    });

    it('trata regra de negócio com HTTP 422', function (): void {
        try {
            correiosTeste(clienteMock(respostaFixture(422, 'erro_ar_422.json')))->prePostagem()->cancelar(tokenTeste(), 'PRx');
            $this->fail('Deveria lançar ErroApi');
        } catch (ErroApi $e) {
            expect($e->statusHttp)->toBe(422)->and($e->mensagens[0])->toContain('AR Convencional 001');
        }
    });

    it('extrai o código ERP-049', function (): void {
        try {
            correiosTeste(clienteMock(respostaFixture(400, 'erro_erp049.json')))->cep()->consultar(tokenTeste(), '01001000');
        } catch (ErroApi $e) {
            expect($e->codigoErro)->toBe('ERP-049');
        }
    });

    it('guarda o texto bruto quando o corpo é HTML (HTTP 500)', function (): void {
        $resposta = new Response(500, ['Content-Type' => 'text/html'], arquivoFixture('respostas/erro_html_500.html'));
        try {
            correiosTeste(clienteMock($resposta))->cep()->consultar(tokenTeste(), '01001000');
            $this->fail('Deveria lançar ErroApi');
        } catch (ErroApi $e) {
            expect($e->statusHttp)->toBe(500)
                ->and($e->respostaBruta)->toBeString()
                ->and($e->respostaBruta)->toContain('HTTP Status 500')
                ->and($e->getMessage())->toContain('não veio em JSON');
        }
    });

    it('diferencia 401 e 403', function (int $status, string $trecho): void {
        expect(fn() => correiosTeste(clienteMock(respostaJson($status, [])))->cep()->consultar(tokenTeste(), '01001000'))
            ->toThrow(ErroAutenticacao::class, $trecho);
    })->with([[401, 'novo token'], [403, 'cartão de postagem']]);

    it('envolve falhas de rede em ErroConexao', function (): void {
        $cliente = clienteMock();
        $cliente->addException(new NetworkException('timeout', new GuzzleHttp\Psr7\Request('GET', '/')));

        try {
            correiosTeste($cliente)->cep()->consultar(tokenTeste(), '01001000');
            $this->fail('Deveria lançar ErroConexao');
        } catch (ErroConexao $e) {
            expect($e->getPrevious())->toBeInstanceOf(NetworkException::class)
                ->and($e->getMessage())->toContain('timeout');
        }
    });

    it('envia Authorization: Bearer com o token recebido como objeto ou texto', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'cep_01001000.json'), respostaFixture(200, 'cep_01001000.json'));
        $correios = correiosTeste($cliente);
        $correios->cep()->consultar(tokenTeste(), '01001000');
        $primeira = ultimaRequisicao($cliente);
        $correios->cep()->consultar('token-em-texto', '01001000');

        expect($primeira->getHeaderLine('Authorization'))->toBe('Bearer eyJhbGciOiJSUzUxMiJ9.token-ficticio.assinatura')
            ->and(ultimaRequisicao($cliente)->getHeaderLine('Authorization'))->toBe('Bearer token-em-texto');
    });

    it('recusa token vazio antes de chamar a API', function (): void {
        $cliente = clienteMock();
        expect(fn() => correiosTeste($cliente)->cep()->consultar(' ', '01001000'))->toThrow(ErroValidacao::class, 'token')
            ->and($cliente->getRequests())->toBeEmpty();
    });

    it('troca o host conforme o ambiente e a urlBase', function (Ambiente|array $config, string $host): void {
        $cliente = clienteMock(respostaFixture(200, 'cep_01001000.json'));
        $configuracao = configuracaoTeste(is_array($config) ? $config : ['ambiente' => $config]);
        correiosTeste($cliente, $configuracao)->cep()->consultar(tokenTeste(), '01001-000');

        expect((string) ultimaRequisicao($cliente)->getUri())->toBe($host . '/cep/v2/enderecos/01001000');
    })->with([
        [Ambiente::Homologacao, 'https://apihom.correios.com.br'],
        [Ambiente::Producao, 'https://api.correios.com.br'],
        [['ambiente' => Ambiente::Producao, 'urlBase' => 'http://localhost:9999/'], 'http://localhost:9999'],
    ]);

    it('registra no log sem token, código de acesso, documentos nem Base64', function (): void {
        $logger = new LoggerMemoria();
        $cliente = clienteMock(respostaFixture(201, 'token_201.json'), respostaFixture(200, 'rotulo_download.json'), respostaJson(200, ['id' => 'PR1', 'statusAtual' => 2]));
        $correios = correiosTeste($cliente, logger: $logger);
        $token = $correios->token()->gerar();
        $correios->rotulo()->baixar($token, 'recibo');
        $correios->contrato()->listarServicos($token);

        $log = $logger->tudo();
        expect($log)->not->toContain($token->valor)
            ->and($log)->not->toContain('codigo-de-acesso-ficticio')
            ->and($log)->not->toContain(CNPJ_TESTE)
            ->and($log)->not->toContain('JVBER')
            ->and($log)->toContain('omitido')
            ->and($log)->toContain('apihom.correios.com.br');
    });

    it('registra falhas de conexão no log', function (): void {
        $logger = new LoggerMemoria();
        $cliente = clienteMock();
        $cliente->addException(new NetworkException('sem rede', new GuzzleHttp\Psr7\Request('GET', '/')));

        expect(fn() => correiosTeste($cliente, logger: $logger)->cep()->consultar(tokenTeste(), '01001000'))->toThrow(ErroConexao::class)
            ->and($logger->registros[1]['nivel'])->toBe('error');
    });

    it('cria o cliente Guzzle com timeout quando nenhum é injetado', function (): void {
        $correios = new MarceloFecchio\Correios\Correios(configuracaoTeste(['timeout' => 5]));

        expect($correios->cep())->toBeInstanceOf(MarceloFecchio\Correios\Servicos\Cep::class)
            ->and($correios->configuracao->timeout)->toBe(5.0);
    });
});
