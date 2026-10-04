<?php

namespace CorreiosApi\Includes;

class QrCodeLabel
{
	private static $matrix = [
			'CEPdestino' => null, // 8
			'ComplementoDoCEP' => '00000', // 5
			'CEPOrigem' => null, // 8
			'ComplementoDoCEPOrigem' => '00000', // 5
			'ValidadorDoCEPDestino' => null, // 1
			'IDV' => '51', //2
			'Etiqueta' => null, // 13
			'ServicosAdicionais' => '250000000000', // 12
			'CartaoDePostagem' => null, // 10
			'CodigoServico' => null, // 5
			'InformacaoDeAgrupamento' => '00', // 2
			'NumeroDoLogradouro' => null, // 5
			'ComplementoDoLogradouro' => null, // 20
			'ValorDeclarado' => '00000', // 5
			'DDDTelefoneDestinatario' => '000000000000', // 12
			'Latitude' => '-00.000000', // 10
			'Longitude' => '-00.000000', // 10
			'Pipe' => '|', //  1
			'ReservaParaCliente' => '000000000000000000000000000000' // 30
		];	

	public static function create(
		$cep_origem, 
		$cep_destino, 
		$codigo_rastreio,
		$cartao_de_postagem,
		$codigo_servico,
		$numero_logradouro,
		$complemento
	)
	{
		self::$matrix['CEPdestino'] = sprintf('%08d', str_replace('-', '', $cep_destino)); // completa com zero a esquerda
		self::$matrix['CEPOrigem'] = sprintf('%08d', str_replace('-', '', $cep_origem));
		self::$matrix['ValidadorDoCEPDestino'] = self::digitoVerificadorCep($cep_destino);
		self::$matrix['Etiqueta'] = $codigo_rastreio; 
		self::$matrix['CartaoDePostagem'] = sprintf('%010d', $cartao_de_postagem);
		self::$matrix['CodigoServico'] = sprintf('%05d', $codigo_servico);
		self::$matrix['NumeroDoLogradouro'] = sprintf('%05d', $numero_logradouro);
		self::$matrix['ComplementoDoLogradouro'] = str_pad(substr($complemento, 0, 20), 20); // se for maior que 20 corta ou então preenche //

		$result_matrix = '';
		foreach (self::$matrix as $value) {
			$result_matrix .= $value;
		}

		return $result_matrix;

	}

	private static function digitoVerificadorCep($cep){

		$cep = str_replace('-', '', $cep);
		$cep = str_split($cep);
		$soma = 0;
		foreach ($cep as $value) {
			$soma += $value;
		}
		$resultado = ceil($soma / 10)*10-$soma;
		return $resultado;
	}

}