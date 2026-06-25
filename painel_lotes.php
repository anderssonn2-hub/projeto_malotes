<?php
/* painel_lotes.php — v1.2.2
   Controle de lotes: estante, em trânsito, retornou
   Status via conferencia_pacotes (packaged) + ciMalotes tipo=1/2 (saiu/retornou)
   Despachado em/por = dados do ofício gerado (ciDespachos)
*/
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

define('PL_DB_HOST', getenv('DB_HOST') ?: (getenv('DB_HOST') ?: '10.15.61.169'));
define('PL_DB_NAME', getenv('DB_NAME') ?: (getenv('DB_NAME') ?: 'controle'));
define('PL_DB_USER', getenv('DB_USER') ?: (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat')));
define('PL_DB_PASS', getenv('DB_PASS') ?: (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256')));

function ePL($s) {
    $s2 = (string)$s;
    if (!preg_match('//u', $s2) && function_exists('iconv')) {
        $t = @iconv('UTF-8','UTF-8//IGNORE',$s2);
        if ($t !== false) $s2 = $t;
    }
    return htmlspecialchars($s2, ENT_QUOTES, 'UTF-8');
}
function dataBrPL($d) {
    if (!$d || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') return '—';
    $s = substr(trim((string)$d), 0, 10);
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    return ($dt === false) ? $s : $dt->format('d/m/Y');
}
function diasAtrasPL($d) {
    if (!$d) return null;
    $dt = DateTime::createFromFormat('Y-m-d', substr(trim((string)$d), 0, 10));
    if ($dt === false) return null;
    return (int)(new DateTime('today'))->diff($dt)->days;
}

/* ── DB ── */
$dbOk = false; $erroMsg = '';
try {
    $pdo = new PDO("mysql:host=".PL_DB_HOST.";dbname=".PL_DB_NAME.";charset=utf8", PL_DB_USER, PL_DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;
} catch (Exception $ex) { $erroMsg = 'Falha DB: '.$ex->getMessage(); }

/* ── MIGRATIONS ── */
if ($dbOk) {
    $c1 = $pdo->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'data_despacho_correios'")->fetchAll();
    if (empty($c1)) $pdo->exec("ALTER TABLE ciDespachoLotes ADD COLUMN data_despacho_correios DATE NULL DEFAULT NULL AFTER etiqueta_correios");
    $c2 = $pdo->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'despachado_por'")->fetchAll();
    if (empty($c2)) $pdo->exec("ALTER TABLE ciDespachoLotes ADD COLUMN despachado_por VARCHAR(100) NULL DEFAULT NULL AFTER data_despacho_correios");
    $c3 = $pdo->query("SHOW COLUMNS FROM ciDespachos LIKE 'criado_em'")->fetchAll();
    if (empty($c3)) $pdo->exec("ALTER TABLE ciDespachos ADD COLUMN criado_em TIMESTAMP NULL DEFAULT NULL");
}

/* ── AJAX ── */
if ($dbOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? trim((string)$_POST['acao']) : '';
    if ($acao === 'confirmar_despacho') {
        header('Content-Type: application/json; charset=UTF-8');
        $id   = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $resp = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
        if ($id <= 0 || $resp === '') { echo json_encode(array('ok'=>false,'msg'=>'Informe o responsavel.')); exit; }
        $pdo->prepare("UPDATE ciDespachoLotes SET data_despacho_correios=CURDATE(), despachado_por=? WHERE id=?")->execute(array($resp, $id));
        echo json_encode(array('ok'=>true,'msg'=>'Despacho confirmado!','data'=>date('d/m/Y'),'por'=>ePL($resp)));
        exit;
    }
    if ($acao === 'desfazer_despacho') {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        if ($id <= 0) { echo json_encode(array('ok'=>false,'msg'=>'ID invalido.')); exit; }
        $st = $pdo->prepare("UPDATE ciDespachoLotes SET data_despacho_correios=NULL, despachado_por=NULL WHERE id=?");
        $st->execute(array($id));
        echo json_encode(array('ok'=>($st->rowCount()>0),'msg'=>($st->rowCount()>0)?'Devolvido à estante.':'Não foi possível desfazer.'));
        exit;
    }
    exit;
}

/* ── FILTROS ── */
$filtro_dias  = (isset($_GET['dias']) && is_numeric($_GET['dias']) && (int)$_GET['dias'] >= 0) ? (int)$_GET['dias'] : 30;
$filtro_data  = (isset($_GET['data_filtro']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET['data_filtro']))) ? trim($_GET['data_filtro']) : '';
$filtro_grupo = isset($_GET['grupo']) ? trim((string)$_GET['grupo']) : '';
$filtro_lote  = isset($_GET['lote']) ? preg_replace('/\D+/', '', trim((string)$_GET['lote'])) : '';

$lotes = array();
$kpis  = array('na_estante'=>0,'em_transito'=>0,'retornou'=>0,'total'=>0);
$grupos = array();

if ($dbOk) {
    $stG = $pdo->query("SELECT DISTINCT grupo FROM ciDespachos WHERE grupo IS NOT NULL AND grupo<>'' ORDER BY grupo ASC");
    $grupos = $stG->fetchAll(PDO::FETCH_COLUMN, 0);

    /* WHERE dinâmico — base: lotes_na_estante (triado_em) */
    if ($filtro_data !== '') {
        $sqlWhere = "WHERE (DATE(ln.triado_em)=? OR DATE(ln.producao_de)=?)";
        $params = array($filtro_data, $filtro_data);
    } elseif ($filtro_dias === 0) {
        $sqlWhere = "WHERE DATE(ln.triado_em)=CURDATE()";
        $params = array();
    } else {
        $sqlWhere = "WHERE DATE(ln.triado_em)>=DATE_SUB(CURDATE(),INTERVAL ? DAY)";
        $params = array($filtro_dias);
    }
    if ($filtro_grupo !== '') { $sqlWhere .= " AND cd.grupo=?"; $params[] = $filtro_grupo; }
    if ($filtro_lote !== '')  { $sqlWhere .= " AND ln.lote=?"; $params[] = (int)$filtro_lote; }

    /* Query principal: lotes_na_estante como base, LEFT JOIN despachos e conferência */
    $sql = "SELECT ln.lote, ln.posto, ln.regional, ln.quantidade, ln.triado_em, ln.producao_de,
                   ln.triado_por,
                   cdl.id AS cdl_id, cdl.etiqueta_correios, cdl.data_carga,
                   cdl.responsaveis, cdl.data_despacho_correios, cdl.despachado_por,
                   cd.grupo, cd.datas_str,
                   COALESCE(cd.usuario,'') AS oficio_usuario,
                   COALESCE(cd.criado_em,'') AS oficio_criado_em,
                   cp2.conf_em
            FROM lotes_na_estante ln
            LEFT JOIN ciDespachoLotes cdl
                   ON CAST(cdl.lote AS UNSIGNED) = ln.lote
                  AND CAST(cdl.posto AS UNSIGNED) = ln.posto
            LEFT JOIN ciDespachos cd ON cd.id = cdl.id_despacho
            LEFT JOIN (
                SELECT CAST(nlote AS UNSIGNED) AS nlote_n, CAST(nposto AS UNSIGNED) AS nposto_n,
                       MAX(conferido_em) AS conf_em
                FROM conferencia_pacotes WHERE conf='s'
                GROUP BY nlote_n, nposto_n
            ) cp2 ON ln.lote=cp2.nlote_n AND ln.posto=cp2.nposto_n
            $sqlWhere
            ORDER BY ln.triado_em DESC, ln.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $todosLotes = $stmt->fetchAll();

    /* Buscar ciMalotes tipo=1 (saiu) e tipo=2 (retornou) em lote */
    $all_etiq = array();
    foreach ($todosLotes as $r) { if ($r['etiqueta_correios']) $all_etiq[] = $r['etiqueta_correios']; }
    $all_etiq = array_values(array_unique(array_filter($all_etiq)));
    $mapaSaidas = array(); $mapaRetornos = array();
    if (!empty($all_etiq)) {
        $ph = implode(',', array_fill(0, count($all_etiq), '?'));
        $sMal = $pdo->prepare("SELECT leitura, tipo, MAX(data) AS data_evt FROM ciMalotes WHERE leitura IN ($ph) AND tipo IN (1,2) GROUP BY leitura, tipo");
        $sMal->execute($all_etiq);
        while ($r = $sMal->fetch()) {
            if ($r['tipo'] == 1) $mapaSaidas[$r['leitura']]   = $r['data_evt'];
            if ($r['tipo'] == 2) $mapaRetornos[$r['leitura']] = $r['data_evt'];
        }
    }

    /* Determina status: Na estante / Em trânsito / Retornou */
    foreach ($todosLotes as $row) {
        $etiq = $row['etiqueta_correios'];
        $conferenciado = !empty($row['conf_em']);
        $saiu  = $etiq && isset($mapaSaidas[$etiq]);
        /* Retornou apenas se: existe tipo=2 E existe tipo=1 E data_retorno >= data_saida
           (garante que o retorno é desta viagem e não de uma anterior com a mesma etiqueta) */
        $voltou = $etiq
            && isset($mapaRetornos[$etiq])
            && isset($mapaSaidas[$etiq])
            && strcmp((string)$mapaRetornos[$etiq], (string)$mapaSaidas[$etiq]) >= 0;
        if ($voltou) {
            $row['status'] = 'retornou';
            $row['data_retorno'] = $mapaRetornos[$etiq];
        } elseif ($conferenciado || $saiu) {
            $row['status'] = 'em_transito';
            $row['data_retorno'] = null;
        } else {
            $row['status'] = 'na_estante';
            $row['data_retorno'] = null;
        }
        $row['foi_conferenciado'] = $conferenciado;
        $kpis[$row['status']]++;
        $kpis['total']++;
        $lotes[] = $row;
    }

    $ordemStatus = array('na_estante'=>0,'em_transito'=>1,'retornou'=>2);
    usort($lotes, function($a, $b) use ($ordemStatus) {
        $oa = isset($ordemStatus[$a['status']]) ? $ordemStatus[$a['status']] : 3;
        $ob = isset($ordemStatus[$b['status']]) ? $ordemStatus[$b['status']] : 3;
        return ($oa !== $ob) ? $oa - $ob : strcmp((string)$b['triado_em'], (string)$a['triado_em']);
    });
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel de Lotes v1.2.2</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Trebuchet MS","Segoe UI",Arial,sans-serif;background:#eef2f7;color:#1a2b3c;min-height:100vh;}
a{color:#0b3d91;text-decoration:none;}
.topbar{background:#0b1a2e;color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.topbar h1{font-size:16px;font-weight:700;flex:1;}
.topbar a.home{color:#90caf9;font-size:12px;}
.main{max-width:1300px;margin:18px auto;padding:0 14px;}
.card{background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:14px;}
.kpis{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
.kpi{background:#fff;border-radius:10px;padding:12px 18px;box-shadow:0 2px 8px rgba(0,0,0,.07);flex:1;min-width:110px;text-align:center;cursor:pointer;border:2px solid transparent;transition:border-color .15s;}
.kpi:hover{border-color:#b0c4d8;}.kpi.ativo{border-color:#0b3d91;}
.kpi .k-label{font-size:11px;color:#607080;margin-bottom:4px;}
.kpi .k-val{font-size:28px;font-weight:700;color:#0b1a2e;}
.kpi.kpi-estante .k-val{color:#e65100;}.kpi.kpi-transito .k-val{color:#1565c0;}.kpi.kpi-retornou .k-val{color:#2e7d32;}
.filtros{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;}
.filtros label{font-size:11px;font-weight:700;color:#3a5068;margin-right:2px;}
.fbtn{padding:5px 12px;border-radius:16px;border:1px solid #b0c4d8;font-size:11px;font-weight:700;color:#3a5068;background:#fff;cursor:pointer;text-decoration:none;display:inline-block;}
.fbtn:hover{background:#f0f4f8;}.fbtn.ativo{background:#0b3d91;color:#fff;border-color:#0b3d91;}
.sep{width:1px;height:22px;background:#d0dae4;margin:0 4px;}
.date-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px;}
.date-row label{font-size:11px;font-weight:700;color:#3a5068;}
.date-row input[type=date]{padding:5px 8px;border:1px solid #b0c4d8;border-radius:8px;font-size:12px;color:#1a2b3c;}
.date-row input[type=date]:focus{outline:none;border-color:#0b3d91;}
.resp-row{display:flex;gap:10px;align-items:center;margin-top:10px;flex-wrap:wrap;}
.resp-row label{font-size:12px;font-weight:700;color:#3a5068;white-space:nowrap;}
.resp-row input{padding:8px 12px;border:2px solid #b0c4d8;border-radius:8px;font-size:13px;min-width:220px;}
.resp-row input:focus{border-color:#0b3d91;outline:none;}
.tabela{width:100%;border-collapse:collapse;font-size:12px;}
.tabela th{background:#1a2b3c;color:#fff;padding:8px 10px;text-align:left;font-size:11px;white-space:nowrap;min-width:80px;}
.tabela td{padding:7px 10px;border-bottom:1px solid #eef2f7;vertical-align:middle;}
.tabela tr:hover td{background:#f5f8fc;}
.mono{font-family:"Courier New",Courier,monospace;font-size:11px;letter-spacing:0.3px;color:#1a2b3c;word-break:break-all;}
.badge-status{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;}
.bs-estante{background:#fff3e0;color:#e65100;border:1px solid #ffcc80;}
.bs-transito{background:#e3f2fd;color:#0d47a1;border:1px solid #90caf9;}
.bs-retornou{background:#e8f5e9;color:#1b5e20;border:1px solid #a5d6a7;}
.dias-tag{display:inline-block;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap;}
.dt-ok{background:#e8f5e9;color:#1b5e20;}.dt-medio{background:#fff8e1;color:#7d4e00;}.dt-antigo{background:#ffebee;color:#b71c1c;}
.btn-conf{background:#1565c0;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-conf:hover{background:#0d47a1;}.btn-conf:disabled{background:#90a4ae;cursor:default;}
.btn-desfazer{background:none;border:1px solid #b0c4d8;border-radius:6px;padding:4px 10px;font-size:10px;color:#607080;cursor:pointer;white-space:nowrap;}
.btn-desfazer:hover{background:#ffebee;color:#c62828;border-color:#ef9a9a;}
.btn-devolver{background:none;border:2px solid #e65100;border-radius:6px;padding:4px 10px;font-size:10px;color:#e65100;cursor:pointer;white-space:nowrap;font-weight:700;}
.btn-devolver:hover{background:#fff3e0;}
.msg-inline{font-size:11px;color:#2e7d32;font-weight:700;display:none;}.msg-inline.erro{color:#c62828;}
.empty-msg{text-align:center;padding:28px;color:#90a4ae;font-size:13px;}
.grupo-tag{display:inline-block;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;background:#e8eaf6;color:#283593;}
.hidden-row{display:none;}
.search-wrap{display:flex;gap:8px;align-items:center;margin-top:8px;}
.search-wrap input{padding:7px 11px;border:2px solid #b0c4d8;border-radius:8px;font-size:12px;width:160px;}
.search-wrap input:focus{outline:none;border-color:#0b3d91;}
.conf-tag{display:inline-block;padding:1px 5px;border-radius:4px;font-size:9px;font-weight:700;background:#e3f2fd;color:#0d47a1;white-space:nowrap;margin-top:2px;}
.retornou-tag{display:inline-block;padding:1px 5px;border-radius:4px;font-size:9px;font-weight:700;background:#e8f5e9;color:#1b5e20;white-space:nowrap;}
</style>
</head>
<body>
<div class="topbar">
  <a class="home" href="inicio.php">&#8592; Início</a>
  <h1>&#128230; Painel de Controle de Lotes</h1>
  <span style="font-size:11px;opacity:.7;">v2.0.3</span>
</div>

<div class="main">
<?php if (!$dbOk): ?>
  <div class="card"><p style="color:#c62828;">&#9888; <?php echo ePL($erroMsg); ?></p></div>
<?php else: ?>

<!-- KPIs -->
<div class="kpis">
  <div class="kpi kpi-estante" id="kpi-estante" onclick="filtrarStatus('na_estante')">
    <div class="k-label">&#128230; Na estante</div>
    <div class="k-val" id="kval-estante"><?php echo $kpis['na_estante']; ?></div>
    <div style="font-size:10px;color:#e65100;margin-top:2px;">Não passou por conferência</div>
  </div>
  <div class="kpi kpi-transito" id="kpi-transito" onclick="filtrarStatus('em_transito')">
    <div class="k-label">&#9992; Em trânsito</div>
    <div class="k-val" id="kval-transito"><?php echo $kpis['em_transito']; ?></div>
    <div style="font-size:10px;color:#1565c0;margin-top:2px;">Saiu — display não retornou</div>
  </div>
  <div class="kpi kpi-retornou" id="kpi-retornou" onclick="filtrarStatus('retornou')">
    <div class="k-label">&#10003; Retornou</div>
    <div class="k-val" id="kval-retornou"><?php echo $kpis['retornou']; ?></div>
    <div style="font-size:10px;color:#2e7d32;margin-top:2px;">Display voltou (ciMalotes tipo=2)</div>
  </div>
  <div class="kpi" id="kpi-todos" onclick="filtrarStatus('')" style="border-color:#b0c4d8;">
    <div class="k-label">Total</div>
    <div class="k-val"><?php echo $kpis['total']; ?></div>
    <div style="font-size:10px;color:#607080;margin-top:2px;">no período</div>
  </div>
</div>

<!-- Filtros -->
<div class="card" style="padding:14px 18px;">
  <div class="filtros">
    <label>Período:</label>
    <?php foreach (array(0=>'Hoje',15=>'15 dias',30=>'30 dias',60=>'60 dias',90=>'90 dias') as $v=>$lbl):
      $isAtivo = ($filtro_data === '' && $filtro_dias === $v); ?>
      <a href="?dias=<?php echo $v; ?><?php echo $filtro_grupo?'&grupo='.urlencode($filtro_grupo):''; ?><?php echo $filtro_lote?'&lote='.$filtro_lote:''; ?>"
         class="fbtn <?php echo $isAtivo?'ativo':''; ?>"><?php echo $lbl; ?></a>
    <?php endforeach; ?>
    <div class="sep"></div>
    <label>Grupo:</label>
    <a href="?dias=<?php echo $filtro_dias; ?><?php echo $filtro_lote?'&lote='.$filtro_lote:''; ?>"
       class="fbtn <?php echo ($filtro_grupo===''?'ativo':''); ?>">Todos</a>
    <?php foreach ($grupos as $g): ?>
      <a href="?dias=<?php echo $filtro_dias; ?>&grupo=<?php echo urlencode($g); ?><?php echo $filtro_lote?'&lote='.$filtro_lote:''; ?>"
         class="fbtn <?php echo ($filtro_grupo===$g?'ativo':''); ?>"><?php echo ePL($g); ?></a>
    <?php endforeach; ?>
    <?php if ($filtro_lote !== ''): ?>
      <div class="sep"></div>
      <span style="font-size:11px;background:#e3f2fd;padding:4px 10px;border-radius:12px;color:#0d47a1;font-weight:700;">Lote: <?php echo ePL($filtro_lote); ?></span>
      <a href="?dias=<?php echo $filtro_dias; ?><?php echo $filtro_grupo?'&grupo='.urlencode($filtro_grupo):''; ?>" class="fbtn">&#10005; Limpar</a>
    <?php endif; ?>
  </div>

  <!-- Filtro por data específica -->
  <div class="date-row">
    <label>Data específica:</label>
    <form method="GET" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
      <?php if ($filtro_grupo): ?><input type="hidden" name="grupo" value="<?php echo ePL($filtro_grupo); ?>"><?php endif; ?>
      <?php if ($filtro_lote): ?><input type="hidden" name="lote" value="<?php echo ePL($filtro_lote); ?>"><?php endif; ?>
      <input type="date" name="data_filtro" value="<?php echo ePL($filtro_data); ?>" max="<?php echo $today; ?>">
      <button type="submit" class="fbtn <?php echo $filtro_data?'ativo':''; ?>" style="padding:5px 12px;">Filtrar por data</button>
      <?php if ($filtro_data): ?>
        <a href="?dias=<?php echo $filtro_dias; ?><?php echo $filtro_grupo?'&grupo='.urlencode($filtro_grupo):''; ?><?php echo $filtro_lote?'&lote='.$filtro_lote:''; ?>" class="fbtn">&#10005; Limpar data</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Responsável global -->
  <div class="resp-row">
    <label for="resp-global">Responsável pelo despacho:</label>
    <input type="text" id="resp-global" autocomplete="off" placeholder="Seu nome...">
    <span style="font-size:11px;color:#90a4ae;">Preenchido uma vez, vale para todos os botões.</span>
  </div>

  <!-- Busca por lote -->
  <div class="search-wrap">
    <label style="font-size:12px;font-weight:700;color:#3a5068;white-space:nowrap;">Buscar lote:</label>
    <input type="text" id="inp-busca-lote" placeholder="Nº do lote" maxlength="10"
           oninput="this.value=this.value.replace(/\D+/g,'')">
    <button class="btn-conf" onclick="irParaLote()">Buscar</button>
  </div>
</div>

<!-- Tabela -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-size:14px;color:#0b1a2e;font-weight:700;">
      Lotes — <span id="contador-visivel"><?php echo count($lotes); ?></span> registro(s)
      <span id="filtro-label" style="font-weight:400;font-size:12px;color:#607080;"></span>
    </h2>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <button class="fbtn ativo" id="fb-todos"    onclick="filtrarStatus('')">Todos</button>
      <button class="fbtn"       id="fb-estante"  onclick="filtrarStatus('na_estante')">&#128230; Na estante</button>
      <button class="fbtn"       id="fb-transito" onclick="filtrarStatus('em_transito')">&#9992; Em trânsito</button>
      <button class="fbtn"       id="fb-retornou" onclick="filtrarStatus('retornou')">&#10003; Retornou</button>
    </div>
  </div>

  <?php if (empty($lotes)): ?>
    <div class="empty-msg">Nenhum lote encontrado para os filtros selecionados.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="tabela" id="tabela-lotes">
    <thead>
      <tr>
        <th>#</th>
        <th>Lote</th>
        <th>Grupo</th>
        <th>Posto</th>
        <th>Qtd</th>
        <th>Data carga</th>
        <th>Etiqueta Correios</th>
        <th>Status</th>
        <th>Conferenciado em</th>
        <th>Despachado em</th>
        <th>Triado / Despachado por</th>
        <th style="min-width:90px;">Retornou em</th>
        <th>Ação</th>
      </tr>
    </thead>
    <tbody>
    <?php $i=1; foreach ($lotes as $row):
      $status    = $row['status'];
      $cdlId     = isset($row['cdl_id']) ? (int)$row['cdl_id'] : 0;
      $dataRef   = !empty($row['triado_em']) ? $row['triado_em'] : $row['producao_de'];
      $diasConf  = diasAtrasPL($dataRef);
      $diasClass = ($diasConf===null)?'':($diasConf<=7?'dt-ok':($diasConf<=20?'dt-medio':'dt-antigo'));
      $etiq      = $row['etiqueta_correios'] ? $row['etiqueta_correios'] : '';
      $isHoje    = (!empty($row['data_despacho_correios']) && $row['data_despacho_correios'] === $today);
      $statusClass = ($status==='na_estante'?'bs-estante':($status==='em_transito'?'bs-transito':'bs-retornou'));
      $statusLabel = ($status==='na_estante'?'&#128230; Na estante':($status==='em_transito'?'&#9992; Em trânsito':'&#10003; Retornou'));
      $oficioGrupo   = $row['grupo'] ? $row['grupo'] : '—';
      $oficioCriado  = isset($row['oficio_criado_em']) ? $row['oficio_criado_em'] : '';
      /* "Triado / Despachado por": só mostra usuario do ofício para Em trânsito/Retornou;
         para Na estante, mostra o triado_por com prefixo "Triado:" */
      if ($status === 'na_estante') {
          $responsavelLabel = $row['triado_por'] ? 'Triado: '.ePL($row['triado_por']) : '—';
      } else {
          $responsavelLabel = $row['oficio_usuario'] ? ePL($row['oficio_usuario']) : '—';
      }
    ?>
      <tr data-status="<?php echo ePL($status); ?>" data-id="<?php echo $cdlId; ?>">
        <td><?php echo $i++; ?></td>
        <td><strong><?php echo ePL(str_pad(preg_replace('/\D+/','',(string)$row['lote']),8,'0',STR_PAD_LEFT)); ?></strong></td>
        <td><span class="grupo-tag"><?php echo ePL($oficioGrupo); ?></span></td>
        <td style="font-size:11px;"><?php echo ePL($row['posto']?:'—'); ?></td>
        <td><?php echo ePL($row['quantidade']?:'—'); ?></td>
        <td>
          <?php echo dataBrPL($dataRef); ?>
          <?php if ($diasConf !== null): ?><br><span class="dias-tag <?php echo $diasClass; ?>"><?php echo $diasConf; ?>d atrás</span><?php endif; ?>
        </td>
        <td>
          <span class="mono"><?php echo $etiq ? ePL($etiq) : '—'; ?></span>
          <?php if ($row['foi_conferenciado']): ?><br><span class="conf-tag">&#10003; Conf. <?php echo dataBrPL($row['conf_em']); ?></span><?php endif; ?>
        </td>
        <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
        <td style="font-size:11px;color:#607080;"><?php echo dataBrPL($row['conf_em']); ?></td>
        <td style="font-size:11px;">
          <?php
          /* Despachado em: só exibe se houver ofício E se a data for >= data de triagem/carga */
          $dtOficioRaw = ($oficioCriado && $oficioCriado !== '' && substr($oficioCriado,0,10) !== '0000-00-00') ? substr($oficioCriado,0,10) : '';
          $dataRefDate = $dataRef ? substr($dataRef,0,10) : '';
          $dtOficioOk  = ($dtOficioRaw !== '' && ($dataRefDate === '' || strcmp($dtOficioRaw,$dataRefDate) >= 0));
          if ($dtOficioOk): echo ePL(dataBrPL($dtOficioRaw));
              if ($row['datas_str']): ?><br><span style="font-size:10px;color:#607080;"><?php echo ePL($row['datas_str']); ?></span><?php endif;
          else: echo '—'; endif; ?>
        </td>
        <td style="font-size:11px;"><?php echo $responsavelLabel; ?></td>
        <td style="font-size:11px;">
          <?php if (!empty($row['data_retorno'])): ?>
            <span class="retornou-tag">&#10003; <?php echo dataBrPL($row['data_retorno']); ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td style="white-space:nowrap;">
          <?php if ($status === 'na_estante' && $cdlId > 0): ?>
            <button class="btn-conf" onclick="confirmarDespacho(<?php echo $cdlId; ?>, this)">Confirmar Despacho</button>
            <div class="msg-inline" id="msg-<?php echo $cdlId; ?>"></div>
          <?php elseif ($status === 'na_estante'): ?>
            <span style="font-size:10px;color:#607080;">Aguardando ofício</span>
          <?php elseif ($status === 'em_transito' && $cdlId > 0): ?>
            <?php if ($isHoje): ?>
              <button class="btn-desfazer" onclick="desfazerDespacho(<?php echo $cdlId; ?>, this, false)">Desfazer</button>
            <?php else: ?>
              <button class="btn-devolver" onclick="desfazerDespacho(<?php echo $cdlId; ?>, this, true)">&#8617; Devolver à Estante</button>
            <?php endif; ?>
            <div class="msg-inline" id="msg-<?php echo $cdlId; ?>"></div>
          <?php else: ?>
            <span style="font-size:10px;color:#2e7d32;">Concluído &#10003;</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>
</div>

<?php include __DIR__ . '/_acess.php'; ?>
<?php if (file_exists(__DIR__ . '/processando_overlay.php')) include __DIR__ . '/processando_overlay.php'; ?>

<script>
(function() {
  var respInput = document.getElementById('resp-global');
  if (respInput) {
    var salvo = localStorage.getItem('responsavel_painel_lotes') || localStorage.getItem('responsavel_devolucao') || '';
    if (salvo) respInput.value = salvo;
    respInput.addEventListener('input', function() { localStorage.setItem('responsavel_painel_lotes', this.value); });
  }
  function getResp() { return respInput ? respInput.value.trim() : ''; }

  /* Filtro status */
  var filtroAtual = '';
  window.filtrarStatus = function(status) {
    filtroAtual = status;
    var rows = document.querySelectorAll('#tabela-lotes tbody tr');
    var visivel = 0;
    for (var i = 0; i < rows.length; i++) {
      var st = rows[i].getAttribute('data-status') || '';
      if (status === '' || st === status) { rows[i].className = ''; visivel++; }
      else { rows[i].className = 'hidden-row'; }
    }
    var contEl = document.getElementById('contador-visivel');
    if (contEl) contEl.textContent = String(visivel);
    var labelEl = document.getElementById('filtro-label');
    var labels = {'na_estante':'— Na estante','em_transito':'— Em trânsito','retornou':'— Retornou','':''};
    if (labelEl) labelEl.textContent = labels[status] !== undefined ? labels[status] : '';
    var btns = {'':'fb-todos','na_estante':'fb-estante','em_transito':'fb-transito','retornou':'fb-retornou'};
    var allBtns = ['fb-todos','fb-estante','fb-transito','fb-retornou'];
    for (var j = 0; j < allBtns.length; j++) { var el = document.getElementById(allBtns[j]); if (el) el.className = 'fbtn'; }
    var atvEl = document.getElementById(btns[status] !== undefined ? btns[status] : 'fb-todos');
    if (atvEl) atvEl.className = 'fbtn ativo';
    var kpiMap = {'':'kpi-todos','na_estante':'kpi-estante','em_transito':'kpi-transito','retornou':'kpi-retornou'};
    var allKpis = ['kpi-todos','kpi-estante','kpi-transito','kpi-retornou'];
    for (var k = 0; k < allKpis.length; k++) { var kEl = document.getElementById(allKpis[k]); if (kEl) kEl.className = kEl.className.replace(/\s*ativo/g,''); }
    var kAtivo = document.getElementById(kpiMap[status] !== undefined ? kpiMap[status] : 'kpi-todos');
    if (kAtivo) kAtivo.className += ' ativo';
  };

  /* Confirmar Despacho */
  window.confirmarDespacho = function(id, btn) {
    var resp = getResp();
    if (!resp) { alert('Preencha o nome do responsável acima.'); if (respInput) respInput.focus(); return; }
    if (!confirm('Confirmar despacho físico via Correios hoje?\nResponsável: ' + resp)) return;
    btn.disabled = true; btn.textContent = '...';
    var fd = new FormData();
    fd.append('acao','confirmar_despacho'); fd.append('id',String(id)); fd.append('responsavel',resp);
    fetch(window.location.pathname, {method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d) {
        var msgEl = document.getElementById('msg-'+id);
        if (d && d.ok) {
          btn.textContent='Despachado!'; btn.style.background='#2e7d32';
          if (msgEl) { msgEl.style.display='inline'; msgEl.textContent='✔ '+String(d.data||''); }
        } else {
          btn.disabled=false; btn.textContent='Confirmar Despacho';
          if (msgEl) { msgEl.style.display='inline'; msgEl.className='msg-inline erro'; msgEl.textContent=d&&d.msg?d.msg:'Erro.'; }
        }
      }).catch(function(){btn.disabled=false;btn.textContent='Confirmar Despacho';});
  };

  /* Desfazer Despacho */
  window.desfazerDespacho = function(id, btn, conf_needed) {
    if (conf_needed && !confirm('Devolver este lote à estante?')) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('acao','desfazer_despacho'); fd.append('id',String(id));
    fetch(window.location.pathname, {method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d) {
        var msgEl = document.getElementById('msg-'+id);
        if (d && d.ok) {
          if (msgEl) { msgEl.style.display='inline'; msgEl.textContent='↩ Devolvido'; }
          setTimeout(function(){window.location.reload();},1200);
        } else {
          btn.disabled=false;
          if (msgEl) { msgEl.style.display='inline'; msgEl.className='msg-inline erro'; msgEl.textContent=d&&d.msg?d.msg:'Erro.'; }
        }
      }).catch(function(){btn.disabled=false;});
  };

  /* Busca por lote */
  window.irParaLote = function() {
    var v = document.getElementById('inp-busca-lote');
    if (!v || !v.value.trim()) return;
    var url = window.location.pathname + '?lote='+encodeURIComponent(v.value.trim());
    url += '&dias=<?php echo $filtro_dias; ?>';
    if ('<?php echo addslashes($filtro_grupo); ?>' !== '') url += '&grupo=<?php echo urlencode($filtro_grupo); ?>';
    window.location.href = url;
  };
  var buscaEl = document.getElementById('inp-busca-lote');
  if (buscaEl) {
    if ('<?php echo ePL($filtro_lote); ?>') buscaEl.value = '<?php echo ePL($filtro_lote); ?>';
    buscaEl.addEventListener('keypress', function(e){if(e.keyCode===13)irParaLote();});
  }
})();
</script>
</body>
</html>
