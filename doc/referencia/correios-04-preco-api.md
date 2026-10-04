# Correios — API Preço

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/preco/v3/api-docs`), versão **3.20.3** (homologação), em 25/09/2026. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 04 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-03-cep-enderecos-api.md` · Próxima: `correios-05-prazo-api.md`

## 0. Resumo rápido

- **Finalidade:** consultar o preço de produtos/serviços dos Correios (nacionais e internacionais) e dos serviços adicionais (aviso de recebimento, mão própria, valor declarado etc.).
- **Acesso (conforme a especificação):** externo **restrito**, com autorização por **contrato** — os preços retornados consideram o contrato informado.
- **Autenticação:** `Authorization: Bearer <token>` — token gerado pela **API Token** (normalmente em `/v1/autentica/cartaopostagem`). Ver `correios-01-token-api.md`.
- **Formato:** JSON (`Content-Type: application/json` nas chamadas POST).
- **Unidades:** peso em **gramas**; dimensões em **centímetros**; CEP com 8 dígitos sem hífen.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/preco` |
| Homologação | `https://apihom.correios.com.br/preco` |

| Método | Caminho | Grupo | Resumo |
|---|---|---|---|
| POST | `/v1/nacional/` | Nacional | Consulta os preços de um lote de produtos nacionais |
| POST | `/v1/nacional` | Nacional | Consulta os preços de um lote de produtos nacionais |
| GET | `/v1/nacional/{coProduto}` | Nacional | Consulta o preço de um produto ou serviço nacional |
| POST | `/v1/internacional` | Internacional | Consulta os preços de um lote de produtos internacionais |
| GET | `/v1/internacional/{coProduto}` | Internacional | Consulta o preço de um produto ou serviço internacional |
| GET | `/v1/servicos-adicionais/{coProduto}` | Serviços Adicionais | Consulta os serviços adicionais disponíveis para um produto |

> Observação: a especificação publica `POST /v1/nacional` e `POST /v1/nacional/` (com barra no final) com o mesmo conteúdo. Use a forma sem barra.

## 1. Endpoints

### Nacional

Preço de produtos e serviços nacionais.

#### 1.1 `POST /v1/nacional/`

**Consulta os preços de um lote de produtos nacionais**

O status da resposta representa o resultado consolidado do lote.

- URL (produção): `https://api.correios.com.br/preco/v1/nacional/`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/nacional/`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `listaPrecos`

**Corpo da requisição** (obrigatório) — Lote de produtos a precificar — schema `LotePrecoNacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosProduto` | array<objeto `PrecoNacionalParam`> |  |  |
| `parametrosProduto[].coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `parametrosProduto[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `parametrosProduto[].nuContrato` | string |  | Número do contrato. |
| `parametrosProduto[].nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `parametrosProduto[].cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `parametrosProduto[].psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `parametrosProduto[].nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `parametrosProduto[].tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `parametrosProduto[].comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `parametrosProduto[].largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `parametrosProduto[].altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `parametrosProduto[].diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `parametrosProduto[].psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `parametrosProduto[].servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `parametrosProduto[].criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `parametrosProduto[].vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `parametrosProduto[].dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `parametrosProduto[].coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `parametrosProduto[].dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `parametrosProduto[].vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `parametrosProduto[].cepDestino` | string |  | CEP de destino da postagem. — Ex.: `71930000` |

```json
{
  "idLote": "string",
  "parametrosProduto": [
    {
      "coProduto": "04162",
      "nuRequisicao": "string",
      "nuContrato": "string",
      "nuDR": 0,
      "cepOrigem": "70902000",
      "psObjeto": "300",
      "nuUnidade": "string",
      "tpObjeto": "string",
      "comprimento": "string",
      "largura": "string",
      "altura": "string",
      "diametro": "string",
      "psCubico": "string",
      "servicosAdicionais": [
        "string"
      ],
      "criterios": [
        "string"
      ],
      "vlDeclarado": "string",
      "dtEvento": "string",
      "coUnidadeOrigem": "string",
      "dtArmazenagem": "string",
      "vlRemessa": "string",
      "cepDestino": "71930000"
    }
  ]
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Todos os itens foram processados com sucesso | array<objeto `PrecoProdutoResponse`> |
| `206` | O lote possui resultados com status diferentes | array<objeto `PrecoProdutoResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `[].noProduto` | string |  | Código do produto ou serviço consultado. |
| `[].pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `[].pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `[].peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `[].pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `[].beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `[].beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `[].beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `[].beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `[].beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `[].beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `[].beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `[].beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `[].beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `[].beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `[].beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `[].beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `[].beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `[].beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `[].beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `[].beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `[].beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `[].vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `[].inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `[].psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `[].servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `[].servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `[].servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `[].servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `[].peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `[].vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `[].qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `[].pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `[].pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `[].pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `[].pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `[].pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `[].pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `[].vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `[].vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `[].pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `[].pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `[].pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `[].txErro` | string |  | Descricao do erro retornado |
| `[].infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `[].infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `[].infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `[].nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `[].peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `[].taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `[].taxaExtra[].codigo` | string |  |  |
| `[].taxaExtra[].tipo` | string |  |  |
| `[].taxaExtra[].vlTaxa` | string |  |  |

Exemplo de resposta 200:

```json
[
  {
    "coProduto": "45209",
    "noProduto": "string",
    "pcBase": "string",
    "pcBaseGeral": "string",
    "peVariacao": "string",
    "pcReferencia": "string",
    "beneficios": [
      {
        "codigo": "string",
        "tipoBeneficio": "DP",
        "tipo": "string",
        "abrangencia": "O",
        "coPacote": "string",
        "noPacote": "string",
        "incondicional": "string",
        "criterio": [
          {
            "nuCriterio": "string",
            "noCriterio": "string",
            "atendido": "string",
            "peNegociado": "string",
            "peConcedido": "string"
          }
        ],
        "vlBaseCalculo": "string",
        "percentual": "string",
        "valor": "string"
      }
    ],
    "vlBaseCalculoImposto": "string",
    "nuRequisicao": "string",
    "inPesoCubico": "string",
    "psCobrado": "string",
    "servicoAdicional": [
      {
        "coServAdicional": "string",
        "tpServAdicional": "string",
        "pcServicoAdicional": "string"
      }
    ],
    "peAdValorem": "0,1000",
    "vlSeguroAutomatico": "50,00",
    "qtAdicional": "string",
    "pcFaixa": "string",
    "pcCadaAdicional": "string",
    "pcTotalAdicional": "string",
    "pcFaixaVariacao": "string",
    "pcCadaAdicionalVariacao": "string",
    "pcTotalAdicionalVariacao": "string",
    "vlTotalDescVariacao": "50,00",
    "vlTotalBeneficios": "50,00",
    "pcProduto": "10,00",
    "pcTotalServicosAdicionais": "10,00",
    "pcFinal": "string",
    "txErro": "string",
    "infoAdicional": [
      {
        "tipo": "LOB_ERP",
        "valor": "string"
      }
    ],
    "nomeProduto": "string",
    "peIsencaoArmazenagem": "string",
    "taxaExtra": [
      {
        "codigo": "string",
        "tipo": "string",
        "vlTaxa": "string"
      }
    ]
  }
]
```

#### 1.2 `POST /v1/nacional`

**Consulta os preços de um lote de produtos nacionais**

O status da resposta representa o resultado consolidado do lote.

- URL (produção): `https://api.correios.com.br/preco/v1/nacional`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/nacional`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `listaPrecos_1`

**Corpo da requisição** (obrigatório) — Lote de produtos a precificar — schema `LotePrecoNacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosProduto` | array<objeto `PrecoNacionalParam`> |  |  |
| `parametrosProduto[].coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `parametrosProduto[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `parametrosProduto[].nuContrato` | string |  | Número do contrato. |
| `parametrosProduto[].nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `parametrosProduto[].cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `parametrosProduto[].psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `parametrosProduto[].nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `parametrosProduto[].tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `parametrosProduto[].comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `parametrosProduto[].largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `parametrosProduto[].altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `parametrosProduto[].diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `parametrosProduto[].psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `parametrosProduto[].servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `parametrosProduto[].criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `parametrosProduto[].vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `parametrosProduto[].dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `parametrosProduto[].coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `parametrosProduto[].dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `parametrosProduto[].vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `parametrosProduto[].cepDestino` | string |  | CEP de destino da postagem. — Ex.: `71930000` |

```json
{
  "idLote": "string",
  "parametrosProduto": [
    {
      "coProduto": "04162",
      "nuRequisicao": "string",
      "nuContrato": "string",
      "nuDR": 0,
      "cepOrigem": "70902000",
      "psObjeto": "300",
      "nuUnidade": "string",
      "tpObjeto": "string",
      "comprimento": "string",
      "largura": "string",
      "altura": "string",
      "diametro": "string",
      "psCubico": "string",
      "servicosAdicionais": [
        "string"
      ],
      "criterios": [
        "string"
      ],
      "vlDeclarado": "string",
      "dtEvento": "string",
      "coUnidadeOrigem": "string",
      "dtArmazenagem": "string",
      "vlRemessa": "string",
      "cepDestino": "71930000"
    }
  ]
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Todos os itens foram processados com sucesso | array<objeto `PrecoProdutoResponse`> |
| `206` | O lote possui resultados com status diferentes | array<objeto `PrecoProdutoResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `[].noProduto` | string |  | Código do produto ou serviço consultado. |
| `[].pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `[].pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `[].peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `[].pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `[].beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `[].beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `[].beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `[].beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `[].beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `[].beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `[].beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `[].beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `[].beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `[].beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `[].beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `[].beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `[].beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `[].beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `[].beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `[].beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `[].beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `[].vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `[].inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `[].psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `[].servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `[].servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `[].servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `[].servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `[].peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `[].vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `[].qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `[].pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `[].pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `[].pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `[].pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `[].pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `[].pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `[].vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `[].vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `[].pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `[].pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `[].pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `[].txErro` | string |  | Descricao do erro retornado |
| `[].infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `[].infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `[].infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `[].nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `[].peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `[].taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `[].taxaExtra[].codigo` | string |  |  |
| `[].taxaExtra[].tipo` | string |  |  |
| `[].taxaExtra[].vlTaxa` | string |  |  |

Exemplo de resposta 200:

