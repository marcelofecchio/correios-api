<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use Http\Mock\Client as ClienteMock;
use MarceloFecchio\Correios\ArquivoPdf;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Enums\TamanhoPagina;
use MarceloFecchio\Correios\Etiquetas\ConversorLabelary;
use MarceloFecchio\Correios\Etiquetas\EtiquetaPdf;
use MarceloFecchio\Correios\Etiquetas\EtiquetaZpl;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConexao;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroLimiteRequisicoes;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use Psr\Http\Message\ResponseInterface;

const PDF_TESTE = "%PDF-1.4\n%fictício\n%%EOF";

/** @param array<string, mixed> $opcoes */
function conversorTeste(ClienteMock $cliente, array $opcoes = [], ?Psr\Log\LoggerInterface $logger = null): ConversorLabelary
{
    $fabrica = new HttpFactory();

    return new ConversorLabelary(...array_merge($opcoes, [
        'clienteHttp' => $cliente,
        'fabricaRequisicao' => $fabrica,
        'fabricaStream' => $fabrica,
        'logger' => $logger,
    ]));
}

function respostaPdf(int $total = 1): ResponseInterface
{
    return new Response(200, ['Content-Type' => 'application/pdf', 'X-Total-Count' => (string) $total], PDF_TESTE);
}

function respostaTexto(int $status, string $corpo, array $cabecalhos = []): ResponseInterface
{
    return new Response($status, ['Content-Type' => 'text/plain;charset=utf-8'] + $cabecalhos, $corpo);
}

