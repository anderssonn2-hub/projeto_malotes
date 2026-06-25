<?php
/* automacoes.php — v1.0.0
   Centro de Automacoes: reune as ferramentas novas (painel, leitura unica
   mobile e geradores de QR) em uma unica pagina, acessivel por 1 botao no
   inicio.php — sem poluir a tela inicial com varios cards. */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Automacoes - Projeto Lacres</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Trebuchet MS", "Verdana", sans-serif; background: radial-gradient(circle at top, #f4f0ea 0%, #f2f6fb 45%, #eef1f5 100%); color: #1f2a35; min-height: 100vh; padding: 32px; }
    .page { max-width: 1000px; margin: 0 auto; }
    .header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; flex-wrap: wrap; }
    h1 { font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif; font-size: 28px; color: #1b3a57; line-height: 1.1; }
    .sub { font-size: 13px; color: #51606f; margin-top: 4px; }
    .voltar { text-decoration: none; background: #e2e8f0; color: #334155; padding: 9px 14px; border-radius: 10px; font-weight: 700; font-size: 13px; }
    .quadrants { display: grid; grid-template-columns: repeat(2, minmax(240px, 1fr)); gap: 18px; }
    .quadrant { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); border: 1px solid #e1e5ea; }
    .quadrant h2 { font-size: 14px; letter-spacing: 1px; text-transform: uppercase; color: #6b7a88; margin-bottom: 12px; }
    .actions { display: grid; gap: 10px; }
    a.btn { display: flex; flex-direction: column; gap: 4px; text-decoration: none; padding: 14px 16px; border-radius: 12px; color: #fff; font-weight: 700; min-height: 84px; justify-content: center; box-shadow: 0 6px 14px rgba(0,0,0,0.14); }
    .btn span { font-size: 12px; font-weight: 500; opacity: 0.95; }
    .b-painel  { background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); }
    .b-carteira{ background: linear-gradient(135deg, #0f2027 0%, #2c5364 100%); }
    .b-aud     { background: linear-gradient(135deg, #00897b 0%, #26a69a 100%); }
    .b-posto   { background: linear-gradient(135deg, #6d4c41 0%, #8d6e63 100%); }
    .b-busca   { background: linear-gradient(135deg, #2f80ed 0%, #56ccf2 100%); }
    .b-devol   { background: linear-gradient(135deg, #ff6f00 0%, #ffb300 100%); }
    .nota { grid-column: 1 / -1; font-size: 12px; color: #6b7a88; background: #fff; border-radius: 12px; padding: 12px 16px; border: 1px dashed #cbd5e1; }
    @media (max-width: 900px) { body { padding: 18px; } .quadrants { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div>
        <h1>Automacoes</h1>
        <div class="sub">Ferramentas de agilidade: painel, leitura unica e QR Codes.</div>
      </div>
      <a class="voltar" href="inicio.php">&#8592; Voltar ao inicio</a>
    </div>

    <div class="quadrants">

      <section class="quadrant">
        <h2>Visao geral</h2>
        <div class="actions">
          <a class="btn b-painel" href="dashboard.php">
            Painel do dia
            <span>Producao, conferencia, oficios e displays do periodo</span>
          </a>
        </div>
      </section>

      <section class="quadrant">
        <h2>Leitura no celular</h2>
        <div class="actions">
          <a class="btn b-carteira" href="carteira_mobile.php">
            Carteira do operador
            <span>Um leitor so: bipe e ele reconhece lote, display ou posto</span>
          </a>
        </div>
      </section>

      <section class="quadrant">
        <h2>Gerar QR Codes</h2>
        <div class="actions">
          <a class="btn b-aud" href="qrcode_auditoria.php">
            QR de auditoria do lote
            <span>Cole no oficio: abre a linha do tempo do lote</span>
          </a>
          <a class="btn b-posto" href="qrcode_posto.php">
            QR por posto
            <span>Cole na prateleira: abre a producao do posto</span>
          </a>
          <a class="btn b-busca" href="qrcode_paginas.php">
            QR CODE de paginas
            <span>Mural: QRs para busca, conferencia por camera e posto por voz</span>
          </a>
        </div>
      </section>

      <section class="quadrant">
        <h2>Devolucao por leitura</h2>
        <div class="actions">
          <a class="btn b-devol" href="devolucao_etiquetas.php">
            Devolver displays por scan
            <span>Bipe a etiqueta Correios para registrar o retorno</span>
          </a>
        </div>
      </section>

      <div class="nota">
        Dica: os QR Codes apontam, por padrao, para o servidor da rede
        (10.15.61.169). Em cada gerador da para ajustar o endereco e o tamanho
        antes de imprimir.
      </div>

    </div>
  </div>
</body>
</html>
