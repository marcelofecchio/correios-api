<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use MarceloFecchio\Correios\Enums\TamanhoPagina;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConexao;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroLimiteRequisicoes;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Http\ClienteHttp;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Converte o ZPL em PDF pela API do Labelary (<https://labelary.com/service.html>), o mesmo motor do visualizador
 * <https://labelary.com/viewer.html>.
 *
 * ```php
 * $pdf = (new ConversorLabelary())->converter($etiqueta);   // ou $etiqueta->paraPdf($conversor)
 * $pdf->salvar('/tmp/etiqueta.pdf');
 * ```
 *
 * - Densidade: a da `EtiquetaZpl` (203 dpi → 8 dpmm, 300 dpi → 12 dpmm).
 * - Tamanho da etiqueta: o maior `^PW` (largura) e `^LL` (comprimento) do ZPL; sem eles, 4 × 6 polegadas.
 * - Página: do tamanho da etiqueta, uma por página. Com `pagina` e `layout`, várias por folha, sem reduzir: no A4
 *   cabem duas etiquetas 10x15 cm (`2x1`); layout que não cabe volta como `ErroApi` (HTTP 400).
 * - Plano gratuito: sem chave, 3 requisições por segundo e 5.000 por dia, **sem retenção zero de dados**. Os planos
 *   pagos trocam o host (`url`) e pedem a chave (`chaveApi`, cabeçalho `X-API-Key`).
 * - Até 50 etiquetas e 1 MB de ZPL por chamada.
 *
 * O ZPL leva nome, endereço e documento de remetente e destinatário a um serviço de terceiros: avalie a LGPD antes
 * de usar o plano gratuito. O log registra só método, URL, status e duração, nunca o ZPL nem o PDF.
 */
final class ConversorLabelary implements ConversorPdf
{
    public const string URL_GRATUITA = 'https://api.labelary.com';
    public const int MAXIMO_ETIQUETAS = 50;
    public const int MAXIMO_BYTES = 1_048_576;

    /** Tamanho usado quando o ZPL não tem `^PW` ou `^LL` (polegadas). */
    private const float LARGURA_PADRAO = 4.0;
    private const float ALTURA_PADRAO = 6.0;

    private readonly string $url;
    private readonly ?string $chaveApi;
    private readonly ?string $layout;
    private readonly ClientInterface $cliente;
    private readonly RequestFactoryInterface $fabricaRequisicao;
    private readonly StreamFactoryInterface $fabricaStream;

    /**
     * @param ?string        $chaveApi     chave dos planos pagos (enviada em `X-API-Key`); null = plano gratuito
     * @param string         $url          host da API (os planos pagos usam outro, informado pelo Labelary)
     * @param ?TamanhoPagina $pagina       null = página do tamanho da etiqueta
     * @param ?string        $layout       colunas x linhas por página (ex.: `2x1`); exige `pagina`
     * @param bool           $borda        borda tracejada em volta de cada etiqueta, para recortar (só com `pagina`)
     * @param float          $timeout      segundos (aplicado quando o componente cria o cliente Guzzle)
     */
    public function __construct(
        ?string $chaveApi = null,
        string $url = self::URL_GRATUITA,
        private readonly ?TamanhoPagina $pagina = null,
        ?string $layout = null,
        private readonly bool $borda = true,
        float $timeout = 30.0,
        ?ClientInterface $clienteHttp = null,
        ?RequestFactoryInterface $fabricaRequisicao = null,
        ?StreamFactoryInterface $fabricaStream = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $url = rtrim(trim($url), '/');
        if (preg_match('#^https?://[^\s/]+#i', $url) !== 1) {
            throw new ErroConfiguracao(sprintf('labelary.url "%s" inválida: informe uma URL http(s).', $url));
        }
        $this->url = $url;

        $chaveApi = trim((string) $chaveApi);
        $this->chaveApi = $chaveApi !== '' ? $chaveApi : null;

        $layout = strtolower(trim((string) $layout));
        if ($layout !== '' && preg_match('/^[1-9]\d?x[1-9]\d?$/', $layout) !== 1) {
            throw new ErroConfiguracao(sprintf('labelary.layout "%s" inválido: use colunas x linhas, como "2x1".', $layout));
        }
        if ($layout !== '' && $pagina === null) {
            throw new ErroConfiguracao('labelary.layout exige labelary.pagina (ex.: A4).');
        }
        $this->layout = $layout !== '' ? $layout : null;

        if ($timeout <= 0) {
            throw new ErroConfiguracao('labelary.timeout deve ser maior que zero (segundos).');
        }

        try {
            $this->cliente = $clienteHttp ?? ClienteHttp::clientePadrao($timeout);
            $this->fabricaRequisicao = $fabricaRequisicao ?? Psr17FactoryDiscovery::findRequestFactory();
            $this->fabricaStream = $fabricaStream ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException $e) {
            throw new ErroConfiguracao(
                'Nenhum cliente HTTP PSR-18 ou fábrica PSR-17 encontrado. Instale um (ex.: composer require guzzlehttp/guzzle) ou injete-o no construtor de ConversorLabelary.',
                0,
                $e,
            );
        }
    }

    /**
     * Chaves (snake_case ou camelCase): `chave_api`, `url`, `pagina` (A4, A5, A6, Carta, Oficio), `layout`,
     * `borda`, `timeout`. Valores vazios usam o padrão.
     *
     * @param array<mixed> $config
     */
    public static function deArray(
        array $config,
        ?ClientInterface $clienteHttp = null,
        ?RequestFactoryInterface $fabricaRequisicao = null,
        ?StreamFactoryInterface $fabricaStream = null,
        ?LoggerInterface $logger = null,
    ): self {
        $texto = static function (string $camel, string $snake) use ($config): ?string {
            $valor = $config[$camel] ?? $config[$snake] ?? null;
            if ($valor instanceof TamanhoPagina) {
                return $valor->value;
            }
            if ($valor !== null && !is_scalar($valor)) {
                throw new ErroConfiguracao(sprintf('Configuração inválida: a chave "labelary.%s" deve ser um texto.', $snake));
            }
            $valor = trim((string) $valor);

            return $valor !== '' ? $valor : null;
        };

        $paginaTexto = $texto('pagina', 'pagina');
        $pagina = $paginaTexto !== null ? TamanhoPagina::deTexto($paginaTexto) : null;
        if ($paginaTexto !== null && $pagina === null) {
            throw new ErroConfiguracao(sprintf('labelary.pagina "%s" inválida: use A4, A5, A6, Carta ou Oficio.', $paginaTexto));
        }

        $borda = $texto('borda', 'borda');
        $borda = $borda !== null ? filter_var($borda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : true;
        if ($borda === null) {
            throw new ErroConfiguracao('Configuração inválida: "labelary.borda" deve ser verdadeiro ou falso.');
        }

        $timeout = $texto('timeout', 'timeout');
        if ($timeout !== null && !is_numeric($timeout)) {
            throw new ErroConfiguracao('Configuração inválida: "labelary.timeout" deve ser um número de segundos.');
        }

        return new self(
            chaveApi: $texto('chaveApi', 'chave_api'),
            url: $texto('url', 'url') ?? self::URL_GRATUITA,
            pagina: $pagina,
            layout: $texto('layout', 'layout'),
            borda: $borda,
            timeout: $timeout !== null ? (float) $timeout : 30.0,
            clienteHttp: $clienteHttp,
            fabricaRequisicao: $fabricaRequisicao,
            fabricaStream: $fabricaStream,
            logger: $logger,
        );
    }

    public function converter(EtiquetaZpl $etiqueta): EtiquetaPdf
    {
        if ($etiqueta->quantidade === 0) {
            throw ErroValidacao::campo('etiqueta', 'o ZPL não tem nenhum bloco ^XA … ^XZ.');
        }
        if ($etiqueta->quantidade > self::MAXIMO_ETIQUETAS) {
            throw ErroValidacao::campo('etiqueta', sprintf(
                'o Labelary converte até %d etiquetas por chamada e recebeu %d: converta em partes.',
                self::MAXIMO_ETIQUETAS,
                $etiqueta->quantidade,
            ));
        }
        if (strlen($etiqueta->zpl) > self::MAXIMO_BYTES) {
            throw ErroValidacao::campo('etiqueta', 'o Labelary aceita até 1 MB de ZPL por chamada: converta em partes.');
        }

        [$largura, $altura] = self::tamanhoPolegadas($etiqueta);
        $url = sprintf(
            '%s/v1/printers/%ddpmm/labels/%sx%s/',
            $this->url,
            $etiqueta->resolucao->pontosPorMilimetro(),
            self::polegadas($largura),
            self::polegadas($altura),
        );

        // Corpo cru: o Labelary não decodifica o application/x-www-form-urlencoded (+, % e & chegam como estão)
        $requisicao = $this->fabricaRequisicao->createRequest('POST', $url)
            ->withHeader('Accept', 'application/pdf')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->fabricaStream->createStream($etiqueta->zpl));
        if ($this->chaveApi !== null) {
            $requisicao = $requisicao->withHeader('X-API-Key', $this->chaveApi);
        }
        if ($this->pagina !== null) {
            $requisicao = $requisicao->withHeader('X-Page-Size', $this->pagina->value)
                ->withHeader('X-Label-Border', $this->borda ? 'Dashed' : 'None');
            if ($this->layout !== null) {
                $requisicao = $requisicao->withHeader('X-Page-Layout', $this->layout);
            }
        }

        $this->logger?->debug('Labelary → POST {url}', ['url' => $url, 'etiquetas' => $etiqueta->quantidade]);
        $inicio = microtime(true);
        try {
            $resposta = $this->cliente->sendRequest($requisicao);
        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Labelary ✕ POST {url}: {erro}', ['url' => $url, 'erro' => $e->getMessage()]);

            throw new ErroConexao('Falha de conexão com o Labelary ao converter o ZPL em PDF: ' . $e->getMessage(), 0, $e);
        }

        $status = $resposta->getStatusCode();
        $corpo = (string) $resposta->getBody();
        $this->logger?->debug('Labelary ← {status} POST {url} ({ms} ms)', [
            'status' => $status,
            'url' => $url,
            'ms' => (int) round((microtime(true) - $inicio) * 1000),
            'bytes' => strlen($corpo),
        ]);

        if ($status === 200 && str_starts_with($corpo, '%PDF')) {
            $total = $resposta->getHeaderLine('X-Total-Count');

            return new EtiquetaPdf($corpo, ctype_digit($total) ? (int) $total : $etiqueta->quantidade, $etiqueta->resolucao);
        }

        $detalhe = self::mensagemErro($corpo, $resposta->getHeaderLine('Content-Type'));
        if ($status === 429) {
            $espera = $resposta->getHeaderLine('Retry-After');

            throw new ErroLimiteRequisicoes(
                sprintf(
                    'Labelary respondeu HTTP 429 ao converter o ZPL em PDF: %s Limite de requisições excedido (plano gratuito: 3 por segundo e 5.000 por dia)%s.',
                    $detalhe,
                    $espera !== '' ? sprintf('; tente de novo em %s s', $espera) : '',
                ),
                $status,
                [$detalhe],
                null,
                null,
                $corpo,
            );
        }

        throw new ErroApi(
            $status === 200
                ? 'Labelary respondeu HTTP 200 ao converter o ZPL em PDF, mas o conteúdo não é um PDF.'
                : sprintf('Labelary respondeu HTTP %d ao converter o ZPL em PDF: %s', $status, $detalhe),
            $status,
            $status === 200 ? [] : [$detalhe],
            null,
            null,
            str_starts_with($corpo, '%PDF') ? '' : $corpo,
        );
    }

    /**
     * Maior `^PW` e `^LL` do ZPL, convertidos em polegadas na densidade da etiqueta.
     *
     * @return array{float, float}
     */
    private static function tamanhoPolegadas(EtiquetaZpl $etiqueta): array
    {
        $pontosPorPolegada = $etiqueta->resolucao->pontosPorMilimetro() * 25.4;
        $maior = static function (string $comando) use ($etiqueta): ?int {
            preg_match_all('/\^' . $comando . '(\d+)/', $etiqueta->zpl, $m);
            $valores = array_map(intval(...), $m[1]);

            return $valores !== [] && max($valores) > 0 ? max($valores) : null;
        };

        $largura = $maior('PW');
        $altura = $maior('LL');

        return [
            $largura !== null ? $largura / $pontosPorPolegada : self::LARGURA_PADRAO,
            $altura !== null ? $altura / $pontosPorPolegada : self::ALTURA_PADRAO,
        ];
    }

    /** Duas casas, arredondando para cima para não cortar a etiqueta (800 pontos em 8 dpmm → 3.94). */
    private static function polegadas(float $valor): string
    {
        $centesimos = (int) ceil(round($valor * 100, 6));

        return rtrim(rtrim(sprintf('%d.%02d', intdiv($centesimos, 100), $centesimos % 100), '0'), '.');
    }

    /** O Labelary responde os erros em texto puro: `ERROR: Label width is larger than 15.0 inches`. */
    private static function mensagemErro(string $corpo, string $tipo): string
    {
        if (str_contains(strtolower($tipo), 'html') || trim($corpo) === '') {
            return 'sem mensagem (conteúdo guardado em respostaBruta).';
        }
        $texto = trim((string) preg_replace('/^\s*ERROR:\s*/i', '', $corpo));

        return mb_strlen($texto) > 300 ? mb_substr($texto, 0, 300) . '…' : $texto;
    }
}
