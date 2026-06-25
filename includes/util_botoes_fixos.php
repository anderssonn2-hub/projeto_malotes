<?php
/* util_botoes_fixos.php — v1.0.0
   Substitui melhorias_widget: dois icones flutuantes discretos no canto inferior direito.
   - Subir ao topo  (seta para cima)
   - Voltar Acao    (seta circular, ativa somente quando _undoStackPT tiver entradas)
*/
?>
<style type="text/css">
.util-botoes-fixos{
    position:fixed;
    right:14px;
    bottom:14px;
    z-index:9997;
    display:flex;
    flex-direction:column;
    gap:7px;
    align-items:center;
}
.util-btn-fixo{
    width:32px;
    height:32px;
    border-radius:50%;
    border:none;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:17px;
    line-height:1;
    box-shadow:0 2px 8px rgba(0,0,0,0.28);
    background:#2c3e50;
    color:#fff;
    padding:0;
    opacity:0.55;
    transition:opacity 0.18s, background 0.18s;
}
.util-btn-fixo:hover{opacity:1}
.util-btn-fixo[disabled]{opacity:0.15;cursor:default;pointer-events:none}
@media print{
    .util-botoes-fixos{display:none !important}
}
</style>

<div class="util-botoes-fixos" id="utilBotoesFixos">
    <button type="button" class="util-btn-fixo" id="utilBtnTopo"
            onclick="window.scrollTo(0,0)"
            title="Subir ao topo">&#8593;</button>
    <button type="button" class="util-btn-fixo" id="btnVoltarAcaoPT"
            onclick="if(typeof voltarAcaoPT==='function')voltarAcaoPT()"
            title="Voltar acao" disabled>&#8634;</button>
</div>

<script type="text/javascript">
(function() {
    var btnTopo = document.getElementById('utilBtnTopo');
    if (!btnTopo) return;
    function atualizarTopo() {
        var scroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        btnTopo.style.opacity = scroll > 150 ? '0.72' : '0.25';
    }
    if (window.addEventListener) {
        window.addEventListener('scroll', atualizarTopo, false);
    }
    atualizarTopo();
})();
</script>
