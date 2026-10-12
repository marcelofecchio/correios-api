<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Etiquetas\ConversorLabelary;
use MarceloFecchio\Correios\Etiquetas\ConversorPdf;
use MarceloFecchio\Correios\FabricaCorreios;
use Psr\Log\LoggerInterface;

/**
 * Integração opcional com o Laravel (auto-discovery).
 *
 * - `Configuracao` e `Correios`: singletons a partir de `config/correios.php` (`.env`). São preguiçosos: sem
 *   configuração, nada falha até alguém pedir um deles.
 * - `FabricaCorreios`: singleton com o logger da aplicação, para criar um `Correios` por configuração quando as
 *   credenciais vêm do banco ou variam por ambiente/contrato.
 * - `ConversorPdf`: `ConversorLabelary` a partir de `correios.labelary`, para converter o ZPL em PDF. Sem uso, nenhuma
 *   chamada é feita.
 */
final class CorreiosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(self::arquivoConfiguracao(), 'correios');

        $this->app->singleton(FabricaCorreios::class, static function (Application $app): FabricaCorreios {
            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return new FabricaCorreios(logger: $logger instanceof LoggerInterface ? $logger : null);
        });

        $this->app->singleton(Configuracao::class, static function (Application $app): Configuracao {
            $config = $app->make(Repository::class)->get('correios', []);

            return Configuracao::deArray(is_array($config) ? $config : []);
        });

        $this->app->singleton(
            Correios::class,
            static fn(Application $app): Correios => $app->make(FabricaCorreios::class)->criar($app->make(Configuracao::class)),
        );

        $this->app->alias(Correios::class, 'correios');

        $this->app->singleton(ConversorPdf::class, static function (Application $app): ConversorPdf {
            $config = $app->make(Repository::class)->get('correios.labelary', []);
            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return ConversorLabelary::deArray(is_array($config) ? $config : [], logger: $logger instanceof LoggerInterface ? $logger : null);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([self::arquivoConfiguracao() => $this->app->configPath('correios.php')], 'correios-config');
        }
    }

    private static function arquivoConfiguracao(): string
    {
        return dirname(__DIR__, 2) . '/config/correios.php';
    }
}
