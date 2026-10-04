# Correios — Pré-postagem: `POST /v1/prepostagens/rotulo/assincrono/pdf`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 25/09/2026. As seções 1 a 5 reproduzem a especificação; a seção 6 traz orientações de implementação derivadas dela.
>
> **Atualizado em 27/09/2026** com a resposta real obtida em homologação (seções 0 e 3 — marcadas como **✔ Observado em homologação**).
>
> **Sequência de testes:** etapa 08 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-07-prepostagem-assincrona-registrados-api.md` · Próxima: `correios-09-rotulo-download-assincrono-api.md`

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Para objetos Registrados, solicita de maneira assíncrona a geração do rótulo pelos ids das pré-postagens. (somente para geração de rótulos de objetos REGISTRADOS). |
| **Método / caminho** | `POST /v1/prepostagens/rotulo/assincrono/pdf` |
| **URL produção** | `https://api.correios.com.br/prepostagem/v1/prepostagens/rotulo/assincrono/pdf` |
| **URL homologação** | `https://apihom.correios.com.br/prepostagem/v1/prepostagens/rotulo/assincrono/pdf` |
| **Headers** | `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`) · `Content-Type: application/json` |
| **Schema da requisição** | `PrePostagemRotuloRequestInput` |
| **Schema da resposta 200** | Especificação: `object`. ✔ Observado: `{ "idRecibo": "<string>" }` |
| **Processamento** | Assíncrono — a geração do PDF é solicitada aqui e o arquivo é obtido depois em `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}` |
| **Aplica-se a** | Somente objetos **REGISTRADOS**. Para objetos **SIMPLES** em lote, use `POST /v1/prepostagens/rotulo/lote/assincrono/pdf`. |

### Fluxo

```
1. POST /v1/prepostagens            → cria a pré-postagem (retorna id e código do objeto)
2. POST /v1/prepostagens/rotulo/assincrono/pdf  → solicita o PDF do rótulo → { "idRecibo": "..." }
3. GET  /v1/prepostagens/rotulo/download/assincrono/{idRecibo} → baixa o PDF gerado
```

## 1. Requisição — campos

| Campo | Tipo | Obrig. | Descrição (especificação) | Valores / regras |
|---|---|:-:|---|---|
| `codigosObjeto` | array<string> | condicional | Códigos dos objetos. Campo obrigatório caso não seja informado idAtendimento e idsPrePostagem. | Um dos três identificadores (ver regra abaixo). |
| `idCorreios` | string |  | IDCorreios de quem gerou a pré-postagem. |  |
| `numeroCartaoPostagem` | string |  | Número do cartão de postagem de quem gerou a pré-postagem. |  |
| `tipoRotulo` | string | ✔ | Tipo de Rótulo: P (padrão) ou R (reduzido). | Valores: `P`, `R` |
| `formatoRotulo` | string |  | Formato de Rótulo: ET (Etiqueta) ou EV (Envelope). Default ET. | Valores: `ET`, `EV` — padrão `ET` |
| `idAtendimento` | string | condicional | ID do atendimento. Campo obrigatório caso não seja informado codigosObjeto e idsPrePostagem. | Um dos três identificadores (ver regra abaixo). |
| `imprimeRemetente` | string |  | Indicador se o Remetente deve ser impresso: S (Sim) ou N (não). Default S. | Valores: `S`, `N` — padrão `S` |
| `idsPrePostagem` | array<string> | condicional | IDs das pré-postagens. Campo obrigatório caso não seja informado idAtendimento e codigosObjeto. | Um dos três identificadores (ver regra abaixo). |
| `layoutImpressao` | string |  | Layout de impressão. PADRAO, LINEAR_100_150, LINEAR_100_80, LINEAR_A4, LINEAR_A. Default PADRAO. | Valores: `PADRAO`, `LINEAR_100_150`, `LINEAR_100_80`, `LINEAR_A4`, `LINEAR_A` — padrão `PADRAO` |

### Regras de preenchimento

