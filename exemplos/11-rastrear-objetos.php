<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Enums\ResultadoRastreamento;

require __DIR__ . '/_inicio.php';

// Um objeto, todos os eventos
$rastreamento = correios()->rastro()->rastrear(token: token(), codigoObjeto: 'DG 049 186 226 BR');
if (!$rastreamento->encontrado()) {
    mostrar('Mensagem', $rastreamento->mensagem);
}
foreach ($rastreamento->eventos as $evento) {
    echo $evento->dataHora?->format('d/m/Y H:i'), ' - ', $evento->descricao, ' (', $evento->cidade, '/', $evento->uf, ')', PHP_EOL;
}

// Até 50 objetos por chamada, só o último evento
foreach (correios()->rastro()->rastrearVarios(token(), ['DG049186226BR', 'AB043180865BR'], ResultadoRastreamento::Ultimo) as $objeto) {
    echo $objeto->codigoObjeto, ': ', $objeto->ultimoEvento()?->descricao ?? $objeto->mensagem, PHP_EOL;
}
