# Correios — API CEP: `GET /v2/enderecos/{cep}`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/cep/v3/api-docs`), API **CEP** versão **3.15.1** (homologação), em 27/09/2026. As seções 1 a 5 reproduzem a especificação; a seção 6 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 03 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-02-meucontrato-servicos-cartao-api.md` · Próxima: `correios-04-preco-api.md`

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Consulta um endereço |
| **Método / caminho** | `GET /v2/enderecos/{cep}` |
| **URL produção** | `https://api.correios.com.br/cep/v2/enderecos/{cep}` |
| **URL homologação** | `https://apihom.correios.com.br/cep/v2/enderecos/{cep}` |
| **Headers** | `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`) |
| **Corpo da requisição** | Nenhum |
| **operationId** | `consultaEndereco` |
| **Schema da resposta 200** | `EnderecoResponse` |
| **Acesso (conforme a especificação)** | Externo **restrito**, autorização por **contrato**, **exige serviço** habilitado no contrato |

### Para que serve

Retorna o endereço de um CEP (logradouro, bairro, cidade, UF, código IBGE, tipo de CEP etc.). Uso típico: **autopreencher o endereço** do remetente/destinatário antes de criar a pré-postagem e **validar o CEP** antes de consultar Preço e Prazo.

### Fluxo típico

```
1. Usuário digita o CEP (8 dígitos)
2. GET /v2/enderecos/{cep}                 → endereço   ◄ este documento
3. Preenche logradouro, bairro, cidade e UF; usuário completa número e complemento
4. Usa o endereço no POST /v1/prepostagens (remetente/destinatario.endereco)
```

## 1. Parâmetros

| Nome | Em | Tipo | Obrig. | Regra | Descrição (especificação) |
|---|---|---|:-:|---|---|
| `cep` | path | string | ✔ | pattern `[0-9]{8}` (8 dígitos) | Número dos CEPs — Ex.: `24210230` |

### Exemplo de chamada

```http
GET https://apihom.correios.com.br/cep/v2/enderecos/24210230
Authorization: Bearer <token>
Accept: application/json
```

## 2. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `EnderecoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão (contrato sem o serviço de CEP habilitado). | — |

### 2.1 Resposta 200 — todos os campos (caminho completo)

| Campo | Tipo | Descrição |
|---|---|---|
| `cep` | string | CEP: Código de endereçamento postal |
| `uf` | string | Sigla da UF |
| `numeroLocalidade` | integer (int32) | Número da localidade referente ao CEP |
| `localidade` | string | Localidade |
| `logradouro` | string | Logradouro |
| `tipoLogradouro` | string | Tipo de logradouro: avenida, rua, conjunto, fazenda |
| `nomeLogradouro` | string | Nome do logradouro |
| `numeroLogradouro` | string | Número do logradouro |
| `complemento` | string | Complemento |
| `abreviatura` | string | Abreviatura |
| `bairro` | string | Bairro |
| `numeroLocalidadeSuperior` | integer (int32) | Número da localidade superior. |
| `localidadeSuperior` | string | Nome da localidade superior. |
| `nome` | string | Nome de grandes usuários |
| `siglaUnidade` | string | Sigla da unidade |
| `tipoCEP` | integer (int32) | Tipo do CEP. 1: Localidade; 2: Logradouro; 3: Cep Promocional; 4 - Caixa Postal Comunitária; 5 - Grande Usuário; 6 - Unidade Operacional |
| `cepAnterior` | string | Número do CEP anterior |
| `distrito` | string | Distrito Postal |
| `cepUnidadeOperacional` | string | CEP da unidade operacional |
| `lado` | string | Lado |
| `numeroInicial` | integer (int64) | Número inicial |
| `numeroFinal` | integer (int64) | Número final |
| `clique` | string | Indica que é uma unidade de Clique e Retire |
| `caixasPostais` | array<`FaixaCaixaPostalResponse`> |  |
| `caixasPostais[].nuInicial` | integer (int32) | Número inicial |
| `caixasPostais[].nuFinal` | integer (int32) | Número Final |
| `locker` | string | Indica que é uma unidade de locker |
| `agenciaModular` | string | Indica que é uma unidade de agência modular |
| `codigoIbge` | string | Código do IBGE |
| `txMsg` | string | Descricao da mensagem |
| `inSituacaoLocalidade` | string | Situação da localidade: 0: Não codificada. 3: Em Fase de codificação |
| `dtFinalVigencia` | string (date) | Data final de vigência |

### 2.2 Valores codificados

| Campo | Valores |
|---|---|
| `tipoCEP` | `1` Localidade · `2` Logradouro · `3` CEP Promocional · `4` Caixa Postal Comunitária · `5` Grande Usuário · `6` Unidade Operacional |
| `inSituacaoLocalidade` | `0` Não codificada · `3` Em fase de codificação |
| `clique`, `locker`, `agenciaModular` | Indicam se o CEP é de unidade Clique e Retire, locker ou agência modular |