- **`tipoRotulo` é o único campo obrigatório fixo:** `P` = padrão, `R` = reduzido.
- **Identificação dos objetos — informe pelo menos UM destes:** `idsPrePostagem` (IDs retornados pelo `POST /v1/prepostagens`), `codigosObjeto` (códigos de 13 caracteres, ex.: `AA123456789BR`) ou `idAtendimento`.
- `formatoRotulo`: `ET` = etiqueta (padrão), `EV` = envelope.
- `imprimeRemetente`: `S` (padrão) ou `N`.
- `layoutImpressao`: `PADRAO` (padrão), `LINEAR_100_150`, `LINEAR_100_80`, `LINEAR_A4` ou `LINEAR_A`.
- `idCorreios` e `numeroCartaoPostagem`: identificam quem gerou a pré-postagem.

## 2. Exemplos de corpo

Por IDs de pré-postagem (mais comum logo após criar a pré-postagem):

```json
{
  "idsPrePostagem": [
    "PRxxxxxxxxxxxxxxxx"
  ],
  "tipoRotulo": "P",
  "formatoRotulo": "ET",
  "imprimeRemetente": "S",
  "layoutImpressao": "PADRAO"
}
```

Por códigos de objeto:

```json
{
  "codigosObjeto": [
    "AA123456789BR",
    "AA987654321BR"
  ],
  "tipoRotulo": "P",
  "numeroCartaoPostagem": "0000000000"
}
```

Mínimo válido (segundo o schema):

```json
{
  "idsPrePostagem": [
    "PRxxxxxxxxxxxxxxxx"
  ],
  "tipoRotulo": "P"
}
```

> Os valores de ID e cartão acima são ilustrativos.

## 3. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | object |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

### Resposta 200 — ✔ Observado em homologação

A especificação declara apenas `object`. Na chamada real (homologação, 27/09/2026) a resposta foi:

```json
{"idRecibo":"0bf495ce83074e1ab495ce83073e1a261002"}
```

| Campo | Tipo | Descrição (observada) |
|---|---|---|
| `idRecibo` | string | Identificador do recibo (36 caracteres hexadecimais no teste). É o valor usado no `{idRecibo}` de `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}`; o PDF baixado vem com o nome `idRecibo_<idRecibo>.pdf` (ver arquivo 09). |

Testes realizados, com `tipoRotulo: "P"`, `formatoRotulo: "ET"`, `imprimeRemetente: "S"` e `layoutImpressao: "PADRAO"`:

| Teste | `idsPrePostagem` | Resultado do download |
|---|---|---|
| 1 | SEDEX 03220 (+ um id vazio) | `idRecibo` `0bf495ce…261002` → PDF A4 com 1 etiqueta |
| 2 | SEDEX 03220 + PAC 03298 | `idRecibo` `6dbebb7b…261002` → **um** PDF A4 com 2 etiquetas lado a lado, na ordem da lista |

Cada solicitação gera um **novo** `idRecibo`, mesmo repetindo uma pré-postagem; a etiqueta repetida mantém o mesmo código do objeto (ver arquivo 09).

> ⚠ **Status da pré-postagem — ✔ observado em homologação:** esta solicitação é aceita (HTTP 200 com `idRecibo`) mesmo quando a pré-postagem está em `statusAtual = 7` (PENDENTE), mas o download desse recibo falha com `PPN-288` — e o recibo **continua inválido** depois que o status muda para `2`. Solicite o rótulo **somente com `statusAtual = 2`**; se já tiver solicitado antes, faça uma nova solicitação. Isso ocorre principalmente com pré-postagens criadas pela via assíncrona (arquivo 07).

> **Observação do teste:** a lista `idsPrePostagem` foi enviada com um segundo item **vazio** (a pré-postagem PAC ainda não tinha sido criada) e a API aceitou, gerando só a etiqueta válida. Não dependa desse comportamento: envie apenas ids preenchidos.

### Resposta de erro — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

## 4. Endpoint complementar — download do PDF

### `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}`

