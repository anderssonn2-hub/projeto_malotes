<?php
$nomesPostos = array();
$ultimoOficioCorreios = 0;
try {
    $pdo_controle = new PDO((getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8' : 'mysql:host=' . (getenv('DB_HOST') ?: (getenv('DB_HOST') ?: '10.15.61.169')) . ';dbname=' . (getenv('DB_NAME') ?: (getenv('DB_NAME') ?: 'controle')) . ';charset=utf8'), 'root', 'vazio');
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stNomes = $pdo_controle->query("SELECT LPAD(CAST(posto AS UNSIGNED), 3, '0') AS posto, MAX(nome) AS nome FROM ciPostosCsv GROUP BY LPAD(CAST(posto AS UNSIGNED), 3, '0') ORDER BY LPAD(CAST(posto AS UNSIGNED), 3, '0')");
    while ($rowNome = $stNomes->fetch(PDO::FETCH_ASSOC)) {
        $nomesPostos[$rowNome['posto']] = trim((string)$rowNome['nome']);
    }

    $stUltimo = $pdo_controle->query("SELECT id FROM ciDespachos WHERE LOWER(grupo) = 'correios' ORDER BY id DESC LIMIT 1");
    $ultimoOficioCorreios = (int)$stUltimo->fetchColumn();
} catch (Exception $e) {
    $nomesPostos = array();
    $ultimoOficioCorreios = 0;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévia do Ofício dos Correios</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            --papel: #ffffff;
            --papel-sombra: #d7d7d7;
            --tinta: #161616;
            --tinta-suave: #5e5e5e;
            --grade: #1f1f1f;
            --tarja: #ececec;
            --destaque: #169b41;
            --destaque-claro: #127a33;
            --aviso: #b46a13;
            --erro: #972d2d;
            --cinza-botao: #747b84;
            --cinza-botao-hover: #656c75;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #ececec;
            color: var(--tinta);
            font-family: Arial, Helvetica, sans-serif;
        }
        .pagina {
            min-height: 100vh;
            padding: 28px 18px 48px;
        }
        .barra-acoes {
            max-width: 1180px;
            margin: 0 auto 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .barra-esquerda {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .titulo-pagina {
            font-size: 22px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: bold;
        }
        .subtitulo-pagina {
            font-size: 12px;
            color: var(--tinta-suave);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            border: 0;
            border-radius: 4px;
            background: var(--cinza-botao);
            color: #fff;
            padding: 11px 18px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            background: var(--cinza-botao-hover);
        }
        .btn-principal {
            background: var(--destaque);
            color: #fff;
        }
        .btn-principal:hover {
            background: var(--destaque-claro);
        }
        .status-barra {
            max-width: 1180px;
            margin: 0 auto 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: var(--tinta-suave);
        }
        .status-item {
            padding: 8px 12px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(45,43,39,0.18);
        }
        .status-item strong {
            color: var(--tinta);
        }
        .aviso {
            max-width: 1180px;
            margin: 0 auto 16px;
            padding: 12px 14px;
            border: 1px solid rgba(180,106,19,0.35);
            background: rgba(255,249,232,0.88);
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #6b460f;
            display: none;
        }
        .aviso.erro {
            border-color: rgba(151,45,45,0.35);
            background: rgba(255,239,239,0.9);
            color: var(--erro);
        }
        .documento {
            max-width: 1180px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
            padding: 16px 16px 24px;
            position: relative;
        }
        .quadro-logo {
            border: 1px solid #000;
            padding: 10px 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .quadro-logo img {
            height: 60px;
            width: auto;
            flex: 0 0 auto;
        }
        .quadro-logo-texto {
            font-size: 14px;
            line-height: 1.05;
            color: #000;
        }
        .quadro-logo-texto strong {
            display: block;
        }
        .info-cliente-box {
            border: 1px solid #000;
            padding: 12px 14px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 132px;
            gap: 12px;
            align-items: center;
        }
        .info-cliente-texto p {
            margin: 0 0 8px;
            font-size: 13px;
            color: #000;
            line-height: 1.25;
        }
        .info-cliente-texto p:last-child {
            margin-bottom: 0;
        }
        .numero-box {
            border: 1px solid #000;
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            background: #fff;
        }
        .documento::before {
            content: '';
            position: absolute;
            inset: 10px;
            border: 1px solid rgba(45,43,39,0.08);
            pointer-events: none;
        }
        .cabecalho {
            display: none;
        }
        .cabecalho-bloco {
            border: 1px solid var(--grade);
            background: #fff;
        }
        .cabecalho-linha {
            display: grid;
            grid-template-columns: 110px 1fr;
            border-bottom: 1px solid rgba(45,43,39,0.4);
            min-height: 32px;
        }
        .cabecalho-linha:last-child {
            border-bottom: 0;
        }
        .cabecalho-rotulo {
            padding: 7px 8px;
            font-family: Arial, sans-serif;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-align: center;
            border-right: 1px solid rgba(45,43,39,0.4);
            background: #efefef;
        }
        .cabecalho-valor {
            padding: 7px 10px;
            font-size: 12px;
            line-height: 1.35;
        }
        .numero-oficio {
            border: 1px solid var(--grade);
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
        }
        .numero-topo {
            padding: 8px 8px 5px;
            font-family: Arial, sans-serif;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-align: center;
            background: #efefef;
        }
        .numero-valor {
            padding: 10px 8px 4px;
            text-align: center;
            font-size: 32px;
            line-height: 1;
            font-family: Georgia, "Times New Roman", serif;
        }
        .numero-rodape {
            padding: 6px 8px 10px;
            font-family: Arial, sans-serif;
            font-size: 10px;
            text-align: center;
            color: var(--tinta-suave);
            text-transform: uppercase;
        }
        .documento-meta {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 6px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: var(--tinta-suave);
        }
        .subtitulo-quadro {
            margin: 0 0 8px;
            font-size: 11px;
            color: var(--tinta-suave);
        }
        .texto-abertura {
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.45;
            text-align: left;
        }
        .secao {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #fff;
        }
        th, td {
            border: 1px solid var(--grade);
            padding: 3px 5px;
            font-size: 11px;
            vertical-align: middle;
        }
        thead th {
            background: #f3f3f3;
            font-family: Arial, sans-serif;
            font-size: 10px;
            text-transform: none;
        }
        .linha-secao th {
            background: #f3f3f3;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .linha-secao th:not(:first-child) {
            text-align: center;
            font-size: 11px;
            text-transform: none;
        }
        .col-posto { width: 34%; text-align: center; }
        .col-iipr { width: 12%; }
        .col-correios { width: 12%; }
        .col-etiqueta { width: 32%; }
        .destino {
            font-size: 12px;
            text-align: center;
        }
        .campo-impressao {
            width: 100%;
            border: 1px solid #8f8f8f;
            background: #fdfdfd;
            color: var(--tinta);
            font-size: 11px;
            font-family: "Courier New", monospace;
            padding: 2px 6px;
            outline: none;
            height: 22px;
            text-align: center;
        }
        .campo-impressao[readonly] {
            cursor: default;
        }
        .campo-lacres-iipr {
            font-family: "Courier New", monospace;
        }
        .campo-etiqueta {
            text-align: left;
        }
        .linha-split td {
            background: #eef6ff;
            color: #0f4d85;
            font-family: Arial, sans-serif;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: center;
            padding: 4px 6px;
        }
        .campo-etiqueta:focus {
            border-color: var(--destaque);
            box-shadow: 0 0 0 1px rgba(22,155,65,0.15);
        }
        .rodape {
            margin-top: 20px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: var(--tinta-suave);
        }
        .info-cliente-impressao,
        .footer-impressao {
            display: none;
        }
        .info-cliente-impressao p,
        .footer-impressao p {
            margin: 4px 0;
            font-size: 11px;
            color: #000;
        }
        .texto-rodape-lotes {
            margin-bottom: 18px;
        }
        .assinaturas {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            width: 100%;
            page-break-inside: avoid;
            align-items: flex-end;
        }
        .assinatura {
            width: 38%;
            padding-top: 22px;
            border-top: 1px solid var(--grade);
            text-align: center;
            color: var(--tinta);
            min-width: 220px;
        }
        .assinatura-data {
            width: 24%;
            min-width: 140px;
            padding-top: 0;
            border-top: 0;
            text-align: center;
            font-weight: bold;
            color: var(--tinta);
        }
        .vazio {
            border: 1px dashed rgba(45,43,39,0.4);
            padding: 28px;
            background: rgba(255,255,255,0.9);
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: var(--tinta-suave);
        }
        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(18, 18, 18, 0.55);
            z-index: 20;
            padding: 18px;
        }
        .modal.ativo {
            display: flex;
        }
        .modal-conteudo {
            width: min(520px, 100%);
            background: #f7f1e4;
            border: 1px solid var(--grade);
            box-shadow: 0 24px 60px rgba(0,0,0,0.28);
            padding: 24px;
        }
        .modal-titulo {
            margin: 0 0 10px;
            font-size: 24px;
        }
        .modal-texto {
            margin: 0 0 18px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--tinta-suave);
        }
        .modal-opcoes {
            display: grid;
            gap: 10px;
        }
        .modal-opcao {
            width: 100%;
            text-align: left;
            padding: 12px 14px;
            background: rgba(255,255,255,0.82);
            border: 1px solid rgba(45,43,39,0.3);
            cursor: pointer;
        }
        .modal-opcao strong {
            display: block;
            font-family: Arial, sans-serif;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .modal-opcao span {
            display: block;
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: var(--tinta-suave);
        }
        .modal-rodape {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
        }
        @media (max-width: 900px) {
            .quadro-logo,
            .info-cliente-box {
                grid-template-columns: 1fr;
                display: block;
            }
            .numero-box {
                margin-top: 10px;
            }
            .cabecalho {
                grid-template-columns: 1fr;
            }
            .documento {
                padding: 22px 18px 28px;
            }
            .cabecalho-linha {
                grid-template-columns: 96px 1fr;
            }
            th, td {
                padding: 7px 6px;
                font-size: 12px;
            }
        }
        @media print {
            @page {
                margin: 12mm;
            }
            body {
                background: #fff;
                font-family: Arial, sans-serif;
            }
            .pagina {
                padding: 0;
            }
            .barra-acoes,
            .status-barra,
            .aviso,
            .modal {
                display: none !important;
            }
            .documento {
                max-width: none;
                box-shadow: none;
                padding: 0;
                background: #fff;
            }
            .documento::before {
                display: none;
            }
            .info-cliente-impressao,
            .footer-impressao {
                display: block;
            }
            .quadro-logo,
            .info-cliente-box,
            .documento-meta,
            .subtitulo-quadro,
            .texto-abertura,
            .texto-rodape-lotes {
                display: none !important;
            }
            .quadro-logo,
            .info-cliente-box {
                break-inside: avoid;
            }
            .campo-impressao {
                appearance: none;
                -webkit-appearance: none;
                border: 0;
                background: transparent;
                height: auto;
                padding: 0;
                font-size: 11px;
                color: #000;
                box-shadow: none;
            }
            .campo-etiqueta {
                text-align: left;
            }
            .texto-abertura,
            .documento-meta,
            .texto-rodape-lotes {
                font-size: 10px;
            }
            .subtitulo-quadro,
            .info-cliente-impressao p,
            .footer-impressao p {
                font-size: 10px;
            }
            th, td {
                font-size: 10px;
            }
            thead th,
            .cabecalho-rotulo,
            .numero-topo,
            .numero-rodape {
                font-size: 9px;
            }
            .assinatura {
                width: 45%;
            }
        }
    </style>
</head>
<body>
    <div class="pagina">
        <div class="barra-acoes">
            <div class="barra-esquerda">
                <div class="titulo-pagina">Prévia do Ofício dos Correios</div>
                <div class="subtitulo-pagina">Modelo editável para gravar e imprimir o ofício final dos Correios</div>
            </div>
            <div class="acoes">
                <button type="button" class="btn btn-principal" id="btnGravarImprimir">Gravar e Imprimir Correios</button>
                <button type="button" class="btn" id="btnImprimir">Apenas Imprimir</button>
            </div>
        </div>

        <div class="status-barra">
            <div class="status-item"><strong id="statusNumeroRotulo">Número:</strong> <span id="statusNumeroValor">Prévia</span></div>
            <div class="status-item"><strong>Linhas prontas:</strong> <span id="statusLinhas">0</span></div>
            <div class="status-item"><strong>Datas:</strong> <span id="statusDatas">-</span></div>
            <div class="status-item"><strong>Responsável:</strong> <span id="statusUsuario">-</span></div>
            <div class="status-item"><strong>Atualização:</strong> <span id="statusAtualizacao">-</span></div>
        </div>

        <div class="aviso" id="caixaAviso"></div>

        <div class="documento">
            <div class="quadro-logo">
                <img src="logo_celepar.png" alt="Celepar">
                <div class="quadro-logo-texto">
                    <strong>CELEPAR – TECNOLOGIA DA INFORMAÇÃO E COMUNICAÇÃO DO PARANÁ</strong>
                    COMPROVANTE DE ENTREGA DE SERVIÇOS
                </div>
            </div>

            <div class="info-cliente-box">
                <div class="info-cliente-texto">
                    <p><strong>CLIENTE:</strong> CORREIO - <strong>END.</strong>R: JOÃO NEGRÃO, 1251 - CENTRO - CURITIBA PARANÁ</p>
                    <p><strong>SISTEMA:</strong> SIV -- <strong>SETOR:</strong> EXPEDIÇÃO</p>
                </div>
                <div class="numero-box" id="numeroOficioBox">Nº Prévia</div>
            </div>

            <div class="info-cliente-impressao">
                <p><strong>CLIENTE:</strong> CORREIO - <strong>END.</strong>R: JOÃO NEGRÃO, 1251 - CENTRO - CURITIBA PARANÁ</p>
                <p><strong>SISTEMA:</strong> SIV -- <strong>SETOR:</strong> EXPEDIÇÃO</p>
            </div>

            <div class="cabecalho">
                <div class="cabecalho-bloco">
                    <div class="cabecalho-linha">
                        <div class="cabecalho-rotulo">Cliente</div>
                        <div class="cabecalho-valor">CORREIOS</div>
                    </div>
                    <div class="cabecalho-linha">
                        <div class="cabecalho-rotulo">Referência</div>
                        <div class="cabecalho-valor">Ofício consolidado de lacres e etiquetas dos malotes expedidos</div>
                    </div>
                    <div class="cabecalho-linha">
                        <div class="cabecalho-rotulo">Período</div>
                        <div class="cabecalho-valor" id="textoPeriodo">Aguardando datas da conferência</div>
                    </div>
                    <div class="cabecalho-linha">
                        <div class="cabecalho-rotulo">Emitido por</div>
                        <div class="cabecalho-valor" id="textoUsuario">Equipe de Conferência</div>
                    </div>
                </div>
                <div class="numero-oficio">
                    <div class="numero-topo">Ofício</div>
                    <div class="numero-valor" id="numeroOficio">Prévia</div>
                    <div class="numero-rodape" id="numeroRodape">Grave para numerar</div>
                </div>
            </div>

            <div class="documento-meta" style="display:none;">
                <div id="metaUltimoOficio">Último ofício Correios: <?php echo (int)$ultimoOficioCorreios; ?></div>
            </div>

            <div id="areaGrade" class="vazio">Aguardando dados da conferência.</div>

            <div class="rodape">
                <div class="texto-rodape-lotes" id="textoRodapeLotes">Nenhuma linha pronta do ofício foi gerada.</div>
                <div class="assinaturas">
                    <div class="assinatura" id="assinaturaEsquerda">RESPONSÁVEL CELEPAR</div>
                    <div class="assinatura-data" id="assinaturaData">Data: -</div>
                    <div class="assinatura">RESPONSÁVEL CORREIOS</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="modalGravacao" aria-hidden="true">
        <div class="modal-conteudo">
            <h2 class="modal-titulo">Gravar ofício Correios</h2>
            <p class="modal-texto" id="modalTextoBase">Escolha se o documento deve sobrescrever o último número existente ou se deve criar um novo ofício.</p>
            <div class="modal-opcoes">
                <button type="button" class="modal-opcao" id="btnSobrescrever">
                    <strong id="textoSobrescrever">Sobrescrever último</strong>
                    <span id="detalheSobrescrever">Atualiza o último ofício Correios disponível.</span>
                </button>
                <button type="button" class="modal-opcao" id="btnCriarNovo">
                    <strong id="textoCriarNovo">Criar novo</strong>
                    <span id="detalheCriarNovo">Gera um novo número de ofício sem alterar o anterior.</span>
                </button>
            </div>
            <div class="modal-rodape">
                <button type="button" class="btn" id="btnCancelarModal">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var nomesPostos = <?php echo json_encode($nomesPostos); ?> || {};
        var ultimoOficioInicial = <?php echo (int)$ultimoOficioCorreios; ?> || 0;
        var paramsUrl = new URLSearchParams(window.location.search || '');
        var canalControle = paramsUrl.get('canal_controle') || 'principal';
        var storageKey = 'conferencia_previa_malotes_v1';
        var areaGrade = document.getElementById('areaGrade');
        var statusNumeroRotulo = document.getElementById('statusNumeroRotulo');
        var statusNumeroValor = document.getElementById('statusNumeroValor');
        var statusLinhas = document.getElementById('statusLinhas');
        var statusDatas = document.getElementById('statusDatas');
        var statusUsuario = document.getElementById('statusUsuario');
        var statusAtualizacao = document.getElementById('statusAtualizacao');
        var caixaAviso = document.getElementById('caixaAviso');
        var textoPeriodo = document.getElementById('textoPeriodo');
        var textoUsuario = document.getElementById('textoUsuario');
        var numeroOficio = document.getElementById('numeroOficio');
        var numeroOficioBox = document.getElementById('numeroOficioBox');
        var numeroRodape = document.getElementById('numeroRodape');
        var textoRodapeLotes = document.getElementById('textoRodapeLotes');
        var assinaturaEsquerda = document.getElementById('assinaturaEsquerda');
        var assinaturaData = document.getElementById('assinaturaData');
        var metaUltimoOficio = document.getElementById('metaUltimoOficio');
        var modalGravacao = document.getElementById('modalGravacao');
        var modalTextoBase = document.getElementById('modalTextoBase');
        var textoSobrescrever = document.getElementById('textoSobrescrever');
        var detalheSobrescrever = document.getElementById('detalheSobrescrever');
        var textoCriarNovo = document.getElementById('textoCriarNovo');
        var detalheCriarNovo = document.getElementById('detalheCriarNovo');
        var estadoOficio = {
            ultimoConhecido: ultimoOficioInicial,
            salvoId: 0,
            salvoNumero: 0,
            salvando: false
        };
        var channel = null;

        function escapeHtml(valor) {
            return String(valor || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function exibirAviso(texto, erro) {
            caixaAviso.textContent = String(texto || '');
            caixaAviso.className = erro ? 'aviso erro' : 'aviso';
            caixaAviso.style.display = texto ? 'block' : 'none';
        }

        function lerSnapshot() {
            try {
                var bruto = localStorage.getItem(storageKey);
                return bruto ? JSON.parse(bruto) : null;
            } catch (e) {
                return null;
            }
        }

        function salvarSnapshot(snapshot) {
            if (!snapshot) return;
            try {
                localStorage.setItem(storageKey, JSON.stringify(snapshot));
            } catch (e1) {}
            if (channel) {
                try {
                    channel.postMessage(snapshot);
                } catch (e2) {}
            }
        }

        function normalizarRegionalTexto(valor) {
            var digitos = String(valor || '').replace(/\D+/g, '');
            if (!digitos) return '';
            return digitos.padStart(3, '0');
        }

        function obterChavesContextoRemoto(estado) {
            var chaves = [];
            var regional = normalizarRegionalTexto(estado && estado.regional ? estado.regional : '');
            var posto = normalizarRegionalTexto(estado && estado.posto ? estado.posto : '');
            if (regional) {
                chaves.push({ tipo: 'regional', chave: regional });
            }
            if (posto) {
                chaves.push({ tipo: 'posto', chave: posto });
            }
            return chaves;
        }

        function itemCorrespondeAoEstado(item, chavesEstado) {
            if (!item || !chavesEstado || !chavesEstado.length) return false;
            var contextoTipo = String(item.contexto_tipo || '').trim();
            var contextoChave = normalizarRegionalTexto(item.contexto_chave || '');
            var regionalCodigo = normalizarRegionalTexto(item.regional_codigo || item.regional || '');
            var postoCodigo = normalizarRegionalTexto(item.posto || '');

            for (var i = 0; i < chavesEstado.length; i++) {
                var chaveEstado = chavesEstado[i];
                if (chaveEstado.tipo === 'regional') {
                    if (contextoTipo === 'regional' && contextoChave === chaveEstado.chave) return true;
                    if (regionalCodigo === chaveEstado.chave && contextoTipo !== 'posto') return true;
                }
                if (chaveEstado.tipo === 'posto') {
                    if (contextoTipo === 'posto' && contextoChave === chaveEstado.chave) return true;
                    if (postoCodigo === chaveEstado.chave) return true;
                }
            }
            return false;
        }

        function itemAceitaEstadoRemoto(item) {
            if (!item) return false;
            if (item.pendente_lacre) return true;
            return !item.grupo_correios && !item.grupo_iipr && !item.etiqueta_correios;
        }

        function aplicarEstadoRemotoAoSnapshot(snapshot, estado) {
            if (!snapshot || !snapshot.resumo || !snapshot.resumo.length || !estado) return snapshot;
            var chavesEstado = obterChavesContextoRemoto(estado);
            if (!chavesEstado.length) return snapshot;
            var alterou = false;
            var candidatos = [];
            var preferenciais = [];
            for (var i = 0; i < snapshot.resumo.length; i++) {
                var item = snapshot.resumo[i] || {};
                if (!itemCorrespondeAoEstado(item, chavesEstado)) continue;
                candidatos.push(i);
                if (itemAceitaEstadoRemoto(item)) {
                    preferenciais.push(i);
                }
            }
            var indicesAtualizacao = preferenciais.length ? preferenciais : candidatos;
            if (!indicesAtualizacao.length) return snapshot;

            var indiceDestino = indicesAtualizacao[indicesAtualizacao.length - 1];
            var itemDestino = snapshot.resumo[indiceDestino] || {};
            if (estado.lacre_iipr && itemDestino.lacre_iipr !== estado.lacre_iipr) {
                itemDestino.lacre_iipr = String(estado.lacre_iipr || '').trim();
                alterou = true;
            }
            if (estado.lacre_correios && itemDestino.lacre_correios !== estado.lacre_correios) {
                itemDestino.lacre_correios = String(estado.lacre_correios || '').trim();
                alterou = true;
            }
            if (estado.etiqueta_correios && itemDestino.etiqueta_correios !== estado.etiqueta_correios) {
                itemDestino.etiqueta_correios = String(estado.etiqueta_correios || '').trim();
                alterou = true;
            }
            snapshot.resumo[indiceDestino] = itemDestino;
            if (alterou) {
                salvarSnapshot(snapshot);
            }
            return snapshot;
        }

        function sincronizarComEstadoRemoto() {
            fetch('conferencia_pacotes.php?ler_estado_remoto_ajax=1&canal=' + encodeURIComponent(canalControle), { cache: 'no-store' })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    var estado = data && data.estado ? data.estado : null;
                    if (!estado) return;
                    var snapshot = lerSnapshot();
                    if (!snapshot) return;
                    snapshot = aplicarEstadoRemotoAoSnapshot(snapshot, estado);
                    renderizarQuandoPossivel(snapshot);
                })
                .catch(function() {});
        }

        function nomeSecao(item) {
            var posto = String(item.posto || '').trim();
            var postoPad = posto && /^\d+$/.test(posto) ? posto.padStart(3, '0') : posto;
            if (postoPad === '001') return 'POSTO 001';
            var codigo = parseInt(item.regional_codigo || 0, 10) || 0;
            if (codigo === 0) return 'CAPITAL';
            if (codigo === 1) return 'METROPOLITANA';
            if (codigo === 999) return 'CENTRAL IIPR';
            return 'REGIONAIS';
        }

        function garantirChavesResumo(snapshot, persistir) {
            if (!snapshot || !snapshot.resumo || !snapshot.resumo.length) return snapshot;
            var alterou = false;
            for (var i = 0; i < snapshot.resumo.length; i++) {
                var item = snapshot.resumo[i] || {};
                if (!item.row_key) {
                    var contextoBase = item.contexto_chave || item.posto || item.regional_codigo || '';
                    if (item.pendente_lacre) {
                        item.row_key = 'pend:' + contextoBase;
                    } else {
                        item.row_key = item.grupo_correios ? ('gc:' + item.grupo_correios) : (item.grupo_iipr ? ('gi:' + item.grupo_iipr) : ('ln:' + i + ':' + contextoBase));
                    }
                    snapshot.resumo[i] = item;
                    alterou = true;
                }
                if (!item.grupos_correios || !item.grupos_correios.length) {
                    item.grupos_correios = item.grupo_correios ? [item.grupo_correios] : [];
                    snapshot.resumo[i] = item;
                    alterou = true;
                }
            }
            if (alterou && persistir) {
                salvarSnapshot(snapshot);
            }
            return snapshot;
        }

        function calcularLinhasLacres(valor) {
            var texto = String(valor || '').trim();
            if (!texto) return 1;
            var segmentos = texto.split(/\s*,\s*/).filter(function(item) { return !!item; }).length;
            var porTamanho = Math.ceil(texto.length / 24);
            var porSegmentos = Math.ceil(segmentos / 3);
            var linhas = Math.max(1, porTamanho, porSegmentos);
            return Math.min(6, linhas);
        }

        function extrairSegmentoSplitGrupo(grupo) {
            var texto = String(grupo || '');
            var match = texto.match(/_S(\d+)(?:_|$)/);
            if (!match || typeof match[1] === 'undefined') return 0;
            return parseInt(match[1], 10) || 0;
        }

        function montarLinhas(snapshot) {
            snapshot = garantirChavesResumo(snapshot, false);
            var resumo = snapshot && snapshot.resumo ? snapshot.resumo : [];
            var linhas = [];
            for (var i = 0; i < resumo.length; i++) {
                var item = resumo[i];
                if (!item) continue;
                var posto = String(item.posto || '').trim();
                var postoPadrao = posto && /^\d+$/.test(posto) ? posto.padStart(3, '0') : posto;
                var contextoTipo = String(item.contexto_tipo || '').trim();
                var contextoRotulo = String(item.contexto_rotulo || '').trim();
                var regionalCodigo = String(item.regional_codigo || '').trim();
                var destinoRotulo = contextoRotulo;
                if (!destinoRotulo && contextoTipo === 'regional' && regionalCodigo) {
                    destinoRotulo = 'Regional ' + regionalCodigo.padStart(3, '0');
                }
                if (!destinoRotulo) {
                    destinoRotulo = postoPadrao && nomesPostos[postoPadrao] ? (postoPadrao + ' - ' + nomesPostos[postoPadrao]) : (postoPadrao ? ('Posto ' + postoPadrao) : 'Sem posto');
                }
                linhas.push({
                    row_key: item.row_key,
                    posto: posto,
                    posto_rotulo: destinoRotulo,
                    contexto_tipo: contextoTipo,
                    contexto_rotulo: contextoRotulo,
                    regional: item.regional || '',
                    regional_codigo: item.regional_codigo || '',
                    grupo_correios: item.grupo_correios || '',
                    grupos_correios: item.grupos_correios || [],
                    grupo_iipr: item.grupo_iipr || '',
                    lacre_iipr: item.lacre_iipr || '',
                    lacre_correios: item.lacre_correios || '',
                    etiqueta_correios: item.etiqueta_correios || '',
                    split_segmento: extrairSegmentoSplitGrupo(item.grupo_correios || item.grupo_iipr || ''),
                    lotes: item.lotes || [],
                    qtd_total: item.qtd_total || 0,
                    pendente_lacre: !!item.pendente_lacre
                });
            }

            linhas.sort(function(a, b) {
                var regA = parseInt(a.regional_codigo || 0, 10) || 0;
                var regB = parseInt(b.regional_codigo || 0, 10) || 0;
                var ordemA = regA === 0 ? 0 : (regA === 1 ? 1 : (regA === 999 ? 2 : 3));
                var ordemB = regB === 0 ? 0 : (regB === 1 ? 1 : (regB === 999 ? 2 : 3));
                if (ordemA !== ordemB) return ordemA - ordemB;
                if (ordemA === 3 && regA !== regB) return regA - regB;
                if (!!a.pendente_lacre !== !!b.pendente_lacre) return a.pendente_lacre ? 1 : -1;
                var grupoA = String(a.grupo_correios || a.grupo_iipr || a.row_key || '');
                var grupoB = String(b.grupo_correios || b.grupo_iipr || b.row_key || '');
                if (grupoA < grupoB) return -1;
                if (grupoA > grupoB) return 1;
                return 0;
            });
            return linhas;
        }

        function obterTituloPrimeiraColuna(secao) {
            return secao === 'REGIONAIS' ? 'Regionais' : 'Posto';
        }

        function montarCampoEdicao(classeExtra, rowKey, field, value, maxLength) {
            return '<input class="campo-impressao campo-editavel ' + escapeHtml(classeExtra || '') + '" type="text" value="' + escapeHtml(value || '') + '" data-row-key="' + escapeHtml(rowKey || '') + '" data-field="' + escapeHtml(field || '') + '" maxlength="' + escapeHtml(String(maxLength || 35)) + '">';
        }

        function montarTabela(secao, linhas) {
            var html = '';
            html += '<div class="secao">';
            html += '<table>';
            html += '<thead>';
            html += '<tr class="linha-secao">';
            html += '<th class="col-posto">' + escapeHtml(secao) + '</th>';
            html += '<th class="col-iipr">Lacre IIPR</th>';
            html += '<th class="col-correios">Lacre Correios</th>';
            html += '<th class="col-etiqueta">Etiqueta Correios</th>';
            html += '</tr>';
            html += '</thead><tbody>';

            for (var i = 0; i < linhas.length; i++) {
                var item = linhas[i];
                var anterior = i > 0 ? linhas[i - 1] : null;
                if (anterior && item.split_segmento !== anterior.split_segmento) {
                    html += '<tr class="linha-split"><td colspan="4">Split do próximo bloco</td></tr>';
                }
                html += '<tr data-row-key="' + escapeHtml(item.row_key) + '">';
                html += '<td><div class="destino">' + escapeHtml(item.posto_rotulo) + '</div></td>';
                html += '<td>' + montarCampoEdicao('campo-lacres-iipr', item.row_key, 'lacre_iipr', item.lacre_iipr || '', 80) + '</td>';
                html += '<td>' + montarCampoEdicao('', item.row_key, 'lacre_correios', item.lacre_correios || '', 80) + '</td>';
                html += '<td>' + montarCampoEdicao('campo-etiqueta', item.row_key, 'etiqueta_correios', item.etiqueta_correios || '', 35) + '</td>';
                html += '</tr>';
            }

            html += '</tbody></table></div>';
            return html;
        }

        function atualizarCabecalho(snapshot, linhas) {
            var datas = formatarListaDatasExibicao(snapshot && snapshot.datas_filtro ? snapshot.datas_filtro : []);
            var usuario = snapshot && snapshot.usuario ? snapshot.usuario : 'Equipe de Conferência';
            var numeroAtual = estadoOficio.salvoNumero || (snapshot && snapshot.oficio_numero ? parseInt(snapshot.oficio_numero, 10) || 0 : 0);
            var ultimoTexto = estadoOficio.ultimoConhecido > 0 ? String(estadoOficio.ultimoConhecido) : 'nenhum';
            var geradoEm = snapshot && snapshot.gerado_em ? formatarDataHoraExibicao(snapshot.gerado_em) : '-';

            statusLinhas.textContent = String(linhas.length || 0);
            statusDatas.textContent = datas;
            statusUsuario.textContent = usuario;
            statusAtualizacao.textContent = geradoEm;
            textoPeriodo.textContent = datas === '-' ? 'Aguardando datas da conferência' : datas;
            textoUsuario.textContent = usuario;
            metaUltimoOficio.textContent = 'Último ofício Correios: ' + ultimoTexto;
            if (assinaturaEsquerda) {
                assinaturaEsquerda.textContent = 'RESPONSÁVEL CELEPAR';
            }
            if (assinaturaData) {
                assinaturaData.textContent = 'Data: ' + geradoEm;
            }

            if (numeroAtual > 0) {
                numeroOficio.textContent = '#' + numeroAtual;
                if (numeroOficioBox) numeroOficioBox.textContent = 'Nº #' + numeroAtual;
                numeroRodape.textContent = 'Documento gravado';
                statusNumeroRotulo.textContent = 'Número gravado:';
                statusNumeroValor.textContent = '#' + String(numeroAtual);
            } else {
                numeroOficio.textContent = 'Prévia';
                if (numeroOficioBox) numeroOficioBox.textContent = 'Nº Prévia';
                numeroRodape.textContent = estadoOficio.ultimoConhecido > 0 ? ('Último existente: ' + estadoOficio.ultimoConhecido) : 'Grave para numerar';
                statusNumeroRotulo.textContent = 'Número:';
                statusNumeroValor.textContent = 'Prévia';
            }

            if (linhas.length) {
                textoRodapeLotes.textContent = linhas.length + ' linha(s) disponíveis no ofício. Você pode ajustar lacre IIPR, lacre Correios e etiqueta antes da gravação definitiva, se necessário.';
            } else {
                textoRodapeLotes.textContent = 'Nenhuma linha pronta do ofício foi gerada.';
            }
        }

        function formatarDataExibicao(valor) {
            if (!valor) {
                return '';
            }

            var texto = String(valor).trim();
            var match = texto.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (match) {
                return match[3] + '-' + match[2] + '-' + match[1];
            }

            match = texto.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);
            if (match) {
                return match[1] + '-' + match[2] + '-' + match[3];
            }

            return texto;
        }

        function formatarListaDatasExibicao(datas) {
            if (!datas || !datas.length) {
                return '-';
            }

            var lista = [];
            for (var i = 0; i < datas.length; i++) {
                lista.push(formatarDataExibicao(datas[i]));
            }
            return lista.join(', ');
        }

        function formatarDataHoraExibicao(valor) {
            if (!valor) {
                return '';
            }

            var texto = String(valor).trim();
            var match = texto.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (match) {
                return match[3] + '-' + match[2] + '-' + match[1] + ' ' + match[4] + ':' + match[5];
            }

            return formatarDataExibicao(texto);
        }

        function renderizar(snapshot) {
            if (!snapshot) {
                areaGrade.className = 'vazio';
                areaGrade.innerHTML = 'Aguardando dados da conferência.';
                atualizarCabecalho(null, []);
                return;
            }

            snapshot = garantirChavesResumo(snapshot, false);
            var linhas = montarLinhas(snapshot);
            atualizarCabecalho(snapshot, linhas);

            if (!linhas.length) {
                areaGrade.className = 'vazio';
                areaGrade.innerHTML = 'Nenhuma linha pronta do ofício foi gerada ainda.';
                return;
            }

            var grupos = {
                'POSTO 001': [],
                'CAPITAL': [],
                'METROPOLITANA': [],
                'CENTRAL IIPR': [],
                'REGIONAIS': []
            };
            for (var i = 0; i < linhas.length; i++) {
                grupos[nomeSecao(linhas[i])].push(linhas[i]);
            }

            var html = '';
            if (grupos['POSTO 001'].length) html += montarTabela('POSTO 001', grupos['POSTO 001']);
            if (grupos['CAPITAL'].length) html += montarTabela('CAPITAL', grupos['CAPITAL']);
            if (grupos['METROPOLITANA'].length) html += montarTabela('METROPOLITANA', grupos['METROPOLITANA']);
            if (grupos['CENTRAL IIPR'].length) html += montarTabela('CENTRAL IIPR', grupos['CENTRAL IIPR']);
            if (grupos['REGIONAIS'].length) html += montarTabela('REGIONAIS', grupos['REGIONAIS']);

            areaGrade.className = '';
            areaGrade.innerHTML = html;
        }

        function atualizarCampoResumo(rowKey, field, value) {
            var snapshot = lerSnapshot();
            if (!snapshot || !snapshot.resumo || !snapshot.resumo.length) return;
            snapshot = garantirChavesResumo(snapshot, false);
            for (var i = 0; i < snapshot.resumo.length; i++) {
                if (String(snapshot.resumo[i].row_key || '') === String(rowKey || '')) {
                    snapshot.resumo[i][field] = value;
                    salvarSnapshot(snapshot);
                    return;
                }
            }
        }

        function estaEditandoCampoResumo() {
            var ativo = document.activeElement;
            return !!(ativo && ativo.classList && ativo.classList.contains('campo-editavel'));
        }

        function renderizarQuandoPossivel(snapshot) {
            if (estaEditandoCampoResumo()) {
                return;
            }
            renderizar(snapshot);
        }

        function fecharModal() {
            modalGravacao.classList.remove('ativo');
            modalGravacao.setAttribute('aria-hidden', 'true');
        }

        function abrirModal() {
            var alvoSobrescrever = estadoOficio.salvoId || estadoOficio.ultimoConhecido || 0;
            var proximo = (estadoOficio.ultimoConhecido || 0) + 1;
            modalTextoBase.textContent = 'Escolha como o número do ofício Correios deve ser tratado para esta prévia.';
            textoSobrescrever.textContent = alvoSobrescrever > 0 ? ('Sobrescrever nº ' + alvoSobrescrever) : 'Sobrescrever último';
            detalheSobrescrever.textContent = alvoSobrescrever > 0 ? ('Regrava o ofício ' + alvoSobrescrever + ' com as linhas atuais da conferência.') : 'Não existe ofício anterior disponível; neste caso será criado um novo automaticamente.';
            textoCriarNovo.textContent = 'Criar novo nº ' + (proximo > 0 ? proximo : 1);
            detalheCriarNovo.textContent = 'Cria um novo ofício sem alterar os anteriores.';
            modalGravacao.classList.add('ativo');
            modalGravacao.setAttribute('aria-hidden', 'false');
        }

        function imprimirDocumento() {
            window.print();
        }

        function garantirResponsavelSnapshot(snapshot) {
            var nome = snapshot && snapshot.usuario ? String(snapshot.usuario).trim() : '';
            if (nome) {
                return nome;
            }
            nome = window.prompt('Informe o nome do responsável para gravar o ofício:', '');
            nome = nome ? String(nome).trim() : '';
            if (!nome) {
                return '';
            }
            snapshot.usuario = nome;
            salvarSnapshot(snapshot);
            renderizar(snapshot);
            return nome;
        }

        function gravarOficio(modo) {
            if (estadoOficio.salvando) return;
            var snapshot = lerSnapshot();
            if (!snapshot || !snapshot.resumo || !snapshot.resumo.length) {
                exibirAviso('Nao ha linhas prontas para gravar.', true);
                fecharModal();
                return;
            }

            snapshot = garantirChavesResumo(snapshot, false);
            var usuarioResponsavel = garantirResponsavelSnapshot(snapshot);
            if (!usuarioResponsavel) {
                exibirAviso('Informe o responsável antes de gravar o ofício.', true);
                fecharModal();
                return;
            }
            var formData = new FormData();
            formData.append('salvar_oficio_correios_preview_ajax', '1');
            formData.append('usuario', usuarioResponsavel);
            formData.append('modo_oficio', modo === 'novo' ? 'novo' : 'sobrescrever');
            formData.append('id_oficio_sobrescrever', String(estadoOficio.salvoId || estadoOficio.ultimoConhecido || 0));
            formData.append('datas_json', JSON.stringify(snapshot.datas_filtro || []));
            formData.append('datas_str', (snapshot.datas_filtro || []).join(','));
            formData.append('snapshot_oficio', JSON.stringify(snapshot));

            estadoOficio.salvando = true;
            exibirAviso('Gravando ofício Correios...', false);
            fecharModal();

            fetch('conferencia_pacotes.php', {
                method: 'POST',
                body: formData
            })
            .then(function(resp) { return resp.json(); })
            .then(function(json) {
                estadoOficio.salvando = false;
                if (!json || !json.success) {
                    exibirAviso(json && json.erro ? json.erro : 'Falha ao gravar o ofício.', true);
                    return;
                }

                estadoOficio.salvoId = parseInt(json.id_oficio || 0, 10) || 0;
                estadoOficio.salvoNumero = parseInt(json.numero_oficio || 0, 10) || 0;
                if (estadoOficio.salvoNumero > estadoOficio.ultimoConhecido) {
                    estadoOficio.ultimoConhecido = estadoOficio.salvoNumero;
                }

                snapshot.oficio_id_salvo = estadoOficio.salvoId;
                snapshot.oficio_numero = estadoOficio.salvoNumero;
                salvarSnapshot(snapshot);
                renderizar(snapshot);
                exibirAviso('Ofício ' + estadoOficio.salvoNumero + ' gravado com ' + (json.linhas_gravadas || 0) + ' linha(s) e ' + (json.lotes_gravados || 0) + ' lote(s).', false);
                window.setTimeout(function() {
                    imprimirDocumento();
                }, 120);
            })
            .catch(function() {
                estadoOficio.salvando = false;
                exibirAviso('Falha de comunicação ao gravar o ofício.', true);
            });
        }

        document.getElementById('btnImprimir').addEventListener('click', function() {
            exibirAviso('', false);
            imprimirDocumento();
        });

        document.getElementById('btnGravarImprimir').addEventListener('click', function() {
            exibirAviso('', false);
            abrirModal();
        });

        document.getElementById('btnCancelarModal').addEventListener('click', fecharModal);
        document.getElementById('btnSobrescrever').addEventListener('click', function() {
            gravarOficio('sobrescrever');
        });
        document.getElementById('btnCriarNovo').addEventListener('click', function() {
            gravarOficio('novo');
        });

        modalGravacao.addEventListener('click', function(event) {
            if (event.target === modalGravacao) {
                fecharModal();
            }
        });

        areaGrade.addEventListener('input', function(event) {
            var alvo = event.target;
            var field = alvo ? alvo.getAttribute('data-field') : '';
            if (!field) return;
            atualizarCampoResumo(alvo.getAttribute('data-row-key'), field, String(alvo.value || '').trim());
        });

        if (window.BroadcastChannel) {
            try {
                channel = new BroadcastChannel('conferencia_previa_malotes');
                channel.onmessage = function(event) {
                    var snapshot = event.data || null;
                    if (snapshot && estadoOficio.salvoNumero > 0 && !snapshot.oficio_numero) {
                        snapshot.oficio_numero = estadoOficio.salvoNumero;
                        snapshot.oficio_id_salvo = estadoOficio.salvoId;
                    }
                    renderizarQuandoPossivel(snapshot);
                };
            } catch (e) {
                channel = null;
            }
        }

        window.addEventListener('storage', function(event) {
            if (event.key === storageKey) {
                renderizarQuandoPossivel(lerSnapshot());
            }
        });

        areaGrade.addEventListener('blur', function(event) {
            var alvo = event.target;
            if (!alvo || !alvo.classList || !alvo.classList.contains('campo-editavel')) return;
            renderizar(lerSnapshot());
        }, true);

        renderizar(lerSnapshot());
        window.setInterval(function() {
            renderizarQuandoPossivel(lerSnapshot());
        }, 2000);
        sincronizarComEstadoRemoto();
        window.setInterval(sincronizarComEstadoRemoto, 1200);
    })();
    </script>
<?php include __DIR__ . '/processando_overlay.php'; ?>
<?php include __DIR__ . '/util_botoes_fixos.php'; ?>
</body>
</html>