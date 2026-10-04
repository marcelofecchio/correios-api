# Correios — API SRO Rastro (rastreamento)

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/srorastro/v3/api-docs`), versão **3.5.49** (homologação), em 25/09/2026. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 10 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-09-rotulo-download-assincrono-api.md` · Próxima: `correios-11-webhook-api.md`

## 0. Resumo rápido

- **Finalidade:** rastrear objetos (eventos de um ou vários objetos), obter imagens de baixa (comprovante de entrega) e imagens do AR Digital.
- **Autenticação:** `Authorization: Bearer <token>` — token gerado pela **API Token**. Ver `correios-01-token-api.md`.
- **Código do objeto:** 13 caracteres (ex.: `AA123456789BR`).
- **Limites:** até **50** objetos por consulta em `GET /v1/objetos`; até **10** objetos por consulta em `GET /v1/ar-digital`.
- **Parâmetro `resultado`:** `T` = todos os eventos, `P` = apenas o primeiro, `U` = apenas o último.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/srorastro` |
| Homologação | `https://apihom.correios.com.br/srorastro` |

| Método | Caminho | Grupo | Resumo |
|---|---|---|---|
| GET | `/v1/objetos` | Rastreamento Externo | Método para consultar os eventos de uma lista de objetos |
| GET | `/v1/objetos/{codigoObjeto}` | Rastreamento Externo | Método para consultar os eventos de um determinado objeto |
| POST | `/v1/objetos/imagens` | Imagens | Método para consultar as imagens de baixa de uma lista de objetos |
| GET | `/v1/recibo/{recibo}` | Imagens | Método para consultar o resultado de processamento de um determinado recibo |
| GET | `/v1/ar-digital` | Aviso de Recebimento | Método para consultar a imagem do AR digital do objeto |

## 1. Endpoints

### Rastreamento Externo

#### 1.1 `GET /v1/objetos`

**Rastreamento de Objeto**

Método para consultar os eventos de uma lista de objetos

- URL (produção): `https://api.correios.com.br/srorastro/v1/objetos`
- URL (homologação): `https://apihom.correios.com.br/srorastro/v1/objetos`
- Header: `Authorization: Bearer <token>`
- operationId: `buscaEventosLista`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `codigosObjetos` | query | array<string> | ✔ | Lista de no máximo 50 objetos, com 13 caracteres cada, para rastreamento — minItems: `1` — maxItems: `50` — Ex.: `AA123456789BR` |
| `resultado` | query | string |  | Tipo de resultado da pesquisa. T para todos os eventos, P para apenas o primeiro evento e U para apenas o último evento. — Valores: `T`, `P`, `U` — default: `U` |

> Lista na query: pelo padrão OpenAPI, repita o parâmetro para cada objeto (`codigosObjetos=A&codigosObjetos=B`). Teste também a forma separada por vírgula em homologação.

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/srorastro/v1/objetos?codigosObjetos=AA123456789BR&codigosObjetos=BB987654321BR&resultado=U
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Um objeto SRO contendo as informações sobre o rastreamento dos objetos | `SRO` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `versao` | string |  | **Versão** — Versão da api em execução — maxLength: `8` |
| `quantidade` | integer (int32) |  | **Quantidade** — Quantidade de objetos rastreados — maxLength: `5` |
| `objetos` | array<objeto `Objeto`> |  | **Objetos** — Lista de objetos com seu respectivo rastreamento |
| `objetos[].codObjeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `objetos[].tipoPostal` | objeto `TipoPostal` |  | Tipo Postal |
| `objetos[].tipoPostal.sigla` | string |  | **Sigla** — Sigla do tipo postal — maxLength: `2` |
| `objetos[].tipoPostal.nome` | string |  | **Nome** — Descrição do tipo postal — maxLength: `40` |
| `objetos[].tipoPostal.categoria` | object |  | **Categoria** — Categoria do tipo postal |
| `objetos[].tipoPostal.familia` | string |  | **Familia** — Familia do tipo postal — maxLength: `30` |
| `objetos[].mensagem` | string |  | Mensagem de erro — maxLength: `500` |
| `objetos[].dtPrevista` | string (date-time) |  | **Data Prevista** — Data prevista para entrega do objeto |
| `objetos[].multiVolume` | string |  | **Multi Volume** — Quantidade de volumes para o objeto — maxLength: `5` |
| `objetos[].afterschedule` | string |  | **Depois do Horário** — Objeto postado depois do horário — Valores: `S`, `N` |
| `objetos[].volume` | integer (int32) |  | **Volume** — Volume do objeto |
| `objetos[].valorRecebido` | string |  | **Valor Recebido** — Valor recebido — maxLength: `11` |
| `objetos[].entregaProgramada` | string (date-time) |  | **Entrega Programada** — Data programada para entrega do objeto |
| `objetos[].prazoTratamento` | integer (int32) |  | **Prazo de Tratamento** — Prazo para tratamento do objeto |
| `objetos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].largura` | number |  | **Largura** — Largura do objeto em cm — maxLength: `4` |
| `objetos[].comprimento` | number |  | **Comprimento** — Comprimento do objeto em cm — maxLength: `4` |
| `objetos[].altura` | number |  | **Altura** — Altura do objeto em cm — maxLength: `4` |
| `objetos[].diametro` | number |  | **Diâmetro** — Diametro do objeto em cm — maxLength: `4` |
| `objetos[].peso` | number |  | **Peso** — Peso do objeto em gramas — maxLength: `10` |
| `objetos[].formato` | string |  | **Formato** — Formato do objeto — maxLength: `40` |
| `objetos[].celRemetSMS` | string |  |  |
| `objetos[].celDestSMS` | string |  |  |
| `objetos[].valorDeclarado` | number |  | **Valor Declarado** — Valor declarado com duas casas de precisão — maxLength: `11` |
| `objetos[].eventos` | array<objeto `Evento`> |  | **Eventos** — Eventos do Objeto |
| `objetos[].eventos[].codigo` | string |  | **Código** — Código do evento de rastreamento — maxLength: `3` |
| `objetos[].eventos[].tipo` | string |  | **Motivo** — Tipo do evento. Representa o status ou motivo do evento — maxLength: `2` |
| `objetos[].eventos[].dtHrCriado` | string (date-time) |  | **Criação** — Data e hora da criação do evento |
| `objetos[].eventos[].descricao` | string |  | **Descrição** — Descrição do evento — maxLength: `80` |
| `objetos[].eventos[].estacao` | string |  | Estação de trabalho responsável pelo registro do evento — maxLength: `3` |
| `objetos[].eventos[].usuario` | string |  | Usuário responsável pelo registro do evento — maxLength: `8` |
| `objetos[].eventos[].carteiro` | string |  | Matrícula do carteiro que realizou o evento — maxLength: `8` |
| `objetos[].eventos[].entregador` | string |  | Identificação do entregador — maxLength: `13` |
| `objetos[].eventos[].latitude` | string |  | **Latitude** — Latitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].longitude` | string |  | **Longitude** — Longitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].detalhe` | string |  | Descrição do detalhe do evento — maxLength: `80` |
| `objetos[].eventos[].descCodigo` | string |  | Descrição da sigla do código do evento — maxLength: `80` |
| `objetos[].eventos[].dtHrGravado` | string (date-time) |  | Data e hora da gravação do evento |
| `objetos[].eventos[].token` | string |  | **Token de atendimento** — Token de atendimento vinculado ao evento LDI |
| `objetos[].eventos[].recebedor` | objeto `Recebedor` |  | **Recebedor** — Classe que representa um recebedor |
| `objetos[].eventos[].recebedor.nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `objetos[].eventos[].recebedor.documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `objetos[].eventos[].recebedor.celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `objetos[].eventos[].recebedor.email` | string |  | **Email do recebedor** — maxLength: `60` |
| `objetos[].eventos[].recebedor.comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |
| `objetos[].eventos[].unidade` | objeto `Unidade` |  | **Unidade de Tratamento** — Classe que representa uma unidade de tratamento do objeto |
| `objetos[].eventos[].unidade.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidade.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidade.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidade.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidade.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidade.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidade.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidade.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidade.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidade.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidade.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidade.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].entregadorExterno` | objeto `EntregadorExterno` |  | **Entregador Externo** — Dados do entregador externo do objeto. |
| `objetos[].eventos[].entregadorExterno.documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `objetos[].eventos[].entregadorExterno.nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |
| `objetos[].eventos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].eventos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].eventos[].servico` | string |  |  |
| `objetos[].eventos[].remetente` | objeto `Remetente` |  | **Remetente** — Classe que representa o remetente do objeto |
| `objetos[].eventos[].remetente.nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `objetos[].eventos[].remetente.documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `objetos[].eventos[].remetente.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `objetos[].eventos[].remetente.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].remetente.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].remetente.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].remetente.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].remetente.endereco.pais` | string |  |  |
| `objetos[].eventos[].remetente.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].remetente.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].remetente.telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `objetos[].eventos[].remetente.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].remetente.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].remetente.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario` | objeto `Destinatario` |  | **Destinatário** — Classe que representa o destinatário do objeto |
| `objetos[].eventos[].destinatario.nome` | string |  | Nome do destinatário — maxLength: `72` |
| `objetos[].eventos[].destinatario.documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `objetos[].eventos[].destinatario.email` | string |  | Email do destinatário — maxLength: `60` |
| `objetos[].eventos[].destinatario.telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `objetos[].eventos[].destinatario.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].destinatario.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].destinatario.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `objetos[].eventos[].destinatario.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].destinatario.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].destinatario.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].destinatario.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].destinatario.endereco.pais` | string |  |  |
| `objetos[].eventos[].destinatario.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].destinatario.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].dtLimiteRetirada` | string (date-time) |  | **Prazo de Retirada** — Prazo de retirada de objeto lançado em LDI |
| `objetos[].eventos[].comentario` | string |  | **Comentário** — Comentário informado pelo usuário referente ao evento — maxLength: `100` |
| `objetos[].eventos[].codLista` | string |  | Recipiente — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino` | objeto `Destino` |  | **Destino** — Classe que representa o destino de um objeto |
| `objetos[].eventos[].unidadeDestino.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidadeDestino.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidadeDestino.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidadeDestino.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidadeDestino.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidadeDestino.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].unidadeDestino.recipiente` | string |  | Recipiente — maxLength: `13` |
| `objetos[].servico` | objeto `Servico` |  | **Serviço** — Classe que representa o serviço utilizado pelo objeto |
| `objetos[].servico.codigo` | string |  | Código do serviço — maxLength: `20` |
| `objetos[].servico.ar` | string |  | Possui serviço adicional Aviso de Recebimento (AR) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.tipoAr` | string |  | Tipo de AR — Valores: `N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)` |
| `objetos[].servico.mp` | string |  | Possui serviço adicional Mão-Própria (MP) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.vd` | string |  | Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor — maxLength: `11` |
| `objetos[].servico.eVizinho` | string |  | Possui serviço adicional Entrega no Vizinho — Valores: `S(Sim);  N(Não)` |
| `objetos[].servico.fotoFachada` | string |  | Possui serviço adicional de Foto Fachada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eControlada` | string |  | Possui serviço adicional de Entrega Controlada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eInterativa` | string |  | Possui serviço adicional de Entrega Interativa — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eLocker` | string |  | Possui serviço adicional de Entrega Locker — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.devDocumento` | string |  | Possui serviço adicional de Devolução de Documentos — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.dgr` | string |  | Possui serviço adicional de Transporte e artigos perigosos — Valores: `S(Sim); N(Não)` |
| `tipoResultado` | string |  | **Resultado** — Resultado da pesquisa — Valores: `Todos os Eventos, Primeiro Evento, Último Evento` — maxLength: `16` |

Exemplo de resposta 200:

