<?php
$controle_canal = isset($_GET['canal_controle']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['canal_controle']) : 'principal';
if ($controle_canal === '') {
    $controle_canal = 'principal';
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Lacres/Displays remotos v1.0.1</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --line: #d6dfeb;
            --text: #17263a;
            --sub: #6a7a8d;
            --ok: #1f7a49;
            --erro: #a61e4d;
            --azul: #1f4fbf;
            --verde: #0f766e;
            --ambar: #b45309;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            background:
                radial-gradient(circle at top, #ffffff 0%, #eef4fb 38%, var(--bg) 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 18px 14px 28px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px;
            box-shadow: 0 14px 32px rgba(18, 42, 66, 0.08);
            margin-bottom: 14px;
        }
        .hero {
            text-align: center;
            padding-top: 24px;
            padding-bottom: 24px;
        }
        h1, h2, p { margin: 0; }
        h1 {
            font-size: 24px;
            letter-spacing: 0.2px;
        }
        .contexto-remoto {
            margin-top: 18px;
            font-size: clamp(34px, 8vw, 58px);
            font-weight: 800;
            line-height: 1;
            color: #103c73;
            word-break: break-word;
        }
        .meta-contexto {
            margin-top: 10px;
            color: var(--sub);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .resumo-contexto {
            margin-top: 10px;
            color: var(--sub);
            font-size: 13px;
            line-height: 1.5;
        }
        .status {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #edf3fb;
            color: #23415f;
            font-size: 13px;
            font-weight: 800;
        }
        .status.ok { background: #e6f6ec; color: var(--ok); }
        .status.erro { background: #fff0f4; color: var(--erro); }
        .bloco-topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .bloco-topo h2 {
            font-size: 18px;
        }
        .valor-atual {
            min-width: 94px;
            text-align: right;
            font-size: 13px;
            font-weight: 800;
            color: #103c73;
        }
        .campo label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-linha {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 8px;
            align-items: stretch;
        }
        .input-linha input {
            width: 100%;
            border: 1px solid #c9d7e6;
            border-radius: 16px;
            padding: 16px 14px;
            font-size: 28px;
            font-weight: 800;
            color: #0d2f57;
            background: #fff;
        }
        .input-linha input.display {
            font-size: 20px;
            letter-spacing: 0.8px;
            grid-column: 1 / -1;
        }
        .btn-seta {
            width: 54px;
            border: 1px solid #c9d7e6;
            border-radius: 16px;
            background: #f7faff;
            color: #0d2f57;
            font-size: 24px;
            font-weight: 800;
            cursor: pointer;
        }
        .acoes {
            margin-top: 12px;
        }
        .btn-triplo {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 18px 14px;
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            touch-action: manipulation;
        }
        .btn-blue { background: linear-gradient(180deg, #2563eb 0%, #1e40af 100%); }
        .btn-amber { background: linear-gradient(180deg, #d97706 0%, #b45309 100%); }
        .btn-green { background: linear-gradient(180deg, #0f766e 0%, #115e59 100%); }
        .btn-split { background: linear-gradient(180deg, #be123c 0%, #9f1239 100%); }
        .ajuda {
            margin-top: 10px;
            color: var(--sub);
            font-size: 12px;
            line-height: 1.5;
        }
        .rodape-link {
            display: inline-flex;
            margin-top: 6px;
            color: var(--azul);
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }
        @media (max-width: 640px) {
            .wrap {
                padding: 12px 10px 24px;
            }
            .card {
                border-radius: 18px;
                padding: 14px;
            }
            .input-linha {
                grid-template-columns: 1fr 50px 50px;
            }
            .input-linha input {
                font-size: 24px;
            }
            .input-linha input.display {
                font-size: 18px;
            }
            .btn-seta {
                width: 50px;
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="card hero">
            <h1>Lacres/Displays remotos</h1>
            <div class="contexto-remoto" id="estadoRegional">-</div>
            <div class="meta-contexto">Atualizado em <span id="estadoAtualizado">-</span></div>
            <div class="resumo-contexto" id="estadoResumo">Aguardando posto ou regional ativos na conferência.</div>
            <div class="status" id="statusEnvio">Aguardando operação.</div>
        </section>

        <section class="card">
            <div class="bloco-topo">
                <h2>Lacre IIPR</h2>
                <div class="valor-atual" id="valorAtualIipr">Próximo: -</div>
            </div>
            <div class="campo">
                <label for="inputLacreIiprRemoto">Lacre inicial ou próximo lacre</label>
                <div class="input-linha">
                    <input type="text" id="inputLacreIiprRemoto" inputmode="numeric" maxlength="12" placeholder="Ex.: 100">
                    <button type="button" class="btn-seta" data-ajuste="iipr:-1">&#8630;</button>
                    <button type="button" class="btn-seta" data-ajuste="iipr:1">&#8631;</button>
                </div>
            </div>
            <div class="acoes">
                <button type="button" class="btn-triplo btn-blue" data-acao="atribuir_iipr">Atribuir lacre IIPR</button>
            </div>
            <div class="ajuda">Ao confirmar com 3 toques, o valor atual será enviado e a sequência seguirá automaticamente para o próximo número.</div>
        </section>

        <section class="card">
            <div class="bloco-topo">
                <h2>Lacre Correios</h2>
                <div class="valor-atual" id="valorAtualCorreios">Próximo: -</div>
            </div>
            <div class="campo">
                <label for="inputLacreCorreiosRemoto">Lacre inicial ou próximo lacre</label>
                <div class="input-linha">
                    <input type="text" id="inputLacreCorreiosRemoto" inputmode="numeric" maxlength="12" placeholder="Ex.: 101">
                    <button type="button" class="btn-seta" data-ajuste="correios:-1">&#8630;</button>
                    <button type="button" class="btn-seta" data-ajuste="correios:1">&#8631;</button>
                </div>
            </div>
            <div class="acoes">
                <button type="button" class="btn-triplo btn-amber" data-acao="atribuir_correios">Atribuir lacre Correios</button>
            </div>
            <div class="ajuda">Se a sequência quebrar, digite um novo número aqui. Esse novo valor passa a ser a continuação oficial.</div>
        </section>

        <section class="card">
            <div class="bloco-topo">
                <h2>Display Correios</h2>
                <div class="valor-atual">35 dígitos</div>
            </div>
            <div class="campo">
                <label for="inputEtiquetaCorreiosRemoto">Display atual</label>
                <div class="input-linha">
                    <input type="text" class="display" id="inputEtiquetaCorreiosRemoto" inputmode="numeric" maxlength="35" placeholder="Leia ou digite o display Correios">
                </div>
            </div>
            <div class="acoes">
                <button type="button" class="btn-triplo btn-green" data-acao="atribuir_display">Atribuir display Correios</button>
            </div>
            <div class="ajuda">Após atribuir o display, o foco volta automaticamente para o lacre Correios. Se a Central usar o mesmo display, basta mantê-lo preenchido.</div>
            <a class="rodape-link" href="conferencia_pacotes_comandos.php" target="_blank" rel="noopener">Abrir folha de comandos por código de barras</a>
        </section>

        <section class="card">
            <div class="bloco-topo">
                <h2>Split</h2>
                <div class="valor-atual">Novo bloco</div>
            </div>
            <div class="acoes">
                <button type="button" class="btn-triplo btn-split" data-acao="marcar_split">Marcar split para o próximo bloco</button>
            </div>
            <div class="ajuda">Use 3 toques quando quiser separar os próximos malotes em um novo bloco visual, com lacres e displays independentes.</div>
        </section>
    </div>

    <script>
    (function() {
        var canal = <?php echo json_encode($controle_canal); ?> || 'principal';
        var storageKey = 'controle_remoto_lacres_v92519_' + canal;
        var statusEnvio = document.getElementById('statusEnvio');
        var estadoRegional = document.getElementById('estadoRegional');
        var estadoResumo = document.getElementById('estadoResumo');
        var estadoAtualizado = document.getElementById('estadoAtualizado');
        var inputLacreIiprRemoto = document.getElementById('inputLacreIiprRemoto');
        var inputLacreCorreiosRemoto = document.getElementById('inputLacreCorreiosRemoto');
        var inputEtiquetaCorreiosRemoto = document.getElementById('inputEtiquetaCorreiosRemoto');
        var valorAtualIipr = document.getElementById('valorAtualIipr');
        var valorAtualCorreios = document.getElementById('valorAtualCorreios');
        var toques = {};
        var ultimoEstadoRemoto = null;

        function normalizarNumero(valor, limite) {
            return String(valor || '').replace(/\D+/g, '').slice(0, limite || 35);
        }

        function obterInputPorTipo(tipo) {
            if (tipo === 'iipr') return inputLacreIiprRemoto;
            if (tipo === 'correios') return inputLacreCorreiosRemoto;
            return null;
        }

        function atualizarStatus(texto, tipo) {
            if (!statusEnvio) return;
            statusEnvio.textContent = texto;
            statusEnvio.className = 'status' + (tipo ? ' ' + tipo : '');
        }

        function obterResumoEstadoAtual() {
            if (ultimoEstadoRemoto && ultimoEstadoRemoto.resumo) {
                return String(ultimoEstadoRemoto.resumo || '');
            }
            return estadoResumo ? String(estadoResumo.textContent || '') : '';
        }

        function normalizarCodigoTresDigitos(valor) {
            var digitos = String(valor || '').replace(/\D+/g, '');
            if (!digitos) return '';
            if (digitos.length > 3) {
                digitos = digitos.substr(0, 3);
            }
            return digitos.padStart(3, '0');
        }

        function formatarRotuloContexto(estado) {
            var textoRegional = estado && estado.regional ? String(estado.regional).trim() : '';
            var textoPosto = estado && estado.posto ? String(estado.posto).trim() : '';
            var origem = textoRegional && textoRegional !== '-' ? textoRegional : textoPosto;
            var matchPosto;
            var matchRegional;
            var codigo;
            var sufixo;

            if (!origem || origem === '-') {
                return '-';
            }

            matchRegional = origem.match(/^regional\s+(\d{1,3})$/i);
            if (matchRegional && matchRegional[1]) {
                codigo = normalizarCodigoTresDigitos(matchRegional[1]);
                if (codigo === '002') {
                    codigo = '200';
                }
                return codigo ? ('Regional ' + codigo) : origem;
            }

            matchPosto = origem.match(/^posto\s+(\d{1,3})(.*)$/i);
            if (matchPosto && matchPosto[1]) {
                codigo = normalizarCodigoTresDigitos(matchPosto[1]);
                sufixo = String(matchPosto[2] || '').replace(/^[\s\-]+/, '').trim();
                return 'Posto ' + codigo + (sufixo ? (' ' + sufixo) : '');
            }

            return origem;
        }

        function atualizarValoresAtuais() {
            if (valorAtualIipr) {
                valorAtualIipr.textContent = 'Próximo: ' + (normalizarNumero(inputLacreIiprRemoto ? inputLacreIiprRemoto.value : '', 12) || '-');
            }
            if (valorAtualCorreios) {
                valorAtualCorreios.textContent = 'Próximo: ' + (normalizarNumero(inputLacreCorreiosRemoto ? inputLacreCorreiosRemoto.value : '', 12) || '-');
            }
        }

        function persistirRascunho() {
            var payload = {
                lacre_iipr: normalizarNumero(inputLacreIiprRemoto ? inputLacreIiprRemoto.value : '', 12),
                lacre_correios: normalizarNumero(inputLacreCorreiosRemoto ? inputLacreCorreiosRemoto.value : '', 12),
                etiqueta_correios: normalizarNumero(inputEtiquetaCorreiosRemoto ? inputEtiquetaCorreiosRemoto.value : '', 35)
            };
            localStorage.setItem(storageKey, JSON.stringify(payload));
            atualizarValoresAtuais();
        }

        function carregarRascunho() {
            try {
                var bruto = localStorage.getItem(storageKey);
                var payload = bruto ? JSON.parse(bruto) : null;
                if (!payload) return;
                if (inputLacreIiprRemoto && payload.lacre_iipr) inputLacreIiprRemoto.value = payload.lacre_iipr;
                if (inputLacreCorreiosRemoto && payload.lacre_correios) inputLacreCorreiosRemoto.value = payload.lacre_correios;
                if (inputEtiquetaCorreiosRemoto && payload.etiqueta_correios) inputEtiquetaCorreiosRemoto.value = payload.etiqueta_correios;
            } catch (e) {}
            atualizarValoresAtuais();
        }

        function ajustarSequencia(tipo, delta) {
            var input = obterInputPorTipo(tipo);
            var valor = normalizarNumero(input ? input.value : '', 12);
            var largura;
            var numero;
            if (!input) return;
            largura = valor ? valor.length : 1;
            numero = valor ? parseInt(valor, 10) : 0;
            if (isNaN(numero)) numero = 0;
            numero += delta;
            if (numero < 0) numero = 0;
            input.value = String(numero).padStart(largura, '0');
            persistirRascunho();
            input.focus();
            input.select();
        }

        function incrementarSequenciaAposEnvio(input) {
            var valor = normalizarNumero(input ? input.value : '', 12);
            var largura;
            var numero;
            if (!input || !valor) return;
            largura = valor.length;
            numero = parseInt(valor, 10);
            if (isNaN(numero)) return;
            input.value = String(numero + 1).padStart(largura, '0');
            persistirRascunho();
        }

        function publicarEstadoDigitado(acao, payload) {
            var formData = new FormData();
            formData.append('atualizar_estado_remoto_ajax', '1');
            formData.append('canal', canal);
            formData.append('usuario', ultimoEstadoRemoto && ultimoEstadoRemoto.usuario ? ultimoEstadoRemoto.usuario : '');
            formData.append('posto', ultimoEstadoRemoto && ultimoEstadoRemoto.posto ? ultimoEstadoRemoto.posto : '');
            formData.append('regional', ultimoEstadoRemoto && ultimoEstadoRemoto.regional ? ultimoEstadoRemoto.regional : '');
            formData.append('resumo', obterResumoEstadoAtual());
            formData.append('lacre_iipr', acao === 'atribuir_iipr' ? payload.valor : (ultimoEstadoRemoto && ultimoEstadoRemoto.lacre_iipr ? ultimoEstadoRemoto.lacre_iipr : ''));
            formData.append('lacre_correios', acao === 'atribuir_correios' ? payload.valor : (ultimoEstadoRemoto && ultimoEstadoRemoto.lacre_correios ? ultimoEstadoRemoto.lacre_correios : ''));
            formData.append('etiqueta_correios', acao === 'atribuir_display' ? payload.valorAux : (ultimoEstadoRemoto && ultimoEstadoRemoto.etiqueta_correios ? ultimoEstadoRemoto.etiqueta_correios : ''));

            return fetch('conferencia_pacotes.php', { method: 'POST', body: formData })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (!data || !data.success) {
                        throw new Error('Falha ao publicar estado remoto');
                    }
                    ultimoEstadoRemoto = ultimoEstadoRemoto || {};
                    if (acao === 'atribuir_iipr') {
                        ultimoEstadoRemoto.lacre_iipr = payload.valor;
                    } else if (acao === 'atribuir_correios') {
                        ultimoEstadoRemoto.lacre_correios = payload.valor;
                    } else if (acao === 'atribuir_display') {
                        ultimoEstadoRemoto.etiqueta_correios = payload.valorAux;
                    }
                });
        }

        function montarPayload(acao) {
            var comando = '';
            var valor = '';
            var valorAux = '';

            if (acao === 'atribuir_iipr') {
                comando = 'atribuir_iipr';
                valor = normalizarNumero(inputLacreIiprRemoto ? inputLacreIiprRemoto.value : '', 12);
            } else if (acao === 'atribuir_correios') {
                comando = 'atribuir_correios';
                valor = normalizarNumero(inputLacreCorreiosRemoto ? inputLacreCorreiosRemoto.value : '', 12);
            } else if (acao === 'atribuir_display') {
                comando = 'atribuir_display';
                valorAux = normalizarNumero(inputEtiquetaCorreiosRemoto ? inputEtiquetaCorreiosRemoto.value : '', 35);
            } else if (acao === 'marcar_split') {
                comando = 'marcar_split';
            }

            return { comando: comando, valor: valor, valorAux: valorAux };
        }

        function tratarPosEnvio(acao) {
            if (acao === 'atribuir_iipr') {
                incrementarSequenciaAposEnvio(inputLacreIiprRemoto);
                if (inputLacreIiprRemoto) {
                    inputLacreIiprRemoto.focus();
                    inputLacreIiprRemoto.select();
                }
                return;
            }
            if (acao === 'atribuir_correios') {
                incrementarSequenciaAposEnvio(inputLacreCorreiosRemoto);
                if (inputEtiquetaCorreiosRemoto) {
                    inputEtiquetaCorreiosRemoto.focus();
                    inputEtiquetaCorreiosRemoto.select();
                }
                return;
            }
            if (acao === 'atribuir_display') {
                persistirRascunho();
                if (inputLacreCorreiosRemoto) {
                    inputLacreCorreiosRemoto.focus();
                    inputLacreCorreiosRemoto.select();
                }
            }
        }

        function enviarAcao(acao) {
            var payload = montarPayload(acao);
            var formData = new FormData();
            if (!payload.comando) {
                atualizarStatus('Ação inválida.', 'erro');
                return;
            }
            if (acao === 'atribuir_iipr' && !payload.valor) {
                atualizarStatus('Informe o lacre IIPR inicial antes de atribuir.', 'erro');
                if (inputLacreIiprRemoto) inputLacreIiprRemoto.focus();
                return;
            }
            if (acao === 'atribuir_correios' && !payload.valor) {
                atualizarStatus('Informe o lacre Correios inicial antes de atribuir.', 'erro');
                if (inputLacreCorreiosRemoto) inputLacreCorreiosRemoto.focus();
                return;
            }
            if (acao === 'atribuir_display' && !payload.valorAux) {
                atualizarStatus('Leia ou digite o display Correios antes de atribuir.', 'erro');
                if (inputEtiquetaCorreiosRemoto) inputEtiquetaCorreiosRemoto.focus();
                return;
            }
            if (acao === 'marcar_split') {
                atualizarStatus('Split preparado para o próximo bloco após 3 toques.', '');
            }

            formData.append('enviar_comando_remoto_ajax', '1');
            formData.append('canal', canal);
            formData.append('comando', payload.comando);
            formData.append('valor', payload.valor);
            formData.append('valor_aux', payload.valorAux);

            fetch('conferencia_pacotes.php', { method: 'POST', body: formData })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (!data || !data.success) {
                        atualizarStatus('Falha ao enviar operação.', 'erro');
                        return;
                    }
                    return publicarEstadoDigitado(acao, payload)
                        .catch(function() {})
                        .then(function() {
                            tratarPosEnvio(acao);
                            atualizarStatus('Operação enviada: ' + acao.replace(/_/g, ' '), 'ok');
                            carregarEstado();
                        });
                })
                .catch(function() {
                    atualizarStatus('Erro de comunicação com a conferência.', 'erro');
                });
        }

        function registrarToque(acao) {
            var agora = Date.now();
            if (!toques[acao] || (agora - toques[acao].ultimo) > 1300) {
                toques[acao] = { total: 0, ultimo: agora };
            }
            toques[acao].total++;
            toques[acao].ultimo = agora;
            if (toques[acao].total >= 3) {
                toques[acao] = { total: 0, ultimo: agora };
                enviarAcao(acao);
                return;
            }
            atualizarStatus('Confirme com 3 toques: ' + acao.replace(/_/g, ' ') + ' (' + toques[acao].total + '/3)', '');
        }

        function bindBotoes() {
            var botoes = document.querySelectorAll('[data-acao]');
            var ajustes = document.querySelectorAll('[data-ajuste]');
            var entradas = [inputLacreIiprRemoto, inputLacreCorreiosRemoto, inputEtiquetaCorreiosRemoto];
            var i;
            for (i = 0; i < botoes.length; i++) {
                botoes[i].addEventListener('click', function() {
                    var acao = this.getAttribute('data-acao') || '';
                    if (acao) registrarToque(acao);
                });
            }
            for (i = 0; i < ajustes.length; i++) {
                ajustes[i].addEventListener('click', function() {
                    var dados = String(this.getAttribute('data-ajuste') || '').split(':');
                    var tipo = dados[0] || '';
                    var delta = parseInt(dados[1], 10);
                    if (!tipo || isNaN(delta)) return;
                    ajustarSequencia(tipo, delta);
                });
            }
            for (i = 0; i < entradas.length; i++) {
                if (!entradas[i]) continue;
                entradas[i].addEventListener('input', persistirRascunho);
                entradas[i].addEventListener('blur', persistirRascunho);
            }
        }

        function carregarEstado() {
            fetch('conferencia_pacotes.php?ler_estado_remoto_ajax=1&canal=' + encodeURIComponent(canal), { cache: 'no-store' })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    var estado = data && data.estado ? data.estado : null;
                    var contexto = '-';
                    ultimoEstadoRemoto = estado;
                    if (!estado) {
                        estadoRegional.textContent = '-';
                        estadoAtualizado.textContent = '-';
                        estadoResumo.textContent = 'Aguardando posto ou regional ativos na conferência.';
                        return;
                    }
                    contexto = formatarRotuloContexto(estado);
                    estadoRegional.textContent = contexto;
                    estadoAtualizado.textContent = estado.atualizado_em || '-';
                    estadoResumo.textContent = estado.resumo || 'A prévia vai espelhar os lacres do contexto atual.';
                })
                .catch(function() {});
        }

        carregarRascunho();
        bindBotoes();
        carregarEstado();
        if (inputLacreIiprRemoto && !inputLacreIiprRemoto.value) {
            inputLacreIiprRemoto.focus();
        }
        window.setInterval(carregarEstado, 1500);
    })();
    </script>
</body>
</html>