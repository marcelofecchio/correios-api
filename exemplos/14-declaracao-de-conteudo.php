<?php

declare(strict_types=1);

/*
 * Declaração de conteúdo (envio sem nota fiscal).
 *
 * A etiqueta de declaração de conteúdo em ZPL ainda NÃO faz parte desta versão (falta o texto legal e o
 * layout aprovados). Use a alternativa oficial: crie a pré-postagem com os itens e emiteDCe = true, e imprima a
 * DACE pelos Correios — veja exemplos/10-dce-e-dace.php. Os itens informados aqui vão para o campo
 * itensDeclaracaoConteudo da pré-postagem.
 */

use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

require __DIR__ . '/../vendor/autoload.php';

$itens = [
    Item::comValor('Livro de receitas', 2, '45,90'),
    new Item('Camiseta algodão', 1, 5990),
];

$total = array_sum(array_map(static fn(Item $item): int => $item->valorTotalCentavos(), $itens));
foreach ($itens as $numero => $item) {
    printf('%d. %s — %d x R$ %s%s', $numero + 1, $item->descricao, $item->quantidade, Dinheiro::formatarBr($item->valorUnitarioCentavos), PHP_EOL);
}
echo 'Total declarado: R$ ', Dinheiro::formatarBr($total), PHP_EOL;
