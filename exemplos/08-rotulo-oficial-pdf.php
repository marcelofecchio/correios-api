<?php

declare(strict_types=1);

/*
 * Rótulo oficial (PDF): confirmar statusAtual = 2 → solicitar → baixar.
 * Se o download lançar ErroPrePostagemPendente (PPN-288), o recibo ficou inválido: aguarde o status 2 e
 * SOLICITE DE NOVO. Rode antes o exemplo 05 (ele grava o id da última pré-postagem).
 */

use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Enums\LayoutImpressao;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;

require __DIR__ . '/_inicio.php';

$ultima = json_decode((string) @file_get_contents(sys_get_temp_dir() . '/correios-exemplos-ultima.json'), true);
$id = is_array($ultima) ? (string) ($ultima['id'] ?? '') : (string) ($argv[1] ?? '');
if ($id === '') {
    exit("Informe o id da pré-postagem: php exemplos/08-rotulo-oficial-pdf.php PR...\n");
}

$recibo = correios()->rotulo()->solicitar(token: token(), solicitacao: SolicitacaoRotulo::porIds([$id], layoutImpressao: LayoutImpressao::Padrao));
mostrar('Recibo', $recibo->idRecibo);

try {
    $pdf = correios()->rotulo()->baixar(token: token(), idRecibo: $recibo);
    $arquivo = sys_get_temp_dir() . '/' . $pdf->nome;
    $pdf->salvar($arquivo);
    mostrar('PDF gravado em', $arquivo);
} catch (ErroPrePostagemPendente $e) {
    echo 'A pré-postagem ainda está PENDENTE (status 7). Aguarde o status 2 e solicite o rótulo de novo.', PHP_EOL;
}
