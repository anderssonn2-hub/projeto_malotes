<?php
/* oficio_dinamico_correios.php — v1.1.8
 * CHANGELOG v1.1.2 (29/04/2026):
 * - [VER] Alinhamento de versão global para v1.1.2
 * CHANGELOG v1.3.1 (29/04/2026):
 * - [FIX] safe_json_odc() substitui json_encode direto: usa JSON_INVALID_UTF8_SUBSTITUTE
 *   para nunca retornar false (die(false) = resposta vazia = JSON.parse error)
 * - [FIX] catch (Exception) -> catch (\Throwable) em handlers AJAX (captura erros PHP 8)
 * - [FIX] ini_set display_errors off + error_reporting(0) para requisicoes AJAX
 * CHANGELOG v1.3.0 (28/04/2026):
 * - [CORRIGIDO] Áudio concluido.mp3 dispara somente quando TODOS os lotes da REGIONAL são conferidos
 * - [NOVO] Áudio pacotedeoutraregional.mp3 quando lote de regional diferente é escaneado
 * - [NOVO] Lote já conferido (conf='s' em conferencia_pacotes) carrega em verde com data/hora
 * - [NOVO] Info completa no lote: data, responsável, codbar, conferido_em
 * - [NOVO] Seleção de ofícios para impressão (checkboxes) — substituiu botão individual por página
 * - [NOVO] Terceiro painel — Ofício Correios com Lacres IIPR/Correios/Etiqueta
 *          carregado automaticamente ao aplicar filtro, com preenchimento sequencial
 * - [FIX]  JOIN com conferencia_pacotes via subquery para evitar duplicatas
 */
error_reporting(E_ALL & ~E_NOTICE);
if (!isset($_SESSION)) { session_start(); }

function e_odc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function normalizar_data_odc($v) {
    $v = trim((string)$v);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v)) return $v;
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) return $m[3].'-'.$m[2].'-'.$m[1];
    return '';
}
function safe_json_odc($data) {
    $r = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($r === false) {
        $r = json_encode(array('success' => false, 'erro' => 'Erro de codificacao: ' . json_last_error_msg()));
    }
    return $r;
}

$pdo = null; $erroConexao = '';
try {
    $pdo = new PDO((getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4' : 'mysql:host=' . (getenv('DB_HOST') ?: '10.15.61.169') . ';dbname=controle;charset=utf8mb4'),(getenv('DB_USER') ?: (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'))),(getenv('DB_PASS') ?: (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256'))),
        array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
} catch (Exception $e) { $erroConexao = $e->getMessage(); }

$isAjax = isset($_POST['ajax_buscar_lote_odc'])
       || isset($_POST['ajax_buscar_lote_pt_odc'])
       || isset($_POST['ajax_listar_lotes_odc'])
       || isset($_POST['ajax_listar_lotes_pt_odc'])
       || isset($_POST['ajax_carregar_lote_odc']);
if ($isAjax) { ini_set('display_errors', '0'); error_reporting(0); }
if ($isAjax && !$pdo) {
    header('Content-Type: application/json');
    die(safe_json_odc(array('success'=>false,'erro'=>'Banco indisponível: '.$erroConexao)));
}

/* ─── AJAX: RESOLVER CÓDIGO DE BARRAS ─── */
if ($pdo && isset($_POST['ajax_buscar_lote_odc'])) {
    header('Content-Type: application/json');
    try {
        $codbar = preg_replace('/\D+/','',(string)$_POST['codbar']);
        $data   = normalizar_data_odc($_POST['data'] ?? date('Y-m-d'));
        $len    = strlen($codbar);
        if ($len!==19 && $len!==17)
            die(safe_json_odc(array('success'=>false,'erro'=>'Código inválido: '.$len.' dígitos.')));

        $lote_cod  = substr($codbar,0,8);
        $posto_cod = str_pad(substr($codbar,11,3),3,'0',STR_PAD_LEFT);

        $stmtP = $pdo->prepare("SELECT LPAD(posto,3,'0') AS posto,
            COALESCE(NULLIF(TRIM(nome),''),CONCAT('POSTO ',LPAD(posto,3,'0'))) AS nome,
            CAST(regional AS UNSIGNED) AS regional,
            LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega
            FROM ciRegionais WHERE LPAD(posto,3,'0')=? LIMIT 1");
        $stmtP->execute(array($posto_cod));
        $postoRow = $stmtP->fetch();
        if (!$postoRow) die(safe_json_odc(array('success'=>false,'erro'=>'Posto '.$posto_cod.' não encontrado.')));
        if (strpos((string)$postoRow['entrega'],'poupa')!==false||strpos((string)$postoRow['entrega'],'tempo')!==false)
            die(safe_json_odc(array('success'=>false,'erro'=>'Posto '.$posto_cod.' é Poupa Tempo.')));

        $regional_id = str_pad((string)(int)$postoRow['regional'],3,'0',STR_PAD_LEFT);
        $stmtR = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nome),''),CONCAT('REGIONAL ',LPAD(posto,3,'0'))) AS nome
            FROM ciRegionais WHERE LPAD(posto,3,'0')=? LIMIT 1");
        $stmtR->execute(array($regional_id));
        $regRow = $stmtR->fetch();
        $nome_regional = $regRow ? (string)$regRow['nome'] : 'REGIONAL '.$regional_id;

        $lote_pad = str_pad($lote_cod,8,'0',STR_PAD_LEFT);
        $stmtC = $pdo->prepare("SELECT LPAD(CAST(lote AS CHAR),8,'0') AS lote,
            COALESCE(quantidade,0) AS quantidade, DATE(dataCarga) AS data_carga,
            COALESCE(usuario,'') AS responsavel
            FROM ciPostosCsv WHERE LPAD(CAST(lote AS CHAR),8,'0')=? AND LPAD(posto,3,'0')=?
            ORDER BY id DESC LIMIT 1");
        $stmtC->execute(array($lote_pad,$posto_cod));
        $loteRow = $stmtC->fetch();

        // Verificar se já foi conferido
        $stmtConf = $pdo->prepare("SELECT conf, codbar, conferido_em FROM conferencia_pacotes
            WHERE CAST(nlote AS UNSIGNED)=? AND CAST(nposto AS UNSIGNED)=? AND conf='s'
            ORDER BY conferido_em DESC LIMIT 1");
        $stmtConf->execute(array((int)$lote_pad,(int)$posto_cod));
        $confRow = $stmtConf->fetch();

        // Gravar conferência se ainda não conferido
        $conferido_em_novo = '';
        if (!$confRow) {
            $qtd_ins  = $loteRow ? (int)$loteRow['quantidade'] : 0;
            $data_ins = ($loteRow && !empty($loteRow['data_carga'])) ? $loteRow['data_carga'] : $data;
            $sqlIns = "INSERT INTO conferencia_pacotes
                           (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, conferido_em)
                       VALUES (?,?,?,?,?,?,'s',?,NOW())
                       ON DUPLICATE KEY UPDATE
                           conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar),
                           dataexp=VALUES(dataexp), usuario=VALUES(usuario), conferido_em=NOW()";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute(array(
                (int)$regional_id,
                (int)$lote_pad,
                (int)$posto_cod,
                $data_ins,
                $qtd_ins,
                $codbar,
                $usuarioSessao
            ));
            $conferido_em_novo = $pdo->query("SELECT NOW()")->fetchColumn() ?: date('Y-m-d H:i:s');
        }

        die(safe_json_odc(array(
            'success'       => true,
            'lote'          => $lote_pad,
            'posto'         => $posto_cod,
            'nome_posto'    => (string)$postoRow['nome'],
            'regional'      => $regional_id,
            'nome_regional' => $nome_regional,
            'quantidade'    => $loteRow ? (int)$loteRow['quantidade'] : 0,
            'data_carga'    => $loteRow ? ($loteRow['data_carga'] ?: $data) : $data,
            'responsavel'   => $loteRow ? (string)$loteRow['responsavel'] : '',
            'conf'          => $confRow ? 1 : 0,
            'codbar'        => $confRow ? (string)$confRow['codbar'] : $codbar,
            'conferido_em'  => $confRow ? (string)$confRow['conferido_em'] : $conferido_em_novo,
            'nao_listado'   => !$loteRow,
            'fora_filtro'   => 0,
        )));
    } catch (\Throwable $ex) {
        die(safe_json_odc(array('success'=>false,'erro'=>'Erro banco: '.$ex->getMessage())));
    }
}

