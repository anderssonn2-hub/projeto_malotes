<?php
// =========================================================================
// qrcode_paginas.php — Versao 1.0.0
// Pagina UNICA com varios QR Codes imprimiveis, um por pagina mobile do
// sistema. Cada QR aponta para a URL da pagina NO PROPRIO SERVIDOR (detectada
// automaticamente pelo host/diretorio em que esta pagina foi aberta), para que,
// ao copiar para o servidor da LAN, os QRs ja apontem para o endereco certo.
//
// Paginas inclusas:
//   - Busca de producao (busca_producao_mobile.php)
//   - Conferencia por camera (conferencia_pacotes.php)
//   - Encontrar posto por voz (encontra_posto_mobile.php)
//   - Recebimento de displays (devolucao_etiquetas.php)
//
// Pode-se sobrescrever a base via ?base=http://host/dir/ e o tamanho via ?tam=
// =========================================================================

@date_default_timezone_set('America/Sao_Paulo');
function eh($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---- Detecta a base (scheme://host/dir/) do proprio servidor ----
$scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '10.15.61.169';
$uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri    = preg_replace('#\?.*$#', '', $uri);   // tira a query string
$dir    = preg_replace('#/[^/]*$#', '/', $uri); // mantem so o diretorio (com / no fim)
if ($dir === '' ) { $dir = '/'; }
$baseDetectada = $scheme . '://' . $host . $dir;

$base = isset($_GET['base']) ? trim((string)$_GET['base']) : $baseDetectada;
if (!preg_match('#^https?://#i', $base)) { $base = $baseDetectada; }
if (substr($base, -1) !== '/') { $base .= '/'; }

$tam = isset($_GET['tam']) ? (int)$_GET['tam'] : 320;
if ($tam < 150) { $tam = 150; }
if ($tam > 700) { $tam = 700; }

// Lib LOCAL de QR (a LAN bloqueia HTTPS/CDN, entao o fallback tem que ser local).
$temQrLib = is_file(__DIR__ . '/assets/js/lib_qrcode.js');

// ---- Paginas que ganham QR ----
$paginas = array(
    array(
        'arquivo' => 'busca_producao_mobile.php',
        'titulo'  => 'Busca de Producao',
        'sub'     => 'Lacres / Displays / Lotes / Oficios',
        'icone'   => "\xF0\x9F\x94\x8E"
    ),
    array(
        'arquivo' => 'conferencia_pacotes.php',
        'titulo'  => 'Conferencia por Camera',
        'sub'     => 'Bipe os lotes pelo celular (camera)',
        'icone'   => "\xF0\x9F\x93\xB7"
    ),
    array(
        'arquivo' => 'encontra_posto_mobile.php',
        'titulo'  => 'Encontrar Posto por Voz',
        'sub'     => 'Le o codigo e fala o posto/regional',
        'icone'   => "\xF0\x9F\x94\x8A"
    ),
    array(
        'arquivo' => 'devolucao_etiquetas.php',
        'titulo'  => 'Recebimento de Displays',
        'sub'     => 'Bipe as etiquetas (35 dig) pela camera',
        'icone'   => "\xF0\x9F\x93\xA6"
    )
);

function urlQr($url, $tam) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$tam . 'x' . (int)$tam
         . '&format=png&margin=2&ecc=M&data=' . rawurlencode($url);
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Codes das paginas</title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #eef2f7; color: #1f2937; padding: 22px;
}
.barra {
    max-width: 1100px; margin: 0 auto 16px auto;
    display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;
}
.barra h1 { font-size: 19px; margin: 0; color: #1a4f7a; }
.barra .acoes { display: flex; gap: 8px; }
.btn {
    border: none; padding: 11px 16px; border-radius: 10px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    text-decoration: none; display: inline-block;
}
.btn.primary { background: #1a4f7a; color: #fff; }
.btn.secondary { background: #e5e7eb; color: #374151; }

.grade {
    max-width: 1100px; margin: 0 auto;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;
}
.cartao {
    background: #fff; border-radius: 14px; padding: 22px 18px; text-align: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 2px solid #1a4f7a;
    page-break-inside: avoid; break-inside: avoid;
}
.cartao .titulo { font-size: 22px; font-weight: 800; color: #1a4f7a; margin-bottom: 2px; }
.cartao .sub { font-size: 13px; color: #4b5563; margin-bottom: 14px; }
.cartao .qrwrap { display: inline-block; padding: 12px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.cartao .qrwrap img, .cartao .qrwrap canvas { display: block; width: <?php echo (int)$tam; ?>px; height: <?php echo (int)$tam; ?>px; max-width: 100%; }
.cartao .instr { margin-top: 14px; font-size: 14px; color: #374151; font-weight: 600; }
.cartao .url {
    margin-top: 8px; font-family: ui-monospace, Consolas, monospace; font-size: 11px; color: #6b7280;
    word-break: break-all; padding: 7px 10px; background: #f9fafb; border-radius: 8px;
    border: 1px dashed #d1d5db; display: inline-block; max-width: 100%;
}
.config {
    max-width: 1100px; margin: 18px auto 0 auto; background: #fff; border-radius: 10px; padding: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.config h3 { margin: 0 0 10px 0; font-size: 14px; color: #1a4f7a; }
.config form { display: grid; grid-template-columns: 1fr auto auto; gap: 8px; align-items: end; }
.config label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
.config input { padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; width: 100%; }
.rodape { max-width: 1100px; margin: 14px auto 0 auto; font-size: 11px; color: #9ca3af; text-align: center; }
.fb-msg {
    display: none; margin-top: 8px; padding: 7px 10px; background: #fef3c7; color: #78350f;
    border-radius: 8px; font-size: 11px; border: 1px solid #fcd34d;
}
@media print {
    body { background: #fff !important; padding: 0 !important; }
    .barra, .config, .rodape, .no-print { display: none !important; }
    .grade { gap: 8px; }
    .cartao { border: 2px solid #000 !important; box-shadow: none !important; }
    .cartao .titulo { color: #000 !important; }
    .cartao .url { background: #fff !important; border-color: #000 !important; color: #000 !important; }
}
</style>
</head>
<body>

<div class="barra no-print">
    <h1>&#128279; QR Codes das paginas</h1>
    <div class="acoes">
        <a class="btn secondary" href="inicio.php">&#10005; Voltar</a>
        <button type="button" class="btn primary" onclick="window.print();">&#128424; Imprimir tudo</button>
    </div>
</div>

<div class="grade">
<?php $idx = 0; foreach ($paginas as $pg): $url = $base . $pg['arquivo']; ?>
    <div class="cartao">
        <div class="titulo"><?php echo eh($pg['icone']); ?> <?php echo eh($pg['titulo']); ?></div>
        <div class="sub"><?php echo eh($pg['sub']); ?></div>
        <div class="qrwrap" id="qrwrap<?php echo $idx; ?>">
            <img id="qrimg<?php echo $idx; ?>" src="<?php echo eh(urlQr($url, $tam)); ?>"
                 alt="QR Code" width="<?php echo (int)$tam; ?>" height="<?php echo (int)$tam; ?>"
                 data-url="<?php echo eh($url); ?>" data-idx="<?php echo $idx; ?>"
                 onerror="gerarQRLocal(<?php echo $idx; ?>);">
            <canvas id="qrcanvas<?php echo $idx; ?>" style="display:none;"></canvas>
        </div>
        <div class="instr">Aponte a camera do celular</div>
        <div class="url"><?php echo eh($url); ?></div>
        <div class="fb-msg no-print" id="fbMsg<?php echo $idx; ?>">Sem internet para api.qrserver.com — usando gerador local.</div>
    </div>
<?php $idx++; endforeach; ?>
</div>

<div class="config no-print">
    <h3>Personalizar (opcional)</h3>
    <form method="get" action="qrcode_paginas.php">
        <div>
            <label for="base">Servidor base (URL)</label>
            <input type="text" id="base" name="base" value="<?php echo eh($base); ?>">
        </div>
        <div>
            <label for="tam">Tamanho (px)</label>
            <input type="number" id="tam" name="tam" min="150" max="700" step="10" value="<?php echo (int)$tam; ?>" style="width:110px;">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn primary" style="padding:10px 16px;">Atualizar</button>
        </div>
    </form>
    <div style="margin-top:8px;font-size:11px;color:#6b7280;">
        Base detectada automaticamente: <code><?php echo eh($baseDetectada); ?></code>.
        Ajuste acima se for imprimir a partir de outra maquina.
    </div>
</div>

<div class="rodape">Sistema Lacres &middot; Imprima e cole onde o pessoal possa escanear pelo celular.</div>

<?php if ($temQrLib): ?>
<script src="assets/js/lib_qrcode.js"></script>
<?php endif; ?>
<script>
// Fallback: se a imagem (api.qrserver) falhar, gera o QR no proprio navegador com
// a lib LOCAL assets/js/lib_qrcode.js (global "qrcode"). Sem CDN, pois a LAN bloqueia HTTPS.
function gerarQRLocal(idx) {
    var fb = document.getElementById('fbMsg' + idx); if (fb) { fb.style.display = 'block'; }
    var img = document.getElementById('qrimg' + idx); if (img) { img.style.display = 'none'; }
    var canvas = document.getElementById('qrcanvas' + idx);
    var url = img ? img.getAttribute('data-url') : '';
    var tam = <?php echo (int)$tam; ?>;
    if (typeof qrcode === 'undefined' || !canvas) { mostraTextoFallback(idx, url); return; }
    try {
        var qr = qrcode(0, 'M'); qr.addData(url); qr.make();
        var modules = qr.getModuleCount();
        var cellSize = Math.floor(tam / modules); if (cellSize < 2) { cellSize = 2; }
        var size = cellSize * modules;
        canvas.width = size; canvas.height = size; canvas.style.display = 'block';
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#000';
        for (var r = 0; r < modules; r++) {
            for (var c = 0; c < modules; c++) {
                if (qr.isDark(r, c)) { ctx.fillRect(c * cellSize, r * cellSize, cellSize, cellSize); }
            }
        }
    } catch (e) { mostraTextoFallback(idx, url); }
}
// XSS-safe: monta o fallback de texto via textContent (nunca innerHTML com a URL).
function mostraTextoFallback(idx, url) {
    var wrap = document.getElementById('qrwrap' + idx);
    if (!wrap) { return; }
    wrap.innerHTML = '';
    var box = document.createElement('div');
    box.style.cssText = 'padding:24px;border:2px dashed #ccc;border-radius:10px;';
    var t1 = document.createElement('div');
    t1.style.cssText = 'font-size:13px;color:#991b1b;font-weight:700;margin-bottom:8px;';
    t1.textContent = 'Sem internet para gerar o QR.';
    var t2 = document.createElement('div');
    t2.style.cssText = 'font-size:12px;color:#374151;';
    t2.textContent = 'Acesse manualmente:';
    var t3 = document.createElement('div');
    t3.style.cssText = 'font-family:monospace;font-size:14px;font-weight:700;margin-top:6px;word-break:break-all;';
    t3.textContent = url;
    box.appendChild(t1); box.appendChild(t2); box.appendChild(t3);
    wrap.appendChild(box);
}
</script>

</body>
</html>
