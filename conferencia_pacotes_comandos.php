<?php
$comandos = array(
    array(
        'titulo' => 'Lacre IIPR',
        'subtitulo' => 'Ativa a leitura do lacre IIPR',
        'codigo' => '990000000000000000001',
        'descricao' => 'Leia este código e depois leia o lacre IIPR que será aplicado na regional atual.',
        'classe' => 'iipr'
    ),
    array(
        'titulo' => 'Lacre Correios',
        'subtitulo' => 'Ativa a leitura do lacre Correios',
        'codigo' => '990000000000000000002',
        'descricao' => 'Leia este código e depois leia o lacre Correios do malote maior da regional.',
        'classe' => 'correios'
    ),
    array(
        'titulo' => 'Display Correios',
        'subtitulo' => 'Ativa a leitura da etiqueta Correios',
        'codigo' => '990000000000000000003',
        'descricao' => 'Leia este código e depois leia a etiqueta de 35 dígitos do malote Correios aberto.',
        'classe' => 'display'
    )
);

$cancelar = array(
    'titulo' => 'Cancelar comando',
    'codigo' => '990000000000000000009',
    'descricao' => 'Opcional. Use apenas se precisar limpar o modo armado atual.'
);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandos por Código de Barras v0.9.25.23</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            background: #f2f2f2;
            color: #1f1f1f;
        }
        .pagina {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 18px 40px;
        }
        .topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .topo h1 {
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
        }
        .topo p {
            margin: 8px 0 0;
            max-width: 760px;
            line-height: 1.6;
            color: #505050;
        }
        .btn-imprimir {
            border: 0;
            border-radius: 6px;
            background: #169b41;
            color: #fff;
            font-weight: 700;
            padding: 12px 18px;
            cursor: pointer;
        }
        .grade {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .card {
            background: #fff;
            border: 1px solid #151515;
            border-radius: 0;
            padding: 14px 14px 18px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
            min-height: 720px;
            display: flex;
            flex-direction: column;
        }
        .card h2 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            text-align: center;
        }
        .subtitulo-card {
            margin: 4px 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            text-align: center;
            color: #5b5b5b;
            letter-spacing: 0.04em;
        }
        .descricao {
            min-height: 48px;
            margin: 0 0 10px;
            color: #4a4a4a;
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
        }
        .codigo {
            margin-top: 12px;
            font-size: 20px;
            letter-spacing: 0.12em;
            color: #111;
            word-break: break-all;
            text-align: center;
            font-family: "Courier New", monospace;
            font-weight: bold;
        }
        .barcode-wrap {
            background: #fff;
            border: 1px solid #000;
            padding: 14px 8px 8px;
            min-height: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
        }
        .barcode-wrap svg {
            display: block;
            width: 160px;
            height: 460px;
        }
        .card.iipr { background: linear-gradient(180deg, #ffffff 0%, #f8fff9 100%); }
        .card.correios { background: linear-gradient(180deg, #ffffff 0%, #fffdfa 100%); }
        .card.display { background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%); }
        .cancelar {
            margin-top: 20px;
            border: 1px solid #222;
            background: #fff;
            padding: 14px;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 16px;
            align-items: center;
        }
        .cancelar-titulo {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cancelar-texto {
            color: #4f4f4f;
            line-height: 1.5;
        }
        .cancelar-codigo {
            font-family: "Courier New", monospace;
            font-weight: bold;
            font-size: 18px;
            margin-top: 6px;
        }
        .rodape {
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid #bfbfbf;
            background: #f9f9f9;
            color: #303030;
            line-height: 1.6;
        }
        @media (max-width: 760px) {
            .grade {
                grid-template-columns: 1fr;
            }
            .card {
                min-height: auto;
            }
            .barcode-wrap {
                min-height: 360px;
            }
            .barcode-wrap svg {
                height: 320px;
            }
            .cancelar {
                grid-template-columns: 1fr;
            }
        }
        @media print {
            body {
                background: #fff;
            }
            .pagina {
                max-width: none;
                padding: 0;
            }
            .btn-imprimir {
                display: none;
            }
            .card {
                box-shadow: none;
                break-inside: avoid;
            }
            .barcode-wrap {
                min-height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="pagina">
        <div class="topo">
            <div>
                <h1>Folha de comandos</h1>
                <p>Imprima e deixe estes três comandos na bancada. A leitura do código arma a ação na tela principal. A leitura seguinte informa o lacre IIPR, o lacre Correios ou a etiqueta de display da regional atual.</p>
            </div>
            <button type="button" class="btn-imprimir" onclick="window.print()">Imprimir folha</button>
        </div>

        <div class="grade">
            <?php foreach ($comandos as $item): ?>
            <section class="card <?php echo htmlspecialchars($item['classe'], ENT_QUOTES, 'UTF-8'); ?>">
                <h2><?php echo htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="subtitulo-card"><?php echo htmlspecialchars($item['subtitulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                <p class="descricao"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="barcode-wrap">
                    <svg class="barcode" data-code="<?php echo htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="Código <?php echo htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8'); ?>"></svg>
                </div>
                <div class="codigo"><?php echo htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8'); ?></div>
            </section>
            <?php endforeach; ?>
        </div>

        <section class="cancelar">
            <div>
                <div class="cancelar-titulo"><?php echo htmlspecialchars($cancelar['titulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="cancelar-codigo"><?php echo htmlspecialchars($cancelar['codigo'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="cancelar-texto"><?php echo htmlspecialchars($cancelar['descricao'], ENT_QUOTES, 'UTF-8'); ?></div>
        </section>

        <div class="rodape">
            Sequência de uso: 1) leia o comando vertical, 2) leia o lacre ou a etiqueta correspondente, 3) confira a confirmação sonora e visual na conferência. Os três códigos principais substituem os botões de ação do controle remoto na bancada.
        </div>
    </div>

    <script>
    (function() {
        var padroes = {
            '0': 'nnnwwnwnn',
            '1': 'wnnwnnnnw',
            '2': 'nnwwnnnnw',
            '3': 'wnwwnnnnn',
            '4': 'nnnwwnnnw',
            '5': 'wnnwwnnnn',
            '6': 'nnwwwnnnn',
            '7': 'nnnwnnwnw',
            '8': 'wnnwnnwnn',
            '9': 'nnwwnnwnn',
            '*': 'nwnnwnwnn'
        };

        function largura(simbolo) {
            return simbolo === 'w' ? 5 : 2;
        }

        function desenhar(svg, valor) {
            var ns = 'http://www.w3.org/2000/svg';
            var texto = '*' + String(valor || '') + '*';
            var cursor = 12;
            var altura = 78;
            while (svg.firstChild) svg.removeChild(svg.firstChild);

            for (var i = 0; i < texto.length; i++) {
                var ch = texto.charAt(i);
                var padrao = padroes[ch];
                if (!padrao) continue;
                for (var j = 0; j < padrao.length; j++) {
                    var w = largura(padrao.charAt(j));
                    if (j % 2 === 0) {
                        var rect = document.createElementNS(ns, 'rect');
                        rect.setAttribute('x', cursor);
                        rect.setAttribute('y', 4);
                        rect.setAttribute('width', w);
                        rect.setAttribute('height', altura);
                        rect.setAttribute('fill', '#111');
                        svg.appendChild(rect);
                    }
                    cursor += w;
                }
                cursor += 2;
            }

            var grupo = document.createElementNS(ns, 'g');
            while (svg.firstChild) {
                grupo.appendChild(svg.firstChild);
            }
            grupo.setAttribute('transform', 'translate(0 ' + (cursor + 12) + ') rotate(-90)');
            svg.appendChild(grupo);

            svg.setAttribute('viewBox', '0 0 90 ' + (cursor + 12));
            svg.setAttribute('preserveAspectRatio', 'none');
        }

        var barcodes = document.querySelectorAll('.barcode');
        for (var i = 0; i < barcodes.length; i++) {
            desenhar(barcodes[i], barcodes[i].getAttribute('data-code') || '');
        }
    })();
    </script>
</body>
</html>