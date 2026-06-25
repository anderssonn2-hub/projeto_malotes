<?php
// VERSAO SISTEMA: 2.0.8 - 2026-06-09
// Cadastro em lote de displays novos em cadastroMalotes.
// Fluxo: informar o posto -> ler 1, alguns ou varios displays -> trocar de posto
// -> ler mais -> Salvar tudo. Cada display vira uma linha em cadastroMalotes
// preenchendo: leitura (35 digitos), cep (8 primeiros), sequencial (5 ultimos), posto.
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

require_once dirname(__FILE__) . '/config/db_config.php';

function normalizarUtf8($s) {
    $s = (string)$s;
    if ($s === '' || preg_match('//u', $s)) return $s;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8','UTF-8//IGNORE',$s);
        if ($t !== false && $t !== '') return $t;
    }
    return $s;
}
function e($s) { return htmlspecialchars(normalizarUtf8($s), ENT_QUOTES, 'UTF-8'); }

/* Conexao (credenciais centralizadas em db_config.php) */
$dbOk = false; $mensagem = '';
try {
    $pdo = getDbPdo();
    $dbOk = true;
} catch (Exception $ex) {
    $mensagem = 'Falha ao conectar no banco.';
}

/* Acao AJAX: salvar varios displays de varios postos de uma vez */
if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_displays') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$dbOk) { echo json_encode(array('ok'=>false,'msg'=>'Banco indisponivel')); exit; }

    $raw = isset($_POST['itens']) ? (string)$_POST['itens'] : '';
    $itens = json_decode($raw, true);
    if (!is_array($itens) || count($itens) === 0) {
        echo json_encode(array('ok'=>false,'msg'=>'Nenhum display para salvar')); exit;
    }

    $inseridos = 0; $jaExistiam = 0; $invalidos = 0; $erros = 0;
    $detalhes = array();

    try { $pdo->beginTransaction(); } catch (Exception $eTx) {}
    try {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM cadastroMalotes WHERE leitura=?');
        $ins = $pdo->prepare('INSERT INTO cadastroMalotes (leitura, cep, sequencial, posto) VALUES (?, ?, ?, ?)');

        $i = 0; $n = count($itens);
        while ($i < $n) {
            $item = $itens[$i];
            $i++;
            $leitura = isset($item['leitura']) ? preg_replace('/\D+/', '', (string)$item['leitura']) : '';
            $posto   = isset($item['posto'])   ? preg_replace('/\s+/', '', (string)$item['posto'])   : '';
            if (strlen($leitura) > 35) { $leitura = substr($leitura, -35); }

            if (strlen($leitura) !== 35 || $posto === '' || strlen($posto) > 20) {
                $invalidos++;
                $detalhes[] = array('leitura'=>$leitura, 'posto'=>$posto, 'status'=>'invalido');
                continue;
            }
            $cep = substr($leitura, 0, 8);
            $seq = substr($leitura, -5);
            try {
                $chk->execute(array($leitura));
                if ((int)$chk->fetchColumn() > 0) {
                    $jaExistiam++;
                    $detalhes[] = array('leitura'=>$leitura, 'posto'=>$posto, 'status'=>'duplicado');
                    continue;
                }
                $ins->execute(array($leitura, $cep, $seq, $posto));
                $inseridos++;
                $detalhes[] = array('leitura'=>$leitura, 'posto'=>$posto, 'status'=>'inserido');
            } catch (Exception $eItem) {
                $erros++;
                $detalhes[] = array('leitura'=>$leitura, 'posto'=>$posto, 'status'=>'erro');
            }
        }
        $commitOk = true;
        try { if ($pdo->inTransaction()) $pdo->commit(); } catch (Exception $eC) {
            $commitOk = false;
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $eR) {}
        }
        if (!$commitOk) {
            echo json_encode(array('ok'=>false,'msg'=>'Falha ao confirmar o salvamento (commit). Nada foi gravado.')); exit;
        }
    } catch (Exception $eAll) {
        try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $eR2) {}
        echo json_encode(array('ok'=>false,'msg'=>$eAll->getMessage())); exit;
    }

    echo json_encode(array(
        'ok'=>true,
        'inseridos'=>$inseridos,
        'jaExistiam'=>$jaExistiam,
        'invalidos'=>$invalidos,
        'erros'=>$erros,
        'detalhes'=>$detalhes
    ));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cadastrar displays novos</title>