```json
[
  {
    "coProduto": "45209",
    "noProduto": "string",
    "pcBase": "string",
    "pcBaseGeral": "string",
    "peVariacao": "string",
    "pcReferencia": "string",
    "beneficios": [
      {
        "codigo": "string",
        "tipoBeneficio": "DP",
        "tipo": "string",
        "abrangencia": "O",
        "coPacote": "string",
        "noPacote": "string",
        "incondicional": "string",
        "criterio": [
          {
            "nuCriterio": "string",
            "noCriterio": "string",
            "atendido": "string",
            "peNegociado": "string",
            "peConcedido": "string"
          }
        ],
        "vlBaseCalculo": "string",
        "percentual": "string",
        "valor": "string"
      }
    ],
    "vlBaseCalculoImposto": "string",
    "nuRequisicao": "string",
    "inPesoCubico": "string",
    "psCobrado": "string",
    "servicoAdicional": [
      {
        "coServAdicional": "string",
        "tpServAdicional": "string",
        "pcServicoAdicional": "string"
      }
    ],
    "peAdValorem": "0,1000",
    "vlSeguroAutomatico": "50,00",
    "qtAdicional": "string",
    "pcFaixa": "string",
    "pcCadaAdicional": "string",
    "pcTotalAdicional": "string",
    "pcFaixaVariacao": "string",
    "pcCadaAdicionalVariacao": "string",
    "pcTotalAdicionalVariacao": "string",
    "vlTotalDescVariacao": "50,00",
    "vlTotalBeneficios": "50,00",
    "pcProduto": "10,00",
    "pcTotalServicosAdicionais": "10,00",
    "pcFinal": "string",
    "txErro": "string",
    "infoAdicional": [
      {
        "tipo": "LOB_ERP",
        "valor": "string"
      }
    ],
    "nomeProduto": "string",
    "peIsencaoArmazenagem": "string",
    "taxaExtra": [
      {
        "codigo": "string",
        "tipo": "string",
        "vlTaxa": "string"
      }
    ]
  }
]
```

#### 1.3 `GET /v1/nacional/{coProduto}`

**Consulta o preço de um produto ou serviço nacional**

- URL (produção): `https://api.correios.com.br/preco/v1/nacional/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/nacional/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPreco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `cepDestino` | query | string |  | CEP de destino da postagem. — Ex.: `71930000` |
| `coProduto` | query | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `nuRequisicao` | query | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `nuContrato` | query | string |  | Número do contrato. |
| `nuDR` | query | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `cepOrigem` | query | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `psObjeto` | query | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `nuUnidade` | query | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `tpObjeto` | query | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `comprimento` | query | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `largura` | query | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `altura` | query | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `diametro` | query | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `psCubico` | query | string |  | Peso cúbico do objeto em gramas. |
| `servicosAdicionais` | query | array<string> |  | Lista de serviços adicionais |
| `criterios` | query | array<string> |  | Lista dos criterios de desconto funcional |
| `vlDeclarado` | query | string |  | Valor declarado associado ao serviço. |
| `dtEvento` | query | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `coUnidadeOrigem` | query | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `dtArmazenagem` | query | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `vlRemessa` | query | string |  | Valor de remessa de valores monetários. |

> Na especificação, `coProduto` aparece tanto no caminho quanto na query (ambos obrigatórios). Envie o mesmo código nos dois.

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/preco/v1/nacional/04162?cepDestino=71930000&coProduto=04162&cepOrigem=70902000&psObjeto=300
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Preço calculado com sucesso | `PrecoProdutoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `noProduto` | string |  | Código do produto ou serviço consultado. |
| `pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `txErro` | string |  | Descricao do erro retornado |
| `infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `taxaExtra[].codigo` | string |  |  |
| `taxaExtra[].tipo` | string |  |  |
| `taxaExtra[].vlTaxa` | string |  |  |

Exemplo de resposta 200:

```json
{
  "coProduto": "45209",
  "noProduto": "string",
  "pcBase": "string",
  "pcBaseGeral": "string",
  "peVariacao": "string",
  "pcReferencia": "string",
  "beneficios": [
    {
      "codigo": "string",
      "tipoBeneficio": "DP",
      "tipo": "string",
      "abrangencia": "O",
      "coPacote": "string",
      "noPacote": "string",
      "incondicional": "string",
      "criterio": [
        {
          "nuCriterio": "string",
          "noCriterio": "string",
          "atendido": "string",
          "peNegociado": "string",
          "peConcedido": "string"
        }
      ],
      "vlBaseCalculo": "string",
      "percentual": "string",
      "valor": "string"
    }
  ],
  "vlBaseCalculoImposto": "string",
  "nuRequisicao": "string",
  "inPesoCubico": "string",
  "psCobrado": "string",
  "servicoAdicional": [
    {
      "coServAdicional": "string",
      "tpServAdicional": "string",
      "pcServicoAdicional": "string"
    }
  ],
  "peAdValorem": "0,1000",
  "vlSeguroAutomatico": "50,00",
  "qtAdicional": "string",
  "pcFaixa": "string",
  "pcCadaAdicional": "string",
  "pcTotalAdicional": "string",
  "pcFaixaVariacao": "string",
  "pcCadaAdicionalVariacao": "string",
  "pcTotalAdicionalVariacao": "string",
  "vlTotalDescVariacao": "50,00",
  "vlTotalBeneficios": "50,00",
  "pcProduto": "10,00",
  "pcTotalServicosAdicionais": "10,00",
  "pcFinal": "string",
  "txErro": "string",
  "infoAdicional": [
    {
      "tipo": "LOB_ERP",
      "valor": "string"
    }
  ],
  "nomeProduto": "string",
  "peIsencaoArmazenagem": "string",
  "taxaExtra": [
    {
      "codigo": "string",
      "tipo": "string",
      "vlTaxa": "string"
    }
  ]
}
```

### Internacional

Preço de produtos e serviços internacionais.

#### 1.4 `POST /v1/internacional`

**Consulta os preços de um lote de produtos internacionais**

O status da resposta representa o resultado consolidado do lote.

- URL (produção): `https://api.correios.com.br/preco/v1/internacional`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/internacional`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `listaPrecos_2`

**Corpo da requisição** (obrigatório) — Lote de produtos a precificar — schema `LotePrecoInternacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosProduto` | array<objeto `PrecoInternacionalParam`> |  |  |
| `parametrosProduto[].coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `parametrosProduto[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `parametrosProduto[].nuContrato` | string |  | Número do contrato. |
| `parametrosProduto[].nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `parametrosProduto[].cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `parametrosProduto[].psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `parametrosProduto[].nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `parametrosProduto[].tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `parametrosProduto[].comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `parametrosProduto[].largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `parametrosProduto[].altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `parametrosProduto[].diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `parametrosProduto[].psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `parametrosProduto[].servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `parametrosProduto[].criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `parametrosProduto[].vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `parametrosProduto[].dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `parametrosProduto[].coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `parametrosProduto[].dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `parametrosProduto[].vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `parametrosProduto[].sgPaisDestino` | string | ✔ | Sigla do país de destino. — minLength: `1` — Ex.: `US` |

```json
{
  "idLote": "string",
  "parametrosProduto": [
    {
      "coProduto": "04162",
      "nuRequisicao": "string",
      "nuContrato": "string",
      "nuDR": 0,
      "cepOrigem": "70902000",
      "psObjeto": "300",
      "nuUnidade": "string",
      "tpObjeto": "string",
      "comprimento": "string",
      "largura": "string",
      "altura": "string",
      "diametro": "string",
      "psCubico": "string",
      "servicosAdicionais": [
        "string"
      ],
      "criterios": [
        "string"
      ],
      "vlDeclarado": "string",
      "dtEvento": "string",
      "coUnidadeOrigem": "string",
      "dtArmazenagem": "string",
      "vlRemessa": "string",
      "sgPaisDestino": "US"
    }
  ]
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Todos os itens foram processados com sucesso | array<objeto `PrecoProdutoResponse`> |
| `206` | O lote possui resultados com status diferentes | array<objeto `PrecoProdutoResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `[].noProduto` | string |  | Código do produto ou serviço consultado. |
| `[].pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `[].pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `[].peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `[].pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `[].beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `[].beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `[].beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `[].beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `[].beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `[].beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `[].beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `[].beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `[].beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `[].beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `[].beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `[].beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `[].beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `[].beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `[].beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `[].beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `[].beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `[].vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `[].inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `[].psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `[].servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `[].servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `[].servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `[].servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `[].peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `[].vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `[].qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `[].pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `[].pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `[].pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `[].pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `[].pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `[].pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `[].vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `[].vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `[].pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `[].pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `[].pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `[].txErro` | string |  | Descricao do erro retornado |
| `[].infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `[].infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `[].infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `[].nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `[].peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `[].taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `[].taxaExtra[].codigo` | string |  |  |
| `[].taxaExtra[].tipo` | string |  |  |
| `[].taxaExtra[].vlTaxa` | string |  |  |

Exemplo de resposta 200:

```json
[
  {
    "coProduto": "45209",
    "noProduto": "string",
    "pcBase": "string",
    "pcBaseGeral": "string",
    "peVariacao": "string",
    "pcReferencia": "string",
    "beneficios": [
      {
        "codigo": "string",
        "tipoBeneficio": "DP",
        "tipo": "string",
        "abrangencia": "O",
        "coPacote": "string",
        "noPacote": "string",
        "incondicional": "string",
        "criterio": [
          {
            "nuCriterio": "string",
            "noCriterio": "string",
            "atendido": "string",
            "peNegociado": "string",
            "peConcedido": "string"
          }
        ],
        "vlBaseCalculo": "string",
        "percentual": "string",
        "valor": "string"
      }
    ],
    "vlBaseCalculoImposto": "string",
    "nuRequisicao": "string",
    "inPesoCubico": "string",
    "psCobrado": "string",
    "servicoAdicional": [
      {
        "coServAdicional": "string",
        "tpServAdicional": "string",
        "pcServicoAdicional": "string"
      }
    ],
    "peAdValorem": "0,1000",
    "vlSeguroAutomatico": "50,00",
    "qtAdicional": "string",
    "pcFaixa": "string",
    "pcCadaAdicional": "string",
    "pcTotalAdicional": "string",
    "pcFaixaVariacao": "string",
    "pcCadaAdicionalVariacao": "string",
    "pcTotalAdicionalVariacao": "string",
    "vlTotalDescVariacao": "50,00",
    "vlTotalBeneficios": "50,00",
    "pcProduto": "10,00",
    "pcTotalServicosAdicionais": "10,00",
    "pcFinal": "string",
    "txErro": "string",
    "infoAdicional": [
      {
        "tipo": "LOB_ERP",
        "valor": "string"
      }
    ],
    "nomeProduto": "string",
    "peIsencaoArmazenagem": "string",
    "taxaExtra": [
      {
        "codigo": "string",
        "tipo": "string",
        "vlTaxa": "string"
      }
    ]
  }
]
```

