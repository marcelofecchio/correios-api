<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\ChaveDocumentoFiscal;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Dados do DANFE Simplificado - Etiqueta, de uma NF-e **autorizada** (modelo 55).
 *
 * Duas formas de montar: `DadosDanfe::deXml($xml)` (XML `nfeProc`) ou o construtor, com os dados da nota.
 * A etiqueta não usa itens nem valores da nota.
 *
 * Expõe `chaveAcesso` e `numero` para reutilizar na pré-postagem (`chaveNFe`, `numeroNotaFiscal`) ou em
 * `$correios->dce()->atualizarDocumentoFiscal()`.
 */
final readonly class DadosDanfe
{
    /** Situações aceitas: 100 (autorizado o uso) e 150 (autorizado fora de prazo). */
    public const array SITUACOES_AUTORIZADAS = ['100', '150'];

    /** Tipo de emissão 4 = contingência EPEC, que exige o protocolo do evento (não suportado). */
    private const string EMISSAO_EPEC = '4';

    private const string NS_NFE = 'http://www.portalfiscal.inf.br/nfe';

    public ChaveDocumentoFiscal $chaveAcesso;
    public string $numero;
    public string $serie;
    public string $protocoloAutorizacao;

    /**
     * @param int $tipoOperacao 0 = entrada, 1 = saída (`ide/tpNF`)
     */
    public function __construct(
        ChaveDocumentoFiscal|string $chaveAcesso,
        string $numero,
        string $serie,
        public DateTimeImmutable $dataEmissao,
        public int $tipoOperacao,
        string $protocoloAutorizacao,
        public DateTimeImmutable $dataAutorizacao,
        public ParticipanteDanfe $emitente,
        public ParticipanteDanfe $destinatario,
    ) {
        $this->chaveAcesso = $chaveAcesso instanceof ChaveDocumentoFiscal ? $chaveAcesso : new ChaveDocumentoFiscal($chaveAcesso);
        if ($this->chaveAcesso->modelo() !== '55') {
            throw ErroValidacao::campo('danfe.chaveAcesso', sprintf('o DANFE simplificado é só para NF-e (modelo 55); a chave é do modelo %s.', $this->chaveAcesso->modelo()));
        }
        if ($this->chaveAcesso->tipoEmissao() === self::EMISSAO_EPEC) {
            throw ErroValidacao::campo('danfe.chaveAcesso', 'NF-e emitida em contingência EPEC não é suportada: o DANFE exigiria o protocolo do evento EPEC.');
        }

        $numero = Texto::digitos($numero);
        if ($numero === '' || strlen($numero) > 9 || (int) $numero !== (int) $this->chaveAcesso->numero()) {
            throw ErroValidacao::campo('danfe.numero', sprintf('o número da nota (%s) não confere com a chave de acesso (%s).', $numero, $this->chaveAcesso->numero()));
        }
        $this->numero = ltrim($numero, '0') ?: '0';

        $serie = Texto::digitos($serie);
        if ($serie === '' || strlen($serie) > 3 || (int) $serie !== (int) $this->chaveAcesso->serie()) {
            throw ErroValidacao::campo('danfe.serie', sprintf('a série (%s) não confere com a chave de acesso (%s).', $serie, $this->chaveAcesso->serie()));
        }
        $this->serie = ltrim($serie, '0') ?: '0';

        if (!in_array($tipoOperacao, [0, 1], true)) {
            throw ErroValidacao::campo('danfe.tipoOperacao', 'use 0 (entrada) ou 1 (saída).');
        }

        $protocolo = trim($protocoloAutorizacao);
        if ($protocolo === '') {
            throw ErroValidacao::campo('danfe.protocoloAutorizacao', 'o protocolo de autorização é obrigatório: o DANFE só pode ser gerado para nota autorizada.');
        }
        $this->protocoloAutorizacao = $protocolo;
    }

    /**
     * Lê o XML autorizado da NF-e (`nfeProc`, com `protNFe`). Não lê itens nem valores.
     *
     * @throws ErroValidacao se o XML for inválido, não estiver autorizado (`cStat` 100 ou 150) ou faltarem tags
     */
    public static function deXml(string $xml): self
    {
        $dom = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);
        try {
            $carregado = trim($xml) !== '' && $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
        if (!$carregado) {
            throw ErroValidacao::campo('danfe.xml', 'XML da NF-e inválido.');
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('n', self::NS_NFE);
        $v = static function (string $caminho) use ($xp): string {
            $valor = $xp->evaluate('string(' . $caminho . ')');

            return is_string($valor) ? trim($valor) : '';
        };

        $prot = '/n:nfeProc/n:protNFe/n:infProt';
        $situacao = $v($prot . '/n:cStat');
        if ($situacao === '') {
            throw ErroValidacao::campo('danfe.xml', 'o XML não tem o protocolo de autorização (nfeProc/protNFe). Use o XML autorizado da nota.');
        }
        if (!in_array($situacao, self::SITUACOES_AUTORIZADAS, true)) {
            throw ErroValidacao::campo('danfe.xml', sprintf('a NF-e não está autorizada (cStat %s: %s).', $situacao, $v($prot . '/n:xMotivo')));
        }

        $inf = '/n:nfeProc/n:NFe/n:infNFe';
        $chave = $v($prot . '/n:chNFe');
        $id = $v($inf . '/@Id');
        if ($chave === '' || 'NFe' . $chave !== $id) {
            throw ErroValidacao::campo('danfe.xml', 'chave de acesso ausente ou diferente entre a nota (infNFe/@Id) e o protocolo (chNFe).');
        }

        $obrigatorio = static function (string $caminho, string $descricao) use ($v): string {
            $valor = $v($caminho);
            if ($valor === '') {
                throw ErroValidacao::campo('danfe.xml', sprintf('falta a tag %s (%s).', $caminho, $descricao));
            }

            return $valor;
        };

        $dataEmissao = Datas::interpretar($obrigatorio($inf . '/n:ide/n:dhEmi', 'data de emissão'));
        $dataAutorizacao = Datas::interpretar($obrigatorio($prot . '/n:dhRecbto', 'data da autorização'));
        if ($dataEmissao === null || $dataAutorizacao === null) {
            throw ErroValidacao::campo('danfe.xml', 'data de emissão ou de autorização em formato inesperado.');
        }

        $ende = $inf . '/n:emit/n:enderEmit';
        $endd = $inf . '/n:dest/n:enderDest';
        $documentoDestinatario = $v($inf . '/n:dest/n:CNPJ') ?: $v($inf . '/n:dest/n:CPF');

        return new self(
            chaveAcesso: $chave,
            numero: $obrigatorio($inf . '/n:ide/n:nNF', 'número'),
            serie: $obrigatorio($inf . '/n:ide/n:serie', 'série'),
            dataEmissao: $dataEmissao,
            tipoOperacao: (int) $obrigatorio($inf . '/n:ide/n:tpNF', 'tipo de operação'),
            protocoloAutorizacao: $obrigatorio($prot . '/n:nProt', 'protocolo'),
            dataAutorizacao: $dataAutorizacao,
            emitente: new ParticipanteDanfe(
                nome: $obrigatorio($inf . '/n:emit/n:xNome', 'razão social do emitente'),
                documento: $obrigatorio($inf . '/n:emit/n:CNPJ', 'CNPJ do emitente'),
                uf: $obrigatorio($ende . '/n:UF', 'UF do emitente'),
                inscricaoEstadual: $v($inf . '/n:emit/n:IE'),
                logradouro: $v($ende . '/n:xLgr'),
                numero: $v($ende . '/n:nro'),
                complemento: $v($ende . '/n:xCpl'),
                bairro: $v($ende . '/n:xBairro'),
                cidade: $v($ende . '/n:xMun'),
                cep: $v($ende . '/n:CEP'),
            ),
            destinatario: new ParticipanteDanfe(
                nome: $obrigatorio($inf . '/n:dest/n:xNome', 'nome do destinatário'),
                documento: $documentoDestinatario !== '' ? $documentoDestinatario : null,
                uf: $obrigatorio($endd . '/n:UF', 'UF do destinatário'),
                inscricaoEstadual: $v($inf . '/n:dest/n:IE'),
                logradouro: $v($endd . '/n:xLgr'),
                numero: $v($endd . '/n:nro'),
                complemento: $v($endd . '/n:xCpl'),
                bairro: $v($endd . '/n:xBairro'),
                cidade: $v($endd . '/n:xMun'),
                cep: $v($endd . '/n:CEP'),
            ),
        );
    }

    /** Número com pontos, 9 dígitos: `000.021.486`. */
    public function numeroFormatado(): string
    {
        return implode('.', str_split(str_pad($this->numero, 9, '0', STR_PAD_LEFT), 3));
    }
}
