<?php
// =========================================================================
// encontra_posto_mobile.php — Versao 1.0.0
// Versao MOBILE de encontra_posto.php: le o codigo de barras do lote (19 dig)
// ou do display (35 dig) por CAMERA ao vivo / leitor Bluetooth / digitacao e
// VOCALIZA (TTS) o posto/regional. NAO grava nada na estante (somente leitura):
// usa os endpoints read-only resolver_posto_voz / resolver_display de
// encontra_posto.php.
//
// Pensada para Android Chrome (celular). Aberta normalmente via QR Code da
// pagina qrcode_paginas.php.
// =========================================================================

@date_default_timezone_set('America/Sao_Paulo');
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$temZxing = is_file(__DIR__ . '/assets/js/lib_zxing.min.js');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>&#128266; Encontrar posto por voz</title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #0f172a; color: #e5e7eb; padding: 0 0 40px 0;
}
.topo {
    position: sticky; top: 0; z-index: 50;
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    color: #fff; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.topo h1 { font-size: 16px; margin: 0; font-weight: 800; }
.topo a { color: #c7d2fe; text-decoration: none; font-size: 13px; font-weight: 700; }
.wrap { max-width: 640px; margin: 0 auto; padding: 14px; }

.cartao {
    background: #111827; border: 1px solid #1f2937; border-radius: 14px;
    padding: 18px 16px; text-align: center; margin-bottom: 14px;
}
#resultado { min-height: 110px; }
#resultado .voz {
    font-size: 30px; font-weight: 900; letter-spacing: .3px; line-height: 1.15;
    margin-bottom: 6px;
}
#resultado .det { font-size: 14px; color: #9ca3af; }
#resultado.ok    { border-color: #16a34a; background: #052e1a; }
#resultado.ok .voz { color: #4ade80; }
#resultado.alerta { border-color: #d97706; background: #2a1c05; }
#resultado.alerta .voz { color: #fbbf24; }
#resultado.erro  { border-color: #dc2626; background: #2a0a0a; }
#resultado.erro .voz { color: #f87171; }
.placeholder { color: #6b7280; font-size: 15px; }

.btns { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.btn {
    flex: 1 1 auto; border: none; padding: 14px 12px; border-radius: 12px;
    font-size: 15px; font-weight: 800; cursor: pointer; color: #fff;
}
.btn.cam { background: #2563eb; }
.btn.cam.on { background: #b91c1c; }
.btn.rep { background: #374151; flex: 0 0 auto; }

#cameraArea { display: none; position: relative; margin-bottom: 12px; border-radius: 12px; overflow: hidden; background: #000; }
#cameraArea video { width: 100%; display: block; max-height: 70vh; }
#cameraArea .mira {
    position: absolute; left: 8%; right: 8%; top: 33%; height: 34%;
    border: 3px solid rgba(74,222,128,.9); border-radius: 10px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,.35); pointer-events: none;
}
#cameraArea .mira.flash { border-color: #fff; }
#cameraArea .fechar-cam {
    position: absolute; top: 8px; right: 8px; z-index: 5;
    background: rgba(0,0,0,.6); color: #fff; border: none;
    width: 38px; height: 38px; border-radius: 50%; font-size: 20px; cursor: pointer;
}
#camDiag {
    position: absolute; left: 6px; bottom: 6px; background: rgba(0,0,0,.6);
    color: #9fffcf; font: 11px/1.3 monospace; padding: 3px 7px; border-radius: 5px;
    pointer-events: none; max-width: 80%;
}

.campo-leitor { margin-bottom: 12px; }
.campo-leitor label { display: block; font-size: 12px; color: #9ca3af; margin-bottom: 5px; text-transform: uppercase; font-weight: 700; }
.campo-leitor input {
    width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #334155;
    background: #0b1220; color: #e5e7eb; font-size: 18px; letter-spacing: 1px;
}

.msg { min-height: 20px; font-size: 13px; margin-bottom: 10px; color: #93c5fd; text-align: center; }
.msg.ok { color: #4ade80; }
.msg.erro { color: #f87171; }
.msg.info { color: #93c5fd; }

.hist h2 { font-size: 13px; color: #9ca3af; text-transform: uppercase; margin: 6px 0; }
.hist ul { list-style: none; margin: 0; padding: 0; }
.hist li {
    background: #111827; border: 1px solid #1f2937; border-left: 4px solid #475569;
    border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; font-size: 14px;
}
.hist li.ok { border-left-color: #16a34a; }
.hist li.alerta { border-left-color: #d97706; }
.hist li.erro { border-left-color: #dc2626; }
.hist li b { color: #e5e7eb; }
.hist li .cod { color: #6b7280; font-family: monospace; font-size: 11px; }

.aviso-http {
    display: none; background: #2a1c05; border: 1px solid #b45309; color: #fcd34d;
    border-radius: 10px; padding: 10px 12px; font-size: 12px; margin-bottom: 12px;
}
</style>
</head>
<body>

<div class="topo">
    <h1>&#128266; Encontrar posto por voz</h1>
    <a href="encontra_posto.php">Triagem &#8594;</a>
</div>

<div class="wrap">

    <div class="aviso-http" id="avisoHttp">
        Para a <b>câmera ao vivo</b> em rede sem HTTPS, ative uma vez no Chrome do
        celular: <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>
        (adicione o endereço deste site). Sem isso, use o <b>leitor</b> / digitação.
    </div>

    <div class="cartao" id="resultado">
        <div class="placeholder">Bipe ou aponte a câmera para um código de barras.<br>O sistema vai <b>falar</b> o posto/regional.</div>
    </div>

    <div class="btns">
        <button type="button" class="btn cam" id="btnCamera" onclick="alternarCamera()">🎥 Câmera ao vivo</button>
        <button type="button" class="btn rep" id="btnRepetir" onclick="repetirUltima()">🔊 Repetir</button>
    </div>

    <div id="cameraArea">
        <button type="button" class="fechar-cam" onclick="pararCamera()">&times;</button>
        <video id="video" playsinline muted></video>
        <div class="mira" id="mira"></div>
        <div id="camDiag"></div>
    </div>

    <div class="campo-leitor">
        <label for="campoLeitor">Leitor Bluetooth / digitar código</label>
        <input type="text" id="campoLeitor" inputmode="numeric" autocomplete="off"
               placeholder="Bipe aqui ou digite e tecle Enter">
    </div>

    <div class="msg info" id="msg">Pronto.</div>

    <div class="hist">
        <h2>Últimas leituras</h2>
        <ul id="listaHist"></ul>
    </div>

</div>

<?php if ($temZxing): ?>
<script src="assets/js/lib_zxing.min.js"></script>
<?php endif; ?>
<script>
var ZXING_OK = (typeof ZXing !== 'undefined' && ZXing);

function so_digitos(s) { return ('' + (s || '')).replace(/\D+/g, ''); }
function pad(n, w) { n = '' + n; while (n.length < w) { n = '0' + n; } return n; }

// ---------- Mensagem ----------
function setMsg(txt, tipo) {
    var m = document.getElementById('msg');
    if (!m) { return; }
    m.textContent = txt || '';
    m.className = 'msg ' + (tipo || 'info');
}

// ---------- Voz (TTS) ----------
var vozPt = null, vozPronta = false;
function carregarVozes() {
    if (typeof speechSynthesis === 'undefined') { return; }
    var vs = speechSynthesis.getVoices();
    for (var i = 0; i < vs.length; i++) {
        var l = (vs[i].lang || '').toLowerCase();
        if (l.indexOf('pt') === 0) { vozPt = vs[i]; if (l.indexOf('br') !== -1) { break; } }
    }
    vozPronta = true;
}
if (typeof speechSynthesis !== 'undefined') {
    carregarVozes();
    if (typeof speechSynthesis.onvoiceschanged !== 'undefined') {
        speechSynthesis.onvoiceschanged = carregarVozes;
    }
}
function falar(texto) {
    if (!texto || typeof speechSynthesis === 'undefined' || typeof SpeechSynthesisUtterance === 'undefined') { return; }
    try {
        speechSynthesis.cancel();
        if (typeof speechSynthesis.resume === 'function') { speechSynthesis.resume(); }
        var u = new SpeechSynthesisUtterance(texto);
        u.lang = 'pt-BR'; u.rate = 1.0; u.pitch = 1.0; u.volume = 1.0;
        if (vozPt) { u.voice = vozPt; }
        speechSynthesis.speak(u);
    } catch (e) {}
}

// Destrava a voz no 1o toque (politica de audio do mobile)
var vozDestravada = false;
function destravarVoz() {
    if (vozDestravada || typeof speechSynthesis === 'undefined') { return; }
    try { var u = new SpeechSynthesisUtterance(' '); u.volume = 0; speechSynthesis.speak(u); } catch (e) {}
    vozDestravada = true;
}
document.addEventListener('touchstart', destravarVoz, { once: true });
document.addEventListener('click', destravarVoz, { once: true });

// ---------- Beep curto (confirmacao) ----------
var audioCtx = null;
function bipar(ok) {
    try {
        if (!audioCtx) { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
        var o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.type = 'sine'; o.frequency.value = ok ? 880 : 300;
        g.gain.value = 0.08; o.connect(g); g.connect(audioCtx.destination);
        o.start(); o.stop(audioCtx.currentTime + 0.12);
    } catch (e) {}
}
function vibrar(p) { try { if (navigator.vibrate) { navigator.vibrate(p); } } catch (e) {} }

// ---------- Resultado + historico ----------
var ultimaVoz = '';
function mostrarResultado(voz, detalhe, classe) {
    var r = document.getElementById('resultado');
    r.className = 'cartao ' + (classe || '');
    r.innerHTML = '<div class="voz">' + escHtml(voz) + '</div>' +
                  (detalhe ? '<div class="det">' + escHtml(detalhe) + '</div>' : '');
}
function escHtml(s) {
    return ('' + (s || '')).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function addHist(voz, codbar, classe) {
    var ul = document.getElementById('listaHist');
    var li = document.createElement('li');
    li.className = classe || '';
    var hora = new Date().toLocaleTimeString();
    li.innerHTML = '<b>' + escHtml(voz) + '</b><br><span class="cod">' + escHtml(codbar) + ' · ' + hora + '</span>';
    ul.insertBefore(li, ul.firstChild);
    while (ul.children.length > 30) { ul.removeChild(ul.lastChild); }
}
function repetirUltima() {
    if (ultimaVoz) { falar(ultimaVoz); } else { setMsg('Nenhuma leitura ainda.', 'info'); }
}

// ---------- Pipeline de leitura ----------
var ultimoCod = '', ultimoTs = 0, buscando = false;
function processarLeitura(bruto) {
    var d = so_digitos(bruto);
    // Normaliza: display tem 35 díg, lote tem 19 — checar 35 ANTES de 19 para
    // nao truncar um display para 19 (senao o caminho resolver_display fica morto).
    if (d.length >= 35) { d = d.slice(-35); }
    else if (d.length > 19) { d = d.slice(-19); }
    var agora = (new Date()).getTime();
    if (d && d === ultimoCod && (agora - ultimoTs) < 1500) { return; } // dedup
    if (d.length !== 19 && d.length !== 35) {
        setMsg('Código com ' + d.length + ' díg — alinhe o código todo na mira.', 'info');
        return;
    }
    ultimoCod = d; ultimoTs = agora;
    resolver(d);
}
// exporto p/ reuso/teste, no mesmo contrato da tela principal
window.processarLeituraCodigo = processarLeitura;

function resolver(d) {
    if (buscando) { return; }
    buscando = true;
    setMsg('Consultando ' + d + '…', 'info');
    var url;
    if (d.length === 35) {
        url = 'encontra_posto.php?resolver_display=1&leitura=' + encodeURIComponent(d);
    } else {
        url = 'encontra_posto.php?resolver_posto_voz=1&codbar=' + encodeURIComponent(d);
    }
    fetch(url, { headers: { 'X-Requested-With': 'fetch' } }).then(function (res) {
        return res.text();
    }).then(function (txt) {
        var j = null;
        try { j = JSON.parse(txt); } catch (e) { j = null; }
        if (!j || !j.success) {
            var err = (j && j.erro) ? j.erro : 'não foi possível consultar';
            mostrarResultado('Erro', err, 'erro');
            setMsg('Falha: ' + err, 'erro');
            bipar(false); vibrar([80, 50, 80]);
            return;
        }
        if (j.encontrado === false) {
            var det35 = (d.length === 35) ? ('Display ' + d + ' — não cadastrado') : ('Lote ' + d.slice(0, 8));
            mostrarResultado('Posto não encontrado', det35, 'alerta');
            falar('Posto não encontrado');
            addHist('Posto não encontrado', d, 'alerta');
            setMsg('Não encontrei o posto deste código.', 'erro');
            bipar(false); vibrar([60, 40, 60]);
            return;
        }
        var voz = j.voz || ('Posto ' + (j.posto_int || j.posto || ''));
        var det = '';
        if (d.length === 35) {
            det = 'Display · Posto ' + (j.posto || '') + (j.nome ? ' · ' + limparNome(j.nome) : '');
        } else {
            det = 'Lote ' + (j.lote || d.slice(0, 8)) + ' · Posto ' + (j.posto || '') +
                  (j.regional_pad ? ' · Regional ' + j.regional_pad : '');
        }
        ultimaVoz = voz;
        mostrarResultado(voz, det, 'ok');
        falar(voz);
        addHist(voz, d, 'ok');
        setMsg('✔ ' + voz, 'ok');
        bipar(true); vibrar(120);
        flashMira();
    }).catch(function () {
        mostrarResultado('Sem conexão', 'Não consegui falar com o servidor', 'erro');
        setMsg('Falha de rede ao consultar.', 'erro');
        bipar(false); vibrar([80, 50, 80]);
    }).then(function () {
        buscando = false;
    });
}
function limparNome(nome) {
    var n = ('' + (nome || '')).replace(/^\s*\d+\s*-\s*/, '');
    return n;
}

// ---------- Campo "estilo leitor" ----------
(function () {
    var campo = document.getElementById('campoLeitor');
    if (!campo) { return; }
    var timer = null;
    campo.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.keyCode === 13) {
            ev.preventDefault();
            var v = so_digitos(campo.value);
            if (v) { processarLeitura(v); }
            campo.value = '';
        }
    });
    campo.addEventListener('input', function () {
        var v = so_digitos(campo.value);
        if (timer) { clearTimeout(timer); }
        // Auto-dispara quando atinge tamanho de lote (19) ou display (35)
        if (v.length === 19 || v.length === 35) {
            processarLeitura(v);
            campo.value = '';
            return;
        }
        timer = setTimeout(function () {
            var w = so_digitos(campo.value);
            if (w.length >= 19) { processarLeitura(w); campo.value = ''; }
        }, 350);
    });
})();

// =====================================================================
// CAMERA AO VIVO — BarcodeDetector NATIVO (primario) + ZXing ROI (fallback)
// (mesma abordagem das telas de conferencia)
// =====================================================================
var cameraAtiva = false, abrindo = false, geracao = 0;
var streamCam = null, loopTimer = null;
var leitorROI = null, roiCanvas = null, roiCtx = null, codeReader = null;
var detectorNativo = null, nativoFalhou = false, quadros = 0;

if (typeof window.isSecureContext !== 'undefined' && !window.isSecureContext) {
    var av = document.getElementById('avisoHttp');
    if (av) { av.style.display = 'block'; }
}

function temDetectorNativo() { return (typeof window.BarcodeDetector === 'function'); }
function criarDetectorNativo() {
    if (detectorNativo || nativoFalhou || !temDetectorNativo()) { return; }
    try { detectorNativo = new window.BarcodeDetector(); }
    catch (e) { detectorNativo = null; nativoFalhou = true; }
}
function setDiag(txt) { var d = document.getElementById('camDiag'); if (d) { d.textContent = txt || ''; } }
function temPrimitivasROI() {
    return !!(ZXING_OK && ZXing.MultiFormatReader && ZXing.BinaryBitmap &&
              ZXing.HybridBinarizer && ZXing.HTMLCanvasElementLuminanceSource);
}
function montarHintsROI() {
    var h = new Map();
    try {
        if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
            h.set(ZXing.DecodeHintType.TRY_HARDER, true);
            h.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                ZXing.BarcodeFormat.CODE_128, ZXing.BarcodeFormat.ITF,
                ZXing.BarcodeFormat.CODE_39, ZXing.BarcodeFormat.CODE_93,
                ZXing.BarcodeFormat.CODABAR, ZXing.BarcodeFormat.EAN_13, ZXing.BarcodeFormat.EAN_8
            ]);
        }
    } catch (e) {}
    return h;
}
function novoReader() {
    try {
        if (!ZXING_OK || !ZXing.BrowserMultiFormatReader) { return null; }
        var hints = montarHintsROI();
        return new ZXing.BrowserMultiFormatReader(hints, 200);
    } catch (e) { return null; }
}
function entregarLeitura(txt) {
    var d = so_digitos(txt);
    // 35 (display) ANTES de 19 (lote) — senao um display de 35 cai no ramo de 19.
    if (d.length >= 35) { processarLeitura(d.slice(-35)); return; }
    if (d.length >= 19) { processarLeitura(d.slice(-19)); return; }
    if (d.length > 0) { setDiag('Detectado ' + d.length + ' díg — alinhe na mira'); }
}
function flashMira() {
    var m = document.getElementById('mira');
    if (!m) { return; }
    m.className = 'mira flash';
    setTimeout(function () { m.className = 'mira'; }, 220);
}

window.alternarCamera = function () {
    if (cameraAtiva) { pararCamera(); } else { iniciarCamera(); }
};

function iniciarCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setMsg('Este navegador bloqueia a câmera ao vivo (precisa de HTTPS). Use o leitor/digitação.', 'erro');
        return;
    }
    if (cameraAtiva || abrindo) { return; }
    if (!temPrimitivasROI() && !temDetectorNativo()) { iniciarCameraLegado(); return; }

    abrindo = true;
    var minhaGeracao = ++geracao;
    setMsg('Abrindo câmera…', 'info');

    var tentativasCam = [
        { audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } } },
        { audio: false, video: { facingMode: { ideal: 'environment' } } },
        { audio: false, video: { facingMode: 'environment' } },
        { audio: false, video: true }
    ];
    function pedirCamera(i) {
        return navigator.mediaDevices.getUserMedia(tentativasCam[i]).catch(function (err) {
            if (i + 1 < tentativasCam.length && err && err.name !== 'NotAllowedError' && err.name !== 'SecurityError') {
                return pedirCamera(i + 1);
            }
            throw err;
        });
    }

    pedirCamera(0).then(function (s) {
        if (minhaGeracao !== geracao) { try { s.getTracks().forEach(function (t) { t.stop(); }); } catch (e) {} return; }
        streamCam = s;
        var video = document.getElementById('video');
        document.getElementById('cameraArea').style.display = 'block';
        var btn = document.getElementById('btnCamera');
        btn.textContent = '⏹ Parar câmera'; btn.className = 'btn cam on';
        video.srcObject = s; video.muted = true;
        var p = video.play(); if (p && p.catch) { p.catch(function () {}); }
        tocarParaFocar();

        cameraAtiva = true; abrindo = false;
        setMsg('Câmera ligada. Aponte para o código de barras.', 'ok');

        if (temPrimitivasROI()) {
            if (!leitorROI) { leitorROI = new ZXing.MultiFormatReader(); leitorROI.setHints(montarHintsROI()); }
            if (!roiCanvas) { roiCanvas = document.createElement('canvas'); roiCtx = roiCanvas.getContext('2d'); }
        }
        quadros = 0;
        criarDetectorNativo();
        loopROI(minhaGeracao);
    }).catch(function (er) {
        abrindo = false;
        var fechado = (minhaGeracao !== geracao);
        pararCamera();
        if (fechado) { return; }
        var nome = (er && er.name) ? er.name : 'erro';
        var dica;
        if (nome === 'NotReadableError' || nome === 'TrackStartError' || nome === 'AbortError') {
            dica = 'A câmera parece estar em uso por outra aba/app. Feche as outras e tente de novo.';
        } else if (nome === 'NotAllowedError' || nome === 'SecurityError') {
            dica = 'Permissão negada. Toque no cadeado/ⓘ ao lado do endereço, permita a Câmera (em HTTP, use o flag indicado acima).';
        } else if (nome === 'NotFoundError' || nome === 'OverconstrainedError') {
            dica = 'Nenhuma câmera compatível. Use o leitor/digitação.';
        } else {
            dica = 'Tente de novo; se persistir, use o leitor/digitação.';
        }
        setMsg('Não consegui abrir a câmera (' + nome + '). ' + dica, 'erro');
    });
}

function escolherCodigoNativo(cods) {
    for (var i = 0; i < cods.length; i++) {
        var n = so_digitos(cods[i].rawValue).length;
        if (n >= 19) { return cods[i].rawValue; }
    }
    return cods[0].rawValue;
}

function loopROI(minhaGeracao) {
    if (!cameraAtiva || minhaGeracao !== geracao) { return; }
    var video = document.getElementById('video');
    var vw = video.videoWidth || 0, vh = video.videoHeight || 0;
    quadros++;

    if (detectorNativo && !nativoFalhou) {
        if (!vw || !vh) { loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 120); return; }
        setDiag('Leitor nativo • ' + vw + 'x' + vh + ' • q' + quadros);
        detectorNativo.detect(video).then(function (cods) {
            if (!cameraAtiva || minhaGeracao !== geracao) { return; }
            if (cods && cods.length) {
                var bruto = escolherCodigoNativo(cods);
                setDiag('Nativo leu ' + so_digitos(bruto).length + ' díg');
                try { entregarLeitura(bruto); } catch (ePipe) {}
            }
        }).catch(function (err) {
            if (err && err.name && err.name !== 'InvalidStateError') {
                nativoFalhou = true; setDiag('Nativo indisponível (' + err.name + ') — usando ZXing');
            }
        }).then(function () {
            if (!cameraAtiva || minhaGeracao !== geracao) { return; }
            loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 120);
        });
        return;
    }

    try {
        if (vw && vh && temPrimitivasROI()) {
            var rx = Math.floor(vw * 0.08), rw = Math.floor(vw * 0.84);
            var ry = Math.floor(vh * 0.33), rh = Math.floor(vh * 0.34);
            var escala = (rw < 800) ? 2 : 1;
            var cw = rw * escala, ch = rh * escala;
            if (roiCanvas.width !== cw) { roiCanvas.width = cw; }
            if (roiCanvas.height !== ch) { roiCanvas.height = ch; }
            roiCtx.drawImage(video, rx, ry, rw, rh, 0, 0, cw, ch);
            setDiag('ZXing • ' + vw + 'x' + vh + ' • q' + quadros);
            var src = new ZXing.HTMLCanvasElementLuminanceSource(roiCanvas);
            var bmp = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(src));
            var resultado = leitorROI.decodeWithState(bmp);
            if (resultado) { entregarLeitura(resultado.getText ? resultado.getText() : ('' + resultado)); }
        }
    } catch (e) { /* NotFoundException por quadro = normal */ }
    loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 180);
}

function tocarParaFocar() {
    try {
        if (streamCam && streamCam.getVideoTracks) {
            var track = streamCam.getVideoTracks()[0];
            if (track && track.applyConstraints) {
                track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(function () {});
            }
        }
    } catch (e) {}
}

function iniciarCameraLegado() {
    document.getElementById('cameraArea').style.display = 'block';
    var btn = document.getElementById('btnCamera');
    btn.textContent = '⏹ Parar câmera'; btn.className = 'btn cam on';
    codeReader = novoReader();
    if (!codeReader) { setMsg('Não foi possível iniciar o leitor de câmera. Use o leitor/digitação.', 'erro'); pararCamera(); return; }
    setMsg('Abrindo câmera…', 'info');
    codeReader.decodeFromConstraints(
        { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } },
        'video',
        function (result) { if (result) { entregarLeitura(result.getText ? result.getText() : ('' + result)); } }
    ).then(function () {
        cameraAtiva = true; setMsg('Câmera ligada. Aponte para o código de barras.', 'ok');
    }).catch(function () {
        pararCamera();
        setMsg('Não consegui abrir a câmera ao vivo (segurança do navegador). Use o leitor/digitação.', 'erro');
    });
}

window.pararCamera = function () {
    geracao++;
    cameraAtiva = false; abrindo = false;
    if (loopTimer) { clearTimeout(loopTimer); loopTimer = null; }
    try { if (codeReader && codeReader.reset) { codeReader.reset(); } } catch (e) {}
    try { if (streamCam) { streamCam.getTracks().forEach(function (t) { t.stop(); }); } } catch (e2) {}
    streamCam = null;
    var video = document.getElementById('video');
    if (video) { try { video.srcObject = null; } catch (e3) {} }
    var area = document.getElementById('cameraArea');
    if (area) { area.style.display = 'none'; }
    var btn = document.getElementById('btnCamera');
    if (btn) { btn.textContent = '🎥 Câmera ao vivo'; btn.className = 'btn cam'; }
};

(function () {
    var area = document.getElementById('cameraArea');
    if (area) {
        area.addEventListener('click', function (ev) {
            var cls = ev && ev.target ? ('' + (ev.target.className || '')) : '';
            if (cls.indexOf('fechar-cam') !== -1) { return; }
            tocarParaFocar();
        });
    }
})();
</script>
</body>
</html>