### 2.3 Objetos da resposta

#### `EnderecoResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `cep` | string | CEP: Código de endereçamento postal |
| `uf` | string | Sigla da UF |
| `numeroLocalidade` | integer (int32) | Número da localidade referente ao CEP |
| `localidade` | string | Localidade |
| `logradouro` | string | Logradouro |
| `tipoLogradouro` | string | Tipo de logradouro: avenida, rua, conjunto, fazenda |
| `nomeLogradouro` | string | Nome do logradouro |
| `numeroLogradouro` | string | Número do logradouro |
| `complemento` | string | Complemento |
| `abreviatura` | string | Abreviatura |
| `bairro` | string | Bairro |
| `numeroLocalidadeSuperior` | integer (int32) | Número da localidade superior. |
| `localidadeSuperior` | string | Nome da localidade superior. |
| `nome` | string | Nome de grandes usuários |
| `siglaUnidade` | string | Sigla da unidade |
| `tipoCEP` | integer (int32) | Tipo do CEP. 1: Localidade; 2: Logradouro; 3: Cep Promocional; 4 - Caixa Postal Comunitária; 5 - Grande Usuário; 6 - Unidade Operacional |
| `cepAnterior` | string | Número do CEP anterior |
| `distrito` | string | Distrito Postal |
| `cepUnidadeOperacional` | string | CEP da unidade operacional |
| `lado` | string | Lado |
| `numeroInicial` | integer (int64) | Número inicial |
| `numeroFinal` | integer (int64) | Número final |
| `clique` | string | Indica que é uma unidade de Clique e Retire |
| `caixasPostais` | array<`FaixaCaixaPostalResponse`> |  |
| `locker` | string | Indica que é uma unidade de locker |
| `agenciaModular` | string | Indica que é uma unidade de agência modular |
| `codigoIbge` | string | Código do IBGE |
| `txMsg` | string | Descricao da mensagem |
| `inSituacaoLocalidade` | string | Situação da localidade: 0: Não codificada. 3: Em Fase de codificação |
| `dtFinalVigencia` | string (date) | Data final de vigência |

#### `FaixaCaixaPostalResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `nuInicial` | integer (int32) | Número inicial |
| `nuFinal` | integer (int32) | Número Final |

### 2.4 Exemplo de resposta 200 (esqueleto)

```json
{
  "cep": "string",
  "uf": "string",
  "numeroLocalidade": 0,
  "localidade": "string",
  "logradouro": "string",
  "tipoLogradouro": "string",
  "nomeLogradouro": "string",
  "numeroLogradouro": "string",
  "complemento": "string",
  "abreviatura": "string",
  "bairro": "string",
  "numeroLocalidadeSuperior": 0,
  "localidadeSuperior": "string",
  "nome": "string",
  "siglaUnidade": "string",
  "tipoCEP": 0,
  "cepAnterior": "string",
  "distrito": "string",
  "cepUnidadeOperacional": "string",
  "lado": "string",
  "numeroInicial": 0,
  "numeroFinal": 0,
  "clique": "string",
  "caixasPostais": [
    {
      "nuInicial": 0,
      "nuFinal": 0
    }
  ],
  "locker": "string",
  "agenciaModular": "string",
  "codigoIbge": "string",
  "txMsg": "string",
  "inSituacaoLocalidade": "string",
  "dtFinalVigencia": "2026-01-01"
}
```

### 2.5 Resposta de erro (400/500) — `MessageResponse`

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
| GET | `/v2/enderecos` | Lista endereços |
| GET | `/v2/enderecos/{cep}` | Consulta um endereço |
| GET | `/v1/enderecos` | Deprected: use /v2/enderecos?cep={cep1,cep2} ⚠️ descontinuado |
| GET | `/v1/enderecos/{cep}` | Deprected: use /v2/enderecos/{cep} ⚠️ descontinuado |
| GET | `/v1/enderecos/intervalo` | Deprected: use /v2/enderecos?cepInicial={cepInicial}&cepFinal={cepFinal} ⚠️ descontinuado |

## 4. JSON Schema da resposta 200 (referências expandidas)

