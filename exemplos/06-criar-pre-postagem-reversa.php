<?php

declare(strict_types=1);

/*
 * Logística reversa: o REMETENTE é o cliente que devolve (e-mail obrigatório) e o DESTINATÁRIO é a loja.
 * Na Pré-postagem o adicional 016 NÃO é exigido (na API Preço ele é obrigatório para o SEDEX REVERSO).
 */

use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;

require __DIR__ . '/_inicio.php';

$cliente = new Pessoa(
    nome: 'Maria Aparecida Souza',
    endereco: new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', 'Apto 12'),
    documento: '52998224725',
    email: 'maria@exemplo.com',
    celular: '11912345678',
);

$reversa = correios()->prePostagem()->criar(token: token(), prePostagem: new NovaPrePostagem(
    destinatario: correios()->configuracao->remetentePadrao ?? throw new RuntimeException('Configure o remetente padrão (a loja).'),
    servico: 'sedex_reverso',
    volume: new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
    remetente: $cliente,
    itens: [new Item('Teclado para notebook', 1, 25000)],
    logisticaReversa: true,
    dataValidadeLogReversa: new DateTimeImmutable('+10 days'),
));

mostrar('Id da reversa', $reversa->id);
mostrar('Código do objeto', (string) $reversa->codigoObjeto);
mostrar('Status', $reversa->status?->descricao());

// Exemplo: cancela a reversa de teste
mostrar('Cancelamento', correios()->prePostagem()->cancelar(token(), $reversa->id)->resultado);
