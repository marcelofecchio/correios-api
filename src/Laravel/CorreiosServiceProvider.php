<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use Psr\Log\LoggerInterface;

/**
 * Integração opcional com o Laravel (auto-discovery). Registra `Configuracao` e `Correios` como singletons,
 * a partir de `config/correios.php`.
 */
final class CorreiosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(self::arquivoConfiguracao(), 'correios');

        $this->app->singleton(Configuracao::class, static function (Application $app): Configuracao {
            $config = $app->make(Repository::class)->get('correios', []);

            return Configuracao::deArray(is_array($config) ? $config : []);
        });

        $this->app->singleton(Correios::class, static function (Application $app): Correios {
            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return new Correios(
                configuracao: $app->make(Configuracao::class),
                logger: $logger instanceof LoggerInterface ? $logger : null,
            );
        });

        $this->app->alias(Correios::class, 'correios');
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
