# Correios Log+ – Regime de Operador Logístico

> Comunicado dos Correios aos clientes, registrado no repositório em 16/10/2026. O texto abaixo reproduz o e-mail
> recebido. O que muda no componente e o que a aplicação deve fazer estão no fim deste arquivo e no README
> ([DC-e e remetente contribuinte do ICMS](../../README.md#dc-e-e-remetente-contribuinte-do-icms)).
>
> **De:** Correios Comunicados Clientes1 SPM – Caixa Postal

---

Prezado Cliente,

A partir de **2 de novembro de 2026**, durante a emissão da **Declaração de Conteúdo Eletrônica (DC-e)**, o CNPJ do
remetente será verificado quanto ao cadastro como contribuinte do ICMS nas Secretarias de Fazenda (SEFAZ).

Caso o remetente esteja inscrito como contribuinte, a DC-e não será emitida e o sistema apresentará a mensagem:

> **Rejeição 812 — Usuário emitente bloqueado para emissão de DC-e [Contribuinte de ICMS]**

Essa validação decorre de exigência das Secretarias de Fazenda, aplica-se a qualquer transportador ou operador
logístico e não é uma regra específica dos Correios.

## Sobre a DC-e

A DC-e foi criada para acompanhar o transporte de bens e mercadorias quando a legislação não exige outro documento
fiscal. Por isso, é utilizada principalmente por pessoas físicas e por empresas que não são contribuintes do ICMS.

As empresas contribuintes do ICMS, em geral aquelas com inscrição estadual ativa, devem, como regra, emitir a **Nota
Fiscal Eletrônica (NF-e)** para o envio de bens e mercadorias. A nova validação passa a verificar automaticamente uma
condição já prevista na legislação. Para o envio de documentos em papel, como contratos e correspondências, a
legislação vigente não exige NF-e, mesmo quando o remetente é contribuinte do ICMS.

## Situações de dispensa da DC-e

Além disso, o **Ajuste SINIEF nº 35/2026** prevê situações em que o não contribuinte não precisa emitir a DC-e. Entre
elas, destacam-se:

- Envio de documentos em papel, como contratos e correspondências;
- Remessas internacionais acompanhadas da documentação aduaneira própria, como a Declaração Simplificada de Importação
  (DSI) e a Declaração de Importação de Remessa (DIR);
- Bens acompanhados de documento emitido por órgão público que identifique o remetente, o destinatário e o objeto
  transportado;
- Amostras ou materiais biológicos destinados a exame, análise ou diagnóstico, acompanhados de documento que
  identifique a origem, o destino e o material.

## Recomendações

Para evitar impedimentos nas postagens a partir de 2 de novembro de 2026, recomendamos:

- Verificar se o CNPJ utilizado na emissão da DC-e está cadastrado como contribuinte do ICMS;
- Caso seja contribuinte, adotar o documento fiscal adequado ao envio, em regra a NF-e;
- Quando se tratar de não contribuinte, verificar se a remessa se enquadra em alguma das situações de dispensa da DC-e
  e se está acompanhada dos documentos exigidos;
- Em caso de dúvida sobre o documento correto, consultar a área contábil ou tributária da sua empresa.

---

Os Correios estão promovendo as adequações necessárias em seus sistemas para assegurar o correto tratamento das
situações legais de dispensa da DC-e. Para orientações sobre os procedimentos de postagem e apoio à adequação
operacional, entre em contato com seu representante comercial ou com sua unidade de atendimento dos Correios.

Agradecemos sua atenção e colaboração.

Atenciosamente,

**CORREIOS**

---

## Impacto no componente

| Situação | O que acontece |
|---|---|
| Remetente marcado com `contribuinteIcms: true` e pré-postagem com `emiteDCe: true` | `ErroValidacao` (campo `emiteDCe`) antes de chamar a API |
| API responde com a Rejeição 812 (criação síncrona, impressão da DACE ou qualquer outra chamada) | `ErroContribuinteIcms` (subclasse de `ErroApi`) |
| Rejeição 812 na consulta da pré-postagem assíncrona | `ProcessamentoAssincrono::recusadaPorContribuinteIcms()` devolve `true` |
| Pré-postagem cancelada pela falha da DC-e (`motivoCancelamento` com a Rejeição 812) | `PrePostagemCriada::recusadaPorContribuinteIcms()` devolve `true` |

Pontos ainda não confirmados com os Correios:

- **Formato da mensagem na API:** o comunicado traz só o texto da rejeição. O componente reconhece "Rejeição 812",
  "bloqueado para emissão de DC-e" e "[Contribuinte de ICMS]" em qualquer posição da mensagem, com ou sem prefixo
  `PPN-xxx:`. Registre aqui o corpo real da primeira resposta observada.
- **Dispensas do Ajuste SINIEF nº 35/2026:** os Correios ainda estão adequando os sistemas. A API Pré-postagem
  (versão 2.0.32) não tem campo para informar dispensa da DC-e; o componente não trata as dispensas de forma especial.
