<?php
header('Cache-Control: no-cache, no-store, must-revalidate');

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$dbOk = false;

try {
    $pdo = new PDO(
        "mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
        (getenv('DB_USER') ?: 'controle_mat'),
        (getenv('DB_PASS') ?: '375256')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosBloqueados (
        id INT NOT NULL AUTO_INCREMENT,
        posto VARCHAR(10) NOT NULL,
        nome VARCHAR(120) DEFAULT NULL,
        motivo VARCHAR(255) DEFAULT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado DATETIME NOT NULL,
        atualizado DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY posto (posto)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $cols = $pdo->query("SHOW COLUMNS FROM ciPostosBloqueados LIKE 'motivo'")->fetchAll();
    if (count($cols) === 0) {
        $pdo->exec("ALTER TABLE ciPostosBloqueados ADD COLUMN motivo VARCHAR(255) DEFAULT NULL AFTER nome");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosBloqueadosHistorico (
        id INT NOT NULL AUTO_INCREMENT,
        posto VARCHAR(10) NOT NULL,
        acao VARCHAR(20) NOT NULL,
        motivo VARCHAR(255) DEFAULT NULL,
        responsavel VARCHAR(120) NOT NULL,
        criado DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_posto (posto),
        KEY idx_criado (criado)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    if (isset($_POST['ajax_listar_bloqueados'])) {
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT id, posto, nome, motivo, criado FROM ciPostosBloqueados WHERE ativo = 1 ORDER BY posto ASC");
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        die(json_encode(array('success' => true, 'bloqueados' => $lista)));
    }

    if (isset($_POST['ajax_bloquear_postos'])) {
        header('Content-Type: application/json');
        $postos_raw = isset($_POST['postos']) ? trim($_POST['postos']) : '';
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';

        if ($motivo === '') {
            die(json_encode(array('success' => false, 'erro' => 'Motivo obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }

        $postos_list = preg_split('/[\s,;]+/', $postos_raw);
        $ok = 0;
        $postos_afetados = array();
        foreach ($postos_list as $p) {
            $p = preg_replace('/\D/', '', trim($p));
            if ($p === '') continue;
            $p_pad = str_pad($p, 3, '0', STR_PAD_LEFT);

            $stmtCheck = $pdo->prepare("SELECT id FROM ciPostosBloqueados WHERE posto = ?");
            $stmtCheck->execute(array($p_pad));
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $stmtUp = $pdo->prepare("UPDATE ciPostosBloqueados SET motivo = ?, ativo = 1, atualizado = NOW() WHERE posto = ?");
                $stmtUp->execute(array($motivo, $p_pad));
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO ciPostosBloqueados (posto, nome, motivo, ativo, criado) VALUES (?, ?, ?, 1, NOW())");
                $stmtIns->execute(array($p_pad, '', $motivo));
            }
            $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'BLOQUEIO', ?, ?, NOW())");
            $stmtHist->execute(array($p_pad, $motivo, $responsavel));
            $postos_afetados[] = $p_pad;
            $ok++;
        }

        die(json_encode(array('success' => true, 'bloqueados' => $ok, 'postos' => $postos_afetados)));
    }

    if (isset($_POST['ajax_bloquear_grupo'])) {
        header('Content-Type: application/json');
        $postos_json = isset($_POST['postos']) ? trim($_POST['postos']) : '';
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        $postos_arr = json_decode($postos_json, true);

        if ($motivo === '') {
            die(json_encode(array('success' => false, 'erro' => 'Motivo obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }

        if (!is_array($postos_arr) || count($postos_arr) === 0) {
            die(json_encode(array('success' => false, 'erro' => 'Nenhum posto informado')));
        }

        $ok = 0;
        $postos_afetados = array();
        foreach ($postos_arr as $item) {
            $p = isset($item['posto']) ? str_pad(trim($item['posto']), 3, '0', STR_PAD_LEFT) : '';
            if ($p === '' || $p === '000') continue;

            $stmtCheck = $pdo->prepare("SELECT id FROM ciPostosBloqueados WHERE posto = ?");
            $stmtCheck->execute(array($p));
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $stmtUp = $pdo->prepare("UPDATE ciPostosBloqueados SET motivo = ?, ativo = 1, atualizado = NOW() WHERE posto = ?");
                $stmtUp->execute(array($motivo, $p));
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO ciPostosBloqueados (posto, nome, motivo, ativo, criado) VALUES (?, ?, ?, 1, NOW())");
                $stmtIns->execute(array($p, '', $motivo));
            }
            $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'BLOQUEIO', ?, ?, NOW())");
            $stmtHist->execute(array($p, $motivo, $responsavel));
            $postos_afetados[] = $p;
            $ok++;
        }

        die(json_encode(array('success' => true, 'bloqueados' => $ok, 'postos' => $postos_afetados)));
    }

    if (isset($_POST['ajax_desbloquear_postos'])) {
        header('Content-Type: application/json');
        $postos_json = isset($_POST['postos']) ? trim($_POST['postos']) : '';
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        $postos_arr = json_decode($postos_json, true);

        if ($motivo === '') {
            die(json_encode(array('success' => false, 'erro' => 'Motivo obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }

        if (!is_array($postos_arr) || count($postos_arr) === 0) {
            die(json_encode(array('success' => false, 'erro' => 'Nenhum posto informado')));
        }

        $ok = 0;
        $postos_afetados = array();
        foreach ($postos_arr as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $stmtDel = $pdo->prepare("DELETE FROM ciPostosBloqueados WHERE posto = ?");
            $stmtDel->execute(array($p));
            $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'DESBLOQUEIO', ?, ?, NOW())");
            $stmtHist->execute(array($p, $motivo, $responsavel));
            $postos_afetados[] = $p;
            $ok++;
        }

        die(json_encode(array('success' => true, 'desbloqueados' => $ok, 'postos' => $postos_afetados)));
    }

    if (isset($_POST['ajax_desbloquear_posto'])) {
        header('Content-Type: application/json');
        $posto = isset($_POST['posto']) ? trim($_POST['posto']) : '';
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        if ($posto === '') {
            die(json_encode(array('success' => false, 'erro' => 'Posto obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }
        $stmtDel = $pdo->prepare("DELETE FROM ciPostosBloqueados WHERE posto = ?");
        $stmtDel->execute(array($posto));
        $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'DESBLOQUEIO', ?, ?, NOW())");
        $stmtHist->execute(array($posto, $motivo, $responsavel));
        die(json_encode(array('success' => true)));
    }

    if (isset($_POST['ajax_desbloquear_todos'])) {
        header('Content-Type: application/json');
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        if ($motivo === '') {
            die(json_encode(array('success' => false, 'erro' => 'Motivo obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }
        $stmtAll = $pdo->query("SELECT posto FROM ciPostosBloqueados WHERE ativo = 1");
        $postosAll = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        $pdo->exec("DELETE FROM ciPostosBloqueados");
        foreach ($postosAll as $p) {
            $postoVal = isset($p['posto']) ? $p['posto'] : '';
            if ($postoVal === '') continue;
            $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'DESBLOQUEIO', ?, ?, NOW())");
            $stmtHist->execute(array($postoVal, $motivo, $responsavel));
        }
        die(json_encode(array('success' => true)));
    }

    if (isset($_POST['ajax_listar_postos_regionais'])) {
        header('Content-Type: application/json');
        $stmt = $pdo->query("SELECT LPAD(posto,3,'0') AS posto, CAST(regional AS UNSIGNED) AS regional, LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega FROM ciRegionais ORDER BY regional, posto");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grupos = array();
        foreach ($rows as $r) {
            $reg = (int)$r['regional'];
            $isPT = (strpos($r['entrega'], 'poupa') !== false || strpos($r['entrega'], 'tempo') !== false);
            $label = '';
            if ($isPT) {
                $label = 'PT';
            } elseif ($reg === 0) {
                $label = 'Capital';
            } elseif ($reg === 999) {
                $label = 'Central';
            } else {
                $label = 'R' . str_pad((string)$reg, 3, '0', STR_PAD_LEFT);
            }
            if (!isset($grupos[$label])) {
                $grupos[$label] = array();
            }
            $grupos[$label][] = $r['posto'];
        }

        die(json_encode(array('success' => true, 'grupos' => $grupos)));
    }

} catch (PDOException $ex) {
    if (isset($_POST['ajax_listar_bloqueados']) || isset($_POST['ajax_bloquear_postos']) || isset($_POST['ajax_desbloquear_posto']) || isset($_POST['ajax_desbloquear_todos']) || isset($_POST['ajax_listar_postos_regionais']) || isset($_POST['ajax_bloquear_grupo']) || isset($_POST['ajax_desbloquear_postos'])) {
        header('Content-Type: application/json');
        die(json_encode(array('success' => false, 'erro' => 'Erro de conexao com o banco de dados')));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloqueios de Postos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            padding-top: 80px;
        }

        .topo-fixo {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: linear-gradient(135deg, #b71c1c 0%, #e53935 100%);
            color: white;
            padding: 12px 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .topo-fixo h1 { font-size: 18px; font-weight: 700; }
        .topo-fixo .versao {
            background: #ff5722; color: white;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 700;
        }

        .btn-voltar {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            background: rgba(255,255,255,0.15);
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-voltar:hover { background: rgba(255,255,255,0.3); }

        .area-principal { max-width: 900px; margin: 0 auto; }

        .painel {
            background: white; border-radius: 10px;
            padding: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        .painel h3 {
            color: #b71c1c; margin-bottom: 12px;
            font-size: 16px; border-left: 4px solid #b71c1c;
            padding-left: 10px;
        }

        .form-bloquear {
            display: flex; gap: 10px; flex-wrap: wrap;
            align-items: flex-end; margin-bottom: 16px;
        }
        .form-bloquear label {
            display: block; font-size: 12px; color: #666;
            font-weight: 700; margin-bottom: 4px;
        }
        .form-bloquear input[type="text"],
        .form-bloquear textarea {
            padding: 10px 14px; border: 2px solid #b71c1c;
            border-radius: 6px; font-size: 14px;
            font-family: inherit;
        }
        .form-bloquear input[type="text"]:focus,
        .form-bloquear textarea:focus {
            outline: none; border-color: #e53935; background: #fff3f3;
        }
        .input-postos { width: 250px; min-height: 40px; resize: vertical; }
        .input-motivo { width: 280px; }
        .btn-bloquear {
            background: #b71c1c; color: white; border: none;
            padding: 10px 20px; border-radius: 6px;
            font-weight: 700; font-size: 14px; cursor: pointer;
        }
        .btn-bloquear:hover { background: #d32f2f; }

        .bloq-tabela {
            width: 100%; border-collapse: collapse; font-size: 13px;
        }
        .bloq-tabela thead { background: #b71c1c; color: white; }
        .bloq-tabela th, .bloq-tabela td {
            padding: 8px 12px; border: 1px solid #ddd; text-align: left;
        }
        .bloq-tabela tbody tr:nth-child(even) { background: #fafafa; }
        .bloq-tabela tbody tr:hover { background: #fff3f3; }
        .btn-remover {
            background: #4caf50; color: white; border: none;
            padding: 6px 14px; border-radius: 4px;
            font-size: 12px; font-weight: 700; cursor: pointer;
        }
        .btn-remover:hover { background: #388e3c; }

        .btn-limpar-todos {
            background: #757575; color: white; border: none;
            padding: 8px 18px; border-radius: 6px;
            font-weight: 700; font-size: 13px; cursor: pointer;
            margin-top: 12px;
        }
        .btn-limpar-todos:hover { background: #616161; }

        .grupo-bloquear {
            background: white; border-radius: 10px;
            padding: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        .grupo-bloquear h3 {
            color: #e65100; margin-bottom: 12px;
            font-size: 16px; border-left: 4px solid #e65100;
            padding-left: 10px;
        }
        .grupo-secao {
            border: 1px solid #e0e0e0; border-radius: 8px;
            margin-bottom: 8px; overflow: hidden;
        }
        .grupo-secao-titulo {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px; background: #fff3e0; cursor: pointer;
            font-weight: 700; font-size: 14px; color: #e65100;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }
        .grupo-secao-titulo:hover { background: #ffe0b2; }
        .grupo-secao-titulo .seta { transition: transform 0.2s; font-size: 12px; }
        .grupo-secao-titulo .seta.aberto { transform: rotate(90deg); }
        .grupo-secao-titulo .chip-count {
            font-size: 11px; font-weight: 400; opacity: 0.8; margin-left: 6px;
        }
        .grupo-secao-titulo .btn-sel-todos {
            font-size: 11px; font-weight: 700; color: #e65100;
            background: none; border: 1px solid #e65100; border-radius: 4px;
            padding: 2px 8px; cursor: pointer; margin-left: 8px;
        }
        .grupo-secao-titulo .btn-sel-todos:hover { background: #e65100; color: white; }
        .grupo-secao-conteudo {
            display: none; padding: 10px 16px;
            flex-wrap: wrap; gap: 6px;
        }
        .grupo-secao-conteudo.aberto { display: flex; }
        .grupo-chip-posto {
            display: inline-flex; align-items: center;
            padding: 6px 12px; border-radius: 6px;
            font-size: 12px; font-weight: 700;
            background: #fafafa; color: #555;
            border: 2px solid #ccc;
            cursor: pointer; transition: all 0.2s;
        }
        .grupo-chip-posto:hover { background: #ffe0b2; border-color: #ffb74d; }
        .grupo-chip-posto.selecionado {
            background: #e65100; color: white; border-color: #e65100;
        }
        .btn-bloquear-grupo {
            background: #e65100; color: white; border: none;
            padding: 10px 20px; border-radius: 6px;
            font-weight: 700; font-size: 14px; cursor: pointer;
        }
        .btn-bloquear-grupo:hover { background: #bf360c; }
        .btn-desbloquear-grupo {
            background: #4caf50; color: white; border: none;
            padding: 10px 20px; border-radius: 6px;
            font-weight: 700; font-size: 14px; cursor: pointer;
            margin-left: 8px;
        }
        .btn-desbloquear-grupo:hover { background: #388e3c; }

        .motivo-grupo-row {
            margin-top: 12px; margin-bottom: 12px;
            display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap;
        }
        .motivo-grupo-row label {
            font-size: 12px; color: #666; font-weight: 700;
            display: block; margin-bottom: 4px;
        }
        .motivo-grupo-row input[type="text"] {
            padding: 10px 14px; border: 2px solid #e65100;
            border-radius: 6px; font-size: 14px; width: 300px;
        }
        .motivo-grupo-row input[type="text"]:focus {
            outline: none; border-color: #bf360c; background: #fff3e0;
        }

        .msg-vazia { color: #999; text-align: center; padding: 20px; font-style: italic; }
        .msg-status { margin-top: 10px; font-size: 13px; font-weight: 700; }

        .toast {
            position: fixed; top: 80px; right: 20px;
            padding: 14px 24px; border-radius: 8px;
            color: white; font-weight: 700; font-size: 14px;
            z-index: 9999; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            opacity: 0; transition: opacity 0.3s;
            max-width: 400px;
        }
        .toast.visivel { opacity: 1; }
        .toast.bloqueio { background: #b71c1c; }
        .toast.desbloqueio { background: #2e7d32; }

        @media (max-width: 600px) {
            .topo-fixo { flex-wrap: wrap; gap: 8px; }
            .input-postos { width: 100%; }
            .input-motivo { width: 100%; }
            .motivo-grupo-row input[type="text"] { width: 100%; }
        }
    </style>
</head>
<body>

<div class="topo-fixo">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="inicio.php" class="btn-voltar">&larr; Inicio</a>
        <h1>Bloqueios de Postos</h1>
        <span class="versao">v2.0.3</span>
    </div>
</div>

<div id="toastContainer"></div>

<div class="area-principal">

    <div class="painel">
        <h3>Bloquear Postos</h3>
        <div style="margin-bottom:10px; font-size:12px; color:#666;">Digite um ou varios postos separados por virgula, espaco ou quebra de linha.</div>
        <div class="form-bloquear">
            <div>
                <label>Postos:</label>
                <textarea id="inputPostos" class="input-postos" placeholder="Ex: 040, 041 042&#10;043" rows="2"></textarea>
            </div>
            <div>
                <label>Motivo (obrigatorio):</label>
                <input type="text" id="inputMotivo" class="input-motivo" placeholder="Ex: Sem funcionario">
            </div>
            <div>
                <label>Responsavel (obrigatorio):</label>
                <input type="text" id="inputResponsavel" class="input-motivo" placeholder="Ex: Joao Silva">
            </div>
            <button class="btn-bloquear" onclick="bloquearPostos()">Bloquear</button>
        </div>
        <div id="msgBloquear" class="msg-status"></div>
    </div>

    <div class="grupo-bloquear">
        <h3>Bloquear/Desbloquear por Grupo</h3>
        <div style="margin-bottom:10px; font-size:12px; color:#666;">Selecione um ou mais grupos e clique em bloquear ou desbloquear.</div>
        <div class="grupo-chips" id="grupoChips">
            <div class="msg-vazia">Carregando grupos...</div>
        </div>
        <div class="motivo-grupo-row">
            <div>
                <label>Motivo (obrigatorio):</label>
                <input type="text" id="inputMotivoGrupo" placeholder="Ex: Ferias regional">
            </div>
            <div>
                <label>Responsavel (obrigatorio):</label>
                <input type="text" id="inputResponsavelGrupo" placeholder="Ex: Maria Souza">
            </div>
        </div>
        <div>
            <button class="btn-bloquear-grupo" onclick="bloquearGrupoSelecionado()">Bloquear Selecionados</button>
            <button class="btn-desbloquear-grupo" onclick="desbloquearGrupoSelecionado()">Desbloquear Selecionados</button>
        </div>
        <div id="msgGrupo" class="msg-status"></div>
    </div>

    <div class="painel">
        <h3>Postos Bloqueados Atualmente</h3>
        <div id="listaBloqueados">
            <div class="msg-vazia">Carregando...</div>
        </div>
        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <label style="font-size:12px; color:#666; font-weight:700; display:block; margin-bottom:4px;">Motivo para desbloquear todos:</label>
                <input type="text" id="inputMotivoDesbloquearTodos" style="padding:8px 12px; border:2px solid #757575; border-radius:6px; font-size:13px; width:250px;">
            </div>
            <div>
                <label style="font-size:12px; color:#666; font-weight:700; display:block; margin-bottom:4px;">Responsavel pelo desbloqueio:</label>
                <input type="text" id="inputResponsavelDesbloquearTodos" style="padding:8px 12px; border:2px solid #757575; border-radius:6px; font-size:13px; width:220px;">
            </div>
            <button class="btn-limpar-todos" onclick="desbloquearTodos()">Desbloquear Todos</button>
        </div>
    </div>

</div>

<script>
var gruposDados = {};
var postosSelecionados = {};

function mostrarToast(texto, tipo) {
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + tipo;
    toast.textContent = texto;
    container.appendChild(toast);
    setTimeout(function() { toast.className += ' visivel'; }, 50);
    setTimeout(function() {
        toast.className = toast.className.replace(' visivel', '');
        setTimeout(function() { container.removeChild(toast); }, 400);
    }, 4000);
}

function vocalizarMotivo(texto) {
    if (typeof speechSynthesis === 'undefined') return;
    var utt = new SpeechSynthesisUtterance(texto);
    utt.lang = 'pt-BR';
    utt.rate = 0.9;
    utt.volume = 1;
    speechSynthesis.speak(utt);
}

var ultimoTextoVoz = '';
var vozTimer = null;

function vocalizarInputPostos(texto) {
    var t = (texto || '').replace(/\s+/g, ' ').trim();
    if (t === '' || t === ultimoTextoVoz) return;
    ultimoTextoVoz = t;
    vocalizarMotivo(t);
}

function carregarBloqueados() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    renderizarBloqueados(resp.bloqueados);
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_listar_bloqueados=1');
}

function renderizarBloqueados(lista) {
    var div = document.getElementById('listaBloqueados');
    if (lista.length === 0) {
        div.innerHTML = '<div class="msg-vazia">Nenhum posto bloqueado.</div>';
        return;
    }
    var html = '<table class="bloq-tabela"><thead><tr><th>Posto</th><th>Motivo</th><th>Bloqueado em</th><th>Acao</th></tr></thead><tbody>';
    for (var i = 0; i < lista.length; i++) {
        var b = lista[i];
        html += '<tr>';
        html += '<td style="font-weight:700;">' + b.posto + '</td>';
        html += '<td>' + (b.motivo || b.nome || '-') + '</td>';
        html += '<td style="font-size:12px;">' + (b.criado || '-') + '</td>';
        html += '<td><button class="btn-remover" onclick="desbloquearPosto(\'' + b.posto + '\')">Desbloquear</button></td>';
        html += '</tr>';
    }
    html += '</tbody></table>';
    div.innerHTML = html;
}

function bloquearPostos() {
    var postos = document.getElementById('inputPostos').value.replace(/^\s+|\s+$/g, '');
    var motivo = document.getElementById('inputMotivo').value.replace(/^\s+|\s+$/g, '');
    var responsavel = document.getElementById('inputResponsavel').value.replace(/^\s+|\s+$/g, '');

    if (postos === '') {
        alert('Informe pelo menos um posto.');
        return;
    }
    if (motivo === '') {
        alert('O motivo e obrigatorio.');
        document.getElementById('inputMotivo').focus();
        return;
    }
    if (responsavel === '') {
        alert('O responsavel e obrigatorio.');
        document.getElementById('inputResponsavel').focus();
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    var msg = 'Bloqueado: ' + resp.bloqueados + ' posto(s). Motivo: ' + motivo;
                    document.getElementById('msgBloquear').innerHTML = '<span style="color:#4caf50;">' + msg + '</span>';
                    document.getElementById('inputPostos').value = '';
                    document.getElementById('inputMotivo').value = '';
                    carregarBloqueados();
                    mostrarToast(msg, 'bloqueio');
                    vocalizarMotivo(motivo);
                    setTimeout(function() { document.getElementById('msgBloquear').innerHTML = ''; }, 5000);
                } else {
                    document.getElementById('msgBloquear').innerHTML = '<span style="color:#f44336;">Erro: ' + (resp.erro || 'desconhecido') + '</span>';
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_bloquear_postos=1&postos=' + encodeURIComponent(postos) + '&motivo=' + encodeURIComponent(motivo) + '&responsavel=' + encodeURIComponent(responsavel));
}

var inputPostos = document.getElementById('inputPostos');
if (inputPostos) {
    inputPostos.addEventListener('input', function() {
        if (vozTimer) clearTimeout(vozTimer);
        var valor = this.value;
        vozTimer = setTimeout(function() {
            vocalizarInputPostos(valor);
        }, 600);
    });
    inputPostos.addEventListener('blur', function() {
        vocalizarInputPostos(this.value);
    });
}

function desbloquearPosto(posto) {
    var responsavel = document.getElementById('inputResponsavelDesbloquearTodos').value.replace(/^\s+|\s+$/g, '');
    var motivo = document.getElementById('inputMotivoDesbloquearTodos').value.replace(/^\s+|\s+$/g, '');
    if (responsavel === '') {
        alert('Informe o responsavel pelo desbloqueio.');
        document.getElementById('inputResponsavelDesbloquearTodos').focus();
        return;
    }
    if (!confirm('Desbloquear o posto ' + posto + '?')) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    carregarBloqueados();
                    mostrarToast('Posto ' + posto + ' desbloqueado', 'desbloqueio');
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_desbloquear_posto=1&posto=' + encodeURIComponent(posto) + '&motivo=' + encodeURIComponent(motivo) + '&responsavel=' + encodeURIComponent(responsavel));
}

function desbloquearTodos() {
    var motivo = document.getElementById('inputMotivoDesbloquearTodos').value.replace(/^\s+|\s+$/g, '');
    var responsavel = document.getElementById('inputResponsavelDesbloquearTodos').value.replace(/^\s+|\s+$/g, '');
    if (motivo === '') {
        alert('Informe o motivo para desbloquear todos.');
        document.getElementById('inputMotivoDesbloquearTodos').focus();
        return;
    }
    if (responsavel === '') {
        alert('Informe o responsavel pelo desbloqueio.');
        document.getElementById('inputResponsavelDesbloquearTodos').focus();
        return;
    }
    if (!confirm('Desbloquear TODOS os postos?\nMotivo: ' + motivo)) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    carregarBloqueados();
                    mostrarToast('Todos os postos desbloqueados. Motivo: ' + motivo, 'desbloqueio');
                    vocalizarMotivo('Todos desbloqueados. ' + motivo);
                    document.getElementById('inputMotivoDesbloquearTodos').value = '';
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_desbloquear_todos=1&motivo=' + encodeURIComponent(motivo) + '&responsavel=' + encodeURIComponent(responsavel));
}

function carregarGrupos() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    gruposDados = resp.grupos;
                    renderizarGrupos(resp.grupos);
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_listar_postos_regionais=1');
}

function renderizarGrupos(grupos) {
    var div = document.getElementById('grupoChips');
    var keys = [];
    for (var k in grupos) {
        if (grupos.hasOwnProperty(k)) {
            keys.push(k);
        }
    }
    keys.sort(function(a, b) {
        var ordem = {'Capital': 0, 'Central': 1, 'PT': 2};
        var oa = ordem.hasOwnProperty(a) ? ordem[a] : 3;
        var ob = ordem.hasOwnProperty(b) ? ordem[b] : 3;
        if (oa !== ob) return oa - ob;
        return a < b ? -1 : (a > b ? 1 : 0);
    });

    if (keys.length === 0) {
        div.innerHTML = '<div class="msg-vazia">Nenhum grupo encontrado.</div>';
        return;
    }

    var html = '';
    for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        var postos = grupos[k];
        var count = postos.length;
        var kSafe = k.replace(/'/g, "\\'");
        html += '<div class="grupo-secao" id="secao_' + i + '">';
        html += '<div class="grupo-secao-titulo" onclick="toggleSecao(' + i + ')">';
        html += '<div><span class="seta" id="seta_' + i + '">&#9654;</span> ' + k + ' <span class="chip-count">(' + count + ' postos)</span></div>';
        html += '<button class="btn-sel-todos" onclick="event.stopPropagation(); selecionarTodosGrupo(\'' + kSafe + '\', ' + i + ')">Sel. Todos</button>';
        html += '</div>';
        html += '<div class="grupo-secao-conteudo" id="conteudo_' + i + '">';
        for (var j = 0; j < postos.length; j++) {
            var p = postos[j];
            var sel = postosSelecionados[p] ? ' selecionado' : '';
            html += '<div class="grupo-chip-posto' + sel + '" data-posto="' + p + '" onclick="togglePosto(\'' + p + '\', this)">' + p + '</div>';
        }
        html += '</div>';
        html += '</div>';
    }
    div.innerHTML = html;
}

function toggleSecao(idx) {
    var conteudo = document.getElementById('conteudo_' + idx);
    var seta = document.getElementById('seta_' + idx);
    if (conteudo.className.indexOf('aberto') !== -1) {
        conteudo.className = 'grupo-secao-conteudo';
        seta.className = 'seta';
    } else {
        conteudo.className = 'grupo-secao-conteudo aberto';
        seta.className = 'seta aberto';
    }
}

function togglePosto(posto, el) {
    if (postosSelecionados[posto]) {
        delete postosSelecionados[posto];
        el.className = 'grupo-chip-posto';
    } else {
        postosSelecionados[posto] = true;
        el.className = 'grupo-chip-posto selecionado';
    }
}

function selecionarTodosGrupo(grupoKey, idx) {
    var postos = gruposDados[grupoKey];
    if (!postos) return;
    var todosJaSelecionados = true;
    for (var i = 0; i < postos.length; i++) {
        if (!postosSelecionados[postos[i]]) {
            todosJaSelecionados = false;
            break;
        }
    }
    var conteudo = document.getElementById('conteudo_' + idx);
    var seta = document.getElementById('seta_' + idx);
    if (conteudo.className.indexOf('aberto') === -1) {
        conteudo.className = 'grupo-secao-conteudo aberto';
        seta.className = 'seta aberto';
    }
    var chips = conteudo.querySelectorAll('.grupo-chip-posto');
    for (var i = 0; i < postos.length; i++) {
        if (todosJaSelecionados) {
            delete postosSelecionados[postos[i]];
        } else {
            postosSelecionados[postos[i]] = true;
        }
    }
    for (var j = 0; j < chips.length; j++) {
        var p = chips[j].getAttribute('data-posto');
        if (postosSelecionados[p]) {
            chips[j].className = 'grupo-chip-posto selecionado';
        } else {
            chips[j].className = 'grupo-chip-posto';
        }
    }
}

function coletarPostosSelecionados() {
    var postos = [];
    for (var p in postosSelecionados) {
        if (postosSelecionados.hasOwnProperty(p)) {
            postos.push({posto: p, nome: ''});
        }
    }
    return postos;
}

function bloquearGrupoSelecionado() {
    var postos = coletarPostosSelecionados();
    var motivo = document.getElementById('inputMotivoGrupo').value.replace(/^\s+|\s+$/g, '');
    var responsavel = document.getElementById('inputResponsavelGrupo').value.replace(/^\s+|\s+$/g, '');

    if (postos.length === 0) {
        alert('Selecione pelo menos um posto.');
        return;
    }
    if (motivo === '') {
        alert('O motivo e obrigatorio.');
        document.getElementById('inputMotivoGrupo').focus();
        return;
    }
    if (responsavel === '') {
        alert('O responsavel e obrigatorio.');
        document.getElementById('inputResponsavelGrupo').focus();
        return;
    }
    if (!confirm('Bloquear ' + postos.length + ' posto(s) dos grupos selecionados?\nMotivo: ' + motivo)) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    var msg = resp.bloqueados + ' posto(s) bloqueado(s). Motivo: ' + motivo;
                    document.getElementById('msgGrupo').innerHTML = '<span style="color:#4caf50;">' + msg + '</span>';
                    carregarBloqueados();
                    mostrarToast(msg, 'bloqueio');
                    vocalizarMotivo(motivo);
                    setTimeout(function() { document.getElementById('msgGrupo').innerHTML = ''; }, 5000);
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_bloquear_grupo=1&postos=' + encodeURIComponent(JSON.stringify(postos)) + '&motivo=' + encodeURIComponent(motivo) + '&responsavel=' + encodeURIComponent(responsavel));
}

function desbloquearGrupoSelecionado() {
    var postos = coletarPostosSelecionados();
    var motivo = document.getElementById('inputMotivoGrupo').value.replace(/^\s+|\s+$/g, '');
    var responsavel = document.getElementById('inputResponsavelGrupo').value.replace(/^\s+|\s+$/g, '');

    if (postos.length === 0) {
        alert('Selecione pelo menos um posto.');
        return;
    }
    if (motivo === '') {
        alert('O motivo e obrigatorio.');
        document.getElementById('inputMotivoGrupo').focus();
        return;
    }
    if (responsavel === '') {
        alert('O responsavel e obrigatorio.');
        document.getElementById('inputResponsavelGrupo').focus();
        return;
    }
    if (!confirm('Desbloquear ' + postos.length + ' posto(s) dos grupos selecionados?\nMotivo: ' + motivo)) return;

    var postosArr = [];
    for (var i = 0; i < postos.length; i++) {
        postosArr.push(postos[i].posto);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'bloqueados.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    var msg = resp.desbloqueados + ' posto(s) desbloqueado(s). Motivo: ' + motivo;
                    document.getElementById('msgGrupo').innerHTML = '<span style="color:#4caf50;">' + msg + '</span>';
                    carregarBloqueados();
                    mostrarToast(msg, 'desbloqueio');
                    vocalizarMotivo('Desbloqueado. ' + motivo);
                    setTimeout(function() { document.getElementById('msgGrupo').innerHTML = ''; }, 5000);
                }
            } catch (ex) {}
        }
    };
    xhr.send('ajax_desbloquear_postos=1&postos=' + encodeURIComponent(JSON.stringify(postosArr)) + '&motivo=' + encodeURIComponent(motivo) + '&responsavel=' + encodeURIComponent(responsavel));
}

carregarBloqueados();
carregarGrupos();
</script>

</body>
</html>
