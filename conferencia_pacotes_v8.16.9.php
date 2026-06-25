<?php
/* conferencia_pacotes.php — v0.9.25.3
 * VERSÃO LIMPA baseada na v8.16.6 funcional
 * Melhorias APENAS:
 * 1) Filtro por múltiplas datas (checkboxes)
 * 2) Agrupamento: Poupa Tempo → Regional 01 → Capital → Central IIPR → Demais
 * 3) Radio auto-save e sons mantidos
 */

// Inicializa as variáveis
$total_codigos = 0;
$regionais_data = array();
$datas_filtro = isset($_GET['datas']) && is_array($_GET['datas']) ? $_GET['datas'] : array();
$poupaTempoPostos = array();

// Conexão com o banco de dados
$host = getenv('DB_HOST') ?: '10.15.61.169';
$dbname = getenv('DB_NAME') ?: 'controle';
$user = getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat');
$pass = getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handler AJAX salvar
    if (isset($_POST['salvar_lote_ajax'])) {
        $lote = isset($_POST['lote']) ? trim($_POST['lote']) : '';
        if (!empty($lote)) {
            $sql = "INSERT INTO conferencia_pacotes (nlote, conf, usuario, lido_em) 
                    VALUES (?, 1, 'conferencia', NOW())
                    ON DUPLICATE KEY UPDATE conf=1, lido_em=NOW()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($lote));
            echo json_encode(array('status' => 'success'));
            exit;
        }
    }

    // Handler AJAX excluir
    if (isset($_POST['excluir_lote_ajax'])) {
        $lote = isset($_POST['lote']) ? trim($_POST['lote']) : '';
        if (!empty($lote)) {
            $sql = "DELETE FROM conferencia_pacotes WHERE nlote = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($lote));
            echo json_encode(array('status' => 'success'));
            exit;
        }
    }

    // Busca postos Poupa Tempo
    $sql = "SELECT LPAD(posto,3,'0') AS posto FROM ciRegionais WHERE LOWER(REPLACE(entrega,' ','')) LIKE '%poupatempo%'";
    $stmt = $pdo->query($sql);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $poupaTempoPostos[] = $r['posto'];
    }

    // Busca conferências já realizadas
    $conferencias = array();
    $stmt = $pdo->query("SELECT nlote FROM conferencia_pacotes WHERE conf=1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conferencias[$row['nlote']] = true;
    }

    // Busca últimas 5 datas disponíveis
    $sql = "SELECT DISTINCT DATE_FORMAT(dataCarga, '%d-%m-%Y') as data 
            FROM ciPostosCsv 
            WHERE dataCarga IS NOT NULL 
            ORDER BY dataCarga DESC 
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $datas_disponiveis = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $datas_disponiveis[] = $row['data'];
    }

    // Se não há filtro, usa a primeira data (mais recente)
    if (empty($datas_filtro) && !empty($datas_disponiveis)) {
        $datas_filtro[] = $datas_disponiveis[0];
    }

    // Busca dados dos postos
    if (!empty($datas_filtro)) {
        $datasSql = array();
        foreach ($datas_filtro as $data) {
            $partes = explode('-', $data);
            if (count($partes) == 3) {
                $datasSql[] = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
            }
        }
        
        if (!empty($datasSql)) {
            $placeholders = implode(',', array_fill(0, count($datasSql), '?'));
            $sql = "SELECT lote, posto, regional, quantidade, dataCarga 
                    FROM ciPostosCsv 
                    WHERE DATE(dataCarga) IN ($placeholders)
                    ORDER BY dataCarga DESC, regional, lote";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($datasSql);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lote = $row['lote'];
                $posto_str = str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
                $regional = (int)$row['regional'];
                $regional_str = str_pad($regional, 3, '0', STR_PAD_LEFT);
                $qtd = str_pad($row['quantidade'], 5, '0', STR_PAD_LEFT);
                $data = date('d-m-Y', strtotime($row['dataCarga']));
                
                $codigo = $lote . $regional_str . $posto_str . $qtd;
                $isPT = in_array($posto_str, $poupaTempoPostos) ? '1' : '0';
                $conf = isset($conferencias[$lote]) ? 1 : 0;
                
                if (!isset($regionais_data[$regional])) {
                    $regionais_data[$regional] = array();
                }
                
                $regionais_data[$regional][] = array(
                    'lote' => $lote,
                    'posto' => $posto_str,
                    'regional' => $regional_str,
                    'data' => $data,
                    'qtd' => ltrim($qtd, '0'),
                    'codigo' => $codigo,
                    'isPT' => $isPT,
                    'conf' => $conf
                );
                
                $total_codigos++;
            }
        }
    }
    
} catch (PDOException $e) {
    die("Erro de banco: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conferência de Pacotes v0.9.25.3</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { 
            color: #333; 
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin: 30px 0 15px;
            padding-left: 10px;
            border-left: 4px solid #007bff;
        }
        
        .radio-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .radio-box label {
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .radio-box input { 
            margin-right: 10px; 
            width: 18px; 
            height: 18px; 
            cursor: pointer; 
        }
        
        .filtro {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filtro h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .datas-checks {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .datas-checks label {
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .datas-checks label:hover { background: #f0f0f0; }
        .datas-checks input {
            margin-right: 6px;
            cursor: pointer;
        }
        
        .botoes button {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .botoes button:hover { transform: translateY(-2px); }
        .btn-filtrar {
            background: #007bff;
            color: white;
        }
        .btn-reset {
            background: #6c757d;
            color: white;
        }
        
        .info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .input-barcode {
            margin-bottom: 20px;
        }
        .input-barcode label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .input-barcode input {
            padding: 12px;
            width: 100%;
            max-width: 400px;
            font-size: 16px;
            border: 2px solid #007bff;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        thead {
            background: #343a40;
            color: white;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        tbody tr {
            cursor: pointer;
            transition: background 0.2s;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        tbody tr.confirmado {
            background: #d4edda !important;
        }
        
        .tag-pt {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Conferência de Pacotes v0.9.25.3</h1>

        <div class="radio-box">
            <label>
                <input type="radio" id="autoSalvar" checked>
                Auto-salvar conferências durante leitura
            </label>
        </div>

        <div class="filtro">
            <form method="GET">
                <h3>📅 Selecione as datas:</h3>
                <div class="datas-checks">
                    <?php foreach ($datas_disponiveis as $dt): ?>
                        <label>
                            <input type="checkbox" name="datas[]" value="<?php echo $dt; ?>" 
                                <?php echo in_array($dt, $datas_filtro) ? 'checked' : ''; ?>>
                            <?php echo $dt; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="botoes">
                    <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
                    <button type="button" class="btn-reset" onclick="window.location.href='?'">🔄 Limpar</button>
                </div>
            </form>
        </div>

        <div class="info">
            📦 Total: <?php echo $total_codigos; ?> pacotes
        </div>

        <div class="input-barcode">
            <label>📍 Código de barras:</label>
            <input type="text" id="barcode" maxlength="19" autofocus placeholder="Escaneie aqui...">
        </div>

        <div id="tabelas">
<?php
// Ordem: 1) Poupa Tempo, 2) Reg 1, 3) Capital (0), 4) Central (999), 5) Demais

// 1. POUPA TEMPO
$pt_todos = array();
foreach ($regionais_data as $postos) {
    foreach ($postos as $p) {
        if ($p['isPT'] == '1') $pt_todos[] = $p;
    }
}
if (!empty($pt_todos)) {
    $total = count($pt_todos);
    $conf_count = count(array_filter($pt_todos, function($x){ return $x['conf']; }));
    echo "<h2>🔴 Postos Poupa Tempo ($total pacotes / $conf_count conferidos)</h2>";
    echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th></tr></thead><tbody>';
    foreach ($pt_todos as $p) {
        $cls = $p['conf'] ? 'confirmado' : '';
        echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='1'>";
        echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
        echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td></tr>";
    }
    echo '</tbody></table>';
}

// 2. REGIONAL 1
if (isset($regionais_data[1])) {
    $r1 = array_filter($regionais_data[1], function($p){ return $p['isPT'] != '1'; });
    if (!empty($r1)) {
        $total = count($r1);
        $conf_count = count(array_filter($r1, function($x){ return $x['conf']; }));
        echo "<h2>001 - Regional 001 ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th></tr></thead><tbody>';
        foreach ($r1 as $p) {
            $cls = $p['conf'] ? 'confirmado' : '';
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td></tr>";
        }
        echo '</tbody></table>';
    }
}

// 3. CAPITAL (0)
if (isset($regionais_data[0])) {
    $cap = array_filter($regionais_data[0], function($p){ return $p['isPT'] != '1'; });
    if (!empty($cap)) {
        $total = count($cap);
        $conf_count = count(array_filter($cap, function($x){ return $x['conf']; }));
        echo "<h2>000 - Capital ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th></tr></thead><tbody>';
        foreach ($cap as $p) {
            $cls = $p['conf'] ? 'confirmado' : '';
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td></tr>";
        }
        echo '</tbody></table>';
    }
}

// 4. CENTRAL IIPR (999)
if (isset($regionais_data[999])) {
    $total = count($regionais_data[999]);
    $conf_count = count(array_filter($regionais_data[999], function($x){ return $x['conf']; }));
    echo "<h2>999 - Central IIPR ($total pacotes / $conf_count conferidos)</h2>";
    echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th></tr></thead><tbody>';
    foreach ($regionais_data[999] as $p) {
        $cls = $p['conf'] ? 'confirmado' : '';
        echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
        echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
        echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td></tr>";
    }
    echo '</tbody></table>';
}

// 5. DEMAIS REGIONAIS
foreach (array_keys($regionais_data) as $reg) {
    if ($reg == 0 || $reg == 1 || $reg == 999) continue;
    $demais = array_filter($regionais_data[$reg], function($p){ return $p['isPT'] != '1'; });
    if (!empty($demais)) {
        $reg_str = str_pad($reg, 3, '0', STR_PAD_LEFT);
        $total = count($demais);
        $conf_count = count(array_filter($demais, function($x){ return $x['conf']; }));
        echo "<h2>$reg_str - Regional $reg ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th></tr></thead><tbody>';
        foreach ($demais as $p) {
            $cls = $p['conf'] ? 'confirmado' : '';
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td></tr>";
        }
        echo '</tbody></table>';
    }
}
?>
        </div>
    </div>

    <audio id="beep" src="assets/audio/beep.mp3" preload="auto"></audio>
    <audio id="concluido" src="assets/audio/concluido.mp3" preload="auto"></audio>
    <audio id="jaconf" src="assets/audio/pacotejaconferido.mp3" preload="auto"></audio>
    <audio id="ptsom" src="assets/audio/posto_poupatempo.mp3" preload="auto"></audio>

    <script>
    (function() {
        const auto = document.getElementById('autoSalvar');
        const input = document.getElementById('barcode');
        const beep = document.getElementById('beep');
        const concluido = document.getElementById('concluido');
        const jaconf = document.getElementById('jaconf');
        const ptsom = document.getElementById('ptsom');

        input.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length !== 19) return;

            const tr = document.querySelector(`tr[data-codigo="${val}"]`);
            if (!tr) {
                this.value = '';
                return;
            }

            const lote = tr.getAttribute('data-lote');
            const isPT = tr.getAttribute('data-pt') === '1';
            const jaSalvo = tr.classList.contains('confirmado');

            if (jaSalvo) {
                jaconf.currentTime = 0;
                jaconf.play();
            } else if (auto.checked) {
                tr.classList.add('confirmado');
                
                if (isPT) {
                    ptsom.currentTime = 0;
                    ptsom.play();
                } else {
                    beep.currentTime = 0;
                    beep.play();
                }

                tr.scrollIntoView({ behavior: 'smooth', block: 'center' });

                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'salvar_lote_ajax=1&lote=' + encodeURIComponent(lote)
                });

                setTimeout(() => {
                    const table = tr.closest('table');
                    const all = table.querySelectorAll('tbody tr');
                    const conf = table.querySelectorAll('tbody tr.confirmado');
                    if (all.length === conf.length) {
                        concluido.currentTime = 0;
                        concluido.play();
                    }
                }, 100);
            }

            this.value = '';
            this.focus();
        });

        document.querySelectorAll('tbody tr').forEach(tr => {
            tr.addEventListener('click', function() {
                const lote = this.getAttribute('data-lote');
                const isConf = this.classList.contains('confirmado');

                if (!isConf) {
                    this.classList.add('confirmado');
                    if (auto.checked) {
                        fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'salvar_lote_ajax=1&lote=' + encodeURIComponent(lote)
                        });
                    }
                } else {
                    this.classList.remove('confirmado');
                    if (auto.checked) {
                        fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'excluir_lote_ajax=1&lote=' + encodeURIComponent(lote)
                        });
                    }
                }
            });
        });
    })();
    </script>
</body>
</html>
