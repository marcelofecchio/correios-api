# Correios — Pré-postagem: `GET /v1/prepostagens/postada`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 03/10/2026. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 12 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-11-webhook-api.md` · Próxima: `correios-13-prepostagem-cancelar-api.md`
>
> **Ainda não testado em homologação:** o endpoint só retorna dados depois que o objeto é **postado** em uma agência; nos testes feitos até aqui as pré-postagens ficaram no status pré-postado.

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Consulta detalhes de pré-postagem após realização da postagem. |
| **Método / caminho** | `GET /v1/prepostagens/postada` |
| **URL produção** | `https://api.correios.com.br/prepostagem/v1/prepostagens/postada?codigoObjeto={codigoObjeto}` |
| **URL homologação** | `https://apihom.correios.com.br/prepostagem/v1/prepostagens/postada?codigoObjeto={codigoObjeto}` |
| **Headers** | `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`) |
| **Corpo da requisição** | Nenhum |
| **operationId** | `consultaMovimento` |
| **Grupo (tag)** | Pré-Postagem Nacional (Objetos Registrados) |
| **Schema da resposta 200** | `MovimentoPostagemDTO` — "Dados de Pré-postagem atualizados após postagem." (24 campos + lista de serviços adicionais) |

### Para que serve

Depois que o objeto é entregue e **postado** na agência, os Correios conferem peso e medidas e calculam o valor efetivamente cobrado. Este endpoint devolve esses dados reais do atendimento: data da postagem, agência, **valor tarifado**, peso e dimensões **tarifados**, valor declarado e serviços adicionais cobrados.

Uso típico: **conciliar** o que foi informado na pré-postagem (e cotado na API Preço) com o que foi cobrado de fato.

### Fluxo

```
1. POST /v1/prepostagens                      → cria a pré-postagem (id + codigoObjeto, statusAtual = 2)
2. Rótulo (arquivos 08 e 09) e postagem física na agência
3. statusAtual passa a 3 (POSTADO)             → acompanhar pela API Rastro ou Webhook (arquivos 10 e 11)
4. GET /v1/prepostagens/postada?codigoObjeto=… → dados reais da postagem   ◄ este documento
```

## 1. Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição (especificação) |
|---|---|---|:-:|---|
| `codigoObjeto` | query | string | ✔ | Código identificador do objeto. |

- `codigoObjeto`: código de rastreio de 13 caracteres (ex.: `AB043180865BR`), sem espaços — o mesmo `codigoObjeto` devolvido na criação da pré-postagem. A consulta é por **um** objeto por chamada.

### Exemplo de chamada

```http
GET https://apihom.correios.com.br/prepostagem/v1/prepostagens/postada?codigoObjeto=AB043180865BR
Authorization: Bearer <token>
Accept: application/json
```

## 2. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `MovimentoPostagemDTO` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

> Os códigos 401 e 403 não constam na especificação deste endpoint; são o comportamento geral das APIs (ver `correios-00-instrucoes-gerais.md`). Outros endpoints desta API também devolveram **422** para regras de negócio (ver arquivo 06).

### 2.1 Resposta 200 — `MovimentoPostagemDTO` (todos os campos)

Nenhum campo é marcado como obrigatório na especificação.

**Identificação do atendimento**

| Campo | Tipo | Descrição (especificação) |
|---|---|---|
| `id` | string | Número sequencial identificador. |
| `numeroPlp` | integer (int64) | Número da PLP (Pré lista de postagem) enviada pelo SIGEP Master. |
| `numeroExternoCliente` | string | Número do Cliente: CPF/CNPJ ou outros que realizou a postagem. |
| `numeroCartaoPostagem` | integer (int32) | Código do cartão de postagem do cliente para realizar a postagem. |
| `numeroAtendimento` | string | Tabela para controle processamento de informações do BAU. |
| `numeroSistema` | string | Número do sistema do atendimento. |
| `dataPostagem` | string | Data da postagem do objeto. |
| `dataHoraAtendimento` | string | Data e hora do atendimento. |
| `valorAtendimento` | number | Valor tarifado após postagem. |

**Endereçamento**

