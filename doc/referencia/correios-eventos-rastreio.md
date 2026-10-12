# Correios — catálogo de eventos de rastreamento

> Documento de referência. Fonte: lista de tipos de evento do serviço de webhook de rastreamento dos Correios
> (`"id": "wh-rastro"`, `"nuApi": 534`), obtida pela aplicação MARCELO ENVIOS e guardada sem alteração em
> [`correios-eventos-rastreio.json`](correios-eventos-rastreio.json). **376 eventos.** É a mesma lista que a API
> Webhook devolve em `tipoEventos` (arquivo 11). A especificação da API Rastro (arquivo 10) não traz essa tabela.

## Como ler

- **Evento** = `codigo-tipo`. A API Rastro devolve os dois campos separados e o tipo com zeros à esquerda
  (`"codigo": "BDE", "tipo": "01"`); o webhook usa `BDE-1` no cabeçalho `x-correios-hook-event`. O componente
  normaliza as duas formas (`CatalogoEventos::chave()`).
- **O tipo muda o significado do código.** `BDE-1` é entrega; `BDE-20` é "carteiro não atendido"; `BDE-14` é
  "entregue ao remetente". Nunca classifique só pelo código.
- **`BDE`, `BDI` e `BDR`** têm os mesmos tipos e descrições (baixa de entrega em fluxos diferentes).
- **`EST`** ("Favor desconsiderar a informação anterior") anula o evento anterior: a situação do objeto vem do evento
  de antes dele (`Rastreamento::ultimoEventoValido()`).
- **Sem descrição no catálogo:** `FC-66`, `FC-68`, `FC-83`, `FC-84`, `FC-85`, `FC-93`, `RO-2` e `PAR-6` a `PAR-9`
  vêm com a descrição igual ao próprio código. Ficam sem situação (`Desconhecida`). Exceção: **`FC-82`**, que
  apareceu em homologação (12/10/2026) como "Etiqueta emitida" no objeto recém-pré-postado.
- Observado em homologação (12/10/2026): código fora do contrato volta sem eventos e com a mensagem "Objeto não
  pertence ao contrato".

## Critérios das situações

A situação é uma classificação do componente, não dos Correios. A aplicação pode trocar qualquer uma com o mapa de
`situacao($mapa)`.

| Situação | Critério |
|---|---|
| `AguardandoPostagem` | Etiqueta emitida (`FC-82`) ou coleta ainda não feita (`CO-2`, `CO-3`, `CO-14` e-ticket) |
| `Postado` | Postagem (`PO`) ou coleta realizada (`CO-1`, `CO-15`, `CO-16`) |
| `EmTransito` | Andamento normal: transferências, chegada à unidade, agendamento, liberações, reencaminhamento |
| `SaiuParaEntrega` | `OEC-1`, `OEC-2`, `OEC-3` e `FC-58` ("será entregue em instantes") |
| `AguardandoRetirada` | Disponível em unidade, caixa postal ou locker (`LDI-1` a `LDI-4`, `BDx-24`, `BDx-77`, `FC-26`, `FC-57`). `LDI-11/13/14` ("encaminhado para retirada") ainda são `EmTransito` |
| `Entregue` | Entregue ao destinatário ou ao contratante (`BDx-1`, `-67`, `-68`, `-70`, `-79`, `CO-8`) |
| `EmDevolucao` | Voltando ao remetente (`BDx-5`, `-33`, `-49`, `-62`, `FC-1`, `FC-47`, `LDE-0`, `OEC-9`, `PAR-24`, `PAR-40`) |
| `Devolvido` | Entregue ao remetente (`BDx-14`, `-23`, `FC-62`) ou devolvido ao país de origem (`PAR-13`) |
| `Cancelado` | Coleta cancelada (`CO-9`, `CO-10`) |
| `Problema` | Entrega sem sucesso, retenção, fiscalização, apreensão, roubo, extravio, bloqueio, pagamento pendente: pede atenção. Os demais `BDx`, `IDC` e `BLQ` (salvo `BLQ-54` e `BLQ-61`) |
| `Desconhecida` | Fora do catálogo, sem descrição ou `EST` |

`finalizada()` vale para `Entregue`, `Devolvido` e `Cancelado`: o objeto não muda mais.