```json
{
  "versao": "string",
  "quantidade": 0,
  "objetos": [
    {
      "codObjeto": "string",
      "tipoPostal": {
        "sigla": "string",
        "nome": "string",
        "categoria": "string",
        "familia": "string"
      },
      "mensagem": "string",
      "dtPrevista": "2026-01-01T00:00:00",
      "multiVolume": "string",
      "afterschedule": "S",
      "volume": 0,
      "valorRecebido": "string",
      "entregaProgramada": "2026-01-01T00:00:00",
      "prazoTratamento": 0,
      "contrato": "string",
      "codAdm": "string",
      "cartaoPostagem": "string",
      "CNPJ": "string",
      "largura": 0,
      "comprimento": 0,
      "altura": 0,
      "diametro": 0,
      "peso": 0,
      "formato": "string",
      "celRemetSMS": "string",
      "celDestSMS": "string",
      "valorDeclarado": 0,
      "eventos": [
        {
          "codigo": "string",
          "tipo": "string",
          "dtHrCriado": "2026-01-01T00:00:00",
          "descricao": "string",
          "estacao": "string",
          "usuario": "string",
          "carteiro": "string",
          "entregador": "string",
          "latitude": "string",
          "longitude": "string",
          "detalhe": "string",
          "descCodigo": "string",
          "dtHrGravado": "2026-01-01T00:00:00",
          "token": "string",
          "recebedor": {
            "nome": "string",
            "documento": "string",
            "celular": "string",
            "email": "string",
            "comentario": "string"
          },
          "unidade": {
            "nome": "string",
            "codSro": "string",
            "codMcu": "string",
            "tipo": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "codse": "string",
            "se": "string"
          },
          "entregadorExterno": {
            "documento": "string",
            "nome": "string"
          },
          "contrato": "string",
          "codAdm": "string",
          "cartaoPostagem": "string",
          "CNPJ": "string",
          "servico": "string",
          "remetente": {
            "nome": "string",
            "documento": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "telefones": [
              {
                "tipo": "Fixo, Celular (Cel)",
                "ddd": "string",
                "numero": "string"
              }
            ]
          },
          "destinatario": {
            "nome": "string",
            "documento": "string",
            "email": "string",
            "telefones": [
              {
                "tipo": "Fixo, Celular (Cel)",
                "ddd": "string",
                "numero": "string"
              }
            ],
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            }
          },
          "dtLimiteRetirada": "2026-01-01T00:00:00",
          "comentario": "string",
          "codLista": "string",
          "unidadeDestino": {
            "nome": "string",
            "codSro": "string",
            "codMcu": "string",
            "tipo": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "codse": "string",
            "se": "string",
            "recipiente": "string"
          }
        }
      ],
      "servico": {
        "codigo": "string",
        "ar": "S(Sim); N(Não)",
        "tipoAr": "N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)",
        "mp": "S(Sim); N(Não)",
        "vd": "string",
        "eVizinho": "S(Sim);  N(Não)",
        "fotoFachada": "S(Sim); N(Não)",
        "eControlada": "S(Sim); N(Não)",
        "eInterativa": "S(Sim); N(Não)",
        "eLocker": "S(Sim); N(Não)",
        "devDocumento": "S(Sim); N(Não)",
        "dgr": "S(Sim); N(Não)"
      }
    }
  ],
  "tipoResultado": "Todos os Eventos, Primeiro Evento, Último Evento"
}
```

#### 1.2 `GET /v1/objetos/{codigoObjeto}`

**Rastreamento de Objeto**

Método para consultar os eventos de um determinado objeto

- URL (produção): `https://api.correios.com.br/srorastro/v1/objetos/{codigoObjeto}`
- URL (homologação): `https://apihom.correios.com.br/srorastro/v1/objetos/{codigoObjeto}`
- Header: `Authorization: Bearer <token>`
- operationId: `buscaEventos`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `codigoObjeto` | path | string | ✔ | Número da etiqueta do objeto com 13 caracteres para rastreamento — minLength: `1` — Ex.: `AA123456789BR` |
| `resultado` | query | string |  | Tipo de resultado da pesquisa. T para todos os eventos, P para apenas o primeiro evento e U para apenas o último evento. — Valores: `T`, `P`, `U` — default: `T` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/srorastro/v1/objetos/AA123456789BR?resultado=T
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Um objeto SRO contendo as informações sobre o rastreamento do objeto | `SRO` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `versao` | string |  | **Versão** — Versão da api em execução — maxLength: `8` |
| `quantidade` | integer (int32) |  | **Quantidade** — Quantidade de objetos rastreados — maxLength: `5` |
| `objetos` | array<objeto `Objeto`> |  | **Objetos** — Lista de objetos com seu respectivo rastreamento |
| `objetos[].codObjeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `objetos[].tipoPostal` | objeto `TipoPostal` |  | Tipo Postal |
| `objetos[].tipoPostal.sigla` | string |  | **Sigla** — Sigla do tipo postal — maxLength: `2` |
| `objetos[].tipoPostal.nome` | string |  | **Nome** — Descrição do tipo postal — maxLength: `40` |
| `objetos[].tipoPostal.categoria` | object |  | **Categoria** — Categoria do tipo postal |
| `objetos[].tipoPostal.familia` | string |  | **Familia** — Familia do tipo postal — maxLength: `30` |
| `objetos[].mensagem` | string |  | Mensagem de erro — maxLength: `500` |
| `objetos[].dtPrevista` | string (date-time) |  | **Data Prevista** — Data prevista para entrega do objeto |
| `objetos[].multiVolume` | string |  | **Multi Volume** — Quantidade de volumes para o objeto — maxLength: `5` |
| `objetos[].afterschedule` | string |  | **Depois do Horário** — Objeto postado depois do horário — Valores: `S`, `N` |
| `objetos[].volume` | integer (int32) |  | **Volume** — Volume do objeto |
| `objetos[].valorRecebido` | string |  | **Valor Recebido** — Valor recebido — maxLength: `11` |
| `objetos[].entregaProgramada` | string (date-time) |  | **Entrega Programada** — Data programada para entrega do objeto |
| `objetos[].prazoTratamento` | integer (int32) |  | **Prazo de Tratamento** — Prazo para tratamento do objeto |
| `objetos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].largura` | number |  | **Largura** — Largura do objeto em cm — maxLength: `4` |
| `objetos[].comprimento` | number |  | **Comprimento** — Comprimento do objeto em cm — maxLength: `4` |
| `objetos[].altura` | number |  | **Altura** — Altura do objeto em cm — maxLength: `4` |
| `objetos[].diametro` | number |  | **Diâmetro** — Diametro do objeto em cm — maxLength: `4` |
| `objetos[].peso` | number |  | **Peso** — Peso do objeto em gramas — maxLength: `10` |
| `objetos[].formato` | string |  | **Formato** — Formato do objeto — maxLength: `40` |
| `objetos[].celRemetSMS` | string |  |  |
| `objetos[].celDestSMS` | string |  |  |
| `objetos[].valorDeclarado` | number |  | **Valor Declarado** — Valor declarado com duas casas de precisão — maxLength: `11` |
| `objetos[].eventos` | array<objeto `Evento`> |  | **Eventos** — Eventos do Objeto |
| `objetos[].eventos[].codigo` | string |  | **Código** — Código do evento de rastreamento — maxLength: `3` |
| `objetos[].eventos[].tipo` | string |  | **Motivo** — Tipo do evento. Representa o status ou motivo do evento — maxLength: `2` |
| `objetos[].eventos[].dtHrCriado` | string (date-time) |  | **Criação** — Data e hora da criação do evento |
| `objetos[].eventos[].descricao` | string |  | **Descrição** — Descrição do evento — maxLength: `80` |
| `objetos[].eventos[].estacao` | string |  | Estação de trabalho responsável pelo registro do evento — maxLength: `3` |
| `objetos[].eventos[].usuario` | string |  | Usuário responsável pelo registro do evento — maxLength: `8` |
| `objetos[].eventos[].carteiro` | string |  | Matrícula do carteiro que realizou o evento — maxLength: `8` |
| `objetos[].eventos[].entregador` | string |  | Identificação do entregador — maxLength: `13` |
| `objetos[].eventos[].latitude` | string |  | **Latitude** — Latitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].longitude` | string |  | **Longitude** — Longitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].detalhe` | string |  | Descrição do detalhe do evento — maxLength: `80` |
| `objetos[].eventos[].descCodigo` | string |  | Descrição da sigla do código do evento — maxLength: `80` |
| `objetos[].eventos[].dtHrGravado` | string (date-time) |  | Data e hora da gravação do evento |
| `objetos[].eventos[].token` | string |  | **Token de atendimento** — Token de atendimento vinculado ao evento LDI |
| `objetos[].eventos[].recebedor` | objeto `Recebedor` |  | **Recebedor** — Classe que representa um recebedor |
| `objetos[].eventos[].recebedor.nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `objetos[].eventos[].recebedor.documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `objetos[].eventos[].recebedor.celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `objetos[].eventos[].recebedor.email` | string |  | **Email do recebedor** — maxLength: `60` |
| `objetos[].eventos[].recebedor.comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |
| `objetos[].eventos[].unidade` | objeto `Unidade` |  | **Unidade de Tratamento** — Classe que representa uma unidade de tratamento do objeto |
| `objetos[].eventos[].unidade.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidade.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidade.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidade.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidade.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidade.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidade.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidade.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidade.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidade.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidade.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidade.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].entregadorExterno` | objeto `EntregadorExterno` |  | **Entregador Externo** — Dados do entregador externo do objeto. |
| `objetos[].eventos[].entregadorExterno.documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `objetos[].eventos[].entregadorExterno.nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |
| `objetos[].eventos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].eventos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].eventos[].servico` | string |  |  |
| `objetos[].eventos[].remetente` | objeto `Remetente` |  | **Remetente** — Classe que representa o remetente do objeto |
| `objetos[].eventos[].remetente.nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `objetos[].eventos[].remetente.documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `objetos[].eventos[].remetente.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `objetos[].eventos[].remetente.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].remetente.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].remetente.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].remetente.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].remetente.endereco.pais` | string |  |  |
| `objetos[].eventos[].remetente.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].remetente.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].remetente.telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `objetos[].eventos[].remetente.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].remetente.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].remetente.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario` | objeto `Destinatario` |  | **Destinatário** — Classe que representa o destinatário do objeto |
| `objetos[].eventos[].destinatario.nome` | string |  | Nome do destinatário — maxLength: `72` |
| `objetos[].eventos[].destinatario.documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `objetos[].eventos[].destinatario.email` | string |  | Email do destinatário — maxLength: `60` |
| `objetos[].eventos[].destinatario.telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `objetos[].eventos[].destinatario.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].destinatario.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].destinatario.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `objetos[].eventos[].destinatario.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].destinatario.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].destinatario.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].destinatario.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].destinatario.endereco.pais` | string |  |  |
| `objetos[].eventos[].destinatario.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].destinatario.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].dtLimiteRetirada` | string (date-time) |  | **Prazo de Retirada** — Prazo de retirada de objeto lançado em LDI |
| `objetos[].eventos[].comentario` | string |  | **Comentário** — Comentário informado pelo usuário referente ao evento — maxLength: `100` |
| `objetos[].eventos[].codLista` | string |  | Recipiente — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino` | objeto `Destino` |  | **Destino** — Classe que representa o destino de um objeto |
| `objetos[].eventos[].unidadeDestino.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidadeDestino.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidadeDestino.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidadeDestino.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidadeDestino.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidadeDestino.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].unidadeDestino.recipiente` | string |  | Recipiente — maxLength: `13` |
| `objetos[].servico` | objeto `Servico` |  | **Serviço** — Classe que representa o serviço utilizado pelo objeto |
| `objetos[].servico.codigo` | string |  | Código do serviço — maxLength: `20` |
| `objetos[].servico.ar` | string |  | Possui serviço adicional Aviso de Recebimento (AR) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.tipoAr` | string |  | Tipo de AR — Valores: `N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)` |
| `objetos[].servico.mp` | string |  | Possui serviço adicional Mão-Própria (MP) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.vd` | string |  | Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor — maxLength: `11` |
| `objetos[].servico.eVizinho` | string |  | Possui serviço adicional Entrega no Vizinho — Valores: `S(Sim);  N(Não)` |
| `objetos[].servico.fotoFachada` | string |  | Possui serviço adicional de Foto Fachada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eControlada` | string |  | Possui serviço adicional de Entrega Controlada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eInterativa` | string |  | Possui serviço adicional de Entrega Interativa — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eLocker` | string |  | Possui serviço adicional de Entrega Locker — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.devDocumento` | string |  | Possui serviço adicional de Devolução de Documentos — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.dgr` | string |  | Possui serviço adicional de Transporte e artigos perigosos — Valores: `S(Sim); N(Não)` |
| `tipoResultado` | string |  | **Resultado** — Resultado da pesquisa — Valores: `Todos os Eventos, Primeiro Evento, Último Evento` — maxLength: `16` |

Exemplo de resposta 200:

```json
{
  "versao": "string",
  "quantidade": 0,
  "objetos": [
    {
      "codObjeto": "string",
      "tipoPostal": {
        "sigla": "string",
        "nome": "string",
        "categoria": "string",
        "familia": "string"
      },
      "mensagem": "string",
      "dtPrevista": "2026-01-01T00:00:00",
      "multiVolume": "string",
      "afterschedule": "S",
      "volume": 0,
      "valorRecebido": "string",
      "entregaProgramada": "2026-01-01T00:00:00",
      "prazoTratamento": 0,
      "contrato": "string",
      "codAdm": "string",
      "cartaoPostagem": "string",
      "CNPJ": "string",
      "largura": 0,
      "comprimento": 0,
      "altura": 0,
      "diametro": 0,
      "peso": 0,
      "formato": "string",
      "celRemetSMS": "string",
      "celDestSMS": "string",
      "valorDeclarado": 0,
      "eventos": [
        {
          "codigo": "string",
          "tipo": "string",
          "dtHrCriado": "2026-01-01T00:00:00",
          "descricao": "string",
          "estacao": "string",
          "usuario": "string",
          "carteiro": "string",
          "entregador": "string",
          "latitude": "string",
          "longitude": "string",
          "detalhe": "string",
          "descCodigo": "string",
          "dtHrGravado": "2026-01-01T00:00:00",
          "token": "string",
          "recebedor": {
            "nome": "string",
            "documento": "string",
            "celular": "string",
            "email": "string",
            "comentario": "string"
          },
          "unidade": {
            "nome": "string",
            "codSro": "string",
            "codMcu": "string",
            "tipo": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "codse": "string",
            "se": "string"
          },
          "entregadorExterno": {
            "documento": "string",
            "nome": "string"
          },
          "contrato": "string",
          "codAdm": "string",
          "cartaoPostagem": "string",
          "CNPJ": "string",
          "servico": "string",
          "remetente": {
            "nome": "string",
            "documento": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "telefones": [
              {
                "tipo": "Fixo, Celular (Cel)",
                "ddd": "string",
                "numero": "string"
              }
            ]
          },
          "destinatario": {
            "nome": "string",
            "documento": "string",
            "email": "string",
            "telefones": [
              {
                "tipo": "Fixo, Celular (Cel)",
                "ddd": "string",
                "numero": "string"
              }
            ],
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            }
          },
          "dtLimiteRetirada": "2026-01-01T00:00:00",
          "comentario": "string",
          "codLista": "string",
          "unidadeDestino": {
            "nome": "string",
            "codSro": "string",
            "codMcu": "string",
            "tipo": "string",
            "endereco": {
              "cep": "string",
              "logradouro": "string",
              "complemento": "string",
              "numero": "string",
              "bairro": "string",
              "cidade": "string",
              "uf": "string",
              "pais": "string",
              "telefone": "string",
              "erro": [
                "string"
              ]
            },
            "codse": "string",
            "se": "string",
            "recipiente": "string"
          }
        }
      ],
      "servico": {
        "codigo": "string",
        "ar": "S(Sim); N(Não)",
        "tipoAr": "N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)",
        "mp": "S(Sim); N(Não)",
        "vd": "string",
        "eVizinho": "S(Sim);  N(Não)",
        "fotoFachada": "S(Sim); N(Não)",
        "eControlada": "S(Sim); N(Não)",
        "eInterativa": "S(Sim); N(Não)",
        "eLocker": "S(Sim); N(Não)",
        "devDocumento": "S(Sim); N(Não)",
        "dgr": "S(Sim); N(Não)"
      }
    }
  ],
  "tipoResultado": "Todos os Eventos, Primeiro Evento, Último Evento"
}
```

### Imagens

Imagens de baixas de objetos

#### 1.3 `POST /v1/objetos/imagens`

**Imagens**

Método para consultar as imagens de baixa de uma lista de objetos

- URL (produção): `https://api.correios.com.br/srorastro/v1/objetos/imagens`
- URL (homologação): `https://apihom.correios.com.br/srorastro/v1/objetos/imagens`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `consultaImagemObjeto`

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/srorastro/v1/objetos/imagens
Content-Type: application/json
Authorization: Bearer <token>

["AA123456789BR","BB987654321BR"]
```

**Corpo da requisição** (obrigatório) — Lista de objetos, com 13 caracteres cada, para pesquisa de imagens: array<string> — lista de códigos de objeto (13 caracteres cada).

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `202` | Um objeto Recibo contendo as informações para consulta futura | `Recibo` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 202:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string |  | **Número do Recibo** — Número do recibo — maxLength: `32` |
| `dtCriacao` | string (date-time) |  | **Data de Geração do Recibo** — Data |
| `dtValidade` | string (date-time) |  | **Data de validade** — Data de Validade do recibo |
| `qtdObjetos` | integer (int32) |  | **Objetos** — Quantidade de objetos para rastreamento — maxLength: `5` |
| `resultado` | string |  | **Resultado** — Qual o evento para o resultado do rastreamento: Primeiro, Último ou Todos — Valores: `P`, `U`, `T` — maxLength: `1` |
| `idioma` | string |  | **Idioma** — Idioma para solicitação do rastreamento — Valores: `PT`, `EN`, `ES` — maxLength: `2` |

Exemplo de resposta 202:

```json
{
  "numero": "string",
  "dtCriacao": "2026-01-01T00:00:00",
  "dtValidade": "2026-01-01T00:00:00",
  "qtdObjetos": 0,
  "resultado": "P",
  "idioma": "PT"
}
```

#### 1.4 `GET /v1/recibo/{recibo}`

**Consulta recibo**

Método para consultar o resultado de processamento de um determinado recibo

- URL (produção): `https://api.correios.com.br/srorastro/v1/recibo/{recibo}`
- URL (homologação): `https://apihom.correios.com.br/srorastro/v1/recibo/{recibo}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaRecibo`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `recibo` | path | string | ✔ | Número do recibo para pesquisa — Ex.: `d2ddbca351cf455c939d694cefb23611` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/srorastro/v1/recibo/d2ddbca351cf455c939d694cefb23611
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Uma coleção contendo as informações sobre os recibos de entregas do objetos solicitados | array<objeto `ObjetoImagemTO`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].objeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `[].observacao` | string |  | **Observação** — Observação da imagem do objeto — maxLength: `40` |
| `[].imagens` | array<objeto `ImagemTO`> |  | **Imagens** — Lista de imagens dos eventos do objeto |
| `[].imagens[].imagem` | string (byte) |  | **Imagem** — Imagem da baixa do objeto. String contendo um array de bytes no formato Base64 que representa a imagem |
| `[].imagens[].comentario` | string |  | **Observações da Imagem** — Informação complementar referente a imagem do evento de baixa — maxLength: `20` |

Exemplo de resposta 200:

```json
[
  {
    "objeto": "string",
    "observacao": "string",
    "imagens": [
      {
        "imagem": "string",
        "comentario": "string"
      }
    ]
  }
]
```

### Aviso de Recebimento

Imagens dos AR Digital

#### 1.5 `GET /v1/ar-digital`

**AR digital**

Método para consultar a imagem do AR digital do objeto

- URL (produção): `https://api.correios.com.br/srorastro/v1/ar-digital`
- URL (homologação): `https://apihom.correios.com.br/srorastro/v1/ar-digital`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaArDigital`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `objetos` | query | array<string> | ✔ | Lista de etiquetas do objeto com 13 caracteres para consulta de sua imagem de baixa, separados por vírgila. Limite de 10 objetos por consulta — minItems: `1` — maxItems: `10` — Ex.: `AA123456789BR,AA123456789BR` |

> Conforme a descrição do parâmetro, os códigos vão **separados por vírgula** (`objetos=A,B`), no máximo 10.

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/srorastro/v1/ar-digital?objetos=AA123456789BR,BB987654321BR
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Imagens do AR Digital, se existir | array<objeto `ArDigitalTO`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].objeto` | string |  |  |
| `[].dataBaixa` | string (date-time) |  |  |
| `[].contentType` | string |  |  |
| `[].imagemBase64` | string |  |  |
| `[].formato` | string |  |  |
| `[].resultadoProcessamento` | objeto `ResultadoProcessamento` |  | **Resultado do Processamento** — Classe que representa o resultado do processamento de uma operação |
| `[].resultadoProcessamento.sucesso` | boolean |  | **Sucesso** — Indica se o processamento foi realizado com sucesso |
| `[].resultadoProcessamento.codigo` | string |  | Código do erro, quando houver |
| `[].resultadoProcessamento.resultado` | string |  | **Resultado do Processamento** — Mensagem descritiva do resultado do processamento |

Exemplo de resposta 200:

```json
[
  {
    "objeto": "string",
    "dataBaixa": "2026-01-01T00:00:00",
    "contentType": "string",
    "imagemBase64": "string",
    "formato": "string",
    "resultadoProcessamento": {
      "sucesso": false,
      "codigo": "string",
      "resultado": "string"
    }
  }
]
```

## 2. Schemas (todos os campos expandidos)

### `ArDigitalTO`

Informações do AR Digital

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `objeto` | string |  |  |
| `dataBaixa` | string (date-time) |  |  |
| `contentType` | string |  |  |
| `imagemBase64` | string |  |  |
| `formato` | string |  |  |
| `resultadoProcessamento` | objeto `ResultadoProcessamento` |  | **Resultado do Processamento** — Classe que representa o resultado do processamento de uma operação |
| `resultadoProcessamento.sucesso` | boolean |  | **Sucesso** — Indica se o processamento foi realizado com sucesso |
| `resultadoProcessamento.codigo` | string |  | Código do erro, quando houver |
| `resultadoProcessamento.resultado` | string |  | **Resultado do Processamento** — Mensagem descritiva do resultado do processamento |

### `Destinatario`

Classe que representa o destinatário do objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | Nome do destinatário — maxLength: `72` |
| `documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `email` | string |  | Email do destinatário — maxLength: `60` |
| `telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `endereco.pais` | string |  |  |
| `endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `endereco.erro` | array<string> |  |  |

### `Destino`

Classe que representa o destino de um objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `endereco.pais` | string |  |  |
| `endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `endereco.erro` | array<string> |  |  |
| `codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `recipiente` | string |  | Recipiente — maxLength: `13` |

### `Endereco`

Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `numero` | string |  | **Número do endereço** — maxLength: `12` |
| `bairro` | string |  | **Bairro** — maxLength: `60` |
| `cidade` | string |  | **Cidade** — maxLength: `60` |
| `uf` | string |  | **Estado** — maxLength: `2` |
| `pais` | string |  |  |
| `telefone` | string |  | **Telefone** — maxLength: `13` |
| `erro` | array<string> |  |  |

### `EntregadorExterno`

Dados do entregador externo do objeto.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |

### `Evento`

Classe que representa os eventos de um objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  | **Código** — Código do evento de rastreamento — maxLength: `3` |
| `tipo` | string |  | **Motivo** — Tipo do evento. Representa o status ou motivo do evento — maxLength: `2` |
| `dtHrCriado` | string (date-time) |  | **Criação** — Data e hora da criação do evento |
| `descricao` | string |  | **Descrição** — Descrição do evento — maxLength: `80` |
| `estacao` | string |  | Estação de trabalho responsável pelo registro do evento — maxLength: `3` |
| `usuario` | string |  | Usuário responsável pelo registro do evento — maxLength: `8` |
| `carteiro` | string |  | Matrícula do carteiro que realizou o evento — maxLength: `8` |
| `entregador` | string |  | Identificação do entregador — maxLength: `13` |
| `latitude` | string |  | **Latitude** — Latitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `longitude` | string |  | **Longitude** — Longitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `detalhe` | string |  | Descrição do detalhe do evento — maxLength: `80` |
| `descCodigo` | string |  | Descrição da sigla do código do evento — maxLength: `80` |
| `dtHrGravado` | string (date-time) |  | Data e hora da gravação do evento |
| `token` | string |  | **Token de atendimento** — Token de atendimento vinculado ao evento LDI |
| `recebedor` | objeto `Recebedor` |  | **Recebedor** — Classe que representa um recebedor |
| `recebedor.nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `recebedor.documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `recebedor.celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `recebedor.email` | string |  | **Email do recebedor** — maxLength: `60` |
| `recebedor.comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |
| `unidade` | objeto `Unidade` |  | **Unidade de Tratamento** — Classe que representa uma unidade de tratamento do objeto |
| `unidade.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `unidade.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `unidade.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `unidade.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `unidade.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `unidade.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `unidade.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `unidade.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `unidade.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `unidade.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `unidade.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `unidade.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `unidade.endereco.pais` | string |  |  |
| `unidade.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `unidade.endereco.erro` | array<string> |  |  |
| `unidade.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `unidade.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `entregadorExterno` | objeto `EntregadorExterno` |  | **Entregador Externo** — Dados do entregador externo do objeto. |
| `entregadorExterno.documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `entregadorExterno.nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |
| `contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `servico` | string |  |  |
| `remetente` | objeto `Remetente` |  | **Remetente** — Classe que representa o remetente do objeto |
| `remetente.nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `remetente.documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `remetente.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `remetente.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `remetente.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `remetente.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `remetente.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `remetente.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `remetente.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `remetente.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `remetente.endereco.pais` | string |  |  |
| `remetente.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `remetente.endereco.erro` | array<string> |  |  |
| `remetente.telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `remetente.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `remetente.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `remetente.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `destinatario` | objeto `Destinatario` |  | **Destinatário** — Classe que representa o destinatário do objeto |
| `destinatario.nome` | string |  | Nome do destinatário — maxLength: `72` |
| `destinatario.documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `destinatario.email` | string |  | Email do destinatário — maxLength: `60` |
| `destinatario.telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `destinatario.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `destinatario.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `destinatario.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `destinatario.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `destinatario.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `destinatario.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `destinatario.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `destinatario.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `destinatario.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `destinatario.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `destinatario.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `destinatario.endereco.pais` | string |  |  |
| `destinatario.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `destinatario.endereco.erro` | array<string> |  |  |
| `dtLimiteRetirada` | string (date-time) |  | **Prazo de Retirada** — Prazo de retirada de objeto lançado em LDI |
| `comentario` | string |  | **Comentário** — Comentário informado pelo usuário referente ao evento — maxLength: `100` |
| `codLista` | string |  | Recipiente — maxLength: `13` |
| `unidadeDestino` | objeto `Destino` |  | **Destino** — Classe que representa o destino de um objeto |
| `unidadeDestino.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `unidadeDestino.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `unidadeDestino.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `unidadeDestino.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `unidadeDestino.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `unidadeDestino.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `unidadeDestino.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `unidadeDestino.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `unidadeDestino.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `unidadeDestino.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `unidadeDestino.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `unidadeDestino.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `unidadeDestino.endereco.pais` | string |  |  |
| `unidadeDestino.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `unidadeDestino.endereco.erro` | array<string> |  |  |
| `unidadeDestino.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `unidadeDestino.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `unidadeDestino.recipiente` | string |  | Recipiente — maxLength: `13` |

### `ImagemTO`

Informações da imagem de baixa de um objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `imagem` | string (byte) |  | **Imagem** — Imagem da baixa do objeto. String contendo um array de bytes no formato Base64 que representa a imagem |
| `comentario` | string |  | **Observações da Imagem** — Informação complementar referente a imagem do evento de baixa — maxLength: `20` |

### `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

