<?php /* _acess.php — Controles de acessibilidade globais (modo escuro + tamanho de fonte) */ ?>
<style id="acess-style">
/* ── WIDGET FLUTUANTE ── */
#acess-widget{
  position:fixed;top:8px;right:10px;z-index:99999;
  display:flex;align-items:center;gap:5px;
  background:rgba(26,58,92,.92);
  border:1px solid rgba(255,255,255,.18);
  border-radius:30px;
  padding:4px 10px;
  box-shadow:0 2px 10px rgba(0,0,0,.28);
  backdrop-filter:blur(4px);
  user-select:none;
}
body.dark #acess-widget{background:rgba(10,20,38,.95);border-color:rgba(255,255,255,.12);}
.acess-btn{
  background:rgba(255,255,255,.14);
  border:1px solid rgba(255,255,255,.28);
  color:#fff;border-radius:5px;
  padding:2px 8px;font-size:11px;font-weight:700;
  cursor:pointer;line-height:1.6;
  transition:background .15s;
}
.acess-btn:hover{background:rgba(255,255,255,.28);}
.acess-sep{width:1px;height:16px;background:rgba(255,255,255,.22);flex-shrink:0;}
.acess-tema{display:flex;align-items:center;gap:4px;}
.acess-ico{font-size:13px;color:#fff;opacity:.85;line-height:1;}
.acess-toggle{
  width:36px;height:19px;border-radius:999px;border:none;
  padding:2px 3px;cursor:pointer;
  background:#4fc3f7;
  display:flex;align-items:center;
  transition:background .25s;flex-shrink:0;
}
.acess-toggle.ativo{background:#1a2e44;}
.acess-knob{
  width:15px;height:15px;border-radius:50%;background:#fff;
  transition:transform .25s;display:block;
  box-shadow:0 1px 3px rgba(0,0,0,.35);
}
.acess-toggle.ativo .acess-knob{transform:translateX(17px);}
/* ── MODO ESCURO — regras gerais (todas as páginas) ── */
body.dark{background:#111114!important;color:#dde3ec!important;}
body.dark a{color:#90caf9;}
body.dark h1,body.dark h2,body.dark h3,body.dark h4{color:#dde3ec;}
body.dark table{border-color:#2a3040;}
body.dark th{background:#1a2434!important;color:#dde3ec!important;border-color:#2a3040!important;}
body.dark td{color:#dde3ec!important;border-color:#2a3040!important;background:#16191e!important;}
body.dark tr:nth-child(even) td{background:#1a1e28!important;}
body.dark input,body.dark select,body.dark textarea{background:#1c2028!important;color:#dde3ec!important;border-color:#2a3040!important;}
body.dark .quadrant,body.dark .card{background:#16191e!important;border-color:#252d3a!important;box-shadow:0 8px 20px rgba(0,0,0,.4)!important;}
body.dark .quadrant h2{color:#8a9ab0!important;}
body.dark .header h1{color:#dde3ec!important;}
body.dark .sub{color:#8a9ab0!important;}
body.dark .version{background:#0e2040!important;}
@media print{#acess-widget{display:none!important;}}
</style>

<div id="acess-widget">
  <button class="acess-btn" onclick="acAjustarFonte(-1)" title="Diminuir texto">A-</button>
  <button class="acess-btn" onclick="acAjustarFonte(1)"  title="Aumentar texto">A+</button>
  <div class="acess-sep"></div>
  <div class="acess-tema">
    <span class="acess-ico">&#9728;</span>
    <button class="acess-toggle" id="acBtnTema" onclick="acAlternarTema()" title="Modo escuro / claro">
      <span class="acess-knob"></span>
    </button>
    <span class="acess-ico">&#9790;</span>
  </div>
</div>

<script>
(function(){
  var nivelFonte = 0;
  var temaEscuro = false;
  var zoomLevels = [0.80,0.87,0.93,1.00,1.08,1.16,1.25,1.35];

  function aplicarZoom(){
    document.body.style.zoom = zoomLevels[nivelFonte + 3];
  }
  function aplicarTema(){
    var b = document.body;
    if(temaEscuro){
      b.className = (b.className.replace(/\s*dark\s*/g,' ') + ' dark').replace(/^\s+|\s+$/g,'');
    } else {
      b.className = b.className.replace(/\s*dark\s*/g,' ').replace(/^\s+|\s+$/g,'');
    }
    var btn = document.getElementById('acBtnTema');
    if(btn){
      if(temaEscuro){
        btn.className = (btn.className.replace(/\s*ativo\s*/g,' ') + ' ativo').replace(/^\s+|\s+$/g,'');
      } else {
        btn.className = btn.className.replace(/\s*ativo\s*/g,' ').replace(/^\s+|\s+$/g,'');
      }
    }
    /* Sincroniza btnTema legado se existir na página */
    var btnLeg = document.getElementById('btnTema');
    if(btnLeg){
      if(temaEscuro){
        btnLeg.className = (btnLeg.className.replace(/\s*ativo\s*/g,' ') + ' ativo').replace(/^\s+|\s+$/g,'');
      } else {
        btnLeg.className = btnLeg.className.replace(/\s*ativo\s*/g,' ').replace(/^\s+|\s+$/g,'');
      }
    }
  }

  window.acAjustarFonte = function(delta){
    nivelFonte = Math.max(-3, Math.min(4, nivelFonte + delta));
    aplicarZoom();
    try{ localStorage.setItem('odc_fonte', String(nivelFonte)); }catch(e){}
  };
  window.acAlternarTema = function(){
    temaEscuro = !temaEscuro;
    aplicarTema();
    try{ localStorage.setItem('odc_tema', temaEscuro ? '1' : '0'); }catch(e){}
  };
  /* Aliases para compatibilidade com código legado em oficio_dinamico_correios.php */
  window.ajustarFonte   = window.acAjustarFonte;
  window.alternarTema   = window.acAlternarTema;

  /* Restaurar preferências salvas */
  try{
    var fs = localStorage.getItem('odc_fonte');
    if(fs !== null){
      nivelFonte = Math.max(-3, Math.min(4, parseInt(fs,10) || 0));
      if(nivelFonte !== 0) aplicarZoom();
    }
    var tm = localStorage.getItem('odc_tema');
    if(tm === '1'){ temaEscuro = true; aplicarTema(); }
  }catch(e){}
})();
</script>
