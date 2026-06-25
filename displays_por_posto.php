<?php
// =========================================================================
// displays_por_posto.php — Versao 1.0.0
// Pesquisa: quantidade de displays (etiquetas Correios) por posto, separando
// quantos estao LOCAIS (em estoque) e quantos estao EM TRANSITO (enviados e
// ainda nao retornados).
//
// Regra (mesma logica de lacres_novo.php):
//   - ciMalotes.tipo = 1 (saida/envio) / 2 (retorno). O estado atual de uma
//     etiqueta e dado pelo ULTIMO movimento (MAX(id)) daquela `leitura`.
//   - EM TRANSITO: ultimo movimento tipo=1 E etiqueta NAO consta no inventario
//     fisico (ciInventarioDisplays).
//   - CADASTRADO: total de etiquetas do posto em cadastroMalotes.
//   - LOCAL = max(0, Cadastrado - Em transito).
//   - Chave do posto: posto numerico de cadastroMalotes; se ausente/textual,
//     usa o posto do movimento. Padronizado em 3 digitos (LPAD).
//
// Somente LEITURA. SQL defensivo (SHOW TABLES, prepared, try/catch). O banco
// da LAN (10.15.61.169) e inacessivel fora da rede do usuario — nesse caso a
// pagina mostra um aviso e segue funcional (sem dados).
// Compativel com PHP 5.3.3 (array(), sem closures/short-array) e IE8+.
// =========================================================================
header('Cache-Control: no-cache, no-store, must-revalidate');
@date_default_timezone_set('America/Sao_Paulo');
require_once dirname(__FILE__) . '/db_config.php';

function dpEsc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function dpTabExiste($pdo, $t) {
    try { $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t)); return ($st && $st->fetch()) ? true : false; }
    catch (Exception $e) { return false; }
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

// ---------- Filtro de posto ----------
$filtroPosto = isset($_GET['posto']) ? preg_replace('/[^0-9]/', '', $_GET['posto']) : '';
$filtroNum   = ($filtroPosto !== '') ? (int)$filtroPosto : -1;
$debug = isset($_GET['debug']);

// ---------- Coleta dos dados ----------
// v1.1.0: alem das CONTAGENS, coletamos a LISTA de cada display (leitura de 35
// digitos) por posto, separando quem esta LOCAL (em estoque) de quem esta EM
// TRANSITO. Isso permite expandir cada posto e ver os codigos numericos.
$linhas = array();          // postoNum => array(pad, cadastrado, transito, local, locais_list, transito_list)
$cadSet = array();          // postoNum => array(leitura => true)  (cadastrados, sem duplicar)
$trSet  = array();          // postoNum => array(leitura => true)  (em transito, sem duplicar)
$avisoTabela = '';
$temDados = false;
$dbg = array();

