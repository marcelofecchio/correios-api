<?php

declare(strict_types=1);

/*
 * Cancelamento síncrono (irreversível), pelo id ou pelo código do objeto. Confira o resultado antes de dar a
 * pré-postagem por cancelada. Em homologação a resposta observada foi:
 * {"resultadoCancelamento": "Cancelamento realizado com sucesso!"}
 * Pré-postagem já cancelada ou postada responde HTTP 400 com PPN-145-5.
 */

use MarceloFecchio\Correios\Excecoes\ErroApi;

require __DIR__ . '/_inicio.php';

$ultima = json_decode((string) @file_get_contents(sys_get_temp_dir() . '/correios-exemplos-ultima.json'), true);
$id = is_array($ultima) ? (string) ($ultima['id'] ?? '') : (string) ($argv[1] ?? '');
if ($id === '') {
    exit("Informe o id da pré-postagem: php exemplos/09-cancelar-pre-postagem.php PR...\n");
}

try {
    $resultado = correios()->prePostagem()->cancelar(token: token(), idPrePostagem: $id);
    mostrar('Resultado', $resultado->resultado);
} catch (ErroApi $e) {
    mostrar('Não cancelada', $e->codigoErro . ' - ' . implode(' ', $e->mensagens));
}

// Também é possível: correios()->prePostagem()->cancelarPorCodigoObjeto(token(), 'AB043180865BR');
