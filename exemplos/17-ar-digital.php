<?php

declare(strict_types=1);

/*
 * Imagem do AR Digital: o comprovante de recebimento digitalizado, para objetos postados com o adicional
 * AR Digital (ARDIG, CategoriaServicoAdicional::AvisoRecebimentoDigital) e já entregues. Até 10 objetos por chamada.
 * Objeto sem imagem não lança erro: vem com disponivel() = false e o motivo em codigoErro/mensagem.
 */

require __DIR__ . '/_inicio.php';

$codigo = $argv[1] ?? 'DG049186226BR';

// Um objeto
$ar = correios()->rastro()->arDigital(token: token(), codigoObjeto: $codigo);
if ($ar->disponivel()) {
    $arquivo = sys_get_temp_dir() . '/ar-' . $ar->codigoObjeto . '.' . $ar->extensao();
    $ar->salvar($arquivo);
    mostrar('AR Digital gravado', sprintf('%s (%s, baixa em %s)', $arquivo, $ar->tipoMime(), $ar->dataBaixa?->format('d/m/Y H:i') ?? '?'));
    // Em HTML: echo '<img src="' . $ar->dataUri() . '" alt="AR Digital">';
} else {
    // SRO-009: objeto de outro contrato; SRO-020: objeto não localizado
    mostrar('Sem imagem do AR Digital', sprintf('[%s] %s', $ar->codigoErro ?? '-', $ar->mensagem ?? ''));
}

// Vários objetos (até 10), na ordem pedida
foreach (correios()->rastro()->arDigitalVarios(token(), [$codigo, 'AB043180865BR']) as $item) {
    echo $item->codigoObjeto, ': ', $item->disponivel() ? 'imagem disponível' : ($item->mensagem ?? 'sem imagem'), PHP_EOL;
}