if ($dbOk) {
    $temCad = dpTabExiste($pdo, 'cadastroMalotes');
    $temMov = dpTabExiste($pdo, 'ciMalotes');
    $temInv = dpTabExiste($pdo, 'ciInventarioDisplays');
    $dbg[] = 'tabelas: cadastroMalotes=' . ($temCad ? 'sim' : 'nao') . ', ciMalotes=' . ($temMov ? 'sim' : 'nao') . ', ciInventarioDisplays=' . ($temInv ? 'sim' : 'nao');

    if (!$temCad && !$temMov) {
        $avisoTabela = 'As tabelas de displays (cadastroMalotes / ciMalotes) nao foram encontradas neste banco.';
    } else {
        // garante a estrutura de um posto
        // (usa um helper inline para nao repetir a inicializacao)
        // A) Cadastrados por posto — agora trazendo a LEITURA (display) de cada um.
        if ($temCad) {
            try {
                $st = $pdo->query("SELECT posto, leitura FROM cadastroMalotes");
                $nRows = 0;
                if ($st) {
                    foreach ($st->fetchAll() as $r) {
                        $raw = trim((string)$r['posto']);
                        if ($raw === '' || !preg_match('/^[0-9]+$/', $raw)) { continue; }
                        $num = (int)$raw;
                        if ($num <= 0) { continue; }
                        $leit = preg_replace('/\D+/', '', (string)$r['leitura']);
                        if ($leit === '') { continue; }
                        if (!isset($cadSet[$num])) { $cadSet[$num] = array(); }
                        $cadSet[$num][$leit] = true;
                        $nRows++;
                    }
                }
                $dbg[] = 'cadastrados: ' . $nRows . ' linhas lidas, ' . count($cadSet) . ' postos numericos validos';
            } catch (Exception $e) { $dbg[] = 'ERRO cadastrados: ' . $e->getMessage(); }
        }

        // B) Em transito — ultimo movimento tipo=1, fora do inventario fisico; posto resolvido no PHP
        if ($temMov) {
            try {
                $joinCad  = $temCad ? "LEFT JOIN cadastroMalotes c ON c.leitura = mt.leitura" : "";
                $selCad   = $temCad ? "c.posto AS posto_cad" : "NULL AS posto_cad";
                $joinInv  = $temInv ? "LEFT JOIN ciInventarioDisplays inv ON inv.leitura = mt.leitura" : "";
                $whereInv = $temInv ? "WHERE inv.leitura IS NULL" : "";
                $sqlTr = "SELECT mt.leitura AS leitura, mt.posto AS posto_mov, " . $selCad . "
                          FROM (
                              SELECT m1.leitura, m1.posto
                              FROM ciMalotes m1
                              INNER JOIN (SELECT leitura, MAX(id) AS maxid FROM ciMalotes GROUP BY leitura) lt
                                      ON m1.id = lt.maxid
                              WHERE m1.tipo = 1
                          ) mt
                          " . $joinCad . "
                          " . $joinInv . "
                          " . $whereInv;
                $st = $pdo->query($sqlTr);
                $nTr = 0;
                if ($st) {
                    foreach ($st->fetchAll() as $r) {
                        $cand = isset($r['posto_cad']) ? trim((string)$r['posto_cad']) : '';
                        if ($cand === '' || !preg_match('/^[0-9]+$/', $cand)) { $cand = trim((string)$r['posto_mov']); }
                        if ($cand === '' || !preg_match('/^[0-9]+$/', $cand)) { continue; }
                        $num = (int)$cand;
                        if ($num <= 0) { continue; }
                        $leit = preg_replace('/\D+/', '', (string)$r['leitura']);
                        if ($leit === '') { continue; }
                        if (!isset($trSet[$num])) { $trSet[$num] = array(); }
                        $trSet[$num][$leit] = true;
                        $nTr++;
                    }
                }
                $dbg[] = 'em transito: ' . $nTr . ' etiquetas';
            } catch (Exception $e) { $dbg[] = 'ERRO transito: ' . $e->getMessage(); }
        }

        // C) Consolida por posto: LOCAIS = cadastrados que NAO estao em transito;
        //    EM TRANSITO = o conjunto em transito (mesmo que nao esteja no cadastro).
        $todosPostos = array();
        foreach ($cadSet as $num => $v) { $todosPostos[$num] = true; }
        foreach ($trSet  as $num => $v) { $todosPostos[$num] = true; }
        foreach ($todosPostos as $num => $_x) {
            $cads = isset($cadSet[$num]) ? $cadSet[$num] : array();
            $trs  = isset($trSet[$num])  ? $trSet[$num]  : array();
            $locaisList = array();
            foreach ($cads as $leit => $_t) {
                if (!isset($trs[$leit])) { $locaisList[] = $leit; }
            }
            $transitoList = array_keys($trs);
            sort($locaisList); sort($transitoList);
            // cadastrado = uniao de cadastrados + em transito (um display em transito
            // continua pertencendo ao posto mesmo se nao constar no cadastro).
            $uniao = $cads;
            foreach ($trs as $leit => $_t) { $uniao[$leit] = true; }
            $linhas[$num] = array(
                'pad'           => str_pad((string)$num, 3, '0', STR_PAD_LEFT),
                'cadastrado'    => count($uniao),
                'transito'      => count($transitoList),
                'local'         => count($locaisList),
                'locais_list'   => $locaisList,
                'transito_list' => $transitoList
            );
        }

        // Ordena por numero do posto
        ksort($linhas, SORT_NUMERIC);
        $temDados = (count($linhas) > 0);
    }
} else {
    $dbg[] = 'sem conexao com o banco: ' . $erroMsg;
}

// Totais
$totCad = 0; $totTr = 0; $totLoc = 0;
foreach ($linhas as $num => $v) {
    $totCad += $v['cadastrado'];
    $totTr  += $v['transito'];
    $totLoc += $v['local'];
}

