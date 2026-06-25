<?php
/**
 * Endpoint AJAX para Prévia de Lacres
 * Retorna lacres sincronizados em tempo real do BD
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');

$pdo_controle = null;
try {
    $pdo_controle = new PDO((getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8' : 'mysql:host=' . (getenv('DB_HOST') ?: (getenv('DB_HOST') ?: '10.15.61.169')) . ';dbname=' . (getenv('DB_NAME') ?: (getenv('DB_NAME') ?: 'controle')) . ';charset=utf8'), 'root', 'vazio');
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(array('sucesso' => false, 'erro' => $e->getMessage())));
}

// Obter ID do último ofício
$ultimoOficioId = 0;
if (isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0) {
    $ultimoOficioId = (int)$_SESSION['id_despacho_correios'];
} else {
    $stUltimo = $pdo_controle->query("SELECT id FROM ciDespachos WHERE grupo = 'CORREIOS' ORDER BY id DESC LIMIT 1");
    $rowUltimo = $stUltimo->fetch(PDO::FETCH_ASSOC);
    if ($rowUltimo) {
        $ultimoOficioId = (int)$rowUltimo['id'];
    }
}

if ($ultimoOficioId === 0) {
    die(json_encode(array('sucesso' => false, 'lacres_por_posto' => array(), 'hash' => '')));
}

// Buscar lacres agregados por posto
try {
    $stLacres = $pdo_controle->prepare("
        SELECT 
            LPAD(CAST(posto AS UNSIGNED), 3, '0') AS posto,
            GROUP_CONCAT(DISTINCT CASE WHEN etiquetaiipr IS NOT NULL AND etiquetaiipr > 0 THEN etiquetaiipr END 
                         ORDER BY CAST(etiquetaiipr AS UNSIGNED) SEPARATOR ',') AS lacres_iipr_csv,
            GROUP_CONCAT(DISTINCT CASE WHEN etiquetacorreios IS NOT NULL AND etiquetacorreios > 0 THEN etiquetacorreios END 
                         ORDER BY CAST(etiquetacorreios AS UNSIGNED) SEPARATOR ',') AS lacres_correios_csv,
            etiqueta_correios,
            MAX(atualizado_em) AS atualizado_em
        FROM ciDespachoLotes
        WHERE id_despacho = ?
        GROUP BY LPAD(CAST(posto AS UNSIGNED), 3, '0')
    ");
    $stLacres->execute(array($ultimoOficioId));
    
    $lacresPorPosto = array();
    while ($row = $stLacres->fetch(PDO::FETCH_ASSOC)) {
        $posto = $row['posto'];
        $iiprCsv = $row['lacres_iipr_csv'] ?: '';
        $correioCsv = $row['lacres_correios_csv'] ?: '';
        $etiqueta = $row['etiqueta_correios'] ?: '';
        
        // Compactar "100,101,102" em "100-102"
        $iiprCompact = compactarLacres($iiprCsv);
        $correioCompact = compactarLacres($correioCsv);
        
        $lacresPorPosto[$posto] = array(
            'lacre_iipr' => $iiprCompact,
            'lacre_correios' => $correioCompact,
            'etiqueta_correios' => $etiqueta
        );
    }
    
    $hash = sha1(json_encode($lacresPorPosto));
    echo json_encode(array(
        'sucesso' => true,
        'lacres_por_posto' => $lacresPorPosto,
        'hash' => $hash
    ));
} catch (Exception $e) {
    echo json_encode(array('sucesso' => false, 'erro' => $e->getMessage()));
}

function compactarLacres($csv) {
    if (!$csv) return '';
    $nums = array();
    $partes = explode(',', $csv);
    foreach ($partes as $p) {
        $n = (int)trim($p);
        if ($n > 0) $nums[$n] = $n;
    }
    if (empty($nums)) return '';
    ksort($nums);
    $nums = array_values($nums);
    $ranges = array();
    $inicio = $nums[0];
    $anterior = $nums[0];
    for ($i = 1; $i < count($nums); $i++) {
        if ($nums[$i] === ($anterior + 1)) {
            $anterior = $nums[$i];
            continue;
        }
        $ranges[] = ($inicio === $anterior) ? (string)$inicio : ($inicio . '-' . $anterior);
        $inicio = $nums[$i];
        $anterior = $nums[$i];
    }
    $ranges[] = ($inicio === $anterior) ? (string)$inicio : ($inicio . '-' . $anterior);
    return implode(', ', $ranges);
}

exit;
