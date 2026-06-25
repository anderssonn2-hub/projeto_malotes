<?php
// =========================================================================
// carteira_mobile.php — Versao 1.1.0
// "Carteira" mobile do operador: um unico leitor que reconhece o TIPO do
// codigo bipado e oferece a acao certa. Funciona no navegador do celular.
//
// Tipos reconhecidos pelo numero de digitos:
//   >= 35  -> Display / etiqueta Correios  -> Devolver / Rastrear
//   14..34 -> Codigo de barras do LOTE (19) -> Auditar lote / Buscar producao
//   <  14  -> Posto ou lote curto           -> Buscar / Auditar
//
// O roteamento e client-side e usa links RELATIVOS (o celular ja esta no
// mesmo host), entao funciona em qualquer ambiente.
// =========================================================================
@date_default_timezone_set('America/Sao_Paulo');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Carteira do Operador</title>
<style>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html, body { margin: 0; padding: 0; }
body { font-family: -apple-system, "Segoe UI", Roboto, "Trebuchet MS", Arial, sans-serif; background: #0f2027; color: #e8eef3; min-height: 100vh; padding: 16px; }
.wrap { max-width: 560px; margin: 0 auto; }
.top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.top h1 { font-size: 19px; margin: 0; color: #fff; }
.top a { color: #9fc7e8; text-decoration: none; font-size: 14px; }
.scanbox { background: #fff; border-radius: 14px; padding: 16px; box-shadow: 0 8px 22px rgba(0,0,0,0.35); }
.scanbox label { display: block; font-size: 12px; font-weight: 700; color: #51606f; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.scanbox input { width: 100%; padding: 16px; font-size: 20px; border: 2px solid #1a4f7a; border-radius: 10px; color: #102a3c; font-family: monospace; }
.dica { font-size: 12px; color: #7a8a98; margin-top: 8px; }
.tipo { margin-top: 16px; display: none; }
.tipo.ativo { display: block; }
.tipo .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 800; color: #fff; }
.b-display { background: #00897b; }
.b-lote { background: #1a4f7a; }
.b-curto { background: #6d4c41; }
.parsed { margin-top: 10px; background: #f3f7fb; border-radius: 10px; padding: 10px 12px; font-size: 13px; color: #2f3d4d; }
.parsed span { display: inline-block; margin-right: 12px; }
.parsed b { color: #1a4f7a; }
.acoes { margin-top: 14px; display: grid; gap: 10px; }
.acoes a { display: flex; flex-direction: column; gap: 2px; text-decoration: none; padding: 16px; border-radius: 12px; color: #fff; font-weight: 800; font-size: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.18); }
.acoes a small { font-weight: 500; font-size: 12px; opacity: 0.95; }
.a-aud { background: linear-gradient(135deg,#00897b,#26a69a); }
.a-busca { background: linear-gradient(135deg,#2f80ed,#56ccf2); }
.a-devol { background: linear-gradient(135deg,#ff6f00,#ffb300); }
.a-rastro { background: linear-gradient(135deg,#512da8,#7e57c2); }
.a-conf { background: linear-gradient(135deg,#c62828,#ef5350); }
.vazio { margin-top: 16px; color: #9fb3c4; text-align: center; font-size: 14px; }
.limpar { margin-top: 12px; text-align: center; }
.limpar button { background: none; border: 1px solid #3a5060; color: #cfe0ee; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-cam { margin-top: 12px; width: 100%; padding: 14px; font-size: 16px; font-weight: 800; color: #fff; background: linear-gradient(135deg,#1a237e,#3949ab); border: none; border-radius: 10px; cursor: pointer; }
.btn-oficio { margin-top: 12px; width: 100%; padding: 14px; font-size: 16px; font-weight: 800; color: #fff; background: linear-gradient(135deg,#512da8,#7e57c2); border: none; border-radius: 10px; cursor: pointer; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>&#128241; Carteira do Operador</h1>
    <a href="automacoes.php">&#8592; Voltar</a>
  </div>

  <div class="scanbox">
    <label for="cod">Bipe ou digite o codigo</label>
    <input type="text" id="cod" inputmode="numeric" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Lote, display ou posto..." />
    <button type="button" class="btn-cam" onclick="abrirCameraCarteira()">&#128247; Ler com a c&acirc;mera</button>
    <div class="dica">Reconhece automaticamente: <b>display</b> (35), <b>lote</b> (19) ou <b>posto</b>.</div>

    <div class="tipo" id="tipo">
      <span class="badge" id="badge"></span>
      <div class="parsed" id="parsed"></div>
      <div class="acoes" id="acoes"></div>
    </div>
    <div class="vazio" id="vazio">Aguardando leitura...</div>
    <div class="limpar"><button type="button" onclick="limpar()">Limpar</button></div>
  </div>

  <div class="scanbox" style="margin-top:14px;">
    <label for="oficio">Buscar por N&ordm; do Of&iacute;cio</label>
    <input type="text" id="oficio" inputmode="numeric" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Ex.: 1234" />
    <div class="dica">Use este campo quando tiver o <b>n&uacute;mero do of&iacute;cio</b> (evita confundir com posto).</div>
    <button type="button" class="btn-oficio" onclick="abrirOficio()">Abrir of&iacute;cio</button>
  </div>

  <div style="text-align:center;color:#5a6b7a;font-size:11px;margin-top:14px;">carteira_mobile v1.1.0</div>
</div>

<script>
var inp = document.getElementById('cod');
var elTipo = document.getElementById('tipo');
var elBadge = document.getElementById('badge');
var elParsed = document.getElementById('parsed');
var elAcoes = document.getElementById('acoes');
var elVazio = document.getElementById('vazio');

function soDigitos(s){ return (s || '').replace(/\D+/g, ''); }
function enc(s){ return encodeURIComponent(s); }
function esc(s){ return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

function botao(cls, titulo, sub, href){
  return '<a class="' + cls + '" href="' + href + '">' + titulo + (sub ? '<small>' + sub + '</small>' : '') + '</a>';
}

function detectar(){
  var raw = inp.value;
  var d = soDigitos(raw);
  if (raw.replace(/\s+/g,'') === '') { reset(); return; }

  var html = '', parsed = '', badgeCls = '', badgeTxt = '';

  if (d.length >= 35) {
    // Display / etiqueta Correios (35 digitos)
    var disp = d.substring(d.length - 35);
    badgeCls = 'b-display'; badgeTxt = 'DISPLAY (etiqueta Correios)';
    parsed = '<span>Etiqueta: <b>' + disp + '</b></span>';
    html += botao('a-devol', 'Devolver display', 'Registrar retorno do display', 'devolucao_etiquetas.php');
    html += botao('a-rastro', 'Rastrear', 'Movimentos do display', 'rastreabilidade.php');
  } else if (d.length >= 14) {
    // Codigo de barras do LOTE: usa os ultimos 19 digitos
    var u = d.substring(d.length - 19);
    var lote = u.substring(0, 8).replace(/^0+/, '') || u.substring(0, 8);
    var regional = u.substring(8, 11).replace(/^0+/, '') || u.substring(8, 11);
    var posto = u.substring(11, 14).replace(/^0+/, '') || u.substring(11, 14);
    var qtd = u.substring(14, 19).replace(/^0+/, '') || '0';
    badgeCls = 'b-lote'; badgeTxt = 'LOTE';
    parsed = '<span>Lote <b>' + lote + '</b></span><span>Regional <b>' + regional + '</b></span><span>Posto <b>' + posto + '</b></span><span>Qtd <b>' + qtd + '</b></span>';
    html += botao('a-aud', 'Auditar lote', 'Linha do tempo do lote', 'auditoria_lote.php?lote=' + enc(lote));
    html += botao('a-busca', 'Buscar producao', 'Pesquisar este lote', 'busca_producao_mobile.php?q=' + enc(lote));
    html += botao('a-busca', 'Ver posto ' + posto, 'Producao do posto', 'busca_producao_mobile.php?q=' + enc(posto));
  } else {
    // Codigo curto: posto ou lote curto
    var v = d.replace(/^0+/, '') || d;
    badgeCls = 'b-curto'; badgeTxt = 'CODIGO CURTO (posto ou lote)';
    parsed = '<span>Valor: <b>' + esc(v || raw) + '</b></span>';
    html += botao('a-busca', 'Buscar producao', 'Posto ou lote', 'busca_producao_mobile.php?q=' + enc(v || raw));
    html += botao('a-aud', 'Auditar como lote', 'Linha do tempo', 'auditoria_lote.php?lote=' + enc(v || raw));
  }

  elBadge.className = 'badge ' + badgeCls;
  elBadge.innerHTML = badgeTxt;
  elParsed.innerHTML = parsed;
  elAcoes.innerHTML = html;
  elTipo.className = 'tipo ativo';
  elVazio.style.display = 'none';
}

function reset(){
  elTipo.className = 'tipo';
  elVazio.style.display = 'block';
  elAcoes.innerHTML = '';
  elParsed.innerHTML = '';
}
function limpar(){ inp.value = ''; reset(); inp.focus(); }

// ITEM 6: busca dedicada por Numero do Oficio (sem ambiguidade com posto).
function abrirOficio(){
  var elO = document.getElementById('oficio');
  var n = soDigitos(elO ? elO.value : '');
  if (!n) { if (elO) elO.focus(); return; }
  window.location.href = 'rastreabilidade.php?modo=oficio&q=' + enc(n);
}

// ITEM 6: leitura por camera (modulo compartilhado CamScanner). Preenche o campo
// principal e deixa o detectar() rotear (display 35 / lote 19 / curto).
function abrirCameraCarteira(){
  if (typeof CamScanner === 'undefined' || !CamScanner.start) {
    alert('Leitor de camera nao disponivel nesta pagina.');
    return;
  }
  CamScanner.start({
    titulo: 'Ler codigo pela camera',
    onRead: function(bruto){
      var d = soDigitos(bruto);
      if (!d) return;
      inp.value = d;
      detectar();
      if (CamScanner.stop) { CamScanner.stop(); }
    }
  });
}

// Detecta ao digitar e tambem ao confirmar (Enter do leitor de codigo)
if (inp.addEventListener) {
  inp.addEventListener('input', detectar, false);
  inp.addEventListener('keydown', function(e){ if ((e.keyCode || e.which) === 13) { e.preventDefault(); detectar(); } }, false);
} else if (inp.attachEvent) {
  inp.attachEvent('onkeyup', detectar);
}
var inpOf = document.getElementById('oficio');
if (inpOf && inpOf.addEventListener) {
  inpOf.addEventListener('keydown', function(e){ if ((e.keyCode || e.which) === 13) { e.preventDefault(); abrirOficio(); } }, false);
}
inp.focus();
</script>
<script src="assets/js/lib_zxing.min.js"></script>
<script src="assets/js/lib_cam_scanner.js"></script>
</body>
</html>
