# Correios — API Prazo

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prazo/v3/api-docs`), versão **4.0.2** (homologação), em 25/09/2026. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 05 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-04-preco-api.md` · Próxima: `correios-06-prepostagem-criar-api.md`

## 0. Resumo rápido

- **Finalidade:** consultar prazos de entrega (nacional e internacional), prazo de coleta, prazos programados por lote e calcular a data prevista de entrega.
- **Autenticação:** `Authorization: Bearer <token>` — token gerado pela **API Token** (normalmente em `/v1/autentica/cartaopostagem`). Ver documento `correios-01-token-api.md`.
- **Formato:** JSON (`Content-Type: application/json` nas chamadas POST).

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/prazo` |
| Homologação | `https://apihom.correios.com.br/prazo` |

| Método | Caminho | Grupo | Resumo |
|---|---|---|---|
| GET | `/v1/nacional/prazobaselote` | Prazo base lote | Consulta prazos programados (base lote) |
| DELETE | `/v1/nacional/prazobaselote/cache` | Prazo base lote | Limpa o cache de prazos programados |
| GET | `/v1/coleta/{cep}` | Coleta | Consulta prazo de uma coleta. |
| POST | `/v1/nacional` | Nacional | Consulta prazo de uma lista de produtos |
| GET | `/v1/nacional/{coProduto}` | Nacional | Consulta prazo de um produto/serviço. |
| GET | `/v1/data-prevista` | Data prevista | Calcula a data prevista de entrega baseada no município, data de postagem e prazo em dias. |
| POST | `/v1/internacional` | Internacional | Consulta prazo de uma lista de produtos ⚠️ **descontinuado** |
| GET | `/v2/internacional/importacao/{coProduto}` | Internacional | Consulta prazo de importação de um produto/serviço. |
| GET | `/v2/internacional/exportacao/{coProduto}` | Internacional | Consulta prazo internacional de exportação de um produto/serviço. |
| GET | `/v1/internacional/{coProduto}` | Internacional | Consulta prazo de um produto/serviço. ⚠️ **descontinuado** |

## 1. Endpoints

### Prazo base lote

Consulta de prazos programados (base lote) armazenados na tabela PRAZO_PROGRAMADO.

#### 1.1 `GET /v1/nacional/prazobaselote`

**Consulta prazos programados (base lote)**

Consulta os prazos programados com filtros dinâmicos (Specification).
Informe exatamente um parâmetro de origem (código IBGE, CEP ou município)
e exatamente um de destino (código IBGE, CEP, município ou UF).
O resultado é agrupado por IBGE, município e nível de troca do destino
e armazenado em cache Redis com TTL deslizante de 24 horas.

- URL (produção): `https://api.correios.com.br/prazo/v1/nacional/prazobaselote`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/nacional/prazobaselote`
- Header: `Authorization: Bearer <token>`
- operationId: `consultarPrazoBaseLote`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `codIbgeOrigem` | query | string |  | Código IBGE do município de origem. — Ex.: `5300108` |
| `codIbgeDestino` | query | string |  | Código IBGE do município de destino. — Ex.: `3550308` |
| `cepOrigem` | query | string |  | CEP de origem da postagem. — Ex.: `70040902` |
| `cepDestino` | query | string |  | CEP de destino da postagem. — Ex.: `01001000` |
| `municipioOrigem` | query | string |  | Nome do município de origem. — Ex.: `Brasília` |
| `municipioDestino` | query | string |  | Nome do município de destino. — Ex.: `São Paulo` |
| `ufDestino` | query | string |  | UF de destino. — Ex.: `SP` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v1/nacional/prazobaselote?codIbgeOrigem=5300108&codIbgeDestino=3550308&cepOrigem=70040902&cepDestino=01001000&municipioOrigem=Brasília&municipioDestino=São Paulo&ufDestino=SP
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Resultado agrupado por destino | `PrazoProgramadoLote` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `origem` | string |  | Valor do filtro de origem informado (IBGE, CEP, município ou UF). — Ex.: `1100015` |
| `faixaCepOrigem` | string |  | Faixa de CEP de origem (menor inicial e maior final do resultado). — Ex.: `01001001 - 05999999` |
| `destinos` | array<objeto `PrazoProgramadoDestino`> |  | Destinos agrupados por IBGE, município e nível de troca. |
| `destinos[].codIbgeDestino` | string |  | Código IBGE do município de destino. — Ex.: `3550308` |
| `destinos[].municipioDestino` | string |  | Nome do município de destino com UF (cidade/UF). — Ex.: `SAO PAULO/SP` |
| `destinos[].nivelTroca` | string |  | Nível de troca. — Ex.: `I4` |
| `destinos[].prazosEntrega` | array<objeto `PrazoProgramadoPrazoEntrega`> |  | Prazos de entrega por faixa de CEP do destino. |
| `destinos[].prazosEntrega[].cepInicioDestino` | string |  | CEP inicial do destino. — Ex.: `01001001` |
| `destinos[].prazosEntrega[].cepFinalDestino` | string |  | CEP final do destino. — Ex.: `01002020` |
| `destinos[].prazosEntrega[].prazoPac` | integer (int32) |  | Prazo PAC em dias. — Ex.: `5` |
| `destinos[].prazosEntrega[].prazoSedex` | integer (int32) |  | Prazo Sedex em dias. — Ex.: `1` |

Exemplo de resposta 200:

```json
{
  "origem": "1100015",
  "faixaCepOrigem": "01001001 - 05999999",
  "destinos": [
    {
      "codIbgeDestino": "3550308",
      "municipioDestino": "SAO PAULO/SP",
      "nivelTroca": "I4",
      "prazosEntrega": [
        {
          "cepInicioDestino": "01001001",
          "cepFinalDestino": "01002020",
          "prazoPac": 5,
          "prazoSedex": 1
        }
      ]
    }
  ]
}
```

#### 1.2 `DELETE /v1/nacional/prazobaselote/cache`

**Limpa o cache de prazos programados**

Remove todas as entradas do cache Redis de prazo programado.
Deve ser chamado pela aplicação responsável por popular a tabela PRAZO_PROGRAMADO,
ao final da rotina de carga/atualização.

- URL (produção): `https://api.correios.com.br/prazo/v1/nacional/prazobaselote/cache`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/nacional/prazobaselote/cache`
- Header: `Authorization: Bearer <token>`
- operationId: `limparCachePrazoBaseLote`

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `204` | Cache limpo com sucesso |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

