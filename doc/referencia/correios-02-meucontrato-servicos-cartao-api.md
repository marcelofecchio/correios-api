# Correios — Meu Contrato: `GET /v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/meucontrato/v3/api-docs`), API **Meu Contrato** versão **1.1.8** (homologação), em 27/09/2026. As seções 1 a 5 reproduzem a especificação; a seção 6 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 02 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-01-token-api.md` · Próxima: `correios-03-cep-enderecos-api.md`

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Consulta os serviços de um cartão de postagem |
| **Método / caminho** | `GET /v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` |
| **URL produção** | `https://api.correios.com.br/meucontrato/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` |
| **URL homologação** | `https://apihom.correios.com.br/meucontrato/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` |
| **Headers** | `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`) |
| **Corpo da requisição** | Nenhum |
| **operationId** | `listaServicosCartao` |
| **Schema da resposta 200** | `PageServicoResponse` (paginado) |
| **Acesso (conforme a especificação)** | Externo **restrito**, autorização por **contrato** |

### Para que serve

Lista os **serviços habilitados no cartão de postagem** (ex.: PAC, SEDEX e demais serviços do contrato), com código, descrição e segmento. O `codigo` retornado é o valor usado em:

- `codigoServico` do `POST /v1/prepostagens` (API Pré-postagem) — que exige um serviço presente no cartão;
- `coProduto` das APIs **Preço** e **Prazo**.

### Fluxo típico

```
1. POST /v1/autentica/cartaopostagem (API Token)  → token Bearer
2. GET  .../cartoes/{nuCartaoPostagem}/servicos    → lista de serviços do cartão   ◄ este documento
3. Guardar/atualizar a tabela local de serviços
4. Usar o código do serviço em Preço, Prazo e Pré-postagem
```

## 1. Parâmetros

| Nome | Em | Tipo | Obrig. | Padrão | Descrição (especificação) |
|---|---|---|:-:|---|---|
| `cnpj` | path | string | ✔ |  | CNPJ |
| `nuContrato` | path | string | ✔ |  | Número do contrato |
| `nuCartaoPostagem` | path | string | ✔ |  | Número do cartão de postagem |
| `page` | query | integer (int32) |  | `0` | Numero da pagina. Inicia com 0 |
| `size` | query | integer (int32) |  | `50` | Tamanho da pagina |

- `cnpj`: CNPJ da empresa titular do contrato, somente dígitos (14).
- `nuContrato`: número do contrato com os Correios.
- `nuCartaoPostagem`: número do cartão de postagem vinculado ao contrato.
- `page`: começa em **0**.
- `size`: itens por página (padrão 50).

### Exemplo de chamada

```http
GET https://apihom.correios.com.br/meucontrato/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos?page=0&size=50
Authorization: Bearer <token>
Accept: application/json
```

## 2. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PageServicoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão informado. | — |

### 2.1 Resposta 200 — `PageServicoResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `itens` | objeto `ServicoResponse` |  |
| `page` | objeto `PageInfo` |  |

#### `ServicoResponse` (itens)

| Campo | Tipo | Descrição |
|---|---|---|
| `codigo` | string | Código do serviço. |
| `descricao` | string | Descrição do serviço. |
| `coSegmento` | string | Segmento do serviço. |
| `descSegmento` | string | Descrição do segmento. |

#### `PageInfo` (page)

| Campo | Tipo | Descrição |
|---|---|---|
| `size` | integer (int64) | Tamanho máximo de cada página |
| `totalElements` | integer (int64) | Numero total de itens |
| `totalPages` | integer (int64) | Numero total de páginas |
| `number` | integer (int64) | Numero da página atual |

### 2.2 Todos os campos (caminho completo)

| Campo | Tipo | Descrição |
|---|---|---|
| `itens.codigo` | string | Código do serviço. |
| `itens.descricao` | string | Descrição do serviço. |
| `itens.coSegmento` | string | Segmento do serviço. |
| `itens.descSegmento` | string | Descrição do segmento. |
| `page.size` | integer (int64) | Tamanho máximo de cada página |
| `page.totalElements` | integer (int64) | Numero total de itens |
| `page.totalPages` | integer (int64) | Numero total de páginas |
| `page.number` | integer (int64) | Numero da página atual |