#### 1.5 `GET /v1/internacional/{coProduto}`

**Consulta o preço de um produto ou serviço internacional**

- URL (produção): `https://api.correios.com.br/preco/v1/internacional/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/internacional/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPreco_1`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `45110` |
| `sgPaisDestino` | query | string | ✔ | Sigla do país de destino. — minLength: `1` — Ex.: `US` |
| `coProduto` | query | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `nuRequisicao` | query | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `nuContrato` | query | string |  | Número do contrato. |
| `nuDR` | query | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `cepOrigem` | query | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `psObjeto` | query | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `nuUnidade` | query | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `tpObjeto` | query | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `comprimento` | query | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `largura` | query | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `altura` | query | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `diametro` | query | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `psCubico` | query | string |  | Peso cúbico do objeto em gramas. |
| `servicosAdicionais` | query | array<string> |  | Lista de serviços adicionais |
| `criterios` | query | array<string> |  | Lista dos criterios de desconto funcional |
| `vlDeclarado` | query | string |  | Valor declarado associado ao serviço. |
| `dtEvento` | query | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `coUnidadeOrigem` | query | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `dtArmazenagem` | query | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `vlRemessa` | query | string |  | Valor de remessa de valores monetários. |

> Na especificação, `coProduto` aparece tanto no caminho quanto na query (ambos obrigatórios). Envie o mesmo código nos dois.

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/preco/v1/internacional/45110?sgPaisDestino=US&coProduto=04162&cepOrigem=70902000&psObjeto=300
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Preço calculado com sucesso | `PrecoProdutoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `noProduto` | string |  | Código do produto ou serviço consultado. |
| `pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `txErro` | string |  | Descricao do erro retornado |
| `infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `taxaExtra[].codigo` | string |  |  |
| `taxaExtra[].tipo` | string |  |  |
| `taxaExtra[].vlTaxa` | string |  |  |

Exemplo de resposta 200:

```json
{
  "coProduto": "45209",
  "noProduto": "string",
  "pcBase": "string",
  "pcBaseGeral": "string",
  "peVariacao": "string",
  "pcReferencia": "string",
  "beneficios": [
    {
      "codigo": "string",
      "tipoBeneficio": "DP",
      "tipo": "string",
      "abrangencia": "O",
      "coPacote": "string",
      "noPacote": "string",
      "incondicional": "string",
      "criterio": [
        {
          "nuCriterio": "string",
          "noCriterio": "string",
          "atendido": "string",
          "peNegociado": "string",
          "peConcedido": "string"
        }
      ],
      "vlBaseCalculo": "string",
      "percentual": "string",
      "valor": "string"
    }
  ],
  "vlBaseCalculoImposto": "string",
  "nuRequisicao": "string",
  "inPesoCubico": "string",
  "psCobrado": "string",
  "servicoAdicional": [
    {
      "coServAdicional": "string",
      "tpServAdicional": "string",
      "pcServicoAdicional": "string"
    }
  ],
  "peAdValorem": "0,1000",
  "vlSeguroAutomatico": "50,00",
  "qtAdicional": "string",
  "pcFaixa": "string",
  "pcCadaAdicional": "string",
  "pcTotalAdicional": "string",
  "pcFaixaVariacao": "string",
  "pcCadaAdicionalVariacao": "string",
  "pcTotalAdicionalVariacao": "string",
  "vlTotalDescVariacao": "50,00",
  "vlTotalBeneficios": "50,00",
  "pcProduto": "10,00",
  "pcTotalServicosAdicionais": "10,00",
  "pcFinal": "string",
  "txErro": "string",
  "infoAdicional": [
    {
      "tipo": "LOB_ERP",
      "valor": "string"
    }
  ],
  "nomeProduto": "string",
  "peIsencaoArmazenagem": "string",
  "taxaExtra": [
    {
      "codigo": "string",
      "tipo": "string",
      "vlTaxa": "string"
    }
  ]
}
```

### Serviços Adicionais

Preço dos serviços adicionais.

#### 1.6 `GET /v1/servicos-adicionais/{coProduto}`

**Consulta os serviços adicionais disponíveis para um produto**

- URL (produção): `https://api.correios.com.br/preco/v1/servicos-adicionais/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/preco/v1/servicos-adicionais/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `listaServicosAdicionais`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `vlDeclarado` | query | string |  | Valor declarado associado ao serviço. |
| `dtEvento` | query | string |  | Data que será usado para o cálculo o preço. YYYY-MM-DD |
| `sgPais` | query | string |  | Sigla do país. — Ex.: `BR` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/preco/v1/servicos-adicionais/04162?sgPais=BR
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Serviços adicionais encontrados | array<objeto `ServicoAdicionalResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].codigo` | string |  |  |
| `[].nome` | string |  |  |
| `[].sigla` | string |  |  |
| `[].sgPais` | string |  |  |
| `[].preco` | number |  |  |
| `[].tipo` | string |  |  |
| `[].coIndicador` | string |  |  |
| `[].noIndicador` | string |  |  |
| `[].sgIndicador` | string |  |  |
| `[].vlMinDeclarado` | number |  |  |
| `[].vlMaxDeclarado` | number |  |  |
| `[].mensagem` | string |  |  |
| `[].requeridos` | array<objeto `ServicoAdicionalRequeridoResponse`> |  |  |
| `[].requeridos[].coRequerido` | string |  |  |
| `[].requeridos[].coSubstituto` | string |  |  |
| `[].requeridos[].dtIniVigencia` | string (date-time) |  |  |
| `[].requeridos[].dtFimVigencia` | string (date-time) |  |  |
| `[].excludentes` | array<objeto `ServicoAdicionalExcludenteResponse`> |  |  |
| `[].excludentes[].coExcludente` | string |  |  |
| `[].excludentes[].dtIniVigencia` | string (date-time) |  |  |
| `[].excludentes[].dtFimVigencia` | string (date-time) |  |  |
| `[].dtIniVigencia` | string (date-time) |  |  |
| `[].dtFimVigencia` | string (date-time) |  |  |
| `[].dtIniVigenciaTarifa` | string (date-time) |  |  |
| `[].dtFimVigenciaTarifa` | string (date-time) |  |  |

Exemplo de resposta 200:

```json
[
  {
    "codigo": "string",
    "nome": "string",
    "sigla": "string",
    "sgPais": "string",
    "preco": 0,
    "tipo": "string",
    "coIndicador": "string",
    "noIndicador": "string",
    "sgIndicador": "string",
    "vlMinDeclarado": 0,
    "vlMaxDeclarado": 0,
    "mensagem": "string",
    "requeridos": [
      {
        "coRequerido": "string",
        "coSubstituto": "string",
        "dtIniVigencia": "2026-01-01T00:00:00",
        "dtFimVigencia": "2026-01-01T00:00:00"
      }
    ],
    "excludentes": [
      {
        "coExcludente": "string",
        "dtIniVigencia": "2026-01-01T00:00:00",
        "dtFimVigencia": "2026-01-01T00:00:00"
      }
    ],
    "dtIniVigencia": "2026-01-01T00:00:00",
    "dtFimVigencia": "2026-01-01T00:00:00",
    "dtIniVigenciaTarifa": "2026-01-01T00:00:00",
    "dtFimVigenciaTarifa": "2026-01-01T00:00:00"
  }
]
```

## 2. Schemas (todos os campos expandidos)

