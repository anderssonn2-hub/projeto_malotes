<?php
// =========================================================================
// dashboard.php — Versao 1.0.0
// Painel do dia: KPIs do periodo (producao, conferencia, oficios, displays).
// Somente LEITURA. SQL defensivo (SHOW TABLES / SHOW COLUMNS, try/catch por
// metrica). O banco da LAN (10.15.61.169) e inacessivel fora da rede do
// usuario — neste caso a pagina mostra um aviso e segue funcional (sem dados).
// Compativel com PHP 5.3.3 (array(), sem closures/short-array) e IE8+.
// =========================================================================
header('Cache-Control: no-cache, no-store, must-revalidate');
@date_default_timezone_set('America/Sao_Paulo');
require_once dirname(__FILE__) . '/db_config.php';

function ehD($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function dTabExiste($pdo, $t) {
    try { $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t)); return ($st && $st->fetch()) ? true : false; }
    catch (Exception $e) { return false; }
}
function dPickCol($pdo, $tab, $cands) {
    foreach ($cands as $c) {
        try {
            $st = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '', $tab) . "` LIKE " . $pdo->quote($c));
            if ($st && $st->fetch()) return $c;
        } catch (Exception $e) {}
    }
    return null;
}
// Executa uma contagem; devolve inteiro ou null se falhar/indisponivel.
function dScalar($pdo, $sql, $params) {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return ($v === false || $v === null) ? 0 : (int)$v;
    } catch (Exception $e) { return null; }
}
function fmt($v) { return ($v === null) ? '—' : number_format((int)$v, 0, ',', '.'); }
// Executa um SELECT e devolve a lista de linhas (assoc); array() vazio se falhar.
function dRows($pdo, $sql, $params) {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetchAll(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : array();
    } catch (Exception $e) { return array(); }
}