### `Objeto`

Classe que representa um objeto do SRO

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codObjeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `tipoPostal` | objeto `TipoPostal` |  | Tipo Postal |
| `tipoPostal.sigla` | string |  | **Sigla** — Sigla do tipo postal — maxLength: `2` |
| `tipoPostal.nome` | string |  | **Nome** — Descrição do tipo postal — maxLength: `40` |
| `tipoPostal.categoria` | object |  | **Categoria** — Categoria do tipo postal |
| `tipoPostal.familia` | string |  | **Familia** — Familia do tipo postal — maxLength: `30` |
| `mensagem` | string |  | Mensagem de erro — maxLength: `500` |
| `dtPrevista` | string (date-time) |  | **Data Prevista** — Data prevista para entrega do objeto |
| `multiVolume` | string |  | **Multi Volume** — Quantidade de volumes para o objeto — maxLength: `5` |
| `afterschedule` | string |  | **Depois do Horário** — Objeto postado depois do horário — Valores: `S`, `N` |
| `volume` | integer (int32) |  | **Volume** — Volume do objeto |
| `valorRecebido` | string |  | **Valor Recebido** — Valor recebido — maxLength: `11` |
| `entregaProgramada` | string (date-time) |  | **Entrega Programada** — Data programada para entrega do objeto |
| `prazoTratamento` | integer (int32) |  | **Prazo de Tratamento** — Prazo para tratamento do objeto |
| `contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `largura` | number |  | **Largura** — Largura do objeto em cm — maxLength: `4` |
| `comprimento` | number |  | **Comprimento** — Comprimento do objeto em cm — maxLength: `4` |
| `altura` | number |  | **Altura** — Altura do objeto em cm — maxLength: `4` |
| `diametro` | number |  | **Diâmetro** — Diametro do objeto em cm — maxLength: `4` |
| `peso` | number |  | **Peso** — Peso do objeto em gramas — maxLength: `10` |
| `formato` | string |  | **Formato** — Formato do objeto — maxLength: `40` |
| `celRemetSMS` | string |  |  |
| `celDestSMS` | string |  |  |
| `valorDeclarado` | number |  | **Valor Declarado** — Valor declarado com duas casas de precisão — maxLength: `11` |
| `eventos` | array<objeto `Evento`> |  | **Eventos** — Eventos do Objeto |
| `eventos[].codigo` | string |  | **Código** — Código do evento de rastreamento — maxLength: `3` |
| `eventos[].tipo` | string |  | **Motivo** — Tipo do evento. Representa o status ou motivo do evento — maxLength: `2` |
| `eventos[].dtHrCriado` | string (date-time) |  | **Criação** — Data e hora da criação do evento |
| `eventos[].descricao` | string |  | **Descrição** — Descrição do evento — maxLength: `80` |
| `eventos[].estacao` | string |  | Estação de trabalho responsável pelo registro do evento — maxLength: `3` |
| `eventos[].usuario` | string |  | Usuário responsável pelo registro do evento — maxLength: `8` |
| `eventos[].carteiro` | string |  | Matrícula do carteiro que realizou o evento — maxLength: `8` |
| `eventos[].entregador` | string |  | Identificação do entregador — maxLength: `13` |
| `eventos[].latitude` | string |  | **Latitude** — Latitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `eventos[].longitude` | string |  | **Longitude** — Longitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `eventos[].detalhe` | string |  | Descrição do detalhe do evento — maxLength: `80` |
| `eventos[].descCodigo` | string |  | Descrição da sigla do código do evento — maxLength: `80` |
| `eventos[].dtHrGravado` | string (date-time) |  | Data e hora da gravação do evento |
| `eventos[].token` | string |  | **Token de atendimento** — Token de atendimento vinculado ao evento LDI |
| `eventos[].recebedor` | objeto `Recebedor` |  | **Recebedor** — Classe que representa um recebedor |
| `eventos[].recebedor.nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `eventos[].recebedor.documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `eventos[].recebedor.celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `eventos[].recebedor.email` | string |  | **Email do recebedor** — maxLength: `60` |
| `eventos[].recebedor.comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |
| `eventos[].unidade` | objeto `Unidade` |  | **Unidade de Tratamento** — Classe que representa uma unidade de tratamento do objeto |
| `eventos[].unidade.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `eventos[].unidade.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `eventos[].unidade.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `eventos[].unidade.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `eventos[].unidade.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `eventos[].unidade.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `eventos[].unidade.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `eventos[].unidade.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `eventos[].unidade.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `eventos[].unidade.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `eventos[].unidade.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `eventos[].unidade.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `eventos[].unidade.endereco.pais` | string |  |  |
| `eventos[].unidade.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `eventos[].unidade.endereco.erro` | array<string> |  |  |
| `eventos[].unidade.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `eventos[].unidade.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `eventos[].entregadorExterno` | objeto `EntregadorExterno` |  | **Entregador Externo** — Dados do entregador externo do objeto. |
| `eventos[].entregadorExterno.documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `eventos[].entregadorExterno.nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |
| `eventos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `eventos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `eventos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `eventos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `eventos[].servico` | string |  |  |
| `eventos[].remetente` | objeto `Remetente` |  | **Remetente** — Classe que representa o remetente do objeto |
| `eventos[].remetente.nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `eventos[].remetente.documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `eventos[].remetente.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `eventos[].remetente.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `eventos[].remetente.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `eventos[].remetente.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `eventos[].remetente.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `eventos[].remetente.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `eventos[].remetente.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `eventos[].remetente.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `eventos[].remetente.endereco.pais` | string |  |  |
| `eventos[].remetente.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `eventos[].remetente.endereco.erro` | array<string> |  |  |
| `eventos[].remetente.telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `eventos[].remetente.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `eventos[].remetente.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `eventos[].remetente.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `eventos[].destinatario` | objeto `Destinatario` |  | **Destinatário** — Classe que representa o destinatário do objeto |
| `eventos[].destinatario.nome` | string |  | Nome do destinatário — maxLength: `72` |
| `eventos[].destinatario.documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `eventos[].destinatario.email` | string |  | Email do destinatário — maxLength: `60` |
| `eventos[].destinatario.telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `eventos[].destinatario.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `eventos[].destinatario.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `eventos[].destinatario.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `eventos[].destinatario.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `eventos[].destinatario.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `eventos[].destinatario.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `eventos[].destinatario.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `eventos[].destinatario.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `eventos[].destinatario.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `eventos[].destinatario.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `eventos[].destinatario.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `eventos[].destinatario.endereco.pais` | string |  |  |
| `eventos[].destinatario.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `eventos[].destinatario.endereco.erro` | array<string> |  |  |
| `eventos[].dtLimiteRetirada` | string (date-time) |  | **Prazo de Retirada** — Prazo de retirada de objeto lançado em LDI |
| `eventos[].comentario` | string |  | **Comentário** — Comentário informado pelo usuário referente ao evento — maxLength: `100` |
| `eventos[].codLista` | string |  | Recipiente — maxLength: `13` |
| `eventos[].unidadeDestino` | objeto `Destino` |  | **Destino** — Classe que representa o destino de um objeto |
| `eventos[].unidadeDestino.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `eventos[].unidadeDestino.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `eventos[].unidadeDestino.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `eventos[].unidadeDestino.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `eventos[].unidadeDestino.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `eventos[].unidadeDestino.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `eventos[].unidadeDestino.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `eventos[].unidadeDestino.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `eventos[].unidadeDestino.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `eventos[].unidadeDestino.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `eventos[].unidadeDestino.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `eventos[].unidadeDestino.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `eventos[].unidadeDestino.endereco.pais` | string |  |  |
| `eventos[].unidadeDestino.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `eventos[].unidadeDestino.endereco.erro` | array<string> |  |  |
| `eventos[].unidadeDestino.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `eventos[].unidadeDestino.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `eventos[].unidadeDestino.recipiente` | string |  | Recipiente — maxLength: `13` |
| `servico` | objeto `Servico` |  | **Serviço** — Classe que representa o serviço utilizado pelo objeto |
| `servico.codigo` | string |  | Código do serviço — maxLength: `20` |
| `servico.ar` | string |  | Possui serviço adicional Aviso de Recebimento (AR) — Valores: `S(Sim); N(Não)` |
| `servico.tipoAr` | string |  | Tipo de AR — Valores: `N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)` |
| `servico.mp` | string |  | Possui serviço adicional Mão-Própria (MP) — Valores: `S(Sim); N(Não)` |
| `servico.vd` | string |  | Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor — maxLength: `11` |
| `servico.eVizinho` | string |  | Possui serviço adicional Entrega no Vizinho — Valores: `S(Sim);  N(Não)` |
| `servico.fotoFachada` | string |  | Possui serviço adicional de Foto Fachada — Valores: `S(Sim); N(Não)` |
| `servico.eControlada` | string |  | Possui serviço adicional de Entrega Controlada — Valores: `S(Sim); N(Não)` |
| `servico.eInterativa` | string |  | Possui serviço adicional de Entrega Interativa — Valores: `S(Sim); N(Não)` |
| `servico.eLocker` | string |  | Possui serviço adicional de Entrega Locker — Valores: `S(Sim); N(Não)` |
| `servico.devDocumento` | string |  | Possui serviço adicional de Devolução de Documentos — Valores: `S(Sim); N(Não)` |
| `servico.dgr` | string |  | Possui serviço adicional de Transporte e artigos perigosos — Valores: `S(Sim); N(Não)` |

### `ObjetoImagemTO`

Classe que representa um objeto com suas respectivas imagens de baixa

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `objeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `observacao` | string |  | **Observação** — Observação da imagem do objeto — maxLength: `40` |
| `imagens` | array<objeto `ImagemTO`> |  | **Imagens** — Lista de imagens dos eventos do objeto |
| `imagens[].imagem` | string (byte) |  | **Imagem** — Imagem da baixa do objeto. String contendo um array de bytes no formato Base64 que representa a imagem |
| `imagens[].comentario` | string |  | **Observações da Imagem** — Informação complementar referente a imagem do evento de baixa — maxLength: `20` |

### `Recebedor`

Classe que representa um recebedor

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `email` | string |  | **Email do recebedor** — maxLength: `60` |
| `comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |

### `Recibo`

Representa os dados do recibo de uma lista de objetos

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string |  | **Número do Recibo** — Número do recibo — maxLength: `32` |
| `dtCriacao` | string (date-time) |  | **Data de Geração do Recibo** — Data |
| `dtValidade` | string (date-time) |  | **Data de validade** — Data de Validade do recibo |
| `qtdObjetos` | integer (int32) |  | **Objetos** — Quantidade de objetos para rastreamento — maxLength: `5` |
| `resultado` | string |  | **Resultado** — Qual o evento para o resultado do rastreamento: Primeiro, Último ou Todos — Valores: `P`, `U`, `T` — maxLength: `1` |
| `idioma` | string |  | **Idioma** — Idioma para solicitação do rastreamento — Valores: `PT`, `EN`, `ES` — maxLength: `2` |

### `Remetente`

Classe que representa o remetente do objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `endereco.pais` | string |  |  |
| `endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `endereco.erro` | array<string> |  |  |
| `telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `telefones[].numero` | string |  | Número do telefone — maxLength: `11` |

### `ResultadoProcessamento`

Classe que representa o resultado do processamento de uma operação

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `sucesso` | boolean |  | **Sucesso** — Indica se o processamento foi realizado com sucesso |
| `codigo` | string |  | Código do erro, quando houver |
| `resultado` | string |  | **Resultado do Processamento** — Mensagem descritiva do resultado do processamento |

### `SRO`

