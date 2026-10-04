<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Dados da etiqueta de postagem 10x15 cm.
 *
 * O código do objeto vem da pré-postagem (`PrePostagemCriada::$codigoObjeto`): a aplicação nunca o gera.
 * Na logística reversa os papéis já chegam invertidos (remetente = cliente, destinatário = loja).
 */
final readonly class DadosEtiquetaPostagem
{
    public CodigoRastreamento $codigoObjeto;
    public ?string $numeroNotaFiscal;
    public ?string $numeroPedido;
    public ?string $observacoes;

    /** @var list<string> */
    public array $siglasAdicionais;

    /**
     * @param string       $codigoServico    código ou apelido configurado
     * @param ?string      $cartaoPostagem   padrão: o da configuração
     * @param ?string      $contrato         padrão: o da configuração
     * @param list<string> $siglasAdicionais ex.: `['VD', 'AR']`
     * @param ?string      $nomeServico      sobrepõe `ConfiguracaoEtiquetas::$nomesServicos`
     */
    public function __construct(
        CodigoRastreamento|string $codigoObjeto,
        public string $codigoServico,
        public Pessoa $remetente,
        public Pessoa $destinatario,
        public int $pesoGramas,
        public ?string $cartaoPostagem = null,
        public ?string $contrato = null,
        public string $volume = '1/1',
        ?string $numeroNotaFiscal = null,
        ?string $numeroPedido = null,
        array $siglasAdicionais = [],
        ?string $observacoes = null,
        public ?string $nomeServico = null,
    ) {
        $this->codigoObjeto = $codigoObjeto instanceof CodigoRastreamento ? $codigoObjeto : new CodigoRastreamento($codigoObjeto);

        if (trim($codigoServico) === '') {
            throw ErroValidacao::campo('codigoServico', 'informe o código ou o apelido do serviço.');
        }
        if ($pesoGramas < 1 || $pesoGramas > 999_999) {
            throw ErroValidacao::campo('pesoGramas', 'deve ser positivo e ter até 6 dígitos.');
        }
        if (preg_match('/^\d{1,3}\/\d{1,3}$/', $volume) !== 1) {
            throw ErroValidacao::campo('volume', 'use o formato "1/1" ou "2/3".');
        }

        $this->numeroNotaFiscal = self::opcional($numeroNotaFiscal);
        $this->numeroPedido = self::opcional($numeroPedido);
        $this->observacoes = self::opcional($observacoes);

        $siglas = [];
        foreach ($siglasAdicionais as $sigla) {
            $sigla = strtoupper(Texto::limpar($sigla));
            if ($sigla !== '' && !in_array($sigla, $siglas, true)) {
                $siglas[] = $sigla;
            }
        }
        $this->siglasAdicionais = $siglas;
    }

    private static function opcional(?string $valor): ?string
    {
        $valor = Texto::limpar((string) $valor);

        return $valor !== '' ? $valor : null;
    }
}
