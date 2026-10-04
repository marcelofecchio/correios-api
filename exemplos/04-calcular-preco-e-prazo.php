<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

require __DIR__ . '/_inicio.php';

$volume = new Volume(pesoGramas: 800, formato: FormatoObjeto::CaixaPacote, comprimento: 30, largura: 20, altura: 10);

// Preço de um serviço (contrato e DR vêm da configuração ou do token)
$preco = correios()->preco()->calcular(token: token(), consulta: new ConsultaPreco(
    servico: 'sedex',
    cepOrigem: '01310-100',
    cepDestino: '01001-000',
    volume: $volume,
));
mostrar('SEDEX', 'R$ ' . Dinheiro::formatarBr((int) $preco->precoFinalCentavos));

// Lote (até 5): HTTP 206 = parte dos itens falhou; confira item a item
$lote = correios()->preco()->calcularLote(token: token(), consultas: [
    new ConsultaPreco('sedex', '01310100', '01001000', $volume),
    new ConsultaPreco('pac', '01310100', '01001000', $volume),
]);
foreach ($lote->itens as $item) {
    echo $item->nuRequisicao, ': ', $item->sucesso() ? 'R$ ' . Dinheiro::formatarBr((int) $item->resultado->precoFinalCentavos) : 'erro - ' . $item->erro(), PHP_EOL;
}

// Adicionais obrigatórios do serviço (sgIndicador = O)
foreach (correios()->preco()->servicosAdicionais(token: token(), servico: 'sedex') as $adicional) {
    echo $adicional->codigo, ' ', $adicional->sigla, ' ', $adicional->obrigatorio() ? '(obrigatório)' : '(opcional)', PHP_EOL;
}

// Prazo
$prazo = correios()->prazo()->calcular(token: token(), consulta: new ConsultaPrazo('pac', '01310100', '01001000'));
mostrar('Prazo do PAC (dias úteis)', $prazo->prazoDias);
mostrar('Data máxima', $prazo->dataMaxima?->format('d/m/Y'));
