<?php
/**
 * conferencia_camera.php
 *
 * Conferencia de lotes por CAMERA do celular (offline).
 *
 * Recebe (POST) a lista de lotes da tela conferencia_pacotes.php (apos o filtro)
 * em "lotes_json" e monta uma pagina ESPELHO dos lotes. A conferencia e feita
 * 100% no proprio aparelho (sem trafegar dados da camera pela rede):
 *   - Opcao 1: tirar foto do codigo de barras (1 toque) -> decodificado localmente.
 *   - Opcao 2: campo "estilo leitor" (app de teclado-scanner / leitor Bluetooth)
 *              e tambem camera ao vivo (best-effort; depende do navegador liberar).
 * Cada lote lido fica VERDE. No fim, exporta a lista conferida para marcar "s"
 * na planilha.
 *
 * Compatibilidade: o PHP do servidor segue 5.3.3 (array(), sem closures pesadas).
 * O JavaScript e moderno de proposito: esta pagina e mobile-only (Android Chrome).
 *
 * Decodificador 1D Code-128 OFFLINE: assets/js/lib_zxing.min.js (@zxing/library, UMD),
 * embutido inline para a pagina funcionar salva localmente, sem internet.
 *
 * Layout do codigo de barras de 19 digitos:
 *   posicoes 0-7  (8) = lote
 *   posicoes 8-10 (3) = regional
 *   posicoes 11-13(3) = posto
 *   posicoes 14-18(5) = quantidade
 */

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// --- Entrada: lista de lotes vinda do POST (ou GET de fallback) ---
$lotesJsonBruto = '';
if (isset($_POST['lotes_json'])) {
    $lotesJsonBruto = (string)$_POST['lotes_json'];
} elseif (isset($_GET['lotes_json'])) {
    $lotesJsonBruto = (string)$_GET['lotes_json'];
}

$periodo = '';
if (isset($_POST['periodo'])) {
    $periodo = trim((string)$_POST['periodo']);
} elseif (isset($_GET['periodo'])) {
    $periodo = trim((string)$_GET['periodo']);
}

$usuario = '';
if (isset($_POST['usuario'])) {
    $usuario = trim((string)$_POST['usuario']);
} elseif (isset($_GET['usuario'])) {
    $usuario = trim((string)$_GET['usuario']);
}

$lotes = array();
if ($lotesJsonBruto !== '') {
    $decodificado = json_decode($lotesJsonBruto, true);
    if (is_array($decodificado)) {
        foreach ($decodificado as $item) {
            if (!is_array($item)) {
                continue;
            }
            $lote = isset($item['lote']) ? preg_replace('/\D/', '', (string)$item['lote']) : '';
            if ($lote === '') {
                continue;
            }
            $codigo = isset($item['codigo']) ? preg_replace('/\D/', '', (string)$item['codigo']) : '';
            $lotes[] = array(
                'lote'            => $lote,
                'codigo'          => $codigo,
                'regional'        => isset($item['regional']) ? trim((string)$item['regional']) : '',
                'regional_codigo' => isset($item['regional_codigo']) ? trim((string)$item['regional_codigo']) : '',
                'posto'           => isset($item['posto']) ? trim((string)$item['posto']) : '',
                'qtd'             => isset($item['qtd']) ? trim((string)$item['qtd']) : '',
                'data'            => isset($item['data']) ? trim((string)$item['data']) : '',
                'data_sql'        => isset($item['data_sql']) ? trim((string)$item['data_sql']) : '',
                'ispt'            => (isset($item['ispt']) && (int)$item['ispt'] === 1) ? 1 : 0,
                'pt_group'        => isset($item['pt_group']) ? trim((string)$item['pt_group']) : '',
                'usuario_prod'    => isset($item['usuario_prod']) ? trim((string)$item['usuario_prod']) : '',
                'conf'            => (isset($item['conf']) && (int)$item['conf'] === 1) ? 1 : 0
            );
        }
    }
}

$totalLotes = count($lotes);