Diferenças em relação ao mapa do MARCELO ENVIOS (`config/tracking.php`, 12/10/2026), que usava "em trânsito" para
evento fora do mapa: aqui `CO-14` é `AguardandoPostagem`; `PAR-24` e `PAR-40` são `EmDevolucao`; `PAR-13` é
`Devolvido`; `PAR-5`, `-11`, `-17`, `-23`, `-30` e `-41` (pagamento ou documentação pendente) são `Problema`; os
eventos sem descrição são `Desconhecida`.

## Tabela

<!-- tabela -->

| Evento | Descrição | Situação no componente |
|---|---|---|
| `BDE-1` | Objeto entregue ao destinatário | `Entregue` |
| `BDE-2` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDE-3` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDE-4` | Objeto não entregue - cliente recusou-se a receber o objeto | `Problema` |
| `BDE-5` | Objeto em devolução | `EmDevolucao` |
| `BDE-6` | Objeto não entregue - cliente desconhecido no local | `Problema` |
| `BDE-7` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDE-8` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDE-10` | Objeto não entregue - cliente mudou-se | `Problema` |
| `BDE-12` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDE-13` | Objeto atingido por incêndio em unidade operacional | `Problema` |
| `BDE-14` | Objeto entregue ao remetente | `Devolvido` |
| `BDE-15` | Recebido na unidade de distribuição | `EmTransito` |
| `BDE-18` | Carteiro não atendido - Entrega não realizada | `Problema` |
| `BDE-19` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDE-20` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDE-21` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDE-22` | Objeto devolvido aos Correios | `Problema` |
| `BDE-23` | Objeto entregue ao remetente | `Devolvido` |
| `BDE-24` | Objeto disponível para retirada em Caixa Postal | `AguardandoRetirada` |
| `BDE-25` | Objeto não entregue - empresa sem expediente | `Problema` |
| `BDE-26` | Prazo de retirada pelo destinatário encerrado | `Problema` |
| `BDE-28` | Objeto e/ou conteúdo avariado | `Problema` |
| `BDE-32` | Objeto com data de entrega agendada | `EmTransito` |
| `BDE-33` | Objeto em devolução | `EmDevolucao` |
| `BDE-34` | Objeto não entregue - Endereço não encontrado | `Problema` |
| `BDE-35` | Objeto não entregue ou não coletado | `Problema` |
| `BDE-36` | Objeto não entregue ou não coletado | `Problema` |
| `BDE-37` | Objeto e/ou conteúdo avariado por acidente com veículo | `Problema` |
| `BDE-38` | Objeto endereçado à empresa falida | `Problema` |
| `BDE-39` | A entrega não pode ser efetuada | `Problema` |
| `BDE-40` | A entrada do objeto no Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDE-41` | A entrega do objeto está condicionada à composição do lote | `Problema` |
| `BDE-42` | Lote de objetos incompleto | `Problema` |
| `BDE-43` | Objeto apreendido por órgão de fiscalização | `Problema` |
| `BDE-44` | Aguardando órgão anuente apresentar documentação fiscal | `Problema` |
| `BDE-45` | Objeto recebido na unidade de distribuição | `EmTransito` |
| `BDE-46` | Tentativa de entrega não efetuada | `Problema` |
| `BDE-47` | Saída para entrega cancelada | `Problema` |
| `BDE-48` | Retirada em Unidade dos Correios não autorizada pelo remetente | `Problema` |
| `BDE-49` | Objeto em devolução | `EmDevolucao` |
| `BDE-50` | Objeto roubado dos Correios | `Problema` |
| `BDE-51` | Objeto roubado dos Correios | `Problema` |
| `BDE-52` | Objeto roubado dos Correios | `Problema` |
| `BDE-53` | Objeto reimpresso e reenviado | `EmTransito` |
| `BDE-54` | Aguardando a guia de recolhimento para pagamento do ICMS Importação | `Problema` |
| `BDE-55` | Solicitação de revisão do tributo | `Problema` |
| `BDE-56` | Objeto com declaração aduaneira ausente ou incorreta | `Problema` |
| `BDE-57` | Revisão de tributo concluída - Objeto liberado | `EmTransito` |
| `BDE-58` | Revisão de tributo concluída - Tributo alterado | `EmTransito` |
| `BDE-59` | Revisão de tributo concluída - Tributo mantido | `EmTransito` |
| `BDE-60` | Objeto encontra-se aguardando prazo para refugo | `Problema` |
| `BDE-61` | Objeto refugado | `Problema` |
| `BDE-62` | Em devolução à origem por determinação da Receita Federal | `EmDevolucao` |
| `BDE-63` | Pagamento não efetuado no prazo | `Problema` |
| `BDE-64` | Solicitação de dados não atendida | `Problema` |
| `BDE-66` | Área com distribuição sujeita a prazo diferenciado | `EmTransito` |
| `BDE-67` | Objeto entregue ao destinatário | `Entregue` |
| `BDE-68` | Objeto entregue na Caixa de Correios Inteligente | `Entregue` |
| `BDE-70` | Objeto entregue ao destinatário | `Entregue` |
| `BDE-71` | Objeto apreendido por: ANVISA/VIGILANCIA SANITARIA | `Problema` |
| `BDE-72` | Objeto apreendido por: BOMBEIROS | `Problema` |
| `BDE-73` | Objeto apreendido por: EXERCITO | `Problema` |
| `BDE-74` | Objeto apreendido por: IBAMA | `Problema` |
| `BDE-76` | Objeto apreendido por: POLICIA CIVIL/MILITAR | `Problema` |
| `BDE-77` | Objeto disponível em locker | `AguardandoRetirada` |
| `BDE-79` | Objeto entregue ao contratante | `Entregue` |
| `BDE-80` | Objeto não localizado no fluxo postal | `Problema` |
| `BDE-81` | A saída do objeto do Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDE-82` | Necessário complementar endereço de entrega | `Problema` |
| `BDE-83` | Prazo de regularização encerrado | `Problema` |
| `BDE-84` | Verificando situação do objeto | `Problema` |
| `BDE-85` | Objeto recebido no Brasil com código de rastreamento fora do padrão | `Problema` |
| `BDE-86` | Objeto com conteúdo avariado | `Problema` |
| `BDE-87` | Objeto destinado a outro país | `Problema` |
| `BDE-89` | Localidade desconhecida no Brasil | `Problema` |
| `BDE-98` | Objeto não localizado no fluxo postal | `Problema` |
| `BDI-1` | Objeto entregue ao destinatário | `Entregue` |
| `BDI-2` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDI-3` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDI-4` | Objeto não entregue - cliente recusou-se a receber o objeto | `Problema` |
| `BDI-5` | Objeto em devolução | `EmDevolucao` |
| `BDI-6` | Cliente desconhecido no local - Entrega não realizada | `Problema` |
| `BDI-7` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDI-8` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDI-10` | Objeto não entregue - cliente mudou-se | `Problema` |
| `BDI-12` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDI-13` | Objeto atingido por incêndio em unidade operacional | `Problema` |
| `BDI-14` | Objeto entregue ao remetente | `Devolvido` |
| `BDI-15` | Recebido na unidade de distribuição | `EmTransito` |
| `BDI-18` | Carteiro não atendido - Entrega não realizada | `Problema` |
| `BDI-19` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDI-20` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDI-21` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDI-22` | Objeto devolvido aos Correios | `Problema` |
| `BDI-23` | Objeto entregue ao remetente | `Devolvido` |
| `BDI-24` | Objeto disponível para retirada em Caixa Postal | `AguardandoRetirada` |
| `BDI-25` | Objeto não entregue - empresa sem expediente | `Problema` |
| `BDI-26` | Prazo de retirada pelo destinatário encerrado | `Problema` |
| `BDI-28` | Objeto e/ou conteúdo avariado | `Problema` |
| `BDI-32` | Objeto com data de entrega agendada | `EmTransito` |
| `BDI-33` | Objeto em devolução | `EmDevolucao` |
| `BDI-34` | Objeto não entregue - Endereço não encontrado | `Problema` |
| `BDI-35` | Objeto não entregue ou não coletado | `Problema` |
| `BDI-36` | Objeto não entregue ou não coletado | `Problema` |
| `BDI-37` | Objeto e/ou conteúdo avariado por acidente com veículo | `Problema` |
| `BDI-38` | Objeto endereçado à empresa falida | `Problema` |
| `BDI-39` | A entrega não pode ser efetuada | `Problema` |
| `BDI-40` | A entrada do objeto no Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDI-41` | A entrega do objeto está condicionada à composição do lote | `Problema` |
| `BDI-42` | Lote de objetos incompleto | `Problema` |
| `BDI-43` | Objeto apreendido por órgão de fiscalização | `Problema` |
| `BDI-44` | Aguardando órgão anuente apresentar documentação fiscal | `Problema` |
| `BDI-45` | Objeto recebido na unidade de distribuição | `EmTransito` |
| `BDI-46` | Tentativa de entrega não efetuada | `Problema` |
| `BDI-47` | Saída para entrega cancelada | `Problema` |
| `BDI-48` | Retirada em Unidade dos Correios não autorizada pelo remetente | `Problema` |
| `BDI-49` | Objeto em devolução | `EmDevolucao` |
| `BDI-50` | Objeto roubado dos Correios | `Problema` |
| `BDI-51` | Objeto roubado dos Correios | `Problema` |
| `BDI-52` | Objeto roubado dos Correios | `Problema` |
| `BDI-53` | Objeto reimpresso e reenviado | `EmTransito` |
| `BDI-54` | Aguardando a guia de recolhimento para pagamento do ICMS Importação | `Problema` |
| `BDI-55` | Solicitação de revisão do tributo | `Problema` |
| `BDI-56` | Objeto com declaração aduaneira ausente ou incorreta | `Problema` |
| `BDI-57` | Revisão de tributo concluída - Objeto liberado | `EmTransito` |
| `BDI-58` | Revisão de tributo concluída - Tributo alterado | `EmTransito` |
| `BDI-59` | Revisão de tributo concluída - Tributo mantido | `EmTransito` |
| `BDI-60` | Objeto encontra-se aguardando prazo para refugo | `Problema` |
| `BDI-61` | Objeto refugado | `Problema` |
| `BDI-62` | Em devolução à origem por determinação da Receita Federal | `EmDevolucao` |
| `BDI-63` | Pagamento não efetuado no prazo | `Problema` |
| `BDI-66` | Área com distribuição sujeita a prazo diferenciado | `EmTransito` |
| `BDI-67` | Objeto entregue ao destinatário | `Entregue` |
| `BDI-68` | Objeto entregue na Caixa de Correios Inteligente | `Entregue` |
| `BDI-70` | Objeto entregue ao destinatário | `Entregue` |
| `BDI-71` | Objeto apreendido por: ANVISA/VIGILANCIA SANITARIA | `Problema` |
| `BDI-72` | Objeto apreendido por: BOMBEIROS | `Problema` |
| `BDI-73` | Objeto apreendido por: EXERCITO | `Problema` |
| `BDI-74` | Objeto apreendido por: IBAMA | `Problema` |
| `BDI-76` | Objeto apreendido por: POLICIA CIVIL/MILITAR | `Problema` |
| `BDI-77` | Objeto disponível em locker | `AguardandoRetirada` |
| `BDI-79` | Objeto entregue ao contratante | `Entregue` |
| `BDI-80` | Objeto não localizado no fluxo postal | `Problema` |
| `BDI-81` | A saída do objeto do Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDI-82` | Necessário complementar endereço de entrega | `Problema` |
| `BDI-83` | Prazo de regularização encerrado | `Problema` |
| `BDI-84` | Verificando situação do objeto | `Problema` |
| `BDI-85` | Objeto recebido no Brasil com código de rastreamento fora do padrão | `Problema` |
| `BDI-86` | Objeto com conteúdo avariado | `Problema` |
| `BDI-87` | Objeto destinado a outro país | `Problema` |
| `BDI-89` | Localidade desconhecida no Brasil | `Problema` |
| `BDI-98` | Objeto não localizado no fluxo postal. | `Problema` |
| `BDR-1` | Objeto entregue ao destinatário | `Entregue` |
| `BDR-2` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDR-3` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDR-4` | Objeto não entregue - cliente recusou-se a receber o objeto | `Problema` |
| `BDR-5` | Objeto em devolução | `EmDevolucao` |
| `BDR-6` | Objeto não entregue - cliente desconhecido no local | `Problema` |
| `BDR-7` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDR-8` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDR-10` | Objeto não entregue - cliente mudou-se | `Problema` |
| `BDR-12` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `BDR-13` | Objeto atingido por incêndio em unidade operacional | `Problema` |
| `BDR-14` | Objeto entregue ao remetente | `Devolvido` |
| `BDR-15` | Recebido na unidade de distribuição | `EmTransito` |
| `BDR-18` | Carteiro não atendido - Entrega não realizada | `Problema` |
| `BDR-19` | Objeto não entregue - endereço incorreto | `Problema` |
| `BDR-20` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDR-21` | Objeto não entregue - carteiro não atendido | `Problema` |
| `BDR-22` | Objeto devolvido aos Correios | `Problema` |
| `BDR-23` | Objeto entregue ao remetente | `Devolvido` |
| `BDR-24` | Objeto disponível para retirada em Caixa Postal | `AguardandoRetirada` |
| `BDR-25` | Objeto não entregue - empresa sem expediente | `Problema` |
| `BDR-26` | Prazo de retirada pelo destinatário encerrado | `Problema` |
| `BDR-28` | Objeto e/ou conteúdo avariado | `Problema` |
| `BDR-31` | Pagamento confirmado | `EmTransito` |
| `BDR-32` | Objeto com data de entrega agendada | `EmTransito` |
| `BDR-33` | Objeto em devolução | `EmDevolucao` |
| `BDR-34` | Objeto não entregue - Endereço não encontrado | `Problema` |
| `BDR-35` | Objeto não entregue ou não coletado | `Problema` |
| `BDR-36` | Objeto não entregue ou não coletado | `Problema` |
| `BDR-37` | Objeto e/ou conteúdo avariado por acidente com veículo | `Problema` |
| `BDR-38` | Objeto endereçado à empresa falida | `Problema` |
| `BDR-39` | A entrega não pode ser efetuada | `Problema` |
| `BDR-40` | A entrada do objeto no Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDR-41` | A entrega do objeto está condicionada à composição do lote | `Problema` |
| `BDR-42` | Lote de objetos incompleto | `Problema` |
| `BDR-43` | Objeto apreendido por órgão de fiscalização | `Problema` |
| `BDR-44` | Aguardando órgão anuente apresentar documentação fiscal | `Problema` |
| `BDR-45` | Objeto recebido na unidade de distribuição | `EmTransito` |
| `BDR-46` | Tentativa de entrega não efetuada | `Problema` |
| `BDR-47` | Saída para entrega cancelada | `Problema` |
| `BDR-48` | Retirada em Unidade dos Correios não autorizada pelo remetente | `Problema` |
| `BDR-49` | Objeto em devolução | `EmDevolucao` |
| `BDR-50` | Objeto roubado dos Correios | `Problema` |
| `BDR-51` | Objeto roubado dos Correios | `Problema` |
| `BDR-52` | Objeto roubado dos Correios | `Problema` |
| `BDR-53` | Objeto reimpresso e reenviado | `EmTransito` |
| `BDR-54` | Aguardando a guia de recolhimento para pagamento do ICMS Importação | `Problema` |
| `BDR-55` | Solicitação de revisão do tributo | `Problema` |
| `BDR-56` | Objeto com declaração aduaneira ausente ou incorreta | `Problema` |
| `BDR-57` | Revisão de tributo concluída - Objeto liberado | `EmTransito` |
| `BDR-58` | Revisão de tributo concluída - Tributo alterado | `EmTransito` |
| `BDR-59` | Revisão de tributo concluída - Tributo mantido | `EmTransito` |
| `BDR-60` | Objeto encontra-se aguardando prazo para refugo | `Problema` |
| `BDR-61` | Objeto refugado | `Problema` |
| `BDR-62` | Em devolução à origem por determinação da Receita Federal | `EmDevolucao` |
| `BDR-63` | Pagamento não efetuado no prazo | `Problema` |
| `BDR-64` | Solicitação de dados não atendida | `Problema` |
| `BDR-66` | Área com distribuição sujeita a prazo diferenciado | `EmTransito` |
| `BDR-67` | Objeto entregue ao destinatário | `Entregue` |
| `BDR-68` | Objeto entregue na Caixa de Correios Inteligente | `Entregue` |
| `BDR-70` | Objeto entregue ao destinatário | `Entregue` |
| `BDR-71` | Objeto apreendido por: ANVISA/VIGILANCIA SANITARIA | `Problema` |
| `BDR-72` | Objeto apreendido por: BOMBEIROS | `Problema` |
| `BDR-73` | Objeto apreendido por: EXERCITO | `Problema` |
| `BDR-74` | Objeto apreendido por: IBAMA | `Problema` |
| `BDR-76` | Objeto apreendido por: POLICIA CIVIL/MILITAR | `Problema` |
| `BDR-77` | Objeto disponível em locker | `AguardandoRetirada` |
| `BDR-79` | Objeto entregue ao contratante | `Entregue` |
| `BDR-80` | Objeto não localizado no fluxo postal | `Problema` |
| `BDR-81` | A saída do objeto do Brasil não foi autorizada pelos órgãos fiscalizadores | `Problema` |
| `BDR-82` | Necessário complementar endereço de entrega | `Problema` |
| `BDR-83` | Prazo de regularização encerrado | `Problema` |
| `BDR-84` | Verificando situação do objeto | `Problema` |
| `BDR-85` | Objeto recebido no Brasil com código de rastreamento fora do padrão | `Problema` |
| `BDR-86` | Objeto com conteúdo avariado | `Problema` |
| `BDR-87` | Objeto destinado a outro país | `Problema` |
| `BDR-89` | Localidade desconhecida no Brasil | `Problema` |
| `BLQ-1` | Solicitação de suspensão de entrega recebida | `Problema` |
| `BLQ-5` | Solicitação de suspensão de entrega recebida | `Problema` |
| `BLQ-11` | Solicitação de suspensão de entrega recebida | `Problema` |
| `BLQ-21` | Solicitação de suspensão de entrega recebida | `Problema` |
| `BLQ-22` | Solicitação de suspensão de entrega recebida | `Problema` |
| `BLQ-24` | Desbloqueio de objeto indenizado | `Problema` |
| `BLQ-30` | Bloqueio teste | `Problema` |
| `BLQ-31` | Extravio varejo pós-indenizado | `Problema` |
| `BLQ-51` | Entrega suspensa | `Problema` |
| `BLQ-54` | Objeto será reencaminhado para entrega | `EmTransito` |
| `BLQ-61` | Desbloqueado | `EmTransito` |
| `CAR-5` | Objeto em trânsito - por favor aguarde | `EmTransito` |
| `CMT-0` | Objeto recebido na Unidade dos Correios | `EmTransito` |
| `CO-1` | Objeto coletado | `Postado` |
| `CO-2` | Coleta solicitada | `AguardandoPostagem` |
| `CO-3` | Carteiro saiu para coleta do objeto | `AguardandoPostagem` |
| `CO-5` | 1ª tentativa de coleta sem sucesso | `Problema` |
| `CO-6` | 2ª tentativa de coleta sem sucesso | `Problema` |
| `CO-8` | Objeto entregue ao destinatário | `Entregue` |
| `CO-9` | Coleta Cancelada | `Cancelado` |
| `CO-10` | Coleta cancelada pelo cliente | `Cancelado` |
| `CO-11` | Objeto Sinistrado | `Problema` |
| `CO-13` | Objeto não coletado | `Problema` |
| `CO-14` | Transformado em e-ticket | `AguardandoPostagem` |
| `CO-15` | Objeto coletado após o horário limite da unidade | `Postado` |
| `CO-16` | Objeto coletado aguardando conferência no destino | `Postado` |
| `DO-1` | Objeto em trânsito - por favor aguarde | `EmTransito` |
| `DO-2` | Objeto em trânsito - por favor aguarde | `EmTransito` |
| `EST-1` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-2` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-3` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-4` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-5` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-6` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `EST-9` | Favor desconsiderar a informação anterior | anula o evento anterior |
| `FC-1` | Objeto será devolvido por solicitação do contratante/remetente | `EmDevolucao` |
| `FC-2` | Objeto com data de entrega agendada | `EmTransito` |
| `FC-3` | Objeto em correção de rota | `EmTransito` |
| `FC-4` | Objeto não entregue - endereço incorreto | `Problema` |
| `FC-5` | Objeto devolvido aos Correios | `Problema` |
| `FC-7` | Objeto não entregue - empresa sem expediente | `Problema` |
| `FC-8` | Área com distribuição sujeita a prazo diferenciado | `EmTransito` |
| `FC-9` | Remetente não retirou objeto na Unidade dos Correios | `Problema` |
| `FC-10` | Objeto recebido na unidade de distribuição | `EmTransito` |
| `FC-11` | Objeto destruído com autorização do remetente | `Problema` |
| `FC-12` | Objeto encaminhado para fiscalização - ANVISA | `Problema` |
| `FC-13` | Fiscalizado ANATEL | `Problema` |
| `FC-14` | Impossibilidade de envio devido a embargos no exterior | `Problema` |
| `FC-15` | Objeto retido por abandono | `Problema` |
| `FC-17` | Objeto dispensado do pagamento de impostos | `EmTransito` |
| `FC-19` | Objeto encaminhado para fiscalização - ANATEL | `Problema` |
| `FC-23` | Entrega não realizada - motivo de força maior | `Problema` |
| `FC-24` | Em fiscalização pela Receita Federal | `Problema` |
| `FC-26` | Objeto aguardando retirada em unidade do correio de destino | `AguardandoRetirada` |
| `FC-27` | Objeto encaminhado para emissão de DSI | `Problema` |
| `FC-28` | Objeto liberado por decisão judicial | `EmTransito` |
| `FC-29` | Objeto encaminhado para apreensão | `Problema` |
| `FC-31` | Aguardando autorização para exportação | `Problema` |
| `FC-32` | Objeto encaminhado para fiscalização - INMETRO | `Problema` |
| `FC-33` | Objeto encaminhado para fiscalização - Polícia Federal | `Problema` |
| `FC-34` | Objeto encaminhado para fiscalização - VIGIAGRO | `Problema` |
| `FC-35` | Objeto encaminhado para fiscalização - Exército | `Problema` |
| `FC-36` | Objeto liberado de impostos por decisão judicial | `EmTransito` |
| `FC-37` | Objeto encaminhado para fiscalização - IBAMA | `Problema` |
| `FC-38` | Objeto retido por determinação judicial | `Problema` |
| `FC-39` | Objeto encaminhado para fiscalização - ANCINE | `Problema` |
| `FC-41` | Problemas na documentação do objeto | `Problema` |
| `FC-42` | Objeto contém produto não permitido | `Problema` |
| `FC-47` | Objeto será devolvido por solicitação do contratante/remetente | `EmDevolucao` |
| `FC-51` | Objeto roubado dos Correios | `Problema` |
| `FC-57` | Aguardando retirada em locker | `AguardandoRetirada` |
| `FC-58` | Objeto será entregue em instantes | `SaiuParaEntrega` |
| `FC-59` | Pedido de confirmação de entrega de telegrama encaminhado para e-mail contratado | `EmTransito` |
| `FC-60` | O PEDIDO DE CONFIRMAÇÃO DE ENTREGA SERÁ GERADO E ENVIADO APÓS BAIXA DO TELEGRAMA | `EmTransito` |
| `FC-61` | Objeto coletado com problema de postagem | `Problema` |
| `FC-62` | Objeto com problema de postagem foi devolvido ao remetente | `Devolvido` |
| `FC-63` | Direcionado para entrega interna a pedido do cliente | `EmTransito` |
| `FC-66` | *(sem descrição no catálogo)* | `Desconhecida` |
| `FC-68` | *(sem descrição no catálogo)* | `Desconhecida` |
| `FC-82` | Etiqueta emitida *(observado em homologação; sem descrição no catálogo)* | `AguardandoPostagem` |
| `FC-83` | *(sem descrição no catálogo)* | `Desconhecida` |
| `FC-84` | *(sem descrição no catálogo)* | `Desconhecida` |
| `FC-85` | *(sem descrição no catálogo)* | `Desconhecida` |
| `FC-93` | *(sem descrição no catálogo)* | `Desconhecida` |
| `IDC-1` | Objeto não localizado | `Problema` |
| `IDC-2` | Objeto não localizado | `Problema` |
| `IDC-3` | Objeto não localizado | `Problema` |
| `IDC-4` | Objeto não localizado | `Problema` |
| `IDC-5` | Objeto não localizado | `Problema` |
| `IDC-6` | Objeto não localizado | `Problema` |
| `IDC-7` | Objeto não localizado | `Problema` |
| `LDE-0` | Objeto saiu para entrega ao remetente | `EmDevolucao` |
| `LDI-1` | Objeto aguardando retirada no endereço indicado | `AguardandoRetirada` |
| `LDI-2` | Objeto disponível para retirada em Caixa Postal | `AguardandoRetirada` |
| `LDI-3` | Objeto aguardando retirada no endereço indicado | `AguardandoRetirada` |
| `LDI-4` | Objeto aguardando retirada no endereço indicado | `AguardandoRetirada` |
| `LDI-11` | Objeto encaminhado para retirada no endereço indicado | `EmTransito` |
| `LDI-13` | Objeto encaminhado para retirada no endereço indicado | `EmTransito` |
| `LDI-14` | Objeto encaminhado para retirada no endereço indicado | `EmTransito` |
| `OEC-1` | Objeto saiu para entrega ao destinatário | `SaiuParaEntrega` |
| `OEC-2` | Objeto saiu para entrega em locker | `SaiuParaEntrega` |
| `OEC-3` | Objeto está em rota de entrega | `SaiuParaEntrega` |
| `OEC-9` | Objeto saiu para entrega ao remetente | `EmDevolucao` |
| `PAR-2` | Objeto em trânsito | `EmTransito` |
| `PAR-3` | Objeto retido pela alfândega do país de destino | `Problema` |
| `PAR-4` | Objeto encaminhado para fiscalização aduaneira de exportação | `EmTransito` |
| `PAR-5` | Documentação insuficiente para análise do pedido de revisão | `Problema` |
| `PAR-6` | *(sem descrição no catálogo)* | `Desconhecida` |
| `PAR-7` | *(sem descrição no catálogo)* | `Desconhecida` |
| `PAR-8` | *(sem descrição no catálogo)* | `Desconhecida` |
| `PAR-9` | *(sem descrição no catálogo)* | `Desconhecida` |
| `PAR-10` | Fiscalização aduaneira finalizada | `EmTransito` |
| `PAR-11` | Aguardando pagamento de taxas no país de destino | `Problema` |
| `PAR-12` | Informações prestadas pelo cliente em análise | `EmTransito` |
| `PAR-13` | Objeto devolvido ao país de origem | `Devolvido` |
| `PAR-14` | Revisão de tributos solicitada pelo cliente | `EmTransito` |
| `PAR-15` | Objeto recebido em | `EmTransito` |
| `PAR-16` | Objeto recebido pelos Correios do Brasil | `EmTransito` |
| `PAR-17` | Aguardando pagamento | `Problema` |
| `PAR-18` | Objeto recebido na unidade de exportação no país de origem | `EmTransito` |
| `PAR-19` | Fiscalização aduaneira finalizada no país de destino | `EmTransito` |
| `PAR-20` | Objeto recebido pelos Correios do Brasil com embalagem danificada | `EmTransito` |
| `PAR-21` | Encaminhado para fiscalização aduaneira | `EmTransito` |
| `PAR-22` | Encaminhado para fiscalização no país de destino | `EmTransito` |
| `PAR-23` | Aguardando autorização da Receita Federal para devolução do objeto ao remetente | `Problema` |
| `PAR-24` | Devolução autorizada pela Receita Federal | `EmDevolucao` |
| `PAR-25` | Faltam informações. Sua ação é necessária | `Problema` |
| `PAR-26` | Destinatário recusou o objeto | `Problema` |
| `PAR-27` | Despacho Postal pago na origem | `EmTransito` |
| `PAR-28` | Encaminhado para a unidade de tratamento | `EmTransito` |
| `PAR-29` | Objeto não poderá ser entregue em domicílio | `Problema` |
| `PAR-30` | Aguardando pagamento do despacho postal | `Problema` |
| `PAR-31` | Pagamento confirmado | `EmTransito` |
| `PAR-32` | Aguardando confirmação de pagamento pela operadora | `EmTransito` |
| `PAR-33` | Pagamento não confirmado pela operadora | `Problema` |
| `PAR-34` | Liberado sem tributação | `EmTransito` |
| `PAR-40` | Devolução a pedido | `EmDevolucao` |
| `PAR-41` | Prazo de pagamento encerrado | `Problema` |
| `PAR-42` | Objeto liberado | `EmTransito` |
| `PMT-1` | Objeto encaminhado | `EmTransito` |
| `PO-1` | Objeto postado | `Postado` |
| `PO-9` | Objeto postado após o horário limite da unidade | `Postado` |
| `RO-0` | Objeto em trânsito - por favor aguarde | `EmTransito` |
| `RO-1` | Objeto em trânsito - por favor aguarde | `EmTransito` |
| `RO-2` | *(sem descrição no catálogo)* | `Desconhecida` |
| `TRI-0` | Objeto encaminhado | `EmTransito` |

<!-- /tabela -->
