<?php

declare(strict_types=1);

/*
 * Envio sem nota fiscal: os Correios emitem a DCe (emiteDCe) e a aplicação imprime a DACE.
 * Tipo T = texto para impressora térmica; R e C = PDF. A DACE ainda não foi testada em homologação.
 */

use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\SolicitacaoDace;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Excecoes\CorreiosException;

require __DIR__ . '/_inicio.php';

$criada = correios()->prePostagem()->criar(token: token(), prePostagem: new NovaPrePostagem(
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP'), '52998224725'),
    servico: 'sedex',
    volume: new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
    itens: [new Item('Teclado para notebook', 1, 25000)],
    emiteDCe: true,
));
mostrar('Pré-postagem com DCe', $criada->id);

try {
    $dace = correios()->dce()->imprimirDace(token: token(), solicitacao: new SolicitacaoDace(TipoDace::Completa, idsPrePostagens: [$criada->id]));
    $dace->salvar(sys_get_temp_dir() . '/dace.pdf');
    mostrar('DACE gravada', sys_get_temp_dir() . '/dace.pdf');
} catch (CorreiosException $e) {
    // A DCe pode demorar a ser gerada; tente de novo mais tarde
    mostrar('DACE indisponível', $e->getMessage());
}

// Com NF-e emitida depois da criação, informe a chave:
// correios()->dce()->atualizarDocumentoFiscal(token(), $criada->codigoObjeto, '<chave de 44 dígitos>');

correios()->prePostagem()->cancelar(token(), $criada->id);
