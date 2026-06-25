<?php
/* restricoes_posto.php — v1.0.0
 * Gerencia restrições de postos: Segurar, Adiantar, Fechado, tipos personalizados.
 * Tabela: ciPostosRestricoes
 */
header('Cache-Control: no-cache, no-store, must-revalidate');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$dbOk = false;
$pdo  = null;

try {
    $pdo = new PDO("mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
                   (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE,         PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;

    // Schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosRestricoes (
        id          INT NOT NULL AUTO_INCREMENT,
        posto       VARCHAR(10) NOT NULL,
        nome        VARCHAR(120) DEFAULT NULL,
        tipo        VARCHAR(60)  NOT NULL DEFAULT 'segurar',
        motivo      VARCHAR(255) DEFAULT NULL,
        responsavel VARCHAR(120) DEFAULT NULL,
        ativo       TINYINT(1)   NOT NULL DEFAULT 1,
        criado      DATETIME     NOT NULL,
        atualizado  DATETIME     DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_posto (posto)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosRestricoesHistorico (
        id          INT NOT NULL AUTO_INCREMENT,
        posto       VARCHAR(10) NOT NULL,
        acao        VARCHAR(20) NOT NULL,
        tipo        VARCHAR(60) DEFAULT NULL,
        motivo      VARCHAR(255) DEFAULT NULL,
        responsavel VARCHAR(120) NOT NULL,
        criado      DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_posto  (posto),
        KEY idx_criado (criado)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciRestricoesTipos (
        id      INT NOT NULL AUTO_INCREMENT,
        label   VARCHAR(60) NOT NULL,
        cor     VARCHAR(20) DEFAULT '#607d8b',
        PRIMARY KEY (id),
        UNIQUE KEY uk_label (label)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    // Tipos padrão
    $pdo->exec("INSERT IGNORE INTO ciRestricoesTipos (label, cor) VALUES
        ('segurar',  '#e53935'),
        ('adiantar', '#1e88e5'),
        ('fechado',  '#37474f')");

    // ===== AJAX =====
    if (isset($_POST['ajax']) && $_POST['ajax']) {
        header('Content-Type: application/json; charset=utf-8');

        // Listar restrições ativas
        if ($_POST['ajax'] === 'listar') {
            $rows = $pdo->query("SELECT r.*, t.cor FROM ciPostosRestricoes r
                LEFT JOIN ciRestricoesTipos t ON t.label = r.tipo
                WHERE r.ativo = 1 ORDER BY r.posto ASC")->fetchAll();
            die(json_encode(array('ok' => true, 'lista' => $rows)));
        }

        // Listar tipos disponíveis
        if ($_POST['ajax'] === 'listar_tipos') {
            $tipos = $pdo->query("SELECT label, cor FROM ciRestricoesTipos ORDER BY id ASC")->fetchAll();
            die(json_encode(array('ok' => true, 'tipos' => $tipos)));
        }

        // Criar tipo personalizado
        if ($_POST['ajax'] === 'criar_tipo') {
            $label = trim((string)(isset($_POST['label']) ? $_POST['label'] : ''));
            $cor   = trim((string)(isset($_POST['cor'])   ? $_POST['cor']   : '#607d8b'));
            if ($label === '') die(json_encode(array('ok' => false, 'erro' => 'Nome obrigatorio')));
            $label = strtolower(preg_replace('/[^a-zA-Z0-9_\- ]/u', '', $label));
            if ($label === '') die(json_encode(array('ok' => false, 'erro' => 'Nome invalido')));
            $st = $pdo->prepare("INSERT IGNORE INTO ciRestricoesTipos (label, cor) VALUES (?,?)");
            $st->execute(array($label, $cor));
            die(json_encode(array('ok' => true)));
        }

        // Excluir tipo personalizado
        if ($_POST['ajax'] === 'excluir_tipo') {
            $label = trim((string)(isset($_POST['label']) ? $_POST['label'] : ''));
            $fixed = array('segurar','adiantar','fechado');
            if (in_array($label, $fixed)) die(json_encode(array('ok' => false, 'erro' => 'Tipo fixo nao pode ser removido')));
            $st = $pdo->prepare("DELETE FROM ciRestricoesTipos WHERE label = ?");
            $st->execute(array($label));
            die(json_encode(array('ok' => true)));
        }

        // Salvar (inserir ou atualizar) restrição
        if ($_POST['ajax'] === 'salvar') {
            $posto  = trim((string)(isset($_POST['posto'])       ? $_POST['posto']       : ''));
            $tipo   = trim((string)(isset($_POST['tipo'])        ? $_POST['tipo']        : 'segurar'));
            $motivo = trim((string)(isset($_POST['motivo'])      ? $_POST['motivo']      : ''));
            $resp   = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
            $nome   = trim((string)(isset($_POST['nome'])        ? $_POST['nome']        : ''));
            if ($posto === '') die(json_encode(array('ok' => false, 'erro' => 'Posto obrigatorio')));
            if ($resp  === '') die(json_encode(array('ok' => false, 'erro' => 'Responsavel obrigatorio')));
            $posto = str_pad(preg_replace('/\D/', '', $posto), 3, '0', STR_PAD_LEFT);
            $stCheck = $pdo->prepare("SELECT id FROM ciPostosRestricoes WHERE posto = ?");
            $stCheck->execute(array($posto));
            if ($stCheck->fetch()) {
                $pdo->prepare("UPDATE ciPostosRestricoes SET tipo=?, motivo=?, responsavel=?, ativo=1, atualizado=NOW() WHERE posto=?")
                    ->execute(array($tipo, $motivo, $resp, $posto));
            } else {
                $pdo->prepare("INSERT INTO ciPostosRestricoes (posto, nome, tipo, motivo, responsavel, ativo, criado) VALUES (?,?,?,?,?,1,NOW())")
                    ->execute(array($posto, $nome, $tipo, $motivo, $resp));
            }
            $pdo->prepare("INSERT INTO ciPostosRestricoesHistorico (posto, acao, tipo, motivo, responsavel, criado) VALUES (?,?,?,?,?,NOW())")
                ->execute(array($posto, 'RESTRICAO', $tipo, $motivo, $resp));
            die(json_encode(array('ok' => true)));
        }

        // Remover restrição
        if ($_POST['ajax'] === 'remover') {
            $posto = trim((string)(isset($_POST['posto'])       ? $_POST['posto']       : ''));
            $resp  = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
            if ($posto === '') die(json_encode(array('ok' => false, 'erro' => 'Posto obrigatorio')));
            if ($resp  === '') die(json_encode(array('ok' => false, 'erro' => 'Responsavel obrigatorio')));
            $pdo->prepare("UPDATE ciPostosRestricoes SET ativo=0, atualizado=NOW() WHERE posto=?")
                ->execute(array($posto));
            $pdo->prepare("INSERT INTO ciPostosRestricoesHistorico (posto, acao, tipo, motivo, responsavel, criado) VALUES (?,?,NULL,NULL,?,NOW())")
                ->execute(array($posto, 'REMOCAO', $resp));
            die(json_encode(array('ok' => true)));
        }

        // Remover todas
        if ($_POST['ajax'] === 'remover_todas') {
            $resp = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
            if ($resp === '') die(json_encode(array('ok' => false, 'erro' => 'Responsavel obrigatorio')));
            $ativos = $pdo->query("SELECT posto FROM ciPostosRestricoes WHERE ativo=1")->fetchAll();
            foreach ($ativos as $r) {
                $pdo->prepare("INSERT INTO ciPostosRestricoesHistorico (posto, acao, tipo, motivo, responsavel, criado) VALUES (?,?,NULL,NULL,?,NOW())")
                    ->execute(array($r['posto'], 'REMOCAO', $resp));
            }
            $pdo->exec("UPDATE ciPostosRestricoes SET ativo=0 WHERE ativo=1");
            die(json_encode(array('ok' => true)));
        }

        // Listar postos por regional (para seleção em grupo)
        if ($_POST['ajax'] === 'grupos') {
            $rows = $pdo->query("SELECT LPAD(posto,3,'0') AS posto, CAST(regional AS UNSIGNED) AS regional, nome,
                LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega
                FROM ciRegionais ORDER BY regional, posto")->fetchAll();
            $grupos = array();
            foreach ($rows as $r) {
                $reg   = (int)$r['regional'];
                $isPT  = (strpos($r['entrega'], 'poupa') !== false || strpos($r['entrega'], 'tempo') !== false);
                $label = $isPT ? 'PT' : ($reg === 0 ? 'Capital' : ($reg === 999 ? 'Central' : 'R' . str_pad((string)$reg, 3, '0', STR_PAD_LEFT)));
                if (!isset($grupos[$label])) $grupos[$label] = array();
                $grupos[$label][] = array('posto' => $r['posto'], 'nome' => $r['nome']);
            }
            die(json_encode(array('ok' => true, 'grupos' => $grupos)));
        }

        die(json_encode(array('ok' => false, 'erro' => 'Acao desconhecida')));
    }

} catch (PDOException $ex) {
    if (isset($_POST['ajax']) && $_POST['ajax']) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(array('ok' => false, 'erro' => 'Erro de conexao com o banco')));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restricoes de Postos</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Trebuchet MS","Segoe UI",Arial,sans-serif;background:#f0f4f8;padding:20px;padding-top:76px;}
.topo-fixo{position:fixed;top:0;left:0;right:0;background:linear-gradient(135deg,#e65100 0%,#ff6d00 100%);color:#fff;padding:12px 20px;z-index:1000;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 10px rgba(0,0,0,.3);}
.topo-fixo h1{font-size:18px;font-weight:700;}
.versao{background:rgba(255,255,255,.25);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.btn-voltar{color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:6px 14px;border-radius:6px;background:rgba(255,255,255,.18);margin-right:10px;display:inline-flex;align-items:center;gap:4px;}
.btn-voltar:hover{background:rgba(255,255,255,.35);}
.area{max-width:960px;margin:0 auto;}
.painel{background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.1);}
.painel h3{color:#e65100;margin-bottom:14px;font-size:16px;border-bottom:2px solid #ffe0b2;padding-bottom:8px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.campo label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px;}
.campo input,.campo select,.campo textarea{width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:13px;background:#fff;color:#222;}
.campo textarea{min-height:60px;resize:vertical;}
.btn{border:none;border-radius:6px;padding:9px 18px;cursor:pointer;font-size:13px;font-weight:600;}
.btn-salvar{background:#e65100;color:#fff;}
.btn-salvar:hover{background:#bf360c;}
.btn-remover{background:#b71c1c;color:#fff;}
.btn-remover:hover{background:#7f0000;}
.btn-remover-todas{background:#546e7a;color:#fff;}
.btn-remover-todas:hover{background:#263238;}
.btn-neutro{background:#e0e0e0;color:#333;}
.btn-neutro:hover{background:#bdbdbd;}
.btn-tipos{background:#1565c0;color:#fff;}
.btn-tipos:hover{background:#0d47a1;}
.acoes{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;}
.tabela-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#fff3e0;color:#e65100;padding:9px 10px;text-align:left;font-weight:700;}
td{padding:8px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
tr:hover td{background:#fafafa;}
.badge{display:inline-block;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;color:#fff;}
.msg-vazia{color:#888;font-size:13px;padding:16px 0;text-align:center;}
.toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.25);transition:opacity .4s;}
.toast.ok{background:#2e7d32;}
.toast.err{background:#b71c1c;}
.toast.hide{opacity:0;}
.row-grupos{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;}
.chip-grupo{padding:5px 12px;border-radius:20px;background:#fff3e0;border:1px solid #ffcc80;font-size:12px;cursor:pointer;user-select:none;}
.chip-grupo:hover{background:#ffe0b2;}
.modal-fundo{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2000;display:none;align-items:center;justify-content:center;}
.modal-fundo.aberto{display:flex;}
.modal-caixa{background:#fff;border-radius:12px;padding:24px;max-width:420px;width:95%;box-shadow:0 8px 32px rgba(0,0,0,.25);}
.modal-caixa h4{margin-bottom:14px;color:#e65100;}
.lista-tipos{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;}
.tipo-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:20px;font-size:12px;font-weight:700;color:#fff;}
.tipo-chip .remover-tipo{cursor:pointer;font-size:14px;line-height:1;opacity:.8;}
.tipo-chip .remover-tipo:hover{opacity:1;}
.novo-tipo-row{display:flex;gap:8px;align-items:flex-end;}
.novo-tipo-row input{flex:1;padding:7px 10px;border:1px solid #ccc;border-radius:6px;font-size:13px;}
.novo-tipo-row input[type=color]{width:44px;padding:2px;cursor:pointer;}
@media(max-width:640px){.grid-2{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="topo-fixo">
    <div style="display:flex;align-items:center;">
        <a href="inicio.php" class="btn-voltar">&#8592; Inicio</a>
        <h1>&#9888; Restricoes de Postos</h1>
    </div>
    <span class="versao">v2.0.3</span>
</div>

<div class="area">

    <!-- Painel de cadastro -->
    <div class="painel">
        <h3>Adicionar / Atualizar Restricao</h3>
        <div class="grid-2">
            <div class="campo">
                <label>Posto (codigo)</label>
                <input type="text" id="inp-posto" placeholder="Ex.: 001, 809" maxlength="10">
            </div>
            <div class="campo">
                <label>Tipo de restricao</label>
                <select id="inp-tipo">
                    <option value="segurar">Segurar — nao enviar</option>
                    <option value="adiantar">Adiantar — prioridade</option>
                    <option value="fechado">Fechado — posto encerrado</option>
                </select>
            </div>
            <div class="campo">
                <label>Motivo / Observacao</label>
                <input type="text" id="inp-motivo" placeholder="Ex.: aguardando autorizacao" maxlength="255">
            </div>
            <div class="campo">
                <label>Responsavel</label>
                <input type="text" id="inp-responsavel" placeholder="Seu nome" maxlength="120">
            </div>
        </div>
        <div class="acoes">
            <button class="btn btn-salvar" onclick="salvarRestricao()">Salvar Restricao</button>
            <button class="btn btn-tipos" onclick="abrirModalTipos()">&#9881; Gerenciar Tipos</button>
            <button class="btn btn-remover-todas" onclick="removerTodas()">&#10005; Remover Todas</button>
        </div>
    </div>

    <!-- Seleção por grupo -->
    <div class="painel">
        <h3>Selecionar por Regional / Grupo</h3>
        <p style="font-size:12px;color:#888;margin-bottom:10px;">Clique num grupo para preencher o campo Posto acima com todos os postos daquele grupo.</p>
        <div id="row-grupos" class="row-grupos"><span style="color:#aaa;font-size:12px;">Carregando...</span></div>
    </div>

    <!-- Lista de restrições ativas -->
    <div class="painel">
        <h3>Restricoes Ativas</h3>
        <div class="tabela-wrap">
            <table id="tabela-restricoes">
                <thead><tr>
                    <th>Posto</th><th>Tipo</th><th>Motivo</th><th>Responsavel</th><th>Desde</th><th>Acao</th>
                </tr></thead>
                <tbody id="tbody-restricoes">
                    <tr><td colspan="6" class="msg-vazia">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal de tipos -->
<div id="modal-tipos" class="modal-fundo">
    <div class="modal-caixa">
        <h4>Tipos de Restricao</h4>
        <div id="lista-tipos-chips" class="lista-tipos"></div>
        <div class="novo-tipo-row">
            <input type="text" id="inp-novo-tipo-label" placeholder="Nome do tipo (ex.: aguardar)" maxlength="50">
            <input type="color" id="inp-novo-tipo-cor" value="#607d8b" title="Cor">
            <button class="btn btn-salvar" onclick="criarTipo()">+ Criar</button>
        </div>
        <div style="margin-top:14px;text-align:right;">
            <button class="btn btn-neutro" onclick="fecharModalTipos()">Fechar</button>
        </div>
    </div>
</div>

<div id="toast" class="toast hide"></div>

<script type="text/javascript">
var tiposCache = [];
var responsavelSalvo = '';
try { responsavelSalvo = localStorage.getItem('restricoes_responsavel') || ''; } catch(e) {}
if (responsavelSalvo) document.getElementById('inp-responsavel').value = responsavelSalvo;

function api(dados, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'restricoes_posto.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        try { callback(JSON.parse(xhr.responseText)); }
        catch(e) { callback({ok: false, erro: 'Resposta invalida'}); }
    };
    var pares = [];
    for (var k in dados) {
        if (Object.prototype.hasOwnProperty.call(dados, k)) {
            pares.push(encodeURIComponent(k) + '=' + encodeURIComponent(dados[k]));
        }
    }
    xhr.send(pares.join('&'));
}

function toast(msg, tipo) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast ' + (tipo === 'err' ? 'err' : 'ok');
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.className = 'toast hide'; }, 3000);
}

function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatarData(s) {
    if (!s) return '-';
    var d = new Date(s);
    if (isNaN(d.getTime())) return s;
    return d.toLocaleString('pt-BR');
}

function corDoTipo(label) {
    for (var i = 0; i < tiposCache.length; i++) {
        if (tiposCache[i].label === label) return tiposCache[i].cor || '#607d8b';
    }
    var cores = {segurar:'#e53935', adiantar:'#1e88e5', fechado:'#37474f'};
    return cores[label] || '#607d8b';
}

function carregarTipos(callback) {
    api({ajax: 'listar_tipos'}, function(r) {
        if (r.ok) {
            tiposCache = r.tipos || [];
            atualizarSelectTipos();
            if (callback) callback();
        }
    });
}

function atualizarSelectTipos() {
    var sel = document.getElementById('inp-tipo');
    var val = sel.value;
    sel.innerHTML = '';
    for (var i = 0; i < tiposCache.length; i++) {
        var opt = document.createElement('option');
        opt.value = tiposCache[i].label;
        var labels = {segurar:'Segurar — nao enviar', adiantar:'Adiantar — prioridade', fechado:'Fechado — posto encerrado'};
        opt.textContent = labels[tiposCache[i].label] || tiposCache[i].label;
        sel.appendChild(opt);
    }
    sel.value = val || 'segurar';
}

function carregarRestricoes() {
    api({ajax: 'listar'}, function(r) {
        var tbody = document.getElementById('tbody-restricoes');
        if (!r.ok || !r.lista || r.lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="msg-vazia">Nenhuma restricao ativa.</td></tr>';
            return;
        }
        var html = '';
        for (var i = 0; i < r.lista.length; i++) {
            var x = r.lista[i];
            var cor = x.cor || corDoTipo(x.tipo);
            html += '<tr>' +
                '<td><strong>' + esc(x.posto) + '</strong></td>' +
                '<td><span class="badge" style="background:' + esc(cor) + '">' + esc(x.tipo) + '</span></td>' +
                '<td>' + esc(x.motivo || '-') + '</td>' +
                '<td>' + esc(x.responsavel || '-') + '</td>' +
                '<td style="white-space:nowrap">' + esc(formatarData(x.criado)) + '</td>' +
                '<td><button class="btn btn-remover" style="padding:4px 10px;font-size:11px;" onclick="removerRestricao(\'' + esc(x.posto) + '\')">Remover</button></td>' +
                '</tr>';
        }
        tbody.innerHTML = html;
    });
}

function carregarGrupos() {
    api({ajax: 'grupos'}, function(r) {
        var div = document.getElementById('row-grupos');
        if (!r.ok || !r.grupos) { div.innerHTML = '<span style="color:#aaa;font-size:12px;">Nao disponivel</span>'; return; }
        var html = '';
        for (var g in r.grupos) {
            if (Object.prototype.hasOwnProperty.call(r.grupos, g)) {
                html += '<span class="chip-grupo" data-grupo="' + esc(g) + '" onclick="selecionarGrupo(this)">' + esc(g) + ' (' + r.grupos[g].length + ')</span>';
            }
        }
        div.innerHTML = html || '<span style="color:#aaa;">Nenhum grupo</span>';
        window._gruposData = r.grupos;
    });
}

function selecionarGrupo(el) {
    var g = el.getAttribute('data-grupo');
    if (!window._gruposData || !window._gruposData[g]) return;
    var postos = window._gruposData[g];
    var lista = [];
    for (var i = 0; i < postos.length; i++) {
        lista.push(postos[i].posto || postos[i]);
    }
    document.getElementById('inp-posto').value = lista.join(', ');
    toast('Grupo ' + g + ' selecionado — ' + lista.length + ' postos', 'ok');
}

function salvarRestricao() {
    var postoRaw = document.getElementById('inp-posto').value.trim();
    var tipo     = document.getElementById('inp-tipo').value;
    var motivo   = document.getElementById('inp-motivo').value.trim();
    var resp     = document.getElementById('inp-responsavel').value.trim();
    if (!postoRaw) { toast('Informe o posto', 'err'); return; }
    if (!resp)     { toast('Informe o responsavel', 'err'); return; }
    try { localStorage.setItem('restricoes_responsavel', resp); } catch(e) {}
    // Suporte a múltiplos postos separados por vírgula/espaço
    var postos = postoRaw.split(/[\s,;]+/);
    var ok = 0; var total = 0;
    function salvarProximo() {
        if (ok >= postos.length) {
            carregarRestricoes();
            toast('Restricao salva: ' + total + ' posto(s)', 'ok');
            document.getElementById('inp-posto').value = '';
            document.getElementById('inp-motivo').value = '';
            return;
        }
        var p = postos[ok]; ok++;
        p = p.replace(/\D/g, '');
        if (!p) { salvarProximo(); return; }
        total++;
        api({ajax: 'salvar', posto: p, tipo: tipo, motivo: motivo, responsavel: resp}, function(r) {
            if (!r.ok) toast(r.erro || 'Erro', 'err');
            salvarProximo();
        });
    }
    salvarProximo();
}

function removerRestricao(posto) {
    var resp = document.getElementById('inp-responsavel').value.trim();
    if (!resp) { toast('Informe o responsavel antes de remover', 'err'); return; }
    if (!confirm('Remover restricao do posto ' + posto + '?')) return;
    api({ajax: 'remover', posto: posto, responsavel: resp}, function(r) {
        if (r.ok) { carregarRestricoes(); toast('Restricao removida', 'ok'); }
        else toast(r.erro || 'Erro', 'err');
    });
}

function removerTodas() {
    var resp = document.getElementById('inp-responsavel').value.trim();
    if (!resp) { toast('Informe o responsavel antes de remover', 'err'); return; }
    if (!confirm('Remover TODAS as restricoes ativas?')) return;
    api({ajax: 'remover_todas', responsavel: resp}, function(r) {
        if (r.ok) { carregarRestricoes(); toast('Todas as restricoes removidas', 'ok'); }
        else toast(r.erro || 'Erro', 'err');
    });
}

function abrirModalTipos() {
    carregarTiposModal();
    document.getElementById('modal-tipos').className = 'modal-fundo aberto';
}
function fecharModalTipos() {
    document.getElementById('modal-tipos').className = 'modal-fundo';
}
document.getElementById('modal-tipos').onclick = function(e) {
    if (e.target === this) fecharModalTipos();
};

function carregarTiposModal() {
    api({ajax: 'listar_tipos'}, function(r) {
        tiposCache = r.tipos || [];
        atualizarSelectTipos();
        var fixed = {segurar:1, adiantar:1, fechado:1};
        var html = '';
        for (var i = 0; i < tiposCache.length; i++) {
            var t = tiposCache[i];
            var btnRem = fixed[t.label] ? '' : '<span class="remover-tipo" onclick="excluirTipo(\'' + esc(t.label) + '\')">&#215;</span>';
            html += '<span class="tipo-chip" style="background:' + esc(t.cor) + '">' + esc(t.label) + btnRem + '</span>';
        }
        document.getElementById('lista-tipos-chips').innerHTML = html || '<span style="color:#aaa">Nenhum tipo</span>';
    });
}

function criarTipo() {
    var label = document.getElementById('inp-novo-tipo-label').value.trim();
    var cor   = document.getElementById('inp-novo-tipo-cor').value;
    if (!label) { toast('Informe o nome do tipo', 'err'); return; }
    api({ajax: 'criar_tipo', label: label, cor: cor}, function(r) {
        if (r.ok) { document.getElementById('inp-novo-tipo-label').value = ''; carregarTiposModal(); }
        else toast(r.erro || 'Erro', 'err');
    });
}

function excluirTipo(label) {
    if (!confirm('Excluir o tipo "' + label + '"?')) return;
    api({ajax: 'excluir_tipo', label: label}, function(r) {
        if (r.ok) carregarTiposModal();
        else toast(r.erro || 'Tipo fixo nao pode ser removido', 'err');
    });
}

// Inicializar
carregarTipos(function() {
    carregarRestricoes();
    carregarGrupos();
});
</script>
<?php include __DIR__ . '/includes/util_botoes_fixos.php'; ?>
</body>
</html>
