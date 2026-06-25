<?php
// =========================================================================
// qrcode_posto.php — Versao 1.0.0
// Gera um QR Code imprimivel por POSTO. Ao escanear, abre a busca de producao
// mobile ja filtrada pelo posto (busca_producao_mobile.php?q=...). Pode ser
// colado na prateleira/estante de cada posto.
//
// Uso: ?posto=250  (ou cole/bipe o codigo de barras do lote de 19 digitos:
//      o posto sai dos 3 digitos na posicao 11)
//      ?base=http://10.15.61.169/controle/malote/  (opcional)
// =========================================================================

@date_default_timezone_set('America/Sao_Paulo');

function eh($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Extrai o numero do posto do que foi digitado/scaneado.
function extrairPosto($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') return $raw;
    if (strlen($d) >= 19) {
        $u = substr($d, -19);     // ultimos 19 digitos
        return ltrim(substr($u, 11, 3), '0'); // posto = 3 digitos na posicao 11
    }
    return ltrim($d, '0') !== '' ? ltrim($d, '0') : $d;
}

$baseDefault = 'http://10.15.61.169/controle/malote/';
$base = isset($_GET['base']) ? trim((string)$_GET['base']) : $baseDefault;
if (!preg_match('#^https?://#i', $base)) $base = $baseDefault;
if (substr($base, -1) !== '/') $base .= '/';

$postoRaw = isset($_GET['posto']) ? (string)$_GET['posto'] : '';
$posto    = extrairPosto($postoRaw);
$tam      = isset($_GET['tam']) ? (int)$_GET['tam'] : 360;
if ($tam < 150) $tam = 150;
if ($tam > 700) $tam = 700;

$temPosto = ($posto !== '');
$url = $base . 'busca_producao_mobile.php?q=' . rawurlencode($posto);
$imgQr = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $tam . 'x' . $tam
       . '&format=png&margin=2&ecc=M&data=' . rawurlencode($url);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Code &mdash; Posto</title>
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
.cartao { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 28px 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); text-align: center; border: 2px solid #6d4c41; }
.cartao .titulo { font-size: 24px; font-weight: 800; color: #5d4037; margin-bottom: 4px; }
.cartao .posto { font-size: 16px; color: #374151; margin-bottom: 16px; }
.cartao .posto b { color: #5d4037; font-size: 30px; }
.qrwrap { display: inline-block; padding: 14px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.qrwrap img, .qrwrap canvas { display: block; width: <?php echo (int)$tam; ?>px; height: <?php echo (int)$tam; ?>px; max-width: 100%; }
.instr { margin-top: 16px; font-size: 15px; color: #374151; font-weight: 600; }
.urlbox { margin-top: 8px; font-family: monospace; font-size: 12px; color: #6b7280; word-break: break-all; padding: 8px 12px; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db; display: inline-block; max-width: 100%; }
.vazio { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 28px; text-align: center; color: #6b7280; border: 2px dashed #cbd5e1; }
.config { max-width: 720px; margin: 18px auto 0 auto; background: #fff; border-radius: 10px; padding: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.config h3 { margin: 0 0 10px 0; font-size: 14px; color: #1a4f7a; }
.config form { display: grid; grid-template-columns: 1fr; gap: 8px; }
.config input { padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; width: 100%; }
.config label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; }
.fb-msg { display: none; margin-top: 10px; padding: 8px 12px; background: #fef3c7; color: #78350f; border-radius: 8px; font-size: 12px; border: 1px solid #fcd34d; }
@media print { body { background: #fff; padding: 0; } .barra, .config, .no-print { display: none; } .cartao { border: 3px solid #000; box-shadow: none; } .cartao .titulo, .cartao .posto b { color: #000; } }
</style>
</head>
<body>

<div class="barra no-print">
  <h1>&#128279; QR &mdash; Posto</h1>
  <div class="acoes">
    <a class="btn secondary" href="automacoes.php">&#8592; Voltar</a>
    <?php if ($temPosto): ?><button type="button" class="btn primary" onclick="window.print();">&#128424; Imprimir</button><?php endif; ?>
  </div>
</div>

<?php if ($temPosto): ?>
<div class="cartao">
  <div class="titulo">Posto</div>
  <div class="posto"><b><?php echo eh($posto); ?></b></div>
  <div class="qrwrap" id="qrwrap">
    <img id="qrimg" src="<?php echo eh($imgQr); ?>" alt="QR Code" width="<?php echo (int)$tam; ?>" height="<?php echo (int)$tam; ?>" onerror="gerarQRLocal();">
    <canvas id="qrcanvas" style="display:none;"></canvas>
  </div>
  <div class="instr">Aponte a camera do celular para ver a producao deste posto</div>
  <div class="urlbox"><?php echo eh($url); ?></div>
  <div class="fb-msg no-print" id="fbMsg">Sem internet para api.qrserver.com — usando gerador JavaScript local.</div>
  <div style="margin-top:14px;" class="no-print"><a class="btn secondary" href="<?php echo eh($url); ?>" target="_blank">Abrir busca agora</a></div>
</div>
<?php else: ?>
<div class="vazio">Informe o numero do posto abaixo (ou bipe o codigo de barras do lote) para gerar o QR Code.</div>
<?php endif; ?>

<div class="config no-print">
  <h3>Gerar QR de um posto</h3>
  <form method="get" action="qrcode_posto.php">
    <label for="posto">Numero do posto (ou codigo de barras do lote)</label>
    <input type="text" id="posto" name="posto" value="<?php echo eh($postoRaw); ?>" autofocus placeholder="Ex.: 250" autocomplete="off">
    <button type="submit" class="btn primary" style="width:100%;">Gerar QR Code</button>
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
