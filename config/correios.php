<?php

declare(strict_types=1);

/*
 * Configuração do componente marcelofecchio/correios-api para o Laravel.
 *
 * Publique com: php artisan vendor:publish --tag=correios-config
 */

return [
    // "homologacao" ou "producao"
    'ambiente' => env('CORREIOS_AMBIENTE', 'homologacao'),

    // ATENÇÃO: em HOMOLOGAÇÃO o usuário é o CNPJ (14 dígitos, sem pontuação).
    // Em PRODUÇÃO é o login do Meu Correios. Cada ambiente tem seu próprio código de acesso.
    'usuario' => env('CORREIOS_USUARIO'),

    // Código de acesso gerado no portal CWS do mesmo ambiente (cwshom.correios.com.br ou cws.correios.com.br)
    'codigo_acesso' => env('CORREIOS_CODIGO_ACESSO'),

    'cnpj' => env('CORREIOS_CNPJ'),

    // Texto: mantenha os zeros à esquerda
    'cartao_postagem' => env('CORREIOS_CARTAO_POSTAGEM'),

    // Opcionais: se ausentes, são lidos do token
    'contrato' => env('CORREIOS_CONTRATO'),
    'diretoria_regional' => env('CORREIOS_DR'),

    // Apelido => código do serviço. Os códigos variam por contrato (consulte $correios->contrato()->listarTodosServicos())
    // e devem ser texto, com os zeros à esquerda. Ex.: CORREIOS_SERVICO_SEDEX=03220
    'servicos' => [
        'sedex' => env('CORREIOS_SERVICO_SEDEX'),
        'pac' => env('CORREIOS_SERVICO_PAC'),
        'sedex_reverso' => env('CORREIOS_SERVICO_SEDEX_REVERSO'),
        'pac_reverso' => env('CORREIOS_SERVICO_PAC_REVERSO'),
    ],

    // Remetente padrão (opcional). Exemplo:
    // 'remetente_padrao' => [
    //     'nome' => 'Minha Loja Ltda',
    //     'documento' => '11222333000181',
    //     'email' => 'contato@minhaloja.com.br',
    //     'celular' => '11987654321',
    //     'endereco' => [
    //         'cep' => '01310100', 'logradouro' => 'Avenida Paulista', 'numero' => '1000',
    //         'complemento' => 'Sala 101', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP',
    //     ],
    // ],
    'remetente_padrao' => null,

    'etiquetas' => [
        // 203 ou 300 dpi
        'resolucao' => env('CORREIOS_ETIQUETAS_RESOLUCAO', 203),
        // Diretório com templates próprios (null = templates do pacote)
        'diretorio_templates' => null,
        'logo_correios' => true,
        // Apelido ou código do serviço => parcial do logo
        'logos_por_servico' => [
            'sedex' => 'logo_servico_sedex',
            'sedex_reverso' => 'logo_servico_sedex',
            'pac' => 'logo_servico_pac',
            'pac_reverso' => 'logo_servico_pac',
        ],
        // Apelido ou código do serviço => nome impresso
        'nomes_servicos' => [
            'sedex' => 'SEDEX',
            'sedex_reverso' => 'SEDEX REVERSO',
            'pac' => 'PAC',
            'pac_reverso' => 'PAC REVERSO',
        ],
        // null = automático (marca impressa em homologação)
        'marca_homologacao' => null,
    ],

    'timeout' => env('CORREIOS_TIMEOUT', 30),

    // Conversão do ZPL em PDF pelo Labelary (app(ConversorPdf::class)), alternativa ao rótulo oficial. ATENÇÃO: o
    // ZPL leva nome, endereço e documento de remetente e destinatário; o plano gratuito não tem retenção zero de dados.
    'labelary' => [
        // Planos pagos: host e chave enviados pelo Labelary. Sem chave = plano gratuito (3 req/s, 5.000/dia)
        'url' => env('CORREIOS_LABELARY_URL', 'https://api.labelary.com'),
        'chave_api' => env('CORREIOS_LABELARY_CHAVE_API'),
        // null = página do tamanho da etiqueta; ou A4, A5, A6, Carta, Oficio
        'pagina' => env('CORREIOS_LABELARY_PAGINA'),
        // Colunas x linhas por página; exige 'pagina'. Sem redução: no A4 cabem duas etiquetas 10x15 cm (2x1)
        'layout' => env('CORREIOS_LABELARY_LAYOUT'),
        // Borda tracejada para recortar (só com 'pagina')
        'borda' => true,
        'timeout' => env('CORREIOS_LABELARY_TIMEOUT', 30),
    ],

    // Grava no log (DEBUG) o corpo das requisições e respostas. O corpo vai mascarado (token, documentos, nome,
    // endereço, telefone, e-mail); false = só método, URL, status e duração.
    'log_corpo' => env('CORREIOS_LOG_CORPO', true),
];
