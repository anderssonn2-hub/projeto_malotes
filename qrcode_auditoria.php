<?php
// =========================================================================
// qrcode_auditoria.php — Versao 1.0.0
// Gera um QR Code imprimivel que abre a AUDITORIA (linha do tempo) de um LOTE
// (auditoria_lote.php?lote=...). Pode ser colado no oficio impresso para que
// qualquer pessoa aponte a camera e veja o historico completo do lote.
//
// Uso: ?lote=12345  (ou cole/bipe o codigo de barras do lote de 19 digitos)
//      ?base=http://10.15.61.169/controle/malote/  (opcional)
// =========================================================================

@date_default_timezone_set('America/Sao_Paulo');

function eh($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Extrai o numero do lote a partir do que foi digitado/scaneado.
// Codigo de barras do lote tem 19 digitos: lote[0,8]/regional[8,3]/posto[11,3]/qtd[14,5].
function extrairLote($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') return $raw; // nao-numerico: usa como veio
    if (strlen($d) >= 19) {
        $u = substr($d, -19);      // ultimos 19 digitos
        return substr($u, 0, 8);   // 8 primeiros = lote
    }
    return $d;
}

$baseDefault = 'http://10.15.61.169/controle/malote/';
$base = isset($_GET['base']) ? trim((string)$_GET['base']) : $baseDefault;
if (!preg_match('#^https?://#i', $base)) $base = $baseDefault;
if (substr($base, -1) !== '/') $base .= '/';

$loteRaw = isset($_GET['lote']) ? (string)$_GET['lote'] : '';
$lote    = extrairLote($loteRaw);
$tam     = isset($_GET['tam']) ? (int)$_GET['tam'] : 360;
if ($tam < 150) $tam = 150;
if ($tam > 700) $tam = 700;

$temLote = ($lote !== '');
$url = $base . 'auditoria_lote.php?lote=' . rawurlencode($lote);
$imgQr = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $tam . 'x' . $tam
       . '&format=png&margin=2&ecc=M&data=' . rawurlencode($url);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Code &mdash; Auditoria do Lote</title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: "Trebuchet MS", Verdana, Arial, sans-serif; background: #eef2f7; color: #1f2937; padding: 24px; }
.barra { max-width: 720px; margin: 0 auto 18px auto; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.barra h1 { font-size: 18px; margin: 0; color: #1a4f7a; }
.barra .acoes { display: flex; gap: 8px; }
.btn { border: none; padding: 12px 18px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
.btn.primary { background: #1a4f7a; color: #fff; }
.btn.secondary { background: #e5e7eb; color: #374151; }
.cartao { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 28px 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); text-align: center; border: 2px solid #00897b; }
.cartao .titulo { font-size: 24px; font-weight: 800; color: #00695c; margin-bottom: 4px; }
.cartao .lote { font-size: 16px; color: #374151; margin-bottom: 16px; }
.cartao .lote b { color: #00695c; font-size: 22px; }
.qrwrap { display: inline-block; padding: 14px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.qrwrap img, .qrwrap canvas { display: block; width: <?php echo (int)$tam; ?>px; height: <?php echo (int)$tam; ?>px; max-width: 100%; }
.instr { margin-top: 16px; font-size: 15px; color: #374151; font-weight: 600; }
.urlbox { margin-top: 8px; font-family: monospace; font-size: 12px; color: #6b7280; word-break: break-all; padding: 8px 12px; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db; display: inline-block; max-width: 100%; }
.vazio { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 28px; text-align: center; color: #6b7280; border: 2px dashed #cbd5e1; }
.config { max-width: 720px; margin: 18px auto 0 auto; background: #fff; border-radius: 10px; padding: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.config h3 { margin: 0 0 10px 0; font-size: 14px; color: #1a4f7a; }
.config form { display: grid; grid-template-columns: 1fr auto; gap: 8px; }
.config input { padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; width: 100%; }
.config label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; }
.config .full { grid-column: 1 / -1; }
.fb-msg { display: none; margin-top: 10px; padding: 8px 12px; background: #fef3c7; color: #78350f; border-radius: 8px; font-size: 12px; border: 1px solid #fcd34d; }
@media print { body { background: #fff; padding: 0; } .barra, .config, .no-print { display: none; } .cartao { border: 3px solid #000; box-shadow: none; } .cartao .titulo, .cartao .lote b { color: #000; } }
</style>
</head>
<body>

<div class="barra no-print">
  <h1>&#128279; QR &mdash; Auditoria do Lote</h1>
  <div class="acoes">
    <a class="btn secondary" href="automacoes.php">&#8592; Voltar</a>
    <?php if ($temLote): ?><button type="button" class="btn primary" onclick="window.print();">&#128424; Imprimir</button><?php endif; ?>
  </div>
</div>

<?php if ($temLote): ?>
<div class="cartao">
  <div class="titulo">Auditoria do Lote</div>
  <div class="lote">Lote <b><?php echo eh($lote); ?></b></div>
  <div class="qrwrap" id="qrwrap">
    <img id="qrimg" src="<?php echo eh($imgQr); ?>" alt="QR Code" width="<?php echo (int)$tam; ?>" height="<?php echo (int)$tam; ?>" onerror="gerarQRLocal();">
    <canvas id="qrcanvas" style="display:none;"></canvas>
  </div>
  <div class="instr">Aponte a camera do celular para ver a linha do tempo do lote</div>
  <div class="urlbox"><?php echo eh($url); ?></div>
  <div class="fb-msg no-print" id="fbMsg">Sem internet para api.qrserver.com — usando gerador JavaScript local.</div>
  <div style="margin-top:14px;" class="no-print"><a class="btn secondary" href="<?php echo eh($url); ?>" target="_blank">Abrir auditoria agora</a></div>
</div>
<?php else: ?>
<div class="vazio">Informe o numero do lote abaixo (ou bipe o codigo de barras de 19 digitos) para gerar o QR Code.</div>
<?php endif; ?>

<div class="config no-print">
  <h3>Gerar QR de um lote</h3>
  <form method="get" action="qrcode_auditoria.php">
    <div class="full">
      <label for="lote">Numero do lote (ou codigo de barras)</label>
      <input type="text" id="lote" name="lote" value="<?php echo eh($loteRaw); ?>" autofocus placeholder="Ex.: 12345" autocomplete="off">
    </div>
    <div class="full">
      <button type="submit" class="btn primary" style="width:100%;">Gerar QR Code</button>
    </div>
    <input type="hidden" name="base" value="<?php echo eh($base); ?>">
    <input type="hidden" name="tam" value="<?php echo (int)$tam; ?>">
  </form>
</div>

<script>
var URL_QR = <?php echo json_encode($url); ?>;
var TAM_QR = <?php echo (int)$tam; ?>;
function gerarQRLocal() {
  var fb = document.getElementById('fbMsg'); if (fb) fb.style.display = 'block';
  var img = document.getElementById('qrimg'); if (img) img.style.display = 'none';
  var canvas = document.getElementById('qrcanvas'); if (!canvas) return;
  canvas.style.display = 'block';
  var s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
  s.onload = function(){
    try {
      var qr = qrcode(0, 'M'); qr.addData(URL_QR); qr.make();
      var modules = qr.getModuleCount();
      var cellSize = Math.floor(TAM_QR / modules); if (cellSize < 2) cellSize = 2;
      var size = cellSize * modules; canvas.width = size; canvas.height = size;
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff'; ctx.fillRect(0,0,size,size); ctx.fillStyle = '#000';
      for (var r=0;r<modules;r++){ for (var c=0;c<modules;c++){ if (qr.isDark(r,c)) ctx.fillRect(c*cellSize, r*cellSize, cellSize, cellSize); } }
    } catch (e) { mostraTextoFallback(); }
  };
  s.onerror = mostraTextoFallback;
  document.head.appendChild(s);
}
function mostraTextoFallback() {
  var w = document.getElementById('qrwrap'); if (!w) return;
  w.innerHTML = '<div style="padding:30px;border:2px dashed #ccc;border-radius:10px;"><div style="font-size:14px;color:#991b1b;font-weight:700;margin-bottom:8px;">Nao foi possivel gerar o QR (sem internet).</div><div style="font-size:13px;color:#374151;">Acesse manualmente:</div><div style="font-family:monospace;font-size:15px;font-weight:700;margin-top:6px;word-break:break-all;">' + URL_QR + '</div></div>';
}
</script>
</body>
</html>