**Consulta pelo id do recibo os rótulos solicitados assincronamente pelos métodos POST de geração de objetos simples ou registrados.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}`
- Header: `Authorization: Bearer <token>`

| Parâmetro | Em | Tipo | Obrig. | Descrição |
|---|---|---|:-:|---|
| `idRecibo` | path | string | ✔ | Id recibo |

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | object |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

> A resposta 200 também é declarada só como `object`. Verifique em homologação como o PDF é devolvido (por exemplo, conteúdo em Base64 dentro do JSON) e se há um estado "em processamento" antes de o arquivo ficar pronto.

## 5. JSON Schema completo da requisição

```json
{
  "title": "PrePostagemRotuloRequestInput",
  "type": "object",
  "properties": {
    "codigosObjeto": {
      "type": "array",
      "description": "Códigos dos objetos. Campo obrigatório caso não seja informado idAtendimento e idsPrePostagem.",
      "items": {
        "type": "string"
      }
    },
    "idCorreios": {
      "type": "string",
      "description": "IDCorreios de quem gerou a pré-postagem."
    },
    "numeroCartaoPostagem": {
      "type": "string",
      "description": "Número do cartão de postagem de quem gerou a pré-postagem."
    },
    "tipoRotulo": {
      "type": "string",
      "description": "Tipo de Rótulo: P (padrão) ou R (reduzido)."
    },
    "formatoRotulo": {
      "type": "string",
      "description": "Formato de Rótulo: ET (Etiqueta) ou EV (Envelope). Default ET."
    },
    "idAtendimento": {
      "type": "string",
      "description": "ID do atendimento. Campo obrigatório caso não seja informado codigosObjeto e idsPrePostagem."
    },
    "imprimeRemetente": {
      "type": "string",
      "description": "Indicador se o Remetente deve ser impresso: S (Sim) ou N (não). Default S."
    },
    "idsPrePostagem": {
      "type": "array",
      "description": "IDs das pré-postagens. Campo obrigatório caso não seja informado idAtendimento e codigosObjeto.",
      "items": {
        "type": "string"
      }
    },
    "layoutImpressao": {
      "type": "string",
      "description": "Layout de impressão. PADRAO, LINEAR_100_150, LINEAR_100_80, LINEAR_A4, LINEAR_A. Default PADRAO."
    }
  },
  "required": [
    "tipoRotulo"
  ]
}
```

## 6. Orientações de implementação

1. **Token:** use o Bearer da API Token (endpoint `/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`.
2. **Registrado × simples:** este endpoint é só para objetos **registrados**. Enviar objetos simples aqui tende a gerar erro 400 — para eles use `POST /v1/prepostagens/rotulo/lote/assincrono/pdf`.
3. **Identificação:** prefira `idsPrePostagem`, guardando o `id` retornado ao criar cada pré-postagem. Envie apenas um tipo de identificador por chamada.
4. **Várias etiquetas em um PDF:** envie a lista de IDs/códigos em uma única solicitação para receber um arquivo com todos os rótulos (✔ confirmado: 2 etiquetas lado a lado na mesma folha A4). Para um PDF por objeto, faça uma solicitação por pré-postagem.
5. **Assíncrono:** após o POST, leia `idRecibo` da resposta, grave-o vinculado às pré-postagens e consulte o download com intervalo entre as tentativas (ex.: alguns segundos, com limite de tentativas) até o arquivo estar disponível.

   ```js
   const { idRecibo } = await res.json(); // resposta observada: {"idRecibo":"..."}
   ```
6. **Impressora térmica:** para etiquetas 10×15 cm, avalie `layoutImpressao: LINEAR_100_150`; para 10×8 cm, `LINEAR_100_80`; para folha A4, `PADRAO` ou `LINEAR_A4`. Teste a impressão em homologação.
7. **Validação prévia:** confirme que todas as pré-postagens da lista estão com `statusAtual = 2` (PREPOSTADO); garanta que `tipoRotulo` esteja presente e que ao menos um dos identificadores tenha itens — são as únicas regras de obrigatoriedade do schema. Remova da lista ids vazios ou nulos antes de enviar.
8. **Ambientes:** mantenha a URL base configurável (homologação × produção).
