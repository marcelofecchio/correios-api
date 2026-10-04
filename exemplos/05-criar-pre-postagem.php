<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Excecoes\ErroApi;

require __DIR__ . '/_inicio.php';

$nova = new NovaPrePostagem(
    destinatario: new Pessoa(
        nome: 'Maria Aparecida Souza',
        endereco: new Endereco('01001-000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', complemento: 'Apto 12'),
        documento: '529.982.247-25',
        email: 'maria@exemplo.com',
        celular: '(11) 91234-5678',
    ),
    servico: 'sedex',
    volume: new Volume(pesoGramas: 800, formato: FormatoObjeto::CaixaPacote, comprimento: 30, largura: 20, altura: 10),
    itens: [Item::comValor('Teclado para notebook', 1, '250,00')],
    // O código do valor declarado muda por serviço (019 no SEDEX e 064 no PAC nos testes)
    adicionais: [ServicoAdicional::valorDeclarado((string) variavel('CORREIOS_ADICIONAL_VD_SEDEX', '019'), 25000)],
    observacao: 'Pedido 12345',
    pedidoExterno: 'PED-12345',
);

try {
    $criada = correios()->prePostagem()->criar(token: token(), prePostagem: $nova);
} catch (ErroApi $e) {
    mostrar('Erro', $e->getMessage());
    mostrar('Código', $e->codigoErro);
    exit(1);
}

mostrar('Id', $criada->id);
mostrar('Código do objeto', $criada->codigoObjeto?->formatado());
mostrar('Status', $criada->status?->descricao());
mostrar('Prazo para postar', $criada->prazoPostagem?->format('d/m/Y'));
mostrar('Pode imprimir o rótulo agora', $criada->podeImprimirRotulo());

file_put_contents(sys_get_temp_dir() . '/correios-exemplos-ultima.json', json_encode(['id' => $criada->id, 'codigo' => (string) $criada->codigoObjeto]));
