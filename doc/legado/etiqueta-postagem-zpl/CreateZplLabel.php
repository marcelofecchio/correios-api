<?php

namespace CorreiosApi\Services\Label\Zpl;

use CorreiosApi\Includes\Person;
use CorreiosApi\Includes\QrCodeLabel;
use Exception;

class CreateZplLabel
{
	private Person $dados_remetente;
	private Person $dados_destinatario;
	private $dados_contrato;
	private $dados_envio;
	private $codigo_etiqueta;
	private $qr_code;
	private $observacoes;

	public function __construct(
		Person $dados_remetente,
		Person $dados_destinatario,
		$dados_contrato,
		$dados_envio,
		$codigo_etiqueta,
		$observacoes
	)
	{
		$this->dados_remetente = $dados_remetente;
		$this->dados_destinatario = $dados_destinatario;
		$this->dados_contrato = $dados_contrato;
		$this->dados_envio = $dados_envio;
		$this->codigo_etiqueta = $codigo_etiqueta;
		$this->observacoes = $observacoes;
	}

	public function getLabel()
	{
		$this->getQrCode();

		$file_name = strtolower($this->dados_envio->nome) . "_template.zpl";

		$template_file_path = __DIR__ . '/../../../Templates/' . $file_name;

		if(!file_exists($template_file_path))
			throw new Exception("Arquivo de template não foi encontrado", 1001);
		
		$template = file_get_contents($template_file_path);

	    $texto_substituir = [
			'[CODIGO_MATRIX]' => $this->qr_code,
			'[NUMERO_NF]' => $this->dados_envio->numero_nota_fiscal ?? '',
			'[CONTRATO]' => $this->dados_contrato->contrato,
			'[NOME_SERVICO]' => $this->dados_envio->nome,
			'[CODIGO_ETIQUETA]' => $this->codigo_etiqueta,
			'[NOME_DESTINATARIO]' => $this->dados_destinatario->getNome(),
			'[ENDERECO_DESTINATARIO]' => $this->dados_destinatario->getLogradouro(),
			'[NUMERO_ENDERECO_DESTINATARIO]' => $this->dados_destinatario->getNumero(),
			'[BAIRRO_DESTINATARIO]' => $this->dados_destinatario->getBairro(),
			'[COMPLEMENTO_DESTINATARIO]' => $this->dados_destinatario->getComplemento(),
			'[CEP_DESTINATARIO_COM_HIFEN]' => substr_replace($this->dados_destinatario->getCep(),"-",5,0),
			'[CIDADE_DESTINATARIO]' => $this->dados_destinatario->getCidade(),
			'[ESTADO_DESTINATARIO]' => $this->dados_destinatario->getUf(),
			'[CEP_DESTINATARIO]' => $this->dados_destinatario->getCep(),
			'[NOME_REMETENTE]' => $this->dados_remetente->getNome(),
			'[ENDERECO_REMETENTE]' => $this->dados_remetente->getLogradouro(),
			'[NUMERO_ENDERECO_REMETENTE]' => $this->dados_remetente->getNumero(),
			'[BAIRRO_REMETENTE]' => $this->dados_remetente->getBairro(),
			'[COMPLEMENTO_REMETENTE]' => $this->dados_remetente->getComplemento(),
			'[CEP_REMETENTE_COM_HIFEN]' => substr_replace($this->dados_remetente->getCep(),"-",5,0),
			'[CIDADE_REMETENTE]' => $this->dados_remetente->getCidade(),
			'[ESTADO_REMETENTE]' => $this->dados_remetente->getUf(),
			'[OBSERVACOES]' => $this->observacoes
	    ];

	    foreach ($texto_substituir as $key => $value) {
	    	$template = str_replace($key, $value, $template);
	    }

	    return $template;

	}

	public function getQrCode()
	{

		$this->qr_code = QrCodeLabel::create(
			cep_origem: $this->dados_remetente->getCep(),
			cep_destino: $this->dados_destinatario->getCep(),
			codigo_rastreio: $this->codigo_etiqueta,
			cartao_de_postagem: $this->dados_contrato->cartao,
			codigo_servico: $this->dados_envio->codigo,
			numero_logradouro: $this->dados_destinatario->getNumero(),
			complemento: $this->dados_destinatario->getComplemento()
		);

	}



}