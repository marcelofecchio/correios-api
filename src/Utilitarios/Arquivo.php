<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;

/** Gravação de arquivos com erro tratado (sem avisos do PHP). */
final class Arquivo
{
    private function __construct() {}

    /** @throws ErroConfiguracao se não conseguir gravar */
    public static function gravar(string $caminho, string $conteudo, string $descricao): void
    {
        $erro = null;
        set_error_handler(static function (int $nivel, string $mensagem) use (&$erro): bool {
            $erro = $mensagem;

            return true;
        });
        try {
            $gravado = file_put_contents($caminho, $conteudo);
        } finally {
            restore_error_handler();
        }

        if ($gravado === false) {
            throw new ErroConfiguracao(sprintf('Não foi possível gravar %s em "%s"%s', $descricao, $caminho, $erro !== null ? ': ' . $erro : '.'));
        }
    }
}
