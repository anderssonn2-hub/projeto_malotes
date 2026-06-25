#!/usr/bin/env python3
# -*- coding: utf-8 -*-
with open('conferencia_pacotes.php','rb') as f:
    c = f.read()

# 1) Adicionar var JS de restricoes logo apos postosBloqueadosMap
old_js = (b'    var postosBloqueados = <?php echo json_encode($postos_bloqueados); ?>;\r\n'
          b'    var postosBloqueadosMap = {};')
new_js = (b'    var postosBloqueados = <?php echo json_encode($postos_bloqueados); ?>;\r\n'
          b'    var postosBloqueadosMap = {};\r\n'
          b'    // v1.2.2: Restricoes de postos (segurar/adiantar/fechado/personalizados)\r\n'
          b'    var postosRestricoes = <?php echo json_encode($postos_restricoes); ?>;')

# 2) Aviso de restricao apos o bloco de bloqueado
old_end = (b'            registrarHistoricoLeitura(\'Posto bloqueado\', \'Posto \' + postoLido + \' bloqueado para confer\xc3\xaancia.\', valor);\r\n'
           b'            finalizarProcessamento(true);\r\n'
           b'            return;\r\n'
           b'        }')
new_end = (b'            registrarHistoricoLeitura(\'Posto bloqueado\', \'Posto \' + postoLido + \' bloqueado para confer\xc3\xaancia.\', valor);\r\n'
           b'            finalizarProcessamento(true);\r\n'
           b'            return;\r\n'
           b'        }\r\n'
           b'\r\n'
           b'        // v1.2.2: Aviso de restricao de posto (nao bloqueia fluxo, apenas alerta)\r\n'
           b'        if (postosRestricoes && postosRestricoes[postoLido]) {\r\n'
           b'            var dadosRest  = postosRestricoes[postoLido];\r\n'
           b'            var tipoRest   = (dadosRest.tipo   || \'restricao\').toString();\r\n'
           b'            var motivoRest = (dadosRest.motivo || \'\').toString().trim();\r\n'
           b'            var corRest    = (dadosRest.cor    || \'#e65100\').toString();\r\n'
           b'            var textoVozR  = tipoRest + (motivoRest ? \': \' + motivoRest : \'\');\r\n'
           b'            avisarSomOuFala(\'restricao_posto:\' + postoLido, null, textoVozR);\r\n'
           b'            if (mensagemLeitura) {\r\n'
           b'                mensagemLeitura.innerHTML = \'<strong style="color:\' + corRest + \'">\u26a0 Restricao [\' + tipoRest + \']:</strong> Posto \' + postoLido + (motivoRest ? \' &mdash; \' + motivoRest : \'\');\r\n'
           b'            }\r\n'
           b'            registrarHistoricoLeitura(\'Restricao de posto\', \'Posto \' + postoLido + \' tem restricao: \' + tipoRest + (motivoRest ? \' (\' + motivoRest + \')\' : \'\'), valor);\r\n'
           b'            // Nao bloqueia — apenas avisa, conferencia continua normalmente\r\n'
           b'        }')

r1 = old_js in c
r2 = old_end in c

if r1:
    c = c.replace(old_js, new_js, 1)
if r2:
    c = c.replace(old_end, new_end, 1)

with open('conferencia_pacotes.php','wb') as f:
    f.write(c)

print('js_var_ok:', r1, '| aviso_ok:', r2)
