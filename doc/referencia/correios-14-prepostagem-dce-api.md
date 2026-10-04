# Correios — Pré-postagem: Emissão de DCe (`POST /v1/prepostagens/dce/dace/impressao` e `PATCH /v1/prepostagens/dce`)

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 03/10/2026. Cobre os 2 endpoints do grupo **"Emissão de DCe"**. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 14 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-13-prepostagem-cancelar-api.md` · Última etapa
>
> **Ainda não testado em homologação:** o conteúdo real de `dados` em cada `tipoDace` e o corpo da resposta 200 do `PATCH` devem ser registrados aqui após o primeiro teste.

## 0. Resumo rápido

- **DCe** = Declaração de Conteúdo Eletrônica: o documento fiscal que acompanha o objeto quando **não há nota fiscal** (substitui a declaração de conteúdo em papel).
- **DACE** = documento auxiliar da DCe: a versão **impressa** que vai junto do objeto.
- **Autenticação:** `Authorization: Bearer <token>` — API Token (`/v1/autentica/cartaopostagem`). Ver `correios-01-token-api.md`.
- **Formato:** JSON (`Content-Type: application/json`) nos dois endpoints.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/prepostagem` |
| Homologação | `https://apihom.correios.com.br/prepostagem` |

| # | Método | Caminho | Função | operationId | Resposta 200 |
|---|---|---|---|---|---|
| 1 | POST | `/v1/prepostagens/dce/dace/impressao` | Solicita impressão de Dace de prepostagem com DCe gerado internamente. Retorna dados (base64) para impressão PDF por tipo de impressão. Para o tipo Térmico, retorna texto para impressão direta. | `geraDace` | `ResponseDace` |
| 2 | PATCH | `/v1/prepostagens/dce` | Permite atualizar prepostagem com chave de documento fiscal(DCe ou NFe). | `atualizaDocumentoFiscal` | sem corpo definido |

### Dois caminhos para o documento fiscal

| Caminho | Quem emite a DCe | O que a aplicação faz |
|---|---|---|
| **A — DCe emitida pelos Correios** ("gerado internamente") | Correios | Cria a pré-postagem com `emiteDCe: "S"` e `itensDeclaracaoConteudo` (arquivo 06) → imprime a DACE com o endpoint **1** |
| **B — documento emitido pelo cliente** | O próprio cliente (DCe emitida em outro sistema, ou NF-e) | Informa a chave de 44 dígitos: na criação (`chaveNFe`, arquivo 06) **ou depois**, com o endpoint **2** |

### Fluxos

```
CAMINHO A — DCe pelos Correios
1. POST  /v1/prepostagens  (emiteDCe = "S", itensDeclaracaoConteudo preenchido)  → id + codigoObjeto
2. POST  /v1/prepostagens/dce/dace/impressao  (ids ou códigos + tipoDace)         → { objetos, dados }
3. Decodificar/imprimir "dados" e enviar a DACE junto com o objeto

CAMINHO B — chave informada depois
1. POST  /v1/prepostagens                                   → id + codigoObjeto
2. (cliente emite a DCe ou a NF-e em seu sistema)
3. PATCH /v1/prepostagens/dce  (codigoObjeto + chaveNFe)     → 200
```

## 1. `POST /v1/prepostagens/dce/dace/impressao`

**Solicita impressão de Dace de prepostagem com DCe gerado internamente. Retorna dados (base64) para impressão PDF por tipo de impressão. Para o tipo Térmico, retorna texto para impressão direta.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/dce/dace/impressao`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/dce/dace/impressao`
- Headers: `Authorization: Bearer <token>` · `Content-Type: application/json`
- operationId: `geraDace`
- Parâmetros de URL: nenhum

### 1.1 Corpo da requisição — `RequestImpressaoDaceDTO` (obrigatório)

Requisição de impressao de Dace de prepostagens por objetos ou por ids da pré-postagem. Obrigatório Informar codigosObjetos ou idsPrePostagens.

| Campo | Tipo | Obrig. | Descrição (especificação) | Valores / regras |
|---|---|:-:|---|---|
| `codigosObjetos` | array<string> | condicional | Códigos dos identificadores dos objetos. Obrigatório se não for informado idsPrePostagens. | Códigos de 13 caracteres |
| `idsPrePostagens` | array<string> | condicional | Identificadores das pré-postagens. Obrigatório se não for informado codigosObjetos. | Ids `PR...` devolvidos na criação |
| `tipoDace` | string | ✔ | Tipo de impressão para Dace: T(Térmica), R(Resumida) ou C(Completa). Exemplo: T. | pattern `^[TRC]$` |

**`tipoDace`**

| Valor | Tipo | Conteúdo de `dados` (segundo o resumo do endpoint) |
|---|---|---|
| `T` | Térmica | **Texto** para impressão direta em impressora térmica |
| `R` | Resumida | PDF em Base64 |
| `C` | Completa | PDF em Base64 |

