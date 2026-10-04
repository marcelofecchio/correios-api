# Correios — API Token (autenticação)

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/token/v3/api-docs`), versão **1.22.3**, em 25/09/2026. As seções 1 a 5 reproduzem a especificação; a seção 6 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 01 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-00-instrucoes-gerais.md` · Próxima: `correios-02-meucontrato-servicos-cartao-api.md`

## 0. Resumo rápido

- **Finalidade:** gerar o token JWT (Bearer) exigido por todas as demais APIs dos Correios (Pré-postagem, Preço, Prazo, Rastro, etc.).
- **Autenticação da chamada:** HTTP Basic — `Authorization: Basic base64(usuario:codigoDeAcesso)`, onde a senha é o **código de acesso** gerado no portal CWS do mesmo ambiente e o usuário depende do ambiente:
  - **Homologação:** usuário = **CNPJ** da empresa, somente dígitos (14 caracteres). ⚠ Observação prática — não consta na especificação nem no manual V2.4.
  - **Produção:** usuário = login do **Meu Correios**, conforme a especificação.
- **Uso do token nas outras APIs:** `Authorization: Bearer <token>`.
- **Validade:** informada no campo `expiraEm` da resposta. Reutilize o token até perto do vencimento.
- **Limite:** 3 requisições por segundo; acima disso retorna HTTP **429**.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/token` |
| Homologação | `https://apihom.correios.com.br/token` |

| Método | Caminho | Quando usar |
|---|---|---|
| POST | `/v1/autentica` | Gera um token para acesso a APIs, cuja autorização foi concedida diretamente para usuário idCorreios |
| POST | `/v1/autentica/contrato` | Gera um token para acesso de APIS, cuja autorização seja pelo número de contrato |
| POST | `/v1/autentica/cartaopostagem` | Gera um token para acesso a APIs, cuja autorização seja por cartão de postagem |

## 1. Instruções oficiais da API

> ⚠ **Observação prática (homologação):** o texto oficial abaixo fala em "usuário (Meu Correios)", mas em **homologação** o usuário aceito no Basic é o **CNPJ** (somente dígitos). Ver seção 4.2 de `correios-00-instrucoes-gerais.md`.

Serviço de geração de token, para acesso a diversas APIs dos Correios.
<br></br>
<b>Geração do token</b>
<ul>
    <li>Para solicitar um token é necessário fazer uma requisição com <b>'Authorization: Basic'</b>, passando o usuário (Meu Correios) e senha (código de acesso).</li>
</ul>
<b>Expiração do token</b>
<ul>
    <li>O token obtido tem uma data de expiração no atributo <b>'expiraEm'</b>, com isso o mesmo token pode ser utilizado até a data de expiração.</li>
    <li>Recomendamos que solicite um novo token próximo da data de expiração, alguns minutos antes do token expirar.</li>
    <li>Será devolvido um <b>NOVO</b> token quando:</li>
    <ul>
        <li>O token estiver expirado;</li>
        <li>Caso haja alguma alteração nas permissões de acesso;</li>
        <li>Dentro da tolerância de até <b>30 minutos</b> antes da data de expiração.</li>
        <li>Nos demais casos, será devolvido um token já solicitado.</li>
    </ul>
</ul>
<b>Limite de solicitações</b>
<ul>
    <li>A API 'token' tem um limite de  <b>3</b> solitações por segundo.</li>
    <li>Caso ultrapasse esse limite é disparado status HTTP 429 - Too Many Requests.</li>
    <li>Para evitar receber o status HTTP 429, sempre verifique a data de expiração do token antes de enviar uma nova solicitação.</li>
</ul>

## 2. Endpoints

### 2.1 `POST /v1/autentica`

**Gera um token para acesso a APIs, cuja autorização foi concedida diretamente para usuário idCorreios**

- URL (produção): `https://api.correios.com.br/token/v1/autentica`
- URL (homologação): `https://apihom.correios.com.br/token/v1/autentica`
- Header: `Authorization: Basic <base64(usuario:codigoDeAcesso)>` — usuário = CNPJ em homologação; login do Meu Correios em produção
- operationId: `token`

**Corpo da requisição:** nenhum (apenas o header Basic).

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Token gerado com sucesso | `Token` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `401` | Não autenticado | `MessageResponse` |
| `403` | Acesso negado | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Exemplo de resposta 201:

