<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Etiquetas\GeradorEtiquetas;
use MarceloFecchio\Correios\Laravel\CorreiosServiceProvider;
use MarceloFecchio\Correios\Laravel\Facades\Correios as CorreiosFacade;

it('registra Configuracao e Correios como singletons a partir do config/correios.php', function (): void {
    $correios = app(Correios::class);

    expect($correios)->toBe(app(Correios::class))
        ->and(app('correios'))->toBe($correios)
        ->and(app(Configuracao::class))->toBe($correios->configuracao)
        ->and($correios->configuracao->ambiente)->toBe(Ambiente::Homologacao)
        ->and($correios->configuracao->diretoriaRegional)->toBe(20)
        ->and($correios->configuracao->timeout)->toBe(15.0)
        ->and($correios->configuracao->resolverServico('sedex'))->toBe('03220');
});

it('expõe a facade', function (): void {
    expect(CorreiosFacade::etiquetas())->toBeInstanceOf(GeradorEtiquetas::class)
        ->and(CorreiosFacade::getFacadeRoot())->toBe(app(Correios::class));
});

it('publica o arquivo de configuração', function (): void {
    $publicaveis = Illuminate\Support\ServiceProvider::pathsToPublish(CorreiosServiceProvider::class, 'correios-config');

    expect(array_key_first($publicaveis))->toEndWith('config/correios.php')
        ->and(array_values($publicaveis)[0])->toEndWith('correios.php');
});

it('lê o arquivo de configuração padrão com as variáveis de ambiente', function (): void {
    $config = require dirname(__DIR__, 2) . '/config/correios.php';

    expect($config)->toHaveKeys(['ambiente', 'usuario', 'codigo_acesso', 'cnpj', 'cartao_postagem', 'contrato', 'diretoria_regional', 'servicos', 'etiquetas', 'timeout'])
        ->and($config['etiquetas']['logos_por_servico'])->toHaveKey('sedex');
});

it('falha com mensagem clara se a configuração estiver incompleta', function (): void {
    config()->set('correios.usuario', null);
    app()->forgetInstance(Configuracao::class);
    app()->forgetInstance(Correios::class);

    app(Correios::class);
})->throws(MarceloFecchio\Correios\Excecoes\ErroConfiguracao::class, 'usuario');

it('registra a FabricaCorreios para credenciais que não vêm do .env', function (): void {
    config()->set('correios', []);
    app()->forgetInstance(Configuracao::class);
    app()->forgetInstance(Correios::class);

    $fabrica = app(MarceloFecchio\Correios\FabricaCorreios::class);
    $producao = $fabrica->deArray([
        'ambiente' => 'producao',
        'usuario' => 'login-meu-correios',
        'codigo_acesso' => 'codigo-de-producao',
        'cnpj' => '11222333000181',
        'cartao_postagem' => '0076543210',
    ]);
    $homologacao = $fabrica->deArray([
        'ambiente' => 'homologacao',
        'usuario' => '11222333000181',
        'codigo_acesso' => 'codigo-de-homologacao',
        'cnpj' => '11222333000181',
        'cartao_postagem' => '0012345678',
    ]);

    expect($fabrica)->toBe(app(MarceloFecchio\Correios\FabricaCorreios::class))
        ->and($producao->configuracao->ambiente)->toBe(Ambiente::Producao)
        ->and($homologacao->configuracao->ambiente)->toBe(Ambiente::Homologacao)
        ->and($producao)->not->toBe($homologacao);
});

it('registra o ConversorPdf (Labelary) a partir de correios.labelary', function (): void {
    config()->set('correios.labelary', ['pagina' => 'A4', 'layout' => '2x2']);

    $conversor = app(MarceloFecchio\Correios\Etiquetas\ConversorPdf::class);

    expect($conversor)->toBeInstanceOf(MarceloFecchio\Correios\Etiquetas\ConversorLabelary::class)
        ->and(app(MarceloFecchio\Correios\Etiquetas\ConversorPdf::class))->toBe($conversor);
});

it('traz a seção labelary no arquivo de configuração padrão', function (): void {
    $config = require dirname(__DIR__, 2) . '/config/correios.php';

    expect($config['labelary'])->toHaveKeys(['url', 'chave_api', 'pagina', 'layout', 'borda', 'timeout']);

    MarceloFecchio\Correios\Etiquetas\ConversorLabelary::deArray($config['labelary']);
});