<style>
  * { box-sizing: border-box; }
  body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#f1f5f9; color:#1f2933; }
  .topbar { background:#0b5e57; color:#fff; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
  .topbar h1 { font-size:18px; margin:0; }
  .topbar a { color:#fff; text-decoration:none; font-weight:bold; font-size:14px; }
  .wrap { max-width:880px; margin:0 auto; padding:16px; }
  .card { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:16px; margin-bottom:16px; }
  .card h2 { font-size:15px; margin:0 0 10px; color:#0b5e57; }
  label { display:block; font-size:13px; font-weight:bold; margin-bottom:4px; color:#52606d; }
  input[type=text] { width:100%; padding:12px; font-size:18px; border:1px solid #cbd2d9; border-radius:8px; }
  input[type=text]:focus { outline:none; border-color:#0b5e57; box-shadow:0 0 0 2px rgba(11,94,87,0.15); }
  .row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .row > div { flex:1 1 200px; }
  .btn { display:inline-block; border:0; cursor:pointer; padding:12px 16px; border-radius:8px; font-size:15px; font-weight:bold; color:#fff; }
  .btn-verde { background:#0b8457; }
  .btn-teal { background:#0b5e57; }
  .btn-cinza { background:#9aa5b1; }
  .btn-vermelho { background:#c0392b; }
  .btn:disabled { opacity:0.5; cursor:not-allowed; }
  .ativo-info { background:#e6f4f1; border:1px solid #b6dfd8; border-radius:8px; padding:10px 12px; font-size:14px; margin-bottom:12px; }
  .ativo-info b { color:#0b5e57; }
  .scan-area { display:none; }
  .scan-area.aberto { display:block; }
  .status { font-size:14px; margin-top:8px; min-height:20px; font-weight:bold; }
  .status.ok { color:#0b8457; }
  .status.err { color:#c0392b; }
  .grupo { border:1px solid #e0e6ed; border-radius:10px; margin-bottom:12px; overflow:hidden; }
  .grupo-head { background:#f0f4f8; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; }
  .grupo-head .nome { font-weight:bold; color:#0b5e57; font-size:15px; }
  .grupo-head .qtd { background:#0b5e57; color:#fff; border-radius:12px; padding:2px 10px; font-size:13px; margin-left:8px; }
  .item { display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-top:1px solid #eef2f6; font-family:"Courier New",monospace; font-size:13px; word-break:break-all; }
  .item .x { color:#c0392b; cursor:pointer; font-weight:bold; margin-left:10px; flex:0 0 auto; }
  .vazio { color:#7b8794; font-size:14px; }
  .resumo { font-size:14px; line-height:1.6; }
  .resumo .li { padding:2px 0; }
  .aviso { background:#fff3cd; color:#7a5a00; border:1px solid #ffe69c; border-radius:8px; padding:10px 12px; font-size:14px; margin-bottom:12px; }
  .acoes-finais { display:flex; gap:10px; flex-wrap:wrap; }
</style>
</head>
<body>
  <div class="topbar">
    <h1>Cadastrar displays novos</h1>
    <a href="inicio.php">&#8592; Voltar ao inicio</a>
  </div>
  <div class="wrap">

    <?php if (!$dbOk): ?>
      <div class="aviso">Banco de dados indisponivel neste ambiente. A tela funciona, mas o salvamento so grava quando estiver na rede do sistema.</div>
    <?php endif; ?>

    <div class="card">
      <h2>1) Informe o posto e inicie a leitura</h2>
      <div class="row">
        <div>
          <label for="posto">Numero do posto</label>
          <input type="text" id="posto" inputmode="numeric" autocomplete="off" placeholder="Ex.: 700">
        </div>
        <div style="flex:0 0 auto;">
          <button type="button" class="btn btn-teal" id="btnIniciar">Iniciar leitura deste posto</button>
        </div>
      </div>
    </div>

    <div class="card scan-area" id="scanArea">
      <h2>2) Leia os displays</h2>
      <div class="ativo-info">Lendo displays do posto <b id="postoAtivoLabel">-</b>. Bipe um, alguns ou varios. Para trocar de posto, mude o numero acima e clique em "Iniciar leitura deste posto".</div>
      <label for="scan">Codigo de barras do display (35 digitos)</label>
      <input type="text" id="scan" autocomplete="off" placeholder="Bipe o display aqui">
      <div class="status" id="status"></div>
    </div>

    <div class="card">
      <h2>3) Conferir e salvar</h2>
      <div id="listaGrupos"><div class="vazio">Nenhum display lido ainda.</div></div>
      <div class="acoes-finais">
        <button type="button" class="btn btn-verde" id="btnSalvar" disabled>Salvar tudo</button>
        <button type="button" class="btn btn-cinza" id="btnLimpar" disabled>Limpar tudo</button>
      </div>
      <div class="status" id="statusSalvar"></div>
    </div>

  </div>

<script>
(function(){
  "use strict";
  // Estrutura: { "700": ["<35digitos>", ...], "950": [...] }  (ordem de insercao preservada via lista de postos)
  var grupos = {};
  var ordemPostos = [];
  var postoAtivo = '';

  var inpPosto   = document.getElementById('posto');
  var btnIniciar = document.getElementById('btnIniciar');
  var scanArea   = document.getElementById('scanArea');
  var inpScan    = document.getElementById('scan');
  var lblPosto   = document.getElementById('postoAtivoLabel');
  var status     = document.getElementById('status');
  var lista      = document.getElementById('listaGrupos');
  var btnSalvar  = document.getElementById('btnSalvar');
  var btnLimpar  = document.getElementById('btnLimpar');
  var statusSalvar = document.getElementById('statusSalvar');

  function beep(ok){
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if(!AC) return;
      var ctx = beep._ctx || (beep._ctx = new AC());
      var o = ctx.createOscillator(); var g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.08;
      o.start();
      setTimeout(function(){ try{ o.stop(); }catch(e){} }, ok?90:200);
    } catch(e){}
  }

  function soDigitos(s){ return (s||'').replace(/\D+/g,''); }

  function totalGeral(){
    var t = 0, k;
    for (k in grupos) { if (grupos.hasOwnProperty(k)) t += grupos[k].length; }
    return t;
  }

  function jaExisteNaSessao(leitura){
    var k, j;
    for (k in grupos) {
      if (!grupos.hasOwnProperty(k)) continue;
      for (j=0; j<grupos[k].length; j++){ if (grupos[k][j] === leitura) return k; }
    }
    return null;
  }

  function setStatus(msg, ok){
    status.textContent = msg || '';
    status.className = 'status ' + (msg ? (ok?'ok':'err') : '');
  }

  function iniciarLeitura(){
    var p = (inpPosto.value||'').replace(/\s+/g,'');
    if (p === '') { inpPosto.focus(); setStatus('Digite o numero do posto.', false); scanArea.className='card scan-area'; return; }
    postoAtivo = p;
    if (!grupos.hasOwnProperty(p)) { grupos[p] = []; ordemPostos.push(p); }
    lblPosto.textContent = p;
    scanArea.className = 'card scan-area aberto';
    setStatus('', true);
    inpScan.value = '';
    inpScan.focus();
    render();
  }

  function adicionarLeitura(valor){
    if (postoAtivo === '') { setStatus('Inicie a leitura de um posto primeiro.', false); return; }
    var d = soDigitos(valor);
    if (d.length > 35) d = d.substring(d.length-35);
    if (d.length !== 35) { setStatus('Codigo invalido (precisa de 35 digitos, lido '+d.length+').', false); beep(false); return; }
    var onde = jaExisteNaSessao(d);
    if (onde !== null) { setStatus('Display ja lido nesta sessao (posto '+onde+').', false); beep(false); return; }
    grupos[postoAtivo].push(d);
    setStatus('Display adicionado ao posto '+postoAtivo+' ('+grupos[postoAtivo].length+' neste posto).', true);
    beep(true);
    render();
  }

  function removerItem(posto, idx){
    if (!grupos.hasOwnProperty(posto)) return;
    grupos[posto].splice(idx, 1);
    render();
  }

  function removerGrupo(posto){
    if (!grupos.hasOwnProperty(posto)) return;
    delete grupos[posto];
    var i = ordemPostos.indexOf(posto);
    if (i >= 0) ordemPostos.splice(i,1);
    if (postoAtivo === posto) { postoAtivo=''; scanArea.className='card scan-area'; }
    render();
  }

  function render(){
    var i, j, html = '';
    var total = totalGeral();
    if (total === 0) {
      lista.innerHTML = '<div class="vazio">Nenhum display lido ainda.</div>';
    } else {
      for (i=0; i<ordemPostos.length; i++){
        var p = ordemPostos[i];
        var arr = grupos[p] || [];
        if (arr.length === 0) continue;
        html += '<div class="grupo">';
        html += '<div class="grupo-head"><span><span class="nome">Posto '+escAttr(p)+'</span><span class="qtd">'+arr.length+'</span></span>';
        html += '<span class="x" data-remgrupo="'+escAttr(p)+'">remover posto</span></div>';
        for (j=0; j<arr.length; j++){
          html += '<div class="item"><span>'+escHtml(arr[j])+'</span><span class="x" data-posto="'+escAttr(p)+'" data-idx="'+j+'">remover</span></div>';
        }
        html += '</div>';
      }
      lista.innerHTML = html;
    }
    btnSalvar.disabled = (total === 0);
    btnLimpar.disabled = (total === 0);
  }

  function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function escAttr(s){ return escHtml(s).replace(/"/g,'&quot;'); }

  function montarItens(){
    var out = [], i, j;
    for (i=0; i<ordemPostos.length; i++){
      var p = ordemPostos[i];
      var arr = grupos[p] || [];
      for (j=0; j<arr.length; j++){ out.push({posto:p, leitura:arr[j]}); }
    }
    return out;
  }

  function salvarTudo(){
    var itens = montarItens();
    if (itens.length === 0) return;
    btnSalvar.disabled = true; btnLimpar.disabled = true;
    statusSalvar.className = 'status';
    statusSalvar.textContent = 'Salvando '+itens.length+' display(s)...';

    var fd = new FormData();
    fd.append('acao','salvar_displays');
    fd.append('itens', JSON.stringify(itens));

    fetch(window.location.pathname, {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d || !d.ok) {
          statusSalvar.className='status err';
          statusSalvar.textContent = 'Erro ao salvar: ' + ((d&&d.msg)?d.msg:'desconhecido');
          btnSalvar.disabled=false; btnLimpar.disabled=false;
          return;
        }
        var msg = 'Inseridos: '+d.inseridos+' | Ja cadastrados: '+d.jaExistiam+' | Invalidos: '+d.invalidos+' | Erros: '+d.erros;
        statusSalvar.className='status ok';
        statusSalvar.textContent = msg;
        beep(d.erros>0 || d.invalidos>0 ? false : true);
        // Remove da sessao os que foram inseridos ou ja existiam (concluidos);
        // mantem invalidos/erros para o usuario rever.
        if (d.detalhes && d.detalhes.length) {
          var concluidos = {}, k;
          for (k=0; k<d.detalhes.length; k++){
            var dt = d.detalhes[k];
            if (dt.status === 'inserido' || dt.status === 'duplicado') concluidos[dt.leitura] = true;
          }
          var pi, novosPostos = [];
          for (pi=0; pi<ordemPostos.length; pi++){
            var pp = ordemPostos[pi];
            var keep = [];
            var a = grupos[pp] || [];
            for (var z=0; z<a.length; z++){ if (!concluidos[a[z]]) keep.push(a[z]); }
            if (keep.length){ grupos[pp]=keep; novosPostos.push(pp); } else { delete grupos[pp]; if (postoAtivo===pp){ postoAtivo=''; scanArea.className='card scan-area'; } }
          }
          ordemPostos = novosPostos;
        }
        render();
      })
      .catch(function(){
        statusSalvar.className='status err';
        statusSalvar.textContent = 'Falha de comunicacao ao salvar.';
        btnSalvar.disabled=false; btnLimpar.disabled=false;
      });
  }

  // Eventos
  btnIniciar.addEventListener('click', iniciarLeitura);
  inpPosto.addEventListener('keydown', function(ev){ if (ev.keyCode===13){ ev.preventDefault(); iniciarLeitura(); } });

  var scanTimer = null;
  function processarScan(){
    if (scanTimer){ clearTimeout(scanTimer); scanTimer = null; }
    var d = soDigitos(inpScan.value);
    if (d.length === 0) return; // Enter em campo vazio (ex.: depois do auto-add): ignora
    adicionarLeitura(inpScan.value);
    inpScan.value = '';
  }
  inpScan.addEventListener('keydown', function(ev){
    if (ev.keyCode===13){ ev.preventDefault(); processarScan(); }
  });
  // Scanners que nao enviam Enter: processa quando a rajada de digitacao parar
  // (debounce). Evita adicionar parcial (>35) e evita disparo duplo com o Enter.
  inpScan.addEventListener('input', function(){
    if (scanTimer) clearTimeout(scanTimer);
    scanTimer = setTimeout(function(){
      scanTimer = null;
      var d = soDigitos(inpScan.value);
      if (d.length >= 35){ adicionarLeitura(inpScan.value); inpScan.value=''; }
    }, 90);
  });

  lista.addEventListener('click', function(ev){
    var t = ev.target;
    if (!t) return;
    if (t.getAttribute('data-remgrupo') !== null && t.getAttribute('data-remgrupo') !== undefined && t.hasAttribute('data-remgrupo')){
      removerGrupo(t.getAttribute('data-remgrupo'));
      return;
    }
    if (t.hasAttribute('data-idx')){
      removerItem(t.getAttribute('data-posto'), parseInt(t.getAttribute('data-idx'),10));
    }
  });

  btnSalvar.addEventListener('click', salvarTudo);
  btnLimpar.addEventListener('click', function(){
    if (totalGeral()===0) return;
    grupos = {}; ordemPostos = []; postoAtivo=''; scanArea.className='card scan-area';
    statusSalvar.textContent=''; statusSalvar.className='status';
    setStatus('', true);
    render();
  });

  render();
  inpPosto.focus();
})();
</script>
</body>
</html>