```json
{
  "title": "EnderecoResponse",
  "properties": {
    "cep": {
      "type": "string",
      "description": "CEP: Código de endereçamento postal"
    },
    "uf": {
      "type": "string",
      "description": "Sigla da UF"
    },
    "numeroLocalidade": {
      "type": "integer",
      "format": "int32",
      "description": "Número da localidade referente ao CEP"
    },
    "localidade": {
      "type": "string",
      "description": "Localidade"
    },
    "logradouro": {
      "type": "string",
      "description": "Logradouro"
    },
    "tipoLogradouro": {
      "type": "string",
      "description": "Tipo de logradouro: avenida, rua, conjunto, fazenda"
    },
    "nomeLogradouro": {
      "type": "string",
      "description": "Nome do logradouro"
    },
    "numeroLogradouro": {
      "type": "string",
      "description": "Número do logradouro"
    },
    "complemento": {
      "type": "string",
      "description": "Complemento"
    },
    "abreviatura": {
      "type": "string",
      "description": "Abreviatura"
    },
    "bairro": {
      "type": "string",
      "description": "Bairro"
    },
    "numeroLocalidadeSuperior": {
      "type": "integer",
      "format": "int32",
      "description": "Número da localidade superior."
    },
    "localidadeSuperior": {
      "type": "string",
      "description": "Nome da localidade superior."
    },
    "nome": {
      "type": "string",
      "description": "Nome de grandes usuários"
    },
    "siglaUnidade": {
      "type": "string",
      "description": "Sigla da unidade"
    },
    "tipoCEP": {
      "type": "integer",
      "format": "int32",
      "description": "Tipo do CEP. 1: Localidade; 2: Logradouro; 3: Cep Promocional; 4 - Caixa Postal Comunitária; 5 - Grande Usuário; 6 - Unidade Operacional"
    },
    "cepAnterior": {
      "type": "string",
      "description": "Número do CEP anterior"
    },
    "distrito": {
      "type": "string",
      "description": "Distrito Postal"
    },
    "cepUnidadeOperacional": {
      "type": "string",
      "description": "CEP da unidade operacional"
    },
    "lado": {
      "type": "string",
      "description": "Lado"
    },
    "numeroInicial": {
      "type": "integer",
      "format": "int64",
      "description": "Número inicial"
    },
    "numeroFinal": {
      "type": "integer",
      "format": "int64",
      "description": "Número final"
    },
    "clique": {
      "type": "string",
      "description": "Indica que é uma unidade de Clique e Retire"
    },
    "caixasPostais": {
      "type": "array",
      "items": {
        "title": "FaixaCaixaPostalResponse",
        "properties": {
          "nuInicial": {
            "type": "integer",
            "format": "int32",
            "description": "Número inicial"
          },
          "nuFinal": {
            "type": "integer",
            "format": "int32",
            "description": "Número Final"
          }
        }
      }
    },
    "locker": {
      "type": "string",
      "description": "Indica que é uma unidade de locker"
    },
    "agenciaModular": {
      "type": "string",
      "description": "Indica que é uma unidade de agência modular"
    },
    "codigoIbge": {
      "type": "string",
      "description": "Código do IBGE"
    },
    "txMsg": {
      "type": "string",
      "description": "Descricao da mensagem"
    },
    "inSituacaoLocalidade": {
      "type": "string",
      "description": "Situação da localidade: 0: Não codificada. 3: Em Fase de codificação"
    },
    "dtFinalVigencia": {
      "type": "string",
      "format": "date",
      "description": "Data final de vigência"
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

1. **Token:** use o Bearer da API Token e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita. Se receber 403, verifique se o serviço de CEP está habilitado no contrato.
2. **Validação local antes da chamada:** remova tudo que não for dígito e confirme 8 dígitos (`^[0-9]{8}$`). Não chame a API com CEP incompleto.
3. **Mapeamento para a Pré-postagem (`EnderecoRemetenteDTO` / `EnderecoDestinatarioDTO`):**

   | Pré-postagem | API CEP |
   |---|---|
   | `cep` | `cep` |
   | `logradouro` | `logradouro` |
   | `bairro` | `bairro` |
   | `cidade` | `localidade` |
   | `uf` | `uf` |
   | `numero`, `complemento` | informados pelo usuário |

4. **CEP de localidade (`tipoCEP = 1`):** cidades com CEP único podem retornar sem logradouro e bairro — deixe esses campos editáveis e obrigatórios no formulário.
5. **Grande usuário / caixa postal / unidade (`tipoCEP` 4, 5 ou 6):** o endereço pode ser de empresa, caixa postal ou unidade dos Correios — use `nome` e `caixasPostais` quando aplicável e permita ao usuário revisar.
6. **CEP não encontrado ou inválido:** trate o 400 exibindo `msgs` e permitindo preenchimento manual do endereço.
7. **Cache:** endereços de CEP mudam raramente — armazene em cache por CEP (ex.: alguns dias) para reduzir chamadas.
8. **`codigoIbge`:** guarde junto do endereço; é útil para a API Prazo (`prazobaselote`) e para documentos fiscais.
9. **Ambientes:** mantenha a URL base configurável (homologação × produção).
