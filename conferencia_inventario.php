<?php
// Conferencia de Inventario - cruza inventario fisico (texto) com ciMalotes
require_once 'db_config.php';
session_start();
$pdo = getDbPdo();

// Tabela persistente do inventario fisico (usada pelo filtro "Em Transito" de
// devolucao_etiquetas.php para excluir displays que comprovadamente voltaram).
// Criada de forma tolerante: se ja existir ou faltar permissao, segue normalmente.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciInventarioDisplays (
        leitura VARCHAR(40) NOT NULL,
        posto VARCHAR(20) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT NULL,
        PRIMARY KEY (leitura)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* tolera ausencia de permissao */ }

// Parse do inventario fisico: linhas "posto XXX" + linhas com 35 digitos.
// Retorna array($inv, $invPostos): $inv = etiqueta => posto; $invPostos = posto => contagem.
function parseInventarioTexto($texto) {
    $inv = array();
    $invPostos = array();
    $postoAtual = '';
    $linhas = preg_split('/\r\n|\r|\n/', (string)$texto);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;
        if (preg_match('/^posto\s+(.+)$/i', $linha, $m)) {
            $postoAtual = trim($m[1]);
            if (!isset($invPostos[$postoAtual])) $invPostos[$postoAtual] = 0;
        } elseif (preg_match('/^\d{35}$/', $linha)) {
            $inv[$linha] = $postoAtual;
            if ($postoAtual !== '' && isset($invPostos[$postoAtual])) $invPostos[$postoAtual]++;
        }
    }
    return array($inv, $invPostos);
}

// Salva o inventario fisico colado na tabela persistente (substitui o anterior).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_inventario') {
    header('Content-Type: application/json; charset=UTF-8');
    $texto = isset($_POST['inventario']) ? (string)$_POST['inventario'] : '';
    list($inv, $invPostos) = parseInventarioTexto($texto);
    try {
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM ciInventarioDisplays");
        $stmt = $pdo->prepare("INSERT INTO ciInventarioDisplays (leitura, posto, atualizado_em) VALUES (?, ?, NOW())");
        $n = 0;
        foreach ($inv as $eti => $posto) {
            $stmt->execute(array($eti, ($posto !== '' ? $posto : null)));
            $n++;
        }
        $pdo->commit();
        echo json_encode(array('ok'=>true, 'salvos'=>$n));
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(array('ok'=>false, 'erro'=>$e->getMessage()));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'conferir') {
    header('Content-Type: application/json; charset=UTF-8');
    $texto = isset($_POST['inventario']) ? (string)$_POST['inventario'] : '';
    $diasTransito = isset($_POST['dias']) ? (int)$_POST['dias'] : 0;

    // 1) Parse do inventario
    list($inv, $invPostos) = parseInventarioTexto($texto);

    // 2) Etiquetas em transito no BD (tipo=1 sem tipo=2 posterior)
    $sqlT = "SELECT m1.leitura, m1.posto, m1.login, DATE(m1.data) AS data_envio
             FROM ciMalotes m1
             WHERE m1.tipo = 1
               AND NOT EXISTS (
                   SELECT 1 FROM ciMalotes m2
                   WHERE m2.leitura = m1.leitura AND m2.tipo = 2 AND m2.id > m1.id
               )";
    if ($diasTransito > 0) {
        $sqlT .= " AND m1.data >= DATE_SUB(NOW(), INTERVAL " . $diasTransito . " DAY)";
    }
    $sqlT .= " ORDER BY m1.data DESC";
    $st = $pdo->prepare($sqlT);
    $st->execute();
    $transito = $st->fetchAll(PDO::FETCH_ASSOC);

    $transitoMap = array();
    foreach ($transito as $t) $transitoMap[$t['leitura']] = $t;

    // 3) Pre-carrega quais etiquetas do inventario existem em ciMalotes (bulk, em chunks de 500)
    $existeMap = array();
    $invKeys = array_keys($inv);
    $chunkSize = 500;
    $total = count($invKeys);
    for ($i = 0; $i < $total; $i += $chunkSize) {
        $chunk = array_slice($invKeys, $i, $chunkSize);
        if (empty($chunk)) continue;
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stIn = $pdo->prepare("SELECT DISTINCT leitura FROM ciMalotes WHERE leitura IN ($placeholders)");
        $stIn->execute($chunk);
        while ($r = $stIn->fetch(PDO::FETCH_ASSOC)) {
            $existeMap[$r['leitura']] = true;
        }
    }

    // 4) Cross-reference (sem N+1)
    $faltaReceber = array(); $sumida = array(); $semEnvio = array(); $consistenteCount = 0;
    foreach ($inv as $eti => $posto) {
        if (isset($transitoMap[$eti])) {
            $faltaReceber[] = array(
                'leitura'=>$eti, 'posto_inventario'=>$posto,
                'enviado_por'=>$transitoMap[$eti]['login'],
                'data_envio'=>$transitoMap[$eti]['data_envio'],
                'posto_envio'=>$transitoMap[$eti]['posto']
            );
            unset($transitoMap[$eti]);
        } else {
            if (!isset($existeMap[$eti])) {
                $semEnvio[] = array('leitura'=>$eti, 'posto_inventario'=>$posto);
            } else {
                $consistenteCount++;
            }
        }
    }
    foreach ($transitoMap as $eti => $det) {
        $sumida[] = array(
            'leitura'=>$eti, 'posto_envio'=>$det['posto'],
            'enviado_por'=>$det['login'], 'data_envio'=>$det['data_envio']
        );
    }

    echo json_encode(array(
        'ok'=>true,
        'total_inventario'=>count($inv),
        'total_transito_bd'=>count($transito),
        'consistente'=>$consistenteCount,
        'falta_receber'=>$faltaReceber,
        'sumida'=>$sumida,
        'sem_envio'=>$semEnvio,
        'postos_inventario'=>$invPostos
    ));
    exit;
}

