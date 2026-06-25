<?php
// =========================================================================
// qrcode_busca_producao.php — Versao 2.0.0
// Gera um QR Code imprimivel apontando para a pagina mobile de busca de
// producao. Pode ser colado em mural, etiqueta de mesa, etc.
//
// Uso: abrir no navegador e clicar em "Imprimir".
//      Pode-se personalizar a URL via ?url=... e o titulo via ?titulo=...
// =========================================================================

@date_default_timezone_set('America/Sao_Paulo');

function eh($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// URL padrao = ambiente real informado pelo usuario.
$urlDefault = 'http://10.15.61.169/controle/malote/busca_producao_mobile.php';
$url    = isset($_GET['url'])    ? trim((string)$_GET['url'])    : $urlDefault;
$titulo = isset($_GET['titulo']) ? trim((string)$_GET['titulo']) : 'Busca de Producao';
$sub    = isset($_GET['sub'])    ? trim((string)$_GET['sub'])    : 'Lacres / Displays / Lotes / Oficios';
$tam    = isset($_GET['tam'])    ? (int)$_GET['tam']             : 420; // px

if ($tam < 150)  $tam = 150;
if ($tam > 900)  $tam = 900;

// Validacao basica da URL
if (!preg_match('#^https?://#i', $url)) {
    $url = $urlDefault;
}

$urlEnc = rawurlencode($url);
// Fonte primaria: api.qrserver.com (PNG direto). Fallback JS via CDN gera
// localmente caso a imagem nao carregue (rede sem internet externa).
$imgQr  = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $tam . 'x' . $tam
        . '&format=png&margin=2&ecc=M&data=' . $urlEnc;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Code &mdash; <?php echo eh($titulo); ?></title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #eef2f7; color: #1f2937; padding: 24px;
}
.barra {
    max-width: 720px; margin: 0 auto 18px auto;
    display: flex; justify-content: space-between; align-items: center;
    gap: 10px;
}
.barra h1 { font-size: 18px; margin: 0; color: #1a4f7a; }
.barra .acoes { display: flex; gap: 8px; }
.btn {
    border: none; padding: 12px 18px; border-radius: 10px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    text-decoration: none; display: inline-block;
}
.btn.primary   { background: #1a4f7a; color: #fff; }
.btn.primary:hover   { background: #143d5e; }
.btn.secondary { background: #e5e7eb; color: #374151; }
.btn.secondary:hover { background: #d1d5db; }

.cartao {
    max-width: 720px; margin: 0 auto;
    background: #fff; border-radius: 14px;
    padding: 32px 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    text-align: center;
    border: 2px solid #1a4f7a;
}
.cartao .titulo {
    font-size: 28px; font-weight: 800; color: #1a4f7a;
    margin-bottom: 4px; letter-spacing: 0.3px;
}
.cartao .sub {
    font-size: 14px; color: #4b5563; margin-bottom: 18px;
}
.cartao .qrwrap {
    display: inline-block; padding: 14px; background: #fff;
    border-radius: 12px; border: 1px solid #e5e7eb;
}
.cartao .qrwrap img,
.cartao .qrwrap canvas {
    display: block; width: <?php echo (int)$tam; ?>px; height: <?php echo (int)$tam; ?>px;
    max-width: 100%;
}
.cartao .instr {
    margin-top: 18px; font-size: 15px; color: #374151; font-weight: 600;
}
.cartao .url {
    margin-top: 8px; font-family: ui-monospace, Consolas, monospace;
    font-size: 12px; color: #6b7280; word-break: break-all;
    padding: 8px 12px; background: #f9fafb; border-radius: 8px;
    border: 1px dashed #d1d5db; display: inline-block; max-width: 100%;
}
.rodape {
    max-width: 720px; margin: 14px auto 0 auto;
    font-size: 11px; color: #9ca3af; text-align: center;
}

.config {
    max-width: 720px; margin: 18px auto 0 auto;
    background: #fff; border-radius: 10px; padding: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.config h3 { margin: 0 0 10px 0; font-size: 14px; color: #1a4f7a; }
.config form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; }
.config input, .config select {
    padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 14px; width: 100%;
}
.config label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; }
.config .full { grid-column: 1 / -1; }
.config .acao { align-self: end; }

.fb-msg {
    display: none; margin-top: 10px; padding: 8px 12px;
    background: #fef3c7; color: #78350f; border-radius: 8px;
    font-size: 12px; border: 1px solid #fcd34d;
}

@media print {
    body { background: #fff !important; padding: 0 !important; }
    .barra, .config, .rodape, .no-print { display: none !important; }
    .cartao {
        border: 3px solid #000 !important; box-shadow: none !important;
        margin: 0 auto !important; max-width: 100% !important;
        page-break-inside: avoid;
    }
    .cartao .titulo { color: #000 !important; }
    .cartao .url   { background: #fff !important; border-color: #000 !important; color: #000 !important; }
}
</style>
</head>
<body>

<div class="barra no-print">
    <h1>&#128279; QR Code &mdash; Busca de Producao</h1>
    <div class="acoes">
        <a class="btn secondary" href="busca_producao_mobile.php">&#10005; Voltar</a>
        <button type="button" class="btn primary" onclick="window.print();">&#128424; Imprimir</button>
    </div>
</div>

<div class="cartao">
    <div class="titulo"><?php echo eh($titulo); ?></div>
    <div class="sub"><?php echo eh($sub); ?></div>

    <div class="qrwrap" id="qrwrap">
        <img id="qrimg"
             src="<?php echo eh($imgQr); ?>"
             alt="QR Code"
             width="<?php echo (int)$tam; ?>"
             height="<?php echo (int)$tam; ?>"
             onerror="gerarQRLocal();">
        <canvas id="qrcanvas" style="display:none;"></canvas>
    </div>

    <div class="instr">Aponte a camera do celular para escanear</div>
    <div class="url"><?php echo eh($url); ?></div>

    <div class="fb-msg no-print" id="fbMsg">
        Sem internet para api.qrserver.com — usando gerador JavaScript local.
    </div>
</div>

<div class="config no-print">
    <h3>Personalizar (opcional)</h3>
    <form method="get" action="qrcode_busca_producao.php">
        <div class="full">
            <label for="url">URL</label>
            <input type="text" id="url" name="url" value="<?php echo eh($url); ?>">
        </div>
        <div>
            <label for="titulo">Titulo</label>
            <input type="text" id="titulo" name="titulo" value="<?php echo eh($titulo); ?>">
        </div>
        <div>
            <label for="sub">Subtitulo</label>
            <input type="text" id="sub" name="sub" value="<?php echo eh($sub); ?>">
        </div>
        <div class="acao">
            <label>&nbsp;</label>
            <button type="submit" class="btn primary" style="padding:10px 14px;">Atualizar</button>
        </div>
        <div class="full">
            <label for="tam">Tamanho (px): <?php echo (int)$tam; ?></label>
            <input type="range" id="tam" name="tam" min="200" max="700" step="20" value="<?php echo (int)$tam; ?>"
                   oninput="document.querySelector('label[for=tam]').textContent='Tamanho (px): '+this.value">
        </div>
    </form>
</div>

<div class="rodape">Sistema Lacres v2.0.0 &middot; Imprima e cole onde o pessoal possa escanear pelo celular.</div>

<!-- Fallback: gera QR no proprio navegador (offline) caso a imagem falhe -->
<script>
var URL_QR = <?php echo json_encode($url); ?>;
var TAM_QR = <?php echo (int)$tam; ?>;

function gerarQRLocal() {
    // Tenta carregar uma lib JS via CDN. Se a rede interna nao tiver
    // acesso externo, esta etapa tambem falha — nesse caso exibe a URL
    // como texto grande para digitacao manual.
    document.getElementById('fbMsg').style.display = 'block';
    var img = document.getElementById('qrimg');
    if (img) img.style.display = 'none';
    var canvas = document.getElementById('qrcanvas');
    canvas.style.display = 'block';

    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js';
    s.onload = function(){
        try {
            var typeNumber = 0; // auto
            var errorCorrectionLevel = 'M';
            var qr = qrcode(typeNumber, errorCorrectionLevel);
            qr.addData(URL_QR);
            qr.make();
            var modules = qr.getModuleCount();
            var cellSize = Math.floor(TAM_QR / modules);
            if (cellSize < 2) cellSize = 2;
            var size = cellSize * modules;
            canvas.width = size; canvas.height = size;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff'; ctx.fillRect(0,0,size,size);
            ctx.fillStyle = '#000';
            for (var r=0; r<modules; r++) {
                for (var c=0; c<modules; c++) {
                    if (qr.isDark(r,c)) ctx.fillRect(c*cellSize, r*cellSize, cellSize, cellSize);
                }
            }
        } catch (e) {
            mostraTextoFallback();
        }
    };
    s.onerror = mostraTextoFallback;
    document.head.appendChild(s);
}

function mostraTextoFallback() {
    document.getElementById('qrwrap').innerHTML =
        '<div style="padding:30px;border:2px dashed #ccc;border-radius:10px;">' +
        '<div style="font-size:14px;color:#991b1b;font-weight:700;margin-bottom:8px;">' +
        'Nao foi possivel gerar o QR Code (sem internet).</div>' +
        '<div style="font-size:13px;color:#374151;">Acesse manualmente:</div>' +
        '<div style="font-family:monospace;font-size:15px;font-weight:700;margin-top:6px;word-break:break-all;">' +
        URL_QR + '</div></div>';
}
</script>

</body>
</html>