| Campo | Tipo | Descrição (especificação) |
|---|---|---|
| `cepAgencia` | string | CEP do Endereço dos Orgãos dos Correios( as agencias proprias, agencias franqueadas, diretoria regional e REOPs. |
| `cepRemetente` | string | CEP do Remetente. |
| `cepDestinatario` | string | Cep do destinatario. |
| `cepDestino` | string | CEP de Destino da Correspondência. |

**Objeto e serviço**

| Campo | Tipo | Descrição (especificação) |
|---|---|---|
| `codigoObjeto` | string | Número da Etiqueta Utilizada no Atendimento. |
| `codigoServico` | string | Código do Produto ou Serviço utilizado pela ECT. |
| `nomeServico` | string | Nome do Produto/Serviço. |
| `alturaObjeto` | string | Altura tarifada do objeto após postagem. |
| `larguraObjeto` | string | Largura tarifada em Centímetros do objeto postado. |
| `comprimentoObjeto` | string | Valor do comprimento tarifado em Centimetros do objeto que esta sendo postado. |
| `diametroObjeto` | string | Valor do Diâmetro tarifado do Objeto. |
| `pesoObjeto` | string | Peso do Objeto. |
| `pesoTarifadoObjeto` | string | Armazena o peso tarifado (resultado do cálculo feito com as dimensões). |
| `valorDeclaradoObjeto` | string | Valor Declarado do Objeto. |

**Serviços adicionais — `listaServicoAdicional[]` (`MovimentoServicoAdicional`)**

| Campo | Tipo | Descrição (especificação) |
|---|---|---|
| `listaServicoAdicional` | array<objeto `MovimentoServicoAdicional`> | Lista de serviços adicionais. |
| `listaServicoAdicional[].codigoServicoAdicional` | string | (sem descrição) — código do adicional, ex.: `019`, `001` |
| `listaServicoAdicional[].valor` | number | (sem descrição) |
| `listaServicoAdicional[].tipoComposicao` | string | (sem descrição) |

### 2.2 Exemplo de resposta 200 (esqueleto — valores ilustrativos)

A especificação não traz exemplo. O esqueleto abaixo mostra apenas a estrutura e os tipos; os formatos de data e de valores devem ser confirmados com uma resposta real.

```json
{
  "id": "string",
  "numeroPlp": 0,
  "numeroExternoCliente": "string",
  "numeroCartaoPostagem": 0,
  "cepAgencia": "string",
  "cepDestino": "string",
  "numeroAtendimento": "string",
  "dataPostagem": "string",
  "dataHoraAtendimento": "string",
  "valorAtendimento": 0,
  "numeroSistema": "string",
  "cepRemetente": "string",
  "cepDestinatario": "string",
  "codigoObjeto": "string",
  "codigoServico": "string",
  "nomeServico": "string",
  "alturaObjeto": "string",
  "comprimentoObjeto": "string",
  "larguraObjeto": "string",
  "diametroObjeto": "string",
  "pesoObjeto": "string",
  "pesoTarifadoObjeto": "string",
  "valorDeclaradoObjeto": "string",
  "listaServicoAdicional": [
    {
      "codigoServicoAdicional": "string",
      "valor": 0,
      "tipoComposicao": "string"
    }
  ]
}
```

### 2.3 Resposta de erro (400/500) — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

```json
{
  "msgs": [
    "Mensagem de validação"
  ],
  "date": "2026-01-01T00:00:00",
  "method": "GET",
  "path": "/v1/prepostagens/postada",
  "causa": "string",
  "stackTrace": "string"
}
```

## 3. JSON Schema da resposta 200 (referências expandidas)

```json
{
  "title": "MovimentoPostagemDTO",
  "type": "object",
  "description": "Dados de Pré-postagem atualizados após postagem.",
  "properties": {
    "id": { "type": "string", "description": "Número sequencial identificador." },
    "numeroPlp": { "type": "integer", "format": "int64", "description": "Número da PLP (Pré lista de postagem) enviada pelo SIGEP Master." },
    "numeroExternoCliente": { "type": "string", "description": "Número do Cliente: CPF/CNPJ ou outros que realizou a postagem." },
    "numeroCartaoPostagem": { "type": "integer", "format": "int32", "description": "Código do cartão de postagem do cliente para realizar a postagem." },
    "cepAgencia": { "type": "string", "description": "CEP do Endereço dos Orgãos dos Correios( as agencias proprias, agencias franqueadas, diretoria regional e REOPs." },
    "cepDestino": { "type": "string", "description": "CEP de Destino da Correspondência." },
    "numeroAtendimento": { "type": "string", "description": "Tabela para controle processamento de informações do BAU." },
    "dataPostagem": { "type": "string", "description": "Data da postagem do objeto." },
    "dataHoraAtendimento": { "type": "string", "description": "Data e hora do atendimento." },
    "valorAtendimento": { "type": "number", "description": "Valor tarifado após postagem." },
    "numeroSistema": { "type": "string", "description": "Número do sistema do atendimento." },
    "cepRemetente": { "type": "string", "description": "CEP do Remetente." },
    "cepDestinatario": { "type": "string", "description": "Cep do destinatario." },
    "codigoObjeto": { "type": "string", "description": "Número da Etiqueta Utilizada no Atendimento." },
    "codigoServico": { "type": "string", "description": "Código do Produto ou Serviço utilizado pela ECT." },
    "nomeServico": { "type": "string", "description": "Nome do Produto/Serviço." },
    "alturaObjeto": { "type": "string", "description": "Altura tarifada do objeto após postagem." },
    "comprimentoObjeto": { "type": "string", "description": "Valor do comprimento tarifado em Centimetros do objeto que esta sendo postado." },
    "larguraObjeto": { "type": "string", "description": "Largura tarifada em Centímetros do objeto postado." },
    "diametroObjeto": { "type": "string", "description": "Valor do Diâmetro tarifado do Objeto." },
    "pesoObjeto": { "type": "string", "description": "Peso do Objeto." },
    "pesoTarifadoObjeto": { "type": "string", "description": "Armazena o peso tarifado (resultado do cálculo feito com as dimensões)." },
    "valorDeclaradoObjeto": { "type": "string", "description": "Valor Declarado do Objeto." },
    "listaServicoAdicional": {
      "type": "array",
      "description": "Lista de serviços adicionais.",
      "items": {
        "title": "MovimentoServicoAdicional",
        "type": "object",
        "properties": {
          "codigoServicoAdicional": { "type": "string" },
          "valor": { "type": "number" },
          "tipoComposicao": { "type": "string" }
        }
      }
    }
  }
}
```

## 4. Endpoints relacionados (mesmo grupo "Pré-Postagem Nacional (Objetos Registrados)")

| Método | Caminho | Resumo (especificação) |
|---|---|---|
| POST | `/v1/prepostagens` | Permite criar uma Pré-Postagem. (arquivo 06) |
| POST | `/v1/prepostagens/lista/objetosregistrados` | Realiza a importação de arquivo de objetos registrados. |
| PATCH | `/v1/prepostagens/{id}/logisticareversa` | Atualiza data de validade de pré-postagem com logística reversa. |
| GET | `/v1/prepostagens/postada` | Consulta detalhes de pré-postagem após realização da postagem. ◄ este documento |
| GET | `/v1/prepostagens/declaracaoconteudo/{ids}` | Permite emitir a declaração de conteúdo da Pré-Postagem. Formato HTML. |
| GET | `/v1/prepostagens/avisorecebimento/{ids}` | Permite emitir o aviso de recebimento da Pré-Postagem que tenha o serviço adicional cadastrado. Formato HTML. |
| DELETE | `/v1/prepostagens/{id}` | Cancela uma pré-postagem pelo identificador. |
| DELETE | `/v1/prepostagens/objeto/{codigoObjeto}` | Cancela uma pré-postagem pelo código do objeto. |

## 5. Orientações de implementação

1. **Token:** use o Bearer da API Token (`/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita.
2. **Quando consultar:** somente depois que o objeto estiver **postado** (`statusAtual = 3`, ou evento de postagem na API Rastro/Webhook). Para objetos ainda pré-postados (`statusAtual = 2`), cancelados ou expirados, espere erro ou resposta sem dados — trate esse caso sem interromper o fluxo e tente novamente mais tarde.
3. **Não faça consulta em massa periódica:** dispare a consulta uma vez por objeto, quando souber da postagem (de preferência a partir do evento do Webhook), e repita apenas se os dados ainda não estiverem disponíveis.
4. **Validação do parâmetro:** envie o código com 13 caracteres, maiúsculo e sem espaços (`^[A-Z]{2}[0-9]{9}[A-Z]{2}$`). A etiqueta impressa mostra o código com espaços (`AB 043 180 865 BR`) — remova-os.
5. **Conciliação:** compare `valorAtendimento` com o preço cotado (API Preço) e `pesoTarifadoObjeto` / dimensões tarifadas com o que foi informado na pré-postagem (`pesoInformado`, `alturaInformada` etc.). Diferenças indicam medidas corrigidas na agência ou cobrança por peso cúbico.
6. **Tipos:** `valorAtendimento` e `listaServicoAdicional[].valor` são `number`; já peso, dimensões e `valorDeclaradoObjeto` vêm como **string**, e `numeroCartaoPostagem` vem como **inteiro** (perde os zeros à esquerda — ex.: `0012345678` → `12345678`; compare como número ou complete com zeros). Converta com cuidado, aceitando vírgula ou ponto decimal até confirmar o formato real.
7. **Datas:** `dataPostagem` e `dataHoraAtendimento` são `string` sem formato declarado. Registre a primeira resposta real e só então fixe o parser.
8. **Persistência:** grave os dados retornados junto da pré-postagem (data de postagem, agência, valor cobrado, peso tarifado, adicionais) — eles não mudam depois, então não é preciso consultar de novo.
9. **Ambientes:** mantenha a URL base configurável (homologação × produção). Em homologação a postagem física não acontece, então este endpoint normalmente só pode ser validado em produção ou com um objeto de teste postado pelos Correios.
