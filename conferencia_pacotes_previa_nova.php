<?php
/**
 * Prévia Simplificada de Lacres - v0.9.25.17
 * 
 * Réplica minimalista de lacres_novo.php mostrando APENAS a tabela de lacres
 * com polling automático para sincronização em tempo real.
 * 
 * Entrada: celular (conferencia_pacotes_controle.php)
 * Saída: prévia + imprime como ofício
 */
session_start();

header('Content-Type: text/html; charset=UTF-8');

$pdo_controle = null;
try {
    $pdo_controle = new PDO((getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8' : 'mysql:host=' . (getenv('DB_HOST') ?: (getenv('DB_HOST') ?: '10.15.61.169')) . ';dbname=' . (getenv('DB_NAME') ?: (getenv('DB_NAME') ?: 'controle')) . ';charset=utf8'), 'root', 'vazio');
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erro ao conectar: ' . htmlspecialchars($e->getMessage()));
}

// Obter o último ofício ativo (Correios)
$ultimoOficioId = 0;
if (isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0) {
    $ultimoOficioId = (int)$_SESSION['id_despacho_correios'];
} else {
    $stUltimoOficio = $pdo_controle->prepare("
        SELECT id FROM ciDespachos 
        WHERE grupo = 'CORREIOS' 
        ORDER BY id DESC LIMIT 1
    ");
    $stUltimoOficio->execute();
    $ultimoOficioRow = $stUltimoOficio->fetch(PDO::FETCH_ASSOC);
    if ($ultimoOficioRow) {
        $ultimoOficioId = (int)$ultimoOficioRow['id'];
    }
}

// Buscar postos agrupados por regional
$postosPorRegional = array(
    'CAPITAL' => array(),
    'CENTRAL IIPR' => array(),
    'REGIONAIS' => array()
);

try {
    $sqlPostos = "SELECT 
        LPAD(CAST(p.posto AS UNSIGNED), 3, '0') AS posto,
        p.nome AS nome_postal,
        CAST(p.regional AS UNSIGNED) AS regional
    FROM ciPostosCsv p
    WHERE p.status = 'ativo'
    ORDER BY CAST(p.regional AS UNSIGNED), CAST(p.posto AS UNSIGNED)";
    
    $stPostos = $pdo_controle->query($sqlPostos);
    while ($row = $stPostos->fetch(PDO::FETCH_ASSOC)) {
        $post = $row['posto'];
        $reg = (int)$row['regional'];
        $nome = $row['nome_postal'];
        
        $item = array(
            'codigo' => $post,
            'nome' => $nome,
            'regional' => $reg,
            'lacre_iipr' => '',
            'lacre_correios' => '',
            'etiqueta_correios' => ''
        );
        
        if ($reg === 0) {
            $postosPorRegional['CAPITAL'][] = $item;
        } elseif ($reg === 999) {
            $postosPorRegional['CENTRAL IIPR'][] = $item;
        } else {
            if (!isset($postosPorRegional['REGIONAIS'][$reg])) {
                $postosPorRegional['REGIONAIS'][$reg] = array();
            }
            $postosPorRegional['REGIONAIS'][$reg][] = $item;
        }
    }
    
    // Se há ofício ativo, carregar lacres do BD
    if ($ultimoOficioId > 0) {
        $stLacres = $pdo_controle->prepare("
            SELECT posto, etiquetaiipr, etiquetacorreios, etiqueta_correios
            FROM ciDespachoLotes
            WHERE id_despacho = ?
        ");
        $stLacres->execute(array($ultimoOficioId));
        $lacresMapping = array();
        
        while ($rLacres = $stLacres->fetch(PDO::FETCH_ASSOC)) {
            $postoKey = str_pad((string)$rLacres['posto'], 3, '0', STR_PAD_LEFT);
            $lacresMapping[$postoKey] = array(
                'iipr' => (int)$rLacres['etiquetaiipr'],
                'correios' => (int)$rLacres['etiquetacorreios'],
                'etiqueta' => $rLacres['etiqueta_correios']
            );
        }
        
        // Aplicar ao mapa
        foreach ($postosPorRegional as $grupo => &$itens) {
            if (is_array($itens) && $grupo !== 'REGIONAIS') {
                foreach ($itens as &$item) {
                    if (isset($lacresMapping[$item['codigo']])) {
                        $item['lacre_iipr'] = $lacresMapping[$item['codigo']]['iipr'] ?: '';
                        $item['lacre_correios'] = $lacresMapping[$item['codigo']]['correios'] ?: '';
                        $item['etiqueta_correios'] = $lacresMapping[$item['codigo']]['etiqueta'];
                    }
                }
                unset($item);
            }
        }
        unset($itens);
        
        // Regionais (agrupadas por número)
        foreach ($postosPorRegional['REGIONAIS'] as $regNum => &$itensReg) {
            foreach ($itensReg as &$item) {
                if (isset($lacresMapping[$item['codigo']])) {
                    $item['lacre_iipr'] = $lacresMapping[$item['codigo']]['iipr'] ?: '';
                    $item['lacre_correios'] = $lacresMapping[$item['codigo']]['correios'] ?: '';
                    $item['etiqueta_correios'] = $lacresMapping[$item['codigo']]['etiqueta'];
                }
            }
            unset($item);
        }
        unset($itensReg);
    }
} catch (Exception $e) {
    // Silenciar erros - continuar com postos vazios
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévia de Lacres - v0.9.25.17</title>
    <style>
        :root {
            --bg: #f9fbfe;
            --card: #ffffff;
            --line: #d9e5f2;
            --text: #17324d;
            --sub: #5b7188;
            --border: #cbd5e1;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Georgia, "Times New Roman", serif;
            background: var(--bg);
            color: var(--text);
            margin: 20px;
            padding: 0;
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text);
        }
        .sub-title {
            font-size: 12px;
            color: var(--sub);
            margin-bottom: 20px;
        }
        .secao {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .secao-titulo {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background: var(--text);
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        thead {
            background: #f0f4f8;
            border-bottom: 2px solid var(--border);
        }
        th {
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            color: var(--text);
        }
        td {
            padding: 6px 4px;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        td:nth-child(1) { text-align: left; }
        td:nth-child(2) { text-align: left; }
        tr:hover { background: #f9fbfe; }
        input {
            width: 85px;
            padding: 4px;
            border: 1px solid var(--border);
            border-radius: 3px;
            text-align: center;
            font-family: monospace;
            font-size: 10px;
        }
        input:focus {
            outline: none;
            border-color: #0f766e;
            background: #f0fdf4;
        }
        .etiqueta-input {
            width: 150px;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .wrap { border: none; box-shadow: none; page-break-inside: avoid; }
            input { border: none; background: transparent; }
            input:focus { background: transparent; }
            .sub-title { display: none; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Prévia de Lacres - Ofício Correios</h1>
    <div class="sub-title">
        v0.9.25.17 • ID Ofício: <?php echo $ultimoOficioId > 0 ? $ultimoOficioId : 'Nenhum'; ?> 
        • Data: <?php echo date('d/m/Y H:i'); ?>
    </div>

    <?php
    // CAPITAL
    if (!empty($postosPorRegional['CAPITAL'])):
    ?>
    <div class="secao">
        <div class="secao-titulo">CAPITAL - <?php echo count($postosPorRegional['CAPITAL']); ?> postos</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome Postal</th>
                    <th>Lacre IIPR</th>
                    <th>Lacre Correios</th>
                    <th>Etiqueta Correios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($postosPorRegional['CAPITAL'] as $item): ?>
                <tr data-posto="<?php echo $item['codigo']; ?>">
                    <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                    <td><?php echo htmlspecialchars(substr($item['nome'], 0, 40)); ?></td>
                    <td><input type="text" class="input-iipr" value="<?php echo htmlspecialchars($item['lacre_iipr']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-correios" value="<?php echo htmlspecialchars($item['lacre_correios']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-etiqueta etiqueta-input" value="<?php echo htmlspecialchars($item['etiqueta_correios']); ?>" maxlength="35"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
    // CENTRAL IIPR
    if (!empty($postosPorRegional['CENTRAL IIPR'])):
    ?>
    <div class="secao">
        <div class="secao-titulo">CENTRAL IIPR - <?php echo count($postosPorRegional['CENTRAL IIPR']); ?> postos</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome Postal</th>
                    <th>Lacre IIPR</th>
                    <th>Lacre Correios</th>
                    <th>Etiqueta Correios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($postosPorRegional['CENTRAL IIPR'] as $item): ?>
                <tr data-posto="<?php echo $item['codigo']; ?>">
                    <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                    <td><?php echo htmlspecialchars(substr($item['nome'], 0, 40)); ?></td>
                    <td><input type="text" class="input-iipr" value="<?php echo htmlspecialchars($item['lacre_iipr']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-correios" value="<?php echo htmlspecialchars($item['lacre_correios']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-etiqueta etiqueta-input" value="<?php echo htmlspecialchars($item['etiqueta_correios']); ?>" maxlength="35"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
    // REGIONAIS
    if (!empty($postosPorRegional['REGIONAIS'])):
        ksort($postosPorRegional['REGIONAIS']);
        foreach ($postosPorRegional['REGIONAIS'] as $regNum => $itens):
    ?>
    <div class="secao">
        <div class="secao-titulo">REGIONAL <?php echo str_pad($regNum, 3, '0', STR_PAD_LEFT); ?> - <?php echo count($itens); ?> postos</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome Postal</th>
                    <th>Lacre IIPR</th>
                    <th>Lacre Correios</th>
                    <th>Etiqueta Correios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                <tr data-posto="<?php echo $item['codigo']; ?>">
                    <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                    <td><?php echo htmlspecialchars(substr($item['nome'], 0, 40)); ?></td>
                    <td><input type="text" class="input-iipr" value="<?php echo htmlspecialchars($item['lacre_iipr']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-correios" value="<?php echo htmlspecialchars($item['lacre_correios']); ?>" maxlength="6"></td>
                    <td><input type="text" class="input-etiqueta etiqueta-input" value="<?php echo htmlspecialchars($item['etiqueta_correios']); ?>" maxlength="35"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; endif; ?>

</div>

<script>
// v0.9.25.14+: Polling automático de sincronização
(function() {
    var lastHash = '';
    var timerId = null;
    var inFlight = false;

    function capturaEstadoAtual() {
        var estado = {};
        var rows = document.querySelectorAll('tr[data-posto]');
        for (var i = 0; i < rows.length; i++) {
            var tr = rows[i];
            var posto = tr.getAttribute('data-posto');
            var iipr = tr.querySelector('.input-iipr').value.trim();
            var correios = tr.querySelector('.input-correios').value.trim();
            var etiqueta = tr.querySelector('.input-etiqueta').value.trim();
            estado[posto] = iipr + '|' + correios + '|' + etiqueta;
        }
        return estado;
    }

    function sincronizarComBD() {
        if (inFlight) {
            timerId = setTimeout(sincronizarComBD, 3000);
            return;
        }

        inFlight = true;
        fetch('conferencia_pacotes_previa_sync.php?ajax=1&ts=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || !data.sucesso) {
                    inFlight = false;
                    timerId = setTimeout(sincronizarComBD, 3000);
                    return;
                }

                if (data.hash && data.hash === lastHash) {
                    inFlight = false;
                    timerId = setTimeout(sincronizarComBD, 3000);
                    return;
                }

                lastHash = data.hash || '';
                var lacresPorPosto = data.lacres_por_posto || {};
                
                var rows = document.querySelectorAll('tr[data-posto]');
                for (var i = 0; i < rows.length; i++) {
                    var tr = rows[i];
                    var posto = tr.getAttribute('data-posto');
                    if (!lacresPorPosto[posto]) continue;

                    var item = lacresPorPosto[posto];
                    var inpIipr = tr.querySelector('.input-iipr');
                    var inpCorreios = tr.querySelector('.input-correios');
                    var inpEtiqueta = tr.querySelector('.input-etiqueta');

                    if (inpIipr && item.lacre_iipr && inpIipr.value === '') {
                        inpIipr.value = item.lacre_iipr;
                    }
                    if (inpCorreios && item.lacre_correios && inpCorreios.value === '') {
                        inpCorreios.value = item.lacre_correios;
                    }
                    if (inpEtiqueta && item.etiqueta_correios && inpEtiqueta.value === '') {
                        inpEtiqueta.value = item.etiqueta_correios;
                    }
                }

                inFlight = false;
                timerId = setTimeout(sincronizarComBD, 3000);
            })
            .catch(function() {
                inFlight = false;
                timerId = setTimeout(sincronizarComBD, 5000);
            });
    }

    sincronizarComBD();
})();
</script>
</body>
</html>
