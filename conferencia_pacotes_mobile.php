<?php
/* conferencia_pacotes_mobile.php — v0.9.25.3 Mobile
 * Versão otimizada para celular com leitura via câmera
 * Mantém mesma lógica de banco e conferência do sistema desktop
 * v0.9.25.3:
 * - Bloqueia conferência em regional/tipo divergente (alerta)
 * - Ajustes de confiabilidade da câmera (seleção traseira + fallback)
 */

header('Cache-Control: no-cache, no-store, must-revalidate');

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Conexão com o banco
$host = (getenv('DB_HOST') ?: '10.15.61.169');
$dbname = (getenv('DB_NAME') ?: 'controle');
$user = (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'));
$pass = (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256'));

$pdo = null;
$erro_conexao = '';
$conferencias = array();
$conferencias_lote = array();
$postosInfo = array();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_TIMEOUT => 5
    ));

    $colsConf = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes LIKE 'conferido_em'")->fetchAll();
    if (count($colsConf) === 0) {
        $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN conferido_em DATETIME DEFAULT NULL");
    }

    // Handler AJAX: Salvar conferência
    if (isset($_POST['salvar_conferencia_ajax'])) {
        header('Content-Type: application/json');
        $codbar = trim($_POST['codbar']);
        $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $tipo_conferencia = isset($_POST['tipo_conferencia']) ? trim($_POST['tipo_conferencia']) : 'correios';

        if ($codbar === '' || strlen($codbar) < 14) {
            die(json_encode(array('success' => false, 'erro' => 'Codigo de barras invalido')));
        }
        if ($usuario === '') {
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }

        // Decodificar código de barras (formato: LLLLLLLLRRPPPPQQQQQ)
        $codbar_limpo = preg_replace('/\D+/', '', $codbar);
        $lote = substr($codbar_limpo, 0, 8);
        $regional = substr($codbar_limpo, 8, 3);
        $posto = substr($codbar_limpo, 11, 3);
        $qtd = (int)ltrim(substr($codbar_limpo, 14), '0');

        // Buscar informações do posto em ciRegionais
        $stmt = $pdo->prepare("SELECT CAST(regional AS UNSIGNED) AS regional_real, 
                                      LOWER(TRIM(REPLACE(entrega,' ',''))) AS entrega
                               FROM ciRegionais 
                               WHERE LPAD(posto,3,'0') = ?
                               LIMIT 1");
        $stmt->execute(array($posto));
        $postoInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $regional_real = $regional;
        $tipo_entrega = 'correios';
        $isPT = 0;

        if ($postoInfo) {
            $regional_real = str_pad($postoInfo['regional_real'], 3, '0', STR_PAD_LEFT);
            if (!empty($postoInfo['entrega'])) {
                if (strpos($postoInfo['entrega'], 'poupa') !== false || strpos($postoInfo['entrega'], 'tempo') !== false) {
                    $tipo_entrega = 'poupatempo';
                    $isPT = 1;
                }
            }
        }

        // Buscar se pacote existe na lista de produção (ciPostosCsv) - PRIMEIRO para obter regional autoritativo
        $stmt = $pdo->prepare("SELECT lote, posto, regional, quantidade, DATE_FORMAT(dataCarga, '%Y-%m-%d') as dataexp_sql,
                                      DATE_FORMAT(dataCarga, '%d-%m-%Y') as dataexp_exib
                               FROM ciPostosCsv 
                               WHERE lote = ? AND LPAD(posto,3,'0') = ?
                               ORDER BY dataCarga DESC
                               LIMIT 1");
        $stmt->execute(array((int)ltrim($lote, '0'), $posto));
        $pacoteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Formato SQL para banco (YYYY-mm-dd) e exibição (dd-mm-YYYY)
        $dataexp_sql = date('Y-m-d');
        $dataexp_exib = date('d-m-Y');
        if ($pacoteInfo && !empty($pacoteInfo['dataexp_sql'])) {
            $dataexp_sql = $pacoteInfo['dataexp_sql'];
            $dataexp_exib = $pacoteInfo['dataexp_exib'];
        }

        // Verificar tipo de conferência vs tipo do pacote E regional diferente
        $audio_alerta = '';
        $alerta_tipo = '';
        $alerta_msg = '';
        $pode_conferir = true;

        // Regional autoritativo:
        // - se existir em ciPostosCsv, ela manda
        // - senão, usamos a regional do ciRegionais (quando existir)
        $regional_autoritativo = $pacoteInfo ? str_pad($pacoteInfo['regional'], 3, '0', STR_PAD_LEFT) : $regional_real;

        // 1) Pacote de outra regional (Correios): regional do código != regional autoritativa
        //    (não se aplica a PT)
        if ($isPT == 0 && $regional_autoritativo !== '' && $regional !== $regional_autoritativo) {
            $audio_alerta = 'pacotedeoutraregional.mp3';
            $alerta_tipo = 'outra_regional';
            $alerta_msg = 'Pacote de outra regional';
            $pode_conferir = false;
        }
        // 2) Está em modo Correios, mas o posto é Poupa Tempo
        elseif ($tipo_conferencia === 'correios' && $isPT == 1) {
            $audio_alerta = 'posto_poupatempo.mp3';
            $alerta_tipo = 'posto_poupatempo';
            $alerta_msg = 'Posto do Poupa Tempo';
            $pode_conferir = false;
        }
        // 3) Está em modo Poupa Tempo, mas o posto é Correios
        elseif ($tipo_conferencia === 'poupatempo' && $isPT == 0) {
            $audio_alerta = 'pertence_aos_correios.mp3';
            $alerta_tipo = 'posto_correios';
            $alerta_msg = 'Posto dos Correios';
            $pode_conferir = false;
        }

        // Regional para salvar: 
        // - PT: usar posto como regional
        // - Correios: usar regional autoritativo (do ciPostosCsv se disponível)
        $regional_salvar = ($isPT == 1) ? $posto : $regional_autoritativo;

        if (!$pode_conferir) {
            $stmt = null;
            $pdo = null;
            die(json_encode(array(
                'success' => false,
                'erro' => 'alerta',
                'alerta_tipo' => $alerta_tipo,
                'alerta_msg' => $alerta_msg,
                'audio_alerta' => $audio_alerta,
                'lote' => $lote,
                'posto' => $posto,
                'regional' => $regional_salvar,
                'regional_codigo' => $regional,
                'qtd' => $qtd,
                'dataexp' => $dataexp_exib,
                'tipo_entrega' => $tipo_entrega
            )));
        }

        // Verificar se já foi conferido (incluindo regional para precisão)
        $stmt = $pdo->prepare("SELECT id FROM conferencia_pacotes 
                               WHERE nlote = ? AND nposto = ? AND regional = ? AND conf = 's'
                               LIMIT 1");
        $stmt->execute(array((int)ltrim($lote, '0'), $posto, $regional_salvar));
        $jaConferido = $stmt->fetch();

        if ($jaConferido) {
            $stmt = null;
            $pdo = null;
            die(json_encode(array(
                'success' => false, 
                'erro' => 'ja_conferido',
                'audio' => 'pacotejaconferido.mp3',
                'lote' => $lote,
                'posto' => $posto,
                'regional' => $regional_salvar
            )));
        }

        // Inserir/atualizar conferência (dataexp em formato SQL YYYY-mm-dd)
        $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, conferido_em) 
            VALUES (?, ?, ?, ?, ?, ?, 's', ?, NOW())
            ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar), dataexp=VALUES(dataexp), usuario=VALUES(usuario), conferido_em=NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($regional_salvar, (int)ltrim($lote, '0'), $posto, $dataexp_sql, $qtd > 0 ? $qtd : 1, $codbar_limpo, $usuario));

        // Verificar se grupo foi concluído (todos pacotes de um posto/regional conferidos)
// IMPORTANTE: dataexp pode ser DATETIME no banco; por isso usamos DATE(dataexp).
// Para não tocar "concluido" repetidamente, só dispara quando cruza o limiar (antes < total e depois >= total).
$audio_conclusao = '';
if ($pacoteInfo) {
    $totalGrupo = 0;
    $conferidos_antes = 0;
    $conferidos_depois = 0;

    if ($isPT == 1) {
        // PT: conta todos os pacotes do posto nessa data
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ciPostosCsv
                               WHERE DATE(dataCarga) = ?
                                 AND LPAD(posto,3,'0') = ?");
        $stmt->execute(array($dataexp_sql, $posto));
        $totalGrupo = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conferencia_pacotes
                               WHERE DATE(dataexp) = ?
                                 AND CAST(nposto AS UNSIGNED) = ?
                                 AND conf = 's'");
        $stmt->execute(array($dataexp_sql, (int)$posto));
        $conferidos_depois = (int)$stmt->fetchColumn();

        // "antes" = depois - 1 (por segurança, não abaixo de 0)
        $conferidos_antes = max(0, $conferidos_depois - 1);
    } else {
        // Correios: conta todos os pacotes da regional autoritativa nessa data
        $regional_base = (int)$pacoteInfo['regional']; // vindo do ciPostosCsv

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ciPostosCsv
                               WHERE DATE(dataCarga) = ?
                                 AND CAST(regional AS UNSIGNED) = ?");
        $stmt->execute(array($dataexp_sql, $regional_base));
        $totalGrupo = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conferencia_pacotes
                               WHERE DATE(dataexp) = ?
                                 AND CAST(regional AS UNSIGNED) = ?
                                 AND conf = 's'");
        $stmt->execute(array($dataexp_sql, (int)$regional_salvar));
        $conferidos_depois = (int)$stmt->fetchColumn();

        $conferidos_antes = max(0, $conferidos_depois - 1);
    }

    if ($totalGrupo > 0 && $conferidos_antes < $totalGrupo && $conferidos_depois >= $totalGrupo) {
        $audio_conclusao = 'concluido.mp3';
    }
}

        $stmt = null;
        $pdo = null;

        die(json_encode(array(
            'success' => true,
            'lote' => $lote,
            'posto' => $posto,
            'regional' => $regional_salvar,
            'regional_codigo' => $regional,
            'qtd' => $qtd,
            'dataexp' => $dataexp_exib,
            'tipo_entrega' => $tipo_entrega,
            'audio_beep' => 'beep.mp3',
            'audio_alerta' => $audio_alerta,
            'audio_conclusao' => $audio_conclusao
        )));
    }

    // Handler AJAX: Consultar código de barras
    if (isset($_POST['consultar_codbar'])) {
        header('Content-Type: application/json');
        $codbar = trim($_POST['codbar']);

        if ($codbar === '' || strlen($codbar) < 14) {
            die(json_encode(array('success' => false, 'erro' => 'Codigo de barras invalido')));
        }

        $codbar_limpo = preg_replace('/\D+/', '', $codbar);
        $lote = substr($codbar_limpo, 0, 8);
        $regional = substr($codbar_limpo, 8, 3);
        $posto = substr($codbar_limpo, 11, 3);
        $qtd = (int)ltrim(substr($codbar_limpo, 14), '0');

        // Buscar info do posto em ciRegionais
        $stmt = $pdo->prepare("SELECT CAST(regional AS UNSIGNED) AS regional_real, 
                                      LOWER(TRIM(REPLACE(entrega,' ',''))) AS entrega
                               FROM ciRegionais 
                               WHERE LPAD(posto,3,'0') = ?
                               LIMIT 1");
        $stmt->execute(array($posto));
        $postoInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $tipo_entrega = 'correios';
        $isPT = 0;
        if ($postoInfo && !empty($postoInfo['entrega'])) {
            if (strpos($postoInfo['entrega'], 'poupa') !== false || strpos($postoInfo['entrega'], 'tempo') !== false) {
                $tipo_entrega = 'poupatempo';
                $isPT = 1;
            }
        }

        // Buscar na produção (ciPostosCsv) - fonte autoritativa
        $stmt = $pdo->prepare("SELECT lote, posto, regional, quantidade, 
                                      DATE_FORMAT(dataCarga, '%Y-%m-%d') as dataexp_sql,
                                      DATE_FORMAT(dataCarga, '%d-%m-%Y') as dataexp_exib
                               FROM ciPostosCsv 
                               WHERE lote = ? AND LPAD(posto,3,'0') = ?
                               ORDER BY dataCarga DESC
                               LIMIT 1");
        $stmt->execute(array((int)ltrim($lote, '0'), $posto));
        $pacoteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Regional autoritativo: usar ciPostosCsv se disponível
        $regional_autoritativo = $pacoteInfo ? str_pad($pacoteInfo['regional'], 3, '0', STR_PAD_LEFT) : $regional;
        $regional_salvar = ($isPT == 1) ? $posto : $regional_autoritativo;

        // Verificar se já conferido (incluindo regional para precisão)
        $stmt = $pdo->prepare("SELECT id FROM conferencia_pacotes 
                               WHERE nlote = ? AND nposto = ? AND regional = ? AND conf = 's'
                               LIMIT 1");
        $stmt->execute(array((int)ltrim($lote, '0'), $posto, $regional_salvar));
        $jaConferido = $stmt->fetch() ? true : false;

        // Verificar regional diferente (outra regional)
        $outra_regional = ($pacoteInfo && $regional !== $regional_autoritativo && $isPT == 0);

        $stmt = null;
        $pdo = null;

        die(json_encode(array(
            'success' => true,
            'lote' => $lote,
            'posto' => $posto,
            'regional' => $regional_autoritativo,
            'regional_codigo' => $regional,
            'qtd' => $qtd,
            'tipo_entrega' => $tipo_entrega,
            'ja_conferido' => $jaConferido,
            'na_producao' => $pacoteInfo ? true : false,
            'outra_regional' => $outra_regional,
            'dataexp_sql' => $pacoteInfo ? $pacoteInfo['dataexp_sql'] : date('Y-m-d'),
            'dataexp' => $pacoteInfo ? $pacoteInfo['dataexp_exib'] : date('d-m-Y')
        )));
    }

    // Handler AJAX: Estatísticas do dia
    if (isset($_POST['get_stats'])) {
        header('Content-Type: application/json');
        $data = isset($_POST['data']) ? trim($_POST['data']) : date('Y-m-d');
        
        // Converter de dd-mm-YYYY para YYYY-mm-dd se necessário
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $data, $m)) {
            $data = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(qtd),0) as carteiras 
                               FROM conferencia_pacotes 
                               WHERE dataexp = ? AND conf = 's'");
        $stmt->execute(array($data));
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = null;
        $pdo = null;

        die(json_encode(array(
            'success' => true,
            'pacotes_conferidos' => (int)$stats['total'],
            'carteiras_conferidas' => (int)$stats['carteiras']
        )));
    }

} catch (PDOException $e) {
    $erro_conexao = $e->getMessage();
    if (isset($_POST['salvar_conferencia_ajax']) || isset($_POST['consultar_codbar']) || isset($_POST['get_stats'])) {
        header('Content-Type: application/json');
        die(json_encode(array('success' => false, 'erro' => 'Erro de conexao: ' . $erro_conexao)));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Conferência Mobile v0.9.25.3</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            color: #fff;
            min-height: 100vh;
            padding: 10px;
            padding-bottom: 100px;
        }

        .header {
            text-align: center;
            padding: 15px 0;
            border-bottom: 2px solid #4a4a6a;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 1.3em;
            color: #00d4ff;
        }

        .usuario-container {
            background: #2d2d44;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .usuario-container label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9em;
        }

        .usuario-container input {
            width: 100%;
            padding: 12px;
            border: 2px solid #4a4a6a;
            border-radius: 8px;
            background: #1a1a2e;
            color: #fff;
            font-size: 1em;
        }

        .tipo-conferencia {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .tipo-btn {
            flex: 1;
            padding: 15px;
            border: 2px solid #4a4a6a;
            border-radius: 10px;
            background: #2d2d44;
            color: #aaa;
            font-size: 1em;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tipo-btn.active {
            border-color: #00d4ff;
            background: #003d4d;
            color: #00d4ff;
        }

        .tipo-btn.correios.active {
            border-color: #ffc107;
            background: #4d3d00;
            color: #ffc107;
        }

        .tipo-btn.poupatempo.active {
            border-color: #28a745;
            background: #0d3d1a;
            color: #28a745;
        }

        .camera-container {
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
            position: relative;
        }

        #reader {
            width: 100%;
            min-height: 280px;
        }

        #reader video {
            width: 100% !important;
            border-radius: 15px;
        }

        .scan-line {
            position: absolute;
            top: 50%;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #00d4ff;
            box-shadow: 0 0 10px #00d4ff;
            animation: scanLine 2s ease-in-out infinite;
        }

        @keyframes scanLine {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .manual-input {
            background: #2d2d44;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .manual-input label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9em;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .manual-input input {
            flex: 1;
            padding: 15px;
            border: 2px solid #4a4a6a;
            border-radius: 8px;
            background: #1a1a2e;
            color: #fff;
            font-size: 1.1em;
            font-family: monospace;
        }

        .btn-conferir {
            padding: 15px 25px;
            background: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
        }

        .status-container {
            background: #2d2d44;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .status-title {
            color: #aaa;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .stat-card {
            background: #1a1a2e;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            color: #00d4ff;
        }

        .stat-label {
            font-size: 0.8em;
            color: #888;
            margin-top: 5px;
        }

        .resultado-container {
            background: #2d2d44;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: none;
        }

        .resultado-container.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resultado-sucesso {
            border-left: 4px solid #28a745;
        }

        .resultado-erro {
            border-left: 4px solid #dc3545;
        }

        .resultado-alerta {
            border-left: 4px solid #ffc107;
        }

        .resultado-titulo {
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .resultado-detalhes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 0.9em;
        }

        .resultado-item {
            background: #1a1a2e;
            padding: 8px;
            border-radius: 5px;
        }

        .resultado-item span {
            color: #888;
            display: block;
            font-size: 0.8em;
        }

        .historico-container {
            background: #2d2d44;
            border-radius: 10px;
            padding: 15px;
        }

        .historico-titulo {
            color: #aaa;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .historico-lista {
            max-height: 200px;
            overflow-y: auto;
        }

        .historico-item {
            background: #1a1a2e;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
        }

        .historico-item.sucesso {
            border-left: 3px solid #28a745;
        }

        .historico-item.erro {
            border-left: 3px solid #dc3545;
        }

        .historico-item .info {
            flex: 1;
        }

        .historico-item .hora {
            color: #666;
            font-size: 0.8em;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #2d2d44;
            border-top-color: #00d4ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-msg {
            background: #3d1a1a;
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .camera-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #4a4a6a;
            border-radius: 8px;
            background: #2d2d44;
            color: #fff;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s;
        }

        .camera-btn.active {
            border-color: #00d4ff;
            background: #003d4d;
        }

        .camera-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .bloqueio-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .bloqueio-box {
            background: #2d2d44;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 90%;
        }

        .bloqueio-box h2 {
            color: #00d4ff;
            margin-bottom: 20px;
        }

        .bloqueio-box input {
            width: 100%;
            padding: 15px;
            border: 2px solid #4a4a6a;
            border-radius: 8px;
            background: #1a1a2e;
            color: #fff;
            font-size: 1.1em;
            margin-bottom: 15px;
        }

        .bloqueio-box button {
            width: 100%;
            padding: 15px;
            background: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
        }

        .usuario-display {
            background: #003d4d;
            border: 1px solid #00d4ff;
            color: #00d4ff;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php if ($erro_conexao): ?>
<div class="error-msg">
    Erro de conexão com o banco de dados.<br>
    Verifique sua conexão de rede.
</div>
<?php endif; ?>

<div id="bloqueioUsuario" class="bloqueio-overlay">
    <div class="bloqueio-box">
        <h2>Conferência de Pacotes</h2>
        <p style="color: #aaa; margin-bottom: 20px;">Informe seu usuário para iniciar</p>
        <input type="text" id="usuarioBloqueio" placeholder="Digite seu usuário" autocomplete="off">
        <button onclick="liberarAcesso()">Iniciar Conferência</button>
    </div>
</div>

<div class="header">
    <h1>Conferência Mobile v0.9.25.3</h1>
</div>

<div id="usuarioDisplay" class="usuario-display" style="display: none;">
    Usuário: <span id="usuarioNome"></span>
</div>

<div class="tipo-conferencia">
    <div class="tipo-btn correios active" onclick="setTipoConferencia('correios', this)">
        CORREIOS
    </div>
    <div class="tipo-btn poupatempo" onclick="setTipoConferencia('poupatempo', this)">
        POUPA TEMPO
    </div>
</div>

<div class="camera-controls">
    <button class="camera-btn active" id="btnIniciarCamera" onclick="iniciarCamera()">
        📷 Iniciar Câmera
    </button>
    <button class="camera-btn" id="btnPararCamera" onclick="pararCamera()" disabled>
        ⏹️ Parar
    </button>
</div>

<div class="camera-container">
    <div id="reader"></div>
    <div class="scan-line" id="scanLine" style="display: none;"></div>
</div>

<div class="manual-input">
    <label>Ou digite o código manualmente:</label>
    <div class="input-group">
         <input type="text" id="codigoManual" placeholder="Ex: 00001234001012" inputmode="numeric" pattern="[0-9]*"
             oninput="if(window.processarCodigo){var v=String(this.value||'').replace(/\D+/g,''); if(v.length>=14){window.processarCodigo(this.value);} }"
             onchange="if(window.processarCodigo){var v=String(this.value||'').replace(/\D+/g,''); if(v.length>=14){window.processarCodigo(this.value);} }"
             onkeydown="if(event && event.keyCode===13){event.preventDefault(); if(window.processarCodigo){var v=String(this.value||'').replace(/\D+/g,''); if(v.length>=14){window.processarCodigo(this.value);} } }">
        <button class="btn-conferir" onclick="conferirManual()">✓</button>
    </div>
</div>

<div class="loading" id="loading">
    <div class="spinner"></div>
    <p>Processando...</p>
</div>

<div class="resultado-container" id="resultadoContainer">
    <div class="resultado-titulo" id="resultadoTitulo"></div>
    <div class="resultado-detalhes" id="resultadoDetalhes"></div>
</div>

<div class="status-container">
    <div class="status-title">Estatísticas de Hoje</div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number" id="statPacotes">0</div>
            <div class="stat-label">Pacotes Conferidos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="statCarteiras">0</div>
            <div class="stat-label">Carteiras</div>
        </div>
    </div>
</div>

<div class="historico-container">
    <div class="historico-titulo">Últimas Leituras</div>
    <div class="historico-lista" id="historicoLista">
        <p style="color: #666; text-align: center; padding: 20px;">Nenhuma leitura ainda</p>
    </div>
</div>

<!-- Áudios -->
<audio id="audioBeep" src="beep.mp3" preload="auto"></audio>
<audio id="audioConcluido" src="concluido.mp3" preload="auto"></audio>
<audio id="audioJaConferido" src="pacotejaconferido.mp3" preload="auto"></audio>
<audio id="audioOutraRegional" src="pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="audioPoupaTempo" src="posto_poupatempo.mp3" preload="auto"></audio>
<audio id="audioCorreios" src="pertence_aos_correios.mp3" preload="auto"></audio>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let usuarioAtual = '';
let tipoConferencia = 'correios';
let audioQueue = [];
let isPlayingAudio = false;
let cameraAtiva = false;
let cameraAutoRetomar = false;
let ultimoCodigo = '';
let ultimaLeitura = 0;

// Fila de áudio
function enqueueAudio(audioId) {
    audioQueue.push(audioId);
    processAudioQueue();
}

function processAudioQueue() {
    if (isPlayingAudio || audioQueue.length === 0) return;
    
    isPlayingAudio = true;
    const audioId = audioQueue.shift();
    const audio = document.getElementById(audioId);
    
    if (audio) {
        audio.currentTime = 0;
        audio.play().then(() => {
            audio.onended = () => {
                isPlayingAudio = false;
                processAudioQueue();
            };
        }).catch(() => {
            isPlayingAudio = false;
            processAudioQueue();
        });
    } else {
        isPlayingAudio = false;
        processAudioQueue();
    }
}

// Desbloquear áudios (necessário em mobile)
function desbloquearAudios() {
    const audios = document.querySelectorAll('audio');
    audios.forEach(a => {
        a.play().then(() => a.pause()).catch(() => {});
    });
}

function liberarAcesso() {
    const usuario = document.getElementById('usuarioBloqueio').value.trim();
    if (usuario === '') {
        alert('Por favor, informe seu usuário');
        return;
    }
    
    usuarioAtual = usuario;
    document.getElementById('bloqueioUsuario').style.display = 'none';
    document.getElementById('usuarioDisplay').style.display = 'block';
    document.getElementById('usuarioNome').textContent = usuario;
    
    desbloquearAudios();
    carregarStats();
}

function setTipoConferencia(tipo, el) {
    tipoConferencia = tipo;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
}

function iniciarCamera() {
    if (cameraAtiva) return;

    if (typeof Html5Qrcode === 'undefined') {
        alert('Leitor de camera indisponivel. Verifique sua conexao.');
        return;
    }

    html5QrCode = new Html5Qrcode("reader");

    const config = {
        fps: 10,
        qrbox: { width: 280, height: 100 },
        aspectRatio: 1.777,
        disableFlip: true,
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        formatsToSupport: [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.ITF,
            Html5QrcodeSupportedFormats.CODABAR
        ]
    };

    Html5Qrcode.getCameras().then((cameras) => {
        let cameraId = null;
        if (cameras && cameras.length) {
            for (let i = 0; i < cameras.length; i++) {
                const label = (cameras[i].label || '').toLowerCase();
                if (label.includes('back') || label.includes('rear') || label.includes('traseira')) {
                    cameraId = cameras[i].id;
                    break;
                }
            }
            if (!cameraId) {
                cameraId = cameras[0].id;
            }
        }

        const cameraConfig = cameraId ? { deviceId: { exact: cameraId } } : { facingMode: "environment" };

        return html5QrCode.start(
            cameraConfig,
            config,
            onScanSuccess,
            onScanError
        );
    }).then(() => {
        cameraAtiva = true;
        document.getElementById('btnIniciarCamera').disabled = true;
        document.getElementById('btnPararCamera').disabled = false;
        document.getElementById('btnIniciarCamera').classList.remove('active');
        document.getElementById('btnPararCamera').classList.add('active');
        document.getElementById('scanLine').style.display = 'block';
    }).catch(err => {
        console.error("Erro ao iniciar câmera:", err);
        alert("Erro ao acessar a câmera. Verifique as permissões.");
    });
}

function pararCamera() {
    if (!cameraAtiva || !html5QrCode) return;
    
    html5QrCode.stop().then(() => {
        cameraAtiva = false;
        document.getElementById('btnIniciarCamera').disabled = false;
        document.getElementById('btnPararCamera').disabled = true;
        document.getElementById('btnIniciarCamera').classList.add('active');
        document.getElementById('btnPararCamera').classList.remove('active');
        document.getElementById('scanLine').style.display = 'none';
    }).catch(err => {
        console.error("Erro ao parar câmera:", err);
    });
}

function onScanSuccess(decodedText, decodedResult) {
    const agora = Date.now();

    const digits = String(decodedText || '').replace(/\D+/g, '');
    if (digits.length < 14) {
        return;
    }
    
    // Evitar leituras duplicadas em sequência (debounce de 2 segundos)
    if (digits === ultimoCodigo && (agora - ultimaLeitura) < 2000) {
        return;
    }

    ultimoCodigo = digits;
    ultimaLeitura = agora;
    
    // Vibrar para feedback
    if (navigator.vibrate) {
        navigator.vibrate(100);
    }
    
    processarCodigo(digits);
}

function onScanError(errorMessage) {
    // Ignorar erros de leitura (normal quando não há código na frente)
}

function conferirManual() {
    const codigo = document.getElementById('codigoManual').value.trim();
    if (codigo === '') {
        alert('Digite um código de barras');
        return;
    }
    processarCodigo(codigo);
    document.getElementById('codigoManual').value = '';
}

function processarCodigo(codigo) {
    if (usuarioAtual === '') {
        alert('Informe seu usuário primeiro');
        return;
    }

    const codigoLimpo = String(codigo || '').replace(/\D+/g, '');
    if (codigoLimpo.length < 14) {
        mostrarResultado('erro', { mensagem: 'Codigo de barras invalido' });
        return;
    }
    
    document.getElementById('loading').classList.add('show');
    
    const formData = new FormData();
    formData.append('salvar_conferencia_ajax', '1');
    formData.append('codbar', codigoLimpo);
    formData.append('usuario', usuarioAtual);
    formData.append('tipo_conferencia', tipoConferencia);
    
    fetch('conferencia_pacotes_mobile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading').classList.remove('show');
        
        if (data.success) {
            mostrarResultado('sucesso', data);
            adicionarHistorico(true, data);
            
            // Tocar áudios na ordem
            if (data.audio_beep) enqueueAudio('audioBeep');
            if (data.audio_alerta === 'posto_poupatempo.mp3') enqueueAudio('audioPoupaTempo');
            if (data.audio_alerta === 'pertence_aos_correios.mp3') enqueueAudio('audioCorreios');
            if (data.audio_alerta === 'pacotedeoutraregional.mp3') enqueueAudio('audioOutraRegional');
            if (data.audio_conclusao) enqueueAudio('audioConcluido');
            
            carregarStats();
        } else {
            if (data.erro === 'ja_conferido') {
                mostrarResultado('alerta', { 
                    mensagem: 'Pacote já conferido!',
                    lote: data.lote,
                    posto: data.posto
                });
                enqueueAudio('audioJaConferido');
            } else if (data.erro === 'alerta') {
                mostrarResultado('alerta', {
                    mensagem: data.alerta_msg || 'Atenção',
                    lote: data.lote,
                    posto: data.posto,
                    regional: data.regional,
                    regional_codigo: data.regional_codigo,
                    tipo_entrega: data.tipo_entrega
                });
                if (data.audio_alerta === 'posto_poupatempo.mp3') enqueueAudio('audioPoupaTempo');
                if (data.audio_alerta === 'pertence_aos_correios.mp3') enqueueAudio('audioCorreios');
                if (data.audio_alerta === 'pacotedeoutraregional.mp3') enqueueAudio('audioOutraRegional');
            } else {
                mostrarResultado('erro', { mensagem: data.erro || 'Erro desconhecido' });
            }
            if (data.erro === 'alerta') {
                adicionarHistorico(false, { mensagem: data.alerta_msg || 'Atenção' });
            } else {
                adicionarHistorico(false, data);
            }
        }
    })
    .catch(err => {
        document.getElementById('loading').classList.remove('show');
        mostrarResultado('erro', { mensagem: 'Erro de conexão' });
        console.error(err);
    });
}

function mostrarResultado(tipo, data) {
    const container = document.getElementById('resultadoContainer');
    const titulo = document.getElementById('resultadoTitulo');
    const detalhes = document.getElementById('resultadoDetalhes');
    
    container.className = 'resultado-container show';
    
    if (tipo === 'sucesso') {
        container.classList.add('resultado-sucesso');
        
        // Verificar se houve divergência de regional
        let alertaRegional = '';
        if (data.regional_codigo && data.regional !== data.regional_codigo) {
            alertaRegional = ` <small style="color:#ffc107">(cód: ${data.regional_codigo})</small>`;
        }
        
        titulo.innerHTML = '✅ Conferência Salva!';
        detalhes.innerHTML = `
            <div class="resultado-item"><span>Lote</span>${data.lote}</div>
            <div class="resultado-item"><span>Posto</span>${data.posto}</div>
            <div class="resultado-item"><span>Regional</span>${data.regional}${alertaRegional}</div>
            <div class="resultado-item"><span>Qtd</span>${data.qtd}</div>
            <div class="resultado-item"><span>Data</span>${data.dataexp}</div>
            <div class="resultado-item"><span>Tipo</span>${data.tipo_entrega}</div>
        `;
    } else if (tipo === 'alerta') {
        container.classList.add('resultado-alerta');
        titulo.innerHTML = '⚠️ ' + (data.mensagem || 'Atenção');
        detalhes.innerHTML = data.lote ? `
            <div class="resultado-item"><span>Lote</span>${data.lote}</div>
            <div class="resultado-item"><span>Posto</span>${data.posto}</div>
            ${data.regional ? `<div class="resultado-item"><span>Regional</span>${data.regional}</div>` : ''}
            ${data.tipo_entrega ? `<div class="resultado-item"><span>Tipo</span>${data.tipo_entrega}</div>` : ''}
        ` : '';
    } else {
        container.classList.add('resultado-erro');
        titulo.innerHTML = '❌ ' + (data.mensagem || 'Erro');
        detalhes.innerHTML = '';
    }
    
    // Auto-hide após 5 segundos
    setTimeout(() => {
        container.classList.remove('show', 'resultado-sucesso', 'resultado-erro', 'resultado-alerta');
    }, 5000);
}

function adicionarHistorico(sucesso, data) {
    const lista = document.getElementById('historicoLista');
    const hora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    
    // Limpar mensagem inicial se existir
    if (lista.querySelector('p')) {
        lista.innerHTML = '';
    }
    
    const item = document.createElement('div');
    item.className = 'historico-item ' + (sucesso ? 'sucesso' : 'erro');
    
    if (sucesso) {
        item.innerHTML = `
            <div class="info">
                <strong>Lote ${data.lote}</strong> - Posto ${data.posto}
            </div>
            <div class="hora">${hora}</div>
        `;
    } else {
        item.innerHTML = `
            <div class="info">
                <strong>${data.erro || data.mensagem || 'Erro'}</strong>
            </div>
            <div class="hora">${hora}</div>
        `;
    }
    
    lista.insertBefore(item, lista.firstChild);
    
    // Manter apenas últimos 10 itens
    while (lista.children.length > 10) {
        lista.removeChild(lista.lastChild);
    }
}

function carregarStats() {
    const formData = new FormData();
    formData.append('get_stats', '1');
    // Enviar data no formato YYYY-mm-dd
    const hoje = new Date();
    const dataFormatada = hoje.getFullYear() + '-' + 
                          String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(hoje.getDate()).padStart(2, '0');
    formData.append('data', dataFormatada);
    
    fetch('conferencia_pacotes_mobile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('statPacotes').textContent = data.pacotes_conferidos;
            document.getElementById('statCarteiras').textContent = data.carteiras_conferidas;
        }
    })
    .catch(err => console.error(err));
}

// Enter no campo manual
document.getElementById('codigoManual').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        conferirManual();
    }
});

// Enter no campo de usuário
document.getElementById('usuarioBloqueio').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        liberarAcesso();
    }
});

// Carregar stats inicial
document.addEventListener('DOMContentLoaded', function() {
    // Focus no campo de usuário
    document.getElementById('usuarioBloqueio').focus();
});

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (cameraAtiva) {
            cameraAutoRetomar = true;
            pararCamera();
        }
    } else if (cameraAutoRetomar) {
        cameraAutoRetomar = false;
        iniciarCamera();
    }
});
</script>

</body>
</html>