Classe que representa o SRO

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `versao` | string |  | **Versão** — Versão da api em execução — maxLength: `8` |
| `quantidade` | integer (int32) |  | **Quantidade** — Quantidade de objetos rastreados — maxLength: `5` |
| `objetos` | array<objeto `Objeto`> |  | **Objetos** — Lista de objetos com seu respectivo rastreamento |
| `objetos[].codObjeto` | string |  | **Código do Objeto** — Código do objeto — maxLength: `13` |
| `objetos[].tipoPostal` | objeto `TipoPostal` |  | Tipo Postal |
| `objetos[].tipoPostal.sigla` | string |  | **Sigla** — Sigla do tipo postal — maxLength: `2` |
| `objetos[].tipoPostal.nome` | string |  | **Nome** — Descrição do tipo postal — maxLength: `40` |
| `objetos[].tipoPostal.categoria` | object |  | **Categoria** — Categoria do tipo postal |
| `objetos[].tipoPostal.familia` | string |  | **Familia** — Familia do tipo postal — maxLength: `30` |
| `objetos[].mensagem` | string |  | Mensagem de erro — maxLength: `500` |
| `objetos[].dtPrevista` | string (date-time) |  | **Data Prevista** — Data prevista para entrega do objeto |
| `objetos[].multiVolume` | string |  | **Multi Volume** — Quantidade de volumes para o objeto — maxLength: `5` |
| `objetos[].afterschedule` | string |  | **Depois do Horário** — Objeto postado depois do horário — Valores: `S`, `N` |
| `objetos[].volume` | integer (int32) |  | **Volume** — Volume do objeto |
| `objetos[].valorRecebido` | string |  | **Valor Recebido** — Valor recebido — maxLength: `11` |
| `objetos[].entregaProgramada` | string (date-time) |  | **Entrega Programada** — Data programada para entrega do objeto |
| `objetos[].prazoTratamento` | integer (int32) |  | **Prazo de Tratamento** — Prazo para tratamento do objeto |
| `objetos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].largura` | number |  | **Largura** — Largura do objeto em cm — maxLength: `4` |
| `objetos[].comprimento` | number |  | **Comprimento** — Comprimento do objeto em cm — maxLength: `4` |
| `objetos[].altura` | number |  | **Altura** — Altura do objeto em cm — maxLength: `4` |
| `objetos[].diametro` | number |  | **Diâmetro** — Diametro do objeto em cm — maxLength: `4` |
| `objetos[].peso` | number |  | **Peso** — Peso do objeto em gramas — maxLength: `10` |
| `objetos[].formato` | string |  | **Formato** — Formato do objeto — maxLength: `40` |
| `objetos[].celRemetSMS` | string |  |  |
| `objetos[].celDestSMS` | string |  |  |
| `objetos[].valorDeclarado` | number |  | **Valor Declarado** — Valor declarado com duas casas de precisão — maxLength: `11` |
| `objetos[].eventos` | array<objeto `Evento`> |  | **Eventos** — Eventos do Objeto |
| `objetos[].eventos[].codigo` | string |  | **Código** — Código do evento de rastreamento — maxLength: `3` |
| `objetos[].eventos[].tipo` | string |  | **Motivo** — Tipo do evento. Representa o status ou motivo do evento — maxLength: `2` |
| `objetos[].eventos[].dtHrCriado` | string (date-time) |  | **Criação** — Data e hora da criação do evento |
| `objetos[].eventos[].descricao` | string |  | **Descrição** — Descrição do evento — maxLength: `80` |
| `objetos[].eventos[].estacao` | string |  | Estação de trabalho responsável pelo registro do evento — maxLength: `3` |
| `objetos[].eventos[].usuario` | string |  | Usuário responsável pelo registro do evento — maxLength: `8` |
| `objetos[].eventos[].carteiro` | string |  | Matrícula do carteiro que realizou o evento — maxLength: `8` |
| `objetos[].eventos[].entregador` | string |  | Identificação do entregador — maxLength: `13` |
| `objetos[].eventos[].latitude` | string |  | **Latitude** — Latitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].longitude` | string |  | **Longitude** — Longitude composta por 25 números inteiros e 21 decimais — maxLength: `46` |
| `objetos[].eventos[].detalhe` | string |  | Descrição do detalhe do evento — maxLength: `80` |
| `objetos[].eventos[].descCodigo` | string |  | Descrição da sigla do código do evento — maxLength: `80` |
| `objetos[].eventos[].dtHrGravado` | string (date-time) |  | Data e hora da gravação do evento |
| `objetos[].eventos[].token` | string |  | **Token de atendimento** — Token de atendimento vinculado ao evento LDI |
| `objetos[].eventos[].recebedor` | objeto `Recebedor` |  | **Recebedor** — Classe que representa um recebedor |
| `objetos[].eventos[].recebedor.nome` | string |  | **Nome do recebedor** — maxLength: `72` |
| `objetos[].eventos[].recebedor.documento` | string |  | **Documento de identificação do recebedor** — maxLength: `25` |
| `objetos[].eventos[].recebedor.celular` | string |  | **Numero do celular do recebedor** — maxLength: `13` |
| `objetos[].eventos[].recebedor.email` | string |  | **Email do recebedor** — maxLength: `60` |
| `objetos[].eventos[].recebedor.comentario` | string |  | **Alguma observação referente ao recebedor** — maxLength: `50` |
| `objetos[].eventos[].unidade` | objeto `Unidade` |  | **Unidade de Tratamento** — Classe que representa uma unidade de tratamento do objeto |
| `objetos[].eventos[].unidade.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidade.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidade.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidade.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidade.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidade.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidade.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidade.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidade.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidade.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidade.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidade.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidade.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidade.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].entregadorExterno` | objeto `EntregadorExterno` |  | **Entregador Externo** — Dados do entregador externo do objeto. |
| `objetos[].eventos[].entregadorExterno.documento` | string |  | **CPF** — Número do CPF do entregador externo — maxLength: `15` |
| `objetos[].eventos[].entregadorExterno.nome` | string |  | **Nome** — Nome do entregador externo — maxLength: `80` |
| `objetos[].eventos[].contrato` | string |  | **Contrato** — Número do contrato utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].codAdm` | string |  | **Código Administrativo** — Código Administrativo do contrato — maxLength: `15` |
| `objetos[].eventos[].cartaoPostagem` | string |  | **Cartão de Postagem** — Número do cartão de postagem utilizado para postagem do objeto — maxLength: `15` |
| `objetos[].eventos[].CNPJ` | string |  | **CNPJ** — Número do CNPJ — maxLength: `25` |
| `objetos[].eventos[].servico` | string |  |  |
| `objetos[].eventos[].remetente` | objeto `Remetente` |  | **Remetente** — Classe que representa o remetente do objeto |
| `objetos[].eventos[].remetente.nome` | string |  | Nome do remetente do objeto — maxLength: `72` |
| `objetos[].eventos[].remetente.documento` | string |  | Documento de identificação do remetente do objeto — maxLength: `25` |
| `objetos[].eventos[].remetente.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do remetente |
| `objetos[].eventos[].remetente.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].remetente.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].remetente.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].remetente.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].remetente.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].remetente.endereco.pais` | string |  |  |
| `objetos[].eventos[].remetente.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].remetente.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].remetente.telefones` | array<objeto `Telefone`> |  | Telefones do remetente |
| `objetos[].eventos[].remetente.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].remetente.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].remetente.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario` | objeto `Destinatario` |  | **Destinatário** — Classe que representa o destinatário do objeto |
| `objetos[].eventos[].destinatario.nome` | string |  | Nome do destinatário — maxLength: `72` |
| `objetos[].eventos[].destinatario.documento` | string |  | Número do documento de identificação do destinatário — maxLength: `25` |
| `objetos[].eventos[].destinatario.email` | string |  | Email do destinatário — maxLength: `60` |
| `objetos[].eventos[].destinatario.telefones` | array<objeto `Telefone`> |  | Telefone do destinatário |
| `objetos[].eventos[].destinatario.telefones[].tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `objetos[].eventos[].destinatario.telefones[].ddd` | string |  | Código DDD — maxLength: `2` |
| `objetos[].eventos[].destinatario.telefones[].numero` | string |  | Número do telefone — maxLength: `11` |
| `objetos[].eventos[].destinatario.endereco` | objeto `Endereco` |  | **Endereço** — Endereço do destinatário |
| `objetos[].eventos[].destinatario.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].destinatario.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].destinatario.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].destinatario.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].destinatario.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].destinatario.endereco.pais` | string |  |  |
| `objetos[].eventos[].destinatario.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].destinatario.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].dtLimiteRetirada` | string (date-time) |  | **Prazo de Retirada** — Prazo de retirada de objeto lançado em LDI |
| `objetos[].eventos[].comentario` | string |  | **Comentário** — Comentário informado pelo usuário referente ao evento — maxLength: `100` |
| `objetos[].eventos[].codLista` | string |  | Recipiente — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino` | objeto `Destino` |  | **Destino** — Classe que representa o destino de um objeto |
| `objetos[].eventos[].unidadeDestino.nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `objetos[].eventos[].unidadeDestino.codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `objetos[].eventos[].unidadeDestino.endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `objetos[].eventos[].unidadeDestino.endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `objetos[].eventos[].unidadeDestino.endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `objetos[].eventos[].unidadeDestino.endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `objetos[].eventos[].unidadeDestino.endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.endereco.pais` | string |  |  |
| `objetos[].eventos[].unidadeDestino.endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `objetos[].eventos[].unidadeDestino.endereco.erro` | array<string> |  |  |
| `objetos[].eventos[].unidadeDestino.codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `objetos[].eventos[].unidadeDestino.se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |
| `objetos[].eventos[].unidadeDestino.recipiente` | string |  | Recipiente — maxLength: `13` |
| `objetos[].servico` | objeto `Servico` |  | **Serviço** — Classe que representa o serviço utilizado pelo objeto |
| `objetos[].servico.codigo` | string |  | Código do serviço — maxLength: `20` |
| `objetos[].servico.ar` | string |  | Possui serviço adicional Aviso de Recebimento (AR) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.tipoAr` | string |  | Tipo de AR — Valores: `N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)` |
| `objetos[].servico.mp` | string |  | Possui serviço adicional Mão-Própria (MP) — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.vd` | string |  | Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor — maxLength: `11` |
| `objetos[].servico.eVizinho` | string |  | Possui serviço adicional Entrega no Vizinho — Valores: `S(Sim);  N(Não)` |
| `objetos[].servico.fotoFachada` | string |  | Possui serviço adicional de Foto Fachada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eControlada` | string |  | Possui serviço adicional de Entrega Controlada — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eInterativa` | string |  | Possui serviço adicional de Entrega Interativa — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.eLocker` | string |  | Possui serviço adicional de Entrega Locker — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.devDocumento` | string |  | Possui serviço adicional de Devolução de Documentos — Valores: `S(Sim); N(Não)` |
| `objetos[].servico.dgr` | string |  | Possui serviço adicional de Transporte e artigos perigosos — Valores: `S(Sim); N(Não)` |
| `tipoResultado` | string |  | **Resultado** — Resultado da pesquisa — Valores: `Todos os Eventos, Primeiro Evento, Último Evento` — maxLength: `16` |

### `Servico`

Classe que representa o serviço utilizado pelo objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  | Código do serviço — maxLength: `20` |
| `ar` | string |  | Possui serviço adicional Aviso de Recebimento (AR) — Valores: `S(Sim); N(Não)` |
| `tipoAr` | string |  | Tipo de AR — Valores: `N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)` |
| `mp` | string |  | Possui serviço adicional Mão-Própria (MP) — Valores: `S(Sim); N(Não)` |
| `vd` | string |  | Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor — maxLength: `11` |
| `eVizinho` | string |  | Possui serviço adicional Entrega no Vizinho — Valores: `S(Sim);  N(Não)` |
| `fotoFachada` | string |  | Possui serviço adicional de Foto Fachada — Valores: `S(Sim); N(Não)` |
| `eControlada` | string |  | Possui serviço adicional de Entrega Controlada — Valores: `S(Sim); N(Não)` |
| `eInterativa` | string |  | Possui serviço adicional de Entrega Interativa — Valores: `S(Sim); N(Não)` |
| `eLocker` | string |  | Possui serviço adicional de Entrega Locker — Valores: `S(Sim); N(Não)` |
| `devDocumento` | string |  | Possui serviço adicional de Devolução de Documentos — Valores: `S(Sim); N(Não)` |
| `dgr` | string |  | Possui serviço adicional de Transporte e artigos perigosos — Valores: `S(Sim); N(Não)` |

### `Telefone`

Classe que representa um telefone

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string |  | Tipo do telefone — Valores: `Fixo, Celular (Cel)` — maxLength: `4` |
| `ddd` | string |  | Código DDD — maxLength: `2` |
| `numero` | string |  | Número do telefone — maxLength: `11` |

### `TipoPostal`

Classe que representa o tipo postal de um objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `sigla` | string |  | **Sigla** — Sigla do tipo postal — maxLength: `2` |
| `nome` | string |  | **Nome** — Descrição do tipo postal — maxLength: `40` |
| `categoria` | object |  | **Categoria** — Categoria do tipo postal |
| `familia` | string |  | **Familia** — Familia do tipo postal — maxLength: `30` |

### `Unidade`

