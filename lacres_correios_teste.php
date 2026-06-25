<?php
// lacres_correios_teste.php — v0.9.25.17
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacres Correios (em teste) v0.9.25.17</title>
    <style>
        :root {
            --bg: #f6f3ef;
            --card: #ffffff;
            --ink: #2b2b2b;
            --accent: #00695c;
            --accent-2: #ffb300;
            --line: #e2ddd6;
            --shadow: 0 12px 28px rgba(0,0,0,0.12);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Trebuchet MS", "Verdana", sans-serif;
            background: linear-gradient(180deg, #f2efe9 0%, #f7f7f7 50%, #f0f4f8 100%);
            color: var(--ink);
            min-height: 100vh;
            padding: 24px;
        }
        .page {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .title {
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            font-size: 26px;
            color: #1b3a57;
        }
        .meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            color: #47515b;
        }
        .pill {
            background: #1b3a57;
            color: #fff;
            padding: 4px 10px;
            border-radius: 14px;
            font-weight: 700;
        }
        .toolbar {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--line);
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #32414f;
            margin-bottom: 6px;
        }
        .field input, .field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8d3cd;
            border-radius: 8px;
            background: #fff;
            font-weight: 700;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn {
            border: none;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 700;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: #263238; color: #fff; }
        .btn-ghost { background: transparent; border: 1px solid #bbb; color: #333; }
        .sheet {
            background: var(--card);
            border-radius: 16px;
            padding: 16px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }
        th {
            background: #f0efe9;
            font-weight: 800;
            color: #2b3b4a;
        }
        td input, td select {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #d9d4cf;
            border-radius: 6px;
            font-weight: 700;
        }
        .row-actions {
            display: flex;
            gap: 6px;
        }
        .btn-mini {
            padding: 6px 8px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }
        .btn-remove { background: #ef5350; color: #fff; }
        .fill-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 1px solid #bbb;
            background: #fff7e6;
            color: #8a5b00;
            cursor: grab;
            font-weight: 900;
            margin-left: 6px;
        }
        .hint {
            font-size: 12px;
            color: #6a6f74;
            margin-top: 8px;
        }
        .footer-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 720px) {
            .header { flex-direction: column; align-items: flex-start; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <div class="title">Lacres Correios (em teste)</div>
                <div class="meta">
                    <span class="pill">v0.9.25.17</span>
                    <span>Planilha simplificada de lacres e etiquetas</span>
                </div>
            </div>
            <div class="actions">
                <a class="btn btn-ghost" href="inicio.php">Voltar ao inicio</a>
            </div>
        </div>

        <div class="toolbar">
            <div class="field">
                <label>Datas do oficio (YYYY-MM-DD, separado por virgula)</label>
                <input type="text" id="datasOficio" placeholder="2026-03-03,2026-03-04">
            </div>
            <div class="field">
                <label>Modo do oficio</label>
                <select id="modoOficio">
                    <option value="">Sobrescrever (padrao)</option>
                    <option value="novo">Criar novo</option>
                </select>
            </div>
            <div class="field">
                <label>Imprimir apos salvar</label>
                <select id="imprimirApos">
                    <option value="1">Sim</option>
                    <option value="0">Nao</option>
                </select>
            </div>
            <div class="field actions">
                <button class="btn btn-secondary" type="button" id="btnAdicionarLinha">+ Adicionar linha</button>
                <button class="btn btn-ghost" type="button" id="btnLimpar">Limpar tudo</button>
            </div>
        </div>

        <div class="sheet">
            <table id="tabelaLacres">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Regional</th>
                        <th>Posto</th>
                        <th>Lacre IIPR</th>
                        <th>Lacre Correios</th>
                        <th>Etiqueta Correios</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbodyLacres"></tbody>
            </table>
            <div class="hint">Dica: clique no + ao lado de um lacre e arraste para baixo para auto incrementar.</div>
            <div class="footer-actions">
                <button class="btn btn-primary" type="button" id="btnGerarOficio">Gerar oficio Correios (PDF)</button>
            </div>
        </div>
    </div>

<script>
(function() {
    var tbody = document.getElementById('tbodyLacres');
    var btnAdd = document.getElementById('btnAdicionarLinha');
    var btnClear = document.getElementById('btnLimpar');
    var btnGerar = document.getElementById('btnGerarOficio');

    var fillState = null;

    function novaLinha(valores) {
        var tr = document.createElement('tr');
        var idx = tbody.children.length;
        tr.setAttribute('data-index', idx);
        tr.innerHTML =
            '<td>' +
                '<select class="grupo">' +
                    '<option value="REGIONAIS">REGIONAIS</option>' +
                    '<option value="CAPITAL">CAPITAL</option>' +
                    '<option value="CENTRAL">CENTRAL</option>' +
                    '<option value="MANUAL">MANUAL</option>' +
                '</select>' +
            '</td>' +
            '<td><input type="text" class="regional" placeholder="000"></td>' +
            '<td><input type="text" class="posto" placeholder="000"></td>' +
            '<td><div style="display:flex; align-items:center; gap:6px;">' +
                '<input type="text" class="lacre-iipr" placeholder="000000">' +
                '<span class="fill-handle" data-col="lacre-iipr">+</span>' +
            '</div></td>' +
            '<td><div style="display:flex; align-items:center; gap:6px;">' +
                '<input type="text" class="lacre-correios" placeholder="000000">' +
                '<span class="fill-handle" data-col="lacre-correios">+</span>' +
            '</div></td>' +
            '<td><div style="display:flex; align-items:center; gap:6px;">' +
                '<input type="text" class="etiqueta" placeholder="35 digitos">' +
                '<span class="fill-handle" data-col="etiqueta">+</span>' +
            '</div></td>' +
            '<td class="row-actions">' +
                '<button type="button" class="btn-mini btn-remove">Remover</button>' +
            '</td>';
        tbody.appendChild(tr);

        if (valores) {
            tr.querySelector('.grupo').value = valores.grupo || 'REGIONAIS';
            tr.querySelector('.regional').value = valores.regional || '';
            tr.querySelector('.posto').value = valores.posto || '';
            tr.querySelector('.lacre-iipr').value = valores.lacre_iipr || '';
            tr.querySelector('.lacre-correios').value = valores.lacre_correios || '';
            tr.querySelector('.etiqueta').value = valores.etiqueta_correios || '';
        }
    }

    function limparTudo() {
        tbody.innerHTML = '';
        novaLinha();
    }

    function lerValorNumerico(val) {
        var n = String(val || '').replace(/\D+/g, '');
        if (!n) return null;
        return parseInt(n, 10);
    }

    function preencherSequencia(col, startRow, endRow) {
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var startIdx = Math.min(startRow, endRow);
        var endIdx = Math.max(startRow, endRow);
        var startInput = rows[startRow].querySelector('.' + col);
        if (!startInput) return;
        var startVal = lerValorNumerico(startInput.value);
        if (startVal === null) return;
        var step = 1;
        for (var i = startIdx; i <= endIdx; i++) {
            var input = rows[i].querySelector('.' + col);
            if (!input) continue;
            var nextVal = startVal + (i - startRow) * step;
            input.value = String(nextVal);
        }
    }

    function montarSnapshot() {
        var linhas = [];
        var rows = tbody.querySelectorAll('tr');
        for (var i = 0; i < rows.length; i++) {
            var grupo = rows[i].querySelector('.grupo').value || 'REGIONAIS';
            var regional = rows[i].querySelector('.regional').value.trim();
            var posto = rows[i].querySelector('.posto').value.trim();
            var lacreI = rows[i].querySelector('.lacre-iipr').value.trim();
            var lacreC = rows[i].querySelector('.lacre-correios').value.trim();
            var etiqueta = rows[i].querySelector('.etiqueta').value.trim();
            if (!posto) continue;
            linhas.push({
                posto: posto,
                grupo: grupo,
                regional: regional || '0',
                lacre_iipr: lacreI,
                lacre_correios: lacreC,
                etiqueta_correios: etiqueta
            });
        }
        return linhas;
    }

    btnAdd.addEventListener('click', function() {
        novaLinha();
    });

    btnClear.addEventListener('click', function() {
        if (confirm('Limpar todas as linhas?')) {
            limparTudo();
        }
    });

    tbody.addEventListener('click', function(e) {
        var target = e.target;
        if (!target) return;
        if (target.classList.contains('btn-remove')) {
            var tr = target.closest('tr');
            if (tr) tr.remove();
        }
    });

    tbody.addEventListener('mousedown', function(e) {
        var handle = e.target;
        if (!handle || !handle.classList.contains('fill-handle')) return;
        var tr = handle.closest('tr');
        if (!tr) return;
        fillState = {
            col: handle.getAttribute('data-col'),
            startRow: parseInt(tr.getAttribute('data-index'), 10)
        };
        document.body.style.cursor = 'grabbing';
    });

    tbody.addEventListener('mouseover', function(e) {
        if (!fillState) return;
        var tr = e.target.closest('tr');
        if (!tr) return;
        var idx = parseInt(tr.getAttribute('data-index'), 10);
        if (!isNaN(idx)) {
            preencherSequencia(fillState.col, fillState.startRow, idx);
        }
    });

    document.addEventListener('mouseup', function() {
        if (fillState) {
            fillState = null;
            document.body.style.cursor = '';
        }
    });

    btnGerar.addEventListener('click', function() {
        var datas = document.getElementById('datasOficio').value.trim();
        if (!datas) {
            alert('Informe as datas do oficio.');
            return;
        }
        var snapshot = montarSnapshot();
        if (!snapshot.length) {
            alert('Adicione ao menos uma linha com posto.');
            return;
        }
        var form = document.createElement('form');
        form.method = 'post';
        form.action = 'lacres_novo.php';
        form.target = '_blank';

        var acao = document.createElement('input');
        acao.type = 'hidden';
        acao.name = 'acao';
        acao.value = 'salvar_oficio_correios';
        form.appendChild(acao);

        var datasInput = document.createElement('input');
        datasInput.type = 'hidden';
        datasInput.name = 'correios_datas';
        datasInput.value = datas;
        form.appendChild(datasInput);

        var modo = document.createElement('input');
        modo.type = 'hidden';
        modo.name = 'modo_oficio';
        modo.value = document.getElementById('modoOficio').value;
        form.appendChild(modo);

        var imprimir = document.createElement('input');
        imprimir.type = 'hidden';
        imprimir.name = 'imprimir_apos_salvar';
        imprimir.value = document.getElementById('imprimirApos').value;
        form.appendChild(imprimir);

        var snap = document.createElement('input');
        snap.type = 'hidden';
        snap.name = 'snapshot_oficio';
        snap.value = JSON.stringify(snapshot);
        form.appendChild(snap);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    limparTudo();
})();
</script>
</body>
</html>