```json
{
  "ambiente": "PRODUCAO",
  "id": "string",
  "ip": "string",
  "perfil": "S",
  "cnpj": "string",
  "pjInternacional": 0,
  "cpf": "string",
  "categoria": "string",
  "chv": 0,
  "cie": "string",
  "certificado": {
    "cn": "string"
  },
  "emissao": "2026-01-01T00:00:00",
  "expiraEm": "2026-01-01T00:00:00",
  "zoneOffset": "string",
  "contrato": {
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "cartaoPostagem": {
    "contrato": "string",
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "api": [
    0
  ],
  "apiGrupos": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "apis": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "paths": [
    "string"
  ],
  "tipoUnidade": {
    "franqueada": "string",
    "loja": "string",
    "locker": "string",
    "modular": "string",
    "pontoColeta": "string",
    "essencial": "string",
    "comunitaria": "string",
    "maquinaFranquear": "string",
    "outras": "string"
  },
  "token": "string"
}
```

### 2.2 `POST /v1/autentica/contrato`

**Gera um token para acesso de APIS, cuja autorização seja pelo número de contrato**

- URL (produção): `https://api.correios.com.br/token/v1/autentica/contrato`
- URL (homologação): `https://apihom.correios.com.br/token/v1/autentica/contrato`
- Header: `Authorization: Basic <base64(usuario:codigoDeAcesso)>` — usuário = CNPJ em homologação; login do Meu Correios em produção
- Header: `Content-Type: application/json`
- operationId: `tokenPorContrato`