Classe que representa uma unidade de tratamento do objeto

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | **Nome da unidade** — maxLength: `40` |
| `codSro` | string |  | **Código SRO da unidade** — maxLength: `8` |
| `codMcu` | string |  | **Código MCMCU da unidade** — maxLength: `12` |
| `tipo` | string |  | **Tipo da unidade** — maxLength: `2` |
| `endereco` | objeto `Endereco` |  | **Endereço** — Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf. |
| `endereco.cep` | string |  | **CEP do endereço** — Cep do endereço — maxLength: `8` |
| `endereco.logradouro` | string |  | **Logradouro do endereço** — Logradouro — maxLength: `80` |
| `endereco.complemento` | string |  | **Informação complementar do endereço** — maxLength: `60` |
| `endereco.numero` | string |  | **Número do endereço** — maxLength: `12` |
| `endereco.bairro` | string |  | **Bairro** — maxLength: `60` |
| `endereco.cidade` | string |  | **Cidade** — maxLength: `60` |
| `endereco.uf` | string |  | **Estado** — maxLength: `2` |
| `endereco.pais` | string |  |  |
| `endereco.telefone` | string |  | **Telefone** — maxLength: `13` |
| `endereco.erro` | array<string> |  |  |
| `codse` | string |  | **Código** — Código da Superintendência Estadual — maxLength: `2` |
| `se` | string |  | **Sigla** — Sigla da Superintendência Estadual — maxLength: `3` |

## 3. Códigos HTTP

| Código | Significado |
|---|---|
| `200` | Consulta realizada com sucesso. |
| `202` | Solicitação de imagens aceita para processamento assíncrono — retorna um `Recibo`. |
| `400` | Parâmetros inválidos (código fora do padrão, limite de objetos excedido etc.). Ver `MessageResponse.msgs`. |
| `401` | Token ausente, inválido ou expirado — gere/renove pela API Token. |
| `403` | Token sem permissão para esta API/contrato. |
| `500` | Erro interno dos Correios. |

## 4. JSON Schema completo (referências expandidas)

### `POST /v1/objetos/imagens`

Resposta 202:

```json
{
  "title": "Recibo",
  "type": "object",
  "description": "Representa os dados do recibo de uma lista de objetos",
  "properties": {
    "numero": {
      "type": "string",
      "description": "Número do recibo",
      "maxLength": 32,
      "title": "Número do Recibo"
    },
    "dtCriacao": {
      "type": "string",
      "format": "date-time",
      "description": "Data",
      "title": "Data de Geração do Recibo"
    },
    "dtValidade": {
      "type": "string",
      "format": "date-time",
      "description": "Data de Validade do recibo",
      "title": "Data de validade"
    },
    "qtdObjetos": {
      "type": "integer",
      "format": "int32",
      "description": "Quantidade de objetos para rastreamento",
      "maxLength": 5,
      "title": "Objetos"
    },
    "resultado": {
      "type": "string",
      "description": "Qual o evento para o resultado do rastreamento: Primeiro, Último ou Todos",
      "enum": [
        "P",
        "U",
        "T"
      ],
      "maxLength": 1,
      "title": "Resultado"
    },
    "idioma": {
      "type": "string",
      "description": "Idioma para solicitação do rastreamento",
      "enum": [
        "PT",
        "EN",
        "ES"
      ],
      "maxLength": 2,
      "title": "Idioma"
    }
  }
}
```