/* ─── AJAX: LISTAR LOTES (intervalo de datas) ─── */
if ($pdo && isset($_POST['ajax_listar_lotes_odc'])) {
    header('Content-Type: application/json');
    try {
        $data_ini = normalizar_data_odc($_POST['data_ini'] ?? date('Y-m-d'));
        $data_fim = normalizar_data_odc($_POST['data_fim'] ?? $data_ini);
        if (!$data_ini) $data_ini = date('Y-m-d');
        if (!$data_fim || $data_fim < $data_ini) $data_fim = $data_ini;
        $incluir_pendentes = !empty($_POST['incluir_pendentes']);

        $selectBase = "
            SELECT
                LPAD(CAST(c.lote AS CHAR),8,'0')                                          AS lote,
                LPAD(c.posto,3,'0')                                                        AS posto,
                LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED), CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0') AS regional,
                COALESCE(c.quantidade,0)                                                   AS quantidade,
                DATE(c.dataCarga)                                                          AS data_carga,
                COALESCE(c.usuario,'')                                                     AS responsavel,
                COALESCE(NULLIF(TRIM(r.nome),''),CONCAT('POSTO ',LPAD(c.posto,3,'0')))    AS nome_posto,
                COALESCE(NULLIF(TRIM(rr.nome),''),CONCAT('REGIONAL ',LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0'))) AS nome_regional,
                0                                                                          AS fora_filtro,
                CASE WHEN cp.conf='s' THEN 1 ELSE 0 END                                   AS conf,
                COALESCE(cp.codbar,'')                                                     AS codbar,
                cp.conferido_em                                                            AS conferido_em
            FROM ciPostosCsv c
            LEFT JOIN ciRegionais r  ON LPAD(r.posto,3,'0')  = LPAD(c.posto,3,'0')
            LEFT JOIN ciRegionais rr ON LPAD(rr.posto,3,'0') = LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0')
            LEFT JOIN (
                SELECT nlote, nposto, conf, codbar, MAX(conferido_em) AS conferido_em
                FROM conferencia_pacotes WHERE conf='s' GROUP BY nlote, nposto
            ) cp ON CAST(cp.nlote AS UNSIGNED) = CAST(c.lote AS UNSIGNED)
                AND CAST(cp.nposto AS UNSIGNED) = CAST(c.posto AS UNSIGNED)
            WHERE DATE(c.dataCarga) BETWEEN ? AND ?
              AND COALESCE(c.quantidade,0) > 0
              AND (r.entrega IS NULL OR REPLACE(LOWER(TRIM(r.entrega)),' ','') NOT LIKE '%poupa%tempo%')";
        $params = array($data_ini, $data_fim);

        if ($incluir_pendentes) {
            $selectBase .= "
            UNION ALL
            SELECT
                LPAD(CAST(c.lote AS CHAR),8,'0') AS lote,
                LPAD(c.posto,3,'0') AS posto,
                LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0') AS regional,
                COALESCE(c.quantidade,0) AS quantidade,
                DATE(c.dataCarga) AS data_carga,
                COALESCE(c.usuario,'') AS responsavel,
                COALESCE(NULLIF(TRIM(r.nome),''),CONCAT('POSTO ',LPAD(c.posto,3,'0'))) AS nome_posto,
                COALESCE(NULLIF(TRIM(rr.nome),''),CONCAT('REGIONAL ',LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0'))) AS nome_regional,
                1 AS fora_filtro,
                CASE WHEN cp.conf='s' THEN 1 ELSE 0 END AS conf,
                COALESCE(cp.codbar,'') AS codbar,
                cp.conferido_em AS conferido_em
            FROM ciPostosCsv c
            LEFT JOIN ciRegionais r  ON LPAD(r.posto,3,'0')  = LPAD(c.posto,3,'0')
            LEFT JOIN ciRegionais rr ON LPAD(rr.posto,3,'0') = LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0')
            LEFT JOIN (
                SELECT nlote, nposto, conf, codbar, MAX(conferido_em) AS conferido_em
                FROM conferencia_pacotes WHERE conf='s' GROUP BY nlote, nposto
            ) cp ON CAST(cp.nlote AS UNSIGNED) = CAST(c.lote AS UNSIGNED)
                AND CAST(cp.nposto AS UNSIGNED) = CAST(c.posto AS UNSIGNED)
            WHERE DATE(c.dataCarga) < ?
              AND DATE(c.dataCarga) >= DATE_SUB(?,INTERVAL 45 DAY)
              AND COALESCE(c.quantidade,0) > 0
              AND (r.entrega IS NULL OR REPLACE(LOWER(TRIM(r.entrega)),' ','') NOT LIKE '%poupa%tempo%')";
            $params[] = $data_ini;
            $params[] = $data_ini;
        }

        $stmt = $pdo->prepare($selectBase);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $lotes = array(); $vistos = array();
        foreach ($rows as $row) {
            $k = $row['lote'].'|'.$row['posto'];
            if (isset($vistos[$k])) continue;
            $vistos[$k] = 1;
            $lotes[] = array(
                'lote'         => (string)$row['lote'],
                'posto'        => (string)$row['posto'],
                'regional'     => (string)$row['regional'],
                'quantidade'   => (int)$row['quantidade'],
                'data_carga'   => (string)$row['data_carga'],
                'responsavel'  => (string)$row['responsavel'],
                'nome_posto'   => (string)$row['nome_posto'],
                'nome_regional'=> (string)$row['nome_regional'],
                'fora_filtro'  => (int)$row['fora_filtro'],
                'conf'         => (int)$row['conf'],
                'codbar'       => (string)$row['codbar'],
                'conferido_em' => $row['conferido_em'] ? (string)$row['conferido_em'] : '',
                'nao_listado'  => 0,
            );
        }
        die(safe_json_odc(array('success'=>true,'lotes'=>$lotes,'total'=>count($lotes))));
    } catch (\Throwable $ex) {
        die(safe_json_odc(array('success'=>false,'erro'=>'Erro ao listar: '.$ex->getMessage())));
    }
}

/* ─── AJAX: CARREGAR LOTE PENDENTE ─── */
if ($pdo && isset($_POST['ajax_carregar_lote_odc'])) {
    header('Content-Type: application/json');
    try {
        $lote     = str_pad(preg_replace('/\D+/','',(string)$_POST['lote']),8,'0',STR_PAD_LEFT);
        $posto    = str_pad(preg_replace('/\D+/','',(string)$_POST['posto']),3,'0',STR_PAD_LEFT);
        $regional = str_pad(preg_replace('/\D+/','',(string)$_POST['regional']),3,'0',STR_PAD_LEFT);
        $qtd      = max(0,(int)($_POST['quantidade']??0));
        $data     = normalizar_data_odc($_POST['data_carga']??date('Y-m-d')) ?: date('Y-m-d');
        $usuario  = trim((string)($_SESSION['usuario']??''));
        $stmtChk  = $pdo->prepare("SELECT id FROM ciPostosCsv WHERE LPAD(CAST(lote AS CHAR),8,'0')=? AND LPAD(posto,3,'0')=? LIMIT 1");
        $stmtChk->execute(array($lote,$posto));
        if ($stmtChk->fetch()) die(safe_json_odc(array('success'=>true,'msg'=>'Lote já existe em ciPostosCsv.')));
        $stmtIns = $pdo->prepare("INSERT INTO ciPostosCsv (lote,posto,regional,quantidade,dataCarga,data,usuario) VALUES (?,?,?,?,?,NOW(),?)");
        $stmtIns->execute(array((int)$lote,(int)$posto,(int)$regional,$qtd,$data,$usuario));
        die(safe_json_odc(array('success'=>true,'msg'=>'Lote '.$lote.' registrado.')));
    } catch (\Throwable $ex) {
        die(safe_json_odc(array('success'=>false,'erro'=>$ex->getMessage())));
    }
}

/* ─── AJAX: RESOLVER CÓDIGO DE BARRAS — POUPA TEMPO ─── */
if ($pdo && isset($_POST['ajax_buscar_lote_pt_odc'])) {
    header('Content-Type: application/json');
    try {
        $codbar = preg_replace('/\D+/','',(string)$_POST['codbar']);
        $data   = normalizar_data_odc(isset($_POST['data']) ? $_POST['data'] : date('Y-m-d'));
        $len    = strlen($codbar);
        if ($len!==19 && $len!==17)
            die(safe_json_odc(array('success'=>false,'erro'=>'Código inválido: '.$len.' dígitos.')));

        $lote_cod  = substr($codbar,0,8);
        $posto_cod = str_pad(substr($codbar,11,3),3,'0',STR_PAD_LEFT);

        $stmtP = $pdo->prepare("SELECT LPAD(posto,3,'0') AS posto,
            COALESCE(NULLIF(TRIM(nome),''),CONCAT('POSTO ',LPAD(posto,3,'0'))) AS nome,
            CAST(regional AS UNSIGNED) AS regional,
            LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega
            FROM ciRegionais WHERE LPAD(posto,3,'0')=? LIMIT 1");
        $stmtP->execute(array($posto_cod));
        $postoRow = $stmtP->fetch();
        if (!$postoRow) die(safe_json_odc(array('success'=>false,'erro'=>'Posto '.$posto_cod.' não encontrado.')));

        // Este handler é exclusivo para Poupa Tempo
        $entrega = (string)$postoRow['entrega'];
        if (strpos($entrega,'poupa')===false && strpos($entrega,'tempo')===false)
            die(safe_json_odc(array('success'=>false,'erro'=>'Posto '.$posto_cod.' não é Poupa Tempo.')));

        $regional_id = str_pad((string)(int)$postoRow['regional'],3,'0',STR_PAD_LEFT);
        $stmtR = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nome),''),CONCAT('REGIONAL ',LPAD(posto,3,'0'))) AS nome
            FROM ciRegionais WHERE LPAD(posto,3,'0')=? LIMIT 1");
        $stmtR->execute(array($regional_id));
        $regRow = $stmtR->fetch();
        $nome_regional = $regRow ? (string)$regRow['nome'] : 'REGIONAL '.$regional_id;

        $lote_pad = str_pad($lote_cod,8,'0',STR_PAD_LEFT);
        $stmtC = $pdo->prepare("SELECT LPAD(CAST(lote AS CHAR),8,'0') AS lote,
            COALESCE(quantidade,0) AS quantidade, DATE(dataCarga) AS data_carga,
            COALESCE(usuario,'') AS responsavel
            FROM ciPostosCsv WHERE LPAD(CAST(lote AS CHAR),8,'0')=? AND LPAD(posto,3,'0')=?
            ORDER BY id DESC LIMIT 1");
        $stmtC->execute(array($lote_pad,$posto_cod));
        $loteRow = $stmtC->fetch();

        // Verificar se já foi conferido
        $stmtConf = $pdo->prepare("SELECT conf, codbar, conferido_em FROM conferencia_pacotes
            WHERE CAST(nlote AS UNSIGNED)=? AND CAST(nposto AS UNSIGNED)=? AND conf='s'
            ORDER BY conferido_em DESC LIMIT 1");
        $stmtConf->execute(array((int)$lote_pad,(int)$posto_cod));
        $confRow = $stmtConf->fetch();

        // Gravar conferência se ainda não conferido
        $conferido_em_novo = '';
        if (!$confRow) {
            $qtd_ins  = $loteRow ? (int)$loteRow['quantidade'] : 0;
            $data_ins = ($loteRow && !empty($loteRow['data_carga'])) ? $loteRow['data_carga'] : $data;
            $sqlIns = "INSERT INTO conferencia_pacotes
                           (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, conferido_em)
                       VALUES (?,?,?,?,?,?,'s',?,NOW())
                       ON DUPLICATE KEY UPDATE
                           conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar),
                           dataexp=VALUES(dataexp), usuario=VALUES(usuario), conferido_em=NOW()";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute(array(
                (int)$regional_id,
                (int)$lote_pad,
                (int)$posto_cod,
                $data_ins,
                $qtd_ins,
                $codbar,
                $usuarioSessao
            ));
            $conferido_em_novo = $pdo->query("SELECT NOW()")->fetchColumn() ?: date('Y-m-d H:i:s');
        }

        die(safe_json_odc(array(
            'success'       => true,
            'lote'          => $lote_pad,
            'posto'         => $posto_cod,
            'nome_posto'    => (string)$postoRow['nome'],
            'regional'      => $regional_id,
            'nome_regional' => $nome_regional,
            'quantidade'    => $loteRow ? (int)$loteRow['quantidade'] : 0,
            'data_carga'    => $loteRow ? ($loteRow['data_carga'] ?: $data) : $data,
            'responsavel'   => $loteRow ? (string)$loteRow['responsavel'] : '',
            'conf'          => $confRow ? 1 : 0,
            'codbar'        => $confRow ? (string)$confRow['codbar'] : $codbar,
            'conferido_em'  => $confRow ? (string)$confRow['conferido_em'] : $conferido_em_novo,
            'nao_listado'   => !$loteRow,
            'fora_filtro'   => 0,
            'modo_pt'       => true,
        )));
    } catch (\Throwable $ex) {
        die(safe_json_odc(array('success'=>false,'erro'=>'Erro banco PT: '.$ex->getMessage())));
    }
}

