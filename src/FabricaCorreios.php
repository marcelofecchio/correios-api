<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Cria instâncias de `Correios` com o mesmo cliente HTTP e o mesmo logger, uma por configuração.
 *
 * Útil quando as credenciais não são fixas: vêm do banco, há mais de um contrato ou cada envio usa o ambiente em
 * que foi criado.
 *
 * ```php
 * $fabrica = new FabricaCorreios(logger: $logger);
 * $correios = $fabrica->deArray($credenciaisDoBanco);   // ou $fabrica->criar(new Configuracao(...))
 * ```
 *
 * No Laravel, a fábrica é registrada como singleton com o logger da aplicação: `app(FabricaCorreios::class)`.
 */
final readonly class FabricaCorreios
{
    /**
     * @param ?ClientInterface         $clienteHttp       cliente PSR-18; nulo = Guzzle (se instalado) ou descoberta automática
     * @param ?RequestFactoryInterface $fabricaRequisicao fábrica PSR-17; nula = descoberta automática
     * @param ?StreamFactoryInterface  $fabricaStream     fábrica PSR-17; nula = descoberta automática
     * @param ?LoggerInterface         $logger            registra requisições e respostas com dados sensíveis mascarados
     */
    public function __construct(
        private ?ClientInterface $clienteHttp = null,
        private ?RequestFactoryInterface $fabricaRequisicao = null,
        private ?StreamFactoryInterface $fabricaStream = null,
        private ?LoggerInterface $logger = null,
    ) {}

    public function criar(Configuracao $configuracao): Correios
    {
        return new Correios($configuracao, $this->clienteHttp, $this->fabricaRequisicao, $this->fabricaStream, $this->logger);
    }

    /**
     * Atalho para `criar(Configuracao::deArray($config))`.
     *
     * @param array<mixed> $config mesmas chaves de `Configuracao::deArray()`
     */
    public function deArray(array $config): Correios
    {
        return $this->criar(Configuracao::deArray($config));
    }
}