### `GET /v1/recibo/{recibo}`

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "Objeto Imagem",
    "type": "object",
    "description": "Classe que representa um objeto com suas respectivas imagens de baixa",
    "properties": {
      "objeto": {
        "type": "string",
        "description": "Código do objeto",
        "maxLength": 13,
        "title": "Código do Objeto"
      },
      "observacao": {
        "type": "string",
        "description": "Observação da imagem do objeto",
        "maxLength": 40,
        "title": "Observação"
      },
      "imagens": {
        "type": "array",
        "description": "Lista de imagens dos eventos do objeto",
        "items": {
          "title": "Imagem",
          "type": "object",
          "description": "Informações da imagem de baixa de um objeto",
          "properties": {
            "imagem": {
              "type": "string",
              "format": "byte",
              "description": "Imagem da baixa do objeto. String contendo um array de bytes no formato Base64 que representa a imagem",
              "title": "Imagem"
            },
            "comentario": {
              "type": "string",
              "description": "Informação complementar referente a imagem do evento de baixa",
              "maxLength": 20,
              "title": "Observações da Imagem"
            }
          }
        },
        "title": "Imagens"
      }
    }
  }
}
```

### `GET /v1/objetos`

Resposta 200:

```json
{
  "title": "SRO",
  "type": "object",
  "description": "Classe que representa o SRO",
  "properties": {
    "versao": {
      "type": "string",
      "description": "Versão da api em execução",
      "maxLength": 8,
      "title": "Versão"
    },
    "quantidade": {
      "type": "integer",
      "format": "int32",
      "description": "Quantidade de objetos rastreados",
      "maxLength": 5,
      "title": "Quantidade"
    },
    "objetos": {
      "type": "array",
      "description": "Lista de objetos com seu respectivo rastreamento",
      "items": {
        "title": "Objeto",
        "type": "object",
        "description": "Classe que representa um objeto do SRO",
        "properties": {
          "codObjeto": {
            "type": "string",
            "description": "Código do objeto",
            "maxLength": 13,
            "title": "Código do Objeto"
          },
          "tipoPostal": {
            "title": "Tipo Postal",
            "type": "object",
            "description": "Tipo Postal",
            "properties": {
              "sigla": {
                "type": "string",
                "description": "Sigla do tipo postal",
                "maxLength": 2,
                "title": "Sigla"
              },
              "nome": {
                "type": "string",
                "description": "Descrição do tipo postal",
                "maxLength": 40,
                "title": "Nome"
              },
              "categoria": {
                "description": "Categoria do tipo postal",
                "title": "Categoria"
              },
              "familia": {
                "type": "string",
                "description": "Familia do tipo postal",
                "maxLength": 30,
                "title": "Familia"
              }
            }
          },
          "mensagem": {
            "type": "string",
            "description": "Mensagem de erro",
            "maxLength": 500,
            "title": "Mensagem de erro"
          },
          "dtPrevista": {
            "type": "string",
            "format": "date-time",
            "description": "Data prevista para entrega do objeto",
            "title": "Data Prevista"
          },
          "multiVolume": {
            "type": "string",
            "description": "Quantidade de volumes para o objeto",
            "maxLength": 5,
            "title": "Multi Volume"
          },
          "afterschedule": {
            "type": "string",
            "description": "Objeto postado depois do horário",
            "enum": [
              "S",
              "N"
            ],
            "title": "Depois do Horário"
          },
          "volume": {
            "type": "integer",
            "format": "int32",
            "description": "Volume do objeto",
            "title": "Volume"
          },
          "valorRecebido": {
            "type": "string",
            "description": "Valor recebido",
            "maxLength": 11,
            "title": "Valor Recebido"
          },
          "entregaProgramada": {
            "type": "string",
            "format": "date-time",
            "description": "Data programada para entrega do objeto",
            "title": "Entrega Programada"
          },
          "prazoTratamento": {
            "type": "integer",
            "format": "int32",
            "description": "Prazo para tratamento do objeto",
            "title": "Prazo de Tratamento"
          },
          "contrato": {
            "type": "string",
            "description": "Número do contrato utilizado para postagem do objeto",
            "maxLength": 15,
            "title": "Contrato"
          },
          "codAdm": {
            "type": "string",
            "description": "Código Administrativo do contrato",
            "maxLength": 15,
            "title": "Código Administrativo"
          },
          "cartaoPostagem": {
            "type": "string",
            "description": "Número do cartão de postagem utilizado para postagem do objeto",
            "maxLength": 15,
            "title": "Cartão de Postagem"
          },
          "CNPJ": {
            "type": "string",
            "description": "Número do CNPJ",
            "maxLength": 25,
            "title": "CNPJ"
          },
          "largura": {
            "type": "number",
            "description": "Largura do objeto em cm",
            "maxLength": 4,
            "title": "Largura"
          },
          "comprimento": {
            "type": "number",
            "description": "Comprimento do objeto em cm",
            "maxLength": 4,
            "title": "Comprimento"
          },
          "altura": {
            "type": "number",
            "description": "Altura do objeto em cm",
            "maxLength": 4,
            "title": "Altura"
          },
          "diametro": {
            "type": "number",
            "description": "Diametro do objeto em cm",
            "maxLength": 4,
            "title": "Diâmetro"
          },
          "peso": {
            "type": "number",
            "description": "Peso do objeto em gramas",
            "maxLength": 10,
            "title": "Peso"
          },
          "formato": {
            "type": "string",
            "description": "Formato do objeto",
            "maxLength": 40,
            "title": "Formato"
          },
          "celRemetSMS": {
            "type": "string"
          },
          "celDestSMS": {
            "type": "string"
          },
          "valorDeclarado": {
            "type": "number",
            "description": "Valor declarado com duas casas de precisão",
            "maxLength": 11,
            "oneOf": [
              {
                "type": "number"
              }
            ],
            "title": "Valor Declarado"
          },
          "eventos": {
            "type": "array",
            "description": "Eventos do Objeto",
            "items": {
              "title": "Evento",
              "type": "object",
              "description": "Classe que representa os eventos de um objeto",
              "properties": {
                "codigo": {
                  "type": "string",
                  "description": "Código do evento de rastreamento",
                  "maxLength": 3,
                  "title": "Código"
                },
                "tipo": {
                  "type": "string",
                  "description": "Tipo do evento. Representa o status ou motivo do evento",
                  "maxLength": 2,
                  "title": "Motivo"
                },
                "dtHrCriado": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Data e hora da criação do evento",
                  "title": "Criação"
                },
                "descricao": {
                  "type": "string",
                  "description": "Descrição do evento",
                  "maxLength": 80,
                  "title": "Descrição"
                },
                "estacao": {
                  "type": "string",
                  "description": "Estação de trabalho responsável pelo registro do evento",
                  "maxLength": 3
                },
                "usuario": {
                  "type": "string",
                  "description": "Usuário responsável pelo registro do evento",
                  "maxLength": 8
                },
                "carteiro": {
                  "type": "string",
                  "description": "Matrícula do carteiro que realizou o evento",
                  "maxLength": 8
                },
                "entregador": {
                  "type": "string",
                  "description": "Identificação do entregador",
                  "maxLength": 13
                },
                "latitude": {
                  "type": "string",
                  "description": "Latitude composta por 25 números inteiros e 21 decimais",
                  "maxLength": 46,
                  "title": "Latitude"
                },
                "longitude": {
                  "type": "string",
                  "description": "Longitude composta por 25 números inteiros e 21 decimais",
                  "maxLength": 46,
                  "title": "Longitude"
                },
                "detalhe": {
                  "type": "string",
                  "description": "Descrição do detalhe do evento",
                  "maxLength": 80
                },
                "descCodigo": {
                  "type": "string",
                  "description": "Descrição da sigla do código do evento",
                  "maxLength": 80
                },
                "dtHrGravado": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Data e hora da gravação do evento"
                },
                "token": {
                  "type": "string",
                  "description": "Token de atendimento vinculado ao evento LDI",
                  "title": "Token de atendimento"
                },
                "recebedor": {
                  "title": "Recebedor",
                  "type": "object",
                  "description": "Classe que representa um recebedor",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 72,
                      "title": "Nome do recebedor"
                    },
                    "documento": {
                      "type": "string",
                      "maxLength": 25,
                      "title": "Documento de identificação do recebedor"
                    },
                    "celular": {
                      "type": "string",
                      "maxLength": 13,
                      "title": "Numero do celular do recebedor"
                    },
                    "email": {
                      "type": "string",
                      "maxLength": 60,
                      "title": "Email do recebedor"
                    },
                    "comentario": {
                      "type": "string",
                      "maxLength": 50,
                      "title": "Alguma observação referente ao recebedor"
                    }
                  }
                },
                "unidade": {
                  "title": "Unidade de Tratamento",
                  "type": "object",
                  "description": "Classe que representa uma unidade de tratamento do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 40,
                      "title": "Nome da unidade"
                    },
                    "codSro": {
                      "type": "string",
                      "maxLength": 8,
                      "title": "Código SRO da unidade"
                    },
                    "codMcu": {
                      "type": "string",
                      "maxLength": 12,
                      "title": "Código MCMCU da unidade"
                    },
                    "tipo": {
                      "type": "string",
                      "maxLength": 2,
                      "title": "Tipo da unidade"
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf.",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "codse": {
                      "type": "string",
                      "description": "Código da Superintendência Estadual",
                      "maxLength": 2,
                      "title": "Código"
                    },
                    "se": {
                      "type": "string",
                      "description": "Sigla da Superintendência Estadual",
                      "maxLength": 3,
                      "title": "Sigla"
                    }
                  }
                },
                "entregadorExterno": {
                  "title": "Entregador Externo",
                  "type": "object",
                  "description": "Dados do entregador externo do objeto.",
                  "properties": {
                    "documento": {
                      "type": "string",
                      "description": "Número do CPF do entregador externo",
                      "maxLength": 15,
                      "title": "CPF"
                    },
                    "nome": {
                      "type": "string",
                      "description": "Nome do entregador externo",
                      "maxLength": 80,
                      "title": "Nome"
                    }
                  }
                },
                "contrato": {
                  "type": "string",
                  "description": "Número do contrato utilizado para postagem do objeto",
                  "maxLength": 15,
                  "title": "Contrato"
                },
                "codAdm": {
                  "type": "string",
                  "description": "Código Administrativo do contrato",
                  "maxLength": 15,
                  "title": "Código Administrativo"
                },
                "cartaoPostagem": {
                  "type": "string",
                  "description": "Número do cartão de postagem utilizado para postagem do objeto",
                  "maxLength": 15,
                  "title": "Cartão de Postagem"
                },
                "CNPJ": {
                  "type": "string",
                  "description": "Número do CNPJ",
                  "maxLength": 25,
                  "title": "CNPJ"
                },
                "servico": {
                  "type": "string"
                },
                "remetente": {
                  "title": "Remetente",
                  "type": "object",
                  "description": "Classe que representa o remetente do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "description": "Nome do remetente do objeto",
                      "maxLength": 72
                    },
                    "documento": {
                      "type": "string",
                      "description": "Documento de identificação do remetente do objeto",
                      "maxLength": 25
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Endereço do remetente",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "telefones": {
                      "type": "array",
                      "description": "Telefones do remetente",
                      "items": {
                        "title": "Telefone",
                        "type": "object",
                        "description": "Classe que representa um telefone",
                        "properties": {
                          "tipo": {
                            "type": "string",
                            "description": "Tipo do telefone",
                            "enum": [
                              "Fixo, Celular (Cel)"
                            ],
                            "maxLength": 4
                          },
                          "ddd": {
                            "type": "string",
                            "description": "Código DDD",
                            "maxLength": 2
                          },
                          "numero": {
                            "type": "string",
                            "description": "Número do telefone",
                            "maxLength": 11
                          }
                        }
                      }
                    }
                  }
                },
                "destinatario": {
                  "title": "Destinatário",
                  "type": "object",
                  "description": "Classe que representa o destinatário do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "description": "Nome do destinatário",
                      "maxLength": 72
                    },
                    "documento": {
                      "type": "string",
                      "description": "Número do documento de identificação do destinatário",
                      "maxLength": 25
                    },
                    "email": {
                      "type": "string",
                      "description": "Email do destinatário",
                      "maxLength": 60
                    },
                    "telefones": {
                      "type": "array",
                      "description": "Telefone do destinatário",
                      "items": {
                        "title": "Telefone",
                        "type": "object",
                        "description": "Classe que representa um telefone",
                        "properties": {
                          "tipo": {
                            "type": "string",
                            "description": "Tipo do telefone",
                            "enum": [
                              "Fixo, Celular (Cel)"
                            ],
                            "maxLength": 4
                          },
                          "ddd": {
                            "type": "string",
                            "description": "Código DDD",
                            "maxLength": 2
                          },
                          "numero": {
                            "type": "string",
                            "description": "Número do telefone",
                            "maxLength": 11
                          }
                        }
                      }
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Endereço do destinatário",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    }
                  }
                },
                "dtLimiteRetirada": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Prazo de retirada de objeto lançado em LDI",
                  "title": "Prazo de Retirada"
                },
                "comentario": {
                  "type": "string",
                  "description": "Comentário informado pelo usuário referente ao evento",
                  "maxLength": 100,
                  "title": "Comentário"
                },
                "codLista": {
                  "type": "string",
                  "description": "Recipiente",
                  "maxLength": 13
                },
                "unidadeDestino": {
                  "title": "Destino",
                  "type": "object",
                  "description": "Classe que representa o destino de um objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 40,
                      "title": "Nome da unidade"
                    },
                    "codSro": {
                      "type": "string",
                      "maxLength": 8,
                      "title": "Código SRO da unidade"
                    },
                    "codMcu": {
                      "type": "string",
                      "maxLength": 12,
                      "title": "Código MCMCU da unidade"
                    },
                    "tipo": {
                      "type": "string",
                      "maxLength": 2,
                      "title": "Tipo da unidade"
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf.",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "codse": {
                      "type": "string",
                      "description": "Código da Superintendência Estadual",
                      "maxLength": 2,
                      "title": "Código"
                    },
                    "se": {
                      "type": "string",
                      "description": "Sigla da Superintendência Estadual",
                      "maxLength": 3,
                      "title": "Sigla"
                    },
                    "recipiente": {
                      "type": "string",
                      "description": "Recipiente",
                      "maxLength": 13
                    }
                  }
                }
              }
            },
            "title": "Eventos"
          },
          "servico": {
            "title": "Serviço",
            "type": "object",
            "description": "Classe que representa o serviço utilizado pelo objeto",
            "properties": {
              "codigo": {
                "type": "string",
                "description": "Código do serviço",
                "maxLength": 20
              },
              "ar": {
                "type": "string",
                "description": "Possui serviço adicional Aviso de Recebimento (AR)",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "tipoAr": {
                "type": "string",
                "description": "Tipo de AR",
                "enum": [
                  "N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)"
                ]
              },
              "mp": {
                "type": "string",
                "description": "Possui serviço adicional Mão-Própria (MP)",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "vd": {
                "type": "string",
                "description": "Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor",
                "maxLength": 11
              },
              "eVizinho": {
                "type": "string",
                "description": "Possui serviço adicional Entrega no Vizinho",
                "enum": [
                  "S(Sim);  N(Não)"
                ]
              },
              "fotoFachada": {
                "type": "string",
                "description": "Possui serviço adicional de Foto Fachada",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eControlada": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Controlada",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eInterativa": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Interativa",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eLocker": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Locker",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "devDocumento": {
                "type": "string",
                "description": "Possui serviço adicional de Devolução de Documentos",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "dgr": {
                "type": "string",
                "description": "Possui serviço adicional de Transporte e artigos perigosos",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              }
            }
          }
        }
      },
      "title": "Objetos"
    },
    "tipoResultado": {
      "type": "string",
      "description": "Resultado da pesquisa",
      "enum": [
        "Todos os Eventos, Primeiro Evento, Último Evento"
      ],
      "maxLength": 16,
      "title": "Resultado"
    }
  }
}
```

### `GET /v1/objetos/{codigoObjeto}`

Resposta 200:

```json
{
  "title": "SRO",
  "type": "object",
  "description": "Classe que representa o SRO",
  "properties": {
    "versao": {
      "type": "string",
      "description": "Versão da api em execução",
      "maxLength": 8,
      "title": "Versão"
    },
    "quantidade": {
      "type": "integer",
      "format": "int32",
      "description": "Quantidade de objetos rastreados",
      "maxLength": 5,
      "title": "Quantidade"
    },
    "objetos": {
      "type": "array",
      "description": "Lista de objetos com seu respectivo rastreamento",
      "items": {
        "title": "Objeto",
        "type": "object",
        "description": "Classe que representa um objeto do SRO",
        "properties": {
          "codObjeto": {
            "type": "string",
            "description": "Código do objeto",
            "maxLength": 13,
            "title": "Código do Objeto"
          },
          "tipoPostal": {
            "title": "Tipo Postal",
            "type": "object",
            "description": "Tipo Postal",
            "properties": {
              "sigla": {
                "type": "string",
                "description": "Sigla do tipo postal",
                "maxLength": 2,
                "title": "Sigla"
              },
              "nome": {
                "type": "string",
                "description": "Descrição do tipo postal",
                "maxLength": 40,
                "title": "Nome"
              },
              "categoria": {
                "description": "Categoria do tipo postal",
                "title": "Categoria"
              },
              "familia": {
                "type": "string",
                "description": "Familia do tipo postal",
                "maxLength": 30,
                "title": "Familia"
              }
            }
          },
          "mensagem": {
            "type": "string",
            "description": "Mensagem de erro",
            "maxLength": 500,
            "title": "Mensagem de erro"
          },
          "dtPrevista": {
            "type": "string",
            "format": "date-time",
            "description": "Data prevista para entrega do objeto",
            "title": "Data Prevista"
          },
          "multiVolume": {
            "type": "string",
            "description": "Quantidade de volumes para o objeto",
            "maxLength": 5,
            "title": "Multi Volume"
          },
          "afterschedule": {
            "type": "string",
            "description": "Objeto postado depois do horário",
            "enum": [
              "S",
              "N"
            ],
            "title": "Depois do Horário"
          },
          "volume": {
            "type": "integer",
            "format": "int32",
            "description": "Volume do objeto",
            "title": "Volume"
          },
          "valorRecebido": {
            "type": "string",
            "description": "Valor recebido",
            "maxLength": 11,
            "title": "Valor Recebido"
          },
          "entregaProgramada": {
            "type": "string",
            "format": "date-time",
            "description": "Data programada para entrega do objeto",
            "title": "Entrega Programada"
          },
          "prazoTratamento": {
            "type": "integer",
            "format": "int32",
            "description": "Prazo para tratamento do objeto",
            "title": "Prazo de Tratamento"
          },
          "contrato": {
            "type": "string",
            "description": "Número do contrato utilizado para postagem do objeto",
            "maxLength": 15,
            "title": "Contrato"
          },
          "codAdm": {
            "type": "string",
            "description": "Código Administrativo do contrato",
            "maxLength": 15,
            "title": "Código Administrativo"
          },
          "cartaoPostagem": {
            "type": "string",
            "description": "Número do cartão de postagem utilizado para postagem do objeto",
            "maxLength": 15,
            "title": "Cartão de Postagem"
          },
          "CNPJ": {
            "type": "string",
            "description": "Número do CNPJ",
            "maxLength": 25,
            "title": "CNPJ"
          },
          "largura": {
            "type": "number",
            "description": "Largura do objeto em cm",
            "maxLength": 4,
            "title": "Largura"
          },
          "comprimento": {
            "type": "number",
            "description": "Comprimento do objeto em cm",
            "maxLength": 4,
            "title": "Comprimento"
          },
          "altura": {
            "type": "number",
            "description": "Altura do objeto em cm",
            "maxLength": 4,
            "title": "Altura"
          },
          "diametro": {
            "type": "number",
            "description": "Diametro do objeto em cm",
            "maxLength": 4,
            "title": "Diâmetro"
          },
          "peso": {
            "type": "number",
            "description": "Peso do objeto em gramas",
            "maxLength": 10,
            "title": "Peso"
          },
          "formato": {
            "type": "string",
            "description": "Formato do objeto",
            "maxLength": 40,
            "title": "Formato"
          },
          "celRemetSMS": {
            "type": "string"
          },
          "celDestSMS": {
            "type": "string"
          },
          "valorDeclarado": {
            "type": "number",
            "description": "Valor declarado com duas casas de precisão",
            "maxLength": 11,
            "oneOf": [
              {
                "type": "number"
              }
            ],
            "title": "Valor Declarado"
          },
          "eventos": {
            "type": "array",
            "description": "Eventos do Objeto",
            "items": {
              "title": "Evento",
              "type": "object",
              "description": "Classe que representa os eventos de um objeto",
              "properties": {
                "codigo": {
                  "type": "string",
                  "description": "Código do evento de rastreamento",
                  "maxLength": 3,
                  "title": "Código"
                },
                "tipo": {
                  "type": "string",
                  "description": "Tipo do evento. Representa o status ou motivo do evento",
                  "maxLength": 2,
                  "title": "Motivo"
                },
                "dtHrCriado": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Data e hora da criação do evento",
                  "title": "Criação"
                },
                "descricao": {
                  "type": "string",
                  "description": "Descrição do evento",
                  "maxLength": 80,
                  "title": "Descrição"
                },
                "estacao": {
                  "type": "string",
                  "description": "Estação de trabalho responsável pelo registro do evento",
                  "maxLength": 3
                },
                "usuario": {
                  "type": "string",
                  "description": "Usuário responsável pelo registro do evento",
                  "maxLength": 8
                },
                "carteiro": {
                  "type": "string",
                  "description": "Matrícula do carteiro que realizou o evento",
                  "maxLength": 8
                },
                "entregador": {
                  "type": "string",
                  "description": "Identificação do entregador",
                  "maxLength": 13
                },
                "latitude": {
                  "type": "string",
                  "description": "Latitude composta por 25 números inteiros e 21 decimais",
                  "maxLength": 46,
                  "title": "Latitude"
                },
                "longitude": {
                  "type": "string",
                  "description": "Longitude composta por 25 números inteiros e 21 decimais",
                  "maxLength": 46,
                  "title": "Longitude"
                },
                "detalhe": {
                  "type": "string",
                  "description": "Descrição do detalhe do evento",
                  "maxLength": 80
                },
                "descCodigo": {
                  "type": "string",
                  "description": "Descrição da sigla do código do evento",
                  "maxLength": 80
                },
                "dtHrGravado": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Data e hora da gravação do evento"
                },
                "token": {
                  "type": "string",
                  "description": "Token de atendimento vinculado ao evento LDI",
                  "title": "Token de atendimento"
                },
                "recebedor": {
                  "title": "Recebedor",
                  "type": "object",
                  "description": "Classe que representa um recebedor",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 72,
                      "title": "Nome do recebedor"
                    },
                    "documento": {
                      "type": "string",
                      "maxLength": 25,
                      "title": "Documento de identificação do recebedor"
                    },
                    "celular": {
                      "type": "string",
                      "maxLength": 13,
                      "title": "Numero do celular do recebedor"
                    },
                    "email": {
                      "type": "string",
                      "maxLength": 60,
                      "title": "Email do recebedor"
                    },
                    "comentario": {
                      "type": "string",
                      "maxLength": 50,
                      "title": "Alguma observação referente ao recebedor"
                    }
                  }
                },
                "unidade": {
                  "title": "Unidade de Tratamento",
                  "type": "object",
                  "description": "Classe que representa uma unidade de tratamento do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 40,
                      "title": "Nome da unidade"
                    },
                    "codSro": {
                      "type": "string",
                      "maxLength": 8,
                      "title": "Código SRO da unidade"
                    },
                    "codMcu": {
                      "type": "string",
                      "maxLength": 12,
                      "title": "Código MCMCU da unidade"
                    },
                    "tipo": {
                      "type": "string",
                      "maxLength": 2,
                      "title": "Tipo da unidade"
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf.",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "codse": {
                      "type": "string",
                      "description": "Código da Superintendência Estadual",
                      "maxLength": 2,
                      "title": "Código"
                    },
                    "se": {
                      "type": "string",
                      "description": "Sigla da Superintendência Estadual",
                      "maxLength": 3,
                      "title": "Sigla"
                    }
                  }
                },
                "entregadorExterno": {
                  "title": "Entregador Externo",
                  "type": "object",
                  "description": "Dados do entregador externo do objeto.",
                  "properties": {
                    "documento": {
                      "type": "string",
                      "description": "Número do CPF do entregador externo",
                      "maxLength": 15,
                      "title": "CPF"
                    },
                    "nome": {
                      "type": "string",
                      "description": "Nome do entregador externo",
                      "maxLength": 80,
                      "title": "Nome"
                    }
                  }
                },
                "contrato": {
                  "type": "string",
                  "description": "Número do contrato utilizado para postagem do objeto",
                  "maxLength": 15,
                  "title": "Contrato"
                },
                "codAdm": {
                  "type": "string",
                  "description": "Código Administrativo do contrato",
                  "maxLength": 15,
                  "title": "Código Administrativo"
                },
                "cartaoPostagem": {
                  "type": "string",
                  "description": "Número do cartão de postagem utilizado para postagem do objeto",
                  "maxLength": 15,
                  "title": "Cartão de Postagem"
                },
                "CNPJ": {
                  "type": "string",
                  "description": "Número do CNPJ",
                  "maxLength": 25,
                  "title": "CNPJ"
                },
                "servico": {
                  "type": "string"
                },
                "remetente": {
                  "title": "Remetente",
                  "type": "object",
                  "description": "Classe que representa o remetente do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "description": "Nome do remetente do objeto",
                      "maxLength": 72
                    },
                    "documento": {
                      "type": "string",
                      "description": "Documento de identificação do remetente do objeto",
                      "maxLength": 25
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Endereço do remetente",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "telefones": {
                      "type": "array",
                      "description": "Telefones do remetente",
                      "items": {
                        "title": "Telefone",
                        "type": "object",
                        "description": "Classe que representa um telefone",
                        "properties": {
                          "tipo": {
                            "type": "string",
                            "description": "Tipo do telefone",
                            "enum": [
                              "Fixo, Celular (Cel)"
                            ],
                            "maxLength": 4
                          },
                          "ddd": {
                            "type": "string",
                            "description": "Código DDD",
                            "maxLength": 2
                          },
                          "numero": {
                            "type": "string",
                            "description": "Número do telefone",
                            "maxLength": 11
                          }
                        }
                      }
                    }
                  }
                },
                "destinatario": {
                  "title": "Destinatário",
                  "type": "object",
                  "description": "Classe que representa o destinatário do objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "description": "Nome do destinatário",
                      "maxLength": 72
                    },
                    "documento": {
                      "type": "string",
                      "description": "Número do documento de identificação do destinatário",
                      "maxLength": 25
                    },
                    "email": {
                      "type": "string",
                      "description": "Email do destinatário",
                      "maxLength": 60
                    },
                    "telefones": {
                      "type": "array",
                      "description": "Telefone do destinatário",
                      "items": {
                        "title": "Telefone",
                        "type": "object",
                        "description": "Classe que representa um telefone",
                        "properties": {
                          "tipo": {
                            "type": "string",
                            "description": "Tipo do telefone",
                            "enum": [
                              "Fixo, Celular (Cel)"
                            ],
                            "maxLength": 4
                          },
                          "ddd": {
                            "type": "string",
                            "description": "Código DDD",
                            "maxLength": 2
                          },
                          "numero": {
                            "type": "string",
                            "description": "Número do telefone",
                            "maxLength": 11
                          }
                        }
                      }
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Endereço do destinatário",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    }
                  }
                },
                "dtLimiteRetirada": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Prazo de retirada de objeto lançado em LDI",
                  "title": "Prazo de Retirada"
                },
                "comentario": {
                  "type": "string",
                  "description": "Comentário informado pelo usuário referente ao evento",
                  "maxLength": 100,
                  "title": "Comentário"
                },
                "codLista": {
                  "type": "string",
                  "description": "Recipiente",
                  "maxLength": 13
                },
                "unidadeDestino": {
                  "title": "Destino",
                  "type": "object",
                  "description": "Classe que representa o destino de um objeto",
                  "properties": {
                    "nome": {
                      "type": "string",
                      "maxLength": 40,
                      "title": "Nome da unidade"
                    },
                    "codSro": {
                      "type": "string",
                      "maxLength": 8,
                      "title": "Código SRO da unidade"
                    },
                    "codMcu": {
                      "type": "string",
                      "maxLength": 12,
                      "title": "Código MCMCU da unidade"
                    },
                    "tipo": {
                      "type": "string",
                      "maxLength": 2,
                      "title": "Tipo da unidade"
                    },
                    "endereco": {
                      "title": "Endereço",
                      "type": "object",
                      "description": "Classe que representa um endereço. Para os eventos do tipo Lançamento, o endereço para as unidades será exibido completamente. Nos demais casos, será exibido apenas cidade e uf.",
                      "properties": {
                        "cep": {
                          "type": "string",
                          "description": "Cep do endereço",
                          "maxLength": 8,
                          "title": "CEP do endereço"
                        },
                        "logradouro": {
                          "type": "string",
                          "description": "Logradouro",
                          "maxLength": 80,
                          "title": "Logradouro do endereço"
                        },
                        "complemento": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Informação complementar do endereço"
                        },
                        "numero": {
                          "type": "string",
                          "maxLength": 12,
                          "title": "Número do endereço"
                        },
                        "bairro": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Bairro"
                        },
                        "cidade": {
                          "type": "string",
                          "maxLength": 60,
                          "title": "Cidade"
                        },
                        "uf": {
                          "type": "string",
                          "maxLength": 2,
                          "title": "Estado"
                        },
                        "pais": {
                          "type": "string"
                        },
                        "telefone": {
                          "type": "string",
                          "maxLength": 13,
                          "title": "Telefone"
                        },
                        "erro": {
                          "type": "array",
                          "items": {
                            "type": "string"
                          }
                        }
                      }
                    },
                    "codse": {
                      "type": "string",
                      "description": "Código da Superintendência Estadual",
                      "maxLength": 2,
                      "title": "Código"
                    },
                    "se": {
                      "type": "string",
                      "description": "Sigla da Superintendência Estadual",
                      "maxLength": 3,
                      "title": "Sigla"
                    },
                    "recipiente": {
                      "type": "string",
                      "description": "Recipiente",
                      "maxLength": 13
                    }
                  }
                }
              }
            },
            "title": "Eventos"
          },
          "servico": {
            "title": "Serviço",
            "type": "object",
            "description": "Classe que representa o serviço utilizado pelo objeto",
            "properties": {
              "codigo": {
                "type": "string",
                "description": "Código do serviço",
                "maxLength": 20
              },
              "ar": {
                "type": "string",
                "description": "Possui serviço adicional Aviso de Recebimento (AR)",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "tipoAr": {
                "type": "string",
                "description": "Tipo de AR",
                "enum": [
                  "N( - ), C(Convencional), D(Digital), E(Eletrônico), V(Virtual)"
                ]
              },
              "mp": {
                "type": "string",
                "description": "Possui serviço adicional Mão-Própria (MP)",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "vd": {
                "type": "string",
                "description": "Valor Declarado: se possuir o serviço adicional Valor Declarado (VD), será informado o valor",
                "maxLength": 11
              },
              "eVizinho": {
                "type": "string",
                "description": "Possui serviço adicional Entrega no Vizinho",
                "enum": [
                  "S(Sim);  N(Não)"
                ]
              },
              "fotoFachada": {
                "type": "string",
                "description": "Possui serviço adicional de Foto Fachada",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eControlada": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Controlada",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eInterativa": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Interativa",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "eLocker": {
                "type": "string",
                "description": "Possui serviço adicional de Entrega Locker",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "devDocumento": {
                "type": "string",
                "description": "Possui serviço adicional de Devolução de Documentos",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              },
              "dgr": {
                "type": "string",
                "description": "Possui serviço adicional de Transporte e artigos perigosos",
                "enum": [
                  "S(Sim); N(Não)"
                ]
              }
            }
          }
        }
      },
      "title": "Objetos"
    },
    "tipoResultado": {
      "type": "string",
      "description": "Resultado da pesquisa",
      "enum": [
        "Todos os Eventos, Primeiro Evento, Último Evento"
      ],
      "maxLength": 16,
      "title": "Resultado"
    }
  }
}
```

### `GET /v1/ar-digital`

Resposta 200:

```json
{
  "type": "array",
  "items": {
    "title": "AR Digital",
    "type": "object",
    "description": "Informações do AR Digital",
    "properties": {
      "objeto": {
        "type": "string"
      },
      "dataBaixa": {
        "type": "string",
        "format": "date-time"
      },
      "contentType": {
        "type": "string"
      },
      "imagemBase64": {
        "type": "string"
      },
      "formato": {
        "type": "string"
      },
      "resultadoProcessamento": {
        "title": "Resultado do Processamento",
        "type": "object",
        "description": "Classe que representa o resultado do processamento de uma operação",
        "properties": {
          "sucesso": {
            "type": "boolean",
            "description": "Indica se o processamento foi realizado com sucesso",
            "title": "Sucesso"
          },
          "codigo": {
            "type": "string",
            "description": "Código do erro, quando houver"
          },
          "resultado": {
            "type": "string",
            "description": "Mensagem descritiva do resultado do processamento",
            "title": "Resultado do Processamento"
          }
        }
      }
    }
  }
}
```

## 5. Orientações de implementação

1. **Token:** obtenha o Bearer pela API Token e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita a chamada.
2. **Rastrear um objeto:** `GET /v1/objetos/{codigoObjeto}?resultado=T` retorna o histórico completo. Para exibir só o status atual, use `resultado=U` (último evento) — resposta menor e mais rápida.
3. **Rastrear vários objetos:** use `GET /v1/objetos` com até 50 códigos por chamada. Para listas maiores, divida em blocos de 50.
4. **Validação do código:** antes de chamar a API, valide o formato (13 caracteres: 2 letras + 9 dígitos + 2 letras, ex.: `AA123456789BR`) e converta para maiúsculas.
5. **Status do objeto:** cada item de `eventos` traz `codigo` (até 3 caracteres) + `tipo` (até 2 caracteres) + `descricao`. A combinação `codigo`+`tipo` identifica o evento; use `descricao` para exibir ao usuário e a combinação para regras (ex.: marcar como entregue). A tabela de códigos de evento não faz parte desta especificação — mapeie os códigos que sua aplicação precisa tratar a partir dos retornos reais.
6. **Ordem dos eventos:** não assuma a ordem da lista; ordene por `dtHrCriado` quando precisar do mais recente.
7. **Imagens de baixa (assíncrono):** `POST /v1/objetos/imagens` com a lista de códigos → resposta `202` com um `Recibo` (`numero`, `dtValidade`). Depois consulte `GET /v1/recibo/{numero}` até obter o resultado, respeitando a `dtValidade` do recibo e com intervalo entre as tentativas.
8. **AR Digital:** `GET /v1/ar-digital?objetos=A,B` (máx. 10) retorna a imagem do AR quando existir — só se aplica a objetos postados com o serviço de AR Digital.
9. **Frequência de consulta:** rastreamento não muda a cada minuto — agende atualizações periódicas (ex.: a cada algumas horas) e pare de consultar objetos já entregues ou devolvidos.
10. **Ambientes:** mantenha a URL base configurável (homologação × produção). Em homologação, os objetos de teste podem não ter eventos reais.