// Pre-carrega o arquivo do inventario (se existir) para facilitar
$arquivoInv = __DIR__ . '/attached_assets/Inventário_de_displays_na_empresa_1779468059030.txt';
$conteudoInicial = '';
if (file_exists($arquivoInv)) {
    $conteudoInicial = (string)file_get_contents($arquivoInv);
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Conferencia de Inventario - Displays</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;color:#2c3e50;margin:0;padding:20px;}
.container{max-width:1200px;margin:0 auto;}
h1{color:#0b3d91;margin:0 0 4px 0;font-size:22px;}
.subtitle{color:#5f7388;font-size:13px;margin-bottom:16px;}
.card{background:#fff;border:1px solid #d6e2ee;border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);}
label{font-weight:700;font-size:13px;color:#3a5068;display:block;margin-bottom:6px;}
textarea{width:100%;min-height:260px;font-family:monospace;font-size:11px;border:1px solid #c8d4e0;border-radius:6px;padding:10px;box-sizing:border-box;}
input[type=number]{padding:8px;border:1px solid #c8d4e0;border-radius:6px;width:80px;}
button{background:#0b3d91;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-weight:700;cursor:pointer;font-size:14px;}
button:hover{background:#0a3478;}
button.secondary{background:#5f7388;}
.kpis{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.kpi{background:#fff;border:1px solid #d6e2ee;border-radius:8px;padding:10px 16px;flex:1;min-width:140px;}
.kpi-label{font-size:11px;color:#5f7388;text-transform:uppercase;font-weight:700;}
.kpi-val{font-size:24px;font-weight:800;margin-top:4px;}
.box{border-radius:8px;padding:12px 16px;margin-bottom:12px;}
.box-amarelo{background:#fff8e1;border:1px solid #ffd54f;}
.box-vermelho{background:#ffebee;border:1px solid #ef9a9a;}
.box-azul{background:#e3f2fd;border:1px solid #90caf9;}
.box-verde{background:#e8f5e9;border:1px solid #a5d6a7;}
.box-titulo{font-weight:800;font-size:14px;margin-bottom:8px;}
table{width:100%;border-collapse:collapse;font-size:12px;background:#fff;}
table th{background:#0b3d91;color:#fff;padding:6px 8px;text-align:left;font-size:11px;}
table td{padding:5px 8px;border-bottom:1px solid #eee;}
table tr:nth-child(even) td{background:#fafbfd;}
.mono{font-family:monospace;font-size:11px;}
.muted{color:#888;font-style:italic;}
.barra-acoes{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px;}
.voltar{display:inline-block;margin-bottom:14px;color:#0b3d91;text-decoration:none;font-weight:700;font-size:13px;}
.voltar:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="container">
  <a href="devolucao_etiquetas.php" class="voltar">&larr; Voltar para Recebimento</a>
  <h1>Conferencia de Inventario de Displays</h1>
  <div class="subtitle">Cruza o inventario fisico (etiquetas que voce sabe que estao na empresa) com o que o banco diz estar em transito.</div>

  <div class="card">
    <label>Inventario fisico (cole abaixo - formato: "posto XXX" seguido das etiquetas, uma por linha)</label>
    <textarea id="invTexto"><?php echo htmlspecialchars($conteudoInicial, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <div class="barra-acoes">
      <label style="margin:0;">Considerar transito dos ultimos:</label>
      <input type="number" id="invDias" value="0" min="0">
      <span style="font-size:12px;color:#5f7388;">dias (0 = todos)</span>
      <button onclick="conferir()">Conferir agora</button>
      <button class="secondary" onclick="salvarInventario()" title="Salva esta lista no sistema para o filtro 'Em Transito' excluir displays que ja voltaram">&#128190; Salvar invent&aacute;rio no sistema</button>
      <span id="status" style="font-size:12px;color:#5f7388;"></span>
    </div>
  </div>

  <div id="resultado"></div>
</div>

<script>
function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}

function conferir(){
  var texto = document.getElementById('invTexto').value;
  var dias = document.getElementById('invDias').value || '0';
  var st = document.getElementById('status'); st.textContent = 'Processando...';
  var fd = new FormData();
  fd.append('acao','conferir');
  fd.append('inventario',texto);
  fd.append('dias',dias);
  fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(d){
      st.textContent = '';
      if(!d.ok){ document.getElementById('resultado').innerHTML = '<div class="box box-vermelho">Erro: '+esc(d.erro||'desconhecido')+'</div>'; return; }
      renderResultado(d);
    })
    .catch(function(){ st.textContent = 'Falha de comunicacao.'; });
}

function renderResultado(d){
  var html = '<div class="kpis">'
    + kpi('No inventario fisico', d.total_inventario, '#0b3d91')
    + kpi('Em transito (BD)', d.total_transito_bd, '#f57c00')
    + kpi('Consistente (no inv. e ja recebida)', d.consistente, '#388e3c')
    + kpi('Faltou registrar retorno', d.falta_receber.length, '#f9a825')
    + kpi('Em transito sem inv.', d.sumida.length, '#c62828')
    + kpi('No inv. sem nenhum envio', d.sem_envio.length, '#6a1b9a')
    + '</div>';

  // Bloco 1: faltou registrar retorno (no inv. mas em transito)
  html += '<div class="box box-amarelo">'
    + '<div class="box-titulo" style="color:#7d4e00;">&#9888; ' + d.falta_receber.length + ' etiqueta(s) estao no inventario fisico MAS o banco diz que estao em transito</div>'
    + '<div style="font-size:12px;color:#7d4e00;margin-bottom:8px;">Acao recomendada: marcar essas etiquetas como RECEBIDAS (tipo 2) na tela de Recebimento.</div>';
  if (d.falta_receber.length === 0) html += '<p class="muted">Nada a fazer aqui.</p>';
  else html += tabela(d.falta_receber, ['leitura','posto_inventario','posto_envio','enviado_por','data_envio'], ['Etiqueta','Posto (inv.)','Posto (envio)','Enviado por','Data envio']);
  html += '</div>';

  // Bloco 2: em transito sem estar no inventario
  html += '<div class="box box-vermelho">'
    + '<div class="box-titulo" style="color:#a01818;">&#10060; ' + d.sumida.length + ' etiqueta(s) o banco diz que estao em transito MAS NAO constam no inventario</div>'
    + '<div style="font-size:12px;color:#a01818;margin-bottom:8px;">Provavelmente estao no posto destino (ainda nao voltaram), OU estao sumidas. Verifique caso a caso.</div>';
  if (d.sumida.length === 0) html += '<p class="muted">Tudo certo aqui.</p>';
  else html += tabela(d.sumida, ['leitura','posto_envio','enviado_por','data_envio'], ['Etiqueta','Posto (envio)','Enviado por','Data envio']);
  html += '</div>';

  // Bloco 3: no inventario mas sem envio
  html += '<div class="box box-azul">'
    + '<div class="box-titulo" style="color:#0d47a1;">&#8505; ' + d.sem_envio.length + ' etiqueta(s) estao no inventario MAS nunca tiveram envio (tipo 1) registrado</div>'
    + '<div style="font-size:12px;color:#0d47a1;margin-bottom:8px;">Podem ser etiquetas novas que vieram direto, sem passar pelo registro. Considere registrar manualmente.</div>';
  if (d.sem_envio.length === 0) html += '<p class="muted">Tudo certo aqui.</p>';
  else html += tabela(d.sem_envio, ['leitura','posto_inventario'], ['Etiqueta','Posto (inv.)']);
  html += '</div>';

  // Resumo por posto
  if (d.postos_inventario){
    var postosArr = []; for (var p in d.postos_inventario){ if (d.postos_inventario.hasOwnProperty(p)) postosArr.push({posto:p, qtd:d.postos_inventario[p]}); }
    postosArr.sort(function(a,b){return b.qtd-a.qtd;});
    html += '<div class="box box-verde"><div class="box-titulo" style="color:#1b5e20;">&#128202; Resumo por posto no inventario</div>';
    html += '<table><thead><tr><th>Posto</th><th>Qtde no inventario</th></tr></thead><tbody>';
    for (var i=0;i<postosArr.length;i++){
      html += '<tr><td>' + esc(postosArr[i].posto) + '</td><td>' + postosArr[i].qtd + '</td></tr>';
    }
    html += '</tbody></table></div>';
  }

  document.getElementById('resultado').innerHTML = html;
}

function salvarInventario(){
  var texto = document.getElementById('invTexto').value;
  var st = document.getElementById('status'); st.textContent = 'Salvando...';
  var fd = new FormData();
  fd.append('acao','salvar_inventario');
  fd.append('inventario',texto);
  fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(d){
      st.textContent = d.ok ? ('\u2713 Inventario salvo: ' + d.salvos + ' etiqueta(s). Agora o filtro "Em Transito" pode excluir esses displays.')
                            : ('Erro ao salvar: ' + esc(d.erro||'desconhecido'));
    })
    .catch(function(){ st.textContent = 'Falha de comunicacao.'; });
}

function kpi(label,val,cor){
  return '<div class="kpi"><div class="kpi-label">' + esc(label) + '</div><div class="kpi-val" style="color:' + cor + ';">' + val + '</div></div>';
}

function tabela(arr,cols,labels){
  var h = '<table><thead><tr>';
  for (var i=0;i<labels.length;i++) h += '<th>' + esc(labels[i]) + '</th>';
  h += '</tr></thead><tbody>';
  for (var j=0;j<arr.length;j++){
    h += '<tr>';
    for (var k=0;k<cols.length;k++){
      var v = arr[j][cols[k]];
      var cls = (cols[k] === 'leitura') ? ' class="mono"' : '';
      h += '<td' + cls + '>' + esc(v||'-') + '</td>';
    }
    h += '</tr>';
  }
  h += '</tbody></table>';
  return h;
}
</script>
</body>
</html>
