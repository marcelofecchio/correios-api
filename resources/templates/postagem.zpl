^XA
^CI28
^PW800
^LL1239
^LH0,0
^LS0
^FX --- Moldura externa ---
^FO17,45^GB765,1155,2^FS
^FX --- Logo dos Correios (parcial; area de 256x250 a partir de 25,50) ---
{{>logo_correios}}
^FX --- DataMatrix (164 caracteres) ---
^FT290,270^BXN,5,200^FD{{{datamatrix}}}^FS
^FX --- Logo do servico (parcial) ---
{{>logo_servico}}
^FX --- Dados do envio ---
^FT31,305^A0N,28,28^FH^FD{{linha_nf}}^FS
^FT270,305^A0N,28,28^FH^FDContrato: {{contrato}}^FS
^FT600,305^A0N,28,28^FH^FDVol: {{volume}}^FS
^FT31,335^A0N,28,28^FH^FDPeso: {{peso_gramas}} g^FS
^FT270,335^A0N,28,28^FH^FD{{nome_servico}}^FS
^FT600,335^A0N,28,28^FH^FD{{siglas_adicionais}}^FS
^FX --- Codigo do objeto: Code 128 com texto legivel acima ---
^BY3,3,120^FT120,510^BCN,,Y,Y^FD{{{codigo_objeto}}}^FS
^FX --- Recebimento ---
^FT40,550^A0N,32,31^FDRecebedor:______________________________^FS
^FT40,590^A0N,32,31^FDAssinatura:____________^FS
^FT380,590^A0N,32,31^FDDocumento:______________^FS
^FX --- Bloco do destinatario ---
^FO17,610^GB765,275,2^FS
^FO17,610^GB195,40,40^FS
^FT30,640^FR^A0N,30,29^FDDESTINATARIO^FS
^FO50,657^A0N,28,27^FH^FD{{destinatario_nome}}^FS
^FO50,686^A0N,26,25^FB710,2,0,L^FH^FD{{destinatario_endereco}}^FS
^FO50,740^A0N,26,25^FH^FD{{destinatario_bairro}}^FS
^FO50,768^A0N,32,31^FH^FD{{destinatario_cep_hifen}}^FS
^FO212,771^A0N,28,27^FH^FD{{destinatario_cidade_uf}}^FS
^FX --- CEP de destino em Code 128, sem texto legivel ---
^BY3,2,70^FO50,805^BCN,,N,N^FD{{{destinatario_cep}}}^FS
^FX --- Bloco do remetente ---
^FT30,920^A0N,32,31^FDRemetente:^FS
^FO50,930^A0N,25,24^FH^FD{{remetente_nome}}^FS
^FO50,958^A0N,25,24^FB710,2,0,L^FH^FD{{remetente_endereco}}^FS
^FO50,1010^A0N,25,24^FH^FD{{remetente_bairro}}^FS
^FO50,1038^A0N,25,24^FH^FD{{remetente_cep_hifen}} {{remetente_cidade_uf}}^FS
^FO37,1070^GB726,0,1^FS
^FX --- Observacoes (ate 2 linhas) ---
^FO50,1082^A0N,25,24^FB710,2,0,L^FH^FD{{observacoes}}^FS
{{>marca_homologacao}}
^XZ
