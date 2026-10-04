<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Cadeia de 164 caracteres do DataMatrix da etiqueta de postagem, com 19 campos de tamanho fixo
 * (guia `correios-etiquetas-zpl.md`, seção 4).
 *
 * Os campos que o sistema antigo deixava fixos (`servicosAdicionais` = `250000000000`, `valorDeclarado` =
 * `00000`, `telefoneDestinatario` = 12 zeros) continuam com esses valores, mas podem ser alterados quando o
 * manual de etiquetas dos Correios definir a codificação.
 */
final readonly class DataMatrixCorreios
{
    public const int TAMANHO = 164;

    public string $cepDestino;
    public string $cepOrigem;
    public string $codigoObjeto;
    public string $cartaoPostagem;
    public string $codigoServico;
    public string $numeroLogradouro;
    public string $complemento;
    public string $reservaCliente;

    public function __construct(
        string $cepDestino,
        string $cepOrigem,
        CodigoRastreamento|string $codigoObjeto,
        string $cartaoPostagem,
        string $codigoServico,
        string $numeroLogradouro = '',
        string $complemento = '',
        public string $servicosAdicionais = '250000000000',
        public string $valorDeclarado = '00000',
        public string $telefoneDestinatario = '000000000000',
        string $reservaCliente = '',
    ) {
        $this->cepDestino = Cep::normalizar($cepDestino, 'datamatrix.cepDestino');
        $this->cepOrigem = Cep::normalizar($cepOrigem, 'datamatrix.cepOrigem');
        $this->codigoObjeto = ($codigoObjeto instanceof CodigoRastreamento ? $codigoObjeto : new CodigoRastreamento($codigoObjeto))->valor;

        $cartao = trim($cartaoPostagem);
        if (preg_match('/^\d{1,10}$/', $cartao) !== 1) {
            throw ErroValidacao::campo('datamatrix.cartaoPostagem', 'deve ter de 1 a 10 dígitos.');
        }
        $this->cartaoPostagem = str_pad($cartao, 10, '0', STR_PAD_LEFT);

        $servico = trim($codigoServico);
        if (preg_match('/^\d{1,5}$/', $servico) !== 1) {
            throw ErroValidacao::campo('datamatrix.codigoServico', 'deve ter de 1 a 5 dígitos.');
        }
        $this->codigoServico = str_pad($servico, 5, '0', STR_PAD_LEFT);

        // Só os dígitos, limitados a 5 ("S/N" → 00000, "123A" → 00123, "1234567" → 12345)
        $this->numeroLogradouro = str_pad(substr(Texto::digitos($numeroLogradouro), 0, 5), 5, '0', STR_PAD_LEFT);

        // ASCII maiúsculo, só A–Z, 0–9 e espaço, cortado e completado para 20 posições
        $complemento = strtoupper(Texto::semAcentos(Texto::limpar($complemento)));
        $complemento = preg_replace('/[^A-Z0-9 ]/', '', $complemento) ?? '';
        $this->complemento = str_pad(substr($complemento, 0, 20), 20, ' ');

        foreach ([
            'servicosAdicionais' => [$servicosAdicionais, 12],
            'valorDeclarado' => [$valorDeclarado, 5],
            'telefoneDestinatario' => [$telefoneDestinatario, 12],
        ] as $campo => [$valor, $tamanho]) {
            if (preg_match('/^\d{' . $tamanho . '}$/', $valor) !== 1) {
                throw ErroValidacao::campo('datamatrix.' . $campo, sprintf('deve ter exatamente %d dígitos.', $tamanho));
            }
        }

        $reserva = trim($reservaCliente);
        if (preg_match('/^[A-Z0-9]{0,30}$/', $reserva) !== 1) {
            throw ErroValidacao::campo('datamatrix.reservaCliente', 'aceita até 30 caracteres A–Z e 0–9.');
        }
        $this->reservaCliente = str_pad($reserva, 30, '0');
    }

    /** Dígito validador do CEP de destino: quanto falta para a soma dos dígitos chegar ao próximo múltiplo de 10. */
    public static function validadorCep(string $cep): int
    {
        $cep = Cep::normalizar($cep);

        return (10 - array_sum(array_map(intval(...), str_split($cep))) % 10) % 10;
    }

    public function gerar(): string
    {
        $cadeia = $this->cepDestino                     //  1. CEP destino (8)
            . '00000'                                   //  2. complemento do CEP (5)
            . $this->cepOrigem                          //  3. CEP origem (8)
            . '00000'                                   //  4. complemento do CEP de origem (5)
            . self::validadorCep($this->cepDestino)     //  5. validador do CEP de destino (1)
            . '51'                                      //  6. IDV (2)
            . $this->codigoObjeto                       //  7. etiqueta (13)
            . $this->servicosAdicionais                 //  8. serviços adicionais (12)
            . $this->cartaoPostagem                     //  9. cartão de postagem (10)
            . $this->codigoServico                      // 10. código do serviço (5)
            . '00'                                      // 11. informação de agrupamento (2)
            . $this->numeroLogradouro                   // 12. número do logradouro (5)
            . $this->complemento                        // 13. complemento do logradouro (20)
            . $this->valorDeclarado                     // 14. valor declarado (5)
            . $this->telefoneDestinatario               // 15. DDD + telefone do destinatário (12)
            . '-00.000000'                              // 16. latitude (10)
            . '-00.000000'                              // 17. longitude (10)
            . '|'                                       // 18. pipe (1)
            . $this->reservaCliente;                    // 19. reserva para o cliente (30)

        if (strlen($cadeia) !== self::TAMANHO) {
            throw new ErroValidacao(sprintf('A cadeia do DataMatrix deve ter %d caracteres; foram gerados %d.', self::TAMANHO, strlen($cadeia)), 'datamatrix');
        }

        return $cadeia;
    }
}