describe('ConversorLabelary', function (): void {
    it('envia o ZPL cru por POST, na densidade e no tamanho da etiqueta de postagem', function (): void {
        $cliente = clienteMock(respostaPdf());
        $zpl = arquivoFixture('zpl/postagem_203dpi.zpl');

        $pdf = conversorTeste($cliente)->converter(new EtiquetaZpl($zpl, Resolucao::Dpi203));
        $requisicao = ultimaRequisicao($cliente);

        expect($requisicao->getMethod())->toBe('POST')
            ->and((string) $requisicao->getUri())->toBe('https://api.labelary.com/v1/printers/8dpmm/labels/3.94x6.1/')
            ->and($requisicao->getHeaderLine('Accept'))->toBe('application/pdf')
            ->and($requisicao->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
            ->and((string) $requisicao->getBody())->toBe($zpl)
            ->and($requisicao->hasHeader('X-API-Key'))->toBeFalse()
            ->and($requisicao->hasHeader('X-Page-Size'))->toBeFalse()
            ->and($pdf)->toBeInstanceOf(ArquivoPdf::class)
            ->and($pdf->conteudoBinario())->toBe(PDF_TESTE)
            ->and($pdf->conteudoBase64())->toBe(base64_encode(PDF_TESTE))
            ->and($pdf->tamanhoBytes())->toBe(strlen(PDF_TESTE))
            ->and($pdf->quantidade)->toBe(1)
            ->and($pdf->resolucao)->toBe(Resolucao::Dpi203);
    });

    it('usa 12 dpmm em 300 dpi e o maior ^PW/^LL quando há etiquetas de tamanhos diferentes', function (): void {
        $cliente = clienteMock(respostaPdf(2));
        $zpl = arquivoFixture('zpl/postagem_300dpi.zpl') . arquivoFixture('zpl/danfe_300dpi.zpl');

        $pdf = conversorTeste($cliente)->converter(new EtiquetaZpl($zpl, Resolucao::Dpi300));

        expect((string) ultimaRequisicao($cliente)->getUri())->toBe('https://api.labelary.com/v1/printers/12dpmm/labels/3.94x6.1/')
            ->and($pdf->quantidade)->toBe(2)
            ->and($pdf->resolucao)->toBe(Resolucao::Dpi300);
    });

    it('usa 4 x 6 polegadas quando o ZPL não tem ^PW nem ^LL', function (): void {
        $cliente = clienteMock(respostaPdf());
        conversorTeste($cliente)->converter(new EtiquetaZpl("^XA^FO50,50^FDA+B%41^FS^XZ\n", Resolucao::Dpi203));

        expect((string) ultimaRequisicao($cliente)->getUri())->toEndWith('/8dpmm/labels/4x6/')
            ->and((string) ultimaRequisicao($cliente)->getBody())->toBe("^XA^FO50,50^FDA+B%41^FS^XZ\n");
    });

    it('arredonda o tamanho para cima, sem cortar a etiqueta', function (): void {
        $cliente = clienteMock(respostaPdf());
        conversorTeste($cliente)->converter(new EtiquetaZpl("^XA^PW800^LL1199^XZ\n", Resolucao::Dpi203));

        expect((string) ultimaRequisicao($cliente)->getUri())->toEndWith('/labels/3.94x5.91/');
    });

    it('envia a chave dos planos pagos, o host próprio e as opções de página', function (): void {
        $cliente = clienteMock(respostaPdf());
        conversorTeste($cliente, [
            'chaveApi' => ' chave-ficticia ',
            'url' => 'https://labelary-pago.exemplo.com/',
            'pagina' => TamanhoPagina::A4,
            'layout' => '2X2',
            'borda' => false,
        ])->converter(new EtiquetaZpl("^XA^PW800^LL1239^XZ\n", Resolucao::Dpi203));
        $requisicao = ultimaRequisicao($cliente);

        expect((string) $requisicao->getUri())->toStartWith('https://labelary-pago.exemplo.com/v1/printers/')
            ->and($requisicao->getHeaderLine('X-API-Key'))->toBe('chave-ficticia')
            ->and($requisicao->getHeaderLine('X-Page-Size'))->toBe('A4')
            ->and($requisicao->getHeaderLine('X-Page-Layout'))->toBe('2x2')
            ->and($requisicao->getHeaderLine('X-Label-Border'))->toBe('None');
    });

    it('converte pelo atalho EtiquetaZpl::paraPdf()', function (): void {
        $pdf = (new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203))->paraPdf(conversorTeste(clienteMock(respostaPdf())));

        expect($pdf)->toBeInstanceOf(EtiquetaPdf::class);
    });

    it('grava o PDF', function (): void {
        $arquivo = tempnam(sys_get_temp_dir(), 'pdf');
        conversorTeste(clienteMock(respostaPdf()))->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203))->salvar($arquivo);

        expect(file_get_contents($arquivo))->toBe(PDF_TESTE);
        unlink($arquivo);
    });

    it('recusa antes de chamar a API o ZPL sem etiquetas ou com mais de 50', function (string $zpl, string $mensagem): void {
        $cliente = clienteMock();

        expect(fn() => conversorTeste($cliente)->converter(new EtiquetaZpl($zpl, Resolucao::Dpi203)))
            ->toThrow(ErroValidacao::class, $mensagem)
            ->and($cliente->getRequests())->toBe([]);
    })->with([
        'vazio' => ['', 'nenhum bloco'],
        '51 etiquetas' => [str_repeat("^XA^XZ\n", 51), 'recebeu 51'],
        'mais de 1 MB' => ['^XA^FD' . str_repeat('x', ConversorLabelary::MAXIMO_BYTES) . '^FS^XZ', '1 MB'],
    ]);

    it('transforma o erro em texto do Labelary em ErroApi', function (): void {
        try {
            conversorTeste(clienteMock(respostaTexto(400, 'ERROR: Label width is larger than 15.0 inches')))
                ->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
            $this->fail('Deveria lançar ErroApi');
        } catch (ErroApi $e) {
            expect($e->getMessage())->toBe('Labelary respondeu HTTP 400 ao converter o ZPL em PDF: Label width is larger than 15.0 inches')
                ->and($e->statusHttp)->toBe(400)
                ->and($e->mensagens)->toBe(['Label width is larger than 15.0 inches'])
                ->and($e->respostaBruta)->toBe('ERROR: Label width is larger than 15.0 inches');
        }
    });

    it('transforma HTTP 429 em ErroLimiteRequisicoes com o Retry-After', function (): void {
        conversorTeste(clienteMock(respostaTexto(429, 'ERROR: Too many requests', ['Retry-After' => '1'])))
            ->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
    })->throws(ErroLimiteRequisicoes::class, 'tente de novo em 1 s');

    it('não aceita HTTP 200 que não seja PDF', function (): void {
        conversorTeste(clienteMock(new Response(200, ['Content-Type' => 'image/png'], "\x89PNG")))
            ->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
    })->throws(ErroApi::class, 'não é um PDF');

    it('não repete página HTML na mensagem', function (): void {
        conversorTeste(clienteMock(new Response(502, ['Content-Type' => 'text/html'], '<html>Bad Gateway</html>')))
            ->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
    })->throws(ErroApi::class, 'HTTP 502 ao converter o ZPL em PDF: sem mensagem');

    it('transforma falha de rede em ErroConexao', function (): void {
        $cliente = clienteMock();
        $cliente->addException(new NetworkException('timeout', (new HttpFactory())->createRequest('POST', 'https://api.labelary.com')));

        conversorTeste($cliente)->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
    })->throws(ErroConexao::class, 'Labelary');

    it('registra no log método, URL e status, sem o ZPL nem o PDF', function (): void {
        $logger = new LoggerMemoria();
        $zpl = arquivoFixture('zpl/postagem_203dpi.zpl');
        conversorTeste(clienteMock(respostaPdf()), logger: $logger)->converter(new EtiquetaZpl($zpl, Resolucao::Dpi203));

        expect($logger->registros)->toHaveCount(2)
            ->and($logger->tudo())->toContain('8dpmm/labels/3.94x6.1')
            ->and($logger->tudo())->not->toContain('Maria Aparecida')
            ->and($logger->tudo())->not->toContain('%PDF');
    });

    it('valida as opções', function (array $opcoes, string $mensagem): void {
        expect(fn() => conversorTeste(clienteMock(), $opcoes))->toThrow(ErroConfiguracao::class, $mensagem);
    })->with([
        'url' => [['url' => 'api.labelary.com'], 'URL http(s)'],
        'layout inválido' => [['pagina' => TamanhoPagina::A4, 'layout' => '2 por 2'], 'colunas x linhas'],
        'layout sem página' => [['layout' => '2x2'], 'exige labelary.pagina'],
        'timeout' => [['timeout' => 0.0], 'timeout'],
    ]);
});

