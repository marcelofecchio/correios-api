<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Tests\Laravel;

use MarceloFecchio\Correios\Laravel\CorreiosServiceProvider;
use MarceloFecchio\Correios\Laravel\Facades\Correios;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [CorreiosServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Correios' => Correios::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('correios', [
            'ambiente' => 'homologacao',
            'usuario' => '11222333000181',
            'codigo_acesso' => 'codigo-de-acesso-ficticio',
            'cnpj' => '11222333000181',
            'cartao_postagem' => '0012345678',
            'contrato' => '9912345678',
            'diretoria_regional' => '20',
            'servicos' => ['sedex' => '03220', 'pac' => '03298'],
            'etiquetas' => ['resolucao' => 203],
            'timeout' => '15',
        ]);
    }
}