// --- Decodificador offline embutido ---
$zxingPath = dirname(__FILE__) . '/assets/js/lib_zxing.min.js';
$zxingDisponivel = is_readable($zxingPath);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Conferência por câmera</title>
<style>
* { box-sizing: border-box; }
body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#0e1726; color:#1b2433; }
.topo { position:sticky; top:0; z-index:50; background:#0b5e57; color:#fff; padding:10px 12px; box-shadow:0 2px 6px rgba(0,0,0,.3); }
.topo h1 { margin:0; font-size:16px; }
.topo .sub { font-size:11px; opacity:.85; margin-top:2px; }
.progresso-wrap { background:#08443f; border-radius:8px; margin-top:8px; height:22px; overflow:hidden; position:relative; }
.progresso-barra { background:#23c552; height:100%; width:0%; transition:width .25s; }
.progresso-txt { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.5); }
.acoes { background:#16243a; padding:10px 12px; display:flex; flex-wrap:wrap; gap:8px; }
.acoes button, .acoes label.botao { flex:1 1 auto; min-width:46%; text-align:center; padding:12px 8px; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; color:#fff; }
.b-cam { background:#2563eb; }
.b-foto { background:#0b9488; }
.b-export { background:#7e3ff2; }
.b-offline { background:#475569; }
.b-reset { background:#b91c1c; }
.acoes label.botao input { display:none; }
.leitor-box { background:#16243a; padding:0 12px 10px; }
.leitor-box input { width:100%; padding:12px; font-size:18px; border:2px solid #2563eb; border-radius:8px; text-align:center; letter-spacing:1px; }
.leitor-box .dica { color:#9fb3c8; font-size:11px; margin-top:6px; }
#cameraArea { display:none; background:#000; position:relative; }
#cameraArea video { width:100%; max-height:46vh; display:block; object-fit:cover; }
#cameraArea .mira { position:absolute; left:8%; right:8%; top:35%; height:30%; border:3px solid rgba(35,197,82,.9); border-radius:8px; box-shadow:0 0 0 9999px rgba(0,0,0,.25); pointer-events:none; }
#cameraArea .fechar-cam { position:absolute; top:8px; right:8px; background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; }
#cameraArea #camDiag { position:absolute; left:6px; bottom:6px; background:rgba(0,0,0,.6); color:#9fffcf; font:11px/1.3 monospace; padding:3px 7px; border-radius:5px; pointer-events:none; max-width:80%; }
.msg { padding:8px 12px; font-size:13px; font-weight:700; text-align:center; }
.msg.ok { background:#143d2a; color:#5ff09a; }
.msg.erro { background:#3d1414; color:#ff9a9a; }
.msg.info { background:#1e2c44; color:#bcd3ee; }
.lista { padding:8px 8px 80px; }
.lote-card { background:#fff; border-radius:10px; padding:10px 12px; margin:8px 6px; box-shadow:0 1px 3px rgba(0,0,0,.4); border-left:6px solid #cbd5e1; }
.lote-card.confirmado { background:#d6f5e0; border-left-color:#1b6c34; }
.lote-card .ln1 { display:flex; align-items:baseline; justify-content:space-between; }
.lote-card .num { font-size:20px; font-weight:800; color:#0e1726; letter-spacing:1px; }
.lote-card.confirmado .num { color:#0f5128; }
.lote-card .check { font-size:18px; font-weight:800; color:#1b6c34; display:none; }
.lote-card.confirmado .check { display:inline; }
.lote-card .meta { font-size:12px; color:#52617a; margin-top:3px; }
.lote-card .meta b { color:#2b3a52; }
.lote-card .barra { font-family: 'Courier New', monospace; font-size:11px; color:#7b8aa3; margin-top:4px; word-break:break-all; }
.vazio { color:#9fb3c8; text-align:center; padding:40px 16px; font-size:14px; }
.rodape-fixo { position:fixed; left:0; right:0; bottom:0; background:#0b5e57; color:#fff; padding:8px 12px; font-size:12px; display:flex; justify-content:space-between; align-items:center; z-index:40; }
.rodape-fixo a { color:#fff; }
@media (min-width: 700px) { .acoes button, .acoes label.botao { min-width:0; } }
</style>
</head>
<body>

<div class="topo">
    <h1>📷 Conferência por câmera</h1>
    <div class="sub">
        <?php echo $periodo !== '' ? 'Período: ' . e($periodo) . ' — ' : ''; ?>
        <span id="contadorTopo"><?php echo (int)$totalLotes; ?></span> lote(s) para conferir
    </div>
    <div class="progresso-wrap">
        <div class="progresso-barra" id="progressoBarra"></div>
        <div class="progresso-txt" id="progressoTxt">0 / <?php echo (int)$totalLotes; ?> conferidos</div>
    </div>
</div>

<div class="acoes">
    <button type="button" class="b-cam" id="btnCamera" onclick="alternarCamera()">🎥 Câmera ao vivo</button>
    <label class="botao b-foto">📸 Tirar foto
        <input type="file" id="inputFoto" accept="image/*" capture="environment">
    </label>
    <button type="button" class="b-export" onclick="exportarConferidos()">📤 Exportar conferidos</button>
    <button type="button" class="b-offline" onclick="salvarOffline()">💾 Salvar offline</button>
    <button type="button" class="b-reset" onclick="resetarConferencia()">🔄 Resetar</button>
</div>

<div id="cameraArea">
    <video id="video" playsinline muted></video>
    <div class="mira"></div>
    <div id="camDiag"></div>
    <button type="button" class="fechar-cam" onclick="pararCamera()">✕</button>
</div>

<div class="leitor-box">
    <input type="text" id="campoLeitor" inputmode="numeric" autocomplete="off"
           placeholder="Bipe aqui (leitor/app) ou digite o lote/código">
    <div class="dica">Funciona com leitor Bluetooth ou app de teclado-scanner. Também aceita digitar o nº do lote (8 dígitos) ou o código de 19 dígitos.</div>
</div>

<div class="msg info" id="msg">Aponte a câmera/foto para o código de barras, ou bipe no campo acima.</div>

<div style="display:none" aria-hidden="true">
    <audio id="concluido" src="assets/audio/concluido.mp3" preload="auto"></audio>
    <audio id="pacotejaconferido" src="assets/audio/pacotejaconferido.mp3" preload="auto"></audio>
    <audio id="pacotedeoutraregional" src="assets/audio/pacotedeoutraregional.mp3" preload="auto"></audio>
    <audio id="pacote_nao_encontrado" src="assets/audio/pacote_nao_foi_encontrado.mp3" preload="auto"></audio>
</div>

<div class="lista" id="lista">
<?php if ($totalLotes === 0): ?>
    <div class="vazio">Nenhum lote recebido.<br>Volte para a tela de conferência, aplique o filtro e toque em "Conferir por câmera".</div>
<?php else: ?>
    <?php foreach ($lotes as $idx => $lt): ?>
    <div class="lote-card<?php echo $lt['conf'] === 1 ? ' confirmado' : ''; ?>"
         id="card_<?php echo (int)$idx; ?>"
         data-lote="<?php echo e($lt['lote']); ?>"
         data-codigo="<?php echo e($lt['codigo']); ?>"
         data-regional="<?php echo e($lt['regional']); ?>"
         data-regional-codigo="<?php echo e($lt['regional_codigo']); ?>"
         data-posto="<?php echo e($lt['posto']); ?>"
         data-qtd="<?php echo e($lt['qtd']); ?>"
         data-dataexp="<?php echo e($lt['data_sql']); ?>"
         data-ispt="<?php echo (int)$lt['ispt']; ?>"
         data-pt-group="<?php echo e($lt['pt_group']); ?>">
        <div class="ln1">
            <span class="num"><?php echo e(str_pad($lt['lote'], 8, '0', STR_PAD_LEFT)); ?></span>
            <span class="check">✔ CONFERIDO</span>
            <span class="savestatus" style="font-size:11px; margin-left:8px; font-weight:700;"></span>
        </div>
        <div class="meta">
            <?php if ($lt['posto'] !== ''): ?>Posto <b><?php echo e($lt['posto']); ?></b> · <?php endif; ?>
            <?php if ($lt['regional'] !== ''): ?>Reg. <b><?php echo e($lt['regional']); ?></b> · <?php endif; ?>
            <?php if ($lt['qtd'] !== ''): ?>Qtd <b><?php echo e($lt['qtd']); ?></b><?php endif; ?>
            <?php if ($lt['data'] !== ''): ?> · <?php echo e($lt['data']); ?><?php endif; ?>
            <?php if ($lt['usuario_prod'] !== ''): ?> · Exp.: <b><?php echo e($lt['usuario_prod']); ?></b><?php endif; ?>
        </div>
        <?php if ($lt['codigo'] !== ''): ?>
        <div class="barra"><?php echo e($lt['codigo']); ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="rodape-fixo">
    <span>✅ <span id="rodapeContador">0</span> de <?php echo (int)$totalLotes; ?></span>
    <a href="javascript:window.close();">Fechar</a>
</div>

<?php if ($zxingDisponivel): ?>
<script>
<?php readfile($zxingPath); ?>
</script>
<?php endif; ?>

<script>
(function () {
    "use strict";

    var ZXING_OK = (typeof ZXing !== 'undefined');
    var codeReader = null;     // leitor de imagem/video (ZXing)
    var cameraAtiva = false;

    function novoReader() {
        if (!ZXING_OK) { return null; }
        try {
            var hints = new Map();
            try { if (ZXing.DecodeHintType) { hints.set(ZXing.DecodeHintType.TRY_HARDER, true); } } catch (eH) {}
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                ZXing.BarcodeFormat.CODE_128,
                ZXing.BarcodeFormat.CODE_39,
                ZXing.BarcodeFormat.ITF,
                ZXing.BarcodeFormat.EAN_13,
                ZXing.BarcodeFormat.CODE_93
            ]);
            return new ZXing.BrowserMultiFormatReader(hints, 200);
        } catch (e) {
            try { return new ZXing.BrowserMultiFormatReader(); } catch (e2) { return null; }
        }
    }

    function so_digitos(s) { return ('' + (s || '')).replace(/\D/g, ''); }

    // Normaliza igual a conferencia_pacotes.php: se vier "lixo" do leitor com
    // digitos a mais, mantem os ULTIMOS 19 (o codigo real fica no fim).
    function normalizar19(bruto) {
        var d = so_digitos(bruto);
        if (d.length > 19) { d = d.slice(-19); }
        return d;
    }

    // O lote tem SEMPRE 8 digitos (zero-padded). Completa com zeros a esquerda.
    function pad8(s) {
        s = String(s || '');
        while (s.length < 8) { s = '0' + s; }
        return s;
    }

    // Extrai o lote a partir do que foi lido (19 dig -> posicoes 0-7; ou 8 dig direto;
    // menos de 8 -> completa com zeros: "770475" e o MESMO lote que "00770475").
    function loteDoCodigo(bruto) {
        var d = normalizar19(bruto);
        if (d.length >= 8) { return d.substr(0, 8); }
        return pad8(d);
    }

    // Indexa os cards por lote e por codigo completo.
    var porLote = {};
    var porCodigo = {};
    var cards = document.querySelectorAll('.lote-card');
    for (var i = 0; i < cards.length; i++) {
        var c = cards[i];
        var l = pad8(so_digitos(c.getAttribute('data-lote')));
        var cod = so_digitos(c.getAttribute('data-codigo'));
        if (l) {
            if (!porLote[l]) { porLote[l] = []; }
            porLote[l].push(c);
        }
        if (cod) { porCodigo[cod] = c; }
    }
    var TOTAL = cards.length;

    function contarConfirmados() {
        var n = 0;
        for (var i = 0; i < cards.length; i++) {
            if (cards[i].className.indexOf('confirmado') !== -1) { n++; }
        }
        return n;
    }

    function atualizarProgresso() {
        var n = contarConfirmados();
        var pct = TOTAL > 0 ? Math.round((n / TOTAL) * 100) : 0;
        document.getElementById('progressoBarra').style.width = pct + '%';
        document.getElementById('progressoTxt').textContent = n + ' / ' + TOTAL + ' conferidos';
        document.getElementById('rodapeContador').textContent = n;
    }

    var ctxAudio = null;
    function bipar(ok) {
        try {
            if (!ctxAudio) {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) { return; }
                ctxAudio = new AC();
            }
            var osc = ctxAudio.createOscillator();
            var g = ctxAudio.createGain();
            osc.connect(g); g.connect(ctxAudio.destination);
            osc.type = 'square';
            osc.frequency.value = ok ? 880 : 200;
            g.gain.value = 0.08;
            osc.start();
            setTimeout(function () { osc.stop(); }, ok ? 120 : 260);
        } catch (e) {}
    }

    function vibrar(ms) { try { if (navigator.vibrate) { navigator.vibrate(ms); } } catch (e) {} }

    function setMsg(txt, tipo) {
        var m = document.getElementById('msg');
        m.textContent = txt;
        m.className = 'msg ' + (tipo || 'info');
    }

    var ultimoProcessado = '';
    var ultimoTs = 0;

    // ---- Salvar no banco + avisos (igual a versao web conferencia_pacotes.php) ----
    var usuarioConf = <?php echo json_encode($usuario); ?>;
    var ehFile = (location.protocol === 'file:');   // pagina salva offline: sem servidor
    var contextoAtual = null;        // grupo (regional/posto) em conferencia agora
    var codigosSalvando = {};        // guarda anti duplo-POST (codigo em voo)
    var overrideCodigo = '';         // codigo da tentativa de troca de regional
    var overrideContador = 0;        // 4 leituras seguidas trocam o contexto
    var overrideTs = 0;              // espaca as contagens do override
    var statusSave = {};             // codigo -> 'salvando'|'salvo'|'erro'|'offline'

    function tocarSom(id) {
        try {
            var el = document.getElementById(id);
            if (el && el.play) { el.currentTime = 0; var pr = el.play(); if (pr && pr.catch) { pr.catch(function () {}); } }
        } catch (e) {}
    }

    // Voz (TTS) para anuncios sem arquivo de audio dedicado (ex.: troca de regional).
    function falarTexto(t) {
        try {
            if (!window.speechSynthesis || !t) { return; }
            var u = new SpeechSynthesisUtterance(t);
            u.lang = 'pt-BR';
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
        } catch (e) {}
    }

    // Nome do responsavel: vem do POST (tela principal) OU fica salvo neste aparelho.
    // Pedido no maximo 1x; depois nunca mais bloqueia a conferencia.
    var USUARIO_LS_KEY = 'conf_camera_usuario';
    try { if (usuarioConf) { localStorage.setItem(USUARIO_LS_KEY, usuarioConf); } } catch (eLS) {}
    function garantirUsuario() {
        if (usuarioConf) { return true; }
        try { var s = localStorage.getItem(USUARIO_LS_KEY); if (s) { usuarioConf = s; return true; } } catch (e) {}
        var nome = window.prompt('Seu nome (responsável pela conferência) — fica salvo neste aparelho:', '');
        usuarioConf = nome ? nome.replace(/^\s+|\s+$/g, '') : '';
        if (usuarioConf) { try { localStorage.setItem(USUARIO_LS_KEY, usuarioConf); } catch (e2) {} return true; }
        return false;
    }

    // Status do salvamento no banco, mostrado em cada card (o verde sai na hora;
    // o banco grava em segundo plano).
    function marcarSaveStatus(alvo, estado, detalhe) {
        var txt = '', cor = '';
        if (estado === 'salvando') { txt = '⏳ salvando…'; cor = '#8a6d00'; }
        else if (estado === 'salvo') { txt = '✓ salvo'; cor = '#1b7a1b'; }
        else if (estado === 'offline') { txt = '• offline (não salvo no banco)'; cor = '#8a6d00'; }
        else { txt = '⚠ não salvou' + (detalhe ? ' (' + detalhe + ')' : '') + ' — bipe de novo'; cor = '#b00020'; }
        for (var i = 0; i < alvo.length; i++) {
            var s = alvo[i].querySelector ? alvo[i].querySelector('.savestatus') : null;
            if (s) { s.textContent = txt; s.style.color = cor; }
        }
    }

    // Rola a pagina ate o card lido (centralizado) p/ ver sempre o ultimo conferido.
    function rolarAteCard(card) {
        if (!card || !card.scrollIntoView) { return; }
        try { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        catch (e) { try { card.scrollIntoView(); } catch (e2) {} }
    }

    // Anuncio falado da troca de regional (4a leitura do mesmo lote de outra regional).
    function anunciarTrocaRegional(card) {
        var reg = (card.getAttribute('data-regional') || card.getAttribute('data-regional-codigo') || '').toString();
        var posto = (card.getAttribute('data-posto') || '').toString();
        var alvoTxt = reg ? ('regional ' + reg) : ('posto ' + posto);
        setMsg('Mudando para a ' + alvoTxt + '.', 'info');
        falarTexto('Mudando para a ' + alvoTxt);
    }

    // Destrava os <audio> e o AudioContext no 1o gesto (politica de mobile).
    var somDestravado = false;
    function destravarSons() {
        if (somDestravado) { return; }
        somDestravado = true;
        var ids = ['concluido', 'pacotejaconferido', 'pacotedeoutraregional', 'pacote_nao_encontrado'];
        for (var i = 0; i < ids.length; i++) {
            (function (el) {
                if (el && el.play) {
                    try {
                        var p = el.play();
                        if (p && p.then) { p.then(function () { try { el.pause(); el.currentTime = 0; } catch (e) {} }).catch(function () {}); }
                    } catch (e) {}
                }
            })(document.getElementById(ids[i]));
        }
        try { if (ctxAudio && ctxAudio.resume) { ctxAudio.resume(); } } catch (e) {}
    }
    document.addEventListener('touchstart', destravarSons, true);
    document.addEventListener('click', destravarSons, true);

    // Chave de grupo (espelha a web): PT -> pt_group/posto; regional 000/999 -> posto;
    // demais -> regional. Usada p/ "outra regional" e "conferencia concluida".
    function grupoKeyCard(card) {
        var ispt = (card.getAttribute('data-ispt') === '1');
        var posto = so_digitos(card.getAttribute('data-posto') || '');
        if (ispt) {
            var pg = (card.getAttribute('data-pt-group') || '').toString().replace(/\s+/g, '');
            return 'PT|' + (pg || posto);
        }
        var regRaw = (card.getAttribute('data-regional-codigo') || card.getAttribute('data-regional') || '').toString();
        var regDig = so_digitos(regRaw);
        var regNum = (regDig !== '') ? parseInt(regDig, 10) : -1;
        if (regNum === 0 || regNum === 999) { return 'P|' + posto; }
        return 'R|' + (regDig !== '' ? String(regNum) : regRaw.replace(/\s+/g, '').toUpperCase());
    }

    function grupoTemPendentes(gk) {
        for (var i = 0; i < cards.length; i++) {
            if (grupoKeyCard(cards[i]) === gk && cards[i].className.indexOf('confirmado') === -1) { return true; }
        }
        return false;
    }

    function levarParaFrente(card) {
        var lista = document.getElementById('lista');
        if (lista && card && lista.firstChild && lista.firstChild !== card) {
            lista.insertBefore(card, lista.firstChild);
        }
    }

    function aplicarConfirmado(alvo) {
        for (var i = 0; i < alvo.length; i++) {
            if (alvo[i].className.indexOf('confirmado') === -1) { alvo[i].className += ' confirmado'; }
        }
        levarParaFrente(alvo[0]);
        atualizarProgresso();
        rolarAteCard(alvo[0]);
    }

    function checarConcluido(gk) {
        if (!grupoTemPendentes(gk)) {
            setMsg('✅ Conferência concluída!', 'ok');
            tocarSom('concluido');
            contextoAtual = null;   // libera para a proxima regional/posto
        }
    }

    // Grava no banco em SEGUNDO PLANO (best-effort). Mostra o status no card.
    // NUNCA bloqueia: o verde ja saiu antes de chamar isto.
    function salvarBg(d, alvo) {
        if (ehFile) { statusSave[d] = 'offline'; marcarSaveStatus(alvo, 'offline'); return; }
        if (codigosSalvando[d]) { return; }                 // ja salvando este codigo
        if (!garantirUsuario()) { statusSave[d] = 'erro'; marcarSaveStatus(alvo, 'erro', 'informe seu nome'); return; }

        var card0 = alvo[0];
        codigosSalvando[d] = true;
        statusSave[d] = 'salvando';
        marcarSaveStatus(alvo, 'salvando');

        var fd = new FormData();
        fd.append('salvar_lote_ajax', '1');
        fd.append('lote', card0.getAttribute('data-lote') || loteDoCodigo(d));
        fd.append('regional', card0.getAttribute('data-regional') || '');
        fd.append('posto', card0.getAttribute('data-posto') || '');
        fd.append('dataexp', card0.getAttribute('data-dataexp') || '');
        fd.append('qtd', card0.getAttribute('data-qtd') || '');
        fd.append('codbar', card0.getAttribute('data-codigo') || d);
        fd.append('usuario', usuarioConf);

        fetch('conferencia_pacotes.php', { method: 'POST', body: fd }).then(function (res) {
            return res.text();
        }).then(function (txt) {
            var ok = false, erro = '';
            try { var j = JSON.parse(txt); ok = !!(j && j.success); erro = (j && j.erro) ? j.erro : ''; } catch (e) { ok = false; }
            statusSave[d] = ok ? 'salvo' : 'erro';
            marcarSaveStatus(alvo, ok ? 'salvo' : 'erro', ok ? '' : (erro || 'tente de novo'));
        }).catch(function () {
            statusSave[d] = 'erro';
            marcarSaveStatus(alvo, 'erro', 'sem rede');
        }).then(function () {
            delete codigosSalvando[d];
        });
    }

    // Verde OTIMISTA: confirma na hora (verde + som + rola ate o card) e grava no
    // banco em segundo plano. A conferencia nunca trava esperando banco/nome.
    function confirmarComSave(d, alvo, gk, agora, silenciarBeep) {
        ultimoProcessado = d; ultimoTs = agora;

        aplicarConfirmado(alvo);

        var concluiu = !grupoTemPendentes(gk);
        if (concluiu) {
            // ultimo do grupo: toca SO "concluido" (sem beep junto).
            setMsg('✅ Conferência concluída!', 'ok');
            tocarSom('concluido');
            contextoAtual = null;   // libera p/ a proxima regional/posto
        } else if (silenciarBeep) {
            // 4a leitura (troca de regional): SO o anuncio (ja falado antes), sem beep.
        } else {
            setMsg('✔ Lote ' + loteDoCodigo(d) + ' conferido!', 'ok');
            bipar(true); vibrar(120);
        }

        salvarBg(d, alvo);
    }

    // Marca o(s) card(s) do lote como conferido (salvando no banco) + avisos.
    function processarLeitura(bruto) {
        var d = normalizar19(bruto);
        if (!d) { return false; }

        // anti-repeticao (mesmo codigo lido em sequencia)
        var agora = Date.now();
        if (d === ultimoProcessado && (agora - ultimoTs) < 1500) { return true; }

        var alvo = null;
        if (porCodigo[d]) {
            alvo = [porCodigo[d]];
        } else {
            var lote = loteDoCodigo(d);
            if (lote && porLote[lote]) { alvo = porLote[lote]; }
        }

        if (!alvo) {
            ultimoProcessado = d; ultimoTs = agora;
            setMsg('Pacote NÃO carregado / não está nesta lista: ' + (loteDoCodigo(d) || d), 'erro');
            tocarSom('pacote_nao_encontrado'); vibrar([60, 40, 60]);   // 1 som so
            return false;
        }

        // Ja conferido? (todos os cards do alvo ja verdes)
        var jaTodos = true;
        for (var i = 0; i < alvo.length; i++) {
            if (alvo[i].className.indexOf('confirmado') === -1) { jaTodos = false; break; }
        }
        if (jaTodos) {
            ultimoProcessado = d; ultimoTs = agora;
            overrideCodigo = ''; overrideContador = 0;
            levarParaFrente(alvo[0]); rolarAteCard(alvo[0]);
            // Se o banco nao gravou antes, re-bipar reenvia (sem som extra de "ja conferido").
            if (statusSave[d] === 'erro' && !codigosSalvando[d] && !ehFile) {
                setMsg('Re-tentando salvar o lote ' + loteDoCodigo(d) + '…', 'info');
                salvarBg(d, alvo);
            } else {
                setMsg('Lote ' + loteDoCodigo(d) + ' já estava conferido.', 'info');
                tocarSom('pacotejaconferido'); vibrar(40);   // 1 som so
            }
            return true;
        }

        // Save em andamento p/ este codigo: ignora (anti duplo-POST)
        if (codigosSalvando[d]) { return true; }

        // Grupo / contexto (aviso "pacote de outra regional", com override no 4o)
        var gk = grupoKeyCard(alvo[0]);
        var trocouRegional = false;
        if (contextoAtual === null) {
            contextoAtual = gk;
        } else if (gk !== contextoAtual) {
            if (grupoTemPendentes(contextoAtual)) {
                if (overrideCodigo === d && (agora - overrideTs) < 1500) { return false; } // espaca contagem (evita troca acidental segurando a camera)
                overrideTs = agora;
                if (overrideCodigo === d) { overrideContador++; } else { overrideCodigo = d; overrideContador = 1; }
                if (overrideContador < 4) {
                    setMsg('⚠ Pacote de OUTRA regional/posto. Termine o atual ou bipe 4× p/ trocar (' + overrideContador + '/4).', 'erro');
                    tocarSom('pacotedeoutraregional'); vibrar([80, 50, 80]);   // 1 som so
                    return false;
                }
                contextoAtual = gk;             // override atingido: troca de contexto
                trocouRegional = true;
                anunciarTrocaRegional(alvo[0]); // 1 anuncio falado so (sem beep junto)
            } else {
                contextoAtual = gk;   // grupo anterior ja concluido: troca p/ o novo
            }
        }
        overrideCodigo = ''; overrideContador = 0;

        confirmarComSave(d, alvo, gk, agora, trocouRegional);
        return true;
    }

    // ---------- Campo "estilo leitor" (Bluetooth / app teclado-scanner / manual) ----------
    var campo = document.getElementById('campoLeitor');
    var timerCampo = null;
    function tratarCampo() {
        var v = so_digitos(campo.value);
        if (v.length >= 19 || v.length === 8) {
            processarLeitura(v);
            campo.value = '';
        }
    }
    campo.addEventListener('input', function () {
        if (timerCampo) { clearTimeout(timerCampo); }
        // leitores enviam tudo de uma vez; damos um respiro p/ digitacao manual
        timerCampo = setTimeout(tratarCampo, 120);
    });
    campo.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            var v = so_digitos(campo.value);
            if (v) { processarLeitura(v); }
            campo.value = '';
        }
    });

    // ---------- Foto (Opcao 1: 1 toque, decodifica localmente) ----------
    var inputFoto = document.getElementById('inputFoto');
    inputFoto.addEventListener('change', function () {
        if (!inputFoto.files || !inputFoto.files[0]) { return; }
        if (!ZXING_OK) {
            setMsg('Leitor de imagem indisponível. Use o campo de digitação.', 'erro');
            return;
        }
        var url = URL.createObjectURL(inputFoto.files[0]);
        setMsg('Lendo a foto...', 'info');
        var reader = novoReader();
        if (!reader) { setMsg('Não foi possível iniciar o leitor.', 'erro'); return; }
        reader.decodeFromImageUrl(url).then(function (res) {
            URL.revokeObjectURL(url);
            processarLeitura(res.getText ? res.getText() : ('' + res));
        }).catch(function () {
            URL.revokeObjectURL(url);
            setMsg('Não consegui ler o código nesta foto. Tente de novo, mais perto e com luz.', 'erro');
            bipar(false);
        });
        inputFoto.value = '';
    });

    // ---------- Camera ao vivo: leitura por REGIAO DE INTERESSE (ROI) ----------
    // Mesmo metodo da tela principal (conferencia_pacotes.php): em vez de decodificar
    // o QUADRO INTEIRO (barras finas num quadro grande nao travam no leitor 1D),
    // recortamos so a faixa central (a mira), ampliamos ~2x e decodificamos so isso.
    var streamCam = null;       // MediaStream da camera
    var loopTimer = null;       // timer do laco de leitura
    var abrindo = false;        // evita clique repetido enquanto abre
    var geracao = 0;            // invalida laco/stream quando o usuario fecha
    var leitorROI = null;       // ZXing.MultiFormatReader (reusado entre quadros)
    var roiCanvas = null, roiCtx = null;
    var detectorNativo = null;  // BarcodeDetector NATIVO (Android Chrome) — preferido
    var nativoFalhou = false;   // se detect() rejeitar (nao-transiente), cai no ZXing
    var quadros = 0;            // contador de quadros lidos (diagnostico na tela)

    function temDetectorNativo() {
        return (typeof window.BarcodeDetector === 'function');
    }

    function criarDetectorNativo() {
        if (detectorNativo || nativoFalhou || !temDetectorNativo()) { return; }
        // Sem {formats}: detecta TODOS os formatos suportados pelo aparelho e nao
        // lanca por formato desconhecido (evita o getSupportedFormats async).
        try { detectorNativo = new window.BarcodeDetector(); }
        catch (e) { detectorNativo = null; nativoFalhou = true; }
    }

    function setDiag(txt) {
        var d = document.getElementById('camDiag');
        if (d) { d.textContent = txt || ''; }
    }

    function temPrimitivasROI() {
        return !!(ZXing && ZXing.MultiFormatReader && ZXing.BinaryBitmap &&
                  ZXing.HybridBinarizer && ZXing.HTMLCanvasElementLuminanceSource);
    }

    function montarHintsROI() {
        var h = new Map();
        try {
            if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
                h.set(ZXing.DecodeHintType.TRY_HARDER, true);
                h.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.ITF,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.CODE_93,
                    ZXing.BarcodeFormat.CODABAR,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8
                ]);
            }
        } catch (e) {}
        return h;
    }

    function entregarLeitura(txt) {
        var d = so_digitos(txt);
        if (d.length >= 19) { processarLeitura(d.slice(-19)); return; }
        if (d.length === 8) { processarLeitura(d); return; }
        if (d.length > 0) { setMsg('Detectado ' + d.length + ' díg — alinhe o código todo na mira', 'info'); }
    }

    window.alternarCamera = function () {
        if (cameraAtiva) { window.pararCamera(); } else { iniciarCamera(); }
    };

    function iniciarCamera() {
        if (!ZXING_OK) {
            setMsg('Câmera ao vivo indisponível (leitor não carregou). Use "Tirar foto".', 'erro');
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setMsg('Este navegador bloqueia a câmera ao vivo (precisa de HTTPS). Use "Tirar foto".', 'erro');
            return;
        }
        if (cameraAtiva || abrindo) { return; }

        // Sem as primitivas de ROI, cai no metodo antigo (quadro inteiro).
        if (!temPrimitivasROI()) { iniciarCameraLegado(); return; }

        abrindo = true;
        var minhaGeracao = ++geracao;
        setMsg('Abrindo câmera...', 'info');

        // Tenta o ideal e, se o aparelho recusar, cai para configuracoes mais simples.
        // Alguns Android recusam 1920x1080 ou "advanced focusMode" no proprio
        // getUserMedia (OverconstrainedError) — por isso o foco continuo vai por
        // applyConstraints DEPOIS de abrir (best-effort), nunca dentro do getUserMedia.
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
            if (minhaGeracao !== geracao) {
                // usuario fechou antes de a permissao resolver: descarta a stream
                try { s.getTracks().forEach(function (t) { t.stop(); }); } catch (e) {}
                return;
            }
            streamCam = s;
            var video = document.getElementById('video');
            document.getElementById('cameraArea').style.display = 'block';
            document.getElementById('btnCamera').textContent = '⏹ Parar câmera';
            video.srcObject = s;
            video.muted = true;
            var p = video.play();
            if (p && p.catch) { p.catch(function () {}); }
            // Autofoco continuo best-effort, fora do getUserMedia p/ nao recusar a abertura.
            tocarParaFocar();

            cameraAtiva = true;
            abrindo = false;
            setMsg('Câmera ligada. Aponte para o código de barras.', 'ok');

            if (!leitorROI) {
                leitorROI = new ZXing.MultiFormatReader();
                leitorROI.setHints(montarHintsROI());
            }
            if (!roiCanvas) {
                roiCanvas = document.createElement('canvas');
                roiCtx = roiCanvas.getContext('2d');
            }
            quadros = 0;
            criarDetectorNativo();
            loopROI(minhaGeracao);
        }).catch(function (e) {
            abrindo = false;
            var fechado = (minhaGeracao !== geracao);  // usuario ja fechou enquanto pedia permissao
            window.pararCamera();
            if (fechado) { return; }  // nao mostra erro num overlay ja fechado
            var nome = (e && e.name) ? e.name : 'erro';
            var dica;
            if (nome === 'NotReadableError' || nome === 'TrackStartError' || nome === 'AbortError') {
                dica = 'A câmera parece estar em uso por outra aba/app. Feche as outras e tente de novo.';
            } else if (nome === 'NotAllowedError' || nome === 'SecurityError') {
                dica = 'Permissão negada para este site. Toque no cadeado/ⓘ ao lado do endereço, permita a Câmera e tente de novo.';
            } else if (nome === 'NotFoundError' || nome === 'OverconstrainedError') {
                dica = 'Nenhuma câmera compatível encontrada. Use "Tirar foto" ou o campo de leitor.';
            } else {
                dica = 'Tente novamente; se persistir, use "Tirar foto".';
            }
            setMsg('Não consegui abrir a câmera (' + nome + '). ' + dica, 'erro');
        });
    }

    function escolherCodigoNativo(cods) {
        for (var i = 0; i < cods.length; i++) {
            if (so_digitos(cods[i].rawValue).length >= 19) { return cods[i].rawValue; }
        }
        return cods[0].rawValue;
    }

    function loopROI(minhaGeracao) {
        if (!cameraAtiva || minhaGeracao !== geracao) { return; }
        var video = document.getElementById('video');
        var vw = video.videoWidth || 0, vh = video.videoHeight || 0;
        quadros++;

        // ---- Caminho 1 (PREFERIDO): detector NATIVO do navegador (ML Kit no Android) ----
        // Decodifica o QUADRO INTEIRO; o engine nativo localiza e tolera o blur do
        // celular MUITO melhor que o ZXing-js -> leitura ao vivo bem mais confiavel.
        if (detectorNativo && !nativoFalhou) {
            if (!vw || !vh) {
                loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 120);
                return;
            }
            setDiag('Leitor nativo • ' + vw + 'x' + vh + ' • q' + quadros);
            detectorNativo.detect(video).then(function (cods) {
                if (!cameraAtiva || minhaGeracao !== geracao) { return; }
                if (cods && cods.length) {
                    var bruto = escolherCodigoNativo(cods);
                    setDiag('Nativo leu ' + so_digitos(bruto).length + ' díg [' + (cods[0].format || '?') + ']');
                    try { entregarLeitura(bruto); } catch (ePipe) {}
                }
            }).catch(function (err) {
                // InvalidStateError = video ainda nao pronto (transiente): nao desliga.
                if (err && err.name && err.name !== 'InvalidStateError') {
                    nativoFalhou = true;
                    setDiag('Nativo indisponível (' + err.name + ') — usando ZXing');
                }
            }).then(function () {
                if (!cameraAtiva || minhaGeracao !== geracao) { return; }
                loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 120);
            });
            return; // o reagendamento acontece na continuacao da promise (1 detect por vez)
        }

        // ---- Caminho 2 (FALLBACK): ZXing por REGIAO DE INTERESSE (ROI) ----
        try {
            if (vw && vh) {
                // ROI = faixa central (alinhada com a .mira: ~8%..92% larg, ~33%..67% alt)
                var rx = Math.floor(vw * 0.08);
                var rw = Math.floor(vw * 0.84);
                var ry = Math.floor(vh * 0.33);
                var rh = Math.floor(vh * 0.34);
                var escala = (rw < 800) ? 2 : 1; // amplia so quando a faixa fica pequena
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
        } catch (e) {
            // NotFoundException a cada quadro sem codigo legivel é normal — ignora
        }
        loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 180);
    }

    // Refoco (best-effort) ao tocar na imagem
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

    // Fallback (so se faltarem as primitivas de ROI): metodo antigo, quadro inteiro.
    function iniciarCameraLegado() {
        document.getElementById('cameraArea').style.display = 'block';
        document.getElementById('btnCamera').textContent = '⏹ Parar câmera';
        codeReader = novoReader();
        if (!codeReader) { setMsg('Não foi possível iniciar o leitor.', 'erro'); return; }
        setMsg('Abrindo câmera...', 'info');
        codeReader.decodeFromConstraints(
            { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } },
            'video',
            function (result, err) {
                if (result) { entregarLeitura(result.getText ? result.getText() : ('' + result)); }
            }
        ).then(function () {
            cameraAtiva = true;
            setMsg('Câmera ligada. Aponte para o código de barras.', 'ok');
        }).catch(function (e) {
            window.pararCamera();
            setMsg('Não consegui abrir a câmera ao vivo (segurança do navegador). Use "Tirar foto".', 'erro');
        });
    }

    window.pararCamera = function () {
        geracao++; // invalida o laco e qualquer getUserMedia ainda pendente
        cameraAtiva = false;
        abrindo = false;
        if (loopTimer) { clearTimeout(loopTimer); loopTimer = null; }
        try { if (codeReader && codeReader.reset) { codeReader.reset(); } } catch (e) {}
        try { if (streamCam) { streamCam.getTracks().forEach(function (t) { t.stop(); }); } } catch (e2) {}
        streamCam = null;
        var video = document.getElementById('video');
        if (video) { try { video.srcObject = null; } catch (e3) {} }
        var area = document.getElementById('cameraArea');
        if (area) { area.style.display = 'none'; }
        var btn = document.getElementById('btnCamera');
        if (btn) { btn.textContent = '🎥 Câmera ao vivo'; }
    };

    // Toque na imagem refoca (nao foca ao tocar no X de fechar)
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

    // ---------- Exportar conferidos (para marcar "s" na planilha) ----------
    window.exportarConferidos = function () {
        var linhas = [];
        for (var i = 0; i < cards.length; i++) {
            if (cards[i].className.indexOf('confirmado') !== -1) {
                var l = so_digitos(cards[i].getAttribute('data-lote'));
                if (l) { linhas.push(l); }
            }
        }
        if (!linhas.length) {
            setMsg('Nenhum lote conferido ainda.', 'info');
            return;
        }
        var txt = linhas.join('\n');
        // tenta copiar
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(txt);
            }
        } catch (e) {}
        // baixa um .txt
        try {
            var blob = new Blob([txt + '\n'], { type: 'text/plain;charset=utf-8' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'lotes_conferidos.txt';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () { URL.revokeObjectURL(a.href); document.body.removeChild(a); }, 1500);
        } catch (e) {}
        setMsg(linhas.length + ' lote(s) conferido(s) exportado(s) (copiado + arquivo .txt).', 'ok');
    };

    // ---------- Salvar a pagina offline (autocontida, c/ leitor embutido) ----------
    window.salvarOffline = function () {
        try {
            var html = '<!DOCTYPE html>\n' + document.documentElement.outerHTML;
            var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'conferencia_camera_offline.html';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () { URL.revokeObjectURL(a.href); document.body.removeChild(a); }, 1500);
            setMsg('Página salva para uso offline no aparelho.', 'ok');
        } catch (e) {
            setMsg('Não foi possível salvar offline neste navegador.', 'erro');
        }
    };

    // ---------- Reset ----------
    window.resetarConferencia = function () {
        if (!window.confirm('Limpar todas as marcações de conferido?')) { return; }
        for (var i = 0; i < cards.length; i++) {
            cards[i].className = cards[i].className.replace(/\s*confirmado/g, '');
        }
        contextoAtual = null;
        overrideCodigo = ''; overrideContador = 0;
        ultimoProcessado = ''; ultimoTs = 0;
        atualizarProgresso();
        setMsg('Conferência reiniciada (marcações locais; não apaga o banco).', 'info');
    };

    // estado inicial
    atualizarProgresso();
    if (!ZXING_OK) {
        setMsg('Leitor de código offline não carregou. Você ainda pode bipar/digitar no campo acima.', 'erro');
    }
    try { campo.focus(); } catch (e) {}
})();
</script>

</body>
</html>
