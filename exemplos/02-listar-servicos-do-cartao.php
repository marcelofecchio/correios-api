<?php

declare(strict_types=1);

require __DIR__ . '/_inicio.php';

// Percorre todas as páginas da API Meu Contrato (a página começa em 0)
foreach (correios()->contrato()->listarTodosServicos(token: token()) as $servico) {
    echo $servico->codigo, ' - ', $servico->descricao, PHP_EOL;
}

// Um serviço específico, pelo apelido configurado
$sedex = correios()->contrato()->consultarServico(token: token(), servico: 'sedex');
mostrar('SEDEX no cartão', $sedex->descricao);