/* ─── AJAX: LISTAR LOTES POUPA TEMPO ─── */
if ($pdo && isset($_POST['ajax_listar_lotes_pt_odc'])) {
    header('Content-Type: application/json');
    try {
        $data_ini = normalizar_data_odc($_POST['data_ini'] ?? date('Y-m-d'));
        $data_fim = normalizar_data_odc($_POST['data_fim'] ?? $data_ini);
        if (!$data_ini) $data_ini = date('Y-m-d');
        if (!$data_fim || $data_fim < $data_ini) $data_fim = $data_ini;
        $incluir_pendentes = !empty($_POST['incluir_pendentes']);
        $selectBase = "
            SELECT
                LPAD(CAST(c.lote AS CHAR),8,'0')                                          AS lote,
                LPAD(c.posto,3,'0')                                                        AS posto,
                LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0') AS regional,
                COALESCE(c.quantidade,0)                                                   AS quantidade,
                DATE(c.dataCarga)                                                          AS data_carga,
                COALESCE(c.usuario,'')                                                     AS responsavel,
                COALESCE(NULLIF(TRIM(r.nome),''),CONCAT('POSTO ',LPAD(c.posto,3,'0')))    AS nome_posto,
                COALESCE(NULLIF(TRIM(rr.nome),''),CONCAT('PT REG ',LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0'))) AS nome_regional,
                0                                                                          AS fora_filtro
            FROM ciPostosCsv c
            LEFT JOIN ciRegionais r  ON LPAD(r.posto,3,'0')  = LPAD(c.posto,3,'0')
            LEFT JOIN ciRegionais rr ON LPAD(rr.posto,3,'0') = LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0')
            WHERE DATE(c.dataCarga) BETWEEN ? AND ?
              AND COALESCE(c.quantidade,0) > 0
              AND REPLACE(LOWER(TRIM(COALESCE(r.entrega,''))),' ','') LIKE '%poupatempo%'";
        $params = array($data_ini, $data_fim);
        if ($incluir_pendentes) {
            $selectBase .= "
            UNION ALL
            SELECT
                LPAD(CAST(c.lote AS CHAR),8,'0') AS lote,
                LPAD(c.posto,3,'0') AS posto,
                LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0') AS regional,
                COALESCE(c.quantidade,0) AS quantidade,
                DATE(c.dataCarga) AS data_carga,
                COALESCE(c.usuario,'') AS responsavel,
                COALESCE(NULLIF(TRIM(r.nome),''),CONCAT('POSTO ',LPAD(c.posto,3,'0'))) AS nome_posto,
                COALESCE(NULLIF(TRIM(rr.nome),''),CONCAT('PT REG ',LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0'))) AS nome_regional,
                1 AS fora_filtro
            FROM ciPostosCsv c
            LEFT JOIN ciRegionais r  ON LPAD(r.posto,3,'0')  = LPAD(c.posto,3,'0')
            LEFT JOIN ciRegionais rr ON LPAD(rr.posto,3,'0') = LPAD(CAST(COALESCE(CAST(r.regional AS UNSIGNED),CAST(c.regional AS UNSIGNED)) AS CHAR),3,'0')
            WHERE DATE(c.dataCarga) < ?
              AND DATE(c.dataCarga) >= DATE_SUB(?,INTERVAL 45 DAY)
              AND COALESCE(c.quantidade,0) > 0
              AND REPLACE(LOWER(TRIM(COALESCE(r.entrega,''))),' ','') LIKE '%poupatempo%'";
            $params[] = $data_ini;
            $params[] = $data_ini;
        }
        $stmt = $pdo->prepare($selectBase);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $lotes = array(); $vistos = array();
        foreach ($rows as $row) {
            $k = $row['lote'].'|'.$row['posto'];
            if (isset($vistos[$k])) continue;
            $vistos[$k] = 1;
            $lotes[] = array(
                'lote'         => (string)$row['lote'],
                'posto'        => (string)$row['posto'],
                'regional'     => (string)$row['regional'],
                'quantidade'   => (int)$row['quantidade'],
                'data_carga'   => (string)$row['data_carga'],
                'responsavel'  => (string)$row['responsavel'],
                'nome_posto'   => (string)$row['nome_posto'],
                'nome_regional'=> (string)$row['nome_regional'],
                'fora_filtro'  => (int)$row['fora_filtro'],
                'conf' => 0, 'codbar' => '', 'conferido_em' => '', 'nao_listado' => 0,
            );
        }
        die(safe_json_odc(array('success'=>true,'lotes'=>$lotes,'total'=>count($lotes))));
    } catch (\Throwable $ex) {
        die(safe_json_odc(array('success'=>false,'erro'=>'Erro PT: '.$ex->getMessage())));
    }
}

$usuarioSessao = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
$dataHoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ofício Dinâmico Correios v1.2.2</title>
<style>
*{box-sizing:border-box;}
body{margin:0;font-family:"Trebuchet MS",Tahoma,Verdana,sans-serif;background:#f0f4f8;color:#1a2b3c;display:flex;flex-direction:column;height:100vh;overflow:hidden;}
/* ── TOP SECTION ── */
.top-panels{display:flex;flex:1;overflow:hidden;min-height:0;}
/* ── LEFT PANEL ── */
.painel-conf{flex:none;width:50%;min-width:280px;max-width:75%;display:flex;flex-direction:column;border-right:2px solid #d0dae4;background:#fff;overflow:hidden;resize:horizontal;box-sizing:border-box;}
.painel-conf::-webkit-resizer{background:#d0dae4;}
.cabecalho{background:#1a3a5c;color:#fff;padding:9px 13px;display:flex;align-items:center;gap:9px;flex-wrap:wrap;}
.cabecalho h1{font-size:14px;margin:0;flex:1;}
.badge-v{background:#4fc3f7;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:999px;}
.toolbar{padding:7px 11px;background:#eef3f8;border-bottom:1px solid #d0dae4;display:flex;gap:5px;align-items:center;flex-wrap:wrap;}
.toolbar label{font-size:11px;font-weight:700;color:#3a5068;white-space:nowrap;}
input[type="date"]{padding:3px 6px;border:1px solid #b0c0d0;border-radius:4px;font-size:11px;width:116px;}
.input-scan{flex:1;padding:5px 10px;border:2px solid #4fc3f7;border-radius:7px;font-size:13px;min-width:160px;outline:none;}
.input-scan:focus{border-color:#0288d1;box-shadow:0 0 0 2px rgba(2,136,209,.15);}
.btn{padding:5px 10px;border:none;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-load{background:#0288d1;color:#fff;}
.btn-limpar{background:#b71c1c;color:#fff;}
.btn-sm{padding:2px 6px;font-size:10px;border-radius:3px;border:none;cursor:pointer;font-weight:700;}
.btn-carregar-lote{background:#e65100;color:#fff;}
.status-bar{padding:4px 11px;font-size:11px;min-height:24px;background:#f7fafc;border-bottom:1px solid #d0dae4;}
.status-bar.ok{color:#155724;background:#d4edda;}
.status-bar.err{color:#721c24;background:#f8d7da;}
.status-bar.warn{color:#7d4e00;background:#fff3cd;}
.lista-scroll{flex:1;overflow-y:auto;padding:5px;}
/* ── GROUPS ── */
.regional-bloco{margin-bottom:7px;border:1px solid #cdd8e4;border-radius:7px;overflow:hidden;}
.regional-header{background:#1a3a5c;color:#fff;padding:6px 11px;display:flex;align-items:center;gap:7px;font-size:12px;font-weight:700;cursor:pointer;user-select:none;}
.regional-header .reg-badge{font-size:10px;background:rgba(255,255,255,.2);padding:2px 6px;border-radius:999px;margin-left:auto;white-space:nowrap;}
.regional-header.concluido{background:#1b5e20;}
/* Posto header */
.posto-header{display:flex;align-items:center;gap:5px;padding:4px 9px;background:#f0f6ff;border-bottom:1px solid #dce8f5;font-size:12px;font-weight:700;}
.posto-header.conferido{background:#e8f5e9;}
.posto-header.parcial{background:#fff8e1;}
.posto-check{font-size:12px;color:#2e7d32;min-width:14px;}
.posto-codigo{min-width:30px;color:#1a3a5c;}
.posto-nome{flex:1;}
.posto-qtd{min-width:48px;text-align:right;color:#2c6e49;font-size:11px;}
.posto-pct{min-width:30px;text-align:right;color:#5c7183;font-size:10px;}
/* Lote rows */
.lote-row{padding:3px 9px 3px 22px;border-bottom:1px solid #eef2f7;font-size:11px;}
.lote-row.conf{background:#e8f5e9;}
.lote-row.fora{background:#fff8e1;}
.lote-row.nao-listado{background:#fdecea;}
.lote-row.scan-hl{outline:2px solid #0288d1;background:#e3f2fd!important;}
.lote-info-line{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.lote-num{font-weight:700;min-width:72px;color:#3a5068;font-size:11px;}
.lote-data{color:#888;font-size:10px;min-width:66px;}
.lote-qtd{color:#2c6e49;font-weight:600;min-width:60px;}
.lote-resp{color:#555;font-size:10px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.lote-conf-em{color:#1b5e20;font-size:10px;}
.lote-codbar{color:#aaa;font-size:9px;font-family:monospace;flex:1;}
.lote-flag{font-size:10px;padding:1px 5px;border-radius:3px;font-weight:700;white-space:nowrap;}
.flag-fora{background:#ffe082;color:#7d4e00;}
.flag-nao{background:#ffcdd2;color:#b71c1c;}
.flag-conf{background:#c8e6c9;color:#1b5e20;}
.flag-estante{background:#e3f2fd;color:#0277bd;}
.lote-estante-saiu{color:#1b5e20;font-size:10px;margin-top:1px;}
/* ── RIGHT PANEL ── */
.painel-imp{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#e8edf2;}
.painel-conf.expandido,.painel-imp.expandido{position:fixed!important;inset:0!important;z-index:9000!important;width:100vw!important;max-width:100vw!important;height:100vh!important;resize:none!important;}
.btn-expand-painel{background:transparent;border:none;color:#fff;font-size:13px;cursor:pointer;padding:2px 5px;border-radius:3px;opacity:.8;}
.btn-expand-painel:hover{opacity:1;background:rgba(255,255,255,.15);}
.imp-cabecalho{padding:6px 11px;background:#263238;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;gap:7px;flex-wrap:wrap;}
.imp-scroll{flex:1;overflow-y:auto;padding:9px;display:flex;flex-direction:column;gap:9px;}
/* ── FOLHA DO OFÍCIO ── */
.folha-oficio{background:#fff;border:1px solid #bbb;border-radius:4px;padding:10px 14px;font-family:"Courier New",Courier,monospace;font-size:10px;line-height:1.35;max-width:640px;width:100%;margin:0 auto;box-shadow:0 2px 5px rgba(0,0,0,.08);position:relative;}
/* Cabeçalho estruturado com bordas */
.of-cab-table{width:100%;border-collapse:collapse;margin-bottom:0;}
.of-cab-table td{border:1px solid #333;padding:4px 7px;font-size:10px;line-height:1.5;}
.of-cab-empresa{text-align:center;font-weight:bold;font-size:11px;}
.of-cab-sub{text-align:center;font-weight:bold;font-size:10px;}
.of-cab-info{text-align:left;}
.of-cab-regional{text-align:center;font-weight:bold;font-size:11px;text-transform:uppercase;}
.of-cab-regional .of-data{font-weight:normal;font-size:10px;display:block;text-transform:none;margin-top:2px;}
/* Tabela de dados com bordas completas */
.folha-oficio .of-data-table{width:100%;border-collapse:collapse;margin-top:0;}
.folha-oficio .of-data-table th{border:1px solid #333;text-align:left;font-size:10px;padding:3px 4px;font-weight:bold;}
.folha-oficio .of-data-table th.r{text-align:right;}
.folha-oficio .of-data-table td{border:1px solid #333;font-size:10px;padding:2px 4px;vertical-align:top;}
.folha-oficio .of-data-table td.r{text-align:right;}
.folha-oficio .of-data-table tr.lote-conf td{background:#e8f5e9;}
.folha-oficio .of-footer{border:1px solid #333;border-top:none;padding:3px 5px;display:flex;justify-content:space-between;font-size:10px;font-weight:bold;}
.folha-oficio.of-scan-hl{outline:3px solid #0288d1!important;box-shadow:0 0 14px rgba(2,136,209,.35)!important;transition:outline .1s;}
.btn-del-row{background:none;border:none;color:#b71c1c;cursor:pointer;font-size:11px;padding:0 2px;line-height:1;}
.folha-sel-bar{display:flex;align-items:center;gap:6px;margin-bottom:5px;}
.folha-chk{width:16px;height:16px;cursor:pointer;accent-color:#1b5e20;}
.vazio-conf{padding:22px;text-align:center;color:#90a4ae;font-size:13px;}
/* ── BOTTOM PANEL — LACRES (iframe overlay) ── */
.bottom-panel{position:fixed;bottom:0;left:0;right:0;height:0;display:flex;flex-direction:column;overflow:hidden;transition:height .3s ease;z-index:200;background:#f5f7fa;box-shadow:0 -4px 16px rgba(0,0,0,.25);}
.bottom-panel.aberto{height:65vh;}
.bottom-panel.tela-cheia{height:100vh!important;}
.bottom-cab{background:#455a64;color:#fff;padding:6px 11px;display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;flex-wrap:wrap;flex-shrink:0;}
.bottom-scroll{flex:1;overflow:hidden;padding:0;}
#iframeLacres{width:100%;height:100%;border:none;display:block;}
/* ── MODO ESCURO ── */
body.dark{background:#111114;color:#dde3ec;}
body.dark .painel-conf{background:#16191e;border-right-color:#252d3a;}
body.dark .cabecalho{background:#0b1a2e;}
body.dark .toolbar{background:#1c2028;border-bottom-color:#252d3a;}
body.dark .toolbar label{color:#8a9ab0;}
body.dark input[type="date"]{background:#1c2028;color:#dde3ec;border-color:#2a3040;}
body.dark .input-scan{background:#1c2028;color:#dde3ec;border-color:#2a5065;}
body.dark .input-scan:focus{border-color:#4fc3f7;box-shadow:0 0 0 2px rgba(79,195,247,.15);}
body.dark .status-bar{background:#1c2028;border-bottom-color:#252d3a;color:#8a9ab0;}
body.dark .status-bar.ok{background:#0a2010;color:#81c784;}
body.dark .status-bar.err{background:#2a0d0d;color:#ef9a9a;}
body.dark .status-bar.warn{background:#2a1e00;color:#ffcc80;}
body.dark .lista-scroll{background:#16191e;}
body.dark .regional-bloco{border-color:#252d3a;}
body.dark .regional-header{background:#0e2040;}
body.dark .regional-header.concluido{background:#0a2810;}
body.dark .posto-header{background:#18253a;border-bottom-color:#252d3a;color:#dde3ec;}
body.dark .posto-header.conferido{background:#0a2010;}
body.dark .posto-header.parcial{background:#2a1e00;}
body.dark .posto-check{color:#81c784;}
body.dark .posto-codigo{color:#90caf9;}
body.dark .posto-qtd{color:#81c784;}
body.dark .posto-pct{color:#607080;}
body.dark .lote-row{border-bottom-color:#1e2430;background:#16191e;}
body.dark .lote-row.conf{background:#0a2010;}
body.dark .lote-row.fora{background:#2a1e00;}
body.dark .lote-row.nao-listado{background:#2a0d0d;}
body.dark .lote-row.scan-hl{background:#0a2040!important;outline-color:#4fc3f7;}
body.dark .lote-num{color:#90caf9;}
body.dark .lote-data{color:#607080;}
body.dark .lote-qtd{color:#81c784;}
body.dark .lote-resp{color:#8a9ab0;}
body.dark .lote-conf-em{color:#81c784;}
body.dark .lote-codbar{color:#3a4a5a;}
body.dark .flag-fora{background:#3a2400;color:#ffb74d;}
body.dark .flag-nao{background:#3a0d0d;color:#ef9a9a;}
body.dark .flag-conf{background:#0a2010;color:#81c784;}
body.dark .flag-estante{background:#0a1a2a;color:#90caf9;}
body.dark .painel-imp{background:#0d1015;}
body.dark .imp-cabecalho{background:#0b1a2e;}
body.dark .vazio-conf{color:#3a5068;}
body.dark .folha-oficio{background:#1a1e26;border-color:#2e3a50;box-shadow:0 2px 8px rgba(0,0,0,.5);color:#dde3ec;}
body.dark .of-cab-table td{border-color:#3a4a60;color:#dde3ec;background:#1a1e26;}
body.dark .folha-oficio .of-data-table th{border-color:#3a4a60;color:#dde3ec;background:#1e2430;}
body.dark .folha-oficio .of-data-table td{border-color:#3a4a60;color:#dde3ec;}
body.dark .folha-oficio .of-data-table tr.lote-conf td{background:#0a2010;}
body.dark .folha-oficio .of-footer{border-color:#3a4a60;color:#dde3ec;}
body.dark .bottom-panel{background:#0d1015;}
body.dark .bottom-cab{background:#0b1a2e;}
body.dark .folha-sel-bar label{color:#dde3ec;}
body.dark .btn-del-row{color:#ef9a9a;}
/* Print */
@media print{
  .painel-conf,.bottom-panel{display:none!important;}
  body{display:block!important;height:auto!important;overflow:visible!important;}
  .top-panels{display:block!important;overflow:visible!important;}
  .painel-imp{width:100vw!important;background:#fff!important;overflow:visible!important;display:block!important;}
  .imp-cabecalho{display:none!important;}
  .imp-scroll{padding:0!important;gap:0!important;overflow:visible!important;}
  .folha-oficio{max-width:100%!important;box-shadow:none!important;border:none!important;padding:4px!important;margin:0!important;}
  .folha-oficio.no-print{display:none!important;}
  .folha-sel-bar,.btn-del-row,.of-del-col{display:none!important;}
  .lote-row.fora,.lote-row.nao-listado{background:#fff!important;}
  .folha-oficio tr.lote-conf td{background:#fff!important;}
}
</style>
</head>
<body>

<div class="top-panels">

  <!-- ══ PAINEL ESQUERDO ══ -->
  <div class="painel-conf" id="painelConf">
    <div class="cabecalho">
      <a href="inicio.php" style="color:#90caf9;font-size:11px;text-decoration:none;">&#8592; Início</a>
      <h1>Ofício Dinâmico Correios</h1>
      <span class="badge-v">v2.0.3</span>
      <button class="btn-expand-painel" id="btnExpandConf" title="Tela cheia" onclick="toggleExpandPainel('painelConf',this)">&#8862;</button>
      <button class="btn-expand-painel" title="Abrir em nova janela" onclick="window.open(location.href,'conf_janela','width=820,height=900,resizable=yes,scrollbars=yes')">&#10138;</button>
    </div>
    <div class="toolbar">
      <label>De:</label><input type="date" id="inputDataIni" value="<?php echo e_odc($dataHoje); ?>">
      <label>Até:</label><input type="date" id="inputDataFim" value="<?php echo e_odc($dataHoje); ?>">
      <button class="btn btn-load" id="btnCarregar">Carregar</button>
      <label title="Incluir lotes mais antigos ainda pendentes"><input type="checkbox" id="chkPendentes"> Pendentes</label>
    </div>
    <div class="toolbar" style="padding-top:4px;padding-bottom:4px;">
      <input type="text" class="input-scan" id="inputScan" placeholder="Escaneie ou digite o código de barras (19 dígitos)..." autocomplete="off" autocorrect="off">
      <button class="btn btn-limpar" id="btnLimpar" title="Limpar">✕</button>
    </div>
    <div class="status-bar" id="statusBar">Informe o intervalo e clique em Carregar.</div>
    <div class="lista-scroll" id="listaConferencia">
      <div class="vazio-conf">Nenhum lote carregado.</div>
    </div>
  </div>

  <!-- ══ PAINEL DIREITO (ofícios) ══ -->
  <div class="painel-imp" id="painelImp">
    <div class="imp-cabecalho">
      <span>Ofícios por Regional</span>
      <button class="btn" style="background:#455a64;color:#fff;margin-left:auto;" id="btnCapitalToggle" onclick="toggleCapitalDesmembrado()">&#9634; Desmembrar Capital</button>
      <button class="btn" style="background:#546e7a;color:#fff;" id="btnSelecionarTodos">&#9745; Todos</button>
      <button class="btn" style="background:#37474f;color:#fff;" onclick="abrirPopout()">&#128470; Nova janela</button>
      <button class="btn" style="background:#1b5e20;color:#fff;" onclick="imprimirSelecionados()">&#128438; Imprimir selecionados</button>
      <button class="btn" style="background:#263238;color:#fff;" onclick="toggleExpandPainel('painelImp',this)" title="Tela cheia painel direito">&#8862; Tela cheia</button>
      <button class="btn" style="background:#455a64;color:#fff;" onclick="toggleBottomPanel()">&#9660; Ofício Correios/Lacres</button>
    </div>
    <div class="imp-scroll" id="paineisOficio">
      <div class="vazio-conf">Os ofícios aparecerão aqui conforme a conferência avança.</div>
    </div>
  </div>

</div><!-- top-panels -->

<!-- ══ PAINEL INFERIOR — Ofício Correios c/ Lacres (lacres_novo.php) ══ -->
<div class="bottom-panel" id="bottomPanel">
  <div class="bottom-cab">
    <span>&#128230; Ofício Correios — Lacres IIPR &amp; Etiquetas</span>
    <div style="margin-left:auto;display:flex;gap:5px;align-items:center;">
      <button class="btn-sm" style="background:#0277bd;color:#fff;" onclick="lacresNovaJanela()" title="Abrir em nova janela">&#8663; Nova janela</button>
      <button class="btn-sm" id="btnTelaCheia" style="background:#37474f;color:#fff;" onclick="lacresTelaCheia()" title="Tela inteira">&#9974; Tela cheia</button>
      <button class="btn-sm" style="background:#607d8b;color:#fff;" onclick="toggleBottomPanel()">&#9660; Fechar</button>
    </div>
  </div>
  <div class="bottom-scroll">
    <iframe id="iframeLacres" src="about:blank"></iframe>
  </div>
</div>

<!-- Áudios -->
<audio id="audioConcluido" src="assets/audio/concluido.mp3" preload="auto"></audio>
<audio id="audioOutraRegional" src="assets/audio/pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="audioBeep" src="assets/audio/beep_correio.mp3" preload="auto"></audio>

<script>
var dataHoje = '<?php echo e_odc($dataHoje); ?>';
var usuario  = '<?php echo e_odc($usuarioSessao); ?>';

/* ══ ESTADO ══ */
var estadoLotes    = {};
var estadoLotesPT  = {};  // postos Poupa Tempo (não entram na conferência)
var excluidos      = {};  // { regional: { posto: true } }
var selecionados   = {};  // chave → true/false para impressão (Correios e PT)
var ultimaRegional = '';  // última regional escaneada (para áudio "outra regional")
var audioAtivo     = false;
var capitalDesmembrado = false;  // false=agrupado (padrão), true=página por posto

function chave(lote, posto) { return lote+'|'+posto; }

/* ── DESBLOQUEAR ÁUDIO ── */
document.addEventListener('click', function(){ audioAtivo=true; }, {once:true});
document.addEventListener('keydown', function(){ audioAtivo=true; }, {once:true});

/* ── UTILS ── */
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmt(n){return Number(n||0).toLocaleString('pt-BR');}
function fmtData(s){if(!s)return'';var m=String(s).match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+'/'+m[2]+'/'+m[1]:s;}
function fmtDH(s){if(!s)return'';var m=String(s).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);return m?m[3]+'/'+m[2]+' '+m[4]+':'+m[5]:fmtData(s);}
function tocarAudio(id){if(!audioAtivo)return;try{var a=document.getElementById(id);if(a){a.currentTime=0;a.play().catch(function(){});}}catch(e){}}
function status(msg,tipo){
    var el=document.getElementById('statusBar');if(!el)return;
    el.textContent=msg;
    el.className='status-bar'+(tipo==='ok'?' ok':tipo==='err'?' err':tipo==='warn'?' warn':'');
}

/* ── ORDENAÇÃO ── */
function ordemReg(rid){var n=parseInt(rid,10);if(n===0)return 0;if(n===999)return 1;return 2+n;}
function regionaisOrdenados(regionais){return Object.keys(regionais).sort(function(a,b){return ordemReg(a)-ordemReg(b);});}
function postosOrdenados(postos){return Object.keys(postos).sort(function(a,b){return parseInt(a,10)-parseInt(b,10);});}

/* ── TÍTULO DO OFÍCIO ── */
function tituloReg(rid, nome){
    var n=parseInt(rid,10);
    if(n===0)   return 'CARTEIRAS ENVIADAS A REGIONAL &mdash; CURITIBA';
    if(n===999) return 'CARTEIRAS ENVIADAS A REGIONAL &mdash; REGIONAL METROPOLITANA';
    return 'CARTEIRAS ENVIADAS A REGIONAL &mdash; '+n+' &mdash; '+esc(nome.toUpperCase());
}

/* ── AGRUPAR ── */
function agrupar(){
    var regionais={};
    Object.keys(estadoLotes).forEach(function(k){
        var item=estadoLotes[k];
        var rk=item.regional;
        if(!regionais[rk]) regionais[rk]={id:rk,nome:item.nome_regional,postos:{}};
        var pk=item.posto;
        if(!regionais[rk].postos[pk]) regionais[rk].postos[pk]={id:pk,nome:item.nome_posto,lotes:[]};
        regionais[rk].postos[pk].lotes.push(item);
    });
    return regionais;
}
function agruparPT(){
    var regionais={};
    Object.keys(estadoLotesPT).forEach(function(k){
        var item=estadoLotesPT[k];
        var rk=item.regional;
        if(!regionais[rk]) regionais[rk]={id:rk,nome:item.nome_regional,postos:{}};
        var pk=item.posto;
        if(!regionais[rk].postos[pk]) regionais[rk].postos[pk]={id:pk,nome:item.nome_posto,lotes:[]};
        regionais[rk].postos[pk].lotes.push(item);
    });
    return regionais;
}

/* ── VERIFICAR REGIONAL CONCLUÍDA (áudio) ── */
function verificarRegionalConcluida(regional){
    var reg=agrupar()[regional];
    if(!reg)return;
    var total=0,conf=0;
    Object.keys(reg.postos).forEach(function(pk){
        reg.postos[pk].lotes.forEach(function(l){total++;if(l.conf)conf++;});
    });
    if(total>0&&conf===total) tocarAudio('audioConcluido');
}
function verificarPostoConcluido(regional,postoKey){
    var reg=agrupar()[regional];
    if(!reg)return;
    var posto=reg.postos[postoKey];
    if(!posto)return;
    var total=0,conf=0;
    posto.lotes.forEach(function(l){total++;if(l.conf)conf++;});
    if(total>0&&conf===total) tocarAudio('audioConcluido');
}


/* ══ RENDERIZAR LISTA ESQUERDA ══ */
function renderizar(){
    var regionais=agrupar();
    var rids=regionaisOrdenados(regionais);
    var el=document.getElementById('listaConferencia');
    if(!el)return;
    if(!rids.length){
        el.innerHTML='<div class="vazio-conf">Nenhum lote carregado.</div>';
        renderizarOficios(regionais,agruparPT());
        return;
    }
    var html='';
    rids.forEach(function(rid){
        var reg=regionais[rid];
        var pids=postosOrdenados(reg.postos);
        var totQtd=0,totConf=0,totLotes=0;
        pids.forEach(function(pk){
            reg.postos[pk].lotes.forEach(function(l){totQtd+=l.quantidade;totLotes++;if(l.conf)totConf++;});
        });
        var regConc=(totLotes>0&&totConf===totLotes);
        html+='<div class="regional-bloco">';
        html+='<div class="regional-header'+(regConc?' concluido':'')+'" onclick="toggleBloco(\'rb-'+rid+'\')">';
        html+='<span>'+esc(rid)+' &ndash; '+esc(reg.nome)+'</span>';
        html+='<span class="reg-badge">'+pids.length+' posto(s) | '+fmt(totQtd)+' cart. | '+totConf+'/'+totLotes+' conf.'+(regConc?' ✓':'')+'</span>';
        html+='</div>';
        html+='<div id="rb-'+rid+'">';
        pids.forEach(function(pk){
            var posto=reg.postos[pk];
            var pLotes=posto.lotes.length,pConf=0,pQtd=0;
            posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.conf)pConf++;});
            var pCls=(pConf===pLotes&&pLotes>0)?'posto-header conferido':(pConf>0?'posto-header parcial':'posto-header');
            html+='<div class="'+pCls+'">';
            html+='<span class="posto-check">'+(pConf===pLotes&&pLotes>0?'&#10004;':'&nbsp;')+'</span>';
            html+='<span class="posto-codigo">'+esc(pk)+'</span>';
            html+='<span class="posto-nome">'+esc(posto.nome)+' <small style="color:#888">- RG Nacional</small></span>';
            html+='<span class="posto-qtd">'+fmt(pQtd)+'</span>';
            html+='<span class="posto-pct">'+pLotes+' pct</span>';
            html+='</div>';
            posto.lotes.forEach(function(lot){
                var lCls='lote-row'+(lot.conf?' conf':lot.nao_listado?' nao-listado':lot.fora_filtro?' fora':'');
                var rowId='lr-'+lot.lote+'-'+lot.posto;
                html+='<div class="'+lCls+'" id="'+rowId+'">';
                html+='<div class="lote-info-line">';
                html+='<span class="lote-num">'+esc(lot.lote)+'</span>';
                html+='<span class="lote-data">'+fmtData(lot.data_carga)+'</span>';
                html+='<span class="lote-qtd">'+fmt(lot.quantidade)+' cart.</span>';
                if(lot.responsavel)html+='<span class="lote-resp">&#128100; '+esc(lot.responsavel)+'</span>';
                if(lot.conf){
                    html+='<span class="lote-flag flag-conf">&#10004; conf '+fmtDH(lot.conferido_em)+'</span>';
                } else if(lot.nao_listado){
                    html+='<span class="lote-flag flag-nao">! NL</span>'
                        +'<button class="btn-sm btn-carregar-lote" onclick="carregarLotePendente(\''+esc(lot.lote)+'\',\''+esc(lot.posto)+'\',\''+esc(lot.regional)+'\','+lot.quantidade+',\''+esc(lot.data_carga)+'\')">Carregar</button>';
                } else if(lot.fora_filtro){
                    html+='<span class="lote-flag flag-fora">Pendente</span>';
                    if(lot.data_carga) html+='<span class="lote-flag flag-estante">&#128230; estante desde '+fmtData(lot.data_carga)+'</span>';
                } else {
                    if(lot.data_carga) html+='<span class="lote-flag flag-estante">&#128230; na estante desde '+fmtData(lot.data_carga)+'</span>';
                }
                html+='</div>';
                if(lot.conf && lot.data_carga && lot.conferido_em){
                    html+='<div class="lote-estante-saiu">&#128230; entrou: '+fmtData(lot.data_carga)+' &nbsp;&#8594;&nbsp; &#10003; saiu: '+fmtDH(lot.conferido_em)+'</div>';
                }
                if(lot.codbar)
                    html+='<div class="lote-codbar">'+esc(lot.codbar)+'</div>';
                html+='</div>';
            });
        });
        html+='</div></div>';
    });
    el.innerHTML=html;
    renderizarOficios(regionais,agruparPT());
}

function toggleBloco(id){var el=document.getElementById(id);if(el)el.style.display=(el.style.display==='none')?'':'none';}

/* ══ RENDERIZAR OFÍCIOS (direita) ══ */
/* v1.1.12: Capital (999) toggle agrupado/desmembrado; posto 001 sempre página própria
            Poupa Tempo: seção separada com cabeçalho próprio, agrupado por regional PT */
function _montarCabecalhoFolha(titulo, periodoStr){
    return '<table class="of-cab-table">'
        +'<tr><td class="of-cab-empresa">Celepar - Tecnologia da Informa&ccedil;&atilde;o e Comunica&ccedil;&atilde;o do Paran&aacute;</td></tr>'
        +'<tr><td class="of-cab-sub">COMPROVANTE DE ENTREGA DE SERVI&Ccedil;OS</td></tr>'
        +'<tr><td class="of-cab-info"><b>CLIENTE:</b> DEPARTAMENTO DA POL&Iacute;CIA CIVIL DO PARAN&Aacute; - IIPR<br>'
        +'<b>ENDERE&Ccedil;O:</b> RUA PEDRO IVO, 386 - CEP 80010-020<br>'
        +'CENTRO - CURITIBA - PARAN&Aacute;<br>'
        +'<b>SISTEMA:</b> RG NACIONAL &nbsp;&nbsp; <b>SETOR:</b> MALOTES</td></tr>'
        +'<tr><td class="of-cab-regional">'+titulo+'<span class="of-data">Expedido em: '+esc(periodoStr)+'</span></td></tr>'
        +'</table>';
}
function _montarCabecalhoFolhaPT(titulo, periodoStr){
    return '<table class="of-cab-table">'
        +'<tr><td class="of-cab-empresa">Celepar - Tecnologia da Informa&ccedil;&atilde;o e Comunica&ccedil;&atilde;o do Paran&aacute;</td></tr>'
        +'<tr><td class="of-cab-sub">POUPA TEMPO &mdash; COMPROVANTE DE ENTREGA DE SERVI&Ccedil;OS</td></tr>'
        +'<tr><td class="of-cab-info"><b>SISTEMA:</b> RG NACIONAL &nbsp;&nbsp; <b>SETOR:</b> MALOTES</td></tr>'
        +'<tr><td class="of-cab-regional">'+titulo+'<span class="of-data">Expedido em: '+esc(periodoStr)+'</span></td></tr>'
        +'</table>';
}
/* chave = identificador sem prefixo "folha-"; elemento fica id="folha-<chave>" */
function _montarFolha(chave, isSel, cabecalho, linhasHtml, totalPosArr, totalQtd){
    var h='<div class="folha-oficio'+(isSel?'':' no-print')+'" id="folha-'+chave+'">';
    h+='<div class="folha-sel-bar">';
    h+='<input type="checkbox" class="folha-chk" id="chk-'+chave+'" '+(isSel?'checked':'')+' onchange="toggleSelecionado(\''+chave+'\')">';
    h+='<label for="chk-'+chave+'" style="font-size:11px;cursor:pointer;">Incluir na impressão</label>';
    h+='</div>';
    h+=cabecalho;
    h+='<table class="of-data-table"><thead><tr><th>Postos</th><th class="r">Quantidade</th><th class="r">Pct</th><th style="width:18px;border:none;background:transparent;"></th></tr></thead>';
    h+='<tbody>'+linhasHtml+'</tbody></table>';
    h+='<div class="of-footer"><span>Postos: '+totalPosArr+'</span><span>Carteiras: '+fmt(totalQtd)+'</span></div>';
    h+='</div>';
    return h;
}
function renderizarOficios(regionais, regionaisPT){
    var el=document.getElementById('paineisOficio');if(!el)return;
    regionaisPT=regionaisPT||{};
    var rids=regionaisOrdenados(regionais);
    var ridsPT=regionaisOrdenados(regionaisPT);
    if(!rids.length&&!ridsPT.length){
        el.innerHTML='<div class="vazio-conf">Os ofícios aparecerão aqui conforme a conferência avança.</div>';
        return;
    }
    var html='';

    /* ── CORREIOS ── */
    rids.forEach(function(rid){
        var reg=regionais[rid];
        var pids=postosOrdenados(reg.postos);
        var n=parseInt(rid,10);

        if(n===0){
            if(capitalDesmembrado){
                /* DESMEMBRADO: cada posto na sua própria folha */
                pids.forEach(function(pid){
                    if(excluidos[rid]&&excluidos[rid][pid])return;
                    var posto=reg.postos[pid];
                    var pQtd=0,pLotes=posto.lotes.length,pConf=0,datasPos=[];
                    posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.conf)pConf++;if(l.data_carga)datasPos.push(l.data_carga);});
                    datasPos.sort();
                    var dMin=fmtData(datasPos[0]||''),dMax=fmtData(datasPos[datasPos.length-1]||'');
                    var periodoStr=(dMin&&dMax)?(dMin===dMax?dMin:dMin+' a '+dMax):'';
                    var allConf=(pConf===pLotes&&pLotes>0);
                    var cv=rid+'_'+pid;
                    var isSel=selecionados[cv]!==false;
                    var titulo='CARTEIRAS ENVIADAS A REGIONAL &mdash; '+parseInt(pid,10)+' &mdash; '+esc(posto.nome.toUpperCase());
                    var lHtml='<tr'+(allConf?' class="lote-conf"':'')+'><td>'+parseInt(pid,10)+' - '+esc(posto.nome)+' - RG Nacional</td>'
                        +'<td class="r">'+fmt(pQtd)+'</td><td class="r">'+pLotes+'</td>'
                        +'<td style="width:18px;"><button class="btn-del-row" onclick="excluirLinha(\''+rid+'\',\''+pid+'\')" title="Excluir">&#10005;</button></td></tr>';
                    html+=_montarFolha(cv,isSel,_montarCabecalhoFolha(titulo,periodoStr),lHtml,1,pQtd);
                });
            } else {
                /* AGRUPADO: posto 001 = página própria; demais capital = página única */
                var postos001=[],postosResto=[];
                pids.forEach(function(pid){
                    if(excluidos[rid]&&excluidos[rid][pid])return;
                    if(parseInt(pid,10)===1){postos001.push(pid);}else{postosResto.push(pid);}
                });
                postos001.forEach(function(pid){
                    var posto=reg.postos[pid];
                    var pQtd=0,pLotes=posto.lotes.length,pConf=0,datasPos=[];
                    posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.conf)pConf++;if(l.data_carga)datasPos.push(l.data_carga);});
                    datasPos.sort();
                    var dMin=fmtData(datasPos[0]||''),dMax=fmtData(datasPos[datasPos.length-1]||'');
                    var periodoStr=(dMin&&dMax)?(dMin===dMax?dMin:dMin+' a '+dMax):'';
                    var allConf=(pConf===pLotes&&pLotes>0);
                    var cv=rid+'_001';
                    var isSel=selecionados[cv]!==false;
                    var titulo='CARTEIRAS ENVIADAS A REGIONAL &mdash; 1 &mdash; '+esc(posto.nome.toUpperCase());
                    var lHtml='<tr'+(allConf?' class="lote-conf"':'')+'><td>'+parseInt(pid,10)+' - '+esc(posto.nome)+' - RG Nacional</td>'
                        +'<td class="r">'+fmt(pQtd)+'</td><td class="r">'+pLotes+'</td>'
                        +'<td style="width:18px;"><button class="btn-del-row" onclick="excluirLinha(\''+rid+'\',\''+pid+'\')" title="Excluir">&#10005;</button></td></tr>';
                    html+=_montarFolha(cv,isSel,_montarCabecalhoFolha(titulo,periodoStr),lHtml,1,pQtd);
                });
                if(postosResto.length){
                    var tPA=[],tQtd=0,datasR=[],lHtml='';
                    postosResto.forEach(function(pid){
                        var posto=reg.postos[pid];
                        var pQtd=0,pLotes=posto.lotes.length,pConf=0;
                        posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.conf)pConf++;if(l.data_carga)datasR.push(l.data_carga);});
                        tQtd+=pQtd;
                        var allConf=(pConf===pLotes&&pLotes>0);
                        lHtml+='<tr'+(allConf?' class="lote-conf"':'')+'><td>'+parseInt(pid,10)+' - '+esc(posto.nome)+' - RG Nacional</td>'
                            +'<td class="r">'+fmt(pQtd)+'</td><td class="r">'+pLotes+'</td>'
                            +'<td style="width:18px;"><button class="btn-del-row" onclick="excluirLinha(\''+rid+'\',\''+pid+'\')" title="Excluir">&#10005;</button></td></tr>';
                        tPA.push(pid);
                    });
                    datasR.sort();
                    var dMin=fmtData(datasR[0]||''),dMax=fmtData(datasR[datasR.length-1]||'');
                    var periodoStr=(dMin&&dMax)?(dMin===dMax?dMin:dMin+' a '+dMax):'';
                    var isSel=selecionados[rid]!==false;
                    html+=_montarFolha(rid,isSel,_montarCabecalhoFolha(tituloReg(rid,reg.nome),periodoStr),lHtml,tPA.length,tQtd);
                }
            }
        } else {
            /* DEMAIS REGIONAIS CORREIOS: uma folha por regional */
            var tPA=[],tQtd=0,datasR=[],lHtml='';
            pids.forEach(function(pid){
                if(excluidos[rid]&&excluidos[rid][pid])return;
                var posto=reg.postos[pid];
                var pQtd=0,pLotes=posto.lotes.length,pConf=0;
                posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.conf)pConf++;if(l.data_carga)datasR.push(l.data_carga);});
                tQtd+=pQtd;
                var allConf=(pConf===pLotes&&pLotes>0);
                lHtml+='<tr'+(allConf?' class="lote-conf"':'')+'><td>'+parseInt(pid,10)+' - '+esc(posto.nome)+' - RG Nacional</td>'
                    +'<td class="r">'+fmt(pQtd)+'</td><td class="r">'+pLotes+'</td>'
                    +'<td style="width:18px;"><button class="btn-del-row" onclick="excluirLinha(\''+rid+'\',\''+pid+'\')" title="Excluir">&#10005;</button></td></tr>';
                tPA.push(pid);
            });
            if(!tPA.length)return;
            datasR.sort();
            var dMin=fmtData(datasR[0]||''),dMax=fmtData(datasR[datasR.length-1]||'');
            var periodoStr=(dMin&&dMax)?(dMin===dMax?dMin:dMin+' a '+dMax):'';
            var isSel=selecionados[rid]!==false;
            html+=_montarFolha(rid,isSel,_montarCabecalhoFolha(tituloReg(rid,reg.nome),periodoStr),lHtml,tPA.length,tQtd);
        }
    });

    /* ── POUPA TEMPO: uma folha por regional PT ── */
    ridsPT.forEach(function(rid){
        var reg=regionaisPT[rid];
        var pids=postosOrdenados(reg.postos);
        var tPA=[],tQtd=0,datasR=[],lHtml='';
        pids.forEach(function(pid){
            var posto=reg.postos[pid];
            var pQtd=0,pLotes=posto.lotes.length;
            posto.lotes.forEach(function(l){pQtd+=l.quantidade;if(l.data_carga)datasR.push(l.data_carga);});
            tQtd+=pQtd;
            lHtml+='<tr><td>'+parseInt(pid,10)+' - '+esc(posto.nome)+' - Poupa Tempo</td>'
                +'<td class="r">'+fmt(pQtd)+'</td><td class="r">'+pLotes+'</td><td></td></tr>';
            tPA.push(pid);
        });
        if(!tPA.length)return;
        datasR.sort();
        var dMin=fmtData(datasR[0]||''),dMax=fmtData(datasR[datasR.length-1]||'');
        var periodoStr=(dMin&&dMax)?(dMin===dMax?dMin:dMin+' a '+dMax):'';
        var cv='pt_'+rid;
        var isSel=selecionados[cv]!==false;
        var tituloP='POUPA TEMPO &mdash; REGIONAL '+parseInt(rid,10)+' &mdash; '+esc(reg.nome.toUpperCase());
        html+=_montarFolha(cv,isSel,_montarCabecalhoFolhaPT(tituloP,periodoStr),lHtml,tPA.length,tQtd);
    });

    if(!html)html='<div class="vazio-conf">Nenhum ofício para exibir.</div>';
    el.innerHTML=html;
}

/* ── SELEÇÃO PARA IMPRESSÃO ── */
function toggleSelecionado(rid){
    var chk=document.getElementById('chk-'+rid);
    var folha=document.getElementById('folha-'+rid);
    selecionados[rid]=chk?chk.checked:true;
    if(folha)folha.classList.toggle('no-print',!selecionados[rid]);
}
document.getElementById('btnSelecionarTodos').addEventListener('click',function(){
    var todos=document.querySelectorAll('.folha-chk');
    var algumDesmarcado=false;
    todos.forEach(function(c){if(!c.checked)algumDesmarcado=true;});
    todos.forEach(function(c){
        c.checked=algumDesmarcado;
        var rid=c.id.replace('chk-','');
        selecionados[rid]=algumDesmarcado;
        var folha=document.getElementById('folha-'+rid);
        if(folha)folha.classList.toggle('no-print',!algumDesmarcado);
    });
});

/* ── EXCLUIR LINHA ── */
function excluirLinha(rid,pid){
    if(!excluidos[rid])excluidos[rid]={};
    excluidos[rid][pid]=true;
    renderizarOficios(agrupar(),agruparPT());
}

/* ── CAPITAL: AGRUPAR / DESMEMBRAR ── */
function toggleCapitalDesmembrado(){
    capitalDesmembrado=!capitalDesmembrado;
    var btn=document.getElementById('btnCapitalToggle');
    if(btn) btn.textContent=capitalDesmembrado?'\u25A6 Agrupar Capital':'\u25A6 Desmembrar Capital';
    renderizarOficios(agrupar(),agruparPT());
}

/* ── CARREGAR LOTES POUPA TEMPO ── */
function carregarLotesPT(){
    var dataIni=document.getElementById('inputDataIni').value;
    var dataFim=document.getElementById('inputDataFim').value||dataIni;
    if(!dataIni)return;
    var pend=document.getElementById('chkPendentes').checked;
    var fd=new FormData();
    fd.append('ajax_listar_lotes_pt_odc','1');
    fd.append('data_ini',dataIni);fd.append('data_fim',dataFim);
    if(pend)fd.append('incluir_pendentes','1');
    fetch('oficio_dinamico_correios.php',{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(resp){
            if(!resp.success)return;
            estadoLotesPT={};
            if(!resp.lotes||!resp.lotes.length){renderizarOficios(agrupar(),agruparPT());return;}
            resp.lotes.forEach(function(item){
                estadoLotesPT[chave(item.lote,item.posto)]=item;
                if(!selecionados['pt_'+item.regional])selecionados['pt_'+item.regional]=true;
            });
            renderizarOficios(agrupar(),agruparPT());
        })
        .catch(function(){});
}

/* ── IMPRIMIR SELECIONADOS (pop-out) ── */
function imprimirSelecionados(){
    var el=document.getElementById('paineisOficio');if(!el)return;
    var folhas=el.querySelectorAll('.folha-oficio:not(.no-print)');
    if(!folhas.length){status('Nenhum ofício selecionado para impressão.','warn');return;}
    var conteudo='';
    folhas.forEach(function(f){conteudo+=f.outerHTML;});
    // Título dinâmico para que o diálogo de impressão/salvar PDF sugira nome correto
    var dataIni=document.getElementById('inputDataIni')?document.getElementById('inputDataIni').value:'';
    var dataLabel=dataIni?dataIni.replace(/-/g,''):'';
    var nomeArquivo='Oficios_Correios'+(dataLabel?'_'+dataLabel:'');
    var w=window.open('','_blank');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'+nomeArquivo+'</title>'
        +'<style>body{font-family:"Courier New",monospace;padding:10px;}'
        +'.folha-oficio{padding:6px;page-break-after:always;}'
        +'.of-cab-table{width:100%;border-collapse:collapse;}'
        +'.of-cab-table td{border:1px solid #333;padding:4px 7px;font-size:10px;line-height:1.5;}'
        +'.of-cab-empresa{text-align:center;font-weight:bold;font-size:11px;}'
        +'.of-cab-sub{text-align:center;font-weight:bold;font-size:10px;}'
        +'.of-cab-info{text-align:left;}'
        +'.of-cab-regional{text-align:center;font-weight:bold;font-size:11px;text-transform:uppercase;}'
        +'.of-cab-regional .of-data{font-weight:normal;font-size:10px;display:block;text-transform:none;}'
        +'.of-data-table{width:100%;border-collapse:collapse;}'
        +'.of-data-table th{border:1px solid #333;font-size:10px;padding:3px 4px;text-align:left;font-weight:bold;}'
        +'.of-data-table th.r{text-align:right;}.of-data-table td{border:1px solid #333;font-size:10px;padding:2px 4px;}'
        +'.of-data-table td.r{text-align:right;}'
        +'.of-footer{border:1px solid #333;border-top:none;padding:3px 5px;display:flex;justify-content:space-between;font-size:10px;font-weight:bold;}'
        +'.folha-sel-bar,.btn-del-row,.of-del-col{display:none!important;}'
        +'</style></head><body>'+conteudo+'</body></html>');
    w.document.close();
    setTimeout(function(){w.print();},400);
}

/* ── POP-OUT TODOS ── */
function abrirPopout(){
    var el=document.getElementById('paineisOficio');if(!el)return;
    var w=window.open('','oficios_popout','width=740,height=880');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ofícios Correios</title>'
        +'<style>body{font-family:"Courier New",monospace;padding:10px;background:#e8edf2;}'
        +'.folha-oficio{background:#fff;border:1px solid #bbb;padding:6px;margin:0 auto 12px;max-width:640px;page-break-after:always;}'
        +'.of-cab-table{width:100%;border-collapse:collapse;}'
        +'.of-cab-table td{border:1px solid #333;padding:4px 7px;font-size:10px;line-height:1.5;}'
        +'.of-cab-empresa{text-align:center;font-weight:bold;font-size:11px;}'
        +'.of-cab-sub{text-align:center;font-weight:bold;font-size:10px;}'
        +'.of-cab-info{text-align:left;}'
        +'.of-cab-regional{text-align:center;font-weight:bold;font-size:11px;text-transform:uppercase;}'
        +'.of-cab-regional .of-data{font-weight:normal;font-size:10px;display:block;text-transform:none;}'
        +'.of-data-table{width:100%;border-collapse:collapse;}'
        +'.of-data-table th{border:1px solid #333;font-size:10px;padding:3px 4px;text-align:left;font-weight:bold;}'
        +'.of-data-table th.r{text-align:right;}.of-data-table td{border:1px solid #333;font-size:10px;padding:2px 4px;}'
        +'.of-data-table td.r{text-align:right;}'
        +'.of-footer{border:1px solid #333;border-top:none;padding:3px 5px;display:flex;justify-content:space-between;font-size:10px;font-weight:bold;}'
        +'.folha-sel-bar,.btn-del-row,.of-del-col{display:none!important;}'
        +'@media print{body{background:#fff;}}'
        +'</style></head><body>'+el.innerHTML
        +'<p style="text-align:center;"><button onclick="window.print()" style="padding:6px 14px;background:#1b5e20;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:700;">&#128438; Imprimir</button></p>'
        +'</body></html>');
    w.document.close();
}

/* ══ TERCEIRO PAINEL — LACRES (iframe lacres_novo.php) ══ */
function atualizarIframeLacres(){
    var dataIni=document.getElementById('inputDataIni').value;
    var dataFim=document.getElementById('inputDataFim').value||dataIni;
    if(!dataIni)return;
    var datas=[];
    var d=new Date(dataIni+'T00:00:00');
    var fim=new Date((dataFim||dataIni)+'T00:00:00');
    while(d<=fim){
        datas.push(d.toISOString().substring(0,10));
        d.setDate(d.getDate()+1);
    }
    var url='lacres_novo.php?'+datas.map(function(dt){return 'datas[]='+encodeURIComponent(dt);}).join('&');
    var iframe=document.getElementById('iframeLacres');
    if(iframe && iframe.src.indexOf(url)===-1){
        iframe.src=url;
    }
}

/* ── PAINEL INFERIOR ── */
function toggleBottomPanel(){
    var p=document.getElementById('bottomPanel');
    var abrindo=!p.classList.contains('aberto');
    p.classList.remove('tela-cheia');
    p.classList.toggle('aberto');
    document.getElementById('btnTelaCheia').textContent='\u25B3 Tela cheia';
    if(abrindo) atualizarIframeLacres();
}
function lacresTelaCheia(){
    var p=document.getElementById('bottomPanel');
    var btn=document.getElementById('btnTelaCheia');
    var ehCheia=p.classList.contains('tela-cheia');
    if(ehCheia){
        p.classList.remove('tela-cheia');
        btn.textContent='\u25BD Tela cheia';
    } else {
        p.classList.add('aberto');
        p.classList.add('tela-cheia');
        btn.textContent='\u25B3 Restaurar';
        atualizarIframeLacres();
    }
}
function lacresNovaJanela(){
    var dataIni=document.getElementById('inputDataIni').value;
    var dataFim=document.getElementById('inputDataFim').value||dataIni;
    if(!dataIni){window.open('lacres_novo.php','_blank');return;}
    var datas=[];
    var d=new Date(dataIni+'T00:00:00');
    var fim=new Date((dataFim||dataIni)+'T00:00:00');
    while(d<=fim){datas.push(d.toISOString().substring(0,10));d.setDate(d.getDate()+1);}
    var url='lacres_novo.php?'+datas.map(function(dt){return 'datas[]='+encodeURIComponent(dt);}).join('&');
    window.open(url,'lacres_janela','width=1100,height=820,resizable=yes,scrollbars=yes');
}

/* ══ CARREGAR LOTES ══ */
function carregarLotes(){
    var dataIni=document.getElementById('inputDataIni').value;
    var dataFim=document.getElementById('inputDataFim').value||dataIni;
    var pend=document.getElementById('chkPendentes').checked;
    if(!dataIni){status('Informe a data inicial.','err');return;}
    status('Carregando lotes de '+fmtData(dataIni)+' a '+fmtData(dataFim)+'...','');
    var fd=new FormData();
    fd.append('ajax_listar_lotes_odc','1');
    fd.append('data_ini',dataIni);fd.append('data_fim',dataFim);
    if(pend)fd.append('incluir_pendentes','1');
    fetch('oficio_dinamico_correios.php',{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(resp){
            if(!resp.success){status(resp.erro||'Falha.','err');return;}
            estadoLotes={};estadoLotesPT={};excluidos={};selecionados={};ultimaRegional='';
            if(!resp.lotes||!resp.lotes.length){
                status('Nenhum lote de Correios no período.','err');
                renderizar();carregarLotesPT();return;
            }
            resp.lotes.forEach(function(item){
                estadoLotes[chave(item.lote,item.posto)]=item;
                if(!selecionados[item.regional])selecionados[item.regional]=true;
            });
            var conf=resp.lotes.filter(function(l){return l.conf;}).length;
            status('Carregados '+resp.total+' lote(s)'+(conf?' ('+conf+' já conferidos)':'')+'. Escaneie para confirmar.','ok');
            renderizar();
            atualizarIframeLacres();
            carregarLotesPT();
        })
        .catch(function(err){status('Falha: '+(err&&err.message?err.message:'sem conexão.'),'err');});
}

/* ══ LER CÓDIGO DE BARRAS ══ */
function lerCodigo(valor){
    var limpo=valor.replace(/\D/g,'');
    if(limpo.length<17||limpo.length>19){if(limpo.length>0)status('Código inválido: '+limpo.length+' dígitos.','err');return;}

    var dataIni=document.getElementById('inputDataIni').value||dataHoje;

    // === ATUALIZAÇÃO IMEDIATA (antes do AJAX) ===
    var marcadoLocalmente=false;
    var ehPT=false;
    if(limpo.length===19){
        var loteParsed=limpo.substr(0,8);
        var postoParsed=limpo.substr(11,3);
        var kLocal=chave(loteParsed,postoParsed);

        // Verificar primeiro em estadoLotesPT (Poupa Tempo)
        if(estadoLotesPT[kLocal]){
            ehPT=true;
            var stPT=estadoLotesPT[kLocal];
            if(stPT.conf){
                status('PT — Lote '+loteParsed+' ('+stPT.nome_posto+') já conferido em '+fmtDH(stPT.conferido_em)+'.','ok');
            } else {
                stPT.conf=1;
                stPT.codbar=limpo;
                stPT.conferido_em='';
                if(!stPT.nao_listado) status('✔ PT — Lote '+loteParsed+' — '+stPT.nome_posto+' — confirmado!','ok');
                tocarAudio('audioBeep');
            }
            renderizarOficios(agrupar(),agruparPT());
            marcadoLocalmente=true;
        } else if(estadoLotes[kLocal]){
            var st=estadoLotes[kLocal];
            if(st.conf){
                status('Lote '+loteParsed+' ('+st.nome_posto+') já conferido em '+fmtDH(st.conferido_em)+'.','ok');
            } else {
                st.conf=1;
                st.codbar=limpo;
                st.conferido_em='';
                if(!st.nao_listado) status('✔ Lote '+loteParsed+' — '+st.nome_posto+' — confirmado!','ok');
                tocarAudio('audioBeep');
                verificarRegionalConcluida(st.regional);
                verificarPostoConcluido(st.regional,postoParsed);
            }
            renderizar();
            scrollParaLote(loteParsed,postoParsed);
            scrollParaOficio(st.regional,postoParsed);
            marcadoLocalmente=true;
        }
    }
    // === FIM ATUALIZAÇÃO IMEDIATA ===

    if(!marcadoLocalmente) status('Buscando lote...','');

    if(ehPT){
        // Lote encontrado localmente em estadoLotesPT — confirmar via handler PT
        var fdPT=new FormData();
        fdPT.append('ajax_buscar_lote_pt_odc','1');fdPT.append('codbar',limpo);fdPT.append('data',dataIni);
        fetch('oficio_dinamico_correios.php',{method:'POST',body:fdPT})
            .then(function(r){return r.json();})
            .then(function(resp){
                if(!resp.success){status(resp.erro||'Falha PT.','err');return;}
                var k=chave(resp.lote,resp.posto);
                if(estadoLotesPT[k]){
                    if(resp.conferido_em) estadoLotesPT[k].conferido_em=resp.conferido_em;
                    if(resp.codbar) estadoLotesPT[k].codbar=resp.codbar;
                    if(!estadoLotesPT[k].conf && resp.conf) estadoLotesPT[k].conf=1;
                } else {
                    estadoLotesPT[k]={
                        lote:resp.lote,posto:resp.posto,regional:resp.regional,
                        nome_posto:resp.nome_posto,nome_regional:resp.nome_regional,
                        quantidade:resp.quantidade,conf:1,
                        data_carga:resp.data_carga,responsavel:resp.responsavel||'',
                        codbar:resp.codbar||limpo,conferido_em:resp.conferido_em||'',
                        fora_filtro:0,nao_listado:resp.nao_listado?1:0
                    };
                }
                renderizarOficios(agrupar(),agruparPT());
            })
            .catch(function(err){status('Falha PT: '+(err&&err.message?err.message:'sem conexão.'),'err');});
        return;
    }

    // Tentar primeiro como Correios; se servidor rejeitar como PT, tentar handler PT
    var fd=new FormData();
    fd.append('ajax_buscar_lote_odc','1');fd.append('codbar',limpo);fd.append('data',dataIni);
    fetch('oficio_dinamico_correios.php',{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(resp){
            // Se servidor identificou como PT, redirecionar para handler PT
            if(!resp.success && resp.erro && resp.erro.indexOf('Poupa Tempo')!==-1){
                var fdPT2=new FormData();
                fdPT2.append('ajax_buscar_lote_pt_odc','1');fdPT2.append('codbar',limpo);fdPT2.append('data',dataIni);
                return fetch('oficio_dinamico_correios.php',{method:'POST',body:fdPT2})
                    .then(function(r2){return r2.json();})
                    .then(function(resp2){
                        if(!resp2.success){status(resp2.erro||'Falha PT.','err');return;}
                        var k=chave(resp2.lote,resp2.posto);
                        if(!estadoLotesPT[k]){
                            estadoLotesPT[k]={
                                lote:resp2.lote,posto:resp2.posto,regional:resp2.regional,
                                nome_posto:resp2.nome_posto,nome_regional:resp2.nome_regional,
                                quantidade:resp2.quantidade,conf:resp2.conf?1:0,
                                data_carga:resp2.data_carga,responsavel:resp2.responsavel||'',
                                codbar:resp2.codbar||limpo,conferido_em:resp2.conferido_em||'',
                                fora_filtro:0,nao_listado:resp2.nao_listado?1:0
                            };
                        } else {
                            if(resp2.conferido_em) estadoLotesPT[k].conferido_em=resp2.conferido_em;
                            if(resp2.codbar) estadoLotesPT[k].codbar=resp2.codbar;
                            estadoLotesPT[k].conf=1;
                        }
                        if(!marcadoLocalmente){
                            if(resp2.conf && !resp2.nao_listado){
                                status('PT já conferido — '+resp2.nome_posto+'.','ok');
                            } else if(!resp2.nao_listado){
                                status('✔ PT — Lote '+resp2.lote+' — '+resp2.nome_posto+' — confirmado!','ok');
                                tocarAudio('audioBeep');
                            } else {
                                status('✔ PT — Lote '+resp2.lote+' ('+resp2.nome_posto+') — não listado, mas gravado.','warn');
                                tocarAudio('audioBeep');
                            }
                        }
                        renderizarOficios(agrupar(),agruparPT());
                    });
            }

            if(!resp.success){
                if(!marcadoLocalmente) status(resp.erro||'Falha.','err');
                return;
            }

            // Áudio "outra regional"
            if(ultimaRegional && resp.regional !== ultimaRegional){
                tocarAudio('audioOutraRegional');
                status('⚠ Lote '+resp.lote+' pertence à regional '+resp.regional+' ('+resp.nome_regional+') — você está conferindo a regional '+ultimaRegional+'!','warn');
            }
            ultimaRegional = resp.regional;

            var k=chave(resp.lote,resp.posto);
            if(!estadoLotes[k]){
                // Lote novo (não estava carregado ainda)
                estadoLotes[k]={
                    lote:resp.lote,posto:resp.posto,regional:resp.regional,
                    nome_posto:resp.nome_posto,nome_regional:resp.nome_regional,
                    quantidade:resp.quantidade,conf:resp.conf?1:0,
                    data_carga:resp.data_carga,responsavel:resp.responsavel||'',
                    codbar:resp.codbar||limpo,conferido_em:resp.conferido_em||'',
                    fora_filtro:resp.fora_filtro||0,nao_listado:resp.nao_listado?1:0
                };
                if(!selecionados[resp.regional])selecionados[resp.regional]=true;
                if(!marcadoLocalmente){
                    if(estadoLotes[k].conf){
                        status('Lote '+resp.lote+' ('+resp.nome_posto+') já conferido em '+fmtDH(resp.conferido_em)+'.','ok');
                    } else {
                        estadoLotes[k].conf=1;
                        estadoLotes[k].conferido_em=resp.conferido_em||'';
                        estadoLotes[k].codbar=resp.codbar||limpo;
                        if(!resp.nao_listado) status('✔ Lote '+resp.lote+' — '+resp.nome_posto+' — confirmado!','ok');
                        tocarAudio('audioBeep');
                        verificarRegionalConcluida(resp.regional);
                        verificarPostoConcluido(resp.regional,resp.posto);
                    }
                    renderizar();
                    scrollParaLote(resp.lote,resp.posto);
                    scrollParaOficio(resp.regional,resp.posto);
                }
            } else {
                // Lote já existente: atualizar com dados definitivos do servidor
                if(!estadoLotes[k].conf && resp.conf) estadoLotes[k].conf=1;
                if(resp.conferido_em) estadoLotes[k].conferido_em=resp.conferido_em;
                if(resp.codbar) estadoLotes[k].codbar=resp.codbar;
                if(resp.nome_posto) estadoLotes[k].nome_posto=resp.nome_posto;
                if(resp.responsavel) estadoLotes[k].responsavel=resp.responsavel;
                renderizar(); // atualiza timestamp de conferência
            }
        })
        .catch(function(err){
            if(!marcadoLocalmente) status('Falha: '+(err&&err.message?err.message:'sem conexão.'),'err');
        });
}

/* ── SCROLL PARA LOTE ── */
function scrollParaLote(lote,posto){
    setTimeout(function(){
        var row=document.getElementById('lr-'+lote+'-'+posto);
        if(!row)return;
        row.scrollIntoView({behavior:'smooth',block:'center'});
        row.classList.add('scan-hl');
        setTimeout(function(){row.classList.remove('scan-hl');},2000);
    },80);
}

/* ── CARREGAR LOTE NÃO LISTADO ── */
function carregarLotePendente(lote,posto,regional,qtd,data_carga){
    status('Registrando lote '+lote+'...','');
    var fd=new FormData();
    fd.append('ajax_carregar_lote_odc','1');fd.append('lote',lote);fd.append('posto',posto);
    fd.append('regional',regional);fd.append('quantidade',qtd);fd.append('data_carga',data_carga);
    fetch('oficio_dinamico_correios.php',{method:'POST',body:fd})
        .then(function(r){return r.json();})
        .then(function(resp){
            if(!resp.success){status(resp.erro||'Falha.','err');return;}
            var k=chave(lote,posto);if(estadoLotes[k])estadoLotes[k].nao_listado=0;
            status(resp.msg||'Lote registrado.','ok');renderizar();
        })
        .catch(function(err){status('Falha: '+(err&&err.message?err.message:'sem conexão.'),'err');});
}

/* ── SCROLL PARA OFÍCIO (painel direito) ── */
function scrollParaOficio(regional, posto){
    if(!regional)return;
    setTimeout(function(){
        var rid=regional;
        var n=parseInt(rid,10);
        var cv;
        if(n===0){
            /* Capital: desmembrado = 000_pid; agrupado: posto 001 → 000_001, resto → 000 */
            if(capitalDesmembrado){
                cv=rid+'_'+(posto||'');
            } else {
                cv=(parseInt(posto||'0',10)===1)?(rid+'_001'):rid;
            }
        } else {
            cv=rid;
        }
        var folha=document.getElementById('folha-'+cv);
        if(!folha) folha=document.getElementById('folha-'+rid);
        if(!folha){
            /* fallback: busca qualquer folha que contenha o rid */
            var todas=document.querySelectorAll('[id^="folha-"]');
            for(var i=0;i<todas.length;i++){
                if(todas[i].id.indexOf(String(n))>=0){folha=todas[i];break;}
            }
        }
        if(!folha)return;
        var scrollEl=document.getElementById('paineisOficio');
        if(scrollEl){
            var fr=folha.getBoundingClientRect();
            var sr=scrollEl.getBoundingClientRect();
            scrollEl.scrollBy({top:fr.top-sr.top-20,behavior:'smooth'});
        } else {
            folha.scrollIntoView({behavior:'smooth',block:'center'});
        }
        folha.classList.add('of-scan-hl');
        setTimeout(function(){folha.classList.remove('of-scan-hl');},1800);
    },200);
}

/* ── TELA CHEIA PAINEL ── */
function toggleExpandPainel(id,btn){
    var el=document.getElementById(id);if(!el)return;
    var exp=el.classList.toggle('expandido');
    if(btn){btn.innerHTML=exp?'&#8863; Sair':'&#8862;';}
    if(exp){
        el._escHandler=function(e){if(e.key==='Escape')toggleExpandPainel(id,btn);};
        document.addEventListener('keydown',el._escHandler);
    } else if(el._escHandler){
        document.removeEventListener('keydown',el._escHandler);
    }
}

/* ── SCANNER INPUT ── */
var inputScan=document.getElementById('inputScan');
if(inputScan){
    inputScan.addEventListener('keydown',function(e){
        if(e.key==='Enter'||e.keyCode===13){var v=this.value.trim();this.value='';if(v)lerCodigo(v);}
    });
    inputScan.addEventListener('input',function(){
        var v=this.value.replace(/\D/g,'');
        if(v.length>=19){var vv=this.value.trim();this.value='';if(vv)lerCodigo(vv);}
    });
}
document.addEventListener('keydown',function(){
    var a=document.activeElement;
    if(a){var t=(a.tagName||'').toLowerCase();if(t==='input'||t==='textarea'||t==='select'||t==='button')return;}
    if(inputScan)inputScan.focus();
});
document.getElementById('btnCarregar').addEventListener('click',carregarLotes);
// v2.5.0: auto-carregar ao montar a pagina quando as datas ja vem preenchidas
// (default = hoje). Assim os lotes ja conferidos (conf='s') aparecem EM VERDE
// automaticamente, sem exigir clique manual em "Carregar".
(function(){
    var di=document.getElementById('inputDataIni');
    if(di&&di.value){carregarLotes();}
})();
document.getElementById('btnLimpar').addEventListener('click',function(){
    estadoLotes={};estadoLotesPT={};excluidos={};selecionados={};ultimaRegional='';
    renderizar();status('Tela limpa.','');if(inputScan)inputScan.focus();
});
if(inputScan)inputScan.focus();
</script>
<?php include __DIR__ . '/includes/util_botoes_fixos.php'; ?>
<?php include __DIR__ . '/includes/_acess.php'; ?>
</body>
</html>