> Atenção aos nomes dos campos: aqui são `codigosObjetos` e `idsPrePostagens` (plural nos dois termos) — diferente do rótulo (arquivo 08), que usa `codigosObjeto` e `idsPrePostagem`.

### 1.2 Exemplos de corpo

Por ids de pré-postagem, DACE completa em PDF:

```json
{
  "idsPrePostagens": [
    "PRxxxxxxxxxxxxxxxxxxxxxx"
  ],
  "tipoDace": "C"
}
```

Por código do objeto, para impressora térmica:

```json
{
  "codigosObjetos": [
    "AA123456789BR"
  ],
  "tipoDace": "T"
}
```

### 1.3 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `ResponseDace` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

**`ResponseDace`**

| Campo | Tipo | Descrição (especificação) |
|---|---|---|
| `objetos` | array<string> | identificadores dos objetos relativos aos dados de impressão retornados. |
| `dados` | string | Dados para impressão. |

```json
{
  "objetos": [
    "AA123456789BR"
  ],
  "dados": "JVBERi0xLjQK..."
}
```

> O exemplo é ilustrativo. Um PDF em Base64 começa com `JVBERi0` (`%PDF-`), como no download do rótulo (arquivo 09). No tipo `T` espera-se texto de comandos da impressora — o formato exato deve ser confirmado em teste.

## 2. `PATCH /v1/prepostagens/dce`

**Permite atualizar prepostagem com chave de documento fiscal(DCe ou NFe).**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/dce`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/dce`
- Headers: `Authorization: Bearer <token>` · `Content-Type: application/json`
- operationId: `atualizaDocumentoFiscal`
- Parâmetros de URL: nenhum (a pré-postagem é identificada pelo `codigoObjeto` no corpo)

### 2.1 Corpo da requisição — `RequestAtualizaDce` (obrigatório)

Requisição alteração de prepostagem para inclusão de chave de documento fiscal.

| Campo | Tipo | Obrig. | Descrição (especificação) | Regras |
|---|---|:-:|---|---|
| `codigoObjeto` | string | ✔ | Código de objeto a ser atualizado. | pattern `^[A-Z]{2}\d{9}[A-Z]{2}$` |
| `chaveNFe` | string | ✔ | Chave do documento fiscal: NFe ou DCe. | minLength `1` (chaves de NF-e e DCe têm 44 dígitos) |
| `numeroNotaFiscal` | string |  | Número da nota fiscal que acompanhará o objeto pré-postado. Tamanho máximo: 12 caracteres numéricos. | até 12 dígitos |

- Apesar do nome, `chaveNFe` recebe tanto a chave da **NF-e** quanto a da **DCe**.
- A identificação é **somente** pelo código do objeto — não há opção por `id` da pré-postagem.

### 2.2 Exemplo de corpo

```json
{
  "codigoObjeto": "AA123456789BR",
  "chaveNFe": "35261011222333000181550010000012341000012345",
  "numeroNotaFiscal": "1234"
}
```

> A chave acima é fictícia (44 dígitos, apenas para mostrar o formato).

### 2.3 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | — (a especificação não define corpo) |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

## 3. Resposta de erro (400/500) — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

> Os códigos 401 e 403 não constam na especificação destes endpoints; são o comportamento geral das APIs. Outros endpoints desta API também devolveram **422** para regras de negócio e erro no formato `{ "mensagem": "PPN-xxx: ..." }` (arquivos 06 e 09) — trate os dois formatos.

## 4. JSON Schemas (referências expandidas)

### `RequestImpressaoDaceDTO` (corpo do endpoint 1)

```json
{
  "title": "RequestImpressaoDaceDTO",
  "type": "object",
  "description": "Requisição de impressao de Dace de prepostagens por objetos ou por ids da pré-postagem. Obrigatório Informar codigosObjetos ou idsPrePostagens.",
  "required": ["tipoDace"],
  "properties": {
    "codigosObjetos": {
      "type": "array",
      "description": "Códigos dos identificadores dos objetos. Obrigatório se não for informado idsPrePostagens.",
      "items": { "type": "string" }
    },
    "idsPrePostagens": {
      "type": "array",
      "description": "Identificadores das pré-postagens. Obrigatório se não for informado codigosObjetos.",
      "items": { "type": "string" }
    },
    "tipoDace": {
      "type": "string",
      "description": "Tipo de impressão para Dace: T(Térmica), R(Resumida) ou C(Completa). Exemplo: T.",
      "pattern": "^[TRC]$"
    }
  }
}
```

### `ResponseDace` (resposta 200 do endpoint 1)

```json
{
  "title": "ResponseDace",
  "type": "object",
  "properties": {
    "objetos": {
      "type": "array",
      "description": "identificadores dos objetos relativos aos dados de impressão retornados.",
      "items": { "type": "string" }
    },
    "dados": { "type": "string", "description": "Dados para impressão." }
  }
}
```

