<?php
// =========================================================================
// auditoria_displays.php — Versao 1.0.0
// Auditoria (SOMENTE LEITURA) que compara o arquivo mestre
// "displays_consolidado_por_posto.txt" com o que esta cadastrado no banco
// (tabela cadastroMalotes), mostrando, por posto:
//   - Esperado (arquivo): quantos displays o arquivo lista para o posto.
//   - No banco (mesmo posto): quantos desses ja estao cadastrados no posto certo.
//   - Faltando: estao no arquivo mas NAO existem em cadastroMalotes.
//   - Em outro posto: existem em cadastroMalotes, mas sob OUTRO posto (divergencia).
// E uma secao "Sobrando no banco": displays do cadastroMalotes que NAO estao
// no arquivo (agrupados por posto).
//
// NAO altera nada no banco. A comparacao e feita pela `leitura` (codigo de 35
// digitos, unico), entao nao depende de normalizacao de posto.
// SQL defensivo (SHOW TABLES, try/catch). Banco da LAN (10.15.61.169) e
// inacessivel fora da rede -> a pagina mostra um aviso e segue (so o arquivo).
// Compativel com PHP 5.3.3 (array(), sem closures/short-array) e IE8+.
// =========================================================================
header('Cache-Control: no-cache, no-store, must-revalidate');
@date_default_timezone_set('America/Sao_Paulo');
require_once dirname(__FILE__) . '/config/db_config.php';

function adEsc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function adTabExiste($pdo, $t) {
    try { $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t)); return ($st && $st->fetch()) ? true : false; }
    catch (Exception $e) { return false; }
}

// Normaliza o posto: numerico -> 3 digitos (LPAD); textual -> MAIUSCULAS sem espacos.
function adPostoKey($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') { return ''; }
    if (preg_match('/^[0-9]+$/', $raw)) {
        $n = (int)$raw;
        if ($n <= 0) { return ''; }
        return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    }
    return strtoupper(preg_replace('/\s+/', '', $raw));
}

// So os 35 digitos da direita (mesmo criterio do cadastro de displays).
function adLeituraNorm($raw) {
    $d = preg_replace('/[^0-9]/', '', (string)$raw);
    if (strlen($d) < 35) { return ''; }
    return substr($d, -35);
}

$ARQUIVO = dirname(__FILE__) . '/displays_consolidado_por_posto.txt';
$LIM_LISTA = 100; // maximo de codigos listados por posto na secao de detalhes

// ---------- 1) Parse do arquivo mestre ----------
$fileByLeitura = array();   // leitura => postoKey
$filePostos    = array();   // postoKey => array(label, num, leituras[])
$arqExiste = is_file($ARQUIVO);
$arqLinhas = 0; $arqDisplays = 0;

if ($arqExiste) {
    $fh = @fopen($ARQUIVO, 'r');
    if ($fh) {
        $postoAtual = '';
        while (($linha = fgets($fh)) !== false) {
            $arqLinhas++;
            $l = trim($linha);
            if ($l === '') { continue; }
            if (preg_match('/^POSTO\s+(.+?)\s*$/i', $l, $m)) {
                $postoAtual = adPostoKey($m[1]);
                if ($postoAtual !== '' && !isset($filePostos[$postoAtual])) {
                    $isNum = preg_match('/^[0-9]+$/', $postoAtual);
                    $filePostos[$postoAtual] = array(
                        'label'    => $isNum ? $postoAtual : ucfirst(strtolower($postoAtual)),
                        'num'      => $isNum ? (int)$postoAtual : -1,
                        'leituras' => array()
                    );
                }
                continue;
            }
            $leit = adLeituraNorm($l);
            if ($leit === '' || $postoAtual === '') { continue; }
            // dedup dentro do posto e no global (1 leitura pertence a 1 posto)
            if (!isset($fileByLeitura[$leit])) {
                $fileByLeitura[$leit] = $postoAtual;
                $filePostos[$postoAtual]['leituras'][$leit] = true;
                $arqDisplays++;
            }
        }
        fclose($fh);
    }
}

