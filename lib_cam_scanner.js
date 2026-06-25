// =====================================================================
// lib_cam_scanner.js — Modulo COMPARTILHADO de leitura por camera ao vivo
// ---------------------------------------------------------------------
// Monta um overlay de tela cheia (video + mira + diagnostico) e le codigos
// de barras pela camera do celular. Usa o BarcodeDetector NATIVO do
// navegador (primario, ML Kit no Android Chrome) e o ZXing por REGIAO (ROI)
// como fallback. Requer a lib LOCAL `lib_zxing.min.js` carregada ANTES deste
// arquivo (global `ZXing`) para o fallback; o leitor nativo funciona sem ela.
//
// Uso:
//   CamScanner.start({ onRead: function (textoBruto) { ... }, titulo: '...' });
//   CamScanner.stop();
//
// `onRead` recebe a string crua lida; a pagina decide o que fazer (ex.: contar
// digitos e rotear 35 -> display, 19 -> lote). O modulo ja faz um deduplicador
// curto (~1200ms para o MESMO codigo) e da feedback visual (flash) + beep.
// =====================================================================
(function () {
    'use strict';

    var overlay = null, video = null, miraEl = null, diagEl = null, msgEl = null,
        tituloEl = null, avisoEl = null, palco = null, painelEl = null;
    var cameraAtiva = false, abrindo = false, geracao = 0, painelMode = false;
    var streamCam = null, loopTimer = null;
    var leitorROI = null, roiCanvas = null, roiCtx = null, codeReader = null;
    var detectorNativo = null, nativoFalhou = false, quadros = 0;
    var onReadCb = null, lastRaw = '', lastTs = 0;
    var dedupMs = 1200, audioCtx = null, beepOnDetect = true;

    function zxLoaded() { return (typeof ZXing !== 'undefined'); }
    function temPrimitivasROI() {
        return !!(zxLoaded() && ZXing.MultiFormatReader && ZXing.BinaryBitmap &&
                  ZXing.HybridBinarizer && ZXing.HTMLCanvasElementLuminanceSource);
    }
    function temDetectorNativo() { return (typeof window.BarcodeDetector === 'function'); }
    function soDig(s) { return ('' + (s || '')).replace(/\D+/g, ''); }
    function setDiag(t) { if (diagEl) { diagEl.textContent = t || ''; } }
    function setMsg(t) { if (msgEl) { msgEl.textContent = t || ''; } }

    function criarDetectorNativo() {
        if (detectorNativo || nativoFalhou || !temDetectorNativo()) { return; }
        try { detectorNativo = new window.BarcodeDetector(); }
        catch (e) { detectorNativo = null; nativoFalhou = true; }
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
            if (!zxLoaded() || !ZXing.BrowserMultiFormatReader) { return null; }
            return new ZXing.BrowserMultiFormatReader(montarHintsROI(), 200);
        } catch (e) { return null; }
    }

    function beep() {
        try {
            if (!audioCtx) {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) { return; }
                audioCtx = new AC();
            }
            if (audioCtx.state === 'suspended' && audioCtx.resume) { audioCtx.resume(); }
            var osc = audioCtx.createOscillator();
            var g = audioCtx.createGain();
            osc.type = 'square'; osc.frequency.value = 880; g.gain.value = 0.05;
            osc.connect(g); g.connect(audioCtx.destination);
            osc.start();
            setTimeout(function () { try { osc.stop(); } catch (e) {} }, 90);
        } catch (e) {}
    }
    function flashMira() {
        if (!miraEl) { return; }
        miraEl.style.borderColor = '#00e676';
        miraEl.style.boxShadow = '0 0 0 4000px rgba(0,0,0,0.45), 0 0 18px #00e676 inset';
        setTimeout(function () {
            miraEl.style.borderColor = '#fff';
            miraEl.style.boxShadow = '0 0 0 4000px rgba(0,0,0,0.45)';
        }, 220);
    }

    function entregar(raw) {
        var d = soDig(raw);
        if (d.length === 0) { setDiag('Detectado 0 dig — alinhe na mira'); return; }
        var agora = Date.now();
        if (d === lastRaw && (agora - lastTs) < dedupMs) { return; }
        lastRaw = d; lastTs = agora;
        flashMira(); if (beepOnDetect) { beep(); }
        setMsg('Lido: ...' + d.slice(-6) + ' (' + d.length + ' dig)');
        if (onReadCb) { try { onReadCb(raw); } catch (e) {} }
    }

    function escolherCodigoNativo(cods) {
        // Quando ha varios codigos no quadro, escolhe o com MAIS digitos entre os de
        // >=19 (favorece o display de 35 sobre um lote de 19 ou um codigo curto/errado).
        // IMPORTANTE: havendo 2+ etiquetas no quadro, PREFERE uma DIFERENTE da ultima
        // ja entregue (dentro da janela de dedup). Sem isso, o detector pode escolher
        // sempre a MESMA etiqueta (a 1a / mais longa) e o dedup a descarta -> a OUTRA
        // etiqueta no quadro nunca e lida ("nem todas as etiquetas sao lidas").
        var agora = Date.now();
        var dedupAtivo = (!!lastRaw && (agora - lastTs) < dedupMs);
        var melhor = null, melhorLen = -1;        // melhor no geral (>=19 dig)
        var melhorNovo = null, melhorNovoLen = -1; // melhor que NAO seja o ultimo entregue
        for (var i = 0; i < cods.length; i++) {
            var dd = soDig(cods[i].rawValue);
            var n = dd.length;
            if (n < 19) { continue; }
            if (n > melhorLen) { melhor = cods[i].rawValue; melhorLen = n; }
            if (!(dedupAtivo && dd === lastRaw) && n > melhorNovoLen) {
                melhorNovo = cods[i].rawValue; melhorNovoLen = n;
            }
        }
        if (melhorNovo !== null) { return melhorNovo; }
        if (melhor !== null) { return melhor; }
        return cods[0].rawValue;
    }

    function montarOverlay() {
        if (overlay) { return; }
        overlay = document.createElement('div');
        overlay.id = 'camScannerOverlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'z-index:2147483000;background:#000;display:none;flex-direction:column;' +
            'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;';

        var header = document.createElement('div');
        header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;' +
            'padding:10px 12px;background:#111;color:#fff;gap:10px;flex:0 0 auto;';
        tituloEl = document.createElement('div');
        tituloEl.style.cssText = 'font-size:15px;font-weight:700;';
        tituloEl.textContent = 'Ler com a camera';
        var btnFechar = document.createElement('button');
        btnFechar.type = 'button';
        btnFechar.textContent = '\u2715 Fechar';
        btnFechar.style.cssText = 'border:none;background:#c62828;color:#fff;font-size:15px;' +
            'font-weight:700;padding:9px 14px;border-radius:8px;cursor:pointer;';
        btnFechar.onclick = function () { stop(); };
        header.appendChild(tituloEl); header.appendChild(btnFechar);

        avisoEl = document.createElement('div');
        avisoEl.style.cssText = 'display:none;background:#fff3cd;color:#7c5700;padding:8px 12px;' +
            'font-size:12px;line-height:1.35;flex:0 0 auto;';
        avisoEl.innerHTML = 'A camera ao vivo precisa de contexto seguro. Em HTTP na rede local, ' +
            'ative em <b>chrome://flags/#unsafely-treat-insecure-origin-as-secure</b> ' +
            '(adicione o endereco deste servidor) e reabra o Chrome.';

        palco = document.createElement('div');
        palco.style.cssText = 'position:relative;flex:1 1 auto;overflow:hidden;background:#000;';
        video = document.createElement('video');
        video.id = 'camScannerVideo';
        video.setAttribute('playsinline', 'true');
        video.setAttribute('autoplay', 'true');
        video.muted = true;
        video.style.cssText = 'width:100%;height:100%;object-fit:cover;background:#000;';
        video.addEventListener('click', tocarParaFocar);
        // Mira em formato de CODIGO DE BARRAS (larga e baixa), centralizada — proporcao
        // boa para encaixar a etiqueta inteira (igual a tela de conferencia).
        miraEl = document.createElement('div');
        miraEl.style.cssText = 'position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);' +
            'width:90%;max-width:520px;height:118px;max-height:60%;box-sizing:border-box;' +
            'border:3px solid #fff;border-radius:12px;box-shadow:0 0 0 4000px rgba(0,0,0,0.45);' +
            'pointer-events:none;';
        var linhaMira = document.createElement('div');
        linhaMira.style.cssText = 'position:absolute;left:6%;right:6%;top:50%;height:2px;' +
            'background:rgba(255,60,60,0.9);box-shadow:0 0 8px rgba(255,60,60,0.9);';
        miraEl.appendChild(linhaMira);
        diagEl = document.createElement('div');
        diagEl.style.cssText = 'position:absolute;left:0;right:0;bottom:6px;text-align:center;' +
            'color:#9fe;font-size:11px;font-family:monospace;text-shadow:0 1px 2px #000;pointer-events:none;';
        palco.appendChild(video); palco.appendChild(miraEl); palco.appendChild(diagEl);

        msgEl = document.createElement('div');
        msgEl.style.cssText = 'padding:10px 12px;background:#111;color:#b7f7c0;font-size:14px;' +
            'text-align:center;font-weight:700;flex:0 0 auto;min-height:20px;';

        // Painel inferior OPCIONAL: a pagina renderiza aqui (via setPainelHTML) a lista
        // do que esta sendo lido/salvo, com o ultimo no topo. Fica abaixo da camera.
        painelEl = document.createElement('div');
        painelEl.id = 'camScannerPainel';
        painelEl.style.cssText = 'display:none;flex:1 1 auto;overflow:auto;background:#0f172a;' +
            'color:#e2e8f0;-webkit-overflow-scrolling:touch;';

        overlay.appendChild(header);
        overlay.appendChild(avisoEl);
        overlay.appendChild(palco);
        overlay.appendChild(msgEl);
        overlay.appendChild(painelEl);
        document.body.appendChild(overlay);
    }

    function iniciarCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setMsg('Este navegador bloqueia a camera ao vivo (precisa de HTTPS). Use o leitor/digitacao.');
            return;
        }
        if (cameraAtiva || abrindo) { return; }
        if (!temPrimitivasROI() && !temDetectorNativo()) { iniciarCameraLegado(); return; }

        abrindo = true;
        var minhaGeracao = ++geracao;
        setMsg('Abrindo camera...');

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
            video.srcObject = s; video.muted = true;
            var p = video.play(); if (p && p.catch) { p.catch(function () {}); }
            tocarParaFocar();

            cameraAtiva = true; abrindo = false;
            setMsg('Camera ligada. Aponte para o codigo de barras.');

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
                dica = 'A camera parece estar em uso por outra aba/app. Feche as outras e tente de novo.';
            } else if (nome === 'NotAllowedError' || nome === 'SecurityError') {
                dica = 'Permissao negada. Toque no cadeado/i ao lado do endereco e permita a Camera (em HTTP, use o flag acima).';
            } else if (nome === 'NotFoundError' || nome === 'OverconstrainedError') {
                dica = 'Nenhuma camera compativel. Use o leitor/digitacao.';
            } else {
                dica = 'Tente de novo; se persistir, use o leitor/digitacao.';
            }
            setMsg('Nao consegui abrir a camera (' + nome + '). ' + dica);
        });
    }

    function loopROI(minhaGeracao) {
        if (!cameraAtiva || minhaGeracao !== geracao) { return; }
        var vw = video.videoWidth || 0, vh = video.videoHeight || 0;
        quadros++;

        if (detectorNativo && !nativoFalhou) {
            if (!vw || !vh) { loopTimer = setTimeout(function () { loopROI(minhaGeracao); }, 120); return; }
            setDiag('Leitor nativo - ' + vw + 'x' + vh + ' - q' + quadros);
            detectorNativo.detect(video).then(function (cods) {
                if (!cameraAtiva || minhaGeracao !== geracao) { return; }
                if (cods && cods.length) {
                    var bruto = escolherCodigoNativo(cods);
                    setDiag('Nativo leu ' + soDig(bruto).length + ' dig');
                    try { entregar(bruto); } catch (ePipe) {}
                }
            }).catch(function (err) {
                if (err && err.name && err.name !== 'InvalidStateError') {
                    nativoFalhou = true; setDiag('Nativo indisponivel (' + err.name + ') — usando ZXing');
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
                setDiag('ZXing - ' + vw + 'x' + vh + ' - q' + quadros);
                var src = new ZXing.HTMLCanvasElementLuminanceSource(roiCanvas);
                var bmp = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(src));
                var resultado = leitorROI.decodeWithState(bmp);
                if (resultado) { entregar(resultado.getText ? resultado.getText() : ('' + resultado)); }
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
        if (abrindo || cameraAtiva) { return; }
        abrindo = true;
        codeReader = novoReader();
        if (!codeReader) { abrindo = false; setMsg('Nao foi possivel iniciar o leitor de camera. Use o leitor/digitacao.'); return; }
        setMsg('Abrindo camera...');
        codeReader.decodeFromConstraints(
            { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } },
            'camScannerVideo',
            function (result) { if (result) { entregar(result.getText ? result.getText() : ('' + result)); } }
        ).then(function () {
            abrindo = false; cameraAtiva = true; setMsg('Camera ligada. Aponte para o codigo de barras.');
        }).catch(function () {
            abrindo = false; setMsg('Nao consegui abrir a camera ao vivo (seguranca do navegador). Use o leitor/digitacao.');
        });
    }

    function pararCamera() {
        geracao++;
        cameraAtiva = false; abrindo = false;
        if (loopTimer) { clearTimeout(loopTimer); loopTimer = null; }
        try { if (codeReader && codeReader.reset) { codeReader.reset(); } } catch (e) {}
        try { if (streamCam) { streamCam.getTracks().forEach(function (t) { t.stop(); }); } } catch (e2) {}
        streamCam = null;
        if (video) { try { video.srcObject = null; } catch (e3) {} }
    }

    function start(opts) {
        opts = opts || {};
        onReadCb = (typeof opts.onRead === 'function') ? opts.onRead : null;
        // Por padrao o modulo emite o bipe ao DETECTAR (compat. encontra_posto/carteira).
        // A pagina pode passar beepOnDetect:false para controlar o bipe ela mesma (ex.:
        // a devolucao so bipa quando o POSTO e identificado) chamando CamScanner.beep().
        beepOnDetect = (opts.beepOnDetect !== false);
        montarOverlay();
        if (tituloEl && opts.titulo) { tituloEl.textContent = opts.titulo; }
        if (avisoEl) {
            var inseguro = (typeof window.isSecureContext !== 'undefined' && !window.isSecureContext);
            avisoEl.style.display = inseguro ? 'block' : 'none';
        }
        // Layout: com painel, a camera fica no TOPO (altura limitada) e a lista abaixo;
        // sem painel, a camera ocupa a tela toda (comportamento padrao).
        painelMode = !!opts.painel;
        if (palco) {
            if (painelMode) { palco.style.flex = '0 0 auto'; palco.style.height = '44vh'; }
            else { palco.style.flex = '1 1 auto'; palco.style.height = ''; }
        }
        if (painelEl) {
            painelEl.style.display = painelMode ? 'block' : 'none';
            if (painelMode) { painelEl.innerHTML = ''; painelEl.scrollTop = 0; }
        }
        lastRaw = ''; lastTs = 0;
        setMsg(''); setDiag('');
        overlay.style.display = 'flex';
        try { document.body.style.overflow = 'hidden'; } catch (e) {}
        iniciarCamera();
    }

    function stop() {
        pararCamera();
        painelMode = false;
        if (overlay) { overlay.style.display = 'none'; }
        try { document.body.style.overflow = ''; } catch (e) {}
    }

    function setPainelHTML(h) { if (painelEl) { painelEl.innerHTML = (h == null ? '' : h); } }

    window.CamScanner = {
        start: start,
        stop: stop,
        beep: beep,
        isActive: function () { return cameraAtiva; },
        isPainel: function () { return painelMode; },
        setPainelHTML: setPainelHTML
    };
})();
