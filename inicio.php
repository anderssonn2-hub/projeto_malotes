<?php
/* inicio.php — v1.0.12 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Início - Projeto Lacres v1.2.2</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: "Trebuchet MS", "Verdana", sans-serif;
      background: radial-gradient(circle at top, #f4f0ea 0%, #f2f6fb 45%, #eef1f5 100%);
      color: #1f2a35;
      min-height: 100vh;
      padding: 32px;
    }
    .page { max-width: 1100px; margin: 0 auto; }
    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 22px;
    }
    h1 {
      font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
      font-size: 28px;
      color: #1b3a57;
      line-height: 1.1;
    }
    .sub { font-size: 13px; color: #51606f; margin-top: 4px; }
    .version {
      background: #1b3a57;
      color: #fff;
      padding: 6px 12px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 12px;
      white-space: nowrap;
    }
    .quadrants {
      display: grid;
      grid-template-columns: repeat(2, minmax(240px, 1fr));
      gap: 18px;
    }
    .quadrant {
      background: #fff;
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.12);
      border: 1px solid #e1e5ea;
    }
    .quadrant h2 {
      font-size: 14px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #6b7a88;
      margin-bottom: 12px;
    }
    .actions { display: grid; gap: 10px; }
    a.btn {
      display: flex;
      flex-direction: column;
      gap: 4px;
      text-decoration: none;
      padding: 14px 16px;
      border-radius: 12px;
      color: #fff;
      font-weight: 700;
      min-height: 84px;
      justify-content: center;
      box-shadow: 0 6px 14px rgba(0,0,0,0.14);
    }
    .btn span { font-size: 12px; font-weight: 500; opacity: 0.95; }
    .btn-conf    { background: linear-gradient(135deg, #2f80ed 0%, #56ccf2 100%); }
    .btn-vocal   { background: linear-gradient(135deg, #c62828 0%, #ef5350 100%); }
    .btn-lacres  { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #4a3200; }
    .btn-oficio  { background: linear-gradient(135deg, #0f2027 0%, #2c5364 100%); }
    .btn-bloq    { background: linear-gradient(135deg, #512da8 0%, #7e57c2 100%); }
    .btn-cons    { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
    .btn-devol   { background: linear-gradient(135deg, #ff6f00 0%, #ffb300 100%); }
    .btn-controle{ background: linear-gradient(135deg, #004d40 0%, #00796b 100%); }
    .btn-previa  { background: linear-gradient(135deg, #6d4c41 0%, #8d6e63 100%); }
    .btn-painel    { background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); }
    .btn-restricao { background: linear-gradient(135deg, #e65100 0%, #ff6d00 100%); }
    .btn-lembrete  { background: linear-gradient(135deg, #0277bd 0%, #29b6f6 100%); }
    .btn-automacoes{ background: linear-gradient(135deg, #311b92 0%, #5e35b1 50%, #00897b 100%); }
    .btn-dinamico{ background: linear-gradient(135deg, #00695c 0%, #26a69a 100%); }

    .card-toggle-wrap { margin-top: 6px; }
    /* Botao-card colorido que tambem funciona como seta de toggle */
    button.btn.card-toggle-card {
      width: 100%;
      border: 0;
      cursor: pointer;
      text-align: left;
      font-family: inherit;
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 8px;
      font-size: 16px;
      padding: 14px 16px;
      border-radius: 12px;
      color: #fff;
      font-weight: 700;
      min-height: 84px;
      box-shadow: 0 6px 14px rgba(0,0,0,0.14);
    }
    button.btn.card-toggle-card .card-toggle-arrow { font-size: 13px; opacity: 0.9; }
    button.btn.card-toggle-card.aberto .card-toggle-arrow { transform: rotate(90deg); }
    .card-toggle-btn {
      width: 100%; background: none; border: 1px dashed #c8d0d8;
      border-radius: 8px; padding: 7px 12px; color: #6b7a88;
      font-size: 12px; font-weight: 700; cursor: pointer; text-align: left;
      letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;
    }
    .card-toggle-btn:hover { background: #f0f4f8; color: #2f3d4d; }
    .card-toggle-arrow { transition: transform 0.2s; display: inline-block; }
    .card-toggle-btn.aberto .card-toggle-arrow { transform: rotate(90deg); }
    .card-toggle-content { display: none; margin-top: 8px; }
    .card-toggle-content.aberto { display: grid; gap: 10px; }
    .sub-quadrant { font-size: 12px; color: #6b7a88; margin: -4px 0 10px; font-weight: 600; }

    @media (max-width: 900px) {
      body { padding: 18px; }
      .quadrants { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div>
        <h1>Projeto Lacres</h1>
        <div class="sub">Selecione um tema e inicie sua rotina.</div>
      </div>
      <div class="version">v2.0.3</div>
    </div>

    <div class="quadrants">

      <!-- Q1: Operação -->
      <section class="quadrant">
        <h2>Operacao</h2>
        <div class="actions">
          <a class="btn btn-conf" href="conferencia_pacotes.php">
            Iniciar conferencia
            <span>Leitura e validacao de pacotes</span>
          </a>
          <div class="card-toggle-wrap">
            <button type="button" class="card-toggle-btn" id="btn-secundarios" onclick="toggleCards('secundarios')">
              <span class="card-toggle-arrow">&#9658;</span> Pagina de controle e Previa
            </button>
            <div class="card-toggle-content" id="card-secundarios">
              <a class="btn btn-controle" href="conferencia_pacotes_controle.php">
                Pagina de controle
                <span>Operacao remota de lacres e etiqueta</span>
              </a>
              <a class="btn btn-previa" href="conferencia_pacotes_previa.php">
                Pagina de previa
                <span>Segunda tela para acompanhar o oficio</span>
              </a>
            </div>
          </div>
          <a class="btn btn-vocal" href="encontra_posto.php">
            Quem eu sou?
            <span>Leitura rapida com voz</span>
          </a>
        </div>
      </section>

      <!-- Q2: Lacres e Ofícios -->
      <section class="quadrant">
        <h2>Lacres e oficios</h2>
        <div class="actions">
          <a class="btn btn-lacres" href="lacres_novo.php">
            Ofício Correios e Poupa Tempo com Displays Correios
            <span>Fluxo completo com regras atuais</span>
          </a>
          <a class="btn btn-oficio" href="gera_oficio_poupa_tempo.php">
            Gerar Ofício Poupa Tempo (Sem displays dos Correios)
            <span>Selecionar datas e imprimir</span>
          </a>
          <div class="card-toggle-wrap">
            <button type="button" class="btn btn-dinamico card-toggle-card" id="btn-gerardinamico" onclick="toggleCards('gerardinamico')">
              <span class="card-toggle-arrow">&#9658;</span> Gerar Ofício Dinâmico
            </button>
            <div class="card-toggle-content" id="card-gerardinamico">
              <div class="card-toggle-wrap">
                <button type="button" class="card-toggle-btn" id="btn-odc" onclick="toggleCards('odc')">
                  <span class="card-toggle-arrow">&#9658;</span> Ofício Dinâmico Correios
                </button>
                <div class="card-toggle-content" id="card-odc">
                  <a class="btn btn-oficio" href="oficio_dinamico_correios.php">
                    Ofício Dinâmico Correios
                    <span>Conferência e geração do ofício por regional em tempo real</span>
                  </a>
                </div>
              </div>
              <div class="card-toggle-wrap">
                <button type="button" class="card-toggle-btn" id="btn-dinamicos" onclick="toggleCards('dinamicos')">
                  <span class="card-toggle-arrow">&#9658;</span> Ofício Dinâmico Poup. Tempo
                </button>
                <div class="card-toggle-content" id="card-dinamicos">
                  <a class="btn btn-oficio" href="gera_oficio_poupa_tempo_dinamico.php">
                    Gerar Ofício Poup. Tempo Dinâmico (Para os postos da capital de 05 a 80)
                    <span>Montagem por leitura de código de barras</span>
                  </a>
                  <a class="btn btn-oficio" href="gera_oficio_poupa_tempo_dinamico.php?modo_correios=1">
                    Gerar Ofício Dinâmico Poup. Tempo (Com Displays Correios - Todos os postos)
                    <span>Com folha inicial de Lacre Poup. Tempo e Etiqueta Correios – todos os postos</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Q3: Administração — recolhido por padrão -->
      <section class="quadrant">
        <h2>Administracao</h2>
        <div class="card-toggle-wrap">
          <button type="button" class="card-toggle-btn" id="btn-admin" onclick="toggleCards('admin')">
            <span class="card-toggle-arrow">&#9658;</span> Ver ferramentas de administração
          </button>
          <div class="card-toggle-content" id="card-admin">
            <a class="btn btn-bloq" href="bloqueados.php">
              Bloqueio de postos
              <span>Definir postos nao enviados</span>
            </a>
            <a class="btn btn-restricao" href="restricoes_posto.php">
              Restricoes de postos
              <span>Segurar, adiantar, fechado e tipos personalizados</span>
            </a>
            <a class="btn btn-lembrete" href="lembretes.php">
              Lembretes de postos
              <span>Aviso por Telegram e Email de postos nao fechados</span>
            </a>
            <a class="btn btn-cons" href="consulta_producao.php">
              Consultar producao
              <span>Busca por lotes, postos e datas</span>
            </a>
            <a class="btn btn-painel" href="painel_lotes.php">
              Painel de Lotes
              <span>Controle de lotes: na estante, em trânsito e retornados</span>
            </a>
            <a class="btn btn-cons" href="auditoria_lote.php">
              Auditoria de lote
              <span>Linha do tempo do lote: produção, conferência, ofício, displays</span>
            </a>
          </div>
        </div>
        <div class="card-toggle-wrap">
          <button type="button" class="card-toggle-btn" id="btn-automacoes" onclick="toggleCards('automacoes')">
            <span class="card-toggle-arrow">&#9658;</span> Ferramentas de automação
          </button>
          <div class="card-toggle-content" id="card-automacoes">
            <a class="btn btn-painel" href="dashboard.php">
              Painel do dia
              <span>Produção, conferência, ofícios e displays do período</span>
            </a>
            <a class="btn btn-controle" href="carteira_mobile.php">
              Carteira do operador (celular)
              <span>Um leitor só: reconhece lote, display ou posto</span>
            </a>
            <a class="btn btn-cons" href="qrcode_auditoria.php">
              QR de auditoria do lote
              <span>Cole no ofício: abre a linha do tempo do lote</span>
            </a>
            <a class="btn btn-previa" href="qrcode_posto.php">
              QR por posto
              <span>Cole na prateleira: abre a produção do posto</span>
            </a>
            <a class="btn btn-lembrete" href="qrcode_paginas.php">
              QR CODE de páginas
              <span>Mural: QRs para busca, conferência por câmera e posto por voz</span>
            </a>
          </div>
        </div>
      </section>

      <!-- Q4: DISPLAY CORREIOS (PESQUISA E ADMINISTRAÇÃO) -->
      <section class="quadrant">
        <h2>DISPLAY CORREIOS (PESQUISA E ADMINISTRAÇÃO)</h2>
        <div class="sub-quadrant">Registrar devolução, recebimentos e demais pesquisas</div>
        <div class="actions">
          <a class="btn btn-devol" href="devolucao_etiquetas.php">
            Devolucao de etiquetas
            <span>Registrar retorno de malotes</span>
          </a>
          <a class="btn btn-devol" href="devolucao_lotes.php" style="background:#fff3cd;color:#7a5a00;">
            Devolucao de lotes
            <span>Lotes que voltaram (malote errado, etc.)</span>
          </a>
          <a class="btn btn-painel" href="displays_por_posto.php">
            Displays por posto (local x trânsito)
            <span>Quantos displays cada posto tem, locais e em trânsito</span>
          </a>
          <a class="btn btn-devol" href="cadastrar_displays.php" style="background:#0b5e57;color:#fff;">
            Cadastrar displays novos
            <span>Ler e gravar displays por posto em cadastroMalotes</span>
          </a>
          <a class="btn btn-painel" href="auditoria_displays.php">
            Auditoria de displays (arquivo x banco)
            <span>Compara o arquivo mestre com o banco: faltando, em outro posto, sobrando</span>
          </a>
        </div>
      </section>

    </div>
  </div>
<?php include __DIR__ . '/includes/_acess.php'; ?>
  <?php include __DIR__ . '/includes/processando_overlay.php'; ?>
  <?php include __DIR__ . '/includes/util_botoes_fixos.php'; ?>
<script>
function toggleCards(id) {
    var btn = document.getElementById('btn-' + id);
    var content = document.getElementById('card-' + id);
    if (!btn || !content) return;
    var aberto = content.className.indexOf('aberto') >= 0;
    if (aberto) {
        content.className = content.className.replace(/\s*aberto/g, '');
        btn.className = btn.className.replace(/\s*aberto/g, '');
    } else {
        content.className = content.className + ' aberto';
        btn.className = btn.className + ' aberto';
    }
}
</script>
</body>
</html>