// Formata uma leitura de display para exibicao (agrupa em blocos p/ leitura facil).
function dpFmtLeitura($leit) {
    $leit = preg_replace('/\D+/', '', (string)$leit);
    if ($leit === '') { return ''; }
    return trim(chunk_split($leit, 5, ' '));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Displays por posto (local x trânsito)</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; background:#eef2f6; color:#243140; margin:0; padding:18px; }
  .page { max-width: 980px; margin:0 auto; }
  .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
  h1 { font-size:20px; margin:0; }
  .sub { font-size:13px; color:#6b7a88; margin-top:4px; }
  .btn-voltar { display:inline-block; background:#1b3a57; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; }
  .aviso { background:#fff3cd; border:1px solid #ffe08a; color:#7a5a00; padding:12px 14px; border-radius:8px; margin-bottom:14px; font-size:13px; }
  .cards { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
  .kpi { flex:1; min-width:150px; background:#fff; border:1px solid #d9e2ec; border-radius:10px; padding:14px; }
  .kpi .n { font-size:24px; font-weight:800; }
  .kpi .l { font-size:12px; color:#6b7a88; text-transform:uppercase; letter-spacing:.4px; font-weight:700; }
  .kpi.loc .n { color:#1f8f4e; }
  .kpi.tr .n { color:#c0392b; }
  .kpi.cad .n { color:#1b3a57; }
  form.filtro { background:#fff; border:1px solid #d9e2ec; border-radius:10px; padding:12px 14px; margin-bottom:16px; }
  form.filtro input[type=text] { padding:8px; border:1px solid #cbd6e2; border-radius:6px; font-size:14px; width:160px; }
  form.filtro button { padding:8px 14px; border:0; background:#1b3a57; color:#fff; border-radius:6px; font-weight:600; cursor:pointer; }
  form.filtro a.limpar { font-size:12px; color:#6b7a88; margin-left:8px; }
  table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #d9e2ec; border-radius:10px; overflow:hidden; }
  th, td { padding:10px 12px; text-align:left; border-bottom:1px solid #eef2f6; font-size:14px; }
  th { background:#f5f8fc; font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#566b80; }
  td.num, th.num { text-align:right; }
  tr.total td { font-weight:800; background:#f5f8fc; }
  .pill { display:inline-block; min-width:22px; text-align:center; padding:2px 8px; border-radius:10px; font-weight:700; font-size:12px; }
  .pill.loc { background:#e3f6ea; color:#1f8f4e; }
  .pill.tr { background:#fbe6e3; color:#c0392b; }
  .vazio { padding:18px; color:#6b7a88; font-size:14px; }
  /* v1.1.0: linha expansivel com a lista de displays do posto */
  tr.posto-row { cursor:pointer; }
  tr.posto-row:hover td { background:#f1f6fc; }
  td.posto-cel { font-weight:700; }
  .caret { display:inline-block; width:14px; color:#6b7a88; font-weight:700; }
  tr.det-row > td { padding:0; background:#f8fafd; border-bottom:1px solid #d9e2ec; }
  tr.det-row.hidden { display:none; }
  .det-wrap { padding:12px 16px; }
  .grade { margin-bottom:12px; }
  .grade:last-child { margin-bottom:0; }
  .grade h4 { margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
  .grade.gloc h4 { color:#1f8f4e; }
  .grade.gtr  h4 { color:#c0392b; }
  .codes { display:flex; flex-wrap:wrap; gap:6px; }
  .code { font-family:"Courier New",monospace; font-size:12px; padding:4px 8px; border-radius:6px; border:1px solid #d9e2ec; background:#fff; white-space:nowrap; }
  .grade.gloc .code { border-color:#bfe7cd; }
  .grade.gtr  .code { border-color:#f2c4bd; background:#fff6f4; }
  .det-vazio { color:#6b7a88; font-size:13px; }
</style>
</head>
<body>
<div class="page">
  <div class="top">
    <div>
      <h1>📦 Displays por posto</h1>
      <div class="sub">Quantos displays cada posto tem: locais (em estoque) x em trânsito (enviados e ainda não retornados).</div>
    </div>
    <a class="btn-voltar" href="inicio.php">&#8592; Voltar ao início</a>
  </div>

<?php if (!$dbOk): ?>
  <div class="aviso">
    Não foi possível conectar ao banco (<?php echo dpEsc($cred['host']); ?>). Esta pesquisa só mostra dados dentro da rede do Poupatempo.
  </div>
<?php elseif ($avisoTabela !== ''): ?>
  <div class="aviso"><?php echo dpEsc($avisoTabela); ?></div>
<?php endif; ?>

  <div class="cards">
    <div class="kpi cad"><div class="n"><?php echo number_format($totCad,0,',','.'); ?></div><div class="l">Cadastrados</div></div>
    <div class="kpi loc"><div class="n"><?php echo number_format($totLoc,0,',','.'); ?></div><div class="l">Locais (estoque)</div></div>
    <div class="kpi tr"><div class="n"><?php echo number_format($totTr,0,',','.'); ?></div><div class="l">Em trânsito</div></div>
  </div>

  <form class="filtro" method="get" action="displays_por_posto.php">
    <label style="font-size:13px; font-weight:600;">Filtrar posto:
      <input type="text" name="posto" value="<?php echo dpEsc($filtroPosto); ?>" placeholder="Ex.: 150" autocomplete="off">
    </label>
    <button type="submit">Filtrar</button>
    <?php if ($filtroPosto !== ''): ?><a class="limpar" href="displays_por_posto.php">limpar</a><?php endif; ?>
  </form>

  <table>
    <thead>
      <tr>
        <th>Posto</th>
        <th class="num">Cadastrados</th>
        <th class="num">Locais</th>
        <th class="num">Em trânsito</th>
      </tr>
    </thead>
    <tbody>
<?php
    $exibiu = 0;
    if ($temDados) {
        $umSo = ($filtroNum >= 0); // quando filtra 1 posto, ja abre a lista
        foreach ($linhas as $num => $v) {
            if ($filtroNum >= 0 && $num !== $filtroNum) { continue; }
            $exibiu++;
            $detId = 'det-' . (int)$num;
            $aberta = $umSo; // aberto por padrao quando ha so 1 posto na tela
            echo '<tr class="posto-row" onclick="dpToggle(\'' . $detId . '\', this)">';
            echo '<td class="posto-cel"><span class="caret">' . ($aberta ? '&#9660;' : '&#9654;') . '</span> ' . dpEsc($v['pad']) . '</td>';
            echo '<td class="num">' . number_format($v['cadastrado'],0,',','.') . '</td>';
            echo '<td class="num"><span class="pill loc">' . number_format($v['local'],0,',','.') . '</span></td>';
            echo '<td class="num"><span class="pill tr">' . number_format($v['transito'],0,',','.') . '</span></td>';
            echo '</tr>';

            // Linha de detalhe: grades de LOCAIS e EM TRANSITO com os codigos.
            echo '<tr class="det-row' . ($aberta ? '' : ' hidden') . '" id="' . $detId . '"><td colspan="4"><div class="det-wrap">';
            // LOCAIS
            echo '<div class="grade gloc"><h4>Locais (em estoque) &middot; ' . count($v['locais_list']) . '</h4>';
            if (count($v['locais_list']) > 0) {
                echo '<div class="codes">';
                foreach ($v['locais_list'] as $leit) {
                    echo '<span class="code" title="' . dpEsc($leit) . '">' . dpEsc(dpFmtLeitura($leit)) . '</span>';
                }
                echo '</div>';
            } else {
                echo '<div class="det-vazio">Nenhum display local.</div>';
            }
            echo '</div>';
            // EM TRANSITO
            echo '<div class="grade gtr"><h4>Em transito (enviados, ainda nao retornaram) &middot; ' . count($v['transito_list']) . '</h4>';
            if (count($v['transito_list']) > 0) {
                echo '<div class="codes">';
                foreach ($v['transito_list'] as $leit) {
                    echo '<span class="code" title="' . dpEsc($leit) . '">' . dpEsc(dpFmtLeitura($leit)) . '</span>';
                }
                echo '</div>';
            } else {
                echo '<div class="det-vazio">Nenhum display em transito.</div>';
            }
            echo '</div>';
            echo '</div></td></tr>';
        }
    }
    if ($exibiu === 0) {
        echo '<tr><td colspan="4" class="vazio">';
        if (!$dbOk) {
            echo 'Sem dados (banco indisponível fora da rede).';
        } elseif ($filtroNum >= 0) {
            echo 'Nenhum display encontrado para o posto ' . dpEsc(str_pad((string)$filtroNum, 3, '0', STR_PAD_LEFT)) . '.';
        } else {
            echo 'Nenhum display encontrado.';
        }
        echo '</td></tr>';
    }
?>
    </tbody>
<?php if ($exibiu > 1): ?>
    <tfoot>
      <tr class="total">
        <td>Total</td>
        <td class="num"><?php echo number_format($totCad,0,',','.'); ?></td>
        <td class="num"><?php echo number_format($totLoc,0,',','.'); ?></td>
        <td class="num"><?php echo number_format($totTr,0,',','.'); ?></td>
      </tr>
    </tfoot>
<?php endif; ?>
  </table>
<?php if ($debug): ?>
  <div class="aviso" style="white-space:pre-wrap; background:#eef; border-color:#99c; color:#225; margin-top:14px;">DEBUG (remova ?debug=1 depois de conferir):
<?php echo dpEsc(implode("\n", $dbg)); ?></div>
<?php endif; ?>
</div>
<script type="text/javascript">
// Abre/fecha a lista de displays de um posto. Compativel com IE8+.
function dpToggle(id, row) {
    var el = document.getElementById(id);
    if (!el) { return; }
    var fechado = (el.className.indexOf('hidden') >= 0);
    if (fechado) {
        el.className = el.className.replace(/\s*hidden/g, '');
    } else {
        el.className = el.className + ' hidden';
    }
    // atualiza a setinha (caret) da linha clicada
    if (row) {
        var c = row.getElementsByTagName('span');
        if (c && c.length) { c[0].innerHTML = fechado ? '\u25BC' : '\u25B6'; }
    }
}
</script>
</body>
</html>
