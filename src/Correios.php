<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios;

use MarceloFecchio\Correios\Etiquetas\GeradorEtiquetas;
use MarceloFecchio\Correios\Http\ClienteHttp;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Ponto de entrada do componente.
 *
 * ```php
 * $correios = new Correios(configuracao: $configuracao);
 * $token = $correios->token()->gerar();               // guarde na sessão: $token->paraArray()
 * $preco = $correios->preco()->calcular(token: $token, consulta: $consulta);
 * ```
 *
 * O componente não guarda token, sessão nem cache. Toda chamada autenticada recebe o token como primeiro argumento.
 */
final class Correios
{
    private readonly ClienteHttp $http;

    private ?Servicos\Token $token = null;
    private ?Servicos\Contrato $contrato = null;
    private ?Servicos\Cep $cep = null;
    private ?Servicos\Preco $preco = null;
    private ?Servicos\Prazo $prazo = null;
    private ?Servicos\PrePostagem $prePostagem = null;
    private ?Servicos\Rotulo $rotulo = null;
    private ?Servicos\Dce $dce = null;
    private ?Servicos\Rastro $rastro = null;
    private ?GeradorEtiquetas $etiquetas = null;

    /**
     * @param ?ClientInterface         $clienteHttp       cliente PSR-18; nulo = Guzzle (se instalado) ou descoberta automática
     * @param ?RequestFactoryInterface $fabricaRequisicao fábrica PSR-17; nula = descoberta automática
     * @param ?StreamFactoryInterface  $fabricaStream     fábrica PSR-17; nula = descoberta automática
     * @param ?LoggerInterface         $logger            registra requisições e respostas com dados sensíveis mascarados
     */
    public function __construct(
        public readonly Configuracao $configuracao,
        ?ClientInterface $clienteHttp = null,
        ?RequestFactoryInterface $fabricaRequisicao = null,
        ?StreamFactoryInterface $fabricaStream = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->http = new ClienteHttp($configuracao, $clienteHttp, $fabricaRequisicao, $fabricaStream, $logger);
    }

    public function token(): Servicos\Token
    {
        return $this->token ??= new Servicos\Token($this->configuracao, $this->http);
    }

    public function contrato(): Servicos\Contrato
    {
        return $this->contrato ??= new Servicos\Contrato($this->configuracao, $this->http);
    }

    public function cep(): Servicos\Cep
    {
        return $this->cep ??= new Servicos\Cep($this->configuracao, $this->http);
    }

    public function preco(): Servicos\Preco
    {
        return $this->preco ??= new Servicos\Preco($this->configuracao, $this->http);
    }

    public function prazo(): Servicos\Prazo
    {
        return $this->prazo ??= new Servicos\Prazo($this->configuracao, $this->http);
    }

    public function prePostagem(): Servicos\PrePostagem
    {
        return $this->prePostagem ??= new Servicos\PrePostagem($this->configuracao, $this->http);
    }

    /** Rótulo oficial dos Correios em PDF. */
    public function rotulo(): Servicos\Rotulo
    {
        return $this->rotulo ??= new Servicos\Rotulo($this->configuracao, $this->http);
    }

    public function dce(): Servicos\Dce
    {
        return $this->dce ??= new Servicos\Dce($this->configuracao, $this->http);
    }

    public function rastro(): Servicos\Rastro
    {
        return $this->rastro ??= new Servicos\Rastro($this->configuracao, $this->http);
    }

    /** Etiquetas em ZPL geradas pelo componente (sem chamada à API). */
    public function etiquetas(): GeradorEtiquetas
    {
        return $this->etiquetas ??= new GeradorEtiquetas($this->configuracao);
    }
}