> **Atenção — possível imprecisão da especificação:** o campo `itens` está declarado como um único objeto `ServicoResponse`, e não como lista. Como o endpoint é paginado e lista os serviços do cartão, o retorno real deve ser uma **lista** de `ServicoResponse`. Confirme em homologação e trate os dois formatos (ver seção 6).

### 2.3 Exemplo de resposta 200 (ilustrativo)

```json
{
  "itens": [
    {
      "codigo": "<código do serviço>",
      "descricao": "<descrição do serviço>",
      "coSegmento": "<código do segmento>",
      "descSegmento": "<descrição do segmento>"
    }
  ],
  "page": {
    "size": 50,
    "totalElements": 1,
    "totalPages": 1,
    "number": 0
  }
}
```

### 2.4 Resposta de erro (400/500) — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

## 3. Endpoints relacionados (mesma API)

| Método | Caminho | Resumo |
|---|---|---|
| GET | `/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes` | Lista os cartões de postagem de um contrato |
| GET | `/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}` | Consulta um cartão de postagem de um contrato |
| GET | `/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` | Consulta os serviços de um cartão de postagem |
| GET | `/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos/{coServico}` | Consulta um código de serviço no cartão de postagem |

## 4. JSON Schema da resposta 200 (referências expandidas)

```json
{
  "title": "PageServicoResponse",
  "type": "object",
  "properties": {
    "itens": {
      "title": "ServicoResponse",
      "properties": {
        "codigo": {
          "type": "string",
          "description": "Código do serviço."
        },
        "descricao": {
          "type": "string",
          "description": "Descrição do serviço."
        },
        "coSegmento": {
          "type": "string",
          "description": "Segmento do serviço."
        },
        "descSegmento": {
          "type": "string",
          "description": "Descrição do segmento."
        }
      }
    },
    "page": {
      "title": "PageInfo",
      "properties": {
        "size": {
          "type": "integer",
          "format": "int64",
          "description": "Tamanho máximo de cada página"
        },
        "totalElements": {
          "type": "integer",
          "format": "int64",
          "description": "Numero total de itens"
        },
        "totalPages": {
          "type": "integer",
          "format": "int64",
          "description": "Numero total de páginas"
        },
        "number": {
          "type": "integer",
          "format": "int64",
          "description": "Numero da página atual"
        }
      }
    }
  }
}
```

## 5. JSON Schema do erro

```json
{
  "title": "MessageResponse",
  "properties": {
    "msgs": {
      "type": "array",
      "description": "Mensagens",
      "items": {
        "type": "string",
        "description": "Mensagens"
      }
    },
    "date": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de ocorrência do evento."
    },
    "method": {
      "type": "string",
      "description": "Método HTTP que foi requisitado."
    },
    "path": {
      "type": "string",
      "description": "URL que foi requisitada."
    },
    "causa": {
      "type": "string",
      "description": "A causa da mensagem."
    },
    "stackTrace": {
      "type": "string",
      "description": "Detalhe da mensagem."
    }
  }
}
```

## 6. Orientações de implementação

1. **Token:** use um Bearer com acesso ao contrato/cartão (API Token — `/v1/autentica/cartaopostagem` ou `/v1/autentica/contrato`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita.
2. **Parâmetros:** CNPJ, contrato e cartão somente com dígitos; guarde-os em configuração (não fixos no código).
3. **Paginação:** comece em `page=0` e avance enquanto `page.number < page.totalPages - 1`, acumulando os itens.
4. **Formato de `itens`:** trate a resposta de forma defensiva — se `itens` vier como objeto, converta para lista de um elemento; se vier como lista, use diretamente.
5. **Tabela local de serviços:** grave `codigo`, `descricao`, `coSegmento` e `descSegmento` no banco e atualize periodicamente (ex.: diariamente ou ao iniciar a aplicação). Use essa tabela para preencher combos de seleção e validar `codigoServico` antes de chamar a Pré-postagem.
6. **Códigos como texto:** mantenha `codigo` como string, preservando zeros à esquerda (ex.: `"03298"`).
7. **Integração:** o mesmo `codigo` alimenta `coProduto` (Preço/Prazo) e `codigoServico` (Pré-postagem) — centralize esse mapeamento.
8. **Detalhe de um serviço:** para consultar apenas um serviço, use `GET .../servicos/{coServico}`.
9. **Ambientes:** mantenha a URL base configurável (homologação × produção); os dados de contrato/cartão de homologação podem diferir dos de produção.