// ---------- Conexao ----------
$dbOk = false; $erroMsg = ''; $pdo = null;
try {
    $cred = getDbCredentials();
    $pdo = new PDO("mysql:host=" . $cred['host'] . ";dbname=" . $cred['name'] . ";charset=utf8", $cred['user'], $cred['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;
} catch (Exception $e) { $dbOk = false; $erroMsg = $e->getMessage(); }

// ---------- Periodo ----------
$hoje = date('Y-m-d');
$di = isset($_GET['di']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['di']) ? $_GET['di'] : $hoje;
$df = isset($_GET['df']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['df']) ? $_GET['df'] : $hoje;
if ($df < $di) { $tmp = $di; $di = $df; $df = $tmp; }

// Posto escolhido para grafico individual (somente digitos, normalizado p/ 3+ digitos)
$postoSel = '';
if (isset($_GET['posto'])) {
    $pnum = preg_replace('/\D/', '', (string)$_GET['posto']);
    if ($pnum !== '') { $postoSel = str_pad($pnum, 3, '0', STR_PAD_LEFT); }
}

// ---------- Metricas ----------
$kpi = array(
    'lotes_prod' => null, 'postos_prod' => null,
    'conferidos' => null, 'pend_conf' => null,
    'oficios' => null, 'lotes_oficio' => null,
    'disp_saida' => null, 'disp_retorno' => null
);

if ($dbOk) {
    // Producao (ciPostosCsv)
    $loteColCsv = null;
    if (dTabExiste($pdo, 'ciPostosCsv')) {
        $loteColCsv = dPickCol($pdo, 'ciPostosCsv', array('lote', 'nlote', 'nLote'));
        $dtCol  = dPickCol($pdo, 'ciPostosCsv', array('dataCarga', 'data', 'datahora', 'criado_em', 'created_at'));
        $pstCol = dPickCol($pdo, 'ciPostosCsv', array('posto', 'nposto'));
        if ($loteColCsv && $dtCol) {
            $kpi['lotes_prod'] = dScalar($pdo,
                "SELECT COUNT(DISTINCT `$loteColCsv`) FROM ciPostosCsv WHERE DATE(`$dtCol`) BETWEEN ? AND ?",
                array($di, $df));
            if ($pstCol) {
                $kpi['postos_prod'] = dScalar($pdo,
                    "SELECT COUNT(DISTINCT `$pstCol`) FROM ciPostosCsv WHERE DATE(`$dtCol`) BETWEEN ? AND ?",
                    array($di, $df));
            }
        }
    }

    // Conferencia (conferencia_pacotes)
    $confLoteCol = null; $confTab = dTabExiste($pdo, 'conferencia_pacotes');
    if ($confTab) {
        $confLoteCol = dPickCol($pdo, 'conferencia_pacotes', array('lote', 'nlote'));
        $confCol = dPickCol($pdo, 'conferencia_pacotes', array('conf', 'conferido', 'status'));
        $confDt  = dPickCol($pdo, 'conferencia_pacotes', array('data_conf', 'dataConferencia', 'data', 'datahora', 'criado_em'));
        if ($confLoteCol && $confCol) {
            $where = "(`$confCol`='s' OR `$confCol`='sim' OR `$confCol`='1' OR `$confCol`='y' OR `$confCol`='yes' OR `$confCol`='t' OR `$confCol`='true')";
            if ($confDt) {
                $kpi['conferidos'] = dScalar($pdo,
                    "SELECT COUNT(DISTINCT `$confLoteCol`) FROM conferencia_pacotes WHERE $where AND DATE(`$confDt`) BETWEEN ? AND ?",
                    array($di, $df));
            } else {
                $kpi['conferidos'] = dScalar($pdo,
                    "SELECT COUNT(DISTINCT `$confLoteCol`) FROM conferencia_pacotes WHERE $where", array());
            }
        }
    }

    // Pendentes de conferencia: produzidos no periodo sem registro de conferencia positiva
    if (isset($loteColCsv) && $loteColCsv && $confTab && $confLoteCol && isset($dtCol) && $dtCol) {
        $confCol2 = dPickCol($pdo, 'conferencia_pacotes', array('conf', 'conferido', 'status'));
        if ($confCol2) {
            $w2 = "(c.`$confCol2`='s' OR c.`$confCol2`='sim' OR c.`$confCol2`='1' OR c.`$confCol2`='y' OR c.`$confCol2`='yes' OR c.`$confCol2`='t' OR c.`$confCol2`='true')";
            $kpi['pend_conf'] = dScalar($pdo,
                "SELECT COUNT(DISTINCT p.`$loteColCsv`) FROM ciPostosCsv p
                 WHERE DATE(p.`$dtCol`) BETWEEN ? AND ?
                 AND NOT EXISTS (SELECT 1 FROM conferencia_pacotes c WHERE c.`$confLoteCol` = p.`$loteColCsv` AND $w2)",
                array($di, $df));
        }
    }

    // Oficios (ciDespachos) + lotes em oficio (ciDespachoLotes)
    if (dTabExiste($pdo, 'ciDespachos')) {
        $dsDt = dPickCol($pdo, 'ciDespachos', array('data_despacho', 'data', 'datahora', 'criado_em', 'created_at'));
        if ($dsDt) {
            $kpi['oficios'] = dScalar($pdo,
                "SELECT COUNT(*) FROM ciDespachos WHERE DATE(`$dsDt`) BETWEEN ? AND ?", array($di, $df));
        } else {
            $kpi['oficios'] = dScalar($pdo, "SELECT COUNT(*) FROM ciDespachos", array());
        }
    }
    if (dTabExiste($pdo, 'ciDespachoLotes')) {
        $dlLote = dPickCol($pdo, 'ciDespachoLotes', array('lote', 'nlote'));
        $dlDt   = dPickCol($pdo, 'ciDespachoLotes', array('data', 'data_despacho', 'datahora', 'criado_em'));
        if ($dlLote && $dlDt) {
            $kpi['lotes_oficio'] = dScalar($pdo,
                "SELECT COUNT(DISTINCT `$dlLote`) FROM ciDespachoLotes WHERE DATE(`$dlDt`) BETWEEN ? AND ?",
                array($di, $df));
        } elseif ($dlLote) {
            $kpi['lotes_oficio'] = dScalar($pdo, "SELECT COUNT(DISTINCT `$dlLote`) FROM ciDespachoLotes", array());
        }
    }

    // Movimentos de display no periodo (ciMalotes tipo=1 saida / tipo=2 retorno)
    if (dTabExiste($pdo, 'ciMalotes')) {
        $mTipo = dPickCol($pdo, 'ciMalotes', array('tipo'));
        $mDt   = dPickCol($pdo, 'ciMalotes', array('data', 'datahora', 'criado_em', 'created_at'));
        $mLeit = dPickCol($pdo, 'ciMalotes', array('leitura', 'etiqueta', 'codigo'));
        if ($mTipo && $mDt) {
            $cnt = $mLeit ? "COUNT(DISTINCT `$mLeit`)" : "COUNT(*)";
            $kpi['disp_saida'] = dScalar($pdo,
                "SELECT $cnt FROM ciMalotes WHERE `$mTipo`=1 AND DATE(`$mDt`) BETWEEN ? AND ?", array($di, $df));
            $kpi['disp_retorno'] = dScalar($pdo,
                "SELECT $cnt FROM ciMalotes WHERE `$mTipo`=2 AND DATE(`$mDt`) BETWEEN ? AND ?", array($di, $df));
        }
    }
}

// ---------- Barras: producao de carteiras por posto e por dia ----------
$barPosto = array();   // [ ['posto','nome','total'], ... ] top 15
$barDia   = array();   // [ ['dia','total'], ... ]
$totCarteiras = null;  // total de carteiras no periodo
$topPosto = null;      // posto recordista
$topDia   = null;      // dia recordista
$medidaProd = 'carteiras';
$postoOpcoes  = array(); // [ ['posto','nome'], ... ] postos com producao no periodo (p/ seletor)
$barDiaPosto  = array(); // producao por dia SO do posto escolhido
$totPostoSel  = null;    // total do posto escolhido no periodo
$nomePostoSel = '';      // nome do posto escolhido

if ($dbOk && dTabExiste($pdo, 'ciPostosCsv')) {
    $qtdCol = dPickCol($pdo, 'ciPostosCsv', array('quantidade', 'qtd', 'qtde', 'qtd_cins', 'cins', 'carteiras'));
    $dtColB = dPickCol($pdo, 'ciPostosCsv', array('dataCarga', 'data', 'datahora', 'criado_em', 'created_at'));
    $pstColB= dPickCol($pdo, 'ciPostosCsv', array('posto', 'nposto'));
    $loteB  = dPickCol($pdo, 'ciPostosCsv', array('lote', 'nlote', 'nLote'));
    $nmCol  = dPickCol($pdo, 'ciPostosCsv', array('nome', 'nomePosto', 'nome_posto', 'descricao', 'posto_nome'));
    if ($dtColB && $pstColB) {
        // Se houver coluna de quantidade, soma carteiras; senao conta lotes.
        if ($qtdCol) { $expr = "SUM(`$qtdCol`)"; $medidaProd = 'carteiras'; }
        elseif ($loteB) { $expr = "COUNT(DISTINCT `$loteB`)"; $medidaProd = 'lotes'; }
        else { $expr = "COUNT(*)"; $medidaProd = 'registros'; }

        $selNome = $nmCol ? ", MAX(`$nmCol`) AS nome" : "";
        $rows = dRows($pdo,
            "SELECT LPAD(`$pstColB`,3,'0') AS posto, $expr AS total $selNome
             FROM ciPostosCsv WHERE DATE(`$dtColB`) BETWEEN ? AND ?
             GROUP BY LPAD(`$pstColB`,3,'0') HAVING total > 0
             ORDER BY total DESC LIMIT 15",
            array($di, $df));
        foreach ($rows as $r) {
            $barPosto[] = array(
                'posto' => (string)$r['posto'],
                'nome'  => isset($r['nome']) ? (string)$r['nome'] : '',
                'total' => (int)$r['total']
            );
        }
        $totCarteiras = dScalar($pdo,
            "SELECT $expr FROM ciPostosCsv WHERE DATE(`$dtColB`) BETWEEN ? AND ?",
            array($di, $df));

        $rowsD = dRows($pdo,
            "SELECT DATE(`$dtColB`) AS dia, $expr AS total
             FROM ciPostosCsv WHERE DATE(`$dtColB`) BETWEEN ? AND ?
             GROUP BY DATE(`$dtColB`) ORDER BY dia ASC LIMIT 60",
            array($di, $df));
        foreach ($rowsD as $r) {
            $barDia[] = array('dia' => (string)$r['dia'], 'total' => (int)$r['total']);
        }

        // Lista de postos com producao no periodo (para o seletor de posto)
        $rowsOpt = dRows($pdo,
            "SELECT LPAD(`$pstColB`,3,'0') AS posto" . ($nmCol ? ", MAX(`$nmCol`) AS nome" : "") . "
             FROM ciPostosCsv WHERE DATE(`$dtColB`) BETWEEN ? AND ?
             GROUP BY LPAD(`$pstColB`,3,'0') HAVING $expr > 0
             ORDER BY LPAD(`$pstColB`,3,'0') ASC",
            array($di, $df));
        foreach ($rowsOpt as $r) {
            $postoOpcoes[] = array(
                'posto' => (string)$r['posto'],
                'nome'  => isset($r['nome']) ? (string)$r['nome'] : ''
            );
        }

        // Se um posto foi escolhido: producao por dia SO desse posto + total + nome
        if ($postoSel !== '') {
            $rowsPD = dRows($pdo,
                "SELECT DATE(`$dtColB`) AS dia, $expr AS total
                 FROM ciPostosCsv
                 WHERE DATE(`$dtColB`) BETWEEN ? AND ? AND LPAD(`$pstColB`,3,'0') = ?
                 GROUP BY DATE(`$dtColB`) ORDER BY dia ASC LIMIT 90",
                array($di, $df, $postoSel));
            foreach ($rowsPD as $r) {
                $barDiaPosto[] = array('dia' => (string)$r['dia'], 'total' => (int)$r['total']);
            }
            $totPostoSel = dScalar($pdo,
                "SELECT $expr FROM ciPostosCsv
                 WHERE DATE(`$dtColB`) BETWEEN ? AND ? AND LPAD(`$pstColB`,3,'0') = ?",
                array($di, $df, $postoSel));
            if ($nmCol) {
                $nrow = dRows($pdo,
                    "SELECT MAX(`$nmCol`) AS nome FROM ciPostosCsv WHERE LPAD(`$pstColB`,3,'0') = ?",
                    array($postoSel));
                if (!empty($nrow) && isset($nrow[0]['nome'])) { $nomePostoSel = (string)$nrow[0]['nome']; }
            }
        }
    }
}
if (!empty($barPosto)) { $topPosto = $barPosto[0]; }
if (!empty($barDia)) {
    foreach ($barDia as $b) { if ($topDia === null || $b['total'] > $topDia['total']) { $topDia = $b; } }
}
// Maximos para dimensionar as barras
$maxPosto = 0; foreach ($barPosto as $b) { if ($b['total'] > $maxPosto) { $maxPosto = $b['total']; } }
$maxDia   = 0; foreach ($barDia as $b) { if ($b['total'] > $maxDia) { $maxDia = $b['total']; } }
$maxDiaPosto = 0; foreach ($barDiaPosto as $b) { if ($b['total'] > $maxDiaPosto) { $maxDiaPosto = $b['total']; } }

$mesmoDia = ($di === $df);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do dia</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Trebuchet MS", Verdana, Arial, sans-serif; background: radial-gradient(circle at top, #f4f0ea 0%, #f2f6fb 45%, #eef1f5 100%); color: #1f2a35; min-height: 100vh; padding: 28px; }
.page { max-width: 1100px; margin: 0 auto; }
.header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; flex-wrap: wrap; }
h1 { font-family: "Palatino Linotype", "Book Antiqua", serif; font-size: 26px; color: #1b3a57; }
.sub { font-size: 13px; color: #51606f; margin-top: 4px; }
.btn { text-decoration: none; background: #1b3a57; color: #fff; padding: 9px 14px; border-radius: 10px; font-weight: 700; font-size: 13px; border: none; cursor: pointer; }
.btn.sec { background: #e2e8f0; color: #334155; }
.filtro { background: #fff; border-radius: 12px; padding: 14px 16px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); border: 1px solid #e1e5ea; margin-bottom: 18px; display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
.filtro label { display: block; font-size: 11px; text-transform: uppercase; color: #6b7a88; font-weight: 700; margin-bottom: 4px; }
.filtro input { padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
.atalhos a { font-size: 12px; color: #1b3a57; text-decoration: none; margin-right: 10px; font-weight: 700; }
.aviso { background: #fff3cd; color: #7a5a00; border: 1px solid #ffe08a; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-size: 13px; }
.grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.kpi { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 10px 24px rgba(0,0,0,0.10); border: 1px solid #e1e5ea; border-top: 4px solid #94a3b8; }
.kpi.azul { border-top-color: #2f80ed; } .kpi.verde { border-top-color: #00897b; } .kpi.laranja { border-top-color: #ff8f00; } .kpi.roxo { border-top-color: #7e57c2; } .kpi.vermelho { border-top-color: #e53935; } .kpi.escuro { border-top-color: #2c5364; }
.kpi .rotulo { font-size: 12px; color: #6b7a88; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
.kpi .valor { font-size: 38px; font-weight: 800; color: #1b3a57; margin-top: 6px; line-height: 1; }
.kpi .nota { font-size: 11px; color: #94a3b8; margin-top: 6px; }
.secao-tit { margin: 22px 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #6b7a88; font-weight: 700; }
@media (max-width: 900px) { .grid { grid-template-columns: repeat(2, 1fr); } body { padding: 16px; } }
@media (max-width: 520px) { .grid { grid-template-columns: 1fr; } }
/* Destaques (recordes) */
.grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
@media (max-width: 760px) { .grid3 { grid-template-columns: 1fr; } }
.destaque { background: linear-gradient(135deg, #1b3a57, #2c5364); color: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 10px 24px rgba(0,0,0,0.14); }
.destaque .rotulo { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; font-weight: 700; }
.destaque .valor { font-size: 30px; font-weight: 800; margin-top: 6px; line-height: 1.05; }
.destaque .nota { font-size: 12px; opacity: 0.85; margin-top: 4px; }
.destaque.verde { background: linear-gradient(135deg, #00695c, #26a69a); }
.destaque.laranja { background: linear-gradient(135deg, #e65100, #ff8f00); }
/* Barras horizontais (por posto) */
.barchart { background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 24px rgba(0,0,0,0.10); border: 1px solid #e1e5ea; margin-bottom: 16px; }
.barchart h3 { font-size: 14px; color: #1b3a57; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.barrow { display: flex; align-items: center; gap: 10px; margin-bottom: 9px; }
.barrow .lbl { width: 180px; flex: 0 0 180px; font-size: 12px; color: #334155; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bartrack { flex: 1 1 auto; background: #eef2f7; border-radius: 8px; height: 22px; overflow: hidden; }
.barfill { height: 100%; border-radius: 8px; background: linear-gradient(90deg, #2f80ed, #56ccf2); min-width: 3px; }
.barrow .val { width: 90px; flex: 0 0 90px; text-align: right; font-size: 13px; font-weight: 800; color: #1b3a57; }
a.barrow, a.barrow:link, a.barrow:visited { display: flex; text-decoration: none; color: inherit; cursor: pointer; }
a.barrow:hover .lbl { color: #2f80ed; }
a.barrow:hover .bartrack { background: #e2e8f2; }
.vazio-bar { color: #94a3b8; font-size: 13px; padding: 8px 0; }
/* Barras verticais (por dia) */
.vbars { display: flex; align-items: flex-end; gap: 10px; height: 200px; padding: 10px 4px 0; overflow-x: auto; }
.vbar { flex: 0 0 auto; width: 46px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
.vbar .vval { font-size: 11px; font-weight: 800; color: #1b3a57; margin-bottom: 4px; }
.vbar .col { width: 30px; background: linear-gradient(180deg, #4db6ac, #00897b); border-radius: 6px 6px 0 0; min-height: 3px; }
.vbar .vlbl { font-size: 10px; color: #64748b; margin-top: 6px; text-align: center; line-height: 1.2; }
@media (max-width: 520px) { .barrow .lbl { width: 110px; flex: 0 0 110px; } .barrow .val { width: 64px; flex: 0 0 64px; } }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div>
      <h1>Painel do dia</h1>
      <div class="sub">Visao rapida da producao, conferencia, oficios e displays.</div>
    </div>
    <div>
      <a class="btn sec" href="automacoes.php">&#8592; Automacoes</a>
      <a class="btn sec" href="inicio.php">Inicio</a>
    </div>
  </div>

  <form class="filtro" method="get" action="dashboard.php">
    <div>
      <label for="di">De</label>
      <input type="date" id="di" name="di" value="<?php echo ehD($di); ?>">
    </div>
    <div>
      <label for="df">Ate</label>
      <input type="date" id="df" name="df" value="<?php echo ehD($df); ?>">
    </div>
    <div>
      <label for="posto">Posto</label>
      <select id="posto" name="posto">
        <option value="">Todos os postos</option>
        <?php
          $achouSel = false;
          foreach ($postoOpcoes as $op) { if ($op['posto'] === $postoSel) { $achouSel = true; break; } }
          if ($postoSel !== '' && !$achouSel):
        ?>
          <option value="<?php echo ehD($postoSel); ?>" selected>Posto <?php echo ehD($postoSel); ?> (sem producao)</option>
        <?php endif; ?>
        <?php foreach ($postoOpcoes as $op): ?>
          <option value="<?php echo ehD($op['posto']); ?>"<?php echo ($op['posto'] === $postoSel) ? ' selected' : ''; ?>>Posto <?php echo ehD($op['posto']); ?><?php echo ($op['nome'] !== '' ? ' - ' . ehD($op['nome']) : ''); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><button type="submit" class="btn">Aplicar</button></div>
    <div class="atalhos">
      <a href="dashboard.php">Hoje</a>
      <a href="dashboard.php?di=<?php echo ehD(date('Y-m-d', strtotime('-1 day'))); ?>&df=<?php echo ehD(date('Y-m-d', strtotime('-1 day'))); ?>">Ontem</a>
      <a href="dashboard.php?di=<?php echo ehD(date('Y-m-d', strtotime('-6 day'))); ?>&df=<?php echo ehD($hoje); ?>">7 dias</a>
    </div>
  </form>

  <?php if (!$dbOk): ?>
  <div class="aviso"><b>Banco indisponivel.</b> Nao foi possivel conectar ao banco da rede (esperado fora da LAN do Poupatempo). Os numeros aparecem como &mdash; aqui; no ambiente real eles serao preenchidos.</div>
  <?php endif; ?>

  <div class="secao-tit">Producao e conferencia (<?php echo $mesmoDia ? ehD(date('d/m/Y', strtotime($di))) : ehD(date('d/m/Y', strtotime($di))) . ' a ' . ehD(date('d/m/Y', strtotime($df))); ?>)</div>
  <div class="grid">
    <div class="kpi azul"><div class="rotulo">Lotes produzidos</div><div class="valor"><?php echo fmt($kpi['lotes_prod']); ?></div><div class="nota">no periodo</div></div>
    <div class="kpi azul"><div class="rotulo">Postos produzidos</div><div class="valor"><?php echo fmt($kpi['postos_prod']); ?></div><div class="nota">postos distintos</div></div>
    <div class="kpi verde"><div class="rotulo">Lotes conferidos</div><div class="valor"><?php echo fmt($kpi['conferidos']); ?></div><div class="nota">conferencia ok</div></div>
    <div class="kpi vermelho"><div class="rotulo">Pendentes de conferencia</div><div class="valor"><?php echo fmt($kpi['pend_conf']); ?></div><div class="nota">produzidos sem conferir</div></div>
  </div>

  <div class="secao-tit">Oficios e displays</div>
  <div class="grid">
    <div class="kpi escuro"><div class="rotulo">Oficios gerados</div><div class="valor"><?php echo fmt($kpi['oficios']); ?></div><div class="nota">no periodo</div></div>
    <div class="kpi escuro"><div class="rotulo">Lotes em oficio</div><div class="valor"><?php echo fmt($kpi['lotes_oficio']); ?></div><div class="nota">despachados</div></div>
    <div class="kpi laranja"><div class="rotulo">Displays enviados</div><div class="valor"><?php echo fmt($kpi['disp_saida']); ?></div><div class="nota">saidas no periodo</div></div>
    <div class="kpi roxo"><div class="rotulo">Displays retornados</div><div class="valor"><?php echo fmt($kpi['disp_retorno']); ?></div><div class="nota">retornos no periodo</div></div>
  </div>

  <?php $unid = ($medidaProd === 'carteiras') ? 'carteiras' : ($medidaProd === 'lotes' ? 'lotes' : 'registros'); ?>

  <div class="secao-tit">Recordes de producao (<?php echo ehD($unid); ?>)</div>
  <div class="grid3">
    <div class="destaque verde">
      <div class="rotulo">Total de <?php echo ehD($unid); ?></div>
      <div class="valor"><?php echo fmt($totCarteiras); ?></div>
      <div class="nota">no periodo selecionado</div>
    </div>
    <div class="destaque">
      <div class="rotulo">Posto com maior producao</div>
      <?php if ($topPosto): ?>
        <div class="valor">Posto <?php echo ehD($topPosto['posto']); ?></div>
        <div class="nota"><?php echo ($topPosto['nome'] !== '' ? ehD($topPosto['nome']) . ' &middot; ' : ''); ?><?php echo fmt($topPosto['total']); ?> <?php echo ehD($unid); ?></div>
      <?php else: ?>
        <div class="valor">&mdash;</div>
        <div class="nota">sem dados no periodo</div>
      <?php endif; ?>
    </div>
    <div class="destaque laranja">
      <div class="rotulo">Dia de maior producao</div>
      <?php if ($topDia): ?>
        <div class="valor"><?php echo ehD(date('d/m/Y', strtotime($topDia['dia']))); ?></div>
        <div class="nota"><?php echo fmt($topDia['total']); ?> <?php echo ehD($unid); ?></div>
      <?php else: ?>
        <div class="valor">&mdash;</div>
        <div class="nota">sem dados no periodo</div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($postoSel !== ''): ?>
  <div class="secao-tit">Producao do Posto <?php echo ehD($postoSel); ?><?php echo ($nomePostoSel !== '' ? ' - ' . ehD($nomePostoSel) : ''); ?></div>
  <div class="grid3">
    <div class="destaque verde">
      <div class="rotulo">Total do posto (<?php echo ehD($unid); ?>)</div>
      <div class="valor"><?php echo fmt($totPostoSel); ?></div>
      <div class="nota">no periodo selecionado</div>
    </div>
  </div>
  <div class="barchart">
    <h3>Producao por dia &mdash; Posto <?php echo ehD($postoSel); ?></h3>
    <?php if (!empty($barDiaPosto)): ?>
      <div class="vbars">
        <?php foreach ($barDiaPosto as $b): ?>
          <?php $hp = ($maxDiaPosto > 0) ? (int)round(($b['total'] / $maxDiaPosto) * 150) : 0; if ($hp < 3 && $b['total'] > 0) { $hp = 3; } ?>
          <div class="vbar">
            <div class="vval"><?php echo fmt($b['total']); ?></div>
            <div class="col" style="height:<?php echo (int)$hp; ?>px;"></div>
            <div class="vlbl"><?php echo ehD(date('d/m', strtotime($b['dia']))); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="vazio-bar">Sem producao desse posto no periodo (ou banco indisponivel).</div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="secao-tit">Producao por posto (Top 15)</div>
  <div class="barchart">
    <h3>Carteiras por posto &mdash; maiores primeiro (clique para ver so o posto)</h3>
    <?php if (!empty($barPosto)): ?>
      <?php foreach ($barPosto as $b): ?>
        <?php $pct = ($maxPosto > 0) ? round(($b['total'] / $maxPosto) * 100) : 0; if ($pct < 2 && $b['total'] > 0) { $pct = 2; } ?>
        <a class="barrow barrow-link" href="dashboard.php?di=<?php echo ehD($di); ?>&df=<?php echo ehD($df); ?>&posto=<?php echo ehD($b['posto']); ?>" title="<?php echo ehD('Ver so o Posto ' . $b['posto'] . ($b['nome'] !== '' ? ' - ' . $b['nome'] : '')); ?>">
          <div class="lbl">Posto <?php echo ehD($b['posto']); ?><?php echo ($b['nome'] !== '' ? ' &middot; ' . ehD($b['nome']) : ''); ?></div>
          <div class="bartrack"><div class="barfill" style="width:<?php echo (int)$pct; ?>%;"></div></div>
          <div class="val"><?php echo fmt($b['total']); ?></div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="vazio-bar">Sem dados de producao no periodo (ou banco indisponivel).</div>
    <?php endif; ?>
  </div>

  <div class="secao-tit">Producao por dia</div>
  <div class="barchart">
    <h3>Carteiras produzidas a cada dia do periodo</h3>
    <?php if (!empty($barDia)): ?>
      <div class="vbars">
        <?php foreach ($barDia as $b): ?>
          <?php $h = ($maxDia > 0) ? (int)round(($b['total'] / $maxDia) * 150) : 0; if ($h < 3 && $b['total'] > 0) { $h = 3; } ?>
          <div class="vbar">
            <div class="vval"><?php echo fmt($b['total']); ?></div>
            <div class="col" style="height:<?php echo (int)$h; ?>px;"></div>
            <div class="vlbl"><?php echo ehD(date('d/m', strtotime($b['dia']))); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="vazio-bar">Sem dados de producao no periodo (ou banco indisponivel).</div>
    <?php endif; ?>
  </div>

  <div style="margin-top:18px;font-size:11px;color:#94a3b8;">&mdash; significa metrica indisponivel (tabela/coluna ausente ou banco fora). Painel somente leitura.</div>
</div>
</body>
</html>
