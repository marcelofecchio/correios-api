<?php

declare(strict_types=1);

/*
 * Pré-postagem assíncrona. PROCESSADO não basta: a pré-postagem fica alguns minutos em 7 (PENDENTE) antes de
 * 2 (PREPOSTADO). A ESPERA É DA APLICAÇÃO (o componente não faz polling): aqui, intervalo crescente e limite
 * de tentativas.
 */

use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\SituacaoProcessamento;

require __DIR__ . '/_inicio.php';

$recibo = correios()->prePostagem()->criarAssincrona(token: token(), prePostagem: new NovaPrePostagem(
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP'), '52998224725'),
    servico: 'pac',
    volume: new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
    itens: [new Item('Livro', 1, 5000)],
));
mostrar('Recibo', $recibo);

$intervalos = [2, 5, 10, 20, 30, 60];
foreach ($intervalos as $tentativa => $segundos) {
    sleep($segundos);
    $processamento = correios()->prePostagem()->consultarAssincrona(token: token(), recibo: $recibo);
    echo sprintf('Tentativa %d: %s', $tentativa + 1, $processamento->situacao?->value ?? 'desconhecida'), PHP_EOL;

    if ($processamento->situacao === SituacaoProcessamento::Falha) {
        mostrar('Falhas', $processamento->todasAsMensagens());
        exit(1);
    }
    if ($processamento->prontaParaRotulo()) {
        mostrar('Pronta para o rótulo', $processamento->prePostagens[0]->id);
        // Cancela a pré-postagem de teste em lote (DELETE com corpo JSON)
        $cancelamento = correios()->prePostagem()->cancelarLote(token(), idsPrePostagens: [$processamento->prePostagens[0]->id]);
        mostrar('Recibo do cancelamento', $cancelamento->idRecibo ?? $cancelamento->respostaBruta());
        exit(0);
    }
}

echo 'Ainda não ficou pronta: tente consultar o recibo mais tarde.', PHP_EOL;