### `RequestAtualizaDce` (corpo do endpoint 2)

```json
{
  "title": "RequestAtualizaDce",
  "type": "object",
  "description": "Requisição alteração de prepostagem para inclusão de chave de documento fiscal.",
  "required": ["codigoObjeto", "chaveNFe"],
  "properties": {
    "codigoObjeto": {
      "type": "string",
      "description": "Código de objeto a ser atualizado.",
      "pattern": "^[A-Z]{2}\\d{9}[A-Z]{2}$"
    },
    "chaveNFe": {
      "type": "string",
      "description": "Chave do documento fiscal: NFe ou DCe.",
      "minLength": 1
    },
    "numeroNotaFiscal": {
      "type": "string",
      "description": "Número da nota fiscal que acompanhará o objeto pré-postado. Tamanho máximo: 12 caracteres numéricos."
    }
  }
}
```

### Campos relacionados na criação da pré-postagem (arquivo 06)

| Campo | Onde | Descrição (especificação) |
|---|---|---|
| `emiteDCe` | requisição | Indicador se a DCe - Declaração de Conteúdo Eletrônica deve ser emitida pelos Correios ou não. Valores possíveis: S ou N. |
| `itensDeclaracaoConteudo` | requisição | Declaração de conteúdo (itens com `conteudo`, `quantidade`, `valor`). |
| `chaveNFe` | requisição | Chave da Nota Fiscal Eletrônica. Obrigatório, na ausência da declaração de conteúdo ou NF. Se informado, deverá conter 44 caracteres numéricos. |
| `motivoCancelamento` | resposta | Erro em geração de DCe que causou eventual cancelamento de prepostagem. |

## 5. Orientações de implementação

1. **Token:** use o Bearer da API Token (`/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita.
2. **Escolha do caminho:** venda com NF-e → informe a chave (na criação ou pelo `PATCH`). Envio sem nota → DCe; deixe os Correios emitirem (`emiteDCe: "S"`) ou emita no seu sistema e informe a chave pelo `PATCH`. Não use os dois caminhos para o mesmo objeto.
3. **A DACE só existe para DCe "gerada internamente":** o endpoint de impressão atende pré-postagens criadas com `emiteDCe: "S"`. Para objetos com NF-e ou com DCe emitida fora dos Correios, espere erro — o documento auxiliar, nesses casos, é o do emissor.
4. **Falha na geração da DCe pode cancelar a pré-postagem:** a resposta da pré-postagem tem o campo `motivoCancelamento` ("Erro em geração de DCe que causou eventual cancelamento de prepostagem"). Depois de criar com `emiteDCe: "S"`, confira `statusAtual` e `motivoCancelamento` antes de pedir a DACE e o rótulo. Como a geração pode não ser imediata, se a impressão falhar logo após a criação, aguarde e tente de novo com intervalo crescente.
5. **Dados da declaração:** com `emiteDCe: "S"`, preencha `itensDeclaracaoConteudo` com descrições reais, quantidades e valores unitários corretos, e CPF/CNPJ e endereços completos de remetente e destinatário — são os dados que vão para o documento fiscal.
6. **Tratamento de `dados` por tipo:**
   - `R` e `C` → decodifique de Base64 para bytes, valide que começam com `%PDF` e grave/exiba como `.pdf` (mesma lógica do rótulo, arquivo 09);
   - `T` → texto para envio direto à impressora térmica. **Não** trate como PDF. Antes de fixar o código, verifique em teste se o texto vem puro ou também em Base64.
7. **Vários objetos:** a requisição aceita listas, mas a resposta tem um único campo `dados`. Use `objetos` para saber quais entraram na impressão; para um arquivo por objeto, faça uma requisição por objeto.
8. **`PATCH` — validação local:** código do objeto no padrão `^[A-Z]{2}\d{9}[A-Z]{2}$` (sem espaços); chave com 44 dígitos numéricos e dígito verificador válido; `numeroNotaFiscal` com até 12 dígitos. Envie apenas dígitos, sem pontos ou espaços.
9. **`PATCH` — momento:** atualize a chave **antes da postagem**, enquanto a pré-postagem está em `statusAtual = 2`. A especificação não informa se a chave pode ser trocada depois de informada nem se é aceita após a postagem — confirme em teste e trate o erro.
10. **Resposta do `PATCH`:** como não há corpo definido, considere sucesso pelo HTTP 200 e registre a chave no seu sistema junto do objeto.
11. **Ordem recomendada na expedição:** criar pré-postagem → (DCe pronta) imprimir DACE → solicitar e imprimir rótulo → postar. O objeto deve seguir com o documento fiscal (DACE ou DANFE).
12. **Ambientes:** mantenha a URL base configurável (homologação × produção); ids, códigos e documentos de um ambiente não valem no outro.
