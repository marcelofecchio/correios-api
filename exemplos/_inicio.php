<?php

declare(strict_types=1);

/*
 * Inicialização comum aos exemplos. As credenciais vêm de variáveis de ambiente (nunca do código):
 *
 *   CORREIOS_AMBIENTE=homologacao
 *   CORREIOS_USUARIO=<em homologação, o CNPJ com 14 dígitos>
 *   CORREIOS_CODIGO_ACESSO=<código de acesso do portal CWS do mesmo ambiente>
 *   CORREIOS_CNPJ=<CNPJ do contrato>
 *   CORREIOS_CARTAO_POSTAGEM=<cartão, com os zeros à esquerda>
 *   CORREIOS_CONTRATO=<contrato>            (opcional: vem do token)
 *   CORREIOS_SERVICO_SEDEX=03220            (códigos do seu contrato)
 *   CORREIOS_SERVICO_PAC=03298
 *   CORREIOS_SERVICO_SEDEX_REVERSO=03247
 *   CORREIOS_ADICIONAL_VD_SEDEX=019         (código do valor declarado no SEDEX do seu contrato)
 *
 * Rode a partir da raiz do projeto: php exemplos/03-consultar-cep.php
 */

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Token;

require __DIR__ . '/../vendor/autoload.php';

function variavel(string $nome, ?string $padrao = null): ?string
{
    $valor = getenv($nome);

    return $valor === false || $valor === '' ? $padrao : $valor;
}

function correios(): Correios
{
    static $correios = null;

    return $correios ??= new Correios(Configuracao::deArray([
        'ambiente' => variavel('CORREIOS_AMBIENTE', 'homologacao'),
        'usuario' => variavel('CORREIOS_USUARIO'),
        'codigo_acesso' => variavel('CORREIOS_CODIGO_ACESSO'),
        'cnpj' => variavel('CORREIOS_CNPJ'),
        'cartao_postagem' => variavel('CORREIOS_CARTAO_POSTAGEM'),
        'contrato' => variavel('CORREIOS_CONTRATO'),
        'servicos' => array_filter([
            'sedex' => variavel('CORREIOS_SERVICO_SEDEX', '03220'),
            'pac' => variavel('CORREIOS_SERVICO_PAC', '03298'),
            'sedex_reverso' => variavel('CORREIOS_SERVICO_SEDEX_REVERSO', '03247'),
        ]),
        'remetente_padrao' => [
            'nome' => 'Loja Exemplo Ltda',
            'documento' => variavel('CORREIOS_CNPJ'),
            'email' => 'contato@exemplo.com.br',
            'celular' => '11987654321',
            'endereco' => [
                'cep' => '01310100', 'logradouro' => 'Avenida Paulista', 'numero' => '1000',
                'complemento' => 'Sala 101', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP',
            ],
        ],
        'etiquetas' => [
            'logos_por_servico' => ['sedex' => 'logo_servico_sedex', 'sedex_reverso' => 'logo_servico_sedex', 'pac' => 'logo_servico_pac'],
            'nomes_servicos' => ['sedex' => 'SEDEX', 'pac' => 'PAC', 'sedex_reverso' => 'SEDEX REVERSO'],
        ],
    ]));
}

/**
 * Reutiliza o token enquanto estiver válido. Nos exemplos (linha de comando) o token fica em um arquivo
 * temporário; numa aplicação web, guarde-o na sessão (veja 01-gerar-token-e-guardar-na-sessao.php).
 */
function token(): Token
{
    $arquivo = sys_get_temp_dir() . '/correios-exemplos-token.json';
    $salvo = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;
    if (is_array($salvo)) {
        $token = Token::deArray($salvo);
        if ($token->estaValido() && $token->ambiente === correios()->configuracao->ambiente) {
            return $token;
        }
    }

    $token = correios()->token()->gerar();
    file_put_contents($arquivo, json_encode($token->paraArray()));
    chmod($arquivo, 0600);

    return $token;
}

function mostrar(string $titulo, mixed $valor): void
{
    echo $titulo, ': ', is_scalar($valor) || $valor === null ? var_export($valor, true) : json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
}