describe('ConversorLabelary::deArray', function (): void {
    it('lê as chaves em snake_case, com a página pelo nome em português', function (): void {
        $cliente = clienteMock(respostaPdf());
        $fabrica = new HttpFactory();
        ConversorLabelary::deArray(
            ['chave_api' => 'chave-ficticia', 'pagina' => 'carta', 'layout' => '1x2', 'borda' => 'false', 'timeout' => '10', 'url' => ''],
            $cliente,
            $fabrica,
            $fabrica,
        )->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));
        $requisicao = ultimaRequisicao($cliente);

        expect((string) $requisicao->getUri())->toStartWith(ConversorLabelary::URL_GRATUITA . '/')
            ->and($requisicao->getHeaderLine('X-API-Key'))->toBe('chave-ficticia')
            ->and($requisicao->getHeaderLine('X-Page-Size'))->toBe('Letter')
            ->and($requisicao->getHeaderLine('X-Page-Layout'))->toBe('1x2')
            ->and($requisicao->getHeaderLine('X-Label-Border'))->toBe('None');
    });

    it('aceita a página como enum e valor do Labelary', function (mixed $pagina, TamanhoPagina $esperada): void {
        $cliente = clienteMock(respostaPdf());
        $fabrica = new HttpFactory();
        ConversorLabelary::deArray(['pagina' => $pagina], $cliente, $fabrica, $fabrica)
            ->converter(new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203));

        expect(ultimaRequisicao($cliente)->getHeaderLine('X-Page-Size'))->toBe($esperada->value)
            ->and(ultimaRequisicao($cliente)->getHeaderLine('X-Label-Border'))->toBe('Dashed');
    })->with([
        [TamanhoPagina::A6, TamanhoPagina::A6],
        ['Legal', TamanhoPagina::Oficio],
        ['oficio', TamanhoPagina::Oficio],
    ]);

    it('recusa valores inválidos', function (array $config, string $mensagem): void {
        expect(fn() => ConversorLabelary::deArray($config, clienteMock(), new HttpFactory(), new HttpFactory()))
            ->toThrow(ErroConfiguracao::class, $mensagem);
    })->with([
        'página' => [['pagina' => 'A3'], 'use A4, A5, A6, Carta ou Oficio'],
        'borda' => [['borda' => 'talvez'], 'labelary.borda'],
        'timeout' => [['timeout' => 'trinta'], 'labelary.timeout'],
        'não escalar' => [['chave_api' => ['x']], 'labelary.chave_api'],
    ]);
});

describe('EtiquetaPdf', function (): void {
    it('recusa conteúdo que não é PDF', function (): void {
        new EtiquetaPdf('<html>', 1, Resolucao::Dpi203);
    })->throws(ErroValidacao::class, '%PDF');
});
