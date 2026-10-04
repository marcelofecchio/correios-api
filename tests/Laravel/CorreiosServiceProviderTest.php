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