### `LotePrecoNacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosProduto` | array<objeto `PrecoNacionalParam`> |  |  |
| `parametrosProduto[].coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `parametrosProduto[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `parametrosProduto[].nuContrato` | string |  | Número do contrato. |
| `parametrosProduto[].nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `parametrosProduto[].cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `parametrosProduto[].psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `parametrosProduto[].nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `parametrosProduto[].tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `parametrosProduto[].comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `parametrosProduto[].largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `parametrosProduto[].altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `parametrosProduto[].diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `parametrosProduto[].psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `parametrosProduto[].servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `parametrosProduto[].criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `parametrosProduto[].vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `parametrosProduto[].dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `parametrosProduto[].coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `parametrosProduto[].dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `parametrosProduto[].vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `parametrosProduto[].cepDestino` | string |  | CEP de destino da postagem. — Ex.: `71930000` |

### `PrecoNacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `nuContrato` | string |  | Número do contrato. |
| `nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `cepDestino` | string |  | CEP de destino da postagem. — Ex.: `71930000` |

### `BeneficioResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  | Código do tipo de benefício. |
| `tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `coPacote` | string |  | Código do pacote de benefício. |
| `noPacote` | string |  | Nome do pacote de benefício. |
| `incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `criterio[].nuCriterio` | string |  | Número do critério |
| `criterio[].noCriterio` | string |  | Nome do critério |
| `criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `percentual` | string |  | Percentual de benefício concedido. |
| `valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |

### `CriterioAtendidoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nuCriterio` | string |  | Número do critério |
| `noCriterio` | string |  | Nome do critério |
| `atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `peNegociado` | string |  | Percentual negociado para o critério |
| `peConcedido` | string |  | Percentual concedido para o critério |

### `InfoAdicionalResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `valor` | string |  | Conteúdo da informação complementar. |

### `PrecoProdutoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço consultado. — Ex.: `45209` |
| `noProduto` | string |  | Código do produto ou serviço consultado. |
| `pcBase` | string |  | Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade) |
| `pcBaseGeral` | string |  | Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional ) |
| `peVariacao` | string |  | Percentual da variação do combo do cliente. |
| `pcReferencia` | string |  | Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao ) |
| `beneficios` | array<objeto `BeneficioResponse`> |  | Lista de benefícios que serão aplicadas ao preço do produto |
| `beneficios[].codigo` | string |  | Código do tipo de benefício. |
| `beneficios[].tipoBeneficio` | string |  | Valores: `DP`, `DF`, `BP`, `BF`, `BC` |
| `beneficios[].tipo` | string |  | Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação; |
| `beneficios[].abrangencia` | string |  | Abrangência.O: Oferta; N: Negociação; — Valores: `O`, `N` |
| `beneficios[].coPacote` | string |  | Código do pacote de benefício. |
| `beneficios[].noPacote` | string |  | Nome do pacote de benefício. |
| `beneficios[].incondicional` | string |  | Incondicional de redução ou não do valor de base de cálculo (S ou N). |
| `beneficios[].criterio` | array<objeto `CriterioAtendidoResponse`> |  | Lista de critérios funcionais. |
| `beneficios[].criterio[].nuCriterio` | string |  | Número do critério |
| `beneficios[].criterio[].noCriterio` | string |  | Nome do critério |
| `beneficios[].criterio[].atendido` | string |  | Indica que o critério foi atendido [S ou N] |
| `beneficios[].criterio[].peNegociado` | string |  | Percentual negociado para o critério |
| `beneficios[].criterio[].peConcedido` | string |  | Percentual concedido para o critério |
| `beneficios[].vlBaseCalculo` | string |  | Valor base para cálculo do imposto. |
| `beneficios[].percentual` | string |  | Percentual de benefício concedido. |
| `beneficios[].valor` | string |  | Valor do benefício concedido. ( valor = vlBaseCalculo * percentual ) |
| `vlBaseCalculoImposto` | string |  | Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] )  |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta |
| `inPesoCubico` | string |  | Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real |
| `psCobrado` | string |  | Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico |
| `servicoAdicional` | array<objeto `PrecoServicoAdicional`> |  | Preço dos serviços adicionais. |
| `servicoAdicional[].coServAdicional` | string |  | Código do serviço adicional |
| `servicoAdicional[].tpServAdicional` | string |  | Tipo do serviço adicional |
| `servicoAdicional[].pcServicoAdicional` | string |  | Preço do serviço adicional |
| `peAdValorem` | string |  | Percentual de advalorem. — Ex.: `0,1000` |
| `vlSeguroAutomatico` | string |  | Valor declarado do seguro automático — Ex.: `50,00` |
| `qtAdicional` | string |  | Quantidade de adicionais. Exemplo: kilos adicionais |
| `pcFaixa` | string |  | Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase ) |
| `pcCadaAdicional` | string |  | Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase ) |
| `pcTotalAdicional` | string |  | Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional ) |
| `pcFaixaVariacao` | string |  | Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) ) |
| `pcCadaAdicionalVariacao` | string |  | Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) )  |
| `pcTotalAdicionalVariacao` | string |  | Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional ) |
| `vlTotalDescVariacao` | string |  | Valor total de descontos da variação. Descontos referente ao combo do cliente — Ex.: `50,00` |
| `vlTotalBeneficios` | string |  | Valor total dos benefícios — Ex.: `50,00` |
| `pcProduto` | string |  | Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios ) — Ex.: `10,00` |
| `pcTotalServicosAdicionais` | string |  | Valor total dos serviços adicionais que foi embutido no preço final — Ex.: `10,00` |
| `pcFinal` | string |  | Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais ) |
| `txErro` | string |  | Descricao do erro retornado |
| `infoAdicional` | array<objeto `InfoAdicionalResponse`> |  | Lista de informações complementares da formação de preço. |
| `infoAdicional[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `infoAdicional[].valor` | string |  | Conteúdo da informação complementar. |
| `nomeProduto` | string |  | Nome do produto. Apresentado apenas para serviço internacional |
| `peIsencaoArmazenagem` | string |  | Percentual de isenção de pagamento para serviços com armazenagem. |
| `taxaExtra` | array<objeto `TaxaExtraResponse`> |  | Taxas extras |
| `taxaExtra[].codigo` | string |  |  |
| `taxaExtra[].tipo` | string |  |  |
| `taxaExtra[].vlTaxa` | string |  |  |

### `PrecoServicoAdicional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coServAdicional` | string |  | Código do serviço adicional |
| `tpServAdicional` | string |  | Tipo do serviço adicional |
| `pcServicoAdicional` | string |  | Preço do serviço adicional |

### `TaxaExtraResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  |  |
| `tipo` | string |  |  |
| `vlTaxa` | string |  |  |