### Coleta

Prazo de coleta.

#### 1.3 `GET /v1/coleta/{cep}`

**Consulta prazo de uma coleta.**

- URL (produção): `https://api.correios.com.br/prazo/v1/coleta/{cep}`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/coleta/{cep}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrazoColeta`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `cep` | path | string | ✔ | CEP da coleta. — Ex.: `70902000` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v1/coleta/70902000
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrazoColeta` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `prazoColeta` | integer (int32) |  | Prazo em dias úteis |
| `txErro` | string |  | Descricao do erro retornado |

Exemplo de resposta 200:

```json
{
  "prazoColeta": 0,
  "txErro": "string"
}
```

### Nacional

Prazo de produtos e serviços nacionais.

#### 1.4 `POST /v1/nacional`

**Consulta prazo de uma lista de produtos**

- URL (produção): `https://api.correios.com.br/prazo/v1/nacional`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/nacional`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `listaPrazos`

**Corpo da requisição** (obrigatório) — schema `LoteParamPrazoNacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosPrazo` | array<objeto `ParamPrazoNacional`> | ✔ | Lista dos parâmetros de consulta. |
| `parametrosPrazo[].coProduto` | string | ✔ | Código do produto — Ex.: `04162` |
| `parametrosPrazo[].nuRequisicao` | string |  | Número informado e controlado pelo requisitante. |
| `parametrosPrazo[].dtEvento` | string |  | Data que será usado para o cálculo o prazo. Formato DD/MM/YYYY. Caso não informado, será considerada a data atual. |
| `parametrosPrazo[].cepOrigem` | string | ✔ | CEP de origem da postagem. |
| `parametrosPrazo[].cepDestino` | string | ✔ | CEP de destino da postagem. |
| `parametrosPrazo[].dataPostagem` | string (date) |  |  |

```json
{
  "idLote": "string",
  "parametrosPrazo": [
    {
      "coProduto": "04162",
      "nuRequisicao": "string",
      "dtEvento": "string",
      "cepOrigem": "string",
      "cepDestino": "string",
      "dataPostagem": "2026-01-01"
    }
  ]
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `PrazoNacional`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].coProduto` | string |  | Código do produto ou serviço |
| `[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `[].prazoEntrega` | integer (int32) |  | Prazo em dias úteis |
| `[].dataMaxima` | string (date-time) |  | Data máxima de entrega |
| `[].txErro` | string |  | Descricao do erro retornado |
| `[].entregaDomiciliar` | string |  | Indicador de entrega domiciliar |
| `[].entregaSabado` | string |  | Indicador de entrega aos sábados |
| `[].entregaDomingo` | string |  | Indicador de entrega aos domingos |
| `[].msgPrazo` | string |  | Mensagem de prazo |

Exemplo de resposta 200:

```json
[
  {
    "coProduto": "string",
    "nuRequisicao": "string",
    "prazoEntrega": 0,
    "dataMaxima": "2026-01-01T00:00:00",
    "txErro": "string",
    "entregaDomiciliar": "string",
    "entregaSabado": "string",
    "entregaDomingo": "string",
    "msgPrazo": "string"
  }
]
```

#### 1.5 `GET /v1/nacional/{coProduto}`

**Consulta prazo de um produto/serviço.**

- URL (produção): `https://api.correios.com.br/prazo/v1/nacional/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/nacional/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrazoNacional`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `45110` |
| `cepOrigem` | query | string | ✔ | CEP de origem da postagem. — Ex.: `70902000` |
| `cepDestino` | query | string | ✔ | CEP de destino da postagem. — Ex.: `71930000` |
| `dtEvento` | query | string |  | Data que será usado para o cálculo o preço. DD-MM-YYYY |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v1/nacional/45110?cepOrigem=70902000&cepDestino=71930000
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrazoNacional` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `prazoEntrega` | integer (int32) |  | Prazo em dias úteis |
| `dataMaxima` | string (date-time) |  | Data máxima de entrega |
| `txErro` | string |  | Descricao do erro retornado |
| `entregaDomiciliar` | string |  | Indicador de entrega domiciliar |
| `entregaSabado` | string |  | Indicador de entrega aos sábados |
| `entregaDomingo` | string |  | Indicador de entrega aos domingos |
| `msgPrazo` | string |  | Mensagem de prazo |

Exemplo de resposta 200:

```json
{
  "coProduto": "string",
  "nuRequisicao": "string",
  "prazoEntrega": 0,
  "dataMaxima": "2026-01-01T00:00:00",
  "txErro": "string",
  "entregaDomiciliar": "string",
  "entregaSabado": "string",
  "entregaDomingo": "string",
  "msgPrazo": "string"
}
```

### Data prevista

Calcula a data prevista de entrega.

#### 1.6 `GET /v1/data-prevista`

**Calcula a data prevista de entrega baseada no município, data de postagem e prazo em dias.**

- URL (produção): `https://api.correios.com.br/prazo/v1/data-prevista`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/data-prevista`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaDataPrevista`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `cepOrigem` | query | string | ✔ | pattern: `\d{8}` |
| `cepDestino` | query | string | ✔ | pattern: `\d{8}` |
| `prazo` | query | integer (int32) | ✔ | Prazo em dias. |
| `dtPostagem` | query | string | ✔ | Data da postagem. DD-MM-YYYY |
| `isEncomendaUPU` | query | boolean |  | Indica se é encomenda da UPU (true ou false) |
| `isSabadoDiaUtil` | query | boolean |  | Verifica se o sábado é considerado como dia útil (true ou false) |
| `isDomingoDiaUtil` | query | boolean |  | Verifica se o domingo é considerado como dia útil (true ou false) |
| `isPostagemDH` | query | boolean |  | Verifica se a postagem é DH (true ou false) |
| `coProduto` | query | string |  | Código do produto ou serviço — Ex.: `45110` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v1/data-prevista?cepOrigem={cepOrigem}&cepDestino={cepDestino}&prazo={prazo}&dtPostagem={dtPostagem}&coProduto=45110
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `DataPrevistaResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `dataPrevista` | string (date-time) |  | Data prevista de entrega. |
| `msgPrazo` | string |  | Mensagem prazo |

Exemplo de resposta 200:

```json
{
  "dataPrevista": "2026-01-01T00:00:00",
  "msgPrazo": "string"
}
```

### Internacional

Prazo de produtos e serviços internacionais.

#### 1.7 `POST /v1/internacional` ⚠️ DESCONTINUADO

**Consulta prazo de uma lista de produtos**

> ⚠️ Endpoint marcado como **deprecated** na especificação — prefira a versão mais nova quando existir.

- URL (produção): `https://api.correios.com.br/prazo/v1/internacional`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/internacional`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `listaPrazos_1`

**Corpo da requisição** (obrigatório) — schema `LoteParamPrazoInternacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosPrazo` | array<objeto `ParamPrazoInternacional`> | ✔ | Lista dos parâmetros de consulta. |
| `parametrosPrazo[].coProduto` | string |  | Código do Produto. |
| `parametrosPrazo[].nuRequisicao` | string |  | Número da requisição.Número informado e controlado pelo requisitante. |
| `parametrosPrazo[].dtEvento` | string |  | Data que será usado para o cálculo o prazo. Caso não informado, será considerada a data atual. |
| `parametrosPrazo[].cepOrigem` | string |  | CEP de origem da postagem. |
| `parametrosPrazo[].sgPaisDestino` | string |  | Sigla do País de destino. |
| `parametrosPrazo[].coCidadeDestino` | string |  | Código MCU da cidade de destino. |

```json
{
  "idLote": "string",
  "parametrosPrazo": [
    {
      "coProduto": "string",
      "nuRequisicao": "string",
      "dtEvento": "string",
      "cepOrigem": "string",
      "sgPaisDestino": "string",
      "coCidadeDestino": "string"
    }
  ]
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `PrazoInternacional`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].coProduto` | string |  | Código do produto ou serviço |
| `[].noProduto` | string |  | Nome do produto ou serviço |
| `[].nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `[].modalidade` | string |  | Modalidade |
| `[].noCidadeDestino` | string |  | Nome da cidade de destino |
| `[].prazoMinimo` | integer (int32) |  | Prazo mínimo em dias |
| `[].prazoMaximo` | integer (int32) |  | Prazo máximo em dias |
| `[].txErro` | string |  | Descricao do erro retornado |
| `[].infoAdicionais` | array<objeto `InfoAdicional`> |  | Lista de informações adicionais |
| `[].infoAdicionais[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `[].infoAdicionais[].valor` | string |  | Conteúdo da informação complementar. |
| `[].dataMinEntrega` | string (date-time) |  | Data mínima de entrega. |
| `[].dataMaxEntrega` | string (date-time) |  | Data máxima de entrega. |

Exemplo de resposta 200:

```json
[
  {
    "coProduto": "string",
    "noProduto": "string",
    "nuRequisicao": "string",
    "modalidade": "string",
    "noCidadeDestino": "string",
    "prazoMinimo": 0,
    "prazoMaximo": 0,
    "txErro": "string",
    "infoAdicionais": [
      {
        "tipo": "LOB_ERP",
        "valor": "string"
      }
    ],
    "dataMinEntrega": "2026-01-01T00:00:00",
    "dataMaxEntrega": "2026-01-01T00:00:00"
  }
]
```

#### 1.8 `GET /v2/internacional/importacao/{coProduto}`

**Consulta prazo de importação de um produto/serviço.**

- URL (produção): `https://api.correios.com.br/prazo/v2/internacional/importacao/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/prazo/v2/internacional/importacao/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrazoInternacionalImportacao`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `45110` |
| `cepOrigem` | query | string | ✔ | CEP de origem da postagem. — Ex.: `70902000` |
| `cepDestino` | query | string | ✔ | CEP de destino da postagem. — Ex.: `71930000` |
| `dtEvento` | query | string |  | Data que será usado para o cálculo o preço. DD-MM-YYYY |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v2/internacional/importacao/45110?cepOrigem=70902000&cepDestino=71930000
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrazoNacional` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `prazoEntrega` | integer (int32) |  | Prazo em dias úteis |
| `dataMaxima` | string (date-time) |  | Data máxima de entrega |
| `txErro` | string |  | Descricao do erro retornado |
| `entregaDomiciliar` | string |  | Indicador de entrega domiciliar |
| `entregaSabado` | string |  | Indicador de entrega aos sábados |
| `entregaDomingo` | string |  | Indicador de entrega aos domingos |
| `msgPrazo` | string |  | Mensagem de prazo |

Exemplo de resposta 200:

```json
{
  "coProduto": "string",
  "nuRequisicao": "string",
  "prazoEntrega": 0,
  "dataMaxima": "2026-01-01T00:00:00",
  "txErro": "string",
  "entregaDomiciliar": "string",
  "entregaSabado": "string",
  "entregaDomingo": "string",
  "msgPrazo": "string"
}
```

#### 1.9 `GET /v2/internacional/exportacao/{coProduto}`

**Consulta prazo internacional de exportação de um produto/serviço.**

- URL (produção): `https://api.correios.com.br/prazo/v2/internacional/exportacao/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/prazo/v2/internacional/exportacao/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrazoInternacionalExportacao`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `45179` |
| `sgPaisOrigem` | query | string | ✔ | Sigla do país de origem. — Ex.: `BR` |
| `sgPaisDestino` | query | string | ✔ | Sigla do país de destino. — Ex.: `DE` |
| `dtPostagem` | query | string | ✔ | Data postagem. DD-MM-YYYY |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v2/internacional/exportacao/45179?sgPaisOrigem=BR&sgPaisDestino=DE&dtPostagem={dtPostagem}
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrazoInternacionalV2` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `noProduto` | string |  | Nome do produto ou serviço |
| `sgPaisOrigem` | string |  | Sigla do pais  de origem |
| `sgPaisDestino` | string |  | Sigla do pais  de destino |
| `prazoMinimo` | integer (int64) |  | Prazo mínimo em dias |
| `prazoMaximo` | integer (int64) |  | Prazo máximo em dias |
| `dataMinEntrega` | string (date) |  | Data mínima |
| `dataMaxEntrega` | string (date) |  | Data máxima |

Exemplo de resposta 200:

```json
{
  "coProduto": "string",
  "noProduto": "string",
  "sgPaisOrigem": "string",
  "sgPaisDestino": "string",
  "prazoMinimo": 0,
  "prazoMaximo": 0,
  "dataMinEntrega": "2026-01-01",
  "dataMaxEntrega": "2026-01-01"
}
```

#### 1.10 `GET /v1/internacional/{coProduto}` ⚠️ DESCONTINUADO

**Consulta prazo de um produto/serviço.**

> ⚠️ Endpoint marcado como **deprecated** na especificação — prefira a versão mais nova quando existir.

- URL (produção): `https://api.correios.com.br/prazo/v1/internacional/{coProduto}`
- URL (homologação): `https://apihom.correios.com.br/prazo/v1/internacional/{coProduto}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrazoInternacional`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `coProduto` | path | string | ✔ | Código do produto ou serviço — Ex.: `45179` |
| `cepOrigem` | query | string | ✔ | CEP de origem da postagem. — Ex.: `72720290` |
| `sgPaisDestino` | query | string | ✔ | Sigla do país de destino. — Ex.: `DE` |
| `coCidadeDestino` | query | string | ✔ | Código da cidade de destino. Apenas para serviço internacional. — Ex.: `00178934` |
| `dtEvento` | query | string |  | Data que será usado para o cálculo o preço. DD-MM-YYYY |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/prazo/v1/internacional/45179?cepOrigem=72720290&sgPaisDestino=DE&coCidadeDestino=00178934
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrazoInternacional` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `noProduto` | string |  | Nome do produto ou serviço |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `modalidade` | string |  | Modalidade |
| `noCidadeDestino` | string |  | Nome da cidade de destino |
| `prazoMinimo` | integer (int32) |  | Prazo mínimo em dias |
| `prazoMaximo` | integer (int32) |  | Prazo máximo em dias |
| `txErro` | string |  | Descricao do erro retornado |
| `infoAdicionais` | array<objeto `InfoAdicional`> |  | Lista de informações adicionais |
| `infoAdicionais[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `infoAdicionais[].valor` | string |  | Conteúdo da informação complementar. |
| `dataMinEntrega` | string (date-time) |  | Data mínima de entrega. |
| `dataMaxEntrega` | string (date-time) |  | Data máxima de entrega. |

Exemplo de resposta 200:

```json
{
  "coProduto": "string",
  "noProduto": "string",
  "nuRequisicao": "string",
  "modalidade": "string",
  "noCidadeDestino": "string",
  "prazoMinimo": 0,
  "prazoMaximo": 0,
  "txErro": "string",
  "infoAdicionais": [
    {
      "tipo": "LOB_ERP",
      "valor": "string"
    }
  ],
  "dataMinEntrega": "2026-01-01T00:00:00",
  "dataMaxEntrega": "2026-01-01T00:00:00"
}
```

## 2. Schemas (todos os campos expandidos)

### `LoteParamPrazoNacional`

lote

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosPrazo` | array<objeto `ParamPrazoNacional`> | ✔ | Lista dos parâmetros de consulta. |
| `parametrosPrazo[].coProduto` | string | ✔ | Código do produto — Ex.: `04162` |
| `parametrosPrazo[].nuRequisicao` | string |  | Número informado e controlado pelo requisitante. |
| `parametrosPrazo[].dtEvento` | string |  | Data que será usado para o cálculo o prazo. Formato DD/MM/YYYY. Caso não informado, será considerada a data atual. |
| `parametrosPrazo[].cepOrigem` | string | ✔ | CEP de origem da postagem. |
| `parametrosPrazo[].cepDestino` | string | ✔ | CEP de destino da postagem. |
| `parametrosPrazo[].dataPostagem` | string (date) |  |  |

### `ParamPrazoNacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string | ✔ | Código do produto — Ex.: `04162` |
| `nuRequisicao` | string |  | Número informado e controlado pelo requisitante. |
| `dtEvento` | string |  | Data que será usado para o cálculo o prazo. Formato DD/MM/YYYY. Caso não informado, será considerada a data atual. |
| `cepOrigem` | string | ✔ | CEP de origem da postagem. |
| `cepDestino` | string | ✔ | CEP de destino da postagem. |
| `dataPostagem` | string (date) |  |  |

### `PrazoNacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `prazoEntrega` | integer (int32) |  | Prazo em dias úteis |
| `dataMaxima` | string (date-time) |  | Data máxima de entrega |
| `txErro` | string |  | Descricao do erro retornado |
| `entregaDomiciliar` | string |  | Indicador de entrega domiciliar |
| `entregaSabado` | string |  | Indicador de entrega aos sábados |
| `entregaDomingo` | string |  | Indicador de entrega aos domingos |
| `msgPrazo` | string |  | Mensagem de prazo |

### `LoteParamPrazoInternacional`

lote

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idLote` | string | ✔ | Identificador do lote da consulta. |
| `parametrosPrazo` | array<objeto `ParamPrazoInternacional`> | ✔ | Lista dos parâmetros de consulta. |
| `parametrosPrazo[].coProduto` | string |  | Código do Produto. |
| `parametrosPrazo[].nuRequisicao` | string |  | Número da requisição.Número informado e controlado pelo requisitante. |
| `parametrosPrazo[].dtEvento` | string |  | Data que será usado para o cálculo o prazo. Caso não informado, será considerada a data atual. |
| `parametrosPrazo[].cepOrigem` | string |  | CEP de origem da postagem. |
| `parametrosPrazo[].sgPaisDestino` | string |  | Sigla do País de destino. |
| `parametrosPrazo[].coCidadeDestino` | string |  | Código MCU da cidade de destino. |

### `ParamPrazoInternacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do Produto. |
| `nuRequisicao` | string |  | Número da requisição.Número informado e controlado pelo requisitante. |
| `dtEvento` | string |  | Data que será usado para o cálculo o prazo. Caso não informado, será considerada a data atual. |
| `cepOrigem` | string |  | CEP de origem da postagem. |
| `sgPaisDestino` | string |  | Sigla do País de destino. |
| `coCidadeDestino` | string |  | Código MCU da cidade de destino. |

### `InfoAdicional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `valor` | string |  | Conteúdo da informação complementar. |

### `PrazoInternacional`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `noProduto` | string |  | Nome do produto ou serviço |
| `nuRequisicao` | string |  | Número da requisição. Número informado e controlado pelo requisitante |
| `modalidade` | string |  | Modalidade |
| `noCidadeDestino` | string |  | Nome da cidade de destino |
| `prazoMinimo` | integer (int32) |  | Prazo mínimo em dias |
| `prazoMaximo` | integer (int32) |  | Prazo máximo em dias |
| `txErro` | string |  | Descricao do erro retornado |
| `infoAdicionais` | array<objeto `InfoAdicional`> |  | Lista de informações adicionais |
| `infoAdicionais[].tipo` | string |  | Código da informação complementar. — Valores: `LOB_ERP`, `LIMITE_MAX_TARIFACAO_ERP`, `VALOR_PARAMETRO_KILO_ADICIONAL_ERP`, `LOB_SGPB`, `NU_PROPOSTA`, `CO_MODELO`, `CO_SIMILARIDADE`, `IN_EXIGENCIA_CEP_DESTINO`, `CO_AGRUPADOR`, `TIPO_AGRUPADOR`, `NU_CORREDOR_ORIGEM`, `NU_CORREDOR_DESTINO`, `NU_TABELA_FATOR`, `NU_LINHA_FATOR`, `NU_COLUNA_FATOR`, `NO_COLUNA_FATOR`, `CLASSIFICACAO_COLUNA`, `NU_COMBO`, `NU_VARIACAO`, `VL_PRECO_BASE`, `FATOR_FAIXA`, `FATOR_CADA_ADICIONAL`, `QT_EXCESSO`, `NO_PRODUTO`, `TP_TARIFACAO_ERP`, `IN_CORREDOR_FAIXA_CEP_SGPB`, `IN_PRECIFICACAO_SGPB`, `VL_TAXA_EXTRA` |
| `infoAdicionais[].valor` | string |  | Conteúdo da informação complementar. |
| `dataMinEntrega` | string (date-time) |  | Data mínima de entrega. |
| `dataMaxEntrega` | string (date-time) |  | Data máxima de entrega. |

### `PrazoInternacionalV2`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `coProduto` | string |  | Código do produto ou serviço |
| `noProduto` | string |  | Nome do produto ou serviço |
| `sgPaisOrigem` | string |  | Sigla do pais  de origem |
| `sgPaisDestino` | string |  | Sigla do pais  de destino |
| `prazoMinimo` | integer (int64) |  | Prazo mínimo em dias |
| `prazoMaximo` | integer (int64) |  | Prazo máximo em dias |
| `dataMinEntrega` | string (date) |  | Data mínima |
| `dataMaxEntrega` | string (date) |  | Data máxima |

### `PrazoProgramadoDestino`

Destino agrupado por IBGE, município e nível de troca.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codIbgeDestino` | string |  | Código IBGE do município de destino. — Ex.: `3550308` |
| `municipioDestino` | string |  | Nome do município de destino com UF (cidade/UF). — Ex.: `SAO PAULO/SP` |
| `nivelTroca` | string |  | Nível de troca. — Ex.: `I4` |
| `prazosEntrega` | array<objeto `PrazoProgramadoPrazoEntrega`> |  | Prazos de entrega por faixa de CEP do destino. |
| `prazosEntrega[].cepInicioDestino` | string |  | CEP inicial do destino. — Ex.: `01001001` |
| `prazosEntrega[].cepFinalDestino` | string |  | CEP final do destino. — Ex.: `01002020` |
| `prazosEntrega[].prazoPac` | integer (int32) |  | Prazo PAC em dias. — Ex.: `5` |
| `prazosEntrega[].prazoSedex` | integer (int32) |  | Prazo Sedex em dias. — Ex.: `1` |

### `PrazoProgramadoLote`

Resultado da consulta de prazos programados (base lote).

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `origem` | string |  | Valor do filtro de origem informado (IBGE, CEP, município ou UF). — Ex.: `1100015` |
| `faixaCepOrigem` | string |  | Faixa de CEP de origem (menor inicial e maior final do resultado). — Ex.: `01001001 - 05999999` |
| `destinos` | array<objeto `PrazoProgramadoDestino`> |  | Destinos agrupados por IBGE, município e nível de troca. |
| `destinos[].codIbgeDestino` | string |  | Código IBGE do município de destino. — Ex.: `3550308` |
| `destinos[].municipioDestino` | string |  | Nome do município de destino com UF (cidade/UF). — Ex.: `SAO PAULO/SP` |
| `destinos[].nivelTroca` | string |  | Nível de troca. — Ex.: `I4` |
| `destinos[].prazosEntrega` | array<objeto `PrazoProgramadoPrazoEntrega`> |  | Prazos de entrega por faixa de CEP do destino. |
| `destinos[].prazosEntrega[].cepInicioDestino` | string |  | CEP inicial do destino. — Ex.: `01001001` |
| `destinos[].prazosEntrega[].cepFinalDestino` | string |  | CEP final do destino. — Ex.: `01002020` |
| `destinos[].prazosEntrega[].prazoPac` | integer (int32) |  | Prazo PAC em dias. — Ex.: `5` |
| `destinos[].prazosEntrega[].prazoSedex` | integer (int32) |  | Prazo Sedex em dias. — Ex.: `1` |

### `PrazoProgramadoPrazoEntrega`

Faixa de CEP de destino com prazos PAC e Sedex.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `cepInicioDestino` | string |  | CEP inicial do destino. — Ex.: `01001001` |
| `cepFinalDestino` | string |  | CEP final do destino. — Ex.: `01002020` |
| `prazoPac` | integer (int32) |  | Prazo PAC em dias. — Ex.: `5` |
| `prazoSedex` | integer (int32) |  | Prazo Sedex em dias. — Ex.: `1` |

### `DataPrevistaResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `dataPrevista` | string (date-time) |  | Data prevista de entrega. |
| `msgPrazo` | string |  | Mensagem prazo |

### `PrazoColeta`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `prazoColeta` | integer (int32) |  | Prazo em dias úteis |
| `txErro` | string |  | Descricao do erro retornado |

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
| `200` | Consulta realizada com sucesso. |
| `204` | Operação concluída sem conteúdo (limpeza de cache). |
| `400` | Parâmetros inválidos ou regra de negócio não atendida. Ver `MessageResponse.msgs`. |
| `401` | Token ausente, inválido ou expirado — gere/renove pela API Token. |
| `403` | Token sem permissão para esta API/contrato. |
| `500` | Erro interno dos Correios. |

## 4. JSON Schema completo (referências expandidas)

### `POST /v1/nacional`

Requisição:

```json
{
  "title": "LoteParamPrazoNacional",
  "type": "object",
  "description": "lote",
  "properties": {
    "idLote": {
      "type": "string",
      "description": "Identificador do lote da consulta."
    },
    "parametrosPrazo": {
      "type": "array",
      "description": "Lista dos parâmetros de consulta.",
      "items": {
        "title": "ParamPrazoNacional",
        "type": "object",
        "properties": {
          "coProduto": {
            "type": "string",
            "description": "Código do produto",
            "example": "04162"
          },
          "nuRequisicao": {
            "type": "string",
            "description": "Número informado e controlado pelo requisitante."
          },
          "dtEvento": {
            "type": "string",
            "description": "Data que será usado para o cálculo o prazo. Formato DD/MM/YYYY. Caso não informado, será considerada a data atual."
          },
          "cepOrigem": {
            "type": "string",
            "description": "CEP de origem da postagem."
          },
          "cepDestino": {
            "type": "string",
            "description": "CEP de destino da postagem."
          },
          "dataPostagem": {
            "type": "string",
            "format": "date"
          }
        },
        "required": [
          "cepDestino",
          "cepOrigem",
          "coProduto"
        ]
      }
    }
  },
  "required": [
    "idLote",
    "parametrosPrazo"
  ]
}
```

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "PrazoNacional",
    "type": "object",
    "properties": {
      "coProduto": {
        "type": "string",
        "description": "Código do produto ou serviço"
      },
      "nuRequisicao": {
        "type": "string",
        "description": "Número da requisição. Número informado e controlado pelo requisitante"
      },
      "prazoEntrega": {
        "type": "integer",
        "format": "int32",
        "description": "Prazo em dias úteis"
      },
      "dataMaxima": {
        "type": "string",
        "format": "date-time",
        "description": "Data máxima de entrega"
      },
      "txErro": {
        "type": "string",
        "description": "Descricao do erro retornado"
      },
      "entregaDomiciliar": {
        "type": "string",
        "description": "Indicador de entrega domiciliar"
      },
      "entregaSabado": {
        "type": "string",
        "description": "Indicador de entrega aos sábados"
      },
      "entregaDomingo": {
        "type": "string",
        "description": "Indicador de entrega aos domingos"
      },
      "msgPrazo": {
        "type": "string",
        "description": "Mensagem de prazo"
      }
    }
  }
}
```

### `POST /v1/internacional`

Requisição:

```json
{
  "title": "LoteParamPrazoInternacional",
  "type": "object",
  "description": "lote",
  "properties": {
    "idLote": {
      "type": "string",
      "description": "Identificador do lote da consulta."
    },
    "parametrosPrazo": {
      "type": "array",
      "description": "Lista dos parâmetros de consulta.",
      "items": {
        "title": "ParamPrazoInternacional",
        "type": "object",
        "properties": {
          "coProduto": {
            "type": "string",
            "description": "Código do Produto."
          },
          "nuRequisicao": {
            "type": "string",
            "description": "Número da requisição.Número informado e controlado pelo requisitante."
          },
          "dtEvento": {
            "type": "string",
            "description": "Data que será usado para o cálculo o prazo. Caso não informado, será considerada a data atual."
          },
          "cepOrigem": {
            "type": "string",
            "description": "CEP de origem da postagem."
          },
          "sgPaisDestino": {
            "type": "string",
            "description": "Sigla do País de destino."
          },
          "coCidadeDestino": {
            "type": "string",
            "description": "Código MCU da cidade de destino."
          }
        }
      }
    }
  },
  "required": [
    "idLote",
    "parametrosPrazo"
  ]
}
```

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "PrazoInternacional",
    "type": "object",
    "properties": {
      "coProduto": {
        "type": "string",
        "description": "Código do produto ou serviço"
      },
      "noProduto": {
        "type": "string",
        "description": "Nome do produto ou serviço"
      },
      "nuRequisicao": {
        "type": "string",
        "description": "Número da requisição. Número informado e controlado pelo requisitante"
      },
      "modalidade": {
        "type": "string",
        "description": "Modalidade"
      },
      "noCidadeDestino": {
        "type": "string",
        "description": "Nome da cidade de destino"
      },
      "prazoMinimo": {
        "type": "integer",
        "format": "int32",
        "description": "Prazo mínimo em dias"
      },
      "prazoMaximo": {
        "type": "integer",
        "format": "int32",
        "description": "Prazo máximo em dias"
      },
      "txErro": {
        "type": "string",
        "description": "Descricao do erro retornado"
      },
      "infoAdicionais": {
        "type": "array",
        "description": "Lista de informações adicionais",
        "items": {
          "title": "InfoAdicional",
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
      "dataMinEntrega": {
        "type": "string",
        "format": "date-time",
        "description": "Data mínima de entrega."
      },
      "dataMaxEntrega": {
        "type": "string",
        "format": "date-time",
        "description": "Data máxima de entrega."
      }
    }
  }
}
```

### `GET /v2/internacional/importacao/{coProduto}`

Resposta 200:

```json
{
  "title": "PrazoNacional",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço"
    },
    "nuRequisicao": {
      "type": "string",
      "description": "Número da requisição. Número informado e controlado pelo requisitante"
    },
    "prazoEntrega": {
      "type": "integer",
      "format": "int32",
      "description": "Prazo em dias úteis"
    },
    "dataMaxima": {
      "type": "string",
      "format": "date-time",
      "description": "Data máxima de entrega"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    },
    "entregaDomiciliar": {
      "type": "string",
      "description": "Indicador de entrega domiciliar"
    },
    "entregaSabado": {
      "type": "string",
      "description": "Indicador de entrega aos sábados"
    },
    "entregaDomingo": {
      "type": "string",
      "description": "Indicador de entrega aos domingos"
    },
    "msgPrazo": {
      "type": "string",
      "description": "Mensagem de prazo"
    }
  }
}
```

### `GET /v2/internacional/exportacao/{coProduto}`

Resposta 200:

```json
{
  "title": "PrazoInternacionalV2",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço"
    },
    "noProduto": {
      "type": "string",
      "description": "Nome do produto ou serviço"
    },
    "sgPaisOrigem": {
      "type": "string",
      "description": "Sigla do pais  de origem"
    },
    "sgPaisDestino": {
      "type": "string",
      "description": "Sigla do pais  de destino"
    },
    "prazoMinimo": {
      "type": "integer",
      "format": "int64",
      "description": "Prazo mínimo em dias"
    },
    "prazoMaximo": {
      "type": "integer",
      "format": "int64",
      "description": "Prazo máximo em dias"
    },
    "dataMinEntrega": {
      "type": "string",
      "format": "date",
      "description": "Data mínima"
    },
    "dataMaxEntrega": {
      "type": "string",
      "format": "date",
      "description": "Data máxima"
    }
  }
}
```

### `GET /v1/nacional/{coProduto}`

Resposta 200:

```json
{
  "title": "PrazoNacional",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço"
    },
    "nuRequisicao": {
      "type": "string",
      "description": "Número da requisição. Número informado e controlado pelo requisitante"
    },
    "prazoEntrega": {
      "type": "integer",
      "format": "int32",
      "description": "Prazo em dias úteis"
    },
    "dataMaxima": {
      "type": "string",
      "format": "date-time",
      "description": "Data máxima de entrega"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    },
    "entregaDomiciliar": {
      "type": "string",
      "description": "Indicador de entrega domiciliar"
    },
    "entregaSabado": {
      "type": "string",
      "description": "Indicador de entrega aos sábados"
    },
    "entregaDomingo": {
      "type": "string",
      "description": "Indicador de entrega aos domingos"
    },
    "msgPrazo": {
      "type": "string",
      "description": "Mensagem de prazo"
    }
  }
}
```

### `GET /v1/nacional/prazobaselote`

Resposta 200:

```json
{
  "title": "PrazoProgramadoLote",
  "type": "object",
  "description": "Resultado da consulta de prazos programados (base lote).",
  "properties": {
    "origem": {
      "type": "string",
      "description": "Valor do filtro de origem informado (IBGE, CEP, município ou UF).",
      "example": "1100015"
    },
    "faixaCepOrigem": {
      "type": "string",
      "description": "Faixa de CEP de origem (menor inicial e maior final do resultado).",
      "example": "01001001 - 05999999"
    },
    "destinos": {
      "type": "array",
      "description": "Destinos agrupados por IBGE, município e nível de troca.",
      "items": {
        "title": "PrazoProgramadoDestino",
        "type": "object",
        "description": "Destino agrupado por IBGE, município e nível de troca.",
        "properties": {
          "codIbgeDestino": {
            "type": "string",
            "description": "Código IBGE do município de destino.",
            "example": "3550308"
          },
          "municipioDestino": {
            "type": "string",
            "description": "Nome do município de destino com UF (cidade/UF).",
            "example": "SAO PAULO/SP"
          },
          "nivelTroca": {
            "type": "string",
            "description": "Nível de troca.",
            "example": "I4"
          },
          "prazosEntrega": {
            "type": "array",
            "description": "Prazos de entrega por faixa de CEP do destino.",
            "items": {
              "title": "PrazoProgramadoPrazoEntrega",
              "type": "object",
              "description": "Faixa de CEP de destino com prazos PAC e Sedex.",
              "properties": {
                "cepInicioDestino": {
                  "type": "string",
                  "description": "CEP inicial do destino.",
                  "example": "01001001"
                },
                "cepFinalDestino": {
                  "type": "string",
                  "description": "CEP final do destino.",
                  "example": "01002020"
                },
                "prazoPac": {
                  "type": "integer",
                  "format": "int32",
                  "description": "Prazo PAC em dias.",
                  "example": 5
                },
                "prazoSedex": {
                  "type": "integer",
                  "format": "int32",
                  "description": "Prazo Sedex em dias.",
                  "example": 1
                }
              }
            }
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
  "title": "PrazoInternacional",
  "type": "object",
  "properties": {
    "coProduto": {
      "type": "string",
      "description": "Código do produto ou serviço"
    },
    "noProduto": {
      "type": "string",
      "description": "Nome do produto ou serviço"
    },
    "nuRequisicao": {
      "type": "string",
      "description": "Número da requisição. Número informado e controlado pelo requisitante"
    },
    "modalidade": {
      "type": "string",
      "description": "Modalidade"
    },
    "noCidadeDestino": {
      "type": "string",
      "description": "Nome da cidade de destino"
    },
    "prazoMinimo": {
      "type": "integer",
      "format": "int32",
      "description": "Prazo mínimo em dias"
    },
    "prazoMaximo": {
      "type": "integer",
      "format": "int32",
      "description": "Prazo máximo em dias"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    },
    "infoAdicionais": {
      "type": "array",
      "description": "Lista de informações adicionais",
      "items": {
        "title": "InfoAdicional",
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
    "dataMinEntrega": {
      "type": "string",
      "format": "date-time",
      "description": "Data mínima de entrega."
    },
    "dataMaxEntrega": {
      "type": "string",
      "format": "date-time",
      "description": "Data máxima de entrega."
    }
  }
}
```

### `GET /v1/data-prevista`

Resposta 200:

```json
{
  "title": "DataPrevistaResponse",
  "type": "object",
  "properties": {
    "dataPrevista": {
      "type": "string",
      "format": "date-time",
      "description": "Data prevista de entrega."
    },
    "msgPrazo": {
      "type": "string",
      "description": "Mensagem prazo"
    }
  }
}
```

### `GET /v1/coleta/{cep}`

Resposta 200:

```json
{
  "title": "PrazoColeta",
  "type": "object",
  "properties": {
    "prazoColeta": {
      "type": "integer",
      "format": "int32",
      "description": "Prazo em dias úteis"
    },
    "txErro": {
      "type": "string",
      "description": "Descricao do erro retornado"
    }
  }
}
```

## 5. Orientações de implementação

1. **Token:** obtenha o Bearer pela API Token e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita a chamada.
2. **Qual endpoint usar (nacional):** para um único serviço, `GET /v1/nacional/{coProduto}`; para vários serviços/CEPs de uma vez, `POST /v1/nacional` (lote) — reduz o número de chamadas.
3. **Internacional:** use os endpoints **v2** (`/v2/internacional/importacao/{coProduto}` e `/v2/internacional/exportacao/{coProduto}`). `POST /v1/internacional` e `GET /v1/internacional/{coProduto}` estão descontinuados.
4. **CEPs:** envie apenas os 8 dígitos, sem hífen.
5. **coProduto:** código do produto/serviço dos Correios (ex.: `45110`), de acordo com os serviços do seu contrato — o mesmo código de serviço usado nas APIs Preço e Pré-postagem; obtenha a lista em `correios-02-meucontrato-servicos-cartao-api.md`.
6. **Datas em parâmetros:** `dtEvento` usa o formato `DD-MM-YYYY`; confira o formato indicado em cada parâmetro de data.
7. **Data prevista:** o prazo é retornado em dias; para mostrar ao cliente uma data de entrega use `GET /v1/data-prevista`, que calcula a data a partir do município, da data de postagem e do prazo (com opções para considerar sábado/domingo como dia útil).
8. **Cache:** prazos mudam pouco — armazene em cache por combinação serviço + CEP origem + CEP destino (ex.: algumas horas) para economizar chamadas.
9. **`DELETE /v1/nacional/prazobaselote/cache`** é uma operação administrativa; normalmente não deve ser chamada pela aplicação.
10. **Ambientes:** mantenha a URL base configurável (homologação × produção). A versão publicada em produção pode estar atrás da de homologação.
