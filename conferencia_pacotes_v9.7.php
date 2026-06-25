<?php
/* conferencia_pacotes.php — v9.7
 * CORREÇÕES DA v9.6:
 * 1. ✅ Fundo verde aplicado corretamente ao carregar para lotes já conferidos
 * 2. ✅ Coluna mostra APENAS data/hora (lido_em), sem nome de usuário
 * 3. ✅ Divisão visual muito mais clara entre POUPA TEMPO e CORREIOS
 * 4. ✅ Som de conclusão corrigido para funcionar com lotes únicos
 * 5. ✅ Simplificado display da coluna "Conferido em"
 */

// Inicializa as variáveis
$total_codigos = 0;
$regionais_data = array();
$datas_filtro = isset($_GET['datas']) && is_array($_GET['datas']) ? $_GET['datas'] : array();
$data_inicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';
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
                    VALUES (?, 's', 'conferencia', NOW())
                    ON DUPLICATE KEY UPDATE conf='s', usuario='conferencia', lido_em=NOW()";
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

    // Busca conferências já realizadas com lido_em (TODAS, sem filtro de data)
    $conferencias = array();
    $stmt = $pdo->query("SELECT nlote, DATE_FORMAT(lido_em, '%d/%m/%Y %H:%i:%s') as lido_em_fmt 
                         FROM conferencia_pacotes 
                         WHERE conf='s'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conferencias[$row['nlote']] = $row['lido_em_fmt'];
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

    // Define quais datas buscar
    $datasSql = array();
    
    // Prioridade 1: Intervalo customizado
    if (!empty($data_inicio) && !empty($data_fim)) {
        // Converte dd-mm-yyyy para yyyy-mm-dd
        $partes_inicio = explode('-', $data_inicio);
        $partes_fim = explode('-', $data_fim);
        if (count($partes_inicio) == 3 && count($partes_fim) == 3) {
            $sql_inicio = $partes_inicio[2] . '-' . $partes_inicio[1] . '-' . $partes_inicio[0];
            $sql_fim = $partes_fim[2] . '-' . $partes_fim[1] . '-' . $partes_fim[0];
            
            $sql = "SELECT lote, posto, regional, quantidade, dataCarga 
                    FROM ciPostosCsv 
                    WHERE DATE(dataCarga) BETWEEN ? AND ?
                    ORDER BY dataCarga DESC, regional, lote";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($sql_inicio, $sql_fim));
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lote = $row['lote'];
                $posto_str = str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
                $regional = (int)$row['regional'];
                $regional_str = str_pad($regional, 3, '0', STR_PAD_LEFT);
                $qtd = str_pad($row['quantidade'], 5, '0', STR_PAD_LEFT);
                $data = date('d-m-Y', strtotime($row['dataCarga']));
                
                $codigo = $lote . $regional_str . $posto_str . $qtd;
                $isPT = in_array($posto_str, $poupaTempoPostos) ? '1' : '0';
                
                // Busca informação de conferência (de TODAS as datas)
                $lido_em = isset($conferencias[$lote]) ? $conferencias[$lote] : '';
                
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
                    'lido_em' => $lido_em
                );
                
                $total_codigos++;
            }
        }
    }
    // Prioridade 2: Datas selecionadas
    elseif (!empty($datas_filtro)) {
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
                
                // Busca informação de conferência (de TODAS as datas)
                $lido_em = isset($conferencias[$lote]) ? $conferencias[$lote] : '';
                
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
                    'lido_em' => $lido_em
                );
                
                $total_codigos++;
            }
        }
    }
    // Prioridade 3: Data mais recente (padrão)
    else {
        if (!empty($datas_disponiveis)) {
            $datas_filtro[] = $datas_disponiveis[0];
            $partes = explode('-', $datas_disponiveis[0]);
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
                
                // Busca informação de conferência (de TODAS as datas)
                $lido_em = isset($conferencias[$lote]) ? $conferencias[$lote] : '';
                
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
                    'lido_em' => $lido_em
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
        .container { max-width: 1600px; margin: 0 auto; }
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
        
        /* ========== DESTAQUE MÁXIMO PARA POUPA TEMPO ========== */
        .secao-poupatempo {
            margin: 40px 0;
            padding: 25px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4);
            border: 3px solid #c0392b;
        }
        .secao-poupatempo h2 {
            color: white;
            font-size: 28px;
            font-weight: 900;
            margin: 0 0 20px 0;
            padding: 0;
            border: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .secao-poupatempo .info-secao {
            text-align: center;
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        /* ========== DESTAQUE MÁXIMO PARA CORREIOS ========== */
        .secao-correios {
            margin: 50px 0 40px;
            padding: 25px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
            border: 3px solid #2980b9;
        }
        .secao-correios h2 {
            color: white;
            font-size: 28px;
            font-weight: 900;
            margin: 0 0 10px 0;
            padding: 0;
            border: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .secao-correios .info-secao {
            text-align: center;
            color: rgba(255,255,255,0.95);
            font-size: 15px;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
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
        
        .filtro-customizado {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        .filtro-customizado h4 {
            margin-bottom: 12px;
            color: #555;
            font-size: 16px;
        }
        .intervalo-datas {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .intervalo-datas label {
            font-weight: 600;
            color: #555;
        }
        .intervalo-datas input[type="text"] {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 130px;
        }
        .intervalo-datas input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
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
        
        .lido-em {
            font-size: 13px;
            color: #28a745;
            font-weight: 600;
        }
        
        .nao-lido {
            font-size: 12px;
            color: #999;
            font-style: italic;
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
                <h3>📅 Selecione as datas (últimas 5):</h3>
                <div class="datas-checks">
                    <?php foreach ($datas_disponiveis as $dt): ?>
                        <label>
                            <input type="checkbox" name="datas[]" value="<?php echo $dt; ?>" 
                                <?php echo in_array($dt, $datas_filtro) ? 'checked' : ''; ?>>
                            <?php echo $dt; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="filtro-customizado">
                    <h4>🔎 Ou busque por intervalo de datas:</h4>
                    <div class="intervalo-datas">
                        <label>De:</label>
                        <input type="text" name="data_inicio" placeholder="dd-mm-aaaa" 
                               value="<?php echo htmlspecialchars($data_inicio); ?>" 
                               maxlength="10">
                        <label>Até:</label>
                        <input type="text" name="data_fim" placeholder="dd-mm-aaaa" 
                               value="<?php echo htmlspecialchars($data_fim); ?>" 
                               maxlength="10">
                    </div>
                </div>
                
                <div class="botoes" style="margin-top: 20px;">
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
// Ordem: 1) Poupa Tempo, 2) DIVISOR, 3) Reg 1, 4) Capital (0), 5) Central (999), 6) Demais

$primeira_secao_correios = true; // flag para imprimir divisor apenas uma vez

// 1. POUPA TEMPO - COM SEÇÃO DESTACADA
$pt_todos = array();
foreach ($regionais_data as $postos) {
    foreach ($postos as $p) {
        if ($p['isPT'] == '1') $pt_todos[] = $p;
    }
}
if (!empty($pt_todos)) {
    $total = count($pt_todos);
    $conf_count = count(array_filter($pt_todos, function($x){ return !empty($x['lido_em']); }));
    
    echo '<div class="secao-poupatempo">';
    echo '<h2>🔴 POUPA TEMPO</h2>';
    echo "<div class='info-secao'>$total pacotes / $conf_count conferidos</div>";
    echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th><th>Conferido em</th></tr></thead><tbody>';
    foreach ($pt_todos as $p) {
        $cls = !empty($p['lido_em']) ? 'confirmado' : '';
        $lido_display = !empty($p['lido_em']) ? "<span class='lido-em'>{$p['lido_em']}</span>" : "<span class='nao-lido'>Não conferido</span>";
        echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='1'>";
        echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
        echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td><td>{$lido_display}</td></tr>";
    }
    echo '</tbody></table>';
    echo '</div>';
}

// 2. REGIONAL 1
if (isset($regionais_data[1])) {
    $r1 = array_filter($regionais_data[1], function($p){ return $p['isPT'] != '1'; });
    if (!empty($r1)) {
        if ($primeira_secao_correios) {
            echo '<div class="secao-correios">';
            echo '<h2>📮 POSTOS DOS CORREIOS</h2>';
            echo '<div class="info-secao">Postos regionais e capital (não Poupa Tempo)</div>';
            echo '</div>';
            $primeira_secao_correios = false;
        }
        $total = count($r1);
        $conf_count = count(array_filter($r1, function($x){ return !empty($x['lido_em']); }));
        echo "<h2>001 - Regional 001 ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th><th>Conferido em</th></tr></thead><tbody>';
        foreach ($r1 as $p) {
            $cls = !empty($p['lido_em']) ? 'confirmado' : '';
            $lido_display = !empty($p['lido_em']) ? "<span class='lido-em'>{$p['lido_em']}</span>" : "<span class='nao-lido'>Não conferido</span>";
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td><td>{$lido_display}</td></tr>";
        }
        echo '</tbody></table>';
    }
}

// 3. CAPITAL (0)
if (isset($regionais_data[0])) {
    $cap = array_filter($regionais_data[0], function($p){ return $p['isPT'] != '1'; });
    if (!empty($cap)) {
        if ($primeira_secao_correios) {
            echo '<div class="secao-correios">';
            echo '<h2>📮 POSTOS DOS CORREIOS</h2>';
            echo '<div class="info-secao">Postos regionais e capital (não Poupa Tempo)</div>';
            echo '</div>';
            $primeira_secao_correios = false;
        }
        $total = count($cap);
        $conf_count = count(array_filter($cap, function($x){ return !empty($x['lido_em']); }));
        echo "<h2>000 - Capital ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th><th>Conferido em</th></tr></thead><tbody>';
        foreach ($cap as $p) {
            $cls = !empty($p['lido_em']) ? 'confirmado' : '';
            $lido_display = !empty($p['lido_em']) ? "<span class='lido-em'>{$p['lido_em']}</span>" : "<span class='nao-lido'>Não conferido</span>";
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td><td>{$lido_display}</td></tr>";
        }
        echo '</tbody></table>';
    }
}

// 4. CENTRAL IIPR (999)
if (isset($regionais_data[999])) {
    $total = count($regionais_data[999]);
    $conf_count = count(array_filter($regionais_data[999], function($x){ return !empty($x['lido_em']); }));
    if ($primeira_secao_correios) {
        echo '<div class="secao-correios">';
        echo '<h2>📮 POSTOS DOS CORREIOS</h2>';
        echo '<div class="info-secao">Postos regionais e capital (não Poupa Tempo)</div>';
        echo '</div>';
        $primeira_secao_correios = false;
    }
    echo "<h2>999 - Central IIPR ($total pacotes / $conf_count conferidos)</h2>";
    echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th><th>Conferido em</th></tr></thead><tbody>';
    foreach ($regionais_data[999] as $p) {
        $cls = !empty($p['lido_em']) ? 'confirmado' : '';
        $lido_display = !empty($p['lido_em']) ? "<span class='lido-em'>{$p['lido_em']}</span>" : "<span class='nao-lido'>Não conferido</span>";
        echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
        echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
        echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td><td>{$lido_display}</td></tr>";
    }
    echo '</tbody></table>';
}

// 5. DEMAIS REGIONAIS
foreach (array_keys($regionais_data) as $reg) {
    if ($reg == 0 || $reg == 1 || $reg == 999) continue;
    $demais = array_filter($regionais_data[$reg], function($p){ return $p['isPT'] != '1'; });
    if (!empty($demais)) {
        if ($primeira_secao_correios) {
            echo '<div class="secao-correios">';
            echo '<h2>📮 POSTOS DOS CORREIOS</h2>';
            echo '<div class="info-secao">Postos regionais e capital (não Poupa Tempo)</div>';
            echo '</div>';
            $primeira_secao_correios = false;
        }
        $reg_str = str_pad($reg, 3, '0', STR_PAD_LEFT);
        $total = count($demais);
        $conf_count = count(array_filter($demais, function($x){ return !empty($x['lido_em']); }));
        echo "<h2>$reg_str - Regional $reg ($total pacotes / $conf_count conferidos)</h2>";
        echo '<table><thead><tr><th>Regional</th><th>Lote</th><th>Posto</th><th>Data</th><th>Qtd</th><th>Código</th><th>Conferido em</th></tr></thead><tbody>';
        foreach ($demais as $p) {
            $cls = !empty($p['lido_em']) ? 'confirmado' : '';
            $lido_display = !empty($p['lido_em']) ? "<span class='lido-em'>{$p['lido_em']}</span>" : "<span class='nao-lido'>Não conferido</span>";
            echo "<tr class='$cls' data-lote='{$p['lote']}' data-codigo='{$p['codigo']}' data-pt='0'>";
            echo "<td>{$p['regional']}</td><td>{$p['lote']}</td><td>{$p['posto']}</td>";
            echo "<td>{$p['data']}</td><td>{$p['qtd']}</td><td>{$p['codigo']}</td><td>{$lido_display}</td></tr>";
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
                
                // Atualiza a célula "Conferido em" com data/hora atual
                const cells = tr.querySelectorAll('td');
                const lidoCell = cells[cells.length - 1]; // última célula
                const agora = new Date();
                const dia = String(agora.getDate()).padStart(2, '0');
                const mes = String(agora.getMonth() + 1).padStart(2, '0');
                const ano = agora.getFullYear();
                const hora = String(agora.getHours()).padStart(2, '0');
                const min = String(agora.getMinutes()).padStart(2, '0');
                const seg = String(agora.getSeconds()).padStart(2, '0');
                const dataHora = `${dia}/${mes}/${ano} ${hora}:${min}:${seg}`;
                lidoCell.innerHTML = `<span class='lido-em'>${dataHora}</span>`;
                
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

                // Verificar se todos pacotes da MESMA TABELA foram conferidos
                setTimeout(() => {
                    const table = tr.closest('table');
                    const tbody = table.querySelector('tbody');
                    const allRows = tbody.querySelectorAll('tr');
                    const confRows = tbody.querySelectorAll('tr.confirmado');
                    
                    if (allRows.length > 0 && allRows.length === confRows.length) {
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
                    
                    // Atualiza a célula "Conferido em" com data/hora atual
                    const cells = this.querySelectorAll('td');
                    const lidoCell = cells[cells.length - 1]; // última célula
                    const agora = new Date();
                    const dia = String(agora.getDate()).padStart(2, '0');
                    const mes = String(agora.getMonth() + 1).padStart(2, '0');
                    const ano = agora.getFullYear();
                    const hora = String(agora.getHours()).padStart(2, '0');
                    const min = String(agora.getMinutes()).padStart(2, '0');
                    const seg = String(agora.getSeconds()).padStart(2, '0');
                    const dataHora = `${dia}/${mes}/${ano} ${hora}:${min}:${seg}`;
                    lidoCell.innerHTML = `<span class='lido-em'>${dataHora}</span>`;
                    
                    if (auto.checked) {
                        fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'salvar_lote_ajax=1&lote=' + encodeURIComponent(lote)
                        });
                    }
                    
                    // Verificar se todos pacotes da MESMA TABELA foram conferidos
                    setTimeout(() => {
                        const table = this.closest('table');
                        const tbody = table.querySelector('tbody');
                        const allRows = tbody.querySelectorAll('tr');
                        const confRows = tbody.querySelectorAll('tr.confirmado');
                        
                        if (allRows.length > 0 && allRows.length === confRows.length) {
                            concluido.currentTime = 0;
                            concluido.play();
                        }
                    }, 100);
                } else {
                    this.classList.remove('confirmado');
                    
                    // Atualiza a célula "Conferido em" para não conferido
                    const cells = this.querySelectorAll('td');
                    const lidoCell = cells[cells.length - 1]; // última célula
                    lidoCell.innerHTML = `<span class='nao-lido'>Não conferido</span>`;
                    
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
        
        // Formatação automática dos inputs de data
        const inputs = document.querySelectorAll('input[name="data_inicio"], input[name="data_fim"]');
        inputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let val = this.value.replace(/\D/g, ''); // remove não dígitos
                if (val.length >= 2) {
                    val = val.substring(0, 2) + '-' + val.substring(2);
                }
                if (val.length >= 5) {
                    val = val.substring(0, 5) + '-' + val.substring(5);
                }
                if (val.length > 10) {
                    val = val.substring(0, 10);
                }
                this.value = val;
            });
        });
    })();
    </script>
</body>
</html>
