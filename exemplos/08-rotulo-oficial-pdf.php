<?php

declare(strict_types=1);

/*
 * Rótulo oficial (PDF): uma tentativa com rotulo()->obter(), que solicita (sem recibo) e baixa.
 * - EmGeracao (PPN-291): o rótulo ainda está sendo gerado; rode de novo passando o recibo mostrado.
 * - Pendente (PPN-288): a pré-postagem está PENDENTE; rode de novo mais tarde, sem recibo.
 * Rode antes o exemplo 05 (ele grava o id da última pré-postagem).
 *
 *   php exemplos/08-rotulo-oficial-pdf.php [idPrePostagem] [idRecibo]
 */

use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Enums\LayoutImpressao;
use MarceloFecchio\Correios\Enums\SituacaoRotulo;

require __DIR__ . '/_inicio.php';

$ultima = json_decode((string) @file_get_contents(sys_get_temp_dir() . '/correios-exemplos-ultima.json'), true);
$id = (string) ($argv[1] ?? (is_array($ultima) ? ($ultima['id'] ?? '') : ''));
if ($id === '') {
    exit("Informe o id da pré-postagem: php exemplos/08-rotulo-oficial-pdf.php PR... [idRecibo]\n");
}

$tentativa = correios()->rotulo()->obter(
    token: token(),
    solicitacao: SolicitacaoRotulo::porIds([$id], layoutImpressao: LayoutImpressao::Padrao),
    recibo: $argv[2] ?? null,
);

switch ($tentativa->situacao) {
    case SituacaoRotulo::Pronto:
        $arquivo = sys_get_temp_dir() . '/' . $tentativa->arquivo?->nome;
        $tentativa->arquivo?->salvar($arquivo);
        mostrar('PDF gravado em', $arquivo);
        break;
    case SituacaoRotulo::EmGeracao:
        echo 'O rótulo ainda está sendo gerado. Daqui a alguns segundos, rode de novo com o recibo:', PHP_EOL;
        echo "php exemplos/08-rotulo-oficial-pdf.php {$id} {$tentativa->recibo?->idRecibo}", PHP_EOL;
        break;
    case SituacaoRotulo::Pendente:
        echo 'A pré-postagem ainda está PENDENTE (status 7). Rode de novo mais tarde, sem recibo.', PHP_EOL;
        break;
}
