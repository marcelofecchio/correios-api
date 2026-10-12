<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Http;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use JsonException;
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Excecoes\ErroConexao;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente interno sobre PSR-18: monta a URL do ambiente, os cabeçalhos e o corpo JSON, registra no log
 * (com dados sensíveis mascarados) e converte respostas de erro em exceções.
 *
 * @internal
 */
final class ClienteHttp
{
    private ClientInterface $cliente;
    private RequestFactoryInterface $fabricaRequisicao;
    private StreamFactoryInterface $fabricaStream;

    public function __construct(
        private readonly Configuracao $configuracao,
        ?ClientInterface $cliente = null,
        ?RequestFactoryInterface $fabricaRequisicao = null,
        ?StreamFactoryInterface $fabricaStream = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        try {
            $this->cliente = $cliente ?? self::clientePadrao($configuracao->timeout);
            $this->fabricaRequisicao = $fabricaRequisicao ?? Psr17FactoryDiscovery::findRequestFactory();
            $this->fabricaStream = $fabricaStream ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException $e) {
            throw new ErroConfiguracao(
                'Nenhum cliente HTTP PSR-18 ou fábrica PSR-17 encontrado. Instale um (ex.: composer require guzzlehttp/guzzle) ou injete-o no construtor de Correios.',
                0,
                $e,
            );
        }
    }

    /**
     * Envia a requisição e devolve a resposta se o código HTTP estiver entre os de sucesso.
     *
     * @throws \MarceloFecchio\Correios\Excecoes\CorreiosException
     */
    public function executar(Requisicao $requisicao): RespostaHttp
    {
        $resposta = $this->enviar($requisicao);
        if (!in_array($resposta->status, $requisicao->sucesso, true)) {
            throw TratadorErros::excecao($resposta, $requisicao);
        }

        return $resposta;
    }

    public function enviar(Requisicao $requisicao): RespostaHttp
    {
        $url = $this->configuracao->urlApi() . $requisicao->caminho;
        $query = $requisicao->queryString();
        if ($query !== '') {
            $url .= '?' . $query;
        }

        $psr = $this->fabricaRequisicao->createRequest($requisicao->metodo, $url)
            ->withHeader('Accept', 'application/json');
        if ($requisicao->autorizacao !== null) {
            $psr = $psr->withHeader('Authorization', $requisicao->autorizacao);
        }

        $corpo = '';
        if ($requisicao->json !== null) {
            try {
                $corpo = json_encode($requisicao->json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $e) {
                throw new ErroConfiguracao('Não foi possível montar o corpo JSON da requisição: ' . $e->getMessage(), 0, $e);
            }
            $psr = $psr->withHeader('Content-Type', 'application/json')
                ->withBody($this->fabricaStream->createStream($corpo));
        }

        $this->logger?->debug('Correios → {metodo} {url}', [
            'metodo' => $requisicao->metodo,
            'url' => MascaradorLog::texto($url),
            'corpo' => $corpo !== '' && $this->configuracao->logarCorpo ? MascaradorLog::corpo($corpo) : null,
        ]);

        $inicio = microtime(true);
        try {
            $respostaPsr = $this->cliente->sendRequest($psr);
        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Correios ✕ {metodo} {url}: {erro}', [
                'metodo' => $requisicao->metodo,
                'url' => MascaradorLog::texto($url),
                'erro' => $e->getMessage(),
            ]);

            throw new ErroConexao(sprintf('Falha de conexão com os Correios em "%s": %s', $requisicao->operacao, $e->getMessage()), 0, $e);
        }

        $resposta = new RespostaHttp(
            $respostaPsr->getStatusCode(),
            (string) $respostaPsr->getBody(),
            $respostaPsr->getHeaderLine('Content-Type'),
        );

        $this->logger?->debug('Correios ← {status} {metodo} {url} ({ms} ms)', [
            'status' => $resposta->status,
            'metodo' => $requisicao->metodo,
            'url' => MascaradorLog::texto($url),
            'ms' => (int) round((microtime(true) - $inicio) * 1000),
            'corpo' => $this->configuracao->logarCorpo ? MascaradorLog::corpo($resposta->corpo) : null,
        ]);

        return $resposta;
    }

    /** Guzzle com o timeout, se instalado; senão, o cliente PSR-18 descoberto. Também usado por `ConversorLabelary`. */
    public static function clientePadrao(float $timeout): ClientInterface
    {
        if (class_exists(\GuzzleHttp\Client::class)) {
            return new \GuzzleHttp\Client(['timeout' => $timeout, 'connect_timeout' => min(10.0, $timeout)]);
        }

        return Psr18ClientDiscovery::find();
    }
}
