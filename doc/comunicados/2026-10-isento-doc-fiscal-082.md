# Novo serviço adicional "Isento Doc Fiscal" (código 082) para postagens dispensadas da DC-e

> Comunicado dos Correios aos clientes, de 27/10/2026, registrado no repositório em 02/11/2026. O texto abaixo
> reproduz o e-mail recebido. O que muda no componente e o que a aplicação deve fazer estão no fim deste arquivo e no
> README ([DC-e e remetente contribuinte do ICMS](../../README.md#dc-e-e-remetente-contribuinte-do-icms)).
> Complementa o comunicado da Rejeição 812 ([2026-10-dce-contribuinte-icms.md](2026-10-dce-contribuinte-icms.md)).
>
> **De:** Correios Comunicados Clientes7 SPM – Caixa Postal

---

Brasília, 27 de outubro de 2026

Prezado (a) cliente,

Sua empresa envia documentos ou materiais dispensados da Declaração de Conteúdo Eletrônica (DC-e)? A partir de
**02/11/2026**, você pode identificar essas postagens nos sistemas dos Correios com o novo serviço adicional **Isento
Doc Fiscal (código 082)**.

Com essa opção, o sistema permite fazer a pré-postagem sem exigir a chave de acesso da Nota Fiscal Eletrônica (NF-e)
ou da DC-e, quando a legislação dispensar essa documentação.

## Quando usar

O Ajuste SINIEF nº 35/2026 prevê situações em que a DC-e não é obrigatória, desde que atendidas as condições legais.
Entre elas:

- **Documentos em papel:** contratos, certidões, documentos pessoais, processos e correspondências.
- **Bens e materiais com documentação específica:** acompanhados de documento exigido por lei ou emitido por órgão
  ou entidade da Administração Pública, que identifique quem envia, quem recebe e o conteúdo.
- **Remessas internacionais:** acompanhadas da documentação aduaneira exigida.
- **Amostras e materiais para exames ou análises:** incluindo materiais biológicos, acompanhados de documento que
  identifique origem, destino e material.

## Como usar

O procedimento depende de como sua empresa realiza a pré-postagem.

**Pela Plataforma de Pré-Postagem Nacional (PPN Web):** a opção Isento Doc Fiscal será disponibilizada na seção
Serviços Adicionais para identificar as postagens dispensadas da DC-e.

- **Código:** 082
- **Tipo:** ISENTO_DOC_FISCAL
- **Nome:** Isento Doc Fiscal

**Por integração com a PPN:** utilize a estrutura abaixo:

```json
"listaServicoAdicional": [
  {
    "codigoServicoAdicional": "082",
    "tipoServicoAdicional": "ISENTO_DOC_FISCAL",
    "nomeServicoAdicional": "Isento Doc Fiscal"
  }
]
```

## O que você precisa saber

O código 082 não substitui a NF-e nem outros documentos fiscais obrigatórios. Antes de utilizá-lo, sua empresa deve
confirmar se a postagem atende às condições legais de dispensa e garantir que as informações prestadas estejam
corretas.

Essa verificação é responsabilidade do remetente. A aceitação do código pelo sistema não representa uma validação
fiscal pelos Correios.

Se tiver dúvidas sobre a documentação necessária, consulte a área contábil ou tributária da sua empresa.

**Ficou com alguma dúvida sobre o uso do código 082?** Seu representante comercial dos Correios pode ajudar.

*Correios*

---

## Impacto no componente

| Situação | O que acontece |
|---|---|
| `ServicoAdicional::isentoDocFiscal()` em `adicionais` | Envia `082` com `tipoServicoAdicional` e `nomeServicoAdicional`, como no comunicado |
| 082 com `emiteDCe: true` | `ErroValidacao` (campo `emiteDCe`): a remessa é dispensada da DC-e |
| 082 sem itens da declaração de conteúdo | `ErroValidacao` (campo `itens`) |
| Adicional da API Preço com sigla `IDFI` | `CategoriaServicoAdicional::IsentoDocFiscal`; `ServicoAdicional::de()` monta o mesmo que o atalho |
| `NovaPrePostagem::isentoDocFiscal()` | Diz se a pré-postagem leva o 082 |

## Observado em homologação (02/11/2026)

- `preco()->servicosAdicionais()` devolve `082` com sigla `IDFI` e nome `ISENTO DOC FISCAL` no SEDEX (03220) e no PAC
  (03298).
- A especificação da API Pré-postagem continua na versão 2.0.32 e não menciona o 082.
- **082 + itens, sem `emiteDCe`:** aceito (status 2). A resposta traz o adicional com a sigla `IDFI`. Enviar só o
  código ou a estrutura completa dá o mesmo resultado; o componente envia a estrutura completa, como o comunicado pede.
- **082 sem itens:** recusado com `PPN-347: É obrigatório informar Declaração de Conteúdo.`, mesmo com chave de NF-e.
  Ou seja, o 082 dispensa a chave, não os itens da declaração.
- **Só a chave da NF-e, sem itens e sem 082:** também recusado com `PPN-347`. Até a versão 0.5.0 o README orientava
  enviar só a chave; agora orienta enviar também os itens. Não se sabe se a produção já se comporta assim.
- **082 + `emiteDCe`:** a API aceitou, mas o pedido é contraditório (remessa dispensada da DC-e pedindo a DC-e). O
  componente recusa antes de chamar a API.
- **Só itens, sem 082 e sem `emiteDCe`:** ainda aceito em homologação (status 7). O comunicado indica que, para
  remessas dispensadas, o caminho é o 082.
