<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\ModalidadePagamento;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\ChaveDocumentoFiscal;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Dados para criar uma pré-postagem (síncrona ou assíncrona).
 *
 * Documento fiscal: informe `chaveNFe`, `numeroNotaFiscal` ou ao menos um item da declaração de conteúdo.
 *
 * Logística reversa: o **remetente é o cliente que devolve** (com e-mail, obrigatório) e o **destinatário é a
 * loja**. Nesse caso `remetente` não pode ficar nulo (o remetente padrão da configuração é a loja).
 */
final readonly class NovaPrePostagem
{
    public ?ChaveDocumentoFiscal $chaveNFe;
    public ?string $numeroNotaFiscal;
    public ?string $observacao;
    public ?string $pedidoExterno;
    public ?CodigoRastreamento $codigoObjetoIda;

    /** @var list<Item> */
    public array $itens;

    /** @var list<ServicoAdicional> */
    public array $adicionais;

    /**
     * @param string                 $servico    apelido configurado (`sedex`) ou código do serviço (`03220`)
     * @param ?Pessoa                $remetente  null = remetente padrão da configuração
     * @param array<Item>             $itens
     * @param array<ServicoAdicional> $adicionais
     */
    public function __construct(
        public Pessoa $destinatario,
        public string $servico,
        public Volume $volume,
        public ?Pessoa $remetente = null,
        array $itens = [],
        array $adicionais = [],
        ChaveDocumentoFiscal|string|null $chaveNFe = null,
        ?string $numeroNotaFiscal = null,
        public bool $emiteDCe = false,
        ?string $observacao = null,
        ?string $pedidoExterno = null,
        public ?DateTimeImmutable $prazoPostagem = null,
        public bool $logisticaReversa = false,
        public ?DateTimeImmutable $dataValidadeLogReversa = null,
        CodigoRastreamento|string|null $codigoObjetoIda = null,
        public ?ModalidadePagamento $modalidadePagamento = null,
        public bool $cienteObjetoNaoProibido = true,
    ) {
        if (trim($servico) === '') {
            throw ErroValidacao::campo('servico', 'informe o apelido ou o código do serviço.');
        }

        $this->itens = array_values($itens);
        $this->adicionais = array_values($adicionais);

        $this->chaveNFe = match (true) {
            $chaveNFe instanceof ChaveDocumentoFiscal => $chaveNFe,
            $chaveNFe === null || trim($chaveNFe) === '' => null,
            default => new ChaveDocumentoFiscal($chaveNFe),
        };

        $numero = Texto::digitos((string) $numeroNotaFiscal);
        if ($numeroNotaFiscal !== null && trim($numeroNotaFiscal) !== '' && ($numero === '' || strlen($numero) > 12 || $numero !== trim($numeroNotaFiscal))) {
            throw ErroValidacao::campo('numeroNotaFiscal', 'deve ter até 12 dígitos numéricos.');
        }
        $this->numeroNotaFiscal = $numero !== '' ? $numero : null;

        if ($this->chaveNFe === null && $this->numeroNotaFiscal === null && $this->itens === []) {
            throw ErroValidacao::campo('documentoFiscal', 'informe a chave da NF-e, o número da nota fiscal ou os itens da declaração de conteúdo.');
        }
        if ($emiteDCe && $this->itens === []) {
            throw ErroValidacao::campo('itens', 'a DCe emitida pelos Correios (emiteDCe) exige os itens da declaração de conteúdo.');
        }

        $this->observacao = self::textoLimitado('observacao', $observacao, 50);
        $this->pedidoExterno = self::textoLimitado('pedidoExterno', $pedidoExterno, 25);

        if ($logisticaReversa) {
            if ($remetente === null) {
                throw ErroValidacao::campo('remetente', 'na logística reversa o remetente é o cliente que devolve e deve ser informado.');
            }
            if ($remetente->email === null) {
                throw ErroValidacao::campo('remetente.email', 'é obrigatório na logística reversa.');
            }
        }

        $this->codigoObjetoIda = match (true) {
            $codigoObjetoIda instanceof CodigoRastreamento => $codigoObjetoIda,
            $codigoObjetoIda === null || trim($codigoObjetoIda) === '' => null,
            default => new CodigoRastreamento($codigoObjetoIda),
        };
    }

    private static function textoLimitado(string $campo, ?string $valor, int $maximo): ?string
    {
        $valor = Texto::limpar((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (Texto::tamanho($valor) > $maximo) {
            throw ErroValidacao::campo($campo, sprintf('deve ter no máximo %d caracteres.', $maximo));
        }

        return $valor;
    }
}