**Corpo da requisição** (obrigatório) — Dados do contrato — schema `ContratoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do contrato — minLength: `1` |
| `dr` | integer (int64) |  | Número do Regional/DR/SE do contrato |

```json
{
  "numero": "string",
  "dr": 0
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Token gerado com sucesso | `Token` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `401` | Não autenticado | `MessageResponse` |
| `403` | Acesso negado | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Exemplo de resposta 201:

```json
{
  "ambiente": "PRODUCAO",
  "id": "string",
  "ip": "string",
  "perfil": "S",
  "cnpj": "string",
  "pjInternacional": 0,
  "cpf": "string",
  "categoria": "string",
  "chv": 0,
  "cie": "string",
  "certificado": {
    "cn": "string"
  },
  "emissao": "2026-01-01T00:00:00",
  "expiraEm": "2026-01-01T00:00:00",
  "zoneOffset": "string",
  "contrato": {
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "cartaoPostagem": {
    "contrato": "string",
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "api": [
    0
  ],
  "apiGrupos": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "apis": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "paths": [
    "string"
  ],
  "tipoUnidade": {
    "franqueada": "string",
    "loja": "string",
    "locker": "string",
    "modular": "string",
    "pontoColeta": "string",
    "essencial": "string",
    "comunitaria": "string",
    "maquinaFranquear": "string",
    "outras": "string"
  },
  "token": "string"
}
```

### 2.3 `POST /v1/autentica/cartaopostagem`

**Gera um token para acesso a APIs, cuja autorização seja por cartão de postagem**

- URL (produção): `https://api.correios.com.br/token/v1/autentica/cartaopostagem`
- URL (homologação): `https://apihom.correios.com.br/token/v1/autentica/cartaopostagem`
- Header: `Authorization: Basic <base64(usuario:codigoDeAcesso)>` — usuário = CNPJ em homologação; login do Meu Correios em produção
- Header: `Content-Type: application/json`
- operationId: `tokenPorCartao`

**Corpo da requisição** (obrigatório) — Dados do cartão de postagem/contrato — schema `CartaoPostagemInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do cartão de postagem — minLength: `1` |
| `contrato` | string |  | Número do contrato |
| `dr` | integer (int64) |  | Número do Regional/DR/SE do contrato |

```json
{
  "numero": "string",
  "contrato": "string",
  "dr": 0
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Token gerado com sucesso | `Token` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `401` | Não autenticado | `MessageResponse` |
| `403` | Acesso negado | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Exemplo de resposta 201:

```json
{
  "ambiente": "PRODUCAO",
  "id": "string",
  "ip": "string",
  "perfil": "S",
  "cnpj": "string",
  "pjInternacional": 0,
  "cpf": "string",
  "categoria": "string",
  "chv": 0,
  "cie": "string",
  "certificado": {
    "cn": "string"
  },
  "emissao": "2026-01-01T00:00:00",
  "expiraEm": "2026-01-01T00:00:00",
  "zoneOffset": "string",
  "contrato": {
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "cartaoPostagem": {
    "contrato": "string",
    "numero": "string",
    "dr": 0,
    "api": [
      0
    ],
    "apis": [
      {
        "api": 0,
        "grupos": [
          {
            "co": "string",
            "urls": [
              "string"
            ],
            "op": "L"
          }
        ],
        "op": "string",
        "un": false,
        "paths": [
          "string"
        ]
      }
    ]
  },
  "api": [
    0
  ],
  "apiGrupos": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "apis": [
    {
      "api": 0,
      "grupos": [
        {
          "co": "string",
          "urls": [
            "string"
          ],
          "op": "L"
        }
      ],
      "op": "string",
      "un": false,
      "paths": [
        "string"
      ]
    }
  ],
  "paths": [
    "string"
  ],
  "tipoUnidade": {
    "franqueada": "string",
    "loja": "string",
    "locker": "string",
    "modular": "string",
    "pontoColeta": "string",
    "essencial": "string",
    "comunitaria": "string",
    "maquinaFranquear": "string",
    "outras": "string"
  },
  "token": "string"
}
```

## 3. Schemas (todos os campos expandidos)

### `ApiToken`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `api` | integer (int32) |  | Número da api |
| `grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `grupos[].co` | string |  | Código do grupo |
| `grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `un` | boolean |  | Indica unidade de negócio externa |
| `paths` | array<string> |  |  |

### `CartaoPostagem`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `contrato` | string |  | Número do contrato |
| `numero` | string |  | Número do Cartão de Postagem |
| `dr` | integer (int64) |  | DR/SE do contrato |
| `api` | array<integer (int32)> |  | Atributo será removido em breve |
| `apis` | array<objeto `ApiToken`> |  | Lista de APIs restritas com autorização de acesso |
| `apis[].api` | integer (int32) |  | Número da api |
| `apis[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `apis[].grupos[].co` | string |  | Código do grupo |
| `apis[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `apis[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `apis[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `apis[].un` | boolean |  | Indica unidade de negócio externa |
| `apis[].paths` | array<string> |  |  |

### `Certificado`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `cn` | string |  | Common name do certificado ICP-BRASIL |

### `Contrato`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string |  | Número do contrato |
| `dr` | integer (int64) |  | DR/SE do contrato |
| `api` | array<integer (int32)> |  | Atributo será removido em breve |
| `apis` | array<objeto `ApiToken`> |  | Lista de APIs restritas com autorização de acesso |
| `apis[].api` | integer (int32) |  | Número da api |
| `apis[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `apis[].grupos[].co` | string |  | Código do grupo |
| `apis[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `apis[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `apis[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `apis[].un` | boolean |  | Indica unidade de negócio externa |
| `apis[].paths` | array<string> |  |  |

### `GrupoToken`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `co` | string |  | Código do grupo |
| `urls` | array<string> |  | Lista das urls permitidas |
| `op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |

### `TipoUnidade`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `franqueada` | string |  |  |
| `loja` | string |  |  |
| `locker` | string |  |  |
| `modular` | string |  |  |
| `pontoColeta` | string |  |  |
| `essencial` | string |  |  |
| `comunitaria` | string |  |  |
| `maquinaFranquear` | string |  |  |
| `outras` | string |  |  |

### `Token`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `ambiente` | string |  | Ambiente — Valores: `PRODUCAO`, `HOMOLOGACAO`, `DESENVOLVIMENTO`, `LOCAL` |
| `id` | string |  | Identificador do usuário |
| `ip` | string |  | IP do requisitante |
| `perfil` | string |  | Perfil: PF: Pessoa Física, PJ: Pessoa Jurídica — Valores: `S`, `PJ`, `PF`, `A` |
| `cnpj` | string |  | CNPJ do usuário |
| `pjInternacional` | integer (int64) |  | Código internacional do usuário |
| `cpf` | string |  | CPF do usuário |
| `categoria` | string |  |  |
| `chv` | integer (int32) |  | Número da chave de acesso |
| `cie` | string |  | CIE do usuário |
| `certificado` | objeto `Certificado` |  | Certificado digital ICP-BRASIL |
| `certificado.cn` | string |  | Common name do certificado ICP-BRASIL |
| `emissao` | string (date-time) |  | Data e hora de emissão do token |
| `expiraEm` | string (date-time) |  | Data e hora de expiração do token |
| `zoneOffset` | string |  | Deslocamento do GMT/UTC |
| `contrato` | objeto `Contrato` |  | Contrato |
| `contrato.numero` | string |  | Número do contrato |
| `contrato.dr` | integer (int64) |  | DR/SE do contrato |
| `contrato.api` | array<integer (int32)> |  | Atributo será removido em breve |
| `contrato.apis` | array<objeto `ApiToken`> |  | Lista de APIs restritas com autorização de acesso |
| `contrato.apis[].api` | integer (int32) |  | Número da api |
| `contrato.apis[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `contrato.apis[].grupos[].co` | string |  | Código do grupo |
| `contrato.apis[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `contrato.apis[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `contrato.apis[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `contrato.apis[].un` | boolean |  | Indica unidade de negócio externa |
| `contrato.apis[].paths` | array<string> |  |  |
| `cartaoPostagem` | objeto `CartaoPostagem` |  | Cartão de postagem |
| `cartaoPostagem.contrato` | string |  | Número do contrato |
| `cartaoPostagem.numero` | string |  | Número do Cartão de Postagem |
| `cartaoPostagem.dr` | integer (int64) |  | DR/SE do contrato |
| `cartaoPostagem.api` | array<integer (int32)> |  | Atributo será removido em breve |
| `cartaoPostagem.apis` | array<objeto `ApiToken`> |  | Lista de APIs restritas com autorização de acesso |
| `cartaoPostagem.apis[].api` | integer (int32) |  | Número da api |
| `cartaoPostagem.apis[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `cartaoPostagem.apis[].grupos[].co` | string |  | Código do grupo |
| `cartaoPostagem.apis[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `cartaoPostagem.apis[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `cartaoPostagem.apis[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `cartaoPostagem.apis[].un` | boolean |  | Indica unidade de negócio externa |
| `cartaoPostagem.apis[].paths` | array<string> |  |  |
| `api` | array<integer (int32)> |  | Atributo será removido em breve |
| `apiGrupos` | array<objeto `ApiToken`> |  |  |
| `apiGrupos[].api` | integer (int32) |  | Número da api |
| `apiGrupos[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `apiGrupos[].grupos[].co` | string |  | Código do grupo |
| `apiGrupos[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `apiGrupos[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `apiGrupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `apiGrupos[].un` | boolean |  | Indica unidade de negócio externa |
| `apiGrupos[].paths` | array<string> |  |  |
| `apis` | array<objeto `ApiToken`> |  | Lista de APIs restritas com autorização de acesso |
| `apis[].api` | integer (int32) |  | Número da api |
| `apis[].grupos` | array<objeto `GrupoToken`> |  | Lista de grupos autorizados |
| `apis[].grupos[].co` | string |  | Código do grupo |
| `apis[].grupos[].urls` | array<string> |  | Lista das urls permitidas |
| `apis[].grupos[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total — Valores: `L`, `T` |
| `apis[].op` | string |  | Operação: de L - Somente Leitura; T - Acesso Total |
| `apis[].un` | boolean |  | Indica unidade de negócio externa |
| `apis[].paths` | array<string> |  |  |
| `paths` | array<string> |  |  |
| `tipoUnidade` | objeto `TipoUnidade` |  |  |
| `tipoUnidade.franqueada` | string |  |  |
| `tipoUnidade.loja` | string |  |  |
| `tipoUnidade.locker` | string |  |  |
| `tipoUnidade.modular` | string |  |  |
| `tipoUnidade.pontoColeta` | string |  |  |
| `tipoUnidade.essencial` | string |  |  |
| `tipoUnidade.comunitaria` | string |  |  |
| `tipoUnidade.maquinaFranquear` | string |  |  |
| `tipoUnidade.outras` | string |  |  |
| `token` | string |  | É o token que será usado na requisição, adicionar no header 'Authorization: Bearer <seu_token>' |

### `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

### `ContratoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do contrato — minLength: `1` |
| `dr` | integer (int64) |  | Número do Regional/DR/SE do contrato |

### `CartaoPostagemInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do cartão de postagem — minLength: `1` |
| `contrato` | string |  | Número do contrato |
| `dr` | integer (int64) |  | Número do Regional/DR/SE do contrato |

### `Link`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `rel` | string |  |  |
| `href` | string |  |  |

## 4. Códigos HTTP

| Código | Significado |
|---|---|
| `201` | Token gerado (ou token vigente devolvido). |
| `400` | Requisição inválida (corpo/campos). Ver `MessageResponse.msgs`. |
| `401` | Usuário ou código de acesso inválidos no header Basic. Em homologação, confira se o usuário é o CNPJ (somente dígitos) e se o código de acesso é o do portal de homologação. |
| `403` | Usuário sem permissão para o contrato/cartão informado. |
| `429` | Limite de 3 req/s excedido. |
| `500` | Erro interno dos Correios. |

## 5. JSON Schema completo (referências expandidas)

### `POST /v1/autentica`

Resposta 201:

```json
{
  "title": "Token",
  "type": "object",
  "properties": {
    "ambiente": {
      "type": "string",
      "description": "Ambiente",
      "enum": [
        "PRODUCAO",
        "HOMOLOGACAO",
        "DESENVOLVIMENTO",
        "LOCAL"
      ]
    },
    "id": {
      "type": "string",
      "description": "Identificador do usuário"
    },
    "ip": {
      "type": "string",
      "description": "IP do requisitante"
    },
    "perfil": {
      "type": "string",
      "description": "Perfil: PF: Pessoa Física, PJ: Pessoa Jurídica",
      "enum": [
        "S",
        "PJ",
        "PF",
        "A"
      ]
    },
    "cnpj": {
      "type": "string",
      "description": "CNPJ do usuário"
    },
    "pjInternacional": {
      "type": "integer",
      "format": "int64",
      "description": "Código internacional do usuário"
    },
    "cpf": {
      "type": "string",
      "description": "CPF do usuário"
    },
    "categoria": {
      "type": "string"
    },
    "chv": {
      "type": "integer",
      "format": "int32",
      "description": "Número da chave de acesso"
    },
    "cie": {
      "type": "string",
      "description": "CIE do usuário"
    },
    "certificado": {
      "title": "Certificado",
      "type": "object",
      "properties": {
        "cn": {
          "type": "string",
          "description": "Common name do certificado ICP-BRASIL"
        }
      },
      "description": "Certificado digital ICP-BRASIL"
    },
    "emissao": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de emissão do token"
    },
    "expiraEm": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de expiração do token"
    },
    "zoneOffset": {
      "type": "string",
      "description": "Deslocamento do GMT/UTC"
    },
    "contrato": {
      "title": "Contrato",
      "type": "object",
      "properties": {
        "numero": {
          "type": "string",
          "description": "Número do contrato"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Contrato"
    },
    "cartaoPostagem": {
      "title": "CartaoPostagem",
      "type": "object",
      "properties": {
        "contrato": {
          "type": "string",
          "description": "Número do contrato"
        },
        "numero": {
          "type": "string",
          "description": "Número do Cartão de Postagem"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Cartão de postagem"
    },
    "api": {
      "type": "array",
      "deprecated": true,
      "description": "Atributo será removido em breve",
      "items": {
        "type": "integer",
        "format": "int32",
        "deprecated": true
      }
    },
    "apiGrupos": {
      "type": "array",
      "deprecated": true,
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "apis": {
      "type": "array",
      "description": "Lista de APIs restritas com autorização de acesso",
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "paths": {
      "type": "array",
      "items": {
        "type": "string"
      }
    },
    "tipoUnidade": {
      "title": "TipoUnidade",
      "type": "object",
      "properties": {
        "franqueada": {
          "type": "string"
        },
        "loja": {
          "type": "string"
        },
        "locker": {
          "type": "string"
        },
        "modular": {
          "type": "string"
        },
        "pontoColeta": {
          "type": "string"
        },
        "essencial": {
          "type": "string"
        },
        "comunitaria": {
          "type": "string"
        },
        "maquinaFranquear": {
          "type": "string"
        },
        "outras": {
          "type": "string"
        }
      }
    },
    "token": {
      "type": "string",
      "description": "É o token que será usado na requisição, adicionar no header 'Authorization: Bearer <seu_token>'"
    }
  }
}
```

### `POST /v1/autentica/contrato`

Requisição:

```json
{
  "title": "ContratoInput",
  "type": "object",
  "properties": {
    "numero": {
      "type": "string",
      "description": "Número do contrato",
      "minLength": 1
    },
    "dr": {
      "type": "integer",
      "format": "int64",
      "description": "Número do Regional/DR/SE do contrato"
    }
  },
  "required": [
    "numero"
  ]
}
```

Resposta 201:

```json
{
  "title": "Token",
  "type": "object",
  "properties": {
    "ambiente": {
      "type": "string",
      "description": "Ambiente",
      "enum": [
        "PRODUCAO",
        "HOMOLOGACAO",
        "DESENVOLVIMENTO",
        "LOCAL"
      ]
    },
    "id": {
      "type": "string",
      "description": "Identificador do usuário"
    },
    "ip": {
      "type": "string",
      "description": "IP do requisitante"
    },
    "perfil": {
      "type": "string",
      "description": "Perfil: PF: Pessoa Física, PJ: Pessoa Jurídica",
      "enum": [
        "S",
        "PJ",
        "PF",
        "A"
      ]
    },
    "cnpj": {
      "type": "string",
      "description": "CNPJ do usuário"
    },
    "pjInternacional": {
      "type": "integer",
      "format": "int64",
      "description": "Código internacional do usuário"
    },
    "cpf": {
      "type": "string",
      "description": "CPF do usuário"
    },
    "categoria": {
      "type": "string"
    },
    "chv": {
      "type": "integer",
      "format": "int32",
      "description": "Número da chave de acesso"
    },
    "cie": {
      "type": "string",
      "description": "CIE do usuário"
    },
    "certificado": {
      "title": "Certificado",
      "type": "object",
      "properties": {
        "cn": {
          "type": "string",
          "description": "Common name do certificado ICP-BRASIL"
        }
      },
      "description": "Certificado digital ICP-BRASIL"
    },
    "emissao": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de emissão do token"
    },
    "expiraEm": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de expiração do token"
    },
    "zoneOffset": {
      "type": "string",
      "description": "Deslocamento do GMT/UTC"
    },
    "contrato": {
      "title": "Contrato",
      "type": "object",
      "properties": {
        "numero": {
          "type": "string",
          "description": "Número do contrato"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Contrato"
    },
    "cartaoPostagem": {
      "title": "CartaoPostagem",
      "type": "object",
      "properties": {
        "contrato": {
          "type": "string",
          "description": "Número do contrato"
        },
        "numero": {
          "type": "string",
          "description": "Número do Cartão de Postagem"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Cartão de postagem"
    },
    "api": {
      "type": "array",
      "deprecated": true,
      "description": "Atributo será removido em breve",
      "items": {
        "type": "integer",
        "format": "int32",
        "deprecated": true
      }
    },
    "apiGrupos": {
      "type": "array",
      "deprecated": true,
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "apis": {
      "type": "array",
      "description": "Lista de APIs restritas com autorização de acesso",
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "paths": {
      "type": "array",
      "items": {
        "type": "string"
      }
    },
    "tipoUnidade": {
      "title": "TipoUnidade",
      "type": "object",
      "properties": {
        "franqueada": {
          "type": "string"
        },
        "loja": {
          "type": "string"
        },
        "locker": {
          "type": "string"
        },
        "modular": {
          "type": "string"
        },
        "pontoColeta": {
          "type": "string"
        },
        "essencial": {
          "type": "string"
        },
        "comunitaria": {
          "type": "string"
        },
        "maquinaFranquear": {
          "type": "string"
        },
        "outras": {
          "type": "string"
        }
      }
    },
    "token": {
      "type": "string",
      "description": "É o token que será usado na requisição, adicionar no header 'Authorization: Bearer <seu_token>'"
    }
  }
}
```

### `POST /v1/autentica/cartaopostagem`

Requisição:

```json
{
  "title": "CartaoPostagemInput",
  "type": "object",
  "properties": {
    "numero": {
      "type": "string",
      "description": "Número do cartão de postagem",
      "minLength": 1
    },
    "contrato": {
      "type": "string",
      "description": "Número do contrato"
    },
    "dr": {
      "type": "integer",
      "format": "int64",
      "description": "Número do Regional/DR/SE do contrato"
    }
  },
  "required": [
    "numero"
  ]
}
```

Resposta 201:

```json
{
  "title": "Token",
  "type": "object",
  "properties": {
    "ambiente": {
      "type": "string",
      "description": "Ambiente",
      "enum": [
        "PRODUCAO",
        "HOMOLOGACAO",
        "DESENVOLVIMENTO",
        "LOCAL"
      ]
    },
    "id": {
      "type": "string",
      "description": "Identificador do usuário"
    },
    "ip": {
      "type": "string",
      "description": "IP do requisitante"
    },
    "perfil": {
      "type": "string",
      "description": "Perfil: PF: Pessoa Física, PJ: Pessoa Jurídica",
      "enum": [
        "S",
        "PJ",
        "PF",
        "A"
      ]
    },
    "cnpj": {
      "type": "string",
      "description": "CNPJ do usuário"
    },
    "pjInternacional": {
      "type": "integer",
      "format": "int64",
      "description": "Código internacional do usuário"
    },
    "cpf": {
      "type": "string",
      "description": "CPF do usuário"
    },
    "categoria": {
      "type": "string"
    },
    "chv": {
      "type": "integer",
      "format": "int32",
      "description": "Número da chave de acesso"
    },
    "cie": {
      "type": "string",
      "description": "CIE do usuário"
    },
    "certificado": {
      "title": "Certificado",
      "type": "object",
      "properties": {
        "cn": {
          "type": "string",
          "description": "Common name do certificado ICP-BRASIL"
        }
      },
      "description": "Certificado digital ICP-BRASIL"
    },
    "emissao": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de emissão do token"
    },
    "expiraEm": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de expiração do token"
    },
    "zoneOffset": {
      "type": "string",
      "description": "Deslocamento do GMT/UTC"
    },
    "contrato": {
      "title": "Contrato",
      "type": "object",
      "properties": {
        "numero": {
          "type": "string",
          "description": "Número do contrato"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Contrato"
    },
    "cartaoPostagem": {
      "title": "CartaoPostagem",
      "type": "object",
      "properties": {
        "contrato": {
          "type": "string",
          "description": "Número do contrato"
        },
        "numero": {
          "type": "string",
          "description": "Número do Cartão de Postagem"
        },
        "dr": {
          "type": "integer",
          "format": "int64",
          "description": "DR/SE do contrato"
        },
        "api": {
          "type": "array",
          "deprecated": true,
          "description": "Atributo será removido em breve",
          "items": {
            "type": "integer",
            "format": "int32",
            "deprecated": true
          }
        },
        "apis": {
          "type": "array",
          "description": "Lista de APIs restritas com autorização de acesso",
          "items": {
            "title": "ApiToken",
            "type": "object",
            "properties": {
              "api": {
                "type": "integer",
                "format": "int32",
                "description": "Número da api"
              },
              "grupos": {
                "type": "array",
                "description": "Lista de grupos autorizados",
                "items": {
                  "title": "GrupoToken",
                  "type": "object",
                  "properties": {
                    "co": {
                      "type": "string",
                      "description": "Código do grupo"
                    },
                    "urls": {
                      "type": "array",
                      "description": "Lista das urls permitidas",
                      "items": {
                        "type": "string"
                      }
                    },
                    "op": {
                      "type": "string",
                      "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                      "enum": [
                        "L",
                        "T"
                      ]
                    }
                  }
                }
              },
              "op": {
                "type": "string",
                "description": "Operação: de L - Somente Leitura; T - Acesso Total"
              },
              "un": {
                "type": "boolean",
                "description": "Indica unidade de negócio externa"
              },
              "paths": {
                "type": "array",
                "items": {
                  "type": "string"
                }
              }
            }
          }
        }
      },
      "description": "Cartão de postagem"
    },
    "api": {
      "type": "array",
      "deprecated": true,
      "description": "Atributo será removido em breve",
      "items": {
        "type": "integer",
        "format": "int32",
        "deprecated": true
      }
    },
    "apiGrupos": {
      "type": "array",
      "deprecated": true,
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "apis": {
      "type": "array",
      "description": "Lista de APIs restritas com autorização de acesso",
      "items": {
        "title": "ApiToken",
        "type": "object",
        "properties": {
          "api": {
            "type": "integer",
            "format": "int32",
            "description": "Número da api"
          },
          "grupos": {
            "type": "array",
            "description": "Lista de grupos autorizados",
            "items": {
              "title": "GrupoToken",
              "type": "object",
              "properties": {
                "co": {
                  "type": "string",
                  "description": "Código do grupo"
                },
                "urls": {
                  "type": "array",
                  "description": "Lista das urls permitidas",
                  "items": {
                    "type": "string"
                  }
                },
                "op": {
                  "type": "string",
                  "description": "Operação: de L - Somente Leitura; T - Acesso Total",
                  "enum": [
                    "L",
                    "T"
                  ]
                }
              }
            }
          },
          "op": {
            "type": "string",
            "description": "Operação: de L - Somente Leitura; T - Acesso Total"
          },
          "un": {
            "type": "boolean",
            "description": "Indica unidade de negócio externa"
          },
          "paths": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "paths": {
      "type": "array",
      "items": {
        "type": "string"
      }
    },
    "tipoUnidade": {
      "title": "TipoUnidade",
      "type": "object",
      "properties": {
        "franqueada": {
          "type": "string"
        },
        "loja": {
          "type": "string"
        },
        "locker": {
          "type": "string"
        },
        "modular": {
          "type": "string"
        },
        "pontoColeta": {
          "type": "string"
        },
        "essencial": {
          "type": "string"
        },
        "comunitaria": {
          "type": "string"
        },
        "maquinaFranquear": {
          "type": "string"
        },
        "outras": {
          "type": "string"
        }
      }
    },
    "token": {
      "type": "string",
      "description": "É o token que será usado na requisição, adicionar no header 'Authorization: Bearer <seu_token>'"
    }
  }
}
```

## 6. Orientações de implementação

1. **Escolha do endpoint:** use `/v1/autentica/cartaopostagem` quando for consumir APIs que dependem do cartão de postagem (ex.: Pré-postagem, Preço, Prazo com contrato); `/v1/autentica/contrato` quando a autorização for por contrato; `/v1/autentica` para APIs liberadas diretamente ao usuário idCorreios.
2. **Credenciais:** guarde usuário e código de acesso em variáveis de ambiente ou cofre de segredos, **separados por ambiente** — nunca no código-fonte nem no front-end. Toda chamada à API Token deve partir do back-end. Em **homologação** o usuário é o **CNPJ** (somente dígitos); em **produção**, o login do Meu Correios.
3. **Cache do token:** armazene `token` e `expiraEm` (em memória, Redis ou banco). Antes de cada chamada às outras APIs, reutilize o token em cache; solicite outro somente quando faltarem poucos minutos para `expiraEm` (a API só devolve token novo dentro de 30 min do vencimento, se expirado ou se as permissões mudarem).
4. **Concorrência:** proteja a renovação com trava (mutex/lock) para que várias requisições simultâneas não disparem várias chamadas e estourem o limite de 3 req/s (HTTP 429).
5. **Uso nas demais APIs:** envie `Authorization: Bearer <token>`. Se alguma API responder 401, renove o token uma única vez e repita a chamada.
6. **Ambientes:** mantenha a URL base configurável (homologação `https://apihom.correios.com.br/token` × produção `https://api.correios.com.br/token`); credenciais e códigos de acesso são diferentes em cada ambiente.
7. **Fuso/data:** interprete `expiraEm` como data-hora local de Brasília quando não houver fuso explícito.

### Exemplo (Node.js)

```js
const BASE = process.env.CORREIOS_TOKEN_URL; // ex.: https://apihom.correios.com.br/token
// CORREIOS_USUARIO: homologação = CNPJ só com dígitos (ex.: 00000000000101); produção = login do Meu Correios
// CORREIOS_CODIGO_ACESSO: código de acesso gerado no portal CWS do mesmo ambiente
let cache = null; // { token, expiraEm }

async function getToken() {
  if (cache && new Date(cache.expiraEm) - Date.now() > 5 * 60 * 1000) return cache.token;
  const auth = Buffer.from(`${process.env.CORREIOS_USUARIO}:${process.env.CORREIOS_CODIGO_ACESSO}`).toString('base64');
  const res = await fetch(`${BASE}/v1/autentica/cartaopostagem`, {
    method: 'POST',
    headers: { Authorization: `Basic ${auth}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ numero: process.env.CORREIOS_CARTAO_POSTAGEM })
  });
  if (!res.ok) throw new Error(`Token Correios: HTTP ${res.status} ${await res.text()}`);
  cache = await res.json();
  return cache.token;
}
```