### `LotePrecoInternacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosProduto` | array<objeto `PrecoInternacionalParam`> |  |  |
| `parametrosProduto[].coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `parametrosProduto[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `parametrosProduto[].nuContrato` | string |  | Número do contrato. |
| `parametrosProduto[].nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `parametrosProduto[].cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `parametrosProduto[].psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `parametrosProduto[].nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `parametrosProduto[].tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `parametrosProduto[].comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `parametrosProduto[].largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `parametrosProduto[].altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `parametrosProduto[].diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `parametrosProduto[].psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `parametrosProduto[].servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `parametrosProduto[].criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `parametrosProduto[].vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `parametrosProduto[].dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `parametrosProduto[].coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `parametrosProduto[].dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `parametrosProduto[].vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `parametrosProduto[].sgPaisDestino` | string | ✔ | Sigla do país de destino. — minLength: `1` — Ex.: `US` |

### `PrecoInternacionalParam`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string | ✔ | Código do produto ou serviço — Ex.: `04162` |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante do serviço. |
| `nuContrato` | string |  | Número do contrato. |
| `nuDR` | integer (int32) |  | Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado. |
| `cepOrigem` | string |  | CEP de origem da postagem. — Ex.: `70902000` |
| `psObjeto` | string |  | Peso do objeto em gramas. — Ex.: `300` |
| `nuUnidade` | string |  | Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos. |
| `tpObjeto` | string |  | Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo. |
| `comprimento` | string |  | Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo. |
| `largura` | string |  | Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote. |
| `altura` | string |  | Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote |
| `diametro` | string |  | Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo |
| `psCubico` | string |  | Peso cúbico do objeto em gramas. |
| `servicosAdicionais` | array<string> |  | Lista de serviços adicionais |
| `criterios` | array<string> |  | Lista dos criterios de desconto funcional |
| `vlDeclarado` | string |  | Valor declarado associado ao serviço. |
| `dtEvento` | string |  | Data que será usado para o cálculo do preço. DD-MM-YYYY |
| `coUnidadeOrigem` | string |  | Código MCMCU da unidade de origem para precificar conforme canal de postagem |
| `dtArmazenagem` | string |  | Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY |
| `vlRemessa` | string |  | Valor de remessa de valores monetários. |
| `sgPaisDestino` | string | ✔ | Sigla do país de destino. — minLength: `1` — Ex.: `US` |

### `ServicoAdicionalExcludenteResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coExcludente` | string |  |  |
| `dtIniVigencia` | string (date-time) |  |  |
| `dtFimVigencia` | string (date-time) |  |  |

### `ServicoAdicionalRequeridoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coRequerido` | string |  |  |
| `coSubstituto` | string |  |  |
| `dtIniVigencia` | string (date-time) |  |  |
| `dtFimVigencia` | string (date-time) |  |  |

### `ServicoAdicionalResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  |  |
| `nome` | string |  |  |
| `sigla` | string |  |  |
| `sgPais` | string |  |  |
| `preco` | number |  |  |
| `tipo` | string |  |  |
| `coIndicador` | string |  |  |
| `noIndicador` | string |  |  |
| `sgIndicador` | string |  |  |
| `vlMinDeclarado` | number |  |  |
| `vlMaxDeclarado` | number |  |  |
| `mensagem` | string |  |  |
| `requeridos` | array<objeto `ServicoAdicionalRequeridoResponse`> |  |  |
| `requeridos[].coRequerido` | string |  |  |
| `requeridos[].coSubstituto` | string |  |  |
| `requeridos[].dtIniVigencia` | string (date-time) |  |  |
| `requeridos[].dtFimVigencia` | string (date-time) |  |  |
| `excludentes` | array<objeto `ServicoAdicionalExcludenteResponse`> |  |  |
| `excludentes[].coExcludente` | string |  |  |
| `excludentes[].dtIniVigencia` | string (date-time) |  |  |
| `excludentes[].dtFimVigencia` | string (date-time) |  |  |
| `dtIniVigencia` | string (date-time) |  |  |
| `dtFimVigencia` | string (date-time) |  |  |
| `dtIniVigenciaTarifa` | string (date-time) |  |  |
| `dtFimVigenciaTarifa` | string (date-time) |  |  |

### `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

### `Link`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `rel` | string |  |  |
| `href` | string |  |  |

## 3. Códigos HTTP

| Código | Significado |
|---|---|
| `200` | Consulta realizada com sucesso (no lote: todos os itens com sucesso). |
| `206` | Lote processado parcialmente — os itens têm status diferentes; verifique o resultado item a item. |
| `400` | Parâmetros inválidos ou regra de negócio não atendida. Ver `MessageResponse.msgs`. |
| `401` | Token ausente, inválido ou expirado — gere/renove pela API Token. |
| `403` | Token sem permissão para esta API/contrato. |
| `500` | Erro interno dos Correios. |

## 4. JSON Schema completo (referências expandidas)

### `POST /v1/nacional`

Requisição:

```json
{
  "title": "LotePrecoNacionalParam",
  "type": "object",
  "properties": {
    "idLote": {
      "type": "string",
      "description": "Identificador do lote da consulta."
    },
    "parametrosProduto": {
      "type": "array",
      "items": {
        "title": "PrecoNacionalParam",
        "type": "object",
        "properties": {
          "coProduto": {
            "type": "string",
            "description": "Código do produto ou serviço",
            "example": "04162"
          },
          "nuRequisicao": {
            "type": "string",
            "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço."
          },
          "nuContrato": {
            "type": "string",
            "description": "Número do contrato."
          },
          "nuDR": {
            "type": "integer",
            "format": "int32",
            "description": "Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado."
          },
          "cepOrigem": {
            "type": "string",
            "description": "CEP de origem da postagem.",
            "example": "70902000"
          },
          "psObjeto": {
            "type": "string",
            "description": "Peso do objeto em gramas.",
            "example": "300"
          },
          "nuUnidade": {
            "type": "string",
            "description": "Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos."
          },
          "tpObjeto": {
            "type": "string",
            "description": "Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo."
          },
          "comprimento": {
            "type": "string",
            "description": "Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo."
          },
          "largura": {
            "type": "string",
            "description": "Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote."
          },
          "altura": {
            "type": "string",
            "description": "Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote"
          },
          "diametro": {
            "type": "string",
            "description": "Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo"
          },
          "psCubico": {
            "type": "string",
            "description": "Peso cúbico do objeto em gramas."
          },
          "servicosAdicionais": {
            "type": "array",
            "description": "Lista de serviços adicionais",
            "items": {
              "type": "string"
            }
          },
          "criterios": {
            "type": "array",
            "description": "Lista dos criterios de desconto funcional",
            "items": {
              "type": "string"
            }
          },
          "vlDeclarado": {
            "type": "string",
            "description": "Valor declarado associado ao serviço."
          },
          "dtEvento": {
            "type": "string",
            "description": "Data que será usado para o cálculo do preço. DD-MM-YYYY"
          },
          "coUnidadeOrigem": {
            "type": "string",
            "description": "Código MCMCU da unidade de origem para precificar conforme canal de postagem"
          },
          "dtArmazenagem": {
            "type": "string",
            "description": "Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY"
          },
          "vlRemessa": {
            "type": "string",
            "description": "Valor de remessa de valores monetários."
          },
          "cepDestino": {
            "type": "string",
            "description": "CEP de destino da postagem.",
            "example": "71930000"
          }
        },
        "required": [
          "coProduto"
        ]
      }
    }
  },
  "required": [
    "idLote"
  ]
}
```

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "PrecoProdutoResponse",
    "type": "object",
    "properties": {
      "coProduto": {
        "type": "string",
        "description": "Código do produto ou serviço consultado.",
        "example": "45209"
      },
      "noProduto": {
        "type": "string",
        "description": "Código do produto ou serviço consultado."
      },
      "pcBase": {
        "type": "string",
        "description": "Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade)"
      },
      "pcBaseGeral": {
        "type": "string",
        "description": "Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional )"
      },
      "peVariacao": {
        "type": "string",
        "description": "Percentual da variação do combo do cliente."
      },
      "pcReferencia": {
        "type": "string",
        "description": "Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao )"
      },
      "beneficios": {
        "type": "array",
        "description": "Lista de benefícios que serão aplicadas ao preço do produto",
        "items": {
          "title": "BeneficioResponse",
          "type": "object",
          "properties": {
            "codigo": {
              "type": "string",
              "description": "Código do tipo de benefício."
            },
            "tipoBeneficio": {
              "type": "string",
              "enum": [
                "DP",
                "DF",
                "BP",
                "BF",
                "BC"
              ]
            },
            "tipo": {
              "type": "string",
              "description": "Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação;"
            },
            "abrangencia": {
              "type": "string",
              "description": "Abrangência.O: Oferta; N: Negociação;",
              "enum": [
                "O",
                "N"
              ]
            },
            "coPacote": {
              "type": "string",
              "description": "Código do pacote de benefício."
            },
            "noPacote": {
              "type": "string",
              "description": "Nome do pacote de benefício."
            },
            "incondicional": {
              "type": "string",
              "description": "Incondicional de redução ou não do valor de base de cálculo (S ou N)."
            },
            "criterio": {
              "type": "array",
              "description": "Lista de critérios funcionais.",
              "items": {
                "title": "CriterioAtendidoResponse",
                "type": "object",
                "properties": {
                  "nuCriterio": {
                    "type": "string",
                    "description": "Número do critério"
                  },
                  "noCriterio": {
                    "type": "string",
                    "description": "Nome do critério"
                  },
                  "atendido": {
                    "type": "string",
                    "description": "Indica que o critério foi atendido [S ou N]"
                  },
                  "peNegociado": {
                    "type": "string",
                    "description": "Percentual negociado para o critério"
                  },
                  "peConcedido": {
                    "type": "string",
                    "description": "Percentual concedido para o critério"
                  }
                }
              }
            },
            "vlBaseCalculo": {
              "type": "string",
              "description": "Valor base para cálculo do imposto."
            },
            "percentual": {
              "type": "string",
              "description": "Percentual de benefício concedido."
            },
            "valor": {
              "type": "string",
              "description": "Valor do benefício concedido. ( valor = vlBaseCalculo * percentual )"
            }
          }
        }
      },
      "vlBaseCalculoImposto": {
        "type": "string",
        "description": "Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] ) "
      },
      "nuRequisicao": {
        "type": "string",
        "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta"
      },
      "inPesoCubico": {
        "type": "string",
        "description": "Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real"
      },
      "psCobrado": {
        "type": "string",
        "description": "Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico"
      },
      "servicoAdicional": {
        "type": "array",
        "description": "Preço dos serviços adicionais.",
        "items": {
          "title": "PrecoServicoAdicional",
          "type": "object",
          "properties": {
            "coServAdicional": {
              "type": "string",
              "description": "Código do serviço adicional"
            },
            "tpServAdicional": {
              "type": "string",
              "description": "Tipo do serviço adicional"
            },
            "pcServicoAdicional": {
              "type": "string",
              "description": "Preço do serviço adicional"
            }
          }
        }
      },
      "peAdValorem": {
        "type": "string",
        "description": "Percentual de advalorem.",
        "example": "0,1000"
      },
      "vlSeguroAutomatico": {
        "type": "string",
        "description": "Valor declarado do seguro automático",
        "example": "50,00"
      },
      "qtAdicional": {
        "type": "string",
        "description": "Quantidade de adicionais. Exemplo: kilos adicionais"
      },
      "pcFaixa": {
        "type": "string",
        "description": "Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase )"
      },
      "pcCadaAdicional": {
        "type": "string",
        "description": "Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase )"
      },
      "pcTotalAdicional": {
        "type": "string",
        "description": "Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional )"
      },
      "pcFaixaVariacao": {
        "type": "string",
        "description": "Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) )"
      },
      "pcCadaAdicionalVariacao": {
        "type": "string",
        "description": "Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) ) "
      },
      "pcTotalAdicionalVariacao": {
        "type": "string",
        "description": "Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional )"
      },
      "vlTotalDescVariacao": {
        "type": "string",
        "description": "Valor total de descontos da variação. Descontos referente ao combo do cliente",
        "example": "50,00"
      },
      "vlTotalBeneficios": {
        "type": "string",
        "description": "Valor total dos benefícios",
        "example": "50,00"
      },
      "pcProduto": {
        "type": "string",
        "description": "Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios )",
        "example": "10,00"
      },
      "pcTotalServicosAdicionais": {
        "type": "string",
        "description": "Valor total dos serviços adicionais que foi embutido no preço final",
        "example": "10,00"
      },
      "pcFinal": {
        "type": "string",
        "description": "Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais )"
      },
      "txErro": {
        "type": "string",
        "description": "Descricao do erro retornado"
      },
      "infoAdicional": {
        "type": "array",
        "description": "Lista de informações complementares da formação de preço.",
        "items": {
          "title": "InfoAdicionalResponse",
          "type": "object",
          "properties": {
            "tipo": {
              "type": "string",
              "description": "Código da informação complementar.",
              "enum": [
                "LOB_ERP",
                "LIMITE_MAX_TARIFACAO_ERP",
                "VALOR_PARAMETRO_KILO_ADICIONAL_ERP",
                "LOB_SGPB",
                "NU_PROPOSTA",
                "CO_MODELO",
                "CO_SIMILARIDADE",
                "IN_EXIGENCIA_CEP_DESTINO",
                "CO_AGRUPADOR",
                "TIPO_AGRUPADOR",
                "NU_CORREDOR_ORIGEM",
                "NU_CORREDOR_DESTINO",
                "NU_TABELA_FATOR",
                "NU_LINHA_FATOR",
                "NU_COLUNA_FATOR",
                "NO_COLUNA_FATOR",
                "CLASSIFICACAO_COLUNA",
                "NU_COMBO",
                "NU_VARIACAO",
                "VL_PRECO_BASE",
                "FATOR_FAIXA",
                "FATOR_CADA_ADICIONAL",
                "QT_EXCESSO",
                "NO_PRODUTO",
                "TP_TARIFACAO_ERP",
                "IN_CORREDOR_FAIXA_CEP_SGPB",
                "IN_PRECIFICACAO_SGPB",
                "VL_TAXA_EXTRA"
              ]
            },
            "valor": {
              "type": "string",
              "description": "Conteúdo da informação complementar."
            }
          }
        }
      },
      "nomeProduto": {
        "type": "string",
        "description": "Nome do produto. Apresentado apenas para serviço internacional"
      },
      "peIsencaoArmazenagem": {
        "type": "string",
        "description": "Percentual de isenção de pagamento para serviços com armazenagem."
      },
      "taxaExtra": {
        "type": "array",
        "description": "Taxas extras",
        "items": {
          "title": "TaxaExtraResponse",
          "type": "object",
          "properties": {
            "codigo": {
              "type": "string"
            },
            "tipo": {
              "type": "string"
            },
            "vlTaxa": {
              "type": "string"
            }
          }
        }
      }
    }
  }
}
```

### `POST /v1/internacional`

Requisição:

```json
{
  "title": "LotePrecoInternacionalParam",
  "type": "object",
  "properties": {
    "idLote": {
      "type": "string",
      "description": "Identificador do lote da consulta."
    },
    "parametrosProduto": {
      "type": "array",
      "items": {
        "title": "PrecoInternacionalParam",
        "type": "object",
        "properties": {
          "coProduto": {
            "type": "string",
            "description": "Código do produto ou serviço",
            "example": "04162"
          },
          "nuRequisicao": {
            "type": "string",
            "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço."
          },
          "nuContrato": {
            "type": "string",
            "description": "Número do contrato."
          },
          "nuDR": {
            "type": "integer",
            "format": "int32",
            "description": "Número da DR do contrato. Obrigatório caso 'nuContrato' seja informado."
          },
          "cepOrigem": {
            "type": "string",
            "description": "CEP de origem da postagem.",
            "example": "70902000"
          },
          "psObjeto": {
            "type": "string",
            "description": "Peso do objeto em gramas.",
            "example": "300"
          },
          "nuUnidade": {
            "type": "string",
            "description": "Número de unidades prestadas no atendimento. Número de páginas, palavras, minutos."
          },
          "tpObjeto": {
            "type": "string",
            "description": "Tipo do objeto da postagem: 1 - Envelope, 2 - Pacote; 3 - Rolo."
          },
          "comprimento": {
            "type": "string",
            "description": "Comprimento do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote ou 3-Rolo."
          },
          "largura": {
            "type": "string",
            "description": "Largura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote."
          },
          "altura": {
            "type": "string",
            "description": "Altura do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 2-Pacote"
          },
          "diametro": {
            "type": "string",
            "description": "Diâmetro do objeto, em centímetros. Obrigatório caso 'tpObjeto' seja 3-Rolo"
          },
          "psCubico": {
            "type": "string",
            "description": "Peso cúbico do objeto em gramas."
          },
          "servicosAdicionais": {
            "type": "array",
            "description": "Lista de serviços adicionais",
            "items": {
              "type": "string"
            }
          },
          "criterios": {
            "type": "array",
            "description": "Lista dos criterios de desconto funcional",
            "items": {
              "type": "string"
            }
          },
          "vlDeclarado": {
            "type": "string",
            "description": "Valor declarado associado ao serviço."
          },
          "dtEvento": {
            "type": "string",
            "description": "Data que será usado para o cálculo do preço. DD-MM-YYYY"
          },
          "coUnidadeOrigem": {
            "type": "string",
            "description": "Código MCMCU da unidade de origem para precificar conforme canal de postagem"
          },
          "dtArmazenagem": {
            "type": "string",
            "description": "Data de armazenagem, para os serviços que ganham isenção de preço, conforme a data de armazenagem. Formato DD-MM-YYYY"
          },
          "vlRemessa": {
            "type": "string",
            "description": "Valor de remessa de valores monetários."
          },
          "sgPaisDestino": {
            "type": "string",
            "description": "Sigla do país de destino.",
            "example": "US",
            "minLength": 1
          }
        },
        "required": [
          "coProduto",
          "sgPaisDestino"
        ]
      }
    }
  },
  "required": [
    "idLote"
  ]
}
```

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "PrecoProdutoResponse",
    "type": "object",
    "properties": {
      "coProduto": {
        "type": "string",
        "description": "Código do produto ou serviço consultado.",
        "example": "45209"
      },
      "noProduto": {
        "type": "string",
        "description": "Código do produto ou serviço consultado."
      },
      "pcBase": {
        "type": "string",
        "description": "Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade)"
      },
      "pcBaseGeral": {
        "type": "string",
        "description": "Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional )"
      },
      "peVariacao": {
        "type": "string",
        "description": "Percentual da variação do combo do cliente."
      },
      "pcReferencia": {
        "type": "string",
        "description": "Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao )"
      },
      "beneficios": {
        "type": "array",
        "description": "Lista de benefícios que serão aplicadas ao preço do produto",
        "items": {
          "title": "BeneficioResponse",
          "type": "object",
          "properties": {
            "codigo": {
              "type": "string",
              "description": "Código do tipo de benefício."
            },
            "tipoBeneficio": {
              "type": "string",
              "enum": [
                "DP",
                "DF",
                "BP",
                "BF",
                "BC"
              ]
            },
            "tipo": {
              "type": "string",
              "description": "Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação;"
            },
            "abrangencia": {
              "type": "string",
              "description": "Abrangência.O: Oferta; N: Negociação;",
              "enum": [
                "O",
                "N"
              ]
            },
            "coPacote": {
              "type": "string",
              "description": "Código do pacote de benefício."
            },
            "noPacote": {
              "type": "string",
              "description": "Nome do pacote de benefício."
            },
            "incondicional": {
              "type": "string",
              "description": "Incondicional de redução ou não do valor de base de cálculo (S ou N)."
            },
            "criterio": {
              "type": "array",
              "description": "Lista de critérios funcionais.",
              "items": {
                "title": "CriterioAtendidoResponse",
                "type": "object",
                "properties": {
                  "nuCriterio": {
                    "type": "string",
                    "description": "Número do critério"
                  },
                  "noCriterio": {
                    "type": "string",
                    "description": "Nome do critério"
                  },
                  "atendido": {
                    "type": "string",
                    "description": "Indica que o critério foi atendido [S ou N]"
                  },
                  "peNegociado": {
                    "type": "string",
                    "description": "Percentual negociado para o critério"
                  },
                  "peConcedido": {
                    "type": "string",
                    "description": "Percentual concedido para o critério"
                  }
                }
              }
            },
            "vlBaseCalculo": {
              "type": "string",
              "description": "Valor base para cálculo do imposto."
            },
            "percentual": {
              "type": "string",
              "description": "Percentual de benefício concedido."
            },
            "valor": {
              "type": "string",
              "description": "Valor do benefício concedido. ( valor = vlBaseCalculo * percentual )"
            }
          }
        }
      },
      "vlBaseCalculoImposto": {
        "type": "string",
        "description": "Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] ) "
      },
      "nuRequisicao": {
        "type": "string",
        "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta"
      },
      "inPesoCubico": {
        "type": "string",
        "description": "Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real"
      },
      "psCobrado": {
        "type": "string",
        "description": "Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico"
      },
      "servicoAdicional": {
        "type": "array",
        "description": "Preço dos serviços adicionais.",
        "items": {
          "title": "PrecoServicoAdicional",
          "type": "object",
          "properties": {
            "coServAdicional": {
              "type": "string",
              "description": "Código do serviço adicional"
            },
            "tpServAdicional": {
              "type": "string",
              "description": "Tipo do serviço adicional"
            },
            "pcServicoAdicional": {
              "type": "string",
              "description": "Preço do serviço adicional"
            }
          }
        }
      },
      "peAdValorem": {
        "type": "string",
        "description": "Percentual de advalorem.",
        "example": "0,1000"
      },
      "vlSeguroAutomatico": {
        "type": "string",
        "description": "Valor declarado do seguro automático",
        "example": "50,00"
      },
      "qtAdicional": {
        "type": "string",
        "description": "Quantidade de adicionais. Exemplo: kilos adicionais"
      },
      "pcFaixa": {
        "type": "string",
        "description": "Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase )"
      },
      "pcCadaAdicional": {
        "type": "string",
        "description": "Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase )"
      },
      "pcTotalAdicional": {
        "type": "string",
        "description": "Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional )"
      },
      "pcFaixaVariacao": {
        "type": "string",
        "description": "Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) )"
      },
      "pcCadaAdicionalVariacao": {
        "type": "string",
        "description": "Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) ) "
      },
      "pcTotalAdicionalVariacao": {
        "type": "string",
        "description": "Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional )"
      },
      "vlTotalDescVariacao": {
        "type": "string",
        "description": "Valor total de descontos da variação. Descontos referente ao combo do cliente",
        "example": "50,00"
      },
      "vlTotalBeneficios": {
        "type": "string",
        "description": "Valor total dos benefícios",
        "example": "50,00"
      },
      "pcProduto": {
        "type": "string",
        "description": "Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios )",
        "example": "10,00"
      },
      "pcTotalServicosAdicionais": {
        "type": "string",
        "description": "Valor total dos serviços adicionais que foi embutido no preço final",
        "example": "10,00"
      },
      "pcFinal": {
        "type": "string",
        "description": "Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais )"
      },
      "txErro": {
        "type": "string",
        "description": "Descricao do erro retornado"
      },
      "infoAdicional": {
        "type": "array",
        "description": "Lista de informações complementares da formação de preço.",
        "items": {
          "title": "InfoAdicionalResponse",
          "type": "object",
          "properties": {
            "tipo": {
              "type": "string",
              "description": "Código da informação complementar.",
              "enum": [
                "LOB_ERP",
                "LIMITE_MAX_TARIFACAO_ERP",
                "VALOR_PARAMETRO_KILO_ADICIONAL_ERP",
                "LOB_SGPB",
                "NU_PROPOSTA",
                "CO_MODELO",
                "CO_SIMILARIDADE",
                "IN_EXIGENCIA_CEP_DESTINO",
                "CO_AGRUPADOR",
                "TIPO_AGRUPADOR",
                "NU_CORREDOR_ORIGEM",
                "NU_CORREDOR_DESTINO",
                "NU_TABELA_FATOR",
                "NU_LINHA_FATOR",
                "NU_COLUNA_FATOR",
                "NO_COLUNA_FATOR",
                "CLASSIFICACAO_COLUNA",
                "NU_COMBO",
                "NU_VARIACAO",
                "VL_PRECO_BASE",
                "FATOR_FAIXA",
                "FATOR_CADA_ADICIONAL",
                "QT_EXCESSO",
                "NO_PRODUTO",
                "TP_TARIFACAO_ERP",
                "IN_CORREDOR_FAIXA_CEP_SGPB",
                "IN_PRECIFICACAO_SGPB",
                "VL_TAXA_EXTRA"
              ]
            },
            "valor": {
              "type": "string",
              "description": "Conteúdo da informação complementar."
            }
          }
        }
      },
      "nomeProduto": {
        "type": "string",
        "description": "Nome do produto. Apresentado apenas para serviço internacional"
      },
      "peIsencaoArmazenagem": {
        "type": "string",
        "description": "Percentual de isenção de pagamento para serviços com armazenagem."
      },
      "taxaExtra": {
        "type": "array",
        "description": "Taxas extras",
        "items": {
          "title": "TaxaExtraResponse",
          "type": "object",
          "properties": {
            "codigo": {
              "type": "string"
            },
            "tipo": {
              "type": "string"
            },
            "vlTaxa": {
              "type": "string"
            }
          }
        }
      }
    }
  }
}
```

