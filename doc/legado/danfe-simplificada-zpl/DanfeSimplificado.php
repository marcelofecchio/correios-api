<?php

namespace classes\expedicao\danfe\zpl;

use classes\Pedidos;

class DanfeSimplificado
{
	public static function gerarDanfe($dados_pedido)
	{
		if($dados_pedido->status_nota_fiscal != 'emissao_concluida')
			return false;

		$produtos_resumido = Pedidos::pegaResumoProdutos($dados_pedido->codigo_pedido);

		$remetente = 'REMETENTE_'.settings('CONTAS')[$dados_pedido->conta];

		$texto_substituir = [
			'NUMERO_NOTA' => $dados_pedido->numero_nota_fiscal,
			'DATA_EMISSAO' => formatData($dados_pedido->data_envio),
			'CHAVE_NOTA_FISCAL' => $dados_pedido->chave_nota_fiscal,
			'NOME_DESTINATARIO' => $dados_pedido->destinatario,
			'ENDERECO_DESTINATARIO_LINHA_1' => $dados_pedido->endereco.', '.$dados_pedido->numero,
			'ENDERECO_DESTINATARIO_LINHA_2' => $dados_pedido->bairro.', '.$dados_pedido->cidade.'/'.$dados_pedido->estado.' '.$dados_pedido->cep,
			'CODIGO_DE_ENVIO' => $dados_pedido->rastreio ?? '',
			'EMITENTE_LINHA1' => settings($remetente)[1],
			'EMITENTE_LINHA2' => settings($remetente)[2],
			'EMITENTE_LINHA3' => settings($remetente)[3],
			'SKU_PRODUTOS' => $produtos_resumido
		];

		return self::pegaTextoZpl($texto_substituir);

	}

	public static function pegaTextoZpl($texto_substituir = null){

		$nome_arquivo = __DIR__ . '/template_danfe_simplificado.zpl';

	    $conteudo = file_get_contents($nome_arquivo);

	    if($texto_substituir):

		    foreach ($texto_substituir as $key => $value) {
		    	if($value)
		    	   	$conteudo = str_replace($key, $value, $conteudo);
		    }
		endif;

	    return $conteudo;

	}

}