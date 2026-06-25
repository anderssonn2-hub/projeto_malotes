<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
 /* processando_overlay.php — v1.1.6 */ ?>
<style type="text/css">
.overlay-processando-global-lite {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.34);
    z-index: 30000;
}
.overlay-processando-global-lite.ativo {
    display: flex;
}
.overlay-processando-global-lite-box {
    min-width: 220px;
    padding: 18px 22px;
    border-radius: 12px;
    background: #ffffff;
    color: #1f2937;
    text-align: center;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
    font-size: 15px;
    font-weight: 700;
}
.overlay-processando-global-lite-box:before {
    content: '';
    display: block;
    width: 28px;
    height: 28px;
    margin: 0 auto 10px;
    border-radius: 50%;
    border: 3px solid #dbeafe;
    border-top-color: #2563eb;
    animation: giro-processando-lite 0.9s linear infinite;
}
@keyframes giro-processando-lite {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@media print {
    .overlay-processando-global-lite {
        display: none !important;
    }
}
</style>

<div id="overlay-processando-global-lite" class="overlay-processando-global-lite" aria-hidden="true">
    <div id="overlay-processando-global-lite-texto" class="overlay-processando-global-lite-box">Processando...</div>
</div>

<script type="text/javascript">
(function() {
    if (window.__overlayProcessandoLiteInicializado) {
        return;
    }
    window.__overlayProcessandoLiteInicializado = true;

    var overlay = document.getElementById('overlay-processando-global-lite');
    var overlayTexto = document.getElementById('overlay-processando-global-lite-texto');
    // v1.1.6: rastrear timers pendentes e timer de seguranca para evitar overlay travado
    var timersPendentes = [];
    var timerSegurancaId = null;

    function limparTimersPendentes() {
        for (var i = 0; i < timersPendentes.length; i++) {
            clearTimeout(timersPendentes[i]);
        }
        timersPendentes = [];
    }

    function agendarMostrar(texto, ms) {
        var id = setTimeout(function() { exibirProcessando(texto); }, ms);
        timersPendentes.push(id);
    }

    function exibirProcessando(texto) {
        if (!overlay) return;
        if (overlayTexto) {
            overlayTexto.textContent = texto || 'Processando...';
        }
        overlay.className = 'overlay-processando-global-lite ativo';
        overlay.setAttribute('aria-hidden', 'false');
        // v1.1.6: failsafe - ocultar automaticamente apos 8s para evitar overlay travado
        if (timerSegurancaId) {
            clearTimeout(timerSegurancaId);
        }
        timerSegurancaId = setTimeout(function() {
            ocultarProcessando();
        }, 8000);
    }

    function ocultarProcessando() {
        if (!overlay) return;
        overlay.className = 'overlay-processando-global-lite';
        overlay.setAttribute('aria-hidden', 'true');
        limparTimersPendentes();
        if (timerSegurancaId) {
            clearTimeout(timerSegurancaId);
            timerSegurancaId = null;
        }
    }

    function encontrarLinkAlvo(node) {
        while (node && node !== document) {
            if (node.tagName && node.tagName.toLowerCase() === 'a') {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    window.exibirProcessandoGlobal = exibirProcessando;
    window.ocultarProcessandoGlobal = ocultarProcessando;

    document.addEventListener('submit', function(evento) {
        var form = evento.target;
        if (!form || form.getAttribute('target') === '_blank' || form.getAttribute('data-sem-processando') === '1') {
            return;
        }
        agendarMostrar('Processando...', 250);
    }, true);

    document.addEventListener('click', function(evento) {
        var link = encontrarLinkAlvo(evento.target);
        var href;
        if (!link) return;
        if (evento.defaultPrevented) return;
        if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) return;
        if (link.getAttribute('target') === '_blank' || link.getAttribute('download') !== null || link.getAttribute('data-sem-processando') === '1') return;
        href = link.getAttribute('href') || '';
        href = String(href).replace(/^\s+|\s+$/g, '');
        if (href === '' || href === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
        agendarMostrar('Processando...', 250);
    }, true);

    window.addEventListener('beforeunload', function() {
        exibirProcessando('Processando...');
    });

    // v1.1.6: garantir que o overlay seja ocultado em multiplos eventos de carregamento
    // (pageshow nem sempre dispara em IE antigo ou apos navegacao com cache)
    window.addEventListener('pageshow', function() {
        ocultarProcessando();
    });
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        ocultarProcessando();
    }
    document.addEventListener('DOMContentLoaded', function() {
        ocultarProcessando();
    });
    window.addEventListener('load', function() {
        ocultarProcessando();
    });
})();
</script>