### `GET /v1/servicos-adicionais/{coProduto}`

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "ServicoAdicionalResponse",
    "type": "object",
    "properties": {
      "codigo": {
        "type": "string"
      },
      "nome": {
        "type": "string"
      },
      "sigla": {
        "type": "string"
      },
      "sgPais": {
        "type": "string"
      },
      "preco": {
        "type": "number"
      },
      "tipo": {
        "type": "string"
      },
      "coIndicador": {
        "type": "string"
      },
      "noIndicador": {
        "type": "string"
      },
      "sgIndicador": {
        "type": "string"
      },
      "vlMinDeclarado": {
        "type": "number"
      },
      "vlMaxDeclarado": {
        "type": "number"
      },
      "mensagem": {
        "type": "string"
      },
      "requeridos": {
        "type": "array",
        "items": {
          "title": "ServicoAdicionalRequeridoResponse",
          "type": "object",
          "properties": {
            "coRequerido": {
              "type": "string"
            },
            "coSubstituto": {
              "type": "string"
            },
            "dtIniVigencia": {
              "type": "string",
              "format": "date-time"
            },
            "dtFimVigencia": {
              "type": "string",
              "format": "date-time"
            }
          }
        }
      },
      "excludentes": {
        "type": "array",
        "items": {
          "title": "ServicoAdicionalExcludenteResponse",
          "type": "object",
          "properties": {
            "coExcludente": {
              "type": "string"
            },
            "dtIniVigencia": {
              "type": "string",
              "format": "date-time"
            },
            "dtFimVigencia": {
              "type": "string",
              "format": "date-time"
            }
          }
        }
      },
      "dtIniVigencia": {
        "type": "string",
        "format": "date-time"
      },
      "dtFimVigencia": {
        "type": "string",
        "format": "date-time"
      },
      "dtIniVigenciaTarifa": {
        "type": "string",
        "format": "date-time"
      },
      "dtFimVigenciaTarifa": {
        "type": "string",
        "format": "date-time"
      }
    }
  }
}
```

### `GET /v1/nacional/{coProduto}`

Resposta 200:

```json
{
  "title": "PrecoProdutoResponse",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço consultado.",
      "example": "45209"
    },
    "noProduto": {
      "type": "string",
      "description": "Código do produto ou serviço consultado."
    },
    "pcBase": {
      "type": "string",
      "description": "Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade)"
    },
    "pcBaseGeral": {
      "type": "string",
      "description": "Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional )"
    },
    "peVariacao": {
      "type": "string",
      "description": "Percentual da variação do combo do cliente."
    },
    "pcReferencia": {
      "type": "string",
      "description": "Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao )"
    },
    "beneficios": {
      "type": "array",
      "description": "Lista de benefícios que serão aplicadas ao preço do produto",
      "items": {
        "title": "BeneficioResponse",
        "type": "object",
        "properties": {
          "codigo": {
            "type": "string",
            "description": "Código do tipo de benefício."
          },
          "tipoBeneficio": {
            "type": "string",
            "enum": [
              "DP",
              "DF",
              "BP",
              "BF",
              "BC"
            ]
          },
          "tipo": {
            "type": "string",
            "description": "Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação;"
          },
          "abrangencia": {
            "type": "string",
            "description": "Abrangência.O: Oferta; N: Negociação;",
            "enum": [
              "O",
              "N"
            ]
          },
          "coPacote": {
            "type": "string",
            "description": "Código do pacote de benefício."
          },
          "noPacote": {
            "type": "string",
            "description": "Nome do pacote de benefício."
          },
          "incondicional": {
            "type": "string",
            "description": "Incondicional de redução ou não do valor de base de cálculo (S ou N)."
          },
          "criterio": {
            "type": "array",
            "description": "Lista de critérios funcionais.",
            "items": {
              "title": "CriterioAtendidoResponse",
              "type": "object",
              "properties": {
                "nuCriterio": {
                  "type": "string",
                  "description": "Número do critério"
                },
                "noCriterio": {
                  "type": "string",
                  "description": "Nome do critério"
                },
                "atendido": {
                  "type": "string",
                  "description": "Indica que o critério foi atendido [S ou N]"
                },
                "peNegociado": {
                  "type": "string",
                  "description": "Percentual negociado para o critério"
                },
                "peConcedido": {
                  "type": "string",
                  "description": "Percentual concedido para o critério"
                }
              }
            }
          },
          "vlBaseCalculo": {
            "type": "string",
            "description": "Valor base para cálculo do imposto."
          },
          "percentual": {
            "type": "string",
            "description": "Percentual de benefício concedido."
          },
          "valor": {
            "type": "string",
            "description": "Valor do benefício concedido. ( valor = vlBaseCalculo * percentual )"
          }
        }
      }
    },
    "vlBaseCalculoImposto": {
      "type": "string",
      "description": "Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] ) "
    },
    "nuRequisicao": {
      "type": "string",
      "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta"
    },
    "inPesoCubico": {
      "type": "string",
      "description": "Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real"
    },
    "psCobrado": {
      "type": "string",
      "description": "Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico"
    },
    "servicoAdicional": {
      "type": "array",
      "description": "Preço dos serviços adicionais.",
      "items": {
        "title": "PrecoServicoAdicional",
        "type": "object",
        "properties": {
          "coServAdicional": {
            "type": "string",
            "description": "Código do serviço adicional"
          },
          "tpServAdicional": {
            "type": "string",
            "description": "Tipo do serviço adicional"
          },
          "pcServicoAdicional": {
            "type": "string",
            "description": "Preço do serviço adicional"
          }
        }
      }
    },
    "peAdValorem": {
      "type": "string",
      "description": "Percentual de advalorem.",
      "example": "0,1000"
    },
    "vlSeguroAutomatico": {
      "type": "string",
      "description": "Valor declarado do seguro automático",
      "example": "50,00"
    },
    "qtAdicional": {
      "type": "string",
      "description": "Quantidade de adicionais. Exemplo: kilos adicionais"
    },
    "pcFaixa": {
      "type": "string",
      "description": "Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase )"
    },
    "pcCadaAdicional": {
      "type": "string",
      "description": "Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase )"
    },
    "pcTotalAdicional": {
      "type": "string",
      "description": "Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional )"
    },
    "pcFaixaVariacao": {
      "type": "string",
      "description": "Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) )"
    },
    "pcCadaAdicionalVariacao": {
      "type": "string",
      "description": "Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) ) "
    },
    "pcTotalAdicionalVariacao": {
      "type": "string",
      "description": "Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional )"
    },
    "vlTotalDescVariacao": {
      "type": "string",
      "description": "Valor total de descontos da variação. Descontos referente ao combo do cliente",
      "example": "50,00"
    },
    "vlTotalBeneficios": {
      "type": "string",
      "description": "Valor total dos benefícios",
      "example": "50,00"
    },
    "pcProduto": {
      "type": "string",
      "description": "Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios )",
      "example": "10,00"
    },
    "pcTotalServicosAdicionais": {
      "type": "string",
      "description": "Valor total dos serviços adicionais que foi embutido no preço final",
      "example": "10,00"
    },
    "pcFinal": {
      "type": "string",
      "description": "Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais )"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    },
    "infoAdicional": {
      "type": "array",
      "description": "Lista de informações complementares da formação de preço.",
      "items": {
        "title": "InfoAdicionalResponse",
        "type": "object",
        "properties": {
          "tipo": {
            "type": "string",
            "description": "Código da informação complementar.",
            "enum": [
              "LOB_ERP",
              "LIMITE_MAX_TARIFACAO_ERP",
              "VALOR_PARAMETRO_KILO_ADICIONAL_ERP",
              "LOB_SGPB",
              "NU_PROPOSTA",
              "CO_MODELO",
              "CO_SIMILARIDADE",
              "IN_EXIGENCIA_CEP_DESTINO",
              "CO_AGRUPADOR",
              "TIPO_AGRUPADOR",
              "NU_CORREDOR_ORIGEM",
              "NU_CORREDOR_DESTINO",
              "NU_TABELA_FATOR",
              "NU_LINHA_FATOR",
              "NU_COLUNA_FATOR",
              "NO_COLUNA_FATOR",
              "CLASSIFICACAO_COLUNA",
              "NU_COMBO",
              "NU_VARIACAO",
              "VL_PRECO_BASE",
              "FATOR_FAIXA",
              "FATOR_CADA_ADICIONAL",
              "QT_EXCESSO",
              "NO_PRODUTO",
              "TP_TARIFACAO_ERP",
              "IN_CORREDOR_FAIXA_CEP_SGPB",
              "IN_PRECIFICACAO_SGPB",
              "VL_TAXA_EXTRA"
            ]
          },
          "valor": {
            "type": "string",
            "description": "Conteúdo da informação complementar."
          }
        }
      }
    },
    "nomeProduto": {
      "type": "string",
      "description": "Nome do produto. Apresentado apenas para serviço internacional"
    },
    "peIsencaoArmazenagem": {
      "type": "string",
      "description": "Percentual de isenção de pagamento para serviços com armazenagem."
    },
    "taxaExtra": {
      "type": "array",
      "description": "Taxas extras",
      "items": {
        "title": "TaxaExtraResponse",
        "type": "object",
        "properties": {
          "codigo": {
            "type": "string"
          },
          "tipo": {
            "type": "string"
          },
          "vlTaxa": {
            "type": "string"
          }
        }
      }
    }
  }
}
```

### `GET /v1/internacional/{coProduto}`

Resposta 200:

```json
{
  "title": "PrecoProdutoResponse",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço consultado.",
      "example": "45209"
    },
    "noProduto": {
      "type": "string",
      "description": "Código do produto ou serviço consultado."
    },
    "pcBase": {
      "type": "string",
      "description": "Preço base é o valor da proposta de preco, um valor único ( não leva em conta outras variaveis como quantidade)"
    },
    "pcBaseGeral": {
      "type": "string",
      "description": "Preço base geral é preço da faixa acrescido do preço dos adicionais. O Adicional representa kilos adicionais, paginas adicionais, etc. ( pcBaseGeral = pcFaixa + pcTotalAdicional )"
    },
    "peVariacao": {
      "type": "string",
      "description": "Percentual da variação do combo do cliente."
    },
    "pcReferencia": {
      "type": "string",
      "description": "Preço de referência é o preço base geral descontado o valor de desconto da variação(combo). ( pcReferencia = pcBaseGeral - vlDescVariacao )"
    },
    "beneficios": {
      "type": "array",
      "description": "Lista de benefícios que serão aplicadas ao preço do produto",
      "items": {
        "title": "BeneficioResponse",
        "type": "object",
        "properties": {
          "codigo": {
            "type": "string",
            "description": "Código do tipo de benefício."
          },
          "tipoBeneficio": {
            "type": "string",
            "enum": [
              "DP",
              "DF",
              "BP",
              "BF",
              "BC"
            ]
          },
          "tipo": {
            "type": "string",
            "description": "Tipo do benefício. Desconto Promocional - Oferta; Desconto Promocional - Negociação; Desconto Funcional - Negociação;"
          },
          "abrangencia": {
            "type": "string",
            "description": "Abrangência.O: Oferta; N: Negociação;",
            "enum": [
              "O",
              "N"
            ]
          },
          "coPacote": {
            "type": "string",
            "description": "Código do pacote de benefício."
          },
          "noPacote": {
            "type": "string",
            "description": "Nome do pacote de benefício."
          },
          "incondicional": {
            "type": "string",
            "description": "Incondicional de redução ou não do valor de base de cálculo (S ou N)."
          },
          "criterio": {
            "type": "array",
            "description": "Lista de critérios funcionais.",
            "items": {
              "title": "CriterioAtendidoResponse",
              "type": "object",
              "properties": {
                "nuCriterio": {
                  "type": "string",
                  "description": "Número do critério"
                },
                "noCriterio": {
                  "type": "string",
                  "description": "Nome do critério"
                },
                "atendido": {
                  "type": "string",
                  "description": "Indica que o critério foi atendido [S ou N]"
                },
                "peNegociado": {
                  "type": "string",
                  "description": "Percentual negociado para o critério"
                },
                "peConcedido": {
                  "type": "string",
                  "description": "Percentual concedido para o critério"
                }
              }
            }
          },
          "vlBaseCalculo": {
            "type": "string",
            "description": "Valor base para cálculo do imposto."
          },
          "percentual": {
            "type": "string",
            "description": "Percentual de benefício concedido."
          },
          "valor": {
            "type": "string",
            "description": "Valor do benefício concedido. ( valor = vlBaseCalculo * percentual )"
          }
        }
      }
    },
    "vlBaseCalculoImposto": {
      "type": "string",
      "description": "Valor da base de cálculo é o preço de referência acrescido do valor total dos serviços adicionais, deduzindo os valores dos benefícios que são incondicionais. ( vlBaseCalculo = pcReferencia + pcTotalServicosAdicionais - [somatório dos valores dos benefícios que são incondicionais] ) "
    },
    "nuRequisicao": {
      "type": "string",
      "description": "Número da requisição. Número informado e controlado pelo requisitante do serviço. Exemplo: Número de etiqueta"
    },
    "inPesoCubico": {
      "type": "string",
      "description": "Indicador de cobrança pelo peso cúbico. Se S: Indica que foi cobrado pelo pelo cubico. N: Indica que foi cobrado pelo peso real"
    },
    "psCobrado": {
      "type": "string",
      "description": "Peso cobrado/tarifado em gramas. Pode ser superior ao peso real, caso seja cobrado pelo peso cúbico"
    },
    "servicoAdicional": {
      "type": "array",
      "description": "Preço dos serviços adicionais.",
      "items": {
        "title": "PrecoServicoAdicional",
        "type": "object",
        "properties": {
          "coServAdicional": {
            "type": "string",
            "description": "Código do serviço adicional"
          },
          "tpServAdicional": {
            "type": "string",
            "description": "Tipo do serviço adicional"
          },
          "pcServicoAdicional": {
            "type": "string",
            "description": "Preço do serviço adicional"
          }
        }
      }
    },
    "peAdValorem": {
      "type": "string",
      "description": "Percentual de advalorem.",
      "example": "0,1000"
    },
    "vlSeguroAutomatico": {
      "type": "string",
      "description": "Valor declarado do seguro automático",
      "example": "50,00"
    },
    "qtAdicional": {
      "type": "string",
      "description": "Quantidade de adicionais. Exemplo: kilos adicionais"
    },
    "pcFaixa": {
      "type": "string",
      "description": "Preço da faixa da tabela base é o fator da faixa de peso multiplicado pelo preço base da tabela. ( pcFaixa = fator(faixa) * pcBase )"
    },
    "pcCadaAdicional": {
      "type": "string",
      "description": "Preço de cada adicional é o fator da faixa do adicional multiplicado pelo preço base da tabela. ( pcCadaAdicional = fator(adicional) * pcBase )"
    },
    "pcTotalAdicional": {
      "type": "string",
      "description": "Preço total dos adicionais é o preço de cada adicional multiplicado pela quantidade de adicionais. ( pcTotalAdicional = pcCadaAdicional * qtAdicional )"
    },
    "pcFaixaVariacao": {
      "type": "string",
      "description": "Preço da faixa da variação é o preço da faixa da tabela base menos o percentual da variação. ( pcFaixaVariacao = pcFaixa - peVariacao(%) )"
    },
    "pcCadaAdicionalVariacao": {
      "type": "string",
      "description": "Preço de cada adicional da variação é o preço de cada adicional menos o percentual da variação. (pcCadaAdicionalVariacao = pcCadaAdicional - peVariacao(%) ) "
    },
    "pcTotalAdicionalVariacao": {
      "type": "string",
      "description": "Preço total dos adicionais da variação é o preço de cada adicional da variação multiplicado pela quantidade de adicionais. ( pcTotalAdicionalVariacao = pcCadaAdicionalVariacao * qtAdicional )"
    },
    "vlTotalDescVariacao": {
      "type": "string",
      "description": "Valor total de descontos da variação. Descontos referente ao combo do cliente",
      "example": "50,00"
    },
    "vlTotalBeneficios": {
      "type": "string",
      "description": "Valor total dos benefícios",
      "example": "50,00"
    },
    "pcProduto": {
      "type": "string",
      "description": "Preço do produto é o preço de referência deduzindo o valor dos benefícios. ( pcProduto = pcReferencia - vlTotalBeneficios )",
      "example": "10,00"
    },
    "pcTotalServicosAdicionais": {
      "type": "string",
      "description": "Valor total dos serviços adicionais que foi embutido no preço final",
      "example": "10,00"
    },
    "pcFinal": {
      "type": "string",
      "description": "Preço final é o preço do produto somado com o preço total do serviços adicionais. ( pcFinal = pcProduto + pcTotalServicosAdicionais )"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    },
    "infoAdicional": {
      "type": "array",
      "description": "Lista de informações complementares da formação de preço.",
      "items": {
        "title": "InfoAdicionalResponse",
        "type": "object",
        "properties": {
          "tipo": {
            "type": "string",
            "description": "Código da informação complementar.",
            "enum": [
              "LOB_ERP",
              "LIMITE_MAX_TARIFACAO_ERP",
              "VALOR_PARAMETRO_KILO_ADICIONAL_ERP",
              "LOB_SGPB",
              "NU_PROPOSTA",
              "CO_MODELO",
              "CO_SIMILARIDADE",
              "IN_EXIGENCIA_CEP_DESTINO",
              "CO_AGRUPADOR",
              "TIPO_AGRUPADOR",
              "NU_CORREDOR_ORIGEM",
              "NU_CORREDOR_DESTINO",
              "NU_TABELA_FATOR",
              "NU_LINHA_FATOR",
              "NU_COLUNA_FATOR",
              "NO_COLUNA_FATOR",
              "CLASSIFICACAO_COLUNA",
              "NU_COMBO",
              "NU_VARIACAO",
              "VL_PRECO_BASE",
              "FATOR_FAIXA",
              "FATOR_CADA_ADICIONAL",
              "QT_EXCESSO",
              "NO_PRODUTO",
              "TP_TARIFACAO_ERP",
              "IN_CORREDOR_FAIXA_CEP_SGPB",
              "IN_PRECIFICACAO_SGPB",
              "VL_TAXA_EXTRA"
            ]
          },
          "valor": {
            "type": "string",
            "description": "Conteúdo da informação complementar."
          }
        }
      }
    },
    "nomeProduto": {
      "type": "string",
      "description": "Nome do produto. Apresentado apenas para serviço internacional"
    },
    "peIsencaoArmazenagem": {
      "type": "string",
      "description": "Percentual de isenção de pagamento para serviços com armazenagem."
    },
    "taxaExtra": {
      "type": "array",
      "description": "Taxas extras",
      "items": {
        "title": "TaxaExtraResponse",
        "type": "object",
        "properties": {
          "codigo": {
            "type": "string"
          },
          "tipo": {
            "type": "string"
          },
          "vlTaxa": {
            "type": "string"
          }
        }
      }
    }
  }
}
```

## 5. Orientações de implementação

1. **Token:** obtenha o Bearer pela API Token e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita a chamada.
2. **Contrato:** para obter o preço do seu contrato, informe `nuContrato` e `nuDR` (a DR é obrigatória quando o contrato é informado). Sem contrato, o valor retornado pode não refletir o preço contratado.
3. **Qual endpoint usar:** para um serviço, `GET /v1/nacional/{coProduto}`; para comparar vários serviços (ex.: PAC × SEDEX) ou várias cotações de uma vez, `POST /v1/nacional` com `idLote` e a lista `parametrosProduto` — menos chamadas.
4. **Lote e HTTP 206:** no `POST`, o status representa o resultado consolidado. Com `206`, alguns itens falharam: trate cada item da resposta individualmente (verifique mensagens de erro por item).
5. **Dimensões conforme o formato (`tpObjeto`):** `1` Envelope; `2` Pacote → `comprimento`, `largura` e `altura` obrigatórios; `3` Rolo → `comprimento` e `diametro` obrigatórios. Peso (`psObjeto`) em gramas; medidas em centímetros.
6. **Serviços adicionais:** consulte antes `GET /v1/servicos-adicionais/{coProduto}` para saber quais adicionais o serviço aceita (e quais são excludentes/requeridos) e depois envie os códigos em `servicosAdicionais`. Valor declarado vai em `vlDeclarado`.
   - ✔ **Observado em homologação — serviços reversos:** a consulta de preço do **SEDEX REVERSO (03247)** sem adicionais retornou `ERP-049: Servico adicional obrigatorio nao informado (016).` Com `servicosAdicionais=016` a consulta funcionou. `GET /v1/servicos-adicionais/{coProduto}` confirmou:

     | Serviço | Adicional | `nome` / `sigla` | `coIndicador` / `sgIndicador` / `noIndicador` | `preco` | Vigência |
     |---|---|---|---|---|---|
     | 03247 SEDEX REVERSO | `016` | LOGISTICA REV SIMULTANEA VOLTA / `LRSV` | `01` / `O` / **Obrigatório** | 0.00 | 28/08/2026 a 31/12/2040 |
     | 03301 PAC REVERSO | `016` | LOGISTICA REV SIMULTANEA VOLTA / `LRSV` | `03` / `P` / **Opcional** | 0.00 | 31/08/2026 a 31/12/2040 |

     Envio: GET `&servicosAdicionais=016`; POST `"servicosAdicionais": ["016"]`. Na Pré-postagem, o código 016 aparece ligado ao campo `objetoIdaSimultanea` ("código do objeto de ida simultânea para o serviço adicional 016").
   - ✔ **Diferença entre APIs:** a exigência do 016 vale para a **API Preço**. Na **Pré-postagem**, o SEDEX REVERSO (03247) foi criado com sucesso **sem** o adicional 016 em `listaServicoAdicional` (ver arquivo 06). Não replique automaticamente na pré-postagem os adicionais exigidos na cotação.
   - **Como descobrir adicionais obrigatórios:** em `GET /v1/servicos-adicionais/{coProduto}`, o campo `noIndicador`/`sgIndicador` indica a exigência — `Obrigatório` (`O`, `coIndicador` `01`) deve ser sempre enviado; `Opcional` (`P`, `coIndicador` `03`) fica a critério do cliente. Os indicadores e a obrigatoriedade podem mudar com a vigência (`dtIniVigencia`/`dtFimVigencia`) — consulte periodicamente em vez de fixar no código. Outros valores de `coIndicador` não foram observados.
   - Exemplo de item observado (03247):

     ```json
     {
       "codigo": "016",
       "nome": "LOGISTICA REV SIMULTANEA VOLTA",
       "sigla": "LRSV",
       "tipo": "O",
       "coIndicador": "01",
       "noIndicador": "Obrigatório",
       "sgIndicador": "O",
       "preco": 0.00,
       "sgPais": "BR",
       "dtIniVigencia": "2026-08-28T00:00:00",
       "dtFimVigencia": "2040-12-31T00:00:00"
     }
     ```
   - Erros do tipo `ERP-xxx` indicam regra de negócio da precificação; o código entre parênteses é o adicional que falta.
7. **Formato de datas — atenção:** em `/v1/nacional` e `/v1/internacional`, `dtEvento` usa `DD-MM-YYYY`; em `/v1/servicos-adicionais`, a especificação indica `YYYY-MM-DD`. Omitindo `dtEvento`, a data atual é o padrão esperado.
8. **Valores numéricos como texto:** vários campos numéricos (peso, medidas, valor declarado) são `string` na especificação — envie-os como texto (ex.: `"300"`).
9. **Integração com as outras APIs:** o `coProduto` é o mesmo código de serviço usado na API Prazo (`coProduto`) e na Pré-postagem (`codigoServico`); obtenha a lista em `correios-02-meucontrato-servicos-cartao-api.md`. Consultar Preço e Prazo com os mesmos parâmetros permite montar a cotação completa para o cliente.
10. **Ambientes:** mantenha a URL base configurável (homologação × produção). A versão publicada em produção pode diferir da de homologação.
