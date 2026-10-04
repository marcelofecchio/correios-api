<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as ClienteMock;
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Etiquetas\ConfiguracaoEtiquetas;
use MarceloFecchio\Correios\Tests\Laravel\TestCase as LaravelTestCase;
use MarceloFecchio\Correios\Token;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

uses(LaravelTestCase::class)->in('Laravel');
uses()->group('integracao')->in('Integracao');

/*
 * Dados fictícios usados nos testes (nenhum dado real de contrato, cartão ou cliente).
 */
const CNPJ_TESTE = '11222333000181';
const CPF_TESTE = '52998224725';
const CARTAO_TESTE = '0012345678';
const CONTRATO_TESTE = '9912345678';

/** @param array<string, mixed> $sobrescrever */
function configuracaoTeste(array $sobrescrever = []): Configuracao
{
    $padrao = [
        'ambiente' => Ambiente::Homologacao,
        'usuario' => CNPJ_TESTE,
        'codigoAcesso' => 'codigo-de-acesso-ficticio',
        'cnpj' => CNPJ_TESTE,
        'cartaoPostagem' => CARTAO_TESTE,
        'contrato' => CONTRATO_TESTE,
        'diretoriaRegional' => 20,
        'servicos' => ['sedex' => '03220', 'pac' => '03298', 'sedex_reverso' => '03247', 'pac_reverso' => '03301'],
        'remetentePadrao' => lojaTeste(),
        'etiquetas' => new ConfiguracaoEtiquetas(
            logosPorServico: ['sedex' => 'logo_servico_sedex', 'sedex_reverso' => 'logo_servico_sedex', '03298' => 'logo_servico_pac', 'pac_reverso' => 'logo_servico_pac'],
            nomesServicos: ['sedex' => 'SEDEX', 'pac' => 'PAC', 'sedex_reverso' => 'SEDEX REVERSO', 'pac_reverso' => 'PAC REVERSO'],
        ),
    ];

    return Configuracao::deArray(array_merge($padrao, $sobrescrever));
}

function lojaTeste(): Pessoa
{
    return new Pessoa(
        nome: 'Loja Teste Homologacao Ltda',
        endereco: new Endereco('01310-100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 'Sala 101'),
        documento: '11.222.333/0001-81',
        email: 'loja.teste@exemplo.com.br',
        celular: '(11) 98765-4321',
    );
}

function clienteTeste(): Pessoa
{
    return new Pessoa(
        nome: 'Maria Aparecida Souza',
        endereco: new Endereco('01001-000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', 'Apto 12'),
        documento: '529.982.247-25',
        email: 'maria.souza.teste@exemplo.com.br',
        celular: '11912345678',
    );
}

function tokenTeste(?DateTimeImmutable $expiraEm = null): Token
{
    return new Token(
        valor: 'eyJhbGciOiJSUzUxMiJ9.token-ficticio.assinatura',
        emitidoEm: new DateTimeImmutable('2026-10-04T10:00:00-03:00'),
        expiraEm: $expiraEm ?? new DateTimeImmutable('2099-01-01T10:00:00-03:00'),
        ambiente: Ambiente::Homologacao,
        cartaoPostagem: CARTAO_TESTE,
        contrato: CONTRATO_TESTE,
        diretoriaRegional: 20,
        cnpj: CNPJ_TESTE,
        apis: [27, 34, 35, 36, 37, 41, 76, 78, 83, 87, 566, 586, 587],
    );
}

function clienteMock(ResponseInterface ...$respostas): ClienteMock
{
    $cliente = new ClienteMock(new HttpFactory());
    foreach ($respostas as $resposta) {
        $cliente->addResponse($resposta);
    }

    return $cliente;
}

function correiosTeste(ClienteMock $cliente, ?Configuracao $configuracao = null, ?Psr\Log\LoggerInterface $logger = null): Correios
{
    $fabrica = new HttpFactory();

    return new Correios($configuracao ?? configuracaoTeste(), $cliente, $fabrica, $fabrica, $logger);
}

function respostaJson(int $status, mixed $corpo): ResponseInterface
{
    return new Response($status, ['Content-Type' => 'application/json'], is_string($corpo) ? $corpo : (string) json_encode($corpo));
}

function respostaFixture(int $status, string $arquivo): ResponseInterface
{
    return new Response($status, ['Content-Type' => 'application/json'], arquivoFixture('respostas/' . $arquivo));
}

function arquivoFixture(string $caminho): string
{
    $conteudo = file_get_contents(__DIR__ . '/Fixtures/' . $caminho);
    if ($conteudo === false) {
        throw new RuntimeException('Fixture não encontrada: ' . $caminho);
    }

    return $conteudo;
}

/** @return array<mixed> */
function corpoJson(RequestInterface $requisicao): array
{
    $corpo = json_decode((string) $requisicao->getBody(), true);

    return is_array($corpo) ? $corpo : [];
}

function ultimaRequisicao(ClienteMock $cliente): RequestInterface
{
    $requisicao = $cliente->getLastRequest();
    if ($requisicao === false) {
        throw new RuntimeException('Nenhuma requisição enviada.');
    }

    return $requisicao;
}

/** Caminho de um arquivo da documentação do projeto (pasta doc/), que não vai no pacote. */
function arquivoDoc(string $caminho): string
{
    return dirname(__DIR__) . '/doc/' . $caminho;
}

/**
 * Compara o ZPL com o snapshot em tests/Fixtures/zpl. Com ATUALIZAR_SNAPSHOTS=1, grava o snapshot.
 */
function compararSnapshot(string $nome, string $zpl): void
{
    $arquivo = __DIR__ . '/Fixtures/zpl/' . $nome . '.zpl';
    if (getenv('ATUALIZAR_SNAPSHOTS') === '1' || !is_file($arquivo)) {
        if (getenv('ATUALIZAR_SNAPSHOTS') !== '1') {
            throw new RuntimeException("Snapshot {$nome}.zpl não existe. Rode com ATUALIZAR_SNAPSHOTS=1 para criá-lo.");
        }
        file_put_contents($arquivo, $zpl);
    }

    expect($zpl)->toBe(file_get_contents($arquivo));
}