// ---------- 2) Conexao ----------
$dbOk = false; $erroMsg = ''; $pdo = null;
try {
    $cred = getDbCredentials();
    $pdo = new PDO("mysql:host=" . $cred['host'] . ";dbname=" . $cred['name'] . ";charset=utf8", $cred['user'], $cred['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;
} catch (Exception $e) { $dbOk = false; $erroMsg = $e->getMessage(); }

// ---------- 3) Leitura do banco (cadastroMalotes: leitura -> posto) ----------
$dbByLeitura = array();   // leitura => postoKey (do banco)
$dbConflito = array();    // leitura => true (cadastrada >1x com postos diferentes)
$dbDuplicados = 0;        // total de linhas duplicadas (mesma leitura)
$erroLeituraDb = '';      // mensagem de erro ao ler cadastroMalotes (se houver)
$avisoTabela = '';
$temCad = false;
$dbg = array();

if ($dbOk) {
    $temCad = adTabExiste($pdo, 'cadastroMalotes');
    $dbg[] = 'cadastroMalotes=' . ($temCad ? 'sim' : 'nao');
    if (!$temCad) {
        $avisoTabela = 'A tabela cadastroMalotes nao foi encontrada neste banco.';
    } else {
        try {
            // ORDER BY torna a escolha em caso de duplicata DETERMINISTICA.
            $st = $pdo->query("SELECT leitura, posto FROM cadastroMalotes ORDER BY posto, leitura");
            $nrows = 0;
            if ($st) {
                while ($r = $st->fetch()) {
                    $nrows++;
                    $leit = adLeituraNorm($r['leitura']);
                    if ($leit === '') { continue; }
                    $pk = adPostoKey($r['posto']);
                    if (!isset($dbByLeitura[$leit])) {
                        $dbByLeitura[$leit] = $pk;
                    } else {
                        // mesma leitura cadastrada mais de uma vez no banco
                        if ($dbByLeitura[$leit] !== $pk && !isset($dbConflito[$leit])) { $dbConflito[$leit] = true; }
                        $dbDuplicados++;
                    }
                }
            }
            $dbg[] = 'cadastroMalotes lidos: ' . $nrows . ' linhas, ' . count($dbByLeitura) . ' leituras validas, ' . $dbDuplicados . ' duplicadas (' . count($dbConflito) . ' com posto divergente)';
        } catch (Exception $e) { $erroLeituraDb = $e->getMessage(); $dbg[] = 'ERRO cadastroMalotes: ' . $e->getMessage(); }
    }
} else {
    $dbg[] = 'sem conexao com o banco: ' . $erroMsg;
}

// ---------- 4) Comparacao por posto ----------
$filtroPosto = isset($_GET['posto']) ? trim($_GET['posto']) : '';
$filtroKey   = ($filtroPosto !== '') ? adPostoKey($filtroPosto) : '';
$debug = isset($_GET['debug']);

$relatorio = array();   // postoKey => array(label,num,esperado,no_banco,faltando[],divergente[])
foreach ($filePostos as $pk => $info) {
    $esperado = count($info['leituras']);
    $noBanco = 0; $faltando = array(); $divergente = array();
    foreach ($info['leituras'] as $leit => $_x) {
        if (!isset($dbByLeitura[$leit])) {
            $faltando[] = $leit;
        } elseif ($dbByLeitura[$leit] === $pk) {
            $noBanco++;
        } else {
            $divergente[] = array('leitura' => $leit, 'posto_banco' => $dbByLeitura[$leit]);
        }
    }
    $relatorio[$pk] = array(
        'label'      => $info['label'],
        'num'        => $info['num'],
        'esperado'   => $esperado,
        'no_banco'   => $noBanco,
        'faltando'   => $faltando,
        'divergente' => $divergente
    );
}

// Ordena: CENTRAL/textuais (num=-1) primeiro, depois numerico crescente.
function adCmp($a, $b) {
    if ($a['num'] === $b['num']) { return strcmp((string)$a['label'], (string)$b['label']); }
    if ($a['num'] === -1) { return -1; }
    if ($b['num'] === -1) { return 1; }
    return ($a['num'] < $b['num']) ? -1 : 1;
}
uasort($relatorio, 'adCmp');

// ---------- 5) Sobrando no banco (em cadastroMalotes mas nao no arquivo) ----------
$sobrando = array();   // postoKey => array(count, exemplos[])
$totSobrando = 0;
if ($temCad) {
    foreach ($dbByLeitura as $leit => $pk) {
        if (!isset($fileByLeitura[$leit])) {
            if (!isset($sobrando[$pk])) { $sobrando[$pk] = array('count' => 0, 'exemplos' => array()); }
            $sobrando[$pk]['count']++;
            if (count($sobrando[$pk]['exemplos']) < $LIM_LISTA) { $sobrando[$pk]['exemplos'][] = $leit; }
            $totSobrando++;
        }
    }
    // ordena sobrando por posto
    $tmp = array();
    foreach ($sobrando as $pk => $v) { $tmp[$pk] = array('label' => $pk, 'num' => (preg_match('/^[0-9]+$/', $pk) ? (int)$pk : -1), 'count' => $v['count'], 'exemplos' => $v['exemplos']); }
    uasort($tmp, 'adCmp');
    $sobrando = $tmp;
}

// ---------- 6) Totais ----------
$totEsperado = 0; $totNoBanco = 0; $totFaltando = 0; $totDivergente = 0; $postosComProblema = 0;
foreach ($relatorio as $pk => $r) {
    $totEsperado   += $r['esperado'];
    $totNoBanco    += $r['no_banco'];
    $totFaltando   += count($r['faltando']);
    $totDivergente += count($r['divergente']);
    if (count($r['faltando']) > 0 || count($r['divergente']) > 0) { $postosComProblema++; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Auditoria de displays (arquivo x banco)</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; background:#eef2f6; color:#243140; margin:0; padding:18px; }
  .page { max-width: 1040px; margin:0 auto; }
  .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
  h1 { font-size:20px; margin:0; }
  h2 { font-size:16px; margin:22px 0 10px; }
  .sub { font-size:13px; color:#6b7a88; margin-top:4px; }
  .btn-voltar { display:inline-block; background:#1b3a57; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; }
  .aviso { background:#fff3cd; border:1px solid #ffe08a; color:#7a5a00; padding:12px 14px; border-radius:8px; margin-bottom:14px; font-size:13px; }
  .erro { background:#fbe6e3; border:1px solid #f3b4ab; color:#9c2a1a; }
  .cards { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
  .kpi { flex:1; min-width:130px; background:#fff; border:1px solid #d9e2ec; border-radius:10px; padding:14px; }
  .kpi .n { font-size:24px; font-weight:800; }
  .kpi .l { font-size:12px; color:#6b7a88; text-transform:uppercase; letter-spacing:.4px; font-weight:700; }
  .kpi.ok .n { color:#1f8f4e; } .kpi.falta .n { color:#c0392b; } .kpi.div .n { color:#b9770e; } .kpi.esp .n { color:#1b3a57; }
  form.filtro { background:#fff; border:1px solid #d9e2ec; border-radius:10px; padding:12px 14px; margin-bottom:16px; }
  form.filtro input[type=text] { padding:8px; border:1px solid #cbd6e2; border-radius:6px; font-size:14px; width:160px; }
  form.filtro button { padding:8px 14px; border:0; background:#1b3a57; color:#fff; border-radius:6px; font-weight:600; cursor:pointer; }
  form.filtro a.limpar { font-size:12px; color:#6b7a88; margin-left:8px; }
  table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #d9e2ec; border-radius:10px; overflow:hidden; }
  th, td { padding:9px 12px; text-align:left; border-bottom:1px solid #eef2f6; font-size:14px; }
  th { background:#f5f8fc; font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#566b80; }
  td.num, th.num { text-align:right; }
  tr.total td { font-weight:800; background:#f5f8fc; }
  tr.prob td { background:#fff7f5; }
  .pill { display:inline-block; min-width:22px; text-align:center; padding:2px 8px; border-radius:10px; font-weight:700; font-size:12px; }
  .pill.ok { background:#e3f6ea; color:#1f8f4e; }
  .pill.falta { background:#fbe6e3; color:#c0392b; }
  .pill.div { background:#fdf0d8; color:#b9770e; }
  .pill.zero { background:#eef2f6; color:#90a0b0; }
  .vazio { padding:18px; color:#6b7a88; font-size:14px; }
  .det { background:#fff; border:1px solid #d9e2ec; border-radius:10px; padding:12px 14px; margin-bottom:10px; }
  .det h3 { margin:0 0 6px; font-size:14px; }
  .codes { font-family: "Courier New", monospace; font-size:12px; color:#3a4654; word-break:break-all; line-height:1.7; }
  .tag-falta { color:#c0392b; font-weight:700; }
  .tag-div { color:#b9770e; font-weight:700; }
  .mini { font-size:12px; color:#6b7a88; }
  details { margin-top:8px; }
  summary { cursor:pointer; font-weight:600; font-size:13px; color:#1b3a57; }
</style>
</head>
<body>
<div class="page">
  <div class="top">
    <div>
      <h1>🔍 Auditoria de displays (arquivo x banco)</h1>
      <div class="sub">Compara o arquivo mestre <strong><?php echo adEsc(basename($ARQUIVO)); ?></strong> com a tabela <strong>cadastroMalotes</strong>. Somente leitura — não altera nada.</div>
    </div>
    <a class="btn-voltar" href="inicio.php">&#8592; Voltar ao início</a>
  </div>

<?php if (!$arqExiste): ?>
  <div class="aviso erro">Arquivo mestre não encontrado: <?php echo adEsc(basename($ARQUIVO)); ?>. Coloque-o na pasta do sistema.</div>
<?php endif; ?>
<?php if (!$dbOk): ?>
  <div class="aviso">Não foi possível conectar ao banco (<?php echo adEsc($cred['host']); ?>). Sem o banco, só dá para mostrar o que o arquivo lista (coluna “Esperado”). Esta auditoria só compara dentro da rede do Poupatempo.</div>
<?php elseif ($avisoTabela !== ''): ?>
  <div class="aviso"><?php echo adEsc($avisoTabela); ?></div>
<?php elseif ($erroLeituraDb !== ''): ?>
  <div class="aviso erro">Erro ao ler cadastroMalotes: <?php echo adEsc($erroLeituraDb); ?>. Os números abaixo podem estar incompletos.</div>
<?php endif; ?>
<?php if ($dbOk && $temCad && count($dbConflito) > 0): ?>
  <div class="aviso"><?php echo number_format(count($dbConflito),0,',','.'); ?> display(s) estão cadastrados em mais de um posto no banco (conflito). Foi considerada a primeira ocorrência por ordem de posto.</div>
<?php endif; ?>

  <div class="cards">
    <div class="kpi esp"><div class="n"><?php echo number_format($totEsperado,0,',','.'); ?></div><div class="l">Esperado (arquivo)</div></div>
    <div class="kpi ok"><div class="n"><?php echo number_format($totNoBanco,0,',','.'); ?></div><div class="l">No banco (ok)</div></div>
    <div class="kpi falta"><div class="n"><?php echo number_format($totFaltando,0,',','.'); ?></div><div class="l">Faltando no banco</div></div>
    <div class="kpi div"><div class="n"><?php echo number_format($totDivergente,0,',','.'); ?></div><div class="l">Em outro posto</div></div>
    <div class="kpi falta"><div class="n"><?php echo number_format($totSobrando,0,',','.'); ?></div><div class="l">Sobrando no banco</div></div>
  </div>

  <form class="filtro" method="get" action="auditoria_displays.php">
    <label style="font-size:13px; font-weight:600;">Filtrar posto:
      <input type="text" name="posto" value="<?php echo adEsc($filtroPosto); ?>" placeholder="Ex.: 790 ou CENTRAL" autocomplete="off">
    </label>
    <button type="submit">Filtrar</button>
    <?php if ($filtroPosto !== ''): ?><a class="limpar" href="auditoria_displays.php">limpar</a><?php endif; ?>
  </form>

  <table>
    <thead>
      <tr>
        <th>Posto</th>
        <th class="num">Esperado</th>
        <th class="num">No banco (ok)</th>
        <th class="num">Faltando</th>
        <th class="num">Em outro posto</th>
        <th>Situação</th>
      </tr>
    </thead>
    <tbody>
<?php
    $exibiu = 0;
    foreach ($relatorio as $pk => $r) {
        if ($filtroKey !== '' && $pk !== $filtroKey) { continue; }
        $exibiu++;
        $nFalta = count($r['faltando']);
        $nDiv   = count($r['divergente']);
        $problema = ($nFalta > 0 || $nDiv > 0);
        if ($dbOk && $temCad) {
            if (!$problema) { $sit = '<span class="pill ok">OK</span>'; }
            else {
                $sit = '';
                if ($nFalta > 0) { $sit .= '<span class="pill falta">' . $nFalta . ' faltando</span> '; }
                if ($nDiv > 0)   { $sit .= '<span class="pill div">' . $nDiv . ' em outro posto</span>'; }
            }
        } else {
            $sit = '<span class="pill zero">sem banco</span>';
        }
        echo '<tr' . ($problema && $dbOk && $temCad ? ' class="prob"' : '') . '>';
        echo '<td><strong>' . adEsc($r['label']) . '</strong></td>';
        echo '<td class="num">' . number_format($r['esperado'],0,',','.') . '</td>';
        echo '<td class="num">' . (($dbOk && $temCad) ? number_format($r['no_banco'],0,',','.') : '—') . '</td>';
        echo '<td class="num">' . (($dbOk && $temCad) ? number_format($nFalta,0,',','.') : '—') . '</td>';
        echo '<td class="num">' . (($dbOk && $temCad) ? number_format($nDiv,0,',','.') : '—') . '</td>';
        echo '<td>' . $sit . '</td>';
        echo '</tr>';
    }
    if ($exibiu === 0) {
        echo '<tr><td colspan="6" class="vazio">';
        if (!$arqExiste) { echo 'Arquivo mestre não encontrado.'; }
        elseif ($filtroKey !== '') { echo 'Posto não encontrado no arquivo mestre.'; }
        else { echo 'Nenhum posto no arquivo mestre.'; }
        echo '</td></tr>';
    }
?>
    </tbody>
<?php if ($exibiu > 1): ?>
    <tfoot>
      <tr class="total">
        <td>Total</td>
        <td class="num"><?php echo number_format($totEsperado,0,',','.'); ?></td>
        <td class="num"><?php echo ($dbOk && $temCad) ? number_format($totNoBanco,0,',','.') : '—'; ?></td>
        <td class="num"><?php echo ($dbOk && $temCad) ? number_format($totFaltando,0,',','.') : '—'; ?></td>
        <td class="num"><?php echo ($dbOk && $temCad) ? number_format($totDivergente,0,',','.') : '—'; ?></td>
        <td><?php echo ($dbOk && $temCad) ? ($postosComProblema . ' posto(s) com pendência') : ''; ?></td>
      </tr>
    </tfoot>
<?php endif; ?>
  </table>

<?php if ($dbOk && $temCad): ?>
  <h2>Detalhes por posto (faltando / em outro posto)</h2>
<?php
    $temDetalhe = false;
    foreach ($relatorio as $pk => $r) {
        if ($filtroKey !== '' && $pk !== $filtroKey) { continue; }
        $nFalta = count($r['faltando']);
        $nDiv   = count($r['divergente']);
        if ($nFalta === 0 && $nDiv === 0) { continue; }
        $temDetalhe = true;
        echo '<div class="det">';
        echo '<h3>Posto ' . adEsc($r['label']) . ' <span class="mini">(esperado ' . (int)$r['esperado'] . ', ok ' . (int)$r['no_banco'] . ')</span></h3>';
        if ($nFalta > 0) {
            echo '<details><summary><span class="tag-falta">' . $nFalta . ' faltando no banco</span> — clique para ver os códigos</summary>';
            echo '<div class="codes">';
            $i = 0;
            foreach ($r['faltando'] as $leit) {
                if ($i >= $LIM_LISTA) { echo '<br>… e mais ' . ($nFalta - $LIM_LISTA) . ' (limite de ' . $LIM_LISTA . ' exibidos).'; break; }
                echo adEsc($leit) . '<br>';
                $i++;
            }
            echo '</div></details>';
        }
        if ($nDiv > 0) {
            echo '<details><summary><span class="tag-div">' . $nDiv . ' em outro posto</span> — clique para ver os códigos</summary>';
            echo '<div class="codes">';
            $i = 0;
            foreach ($r['divergente'] as $d) {
                if ($i >= $LIM_LISTA) { echo '<br>… e mais ' . ($nDiv - $LIM_LISTA) . ' (limite de ' . $LIM_LISTA . ' exibidos).'; break; }
                echo adEsc($d['leitura']) . ' &rarr; banco: <strong>' . adEsc($d['posto_banco']) . '</strong><br>';
                $i++;
            }
            echo '</div></details>';
        }
        echo '</div>';
    }
    if (!$temDetalhe) {
        echo '<div class="det"><span class="mini">Nenhuma pendência' . ($filtroKey !== '' ? ' para este posto' : '') . '. Tudo do arquivo está no banco, no posto certo. 🎉</span></div>';
    }
?>

  <h2>Sobrando no banco (não estão no arquivo)</h2>
<?php
    $temSobra = false;
    foreach ($sobrando as $pk => $v) {
        if ($filtroKey !== '' && $pk !== $filtroKey) { continue; }
        $temSobra = true;
        echo '<div class="det">';
        echo '<h3>Posto ' . adEsc($pk) . ' <span class="mini">(' . (int)$v['count'] . ' display(s) no banco fora do arquivo)</span></h3>';
        echo '<details><summary>Ver códigos</summary><div class="codes">';
        $i = 0;
        foreach ($v['exemplos'] as $leit) {
            echo adEsc($leit) . '<br>';
            $i++;
        }
        if ($v['count'] > count($v['exemplos'])) { echo '… e mais ' . ($v['count'] - count($v['exemplos'])) . ' (limite de ' . $LIM_LISTA . ' exibidos).'; }
        echo '</div></details>';
        echo '</div>';
    }
    if (!$temSobra) {
        echo '<div class="det"><span class="mini">Nada sobrando' . ($filtroKey !== '' ? ' para este posto' : '') . '.</span></div>';
    }
?>
<?php endif; ?>

<?php if ($debug): ?>
  <div class="aviso" style="white-space:pre-wrap; background:#eef; border-color:#99c; color:#225; margin-top:14px;">DEBUG (remova ?debug=1 depois de conferir):
arquivo: <?php echo adEsc($arqExiste ? 'encontrado' : 'NAO encontrado'); ?> (<?php echo (int)$arqLinhas; ?> linhas, <?php echo (int)$arqDisplays; ?> displays, <?php echo count($filePostos); ?> postos)
<?php echo adEsc(implode("\n", $dbg)); ?></div>
<?php endif; ?>
</div>
</body>
</html>
