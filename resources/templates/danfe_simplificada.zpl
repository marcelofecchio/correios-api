^XA
^CI28
^PW800
^LL1199
^LH0,0
^LS0
^FX --- Cabecalho ---
^FT30,62^A0N,34,34^FH^FDDANFe Simplificado - Etiqueta^FS
^FT30,98^A0N,24,24^FH^FD{{tipo_operacao}}^FS
^FT520,50^A0N,24,24^FH^FDNº {{numero_nf}}^FS
^FT520,80^A0N,24,24^FH^FDSérie: {{serie}}^FS
^FT520,110^A0N,24,24^FH^FDEmissão: {{data_emissao}}^FS
^FO14,125^GB772,0,3^FS
^FX --- Emitente ---
^FT30,155^A0N,22,22^FDEMITENTE^FS
^FO30,163^A0N,27,27^FH^FD{{emitente_nome}}^FS
^FO30,196^A0N,23,23^FH^FDCNPJ: {{emitente_cnpj}}  IE: {{emitente_ie}}  UF: {{emitente_uf}}^FS
^FO30,224^A0N,23,23^FH^FD{{emitente_endereco}}^FS
^FO14,258^GB772,0,3^FS
^FX --- Chave de acesso: Code 128 subconjunto C ---
^FO0,270^A0N,25,25^FB800,1,0,C^FDCHAVE DE ACESSO^FS
^BY2,3,110^FO123,303^BCN,110,N,N^FD>;{{{chave_acesso}}}^FS
^FO0,423^A0N,24,24^FB800,1,0,C^FD{{chave_acesso_formatada}}^FS
^FO0,457^A0N,23,23^FB800,1,0,C^FH^FD{{linha_protocolo}}^FS
^FO14,492^GB772,0,3^FS
{{>danfe_codigo_envio}}
^FX --- Destinatario ---
^FO14,665^GB250,45,45^FS
^FT30,699^FR^A0N,28,28^FDDESTINATÁRIO^FS
^FO30,722^A0N,27,27^FH^FD{{destinatario_nome}}^FS
^FO30,755^A0N,23,23^FH^FD{{destinatario_documento}}  UF: {{destinatario_uf}}^FS
^FO30,783^A0N,23,23^FH^FD{{destinatario_endereco_linha1}}^FS
^FO30,811^A0N,23,23^FH^FD{{destinatario_endereco_linha2}}^FS
^FO14,848^GB772,0,3^FS
^FX --- Observacoes (opcional, texto livre da aplicacao) ---
^FO30,864^A0N,24,24^FB740,3,3,L^FH^FD{{observacoes}}^FS
^XZ
