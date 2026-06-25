<?php
date_default_timezone_set('America/Sao_Paulo');
/* conferencia_pacotes.php — v0.9.46
 * CHANGELOG v0.9.46:
 * - [REFACTOR] Handler de scan completo do zero: scanLock 400ms substitui debounce/scannerIgnoreUntil
 * - [FIX] Barcode capturado e input limpo imediatamente ao atingir 19 digitos (sem residuais)
 * - [FIX] resolverBarcode() busca janela de 19 digitos que casa com linha antes de tomar os ultimos 19
 * - [FIX] Fallback lote+posto para 19 digitos quando data-codigo nao casa exato
 * - [FIX] Audio 'pacote nao carregado' (era 'nao encontrado') - so emite quando lote nao esta na tabela
 * - [FIX] Leitura de 8 digitos usa debounce 200ms separado do lock de 19 digitos
 * - [FIX] gera_oficio_correios.php: removido salvamento de etiquetas Correios em ciMalotes
 * - [FIX] bloqueados.php: lista de postos bloqueados renderizada via PHP no carregamento (nao so AJAX)
 * CHANGELOG v0.9.46:
 * - [FIX] Contador X/Y conferidos atualiza dinamicamente apos cada leitura confirmada
 * - [FIX] Lote na lista de pacotes nao listados: limpeza do input ao receber 9-18 digitos evita residuais que corrompiam o lote
 * - [FIX] Maloteamento Visual inicia colapsado; expande/colapsa ao clicar no titulo
 * CHANGELOG v0.9.46:
 * - [FIX] Scanner: disparo imediato ao atingir 19 digitos (sem debounce) + cooldown 200ms
 * - [FIX] Residual de codbar no input apos leitura eliminado via scannerIgnoreUntil
 * - [FIX] Audio 'pacote ja conferido' simplificado para 'conferido'
 * - [FIX] Modal de responsavel sempre exibido ao iniciar (sem pre-preenchimento automatico)
 * CHANGELOG v6.0:
 * - [NOVO] Modal obrigatorio para responsavel + tipo de conferencia
 * - [NOVO] Filtro por intervalo de datas + datas avulsas
 * - [NOVO] Fila de audio sem sobreposicao
 * - [NOVO] Alertas cruzados: posto_poupatempo.mp3 e pertence_aos_correios.mp3
 * - [NOVO] pacotejaconferido.mp3 para pacote ja conferido
 * - [NOVO] Postos bloqueados (nao enviar este posto)
 * - [NOVO] Toggle silenciar beep
 * - [NOVO] Banners visuais para Correios e Poupa Tempo
 * - [NOVO] Cards de estatisticas
 * - [NOVO] Status de conferencias (ultimas/pendentes)
 * - [NOVO] Pacotes nao encontrados acumulados para salvar ao final
 * - [NOVO] Campos usuario e lido_em na conferencia
 * - [MELHORIA] Layout responsivo
 * - [MELHORIA] Agrupamento por regional real (ciRegionais)
 */

$total_codigos = 0;
$datas_expedicao = array();
$regionais_data = array();
$data_ini = isset($_GET['data_ini']) ? trim($_GET['data_ini']) : '';
$data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';
$datas_avulsas = isset($_GET['datas_avulsas']) ? trim($_GET['datas_avulsas']) : '';
$datas_sql = array();
$datas_exib = array();
$poupaTempoPostos = array();
$conferencias = array();
$conferencias_lote = array();
$dias_com_conferencia = array();
$dias_sem_conferencia = array();
$metadados_dias = array();
$db_error = null;
$stats = array('carteiras_emitidas' => 0, 'carteiras_conferidas' => 0, 'pacotes_conferidos' => 0, 'postos_conferidos' => 0);

require_once __DIR__ . '/db_singleton.php';

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = getDb();

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosBloqueados (
        id INT NOT NULL AUTO_INCREMENT,
        posto VARCHAR(10) NOT NULL,
        nome VARCHAR(120) DEFAULT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado DATETIME NOT NULL,
        atualizado DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY posto (posto)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    try { $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN lacre_iipr VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN lacre_correios VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN etiqueta_correios VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE conferencia_pacotes MODIFY COLUMN lido_em DATETIME DEFAULT NULL"); } catch (Exception $e) {}

    if (isset($_POST['salvar_lote_ajax'])) {
        header('Content-Type: application/json');
        $lote = trim($_POST['lote']);
        $regional = trim($_POST['regional']);
        $posto = trim($_POST['posto']);
        $dataexp = trim($_POST['dataexp']);
        $qtd = (int)$_POST['qtd'];
        $codbar = trim($_POST['codbar']);
        $usuario_conf = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';

        if ($dataexp === '') {
            $dataexp = date('d-m-Y');
        }
        if ($usuario_conf === '') {
            die(json_encode(array('sucesso' => false, 'erro' => 'Usuario obrigatorio')));
        }
        
        $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, lido_em) 
                VALUES (?, ?, ?, ?, ?, ?, 's', ?, NOW())
                ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar), dataexp=VALUES(dataexp), usuario=VALUES(usuario), lido_em=NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($regional, $lote, $posto, $dataexp, $qtd, $codbar, $usuario_conf));
        $stmt = null;
        $pdo = null;
        die(json_encode(array('sucesso' => true)));
    }

    if (isset($_POST['inserir_pacotes_nao_listados'])) {
        header('Content-Type: application/json');
        $payload = isset($_POST['pacotes']) ? $_POST['pacotes'] : '';
        $usuario_conf = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        if ($usuario_conf === '') {
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }
        $pacotes = json_decode($payload, true);
        if (!is_array($pacotes)) {
            die(json_encode(array('success' => false, 'erro' => 'Payload invalido')));
        }

        $ok = 0;
        $erros = array();
        $stmtPostos = $pdo->prepare("
            INSERT INTO ciPostos (posto, dia, quantidade, turno, regional, lote, autor, criado, situacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        foreach ($pacotes as $p) {
            try {
                $lote = isset($p['lote']) ? trim($p['lote']) : '';
                $posto = isset($p['posto']) ? trim($p['posto']) : '';
                $regional = isset($p['regional']) ? trim($p['regional']) : '';
                $quantidade = isset($p['quantidade']) ? (int)$p['quantidade'] : 0;
                $dataexp = isset($p['dataexp']) ? trim($p['dataexp']) : '';
                $turno_val = isset($p['turno']) ? (int)$p['turno'] : 1;
                $usuario_pacote = isset($p['responsavel']) ? trim($p['responsavel']) : '';
                if ($usuario_pacote === '') {
                    $usuario_pacote = $usuario_conf;
                }

                if ($lote === '' || $posto === '' || $regional === '' || $quantidade <= 0 || $dataexp === '') {
                    throw new Exception('Campos obrigatorios ausentes');
                }

                if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $dataexp, $m)) {
                    $data_sql_val = $m[3] . '-' . $m[2] . '-' . $m[1];
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataexp)) {
                    $data_sql_val = $dataexp;
                } else {
                    throw new Exception('Data invalida');
                }

                $nome_posto = sprintf('%03d - POSTO', (int)$posto);
                $criado = $data_sql_val . ' 10:10:10';
                $stmtPostos->execute(array(
                    $nome_posto,
                    $data_sql_val,
                    $quantidade,
                    $turno_val,
                    $regional,
                    (int)$lote,
                    $usuario_pacote,
                    $criado
                ));

                // Inserir tambem em ciPostosCsv para rastreabilidade
                $stmtCsv = $pdo->prepare("
                    INSERT INTO ciPostosCsv (lote, posto, regional, quantidade, dataCarga, data, usuario)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantidade=VALUES(quantidade), dataCarga=VALUES(dataCarga), data=VALUES(data), usuario=VALUES(usuario)
                ");
                $stmtCsv->execute(array(
                    str_pad((string)$lote, 8, '0', STR_PAD_LEFT),
                    str_pad($posto, 3, '0', STR_PAD_LEFT),
                    $regional,
                    $quantidade,
                    $data_sql_val,
                    $criado,
                    $usuario_pacote
                ));
                $stmtCsv = null;

                $ok++;
            } catch (Exception $ex) {
                $erros[] = $ex->getMessage();
            }
        }

        $stmtPostos = null;
        $pdo = null;
        if ($ok === 0 && !empty($erros)) {
            die(json_encode(array('success' => false, 'inseridos' => 0, 'erro' => 'Nenhum lote inserido. Erros: ' . implode('; ', $erros))));
        }
        die(json_encode(array('success' => true, 'inseridos' => $ok, 'erros' => $erros)));
    }

    if (isset($_POST['excluir_lote_ajax'])) {
        header('Content-Type: application/json');
        $datasPost = isset($_POST['datas']) ? trim($_POST['datas']) : '';
        if ($datasPost !== '') {
            $partes = explode(',', $datasPost);
            foreach ($partes as $d) {
                $d = trim($d);
                if ($d === '') continue;
                $dp = explode('-', $d);
                if (count($dp) === 3 && strlen($dp[0]) === 2) {
                    $dFmt = $dp[2] . '-' . $dp[1] . '-' . $dp[0];
                } else {
                    $dFmt = $d;
                }
                $stmt = $pdo->prepare("DELETE FROM conferencia_pacotes WHERE dataexp = ?");
                $stmt->execute(array($dFmt));
            }
        }
        $stmt = null;
        $pdo = null;
        die(json_encode(array('sucesso' => true)));
    }

    if (isset($_POST['salvar_posto_bloqueado'])) {
        header('Content-Type: application/json');
        $posto = trim($_POST['posto']);
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        if ($posto === '') {
            die(json_encode(array('success' => false, 'erro' => 'Posto obrigatorio')));
        }
        $stmt = $pdo->prepare("SELECT id FROM ciPostosBloqueados WHERE posto = ?");
        $stmt->execute(array($posto));
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE ciPostosBloqueados SET nome = ?, ativo = 1, atualizado = NOW() WHERE posto = ?");
            $stmt->execute(array($nome, $posto));
        } else {
            $stmt = $pdo->prepare("INSERT INTO ciPostosBloqueados (posto, nome, ativo, criado) VALUES (?, ?, 1, NOW())");
            $stmt->execute(array($posto, $nome));
        }
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true)));
    }

    if (isset($_POST['excluir_posto_bloqueado'])) {
        header('Content-Type: application/json');
        $posto = trim($_POST['posto']);
        if ($posto === '') {
            die(json_encode(array('success' => false, 'erro' => 'Posto obrigatorio')));
        }
        $stmt = $pdo->prepare("DELETE FROM ciPostosBloqueados WHERE posto = ?");
        $stmt->execute(array($posto));
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true)));
    }

    if (isset($_POST['ajax_fechar_malote_iipr'])) {
        header('Content-Type: application/json');
        $lacre = isset($_POST['lacre_iipr']) ? trim($_POST['lacre_iipr']) : '';
        $lotes_json = isset($_POST['lotes']) ? $_POST['lotes'] : '';
        $lotes = json_decode($lotes_json, true);
        if ($lacre === '' || !is_array($lotes) || count($lotes) === 0) {
            die(json_encode(array('success' => false, 'erro' => 'Lacre IIPR e lotes sao obrigatorios')));
        }
        $stmt = $pdo->prepare("UPDATE conferencia_pacotes SET lacre_iipr = ? WHERE nlote = ? AND nposto = ? AND conf = 's'");
        $ok = 0;
        foreach ($lotes as $l) {
            $nlote = isset($l['nlote']) ? trim($l['nlote']) : '';
            $nposto = isset($l['nposto']) ? trim($l['nposto']) : '';
            if ($nlote !== '' && $nposto !== '') {
                $stmt->execute(array($lacre, $nlote, $nposto));
                $ok += $stmt->rowCount();
            }
        }
        $stmt = null;
        die(json_encode(array('success' => true, 'atualizados' => $ok)));
    }

    if (isset($_POST['ajax_salvar_oficio_conferencia'])) {
        header('Content-Type: application/json');
        $linhas_json = isset($_POST['linhas']) ? $_POST['linhas'] : '';
        $linhas = json_decode($linhas_json, true);
        $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $sobrescrever = isset($_POST['sobrescrever']) && $_POST['sobrescrever'] === '1';
        if ($usuario === '') {
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }
        if (!is_array($linhas) || count($linhas) === 0) {
            die(json_encode(array('success' => false, 'erro' => 'Nenhuma linha para salvar')));
        }
        $etiquetas_vistas = array();
        $erros_etq = array();
        foreach ($linhas as $idx => $lin) {
            $etq = isset($lin['etiqueta_correios']) ? trim($lin['etiqueta_correios']) : '';
            if ($etq !== '') {
                $etq_upper = strtoupper($etq);
                if (isset($etiquetas_vistas[$etq_upper])) {
                    $erros_etq[] = 'Etiqueta duplicada: ' . $etq;
                }
                $etiquetas_vistas[$etq_upper] = true;
            }
        }
        if (count($etiquetas_vistas) > 0 && !$sobrescrever) {
            $etqList = array_keys($etiquetas_vistas);
            $ph = implode(',', array_fill(0, count($etqList), '?'));
            try {
                $stmtChk = $pdo->prepare("SELECT leitura, posto FROM ciMalotes WHERE UPPER(leitura) IN ($ph) AND tipo = 1 LIMIT 10");
                $stmtChk->execute($etqList);
                while ($dupR = $stmtChk->fetch(PDO::FETCH_ASSOC)) {
                    $erros_etq[] = 'Etiqueta "' . $dupR['leitura'] . '" ja existe (posto ' . $dupR['posto'] . ')';
                }
            } catch (Exception $e) {}
        }
        if (!empty($erros_etq)) {
            die(json_encode(array('success' => false, 'erro' => implode('; ', $erros_etq))));
        }
        $ok = 0;
        $erros = array();
        if ($sobrescrever) {
            $stmtUpd = $pdo->prepare("UPDATE conferencia_pacotes SET lacre_iipr = ?, lacre_correios = ?, etiqueta_correios = ? WHERE nlote = ? AND nposto = ? AND conf = 's'");
        } else {
            $stmtUpd = $pdo->prepare("UPDATE conferencia_pacotes SET lacre_iipr = COALESCE(NULLIF(lacre_iipr,''), ?), lacre_correios = COALESCE(NULLIF(lacre_correios,''), ?), etiqueta_correios = COALESCE(NULLIF(etiqueta_correios,''), ?) WHERE nlote = ? AND nposto = ? AND conf = 's'");
        }
        $stmtMalote = $pdo->prepare("INSERT INTO ciMalotes (leitura, data, observacao, login, tipo, posto) VALUES (?, CURDATE(), ?, ?, 1, ?)");
        $malotesParaSalvar = array();
        foreach ($linhas as $lin) {
            $lacre_i = isset($lin['lacre_iipr']) ? trim($lin['lacre_iipr']) : '';
            $lacre_c = isset($lin['lacre_correios']) ? trim($lin['lacre_correios']) : '';
            $etiqueta = isset($lin['etiqueta_correios']) ? trim($lin['etiqueta_correios']) : '';
            $posto = isset($lin['posto']) ? trim($lin['posto']) : '';
            $regional = isset($lin['regional']) ? trim($lin['regional']) : '';
            $nlote = isset($lin['lote']) ? trim($lin['lote']) : '';
            $lotes_arr = isset($lin['lotes']) ? $lin['lotes'] : array();
            if ($nlote !== '' && empty($lotes_arr)) {
                $lotes_arr = array($nlote);
            }
            foreach ($lotes_arr as $lote_item) {
                try {
                    if ($sobrescrever) {
                        $stmtUpd->execute(array($lacre_i, $lacre_c, $etiqueta, trim($lote_item), $posto));
                    } else {
                        $stmtUpd->execute(array($lacre_i, $lacre_c, $etiqueta, trim($lote_item), $posto));
                    }
                    $ok++;
                } catch (Exception $ex) {
                    $erros[] = $ex->getMessage();
                }
            }
            if (($lacre_c !== '' || $etiqueta !== '') && !empty($lotes_arr)) {
                $etqKey = $etiqueta !== '' ? $etiqueta : $lacre_c;
                if (!isset($malotesParaSalvar[$etqKey])) {
                    $malotesParaSalvar[$etqKey] = array('posto' => $posto, 'regional' => $regional, 'lacre' => $lacre_c, 'lacres_iipr' => array(), 'lotes_por_iipr' => array());
                }
                if ($lacre_i !== '') {
                    if (!isset($malotesParaSalvar[$etqKey]['lotes_por_iipr'][$lacre_i])) {
                        $malotesParaSalvar[$etqKey]['lacres_iipr'][] = $lacre_i;
                        $malotesParaSalvar[$etqKey]['lotes_por_iipr'][$lacre_i] = array();
                    }
                    foreach ($lotes_arr as $la) {
                        $malotesParaSalvar[$etqKey]['lotes_por_iipr'][$lacre_i][] = trim($la);
                    }
                }
            }
        }
        foreach ($malotesParaSalvar as $codigo_m => $info_m) {
            $obsParts = array();
            foreach ($info_m['lacres_iipr'] as $li) {
                $obsParts[] = 'IIPR ' . $li . ': ' . implode(',', $info_m['lotes_por_iipr'][$li]);
            }
            $obs = implode(' | ', $obsParts);
            if (strlen($obs) > 200) {
                $obs = substr($obs, 0, 197) . '...';
            }
            try {
                $stmtMalote->execute(array($codigo_m, $obs, $usuario, $info_m['posto']));
            } catch (Exception $ex) {
                $erros[] = $ex->getMessage();
            }
        }
        $stmtUpd = null;
        $stmtMalote = null;
        die(json_encode(array('success' => true, 'atualizados' => $ok, 'erros' => $erros)));
    }

    if (isset($_POST['ajax_carregar_malotes'])) {
        header('Content-Type: application/json');
        $di = isset($_POST['data_inicio']) ? trim($_POST['data_inicio']) : '';
        $df = isset($_POST['data_fim']) ? trim($_POST['data_fim']) : '';
        if ($di === '' || $df === '') {
            die(json_encode(array('success' => false, 'erro' => 'Datas obrigatorias')));
        }
        $stmt = $pdo->prepare("SELECT LPAD(cp.nlote,8,'0') AS nlote, LPAD(cp.nposto,3,'0') AS nposto, MAX(cp.regional) AS regional, MAX(cp.qtd) AS qtd, MAX(cp.lacre_iipr) AS lacre_iipr, MAX(cp.lacre_correios) AS lacre_correios, MAX(cp.etiqueta_correios) AS etiqueta_correios, MAX(cp.lido_em) AS lido_em, COALESCE(MAX(CAST(cr.regional AS UNSIGNED)), 0) AS regional_real FROM conferencia_pacotes cp LEFT JOIN ciRegionais cr ON LPAD(cp.nposto,3,'0') = LPAD(cr.posto,3,'0') WHERE cp.conf = 's' AND cp.dataexp BETWEEN ? AND ? GROUP BY LPAD(cp.nlote,8,'0'), LPAD(cp.nposto,3,'0') ORDER BY LPAD(cp.nposto,3,'0'), lacre_iipr, LPAD(cp.nlote,8,'0')");
        $stmt->execute(array($di, $df));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        die(json_encode(array('success' => true, 'lotes' => $rows)));
    }

    $postosInfo = array();
    $regionaisNomes = array();
    $sql = "SELECT LPAD(posto,3,'0') AS posto, 
                   CAST(regional AS UNSIGNED) AS regional,
                   LOWER(TRIM(REPLACE(entrega,' ',''))) AS entrega,
                   nome
            FROM ciRegionais 
            LIMIT 1000";
    $stmt = $pdo->query($sql);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $posto_pad = $r['posto'];
        $regional_real = (int)$r['regional'];
        if (!empty($r['nome'])) {
            $regionaisNomes[$regional_real] = trim($r['nome']);
            $regionaisNomes[$posto_pad] = trim($r['nome']);
        }
        $entrega_tipo = null;
        
        if (!empty($r['entrega'])) {
            $entrega_limpo = $r['entrega'];
            if (strpos($entrega_limpo, 'poupa') !== false || strpos($entrega_limpo, 'tempo') !== false) {
                $entrega_tipo = 'poupatempo';
            } elseif (strpos($entrega_limpo, 'correio') !== false) {
                $entrega_tipo = 'correios';
            }
        }
        
        $postosInfo[$posto_pad] = array(
            'regional' => $regional_real,
            'entrega' => $entrega_tipo
        );
    }

    $conferencias_lido_em = array();
    $stmt = $pdo->query("SELECT nlote, regional, nposto, codbar, lido_em FROM conferencia_pacotes WHERE conf='s'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nlote_raw = trim((string)$row['nlote']);
        $regional_raw = trim((string)$row['regional']);
        $posto_raw = trim((string)$row['nposto']);
        $lido_em_val = isset($row['lido_em']) ? $row['lido_em'] : '';

        $nlote_pad = str_pad($nlote_raw, 8, '0', STR_PAD_LEFT);
        $regional_pad = str_pad($regional_raw, 3, '0', STR_PAD_LEFT);
        $posto_pad = str_pad($posto_raw, 3, '0', STR_PAD_LEFT);

        $keys = array();
        $keys[] = $nlote_pad . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $posto_pad;

        if (!empty($row['codbar'])) {
            $cb_raw = trim((string)$row['codbar']);
            $cb = preg_replace('/\D+/', '', $cb_raw);
            $cb_pad = str_pad($cb, 19, '0', STR_PAD_LEFT);
            $conferencias['codbar:' . $cb] = 1;
            $conferencias['codbar:' . $cb_pad] = 1;
            $conferencias['codbar:' . $cb_raw] = 1;
            $conferencias_lido_em['codbar:' . $cb] = $lido_em_val;
            $conferencias_lido_em['codbar:' . $cb_pad] = $lido_em_val;
            $conferencias_lido_em['codbar:' . $cb_raw] = $lido_em_val;
            if (strlen($cb_pad) >= 14) {
                $lote_cb = substr($cb_pad, 0, 8);
                $reg_cb = substr($cb_pad, 8, 3);
                $pst_cb = substr($cb_pad, 11, 3);
                $keys[] = $lote_cb . '|' . $reg_cb . '|' . $pst_cb;
                $keys[] = $lote_cb . '|' . $pst_cb;
            }
        }

        foreach ($keys as $k) {
            $conferencias[$k] = 1;
            $conferencias_lido_em[$k] = $lido_em_val;
        }

        if ($nlote_raw !== '') {
            $conferencias_lote[$nlote_raw] = 1;
        }
        $conferencias_lote[$nlote_pad] = 1;
    }

    $postosComRegra = array();
    $regrasDiasPorPosto = array();
    $nomeDiasSemana = array(0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sab');
    try {
        $dia_semana_atual = (int)date('w');
        $stRegras = $pdo->query("SELECT LPAD(posto,3,'0') AS posto_pad, dia_semana FROM ciRegrasEnvioPosto WHERE ativo = 1");
        while ($rr = $stRegras->fetch(PDO::FETCH_ASSOC)) {
            $pp = $rr['posto_pad'];
            $ds = (int)$rr['dia_semana'];
            if (!isset($regrasDiasPorPosto[$pp])) {
                $regrasDiasPorPosto[$pp] = array();
            }
            $regrasDiasPorPosto[$pp][] = $ds;
            if ($ds !== $dia_semana_atual) {
                $postosComRegra[$pp] = true;
            }
        }
    } catch (Exception $e) {}

    function normalizarDataSql($d) {
        $d = trim($d);
        if ($d === '') return '';
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $d, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }
        return '';
    }

    function normalizarDataExib($d) {
        $d = trim($d);
        if ($d === '') return '';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $d)) {
            return $d;
        }
        return '';
    }

    $data_ini_sql = normalizarDataSql($data_ini);
    $data_fim_sql = normalizarDataSql($data_fim);

    if ($data_ini_sql !== '' && $data_fim_sql === '') {
        $data_fim_sql = $data_ini_sql;
    }
    if ($data_ini_sql === '' && $data_fim_sql !== '') {
        $data_ini_sql = $data_fim_sql;
    }

    if (!empty($datas_avulsas)) {
        $partes = preg_split('/[\s,;]+/', $datas_avulsas);
        foreach ($partes as $p) {
            $ds = normalizarDataSql($p);
            if ($ds !== '') {
                $datas_sql[] = $ds;
            }
        }
    }

    if ($data_ini_sql === '' && $data_fim_sql === '' && empty($datas_sql)) {
        $stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(dataCarga, '%Y-%m-%d') as data 
                             FROM ciPostosCsv 
                             WHERE dataCarga IS NOT NULL 
                             ORDER BY dataCarga DESC 
                             LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['data'])) {
            $data_ini_sql = $row['data'];
            $data_fim_sql = $row['data'];
            if ($data_ini === '') {
                $data_ini = $row['data'];
            }
            if ($data_fim === '') {
                $data_fim = $row['data'];
            }
        }
    }

    $stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(dataCarga, '%d-%m-%Y') as data 
                         FROM ciPostosCsv 
                         WHERE dataCarga IS NOT NULL 
                         ORDER BY dataCarga DESC 
                         LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $datas_expedicao[] = $row['data'];
    }

    $condicoes_data = array();
    $params_data = array();
    if ($data_ini_sql !== '' && $data_fim_sql !== '') {
        $condicoes_data[] = "DATE(dataCarga) BETWEEN ? AND ?";
        $params_data[] = $data_ini_sql;
        $params_data[] = $data_fim_sql;
    }
    if (!empty($datas_sql)) {
        $ph = implode(',', array_fill(0, count($datas_sql), '?'));
        $condicoes_data[] = "DATE(dataCarga) IN ($ph)";
        $params_data = array_merge($params_data, $datas_sql);
    }

    if (!empty($condicoes_data)) {
        $whereData = "WHERE (" . implode(' OR ', $condicoes_data) . ")";
        $sql = "SELECT lote, posto, regional, quantidade, dataCarga 
                FROM ciPostosCsv 
                $whereData
                ORDER BY regional, lote, posto, dataCarga DESC 
                LIMIT 3000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params_data);

        $vistos_csv = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (empty($row['dataCarga'])) continue;
            $chave_dedup = str_pad($row['lote'], 8, '0', STR_PAD_LEFT) . '|' . str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
            if (isset($vistos_csv[$chave_dedup])) continue;
            $vistos_csv[$chave_dedup] = true;

            $data_formatada = date('d-m-Y', strtotime($row['dataCarga']));
            $data_sql_row = date('Y-m-d', strtotime($row['dataCarga']));

            $lote = str_pad($row['lote'], 8, '0', STR_PAD_LEFT);
            $posto = str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
            $regional_csv = (int)$row['regional'];
            $regional_str = str_pad($regional_csv, 3, '0', STR_PAD_LEFT);
            $quantidade = str_pad($row['quantidade'], 5, '0', STR_PAD_LEFT);

            $codigo_barras = $lote . $regional_str . $posto . $quantidade;
            
            $regional_real = isset($postosInfo[$posto]) ? $postosInfo[$posto]['regional'] : $regional_csv;
            $tipoEntrega = isset($postosInfo[$posto]) ? $postosInfo[$posto]['entrega'] : null;
            $isPT = ($tipoEntrega == 'poupatempo') ? 1 : 0;
            
            $lote_pad = str_pad($lote, 8, '0', STR_PAD_LEFT);
            $posto_pad = str_pad($posto, 3, '0', STR_PAD_LEFT);
            $regional_pad_csv = str_pad($regional_str, 3, '0', STR_PAD_LEFT);

            $regional_exibida = ($isPT == 1) ? $posto : str_pad($regional_real, 3, '0', STR_PAD_LEFT);
            $regional_pad_exib = str_pad($regional_exibida, 3, '0', STR_PAD_LEFT);

            if ($isPT == 1) {
                $regional_display = $regional_exibida;
            } else if ($regional_real == 0) {
                $regional_display = 'Capital';
            } else if ($regional_real == 999) {
                $regional_display = 'Metropolitana';
            } else {
                $regional_display = str_pad($regional_real, 3, '0', STR_PAD_LEFT);
            }

            $keysToTry = array(
                $lote_pad . '|' . $regional_pad_exib . '|' . $posto_pad,
                $lote . '|' . $regional_pad_exib . '|' . $posto_pad,
                $lote_pad . '|' . $regional_pad_csv . '|' . $posto_pad,
                $lote . '|' . $regional_pad_csv . '|' . $posto_pad,
                $lote_pad . '|' . $posto_pad,
                $lote . '|' . $posto_pad
            );

            $conferido = 0;
            $lido_em_exib = '';
            if (isset($conferencias['codbar:' . $codigo_barras])) {
                $conferido = 1;
                if (isset($conferencias_lido_em['codbar:' . $codigo_barras])) {
                    $lido_em_exib = $conferencias_lido_em['codbar:' . $codigo_barras];
                }
            }
            if ($conferido === 0) {
                foreach ($keysToTry as $kTry) {
                    if (isset($conferencias[$kTry])) {
                        $conferido = 1;
                        if (isset($conferencias_lido_em[$kTry])) {
                            $lido_em_exib = $conferencias_lido_em[$kTry];
                        }
                        break;
                    }
                }
            }
            if ($conferido === 0) {
                if (isset($conferencias_lote[$lote]) || isset($conferencias_lote[$lote_pad])) {
                    $conferido = 1;
                }
            }

            $lido_em_formatado = '';
            if ($lido_em_exib !== '' && $lido_em_exib !== null) {
                try {
                    $dtLido = new DateTime($lido_em_exib);
                    $lido_em_formatado = $dtLido->format('d/m/Y H:i:s');
                } catch (Exception $eDt) {
                    $lido_em_formatado = $lido_em_exib;
                }
            }

            if (!isset($regionais_data[$regional_real])) {
                $regionais_data[$regional_real] = array();
            }

            $regionais_data[$regional_real][] = array(
                'lote' => $lote,
                'posto' => $posto,
                'regional' => $regional_exibida,
                'regional_real' => $regional_real,
                'regional_display' => $regional_display,
                'tipoEntrega' => $tipoEntrega,
                'data' => $data_formatada,
                'data_sql' => $data_sql_row,
                'qtd' => ltrim($quantidade, '0'),
                'codigo' => $codigo_barras,
                'isPT' => $isPT,
                'conf' => $conferido,
                'lido_em' => $lido_em_formatado
            );

            $total_codigos++;
        }
    }

    sort($datas_expedicao);

    if ($data_ini_sql !== '' && $data_fim_sql !== '') {
        try {
            $dtIni = new DateTime($data_ini_sql);
            $dtFim = new DateTime($data_fim_sql);
            while ($dtIni <= $dtFim) {
                $datas_exib[] = $dtIni->format('d-m-Y');
                $dtIni->modify('+1 day');
            }
        } catch (Exception $e) {}
    }
    if (!empty($datas_sql)) {
        foreach ($datas_sql as $ds) {
            $datas_exib[] = normalizarDataExib($ds);
        }
    }
    $datas_exib = array_values(array_unique(array_filter($datas_exib)));

    $stats = array(
        'carteiras_emitidas' => 0,
        'carteiras_conferidas' => 0,
        'postos_conferidos' => 0,
        'pacotes_conferidos' => 0
    );

    try {
        $stmt_conferidos = $pdo->query("
            SELECT DISTINCT 
                DATE(dataCarga) as data,
                DAYOFWEEK(dataCarga) as dia_semana
            FROM ciPostosCsv 
            WHERE dataCarga >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY data DESC
            LIMIT 15
        ");
        $dias_com_producao = array();
        while ($row = $stmt_conferidos->fetch(PDO::FETCH_ASSOC)) {
            $data_fmt = date('d/m/Y', strtotime($row['data']));
            $dias_com_producao[] = $data_fmt;

            $dia_num = (int)$row['dia_semana'];
            $labels = array(
                1 => 'DOM', 2 => 'SEG', 3 => 'TER',
                4 => 'QUA', 5 => 'QUI', 6 => 'SEX', 7 => 'SAB'
            );
            $label = isset($labels[$dia_num]) ? $labels[$dia_num] : '';

            $metadados_dias[$data_fmt] = array(
                'dia_semana_num' => $dia_num,
                'label' => $label
            );
        }

        try {
            $stmt_conf = $pdo->query("
                SELECT DISTINCT DATE(csv.dataCarga) as data
                FROM ciPostosCsv csv
                INNER JOIN conferencia_pacotes cp ON csv.lote = cp.nlote
                WHERE csv.dataCarga >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND cp.conf = 's'
                ORDER BY data DESC
            ");
            while ($row = $stmt_conf->fetch(PDO::FETCH_ASSOC)) {
                $dias_com_conferencia[] = date('d/m/Y', strtotime($row['data']));
            }
        } catch (Exception $e) {
            $dias_com_conferencia = array();
        }

        $dias_sem_conferencia = array_diff($dias_com_producao, $dias_com_conferencia);
        $dias_sem_conferencia = array_values($dias_sem_conferencia);
        $dias_sem_conferencia = array_slice($dias_sem_conferencia, 0, 10);
    } catch (Exception $e) {}

    if (!empty($condicoes_data)) {
        $sqlEmitidas = "SELECT COALESCE(SUM(quantidade),0) AS total FROM ciPostosCsv $whereData";
        $stmtEmit = $pdo->prepare($sqlEmitidas);
        $stmtEmit->execute($params_data);
        $stats['carteiras_emitidas'] = (int)$stmtEmit->fetchColumn();
    }

    if (!empty($datas_exib)) {
        $phEx = implode(',', array_fill(0, count($datas_exib), '?'));
        $sqlConf = "SELECT 
                        COALESCE(SUM(qtd),0) AS total_qtd,
                        COUNT(*) AS total_pacotes,
                        COUNT(DISTINCT nposto) AS total_postos
                    FROM conferencia_pacotes
                    WHERE conf='s' AND dataexp IN ($phEx)";
        $stmtConf = $pdo->prepare($sqlConf);
        $stmtConf->execute($datas_exib);
        $rowConf = $stmtConf->fetch(PDO::FETCH_ASSOC);
        if ($rowConf) {
            $stats['carteiras_conferidas'] = (int)$rowConf['total_qtd'];
            $stats['pacotes_conferidos'] = (int)$rowConf['total_pacotes'];
            $stats['postos_conferidos'] = (int)$rowConf['total_postos'];
        }
    }

    $postos_bloqueados = array();
    try {
        $stmtBloq = $pdo->query("SELECT posto, nome FROM ciPostosBloqueados WHERE ativo = 1 ORDER BY posto ASC");
        while ($row = $stmtBloq->fetch(PDO::FETCH_ASSOC)) {
            $postos_bloqueados[] = array(
                'posto' => $row['posto'],
                'nome' => $row['nome']
            );
        }
    } catch (Exception $e) {
        $postos_bloqueados = array();
    }

} catch (PDOException $e) {
    $db_error = $e->getMessage();
    $postos_bloqueados = array();

    if (isset($_POST['salvar_lote_ajax']) || isset($_POST['inserir_pacotes_nao_listados']) || isset($_POST['excluir_lote_ajax']) || isset($_POST['salvar_posto_bloqueado']) || isset($_POST['remover_posto_bloqueado']) || isset($_POST['ajax_fechar_malote_iipr']) || isset($_POST['ajax_salvar_oficio_conferencia']) || isset($_POST['ajax_carregar_malotes'])) {
        header('Content-Type: application/json');
        die(json_encode(array('sucesso' => false, 'success' => false, 'erro' => 'Erro de conexao com o banco de dados')));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferencia de Pacotes v0.9.46</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif; padding: 20px; padding-top: 90px; background: #f5f5f5; }
        h2 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        h3 { 
            color: #555; 
            margin: 30px 0 10px; 
            padding-left: 10px; 
            border-left: 4px solid #007bff; 
        }
        
        .radio-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 6px;
        }
        .barras-topo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .radio-box label {
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .radio-box input[type="radio"] { margin-right: 10px; width: 18px; height: 18px; cursor: pointer; }
        .radio-box input[type="text"] {
            width: 260px;
            max-width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background: #fff;
            color: #333;
        }
        
        .filtro-datas { 
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .filtro-datas form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filtro-datas label { margin-right: 10px; cursor: pointer; }
        .filtro-row { display:flex; flex-wrap:wrap; gap:10px; align-items:center; width:100%; }
        .filtro-datas input[type="date"],
        .filtro-datas input[type="text"] {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            min-width: 180px;
            background: #fff;
        }
        .filtro-datas input[type="submit"] { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filtro-datas input[type="submit"]:hover { background: #0056b3; }
        
        .info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: 600;
        }
        
        #codigo_barras { 
            padding: 12px; 
            font-size: 16px; 
            width: 100%;
            max-width: 400px;
            border: 2px solid #007bff; 
            border-radius: 4px;
            margin: 10px 0;
        }
        
        #resetar {
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }
        #resetar:hover { background-color: #c82333; }
        
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 15px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        thead { background: #343a40; color: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        tbody tr { cursor: pointer; transition: background 0.2s; }
        tbody tr:hover { background: #f8f9fa; }
        tbody tr.confirmado { background-color: #d4edda !important; font-weight: 500; }
        tbody tr.regra-envio { background-color: #fff3cd !important; }
        tbody tr.regra-envio.confirmado { background-color: #d4edda !important; }
        tbody tr.ultimo-lido { animation: pulse 1.2s ease-in-out infinite; }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0,123,255,0.6); }
            70% { box-shadow: 0 0 0 10px rgba(0,123,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(0,123,255,0); }
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
        .topo-status {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            z-index: 1200;
        }
        .btn-voltar {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            background: #007bff;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .btn-voltar:hover {
            background: #0056b3;
        }
        .versao {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 18px;
        }
        th.sortable:hover {
            background: #e0e0e0;
        }
        th.sortable .sort-arrow {
            font-size: 10px;
            margin-left: 4px;
            color: #999;
        }
        th.sortable.sort-asc .sort-arrow { color: #333; }
        th.sortable.sort-desc .sort-arrow { color: #333; }
        .cards-resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin: 15px 0 10px;
        }
        .card-resumo {
            background: #fff;
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #007bff;
        }
        .card-resumo h4 { margin: 0; font-size: 12px; color: #555; text-transform: uppercase; }
        .card-resumo .valor { font-size: 20px; font-weight: 700; color: #007bff; margin-top: 6px; }
        #indicador-dias {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px 12px;
            width: 280px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        #indicador-dias.collapsed .indicador-conteudo { display: none; }
        .indicador-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            font-weight: 700;
            color: #333;
            font-size: 13px;
        }
        .badge-data { display:inline-block; padding:4px 8px; border-radius:6px; font-size:11px; margin:2px 4px 2px 0; }
        .badge-data.conferida { background:#28a745; color:#fff; }
        .badge-data.pendente { background:#ffc107; color:#333; font-weight:bold; }
        .badge-dia { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:16px; margin-left:6px; background:#343a40; color:#fff; font-size:9px; border-radius:3px; padding:0 4px; }
        .indicador-toggle { font-size: 14px; color: #666; }
        .usuario-badge {
            display:inline-block;
            padding:6px 10px;
            background:#28a745;
            color:#fff;
            border-radius:14px;
            font-weight:700;
            font-size:12px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            margin-right: 8px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .2s;
            border-radius: 999px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .2s;
            border-radius: 50%;
        }
        .switch input:checked + .slider { background-color: #dc3545; }
        .switch input:checked + .slider:before { transform: translateX(20px); }
        .banner-grupo {
            margin: 16px 0 8px;
            padding: 12px 16px;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .banner-correios { background: linear-gradient(135deg, #2f80ed 0%, #56ccf2 100%); }
        .banner-pt { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
        .painel-bloqueio {
            background: #fff;
            border-radius: 10px;
            padding: 12px 16px;
            margin: 10px 0 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #dc3545;
        }
        .painel-bloqueio h4 { margin: 0 0 10px; font-size: 13px; color:#444; text-transform: uppercase; }
        .bloqueio-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; align-items: center; }
        .bloqueio-form input { padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; }
        .bloqueio-lista { margin-top: 10px; display: grid; gap: 6px; }
        .bloqueio-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 10px; background: #f8f9fa; border-radius: 6px; font-size: 12px; }
        .bloqueio-item .posto { font-weight: 700; color: #dc3545; }
        .mensagem-leitura { margin: 6px 0 0; font-size: 12px; color: #555; }
        .mensagem-leitura strong { color: #dc3545; }
        .page-locked {
            pointer-events: none;
            filter: blur(1px);
        }
        .overlay-usuario,
        .overlay-tipo {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .overlay-usuario .card,
        .overlay-tipo .card {
            background:#fff;
            padding:20px 24px;
            border-radius:8px;
            width: 360px;
            max-width: 90%;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        }
        .overlay-usuario .card h3,
        .overlay-tipo .card h3 { margin:0 0 10px 0; border:none; padding:0; }
        .overlay-usuario input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 6px;
        }
        .overlay-usuario button,
        .overlay-tipo button {
            margin-top: 12px;
            padding: 10px 14px;
            background:#007bff;
            color:#fff;
            border:none;
            border-radius:4px;
            cursor:pointer;
            width:100%;
            font-weight:700;
        }
        .overlay-tipo .btn-opcao { background:#28a745; }
        .overlay-tipo .btn-opcao.pt { background:#17a2b8; }
        .painel-pacotes-novos {
            background:#fff;
            border-radius:8px;
            padding:12px 16px;
            margin:15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .painel-pacotes-novos table { margin-top: 8px; }
        .btn-acao {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-salvar { background:#28a745; color:#fff; }
        .btn-cancelar { background:#dc3545; color:#fff; }
        .modal-pacote {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2100;
        }
        .modal-pacote .card {
            background:#fff;
            padding:18px;
            border-radius:8px;
            width: 380px;
            max-width: 92%;
        }
        .modal-pacote label { display:block; margin-top:8px; font-size:12px; color:#555; }
        .modal-pacote input { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; }
        .btn-toggle {
            padding: 8px 14px;
            background: #17a2b8;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 6px;
        }
        @media (max-width: 768px) {
            body { padding: 12px; padding-top: 20px; }
            .topo-status { position: static; flex-direction: column; align-items: stretch; }
            #indicador-dias { width: 100%; }
            .barras-topo { grid-template-columns: 1fr; }
            .radio-box { padding: 10px 12px; }
            #codigo_barras { max-width: 100%; font-size: 18px; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            th, td { font-size: 12px; padding: 8px; }
            h2 { font-size: 18px; }
            .cards-resumo { grid-template-columns: 1fr 1fr; }
            #resetar { margin-left: 0; margin-top: 8px; width: 100%; }
        }
        .malote-chip { display:inline-block; background:#e3f2fd; border:1px solid #1565c0; border-radius:6px; padding:4px 10px; margin:2px; font-size:12px; }
        .malote-chip.fechado { background:#c8e6c9; border-color:#2e7d32; }
        .btn-gerar-oficio { padding:10px 20px; background:#e65100; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; margin-top:16px; }
        .btn-gerar-oficio:hover { background:#bf360c; }

        .mv-secoes-container { display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start; }
        .mv-secao { background:#fafafa; border:1px solid #e0e0e0; border-radius:10px; padding:14px; margin-bottom:14px; flex:1; min-width:280px; }
        .mv-secao-header { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .mv-secao-titulo { font-weight:700; font-size:15px; color:#37474f; }
        .mv-secao-count { background:#1565c0; color:#fff; font-size:12px; font-weight:700; padding:2px 10px; border-radius:12px; min-width:24px; text-align:center; }
        .mv-lotes-tabela-area { min-height:36px; }
        .mv-malotes-area { display:flex; flex-wrap:wrap; gap:12px; min-height:50px; }
        .mv-vazio { color:#999; font-size:13px; font-style:italic; padding:6px; }
        .mv-grupo-categoria { margin-bottom:12px; }
        .mv-grupo-categoria-header { background:#37474f; color:#fff; font-weight:700; font-size:13px; padding:6px 12px; border-radius:6px 6px 0 0; }
        .mv-grupo-categoria-header.capital { background:#1565c0; }
        .mv-grupo-categoria-header.central { background:#6a1b9a; }
        .mv-grupo-categoria-header.regional { background:#e65100; }
        .mv-linha-posto { display:flex; align-items:center; gap:6px; padding:6px 10px; border:1px solid #e0e0e0; border-top:none; background:#fff; flex-wrap:wrap; }
        .mv-linha-posto:last-child { border-radius:0 0 6px 6px; }
        .mv-linha-posto-nome { font-weight:700; font-size:12px; color:#37474f; min-width:180px; }
        .mv-linha-posto-lotes { display:flex; flex-wrap:wrap; gap:4px; flex:1; }
        .mv-chip-lote { display:inline-flex; align-items:center; gap:4px; background:#e0e0e0; border:2px solid #bdbdbd; border-radius:8px; padding:4px 10px; font-size:11px; font-weight:600; transition:all 0.15s; }
        .mv-chip-lote.escaneado { background:#c8e6c9; border-color:#43a047; color:#1b5e20; }
        .mv-chip-lote.em-iipr { background:#e8eaf6; border-color:#7986cb; color:#283593; opacity:0.5; }
        .mv-chip-lote .mv-chip-qtd { font-size:10px; color:#888; }
        .mv-chip-lote.escaneado .mv-chip-qtd { color:#2e7d32; }
        .mv-card-iipr { background:#fff; border:2px solid #f9a825; border-radius:10px; padding:12px; min-width:260px; max-width:400px; flex:1; position:relative; }
        .mv-card-iipr.fechado { border-color:#2e7d32; background:#f1f8e9; }
        .mv-card-iipr-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
        .mv-card-iipr-titulo { font-weight:700; font-size:13px; color:#f57f17; }
        .mv-card-iipr.fechado .mv-card-iipr-titulo { color:#2e7d32; }
        .mv-card-iipr-lotes { display:flex; flex-wrap:wrap; gap:4px; min-height:30px; padding:6px; background:#fffde7; border:1px dashed #f9a825; border-radius:6px; margin-bottom:8px; }
        .mv-card-iipr.fechado .mv-card-iipr-lotes { background:#e8f5e9; border-color:#a5d6a7; border-style:solid; }
        .mv-chip-dentro { display:inline-flex; align-items:center; gap:3px; background:#fff8e1; border:1px solid #ffb300; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:600; }
        .mv-card-iipr.fechado .mv-chip-dentro { background:#c8e6c9; border-color:#66bb6a; }
        .mv-card-iipr-campo { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
        .mv-card-iipr-campo label { font-size:12px; font-weight:600; color:#555; white-space:nowrap; }
        .mv-card-iipr-campo input { flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:5px; font-size:13px; }
        .mv-card-iipr-acoes { display:flex; gap:6px; flex-wrap:wrap; }
        .mv-card-correios { background:#fff; border:3px solid #1565c0; border-radius:12px; padding:14px; min-width:320px; flex:1; position:relative; }
        .mv-card-correios.fechado { border-color:#2e7d32; background:#f1f8e9; }
        .mv-card-correios-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .mv-card-correios-titulo { font-weight:700; font-size:14px; color:#1565c0; }
        .mv-card-correios.fechado .mv-card-correios-titulo { color:#2e7d32; }
        .mv-card-correios-conteudo { display:flex; flex-wrap:wrap; gap:8px; min-height:40px; padding:8px; background:#e3f2fd; border:1px dashed #1565c0; border-radius:8px; margin-bottom:10px; }
        .mv-card-correios.fechado .mv-card-correios-conteudo { background:#e8f5e9; border-color:#a5d6a7; border-style:solid; }
        .mv-mini-iipr { display:inline-flex; align-items:center; gap:4px; background:#fff3e0; border:2px solid #f9a825; border-radius:8px; padding:5px 10px; font-size:11px; font-weight:700; cursor:pointer; }
        .mv-mini-iipr.selecionado { background:#1565c0; color:#fff; border-color:#0d47a1; }
        .mv-mini-iipr.dentro { cursor:default; background:#c8e6c9; border-color:#2e7d32; color:#1b5e20; }
        .mv-card-correios-campo { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
        .mv-card-correios-campo label { font-size:12px; font-weight:600; color:#555; white-space:nowrap; }
        .mv-card-correios-campo input { flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:5px; font-size:13px; }
        .mv-btn { padding:8px 14px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; transition:opacity 0.15s; }
        .mv-btn:hover { opacity:0.85; }
        .mv-btn:disabled { opacity:0.4; cursor:not-allowed; }
        .mv-btn-amber { background:#f9a825; color:#fff; }
        .mv-btn-blue { background:#1565c0; color:#fff; }
        .mv-btn-indigo { background:#283593; color:#fff; }
        .mv-btn-green { background:#2e7d32; color:#fff; }
        .mv-btn-orange { background:#e65100; color:#fff; }
        .mv-btn-dark { background:#37474f; color:#fff; }
        .mv-btn-red { background:#c62828; color:#fff; }
        .mv-btn-sm { padding:4px 10px; font-size:11px; border-radius:4px; }
        .mv-resumo { background:#e3f2fd; border:1px solid #90caf9; border-radius:8px; padding:10px; margin-top:10px; font-size:12px; }
        .mv-resumo-linha { margin-bottom:4px; }

        /* ---- Toggles de som/creditos ---- */
        .toggles-fim{display:flex;gap:10px;align-items:center;margin-left:8px;}
        .toggle-label{display:flex;align-items:center;gap:4px;cursor:pointer;user-select:none;}
        .toggle-icon-label{font-size:16px;}
        .toggle-slider-wrap{position:relative;display:inline-block;width:36px;height:20px;}
        .toggle-slider-wrap input{opacity:0;width:0;height:0;}
        .toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;border-radius:20px;transition:.3s;}
        .toggle-slider:before{content:"";position:absolute;height:14px;width:14px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
        .toggle-slider-wrap input:checked + .toggle-slider{background:#28a745;}
        .toggle-slider-wrap input:checked + .toggle-slider:before{transform:translateX(16px);}

        /* ==========================================================
           CREDITOS ESTILO CINEMA — perspectiva real
           ========================================================== */
        #creditsOverlay{
            display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            background:#000;z-index:99999;overflow:hidden;
        }
        /* container que cria o contexto de perspectiva 3D */
        #creditsScene{
            position:absolute;top:0;left:0;width:100%;height:100%;
            perspective:350px;
            perspective-origin:50% 0%;
        }
        /* faixa inclinada que sobe */
        #creditsTrack{
            position:absolute;
            /* top e animacao definidos 100% via JS (keyframe dinamico) */
            left:5%;
            width:90%;
            text-align:center;
            transform:rotateX(25deg);
            transform-origin:50% 100%;
        }
        @keyframes creditsSobe{
            from{ top:9000px; }
            to  { top:-9000px; }
        }
        /* THE END — bloco fixo que sobe e para no centro */
        #theEndDiv{
            display:none;position:fixed;left:50%;
            z-index:100001;
            animation:theEndSobe 2.8s cubic-bezier(.16,.8,.3,1) forwards;
        }
        @keyframes theEndSobe{
            from{ top:110%; transform:translateX(-50%); }
            to  { top:50%;  transform:translate(-50%,-50%); }
        }
        .the-end-texto{
            display:block;font-family:Georgia,serif;font-weight:bold;
            font-size:90px;color:#fff;letter-spacing:22px;text-align:center;
            text-shadow:0 0 100px rgba(255,255,255,.12);
        }
        /* dica de fechar */
        #creditsDica{
            position:fixed;bottom:22px;left:50%;transform:translateX(-50%);
            font-size:10px;color:#222;letter-spacing:4px;text-transform:uppercase;
            z-index:100000;pointer-events:none;font-family:Arial,sans-serif;white-space:nowrap;
        }
        /* --- tipografia dos créditos (só texto, sem tabelas) --- */
        .cr-cat{
            font-size:28px;color:#999;letter-spacing:6px;text-transform:uppercase;
            margin:90px 0 18px 0;font-family:Arial,sans-serif;
        }
        .cr-cat-first{margin-top:0;}
        .cr-pessoa{
            font-size:64px;color:#fff;font-weight:bold;
            letter-spacing:2px;margin:6px 0 28px 0;font-family:Georgia,serif;
        }
        .cr-sub{
            font-size:38px;color:#bbb;letter-spacing:4px;
            margin:6px 0 16px 0;font-family:Arial,sans-serif;text-transform:uppercase;
        }
        .cr-linha{
            font-size:44px;color:#ccc;margin:16px 0;
            font-family:Arial,sans-serif;letter-spacing:1px;
        }
        .cr-linha-sm{
            font-size:32px;color:#999;margin:10px 0;
            font-family:Arial,sans-serif;letter-spacing:1px;
        }
        .cr-num{
            font-size:110px;color:#fff;font-weight:bold;
            font-family:Georgia,serif;margin:6px 0 0 0;line-height:1;
        }
        .cr-num-label{
            font-size:28px;color:#777;letter-spacing:6px;text-transform:uppercase;
            margin:8px 0 50px 0;font-family:Arial,sans-serif;
        }
        .cr-num.alerta{color:#8b0000;}
        .cr-sep{border:none;border-top:1px solid #2a2a2a;margin:80px auto;width:45%;}
        .cr-rod{
            font-size:22px;color:#444;letter-spacing:5px;text-transform:uppercase;
            margin:10px 0;font-family:Arial,sans-serif;
        }

        @media print {
        @media print {
            body > *:not(#painelImpressaoOficio) { display: none !important; }
            #painelImpressaoOficio { display: block !important; padding: 20px; }
            .topo-status, .barras-topo, .filtro-datas, .painel-bloqueio, #painelMaloteamento, #conteudoPagina, audio { display: none !important; }
        }
    </style>
</head>
<body>
<div class="topo-status">
    <a href="inicio.php" class="btn-voltar">&larr; Inicio</a>
    <div class="versao">v0.9.46</div>
    <div class="toggles-fim" id="togglesFim">
        <label class="toggle-label" title="Som final de conferencia">
            <span class="toggle-icon-label">&#128266;</span>
            <span class="toggle-slider-wrap">
                <input type="checkbox" id="toggleSomFinal" checked>
                <span class="toggle-slider"></span>
            </span>
        </label>
        <label class="toggle-label" title="Creditos ao finalizar">
            <span class="toggle-icon-label">&#127909;</span>
            <span class="toggle-slider-wrap">
                <input type="checkbox" id="toggleCreditos" checked>
                <span class="toggle-slider"></span>
            </span>
        </label>
    </div>
    <div id="indicador-dias" class="collapsed">
        <div class="indicador-header" onclick="toggleIndicadorDias()" title="Recolher/Expandir">
            <span>Status de Conferencias</span>
            <span class="indicador-toggle">&#9660;</span>
        </div>
        <div class="indicador-conteudo">
            <div style="margin:10px 0;">
                <strong style="color:#28a745;font-size:12px;">Ultimas Conferencias:</strong><br>
                <div style="margin-top:5px;">
                    <?php 
                    $ultimas_cinco = array_slice($dias_com_conferencia, 0, 5);
                    if (!empty($ultimas_cinco)) {
                        foreach ($ultimas_cinco as $data) {
                            $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                            $badge_label = !empty($label_dia) ? " <span class='badge-dia'>$label_dia</span>" : '';
                            echo '<span class="badge-data conferida">' . e($data) . $badge_label . '</span>';
                        }
                    } else {
                        echo '<span style="color:#999;font-size:11px;">Nenhuma</span>';
                    }
                    ?>
                </div>
            </div>
            <div style="margin:10px 0;">
                <strong style="color:#ffc107;font-size:12px;">Conferencias Pendentes:</strong><br>
                <div style="margin-top:5px;">
                    <?php 
                    $ultimas_pendentes = array_slice($dias_sem_conferencia, 0, 5);
                    if (!empty($ultimas_pendentes)) {
                        foreach ($ultimas_pendentes as $data) {
                            $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                            $badge_label = !empty($label_dia) ? " <span class='badge-dia'>$label_dia</span>" : '';
                            echo '<span class="badge-data pendente">' . e($data) . $badge_label . '</span>';
                        }
                    } else {
                        echo '<span style="color:#999;font-size:11px;">Nenhuma</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($db_error !== null): ?>
<div style="background:#fff3cd;color:#856404;border:1px solid #ffc107;padding:12px 20px;border-radius:8px;margin-bottom:16px;font-size:14px;">
    Erro ao conectar ao banco de dados: <?php echo e($db_error); ?>
</div>
<?php endif; ?>

<h2>Conferencia de Pacotes v0.9.46</h2>

<div class="overlay-usuario" id="overlayUsuario">
    <div class="card">
        <h3>Informe o responsavel</h3>
        <div style="font-size:12px; color:#666;">Obrigatorio para iniciar a conferencia.</div>
        <input type="text" id="usuario_conf_modal" placeholder="Digite o responsavel" autocomplete="off">
        <div style="display:flex; gap:10px; margin-top:10px;">
            <button type="button" id="btnConfirmarUsuario" style="flex:1;">Confirmar</button>
            <button type="button" id="btnCancelarUsuario" style="flex:0; padding:10px 16px; background:#e53935; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Cancelar</button>
        </div>
    </div>
</div>

<div class="overlay-tipo" id="overlayTipo" style="display:none;">
    <div class="card">
        <h3>Tipo de conferencia</h3>
        <div style="font-size:12px; color:#666;">Escolha para iniciar.</div>
        <button type="button" class="btn-opcao" data-tipo="correios">Correios</button>
        <button type="button" class="btn-opcao pt" data-tipo="poupatempo">Poupa Tempo</button>
        <button type="button" class="btn-opcao todos" data-tipo="todos" style="background:#455a64;">Todos</button>
    </div>
</div>

<div id="conteudoPagina" class="page-locked">

<div class="barras-topo">
    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">Responsavel da conferencia</div>
        <span class="usuario-badge" id="usuarioBadge">Nao informado</span>
    </div>

    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">Tipo de conferencia</div>
        <label style="gap:8px; margin-right:16px;">
            <input type="radio" name="tipo_inicio" value="correios" checked>
            Correios
        </label>
        <label style="gap:8px; margin-right:16px;">
            <input type="radio" name="tipo_inicio" value="poupatempo">
            Poupa Tempo
        </label>
        <label style="gap:8px;">
            <input type="radio" name="tipo_inicio" value="todos">
            Todos
        </label>
    </div>

    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">Beep de leitura</div>
        <label style="gap:10px;">
            <span class="switch">
                <input type="checkbox" id="muteBeep">
                <span class="slider"></span>
            </span>
            Silenciar
        </label>
    </div>
</div>
<input type="checkbox" id="autoSalvar" checked style="display:none;">

<div class="filtro-datas">
    <form method="get" action="">
        <strong>Filtrar por intervalo:</strong>
        <div class="filtro-row">
            <input type="date" name="data_ini" value="<?php echo e($data_ini); ?>">
            <input type="date" name="data_fim" value="<?php echo e($data_fim); ?>">
            <input type="submit" value="Aplicar Filtro">
        </div>
        <label style="min-width:100%;">
            Datas avulsas (dd-mm-aaaa ou yyyy-mm-dd, separadas por virgula):
            <input type="text" name="datas_avulsas" value="<?php echo e($datas_avulsas); ?>" style="width:100%; margin-top:4px;">
        </label>
    </form>
</div>

<div class="painel-bloqueio">
    <h4>Postos que nao devem ser enviados</h4>
    <div class="bloqueio-form">
        <input type="text" id="postoBloqueioNumero" placeholder="Posto (numero)">
        <input type="text" id="postoBloqueioNome" placeholder="Nome/descricao (opcional)">
        <button type="button" class="btn-acao btn-cancelar" id="btnAdicionarBloqueio">Adicionar</button>
    </div>
    <div class="bloqueio-lista" id="listaPostosBloqueados">
        <?php foreach ($postos_bloqueados as $pb) { ?>
            <div class="bloqueio-item" data-posto="<?php echo e($pb['posto']); ?>">
                <div>
                    <span class="posto"><?php echo e($pb['posto']); ?></span>
                    <span><?php echo e($pb['nome']); ?></span>
                </div>
                <button type="button" class="btn-acao btn-cancelar" data-remover="<?php echo e($pb['posto']); ?>">Remover</button>
            </div>
        <?php } ?>
    </div>
</div>

<div class="painel-pacotes-novos" id="painelPacotesNovos" style="display:none;">
    <strong>Pacotes nao listados</strong>
    <div style="margin-top:8px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:12px;">
            <thead>
                <tr>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Lote</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Regional</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Posto</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Qtd</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Data</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Turno</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Responsavel</th>
                    <th style="padding:4px 6px; border:1px solid #ccc; background:#f0f0f0;">Acao</th>
                </tr>
            </thead>
            <tbody id="listaPacotesNovos"></tbody>
        </table>
    </div>
    <div style="margin-top:12px; padding:10px; background:#e8f5e9; border:1px solid #a5d6a7; border-radius:4px;">
        <strong style="color:#2e7d32;">Gravar lotes em ciPostos</strong>
        <div style="margin-top:4px; font-size:11px; color:#555;">Os campos abaixo sao padrao para todos os lotes. Edite individualmente na tabela acima para diferenciar por responsavel ou turno.</div>
        <div style="margin-top:8px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label style="font-size:13px;">Data de expedicao:
                <input type="date" id="pendentesDataExp" style="padding:4px 8px; border:1px solid #ced4da; border-radius:4px;">
            </label>
            <label style="font-size:13px;">Turno padrao:
                <select id="pendentesTurno" style="padding:4px 8px; border:1px solid #ced4da; border-radius:4px;">
                    <option value="0">Madrugada</option>
                    <option value="1" selected>Manha</option>
                    <option value="2">Tarde</option>
                    <option value="3">Noite</option>
                </select>
            </label>
            <label style="font-size:13px;">Responsavel padrao:
                <input type="text" id="pendentesResponsavel" placeholder="Nome do responsavel" style="padding:4px 8px; border:1px solid #ced4da; border-radius:4px; width:180px;">
            </label>
            <button type="button" class="btn-acao btn-salvar" id="btnSalvarPacotes" style="background:#2e7d32;">Gravar todos os lotes</button>
            <button type="button" class="btn-acao btn-cancelar" id="btnCancelarPacotes">Limpar lista</button>
        </div>
    </div>
</div>

<div class="modal-pacote" id="modalPacote">
    <div class="card">
        <h3>Pacote nao encontrado</h3>
        <div style="font-size:12px; color:#666;">Informe os dados para inserir nas bases.</div>
        <label>Codigo de barras</label>
        <input type="text" id="pacote_codbar" readonly>
        <label>Lote</label>
        <input type="text" id="pacote_lote">
        <label>Regional</label>
        <input type="text" id="pacote_regional">
        <label>Posto</label>
        <input type="text" id="pacote_posto">
        <label>Quantidade</label>
        <input type="number" id="pacote_qtd" min="1">
        <label>Data de expedicao</label>
        <input type="date" id="pacote_dataexp">
        <label>Responsavel</label>
        <input type="text" id="pacote_responsavel" placeholder="Opcional">
        <input type="hidden" id="pacote_idx" value="">
        <div style="margin-top:10px; display:flex; gap:8px;">
            <button type="button" class="btn-acao btn-salvar" id="btnAdicionarPacote">Adicionar</button>
            <button type="button" class="btn-acao btn-cancelar" id="btnCancelarPacote">Cancelar</button>
        </div>
    </div>
</div>

<button class="btn-toggle" type="button" onclick="var el=document.getElementById('painel-estatisticas'); el.style.display = (el.style.display==='none'?'block':'none');">
    Mostrar/Ocultar Estatisticas
</button>

<div id="painel-estatisticas" style="display:block;">
    <div class="cards-resumo">
        <div class="card-resumo">
            <h4>Pacotes na tela</h4>
            <div class="valor"><?php echo (int)$total_codigos; ?></div>
        </div>
        <div class="card-resumo">
            <h4>Carteiras emitidas</h4>
            <div class="valor"><?php echo number_format((int)$stats['carteiras_emitidas'], 0, ',', '.'); ?></div>
        </div>
        <div class="card-resumo">
            <h4>Carteiras conferidas</h4>
            <div class="valor"><?php echo number_format((int)$stats['carteiras_conferidas'], 0, ',', '.'); ?></div>
        </div>
        <div class="card-resumo">
            <h4>Postos com retirada</h4>
            <div class="valor"><?php echo (int)$stats['postos_conferidos']; ?></div>
        </div>
        <div class="card-resumo">
            <h4>Pacotes conferidos</h4>
            <div class="valor"><?php echo (int)$stats['pacotes_conferidos']; ?></div>
        </div>
    </div>
</div>

<div>
    <input type="text" id="codigo_barras" placeholder="Escaneie o codigo de barras (19 digitos) ou numero do lote (8 digitos)" maxlength="19" autofocus>
    <button id="resetar">Resetar Conferencia</button>
    <div class="mensagem-leitura" id="mensagemLeitura"></div>
</div>

<div id="tabelas">
<?php
$grupo_pt = array();
$grupo_r01 = array();
$grupo_capital = array();
$grupo_999 = array();
$grupo_outros = array();

foreach ($regionais_data as $regional => $postos) {
    foreach ($postos as $posto) {
        if ($posto['tipoEntrega'] == 'poupatempo') {
            $postoKey = $posto['posto'];
            if (!isset($grupo_pt[$postoKey])) {
                $grupo_pt[$postoKey] = array();
            }
            $grupo_pt[$postoKey][] = $posto;
            continue;
        }
        if ($regional == 1) {
            $grupo_r01[] = $posto;
            continue;
        }
        if ($regional == 0) {
            $grupo_capital[] = $posto;
            continue;
        }
        if ($regional == 999) {
            $grupo_999[] = $posto;
            continue;
        }
        if (!isset($grupo_outros[$regional])) {
            $grupo_outros[$regional] = array();
        }
        $grupo_outros[$regional][] = $posto;
    }
}

ksort($grupo_outros);

function renderizarBanner($texto, $classe) {
    echo '<div class="banner-grupo ' . $classe . '">' . e($texto) . '</div>';
}

function renderizarTabela($titulo, $dados, $ehPoupaTempo = false, $ptGroup = '', $nomeRegional = '') {
    global $postosComRegra, $regrasDiasPorPosto, $nomeDiasSemana;
    if (empty($dados)) {
        return;
    }
    
    $primeiro = reset($dados);
    $eh_array_plano = isset($primeiro['lote']);
    
    $postos_para_exibir = array();
    if ($eh_array_plano) {
        $postos_para_exibir = $dados;
    } else {
        foreach ($dados as $regional => $postos) {
            foreach ($postos as $posto) {
                $postos_para_exibir[] = $posto;
            }
        }
    }
    
    $total_pacotes = count($postos_para_exibir);
    $total_conferidos = 0;
    foreach ($postos_para_exibir as $posto) {
        if ($posto['conf'] == 1) {
            $total_conferidos++;
        }
    }
    
    echo '<h3>' . e($titulo);
    echo ' <span class="span-conferidos-count" style="color:#666; font-weight:normal; font-size:14px;">(' . $total_pacotes . ' pacotes / ' . $total_conferidos . ' conferidos)</span>';
    if ($nomeRegional !== '') {
        echo ' <span style="color:#0d47a1; font-weight:600; font-size:14px;"> - ' . e($nomeRegional) . '</span>';
    }
    if ($ehPoupaTempo) {
        echo ' <span class="tag-pt">POUPA TEMPO</span>';
    }
    echo '</h3>';
    echo '<table class="tabela-conferencia">';
    echo '<thead><tr>';
    echo '<th class="sortable sort-asc" data-col="0" onclick="ordenarTabela(this, 0)">Regional <span class="sort-arrow">&#9650;</span></th>';
    echo '<th class="sortable sort-asc" data-col="1" onclick="ordenarTabela(this, 1)">Lote <span class="sort-arrow">&#9650;</span></th>';
    echo '<th class="sortable sort-asc" data-col="2" onclick="ordenarTabela(this, 2)">Posto <span class="sort-arrow">&#9650;</span></th>';
    echo '<th class="sortable sort-asc" data-col="3" onclick="ordenarTabela(this, 3)">Data Expedicao <span class="sort-arrow">&#9650;</span></th>';
    echo '<th>Quantidade</th>';
    echo '<th>Codigo de Barras</th>';
    echo '<th>Data/Hora Leitura</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($postos_para_exibir as $posto) {
        $classeConf = ($posto['conf'] == 1) ? ' confirmado' : '';
        $classeRegra = '';
        $tituloRegra = '';
        $postoKey = str_pad($posto['posto'], 3, '0', STR_PAD_LEFT);
        if (isset($postosComRegra[$postoKey]) && isset($regrasDiasPorPosto[$postoKey])) {
            $classeRegra = ' regra-envio';
            $dias = array();
            foreach ($regrasDiasPorPosto[$postoKey] as $d) {
                if (isset($nomeDiasSemana[$d])) {
                    $dias[] = $nomeDiasSemana[$d];
                }
            }
            $tituloRegra = 'Regra de envio: somente ' . implode(', ', $dias);
        }
        echo '<tr class="linha-conferencia' . $classeConf . $classeRegra . '" ';
        if ($tituloRegra !== '') {
            echo 'title="' . e($tituloRegra) . '" ';
        }
        echo 'data-codigo="' . e($posto['codigo']) . '" ';
        echo 'data-regional="' . e($posto['regional']) . '" ';
        echo 'data-lote="' . e($posto['lote']) . '" ';
        echo 'data-posto="' . e($posto['posto']) . '" ';
        echo 'data-data="' . e($posto['data']) . '" ';
        $data_sql_attr = isset($posto['data_sql']) ? $posto['data_sql'] : '';
        echo 'data-data-sql="' . e($data_sql_attr) . '" ';
        echo 'data-qtd="' . e($posto['qtd']) . '" ';
        echo 'data-ispt="' . $posto['isPT'] . '" ';
        $rr = isset($posto['regional_real']) ? $posto['regional_real'] : '';
        echo 'data-regional-real="' . e($rr) . '" ';
        echo 'data-pt-group="' . e($ptGroup) . '">';
        $reg_display = isset($posto['regional_display']) ? $posto['regional_display'] : $posto['regional'];
        echo '<td>' . e($reg_display) . '</td>';
        echo '<td>' . e($posto['lote']) . '</td>';
        echo '<td>' . e($posto['posto']) . '</td>';
        echo '<td>' . e($posto['data']) . '</td>';
        echo '<td>' . e($posto['qtd']) . '</td>';
        echo '<td>' . e($posto['codigo']) . '</td>';
        $lido_em_val = isset($posto['lido_em']) ? $posto['lido_em'] : '';
        echo '<td class="cel-lido-em">' . e($lido_em_val) . '</td>';
        if ($classeRegra !== '') {
            echo '<td style="padding:0 4px;"><span style="background:#fff3cd;color:#856404;padding:2px 6px;border-radius:4px;font-size:11px;" title="' . e($tituloRegra) . '">&#9888;</span></td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}

$banner_correios_exibido = false;
$banner_pt_exibido = false;
if (!empty($grupo_pt)) {
    ksort($grupo_pt);
    echo '<div class="secao-tipo" data-secao-tipo="poupatempo">';
    if (!$banner_pt_exibido) {
        renderizarBanner('POSTOS DO POUPA TEMPO', 'banner-pt');
        $banner_pt_exibido = true;
    }
    foreach ($grupo_pt as $postoKey => $postosPt) {
        $ptNome = isset($regionaisNomes[$postoKey]) ? $regionaisNomes[$postoKey] : '';
        renderizarTabela('Posto ' . $postoKey . ' - Poupa Tempo', $postosPt, true, $postoKey, $ptNome);
    }
    echo '</div>';
}
echo '<div class="secao-tipo" data-secao-tipo="correios">';
$banner_correios_dentro = false;
if (!empty($grupo_r01)) {
    if (!$banner_correios_exibido && !$banner_correios_dentro) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
        $banner_correios_dentro = true;
    }
    renderizarTabela('Postos do Posto 01', $grupo_r01);
}
if (!empty($grupo_capital)) {
    if (!$banner_correios_exibido && !$banner_correios_dentro) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
        $banner_correios_dentro = true;
    }
    echo '<div class="banner-grupo banner-capital" style="background:#e8f5e9;color:#2e7d32;padding:6px 16px;font-weight:700;border-radius:4px;margin:10px 0 6px;">Capital</div>';
    $capital_por_posto = array();
    foreach ($grupo_capital as $p) {
        $pk = str_pad($p['posto'], 3, '0', STR_PAD_LEFT);
        if (!isset($capital_por_posto[$pk])) {
            $capital_por_posto[$pk] = array();
        }
        $capital_por_posto[$pk][] = $p;
    }
    ksort($capital_por_posto);
    foreach ($capital_por_posto as $pk => $postos_cap) {
        $nomeCap = isset($regionaisNomes[$pk]) ? $regionaisNomes[$pk] : '';
        renderizarTabela('Posto ' . $pk . ' - Capital', $postos_cap, false, '', $nomeCap);
    }
}
if (!empty($grupo_999)) {
    if (!$banner_correios_exibido && !$banner_correios_dentro) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
        $banner_correios_dentro = true;
    }
    renderizarTabela('Postos da Central IIPR', $grupo_999);
}
if (!empty($grupo_outros)) {
    if (!$banner_correios_exibido && !$banner_correios_dentro) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
        $banner_correios_dentro = true;
    }
    foreach ($grupo_outros as $regional => $postos) {
        $regionalStr = str_pad($regional, 3, '0', STR_PAD_LEFT);
        $nomeReg = isset($regionaisNomes[$regional]) ? $regionaisNomes[$regional] : '';
        if ($nomeReg === '') {
            $nomeReg = isset($regionaisNomes[$regionalStr]) ? $regionaisNomes[$regionalStr] : '';
        }
        renderizarTabela($regionalStr . ' - Regional ' . $regionalStr, array($regional => $postos), false, '', $nomeReg);
    }
}
echo '</div>';

if (empty($regionais_data)) {
    echo '<p style="text-align:center; margin-top:40px; color:#999;">Nenhum dado encontrado para as datas selecionadas.</p>';
}
?>

</div>

<div id="painelMaloteamento" style="display:none; margin-top:20px;">
    <div style="background:#fff; border:2px solid #1565c0; border-radius:12px; padding:16px; margin-bottom:16px;">
        <h3 style="color:#1565c0; margin-bottom:12px; cursor:pointer;" onclick="togglePainelMaloteamento()">Maloteamento Visual <span id="toggleMaloteIcon" style="font-size:14px;">&#9654;</span></h3>
        <div id="maloteamentoConteudo" style="display:none;">

            <div style="background:#e3f2fd; border:1px solid #90caf9; border-radius:8px; padding:12px; margin-bottom:14px;">
                <label style="font-weight:700; color:#1565c0; margin-right:8px;">Escanear lote para maloteamento (19 digitos):</label>
                <input type="text" id="mvScanInput" maxlength="19" placeholder="Escaneie o codigo de barras do lote" style="padding:8px 12px; border:2px solid #1565c0; border-radius:6px; font-size:16px; width:300px; font-weight:600;">
                <span id="mvScanMsg" style="margin-left:10px; font-size:13px; font-weight:600;"></span>
            </div>

            <div class="mv-secoes-container">
            <div class="mv-secao" style="flex:2; min-width:400px;">
                <div class="mv-secao-header">
                    <span class="mv-secao-titulo">Lotes Conferidos por Grupo</span>
                    <span class="mv-secao-count" id="mvCountDisponiveis">0</span>
                    <span style="font-size:11px; color:#666; margin-left:6px;">(escaneados para maloteamento: <strong id="mvCountEscaneados">0</strong>)</span>
                </div>
                <div id="mvLotesDisponiveis" class="mv-lotes-tabela-area">
                    <span class="mv-vazio">Nenhum lote conferido ainda. Escaneie pacotes para iniciar.</span>
                </div>
                <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    <button type="button" id="btnFecharLotesIIPR" class="mv-btn mv-btn-amber" disabled>Fechar Malote IIPR com lotes escaneados</button>
                </div>
            </div>

            <div class="mv-secao">
                <div class="mv-secao-header">
                    <span class="mv-secao-titulo">Malotes IIPR</span>
                    <span class="mv-secao-count" id="mvCountIIPR">0</span>
                </div>
                <div id="mvMalotesIIPR" class="mv-malotes-area">
                    <span class="mv-vazio">Nenhum malote IIPR criado.</span>
                </div>
            </div>

            <div class="mv-secao">
                <div class="mv-secao-header">
                    <span class="mv-secao-titulo">Malotes Correios</span>
                    <span class="mv-secao-count" id="mvCountCorreios">0</span>
                </div>
                <div id="mvMalotesCorreios" class="mv-malotes-area">
                    <span class="mv-vazio">Nenhum malote Correios criado.</span>
                </div>
                <div style="margin-top:10px;">
                    <button type="button" id="btnCriarMaloteCorreios" class="mv-btn mv-btn-indigo">+ Novo Malote Correios</button>
                </div>
            </div>
            </div>

            <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap; border-top:2px solid #e0e0e0; padding-top:14px;">
                <button type="button" id="btnSalvarMaloteamento" class="mv-btn mv-btn-green" style="padding:10px 20px;">Salvar no Banco</button>
                <button type="button" id="btnSobrescreverMaloteamento" class="mv-btn mv-btn-orange" style="padding:10px 20px;">Salvar (Sobrescrever)</button>
                <button type="button" id="btnImprimirMaloteamento" class="mv-btn mv-btn-dark" style="padding:10px 20px;">Imprimir Oficio</button>
            </div>
        </div>
    </div>
</div>

</div>

<div id="painelImpressaoOficio" style="display:none;">
    <div style="text-align:center; margin-bottom:20px;">
        <img src="logo_celepar.png" alt="Celepar" style="max-height:60px;">
        <h2 style="margin-top:8px; font-size:18px;">OFICIO DE REMESSA - CORREIOS</h2>
        <div id="oficioImpressaoData" style="font-size:12px; color:#555;"></div>
    </div>
    <table id="tabelaOficioImpressao" style="width:100%; border-collapse:collapse; font-size:11px;">
        <thead>
            <tr>
                <th style="border:1px solid #000; padding:4px 6px; background:#e9e9e9;">Posto</th>
                <th style="border:1px solid #000; padding:4px 6px; background:#e9e9e9;">Lacre IIPR</th>
                <th style="border:1px solid #000; padding:4px 6px; background:#e9e9e9;">Lacre Correios</th>
                <th style="border:1px solid #000; padding:4px 6px; background:#e9e9e9;">Etiqueta Correios</th>
            </tr>
        </thead>
        <tbody id="corpoOficioImpressao"></tbody>
    </table>
    <div style="margin-top:40px; display:flex; justify-content:space-between; gap:15px;">
        <div style="flex:1; border-right:1px solid #000; padding-right:12px; text-align:center;">
            <strong>Conferido por:</strong>
            <div style="margin-top:40px; border-top:1px solid #000; padding-top:3px;">______________________________</div>
            <div style="font-size:11px; margin-top:3px;"><strong>IIPR Data:</strong> _____/_____/________</div>
        </div>
        <div style="flex:1; padding-left:12px; text-align:center;">
            <strong>Expedido por:</strong>
            <div style="margin-top:40px; border-top:1px solid #000; padding-top:3px;">______________________________</div>
            <div style="font-size:11px; margin-top:3px;"><strong>Celepar - Data:</strong> <?php echo date('d-m-Y'); ?></div>
        </div>
    </div>
</div>

<audio id="beep" src="beep.mp3" preload="auto"></audio>
<audio id="concluido" src="concluido.mp3" preload="auto"></audio>
<audio id="pacotejaconferido" src="pacotejaconferido.mp3" preload="auto"></audio>
<audio id="pacotedeoutraregional" src="pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="posto_poupatempo" src="posto_poupatempo.mp3" preload="auto"></audio>
<audio id="pertence_correios" src="pertence_aos_correios.mp3" preload="auto"></audio>
<audio id="somFinalConf" src="som_final_de_conferencia.mp3" preload="auto" loop></audio>

<script>
function ordenarTabela(th, colIdx) {
    var table = th.parentNode.parentNode.parentNode;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var rows = [];
    var trs = tbody.querySelectorAll('tr');
    for (var i = 0; i < trs.length; i++) {
        rows.push(trs[i]);
    }
    var isAsc = th.classList.contains('sort-asc');
    var siblings = th.parentNode.querySelectorAll('th.sortable');
    for (var s = 0; s < siblings.length; s++) {
        siblings[s].classList.remove('sort-asc');
        siblings[s].classList.remove('sort-desc');
        siblings[s].classList.add('sort-asc');
        var arr = siblings[s].querySelector('.sort-arrow');
        if (arr) arr.innerHTML = '&#9650;';
    }
    if (isAsc) {
        th.classList.remove('sort-asc');
        th.classList.add('sort-desc');
        var arrow = th.querySelector('.sort-arrow');
        if (arrow) arrow.innerHTML = '&#9660;';
    } else {
        th.classList.remove('sort-desc');
        th.classList.add('sort-asc');
        var arrow2 = th.querySelector('.sort-arrow');
        if (arrow2) arrow2.innerHTML = '&#9650;';
    }
    var isDateCol = (colIdx === 3);
    var dir = isAsc ? -1 : 1;
    rows.sort(function(a, b) {
        var cellsA = a.querySelectorAll('td');
        var cellsB = b.querySelectorAll('td');
        if (colIdx >= cellsA.length || colIdx >= cellsB.length) return 0;
        var valA = (cellsA[colIdx].textContent || '').replace(/^\s+|\s+$/g, '');
        var valB = (cellsB[colIdx].textContent || '').replace(/^\s+|\s+$/g, '');
        if (isDateCol) {
            var pA = valA.match(/^(\d{2})-(\d{2})-(\d{4})$/);
            var pB = valB.match(/^(\d{2})-(\d{2})-(\d{4})$/);
            if (pA && pB) {
                var dA = pA[3] + pA[2] + pA[1];
                var dB = pB[3] + pB[2] + pB[1];
                if (dA < dB) return -1 * dir;
                if (dA > dB) return 1 * dir;
                return 0;
            }
        }
        if (!isNaN(valA) && !isNaN(valB) && valA !== '' && valB !== '') {
            return (parseFloat(valA) - parseFloat(valB)) * dir;
        }
        if (valA < valB) return -1 * dir;
        if (valA > valB) return 1 * dir;
        return 0;
    });
    for (var r = 0; r < rows.length; r++) {
        tbody.appendChild(rows[r]);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var input = document.getElementById("codigo_barras");
    var radioAutoSalvar = document.getElementById("autoSalvar");
    var beep = document.getElementById("beep");
    var concluido = document.getElementById("concluido");
    var pacoteJaConferido = document.getElementById("pacotejaconferido");
    var pacoteOutraRegional = document.getElementById("pacotedeoutraregional");
    var postoPoupaTempo = document.getElementById("posto_poupatempo");
    var pertenceCorreios = document.getElementById("pertence_correios");
    var muteBeep = document.getElementById("muteBeep");
    var btnResetar = document.getElementById("resetar");
    var usuarioBadge = document.getElementById("usuarioBadge");
    var overlayUsuario = document.getElementById("overlayUsuario");
    var conteudoPagina = document.getElementById("conteudoPagina");
    var usuarioInputModal = document.getElementById("usuario_conf_modal");
    var btnConfirmarUsuario = document.getElementById("btnConfirmarUsuario");
    var overlayTipo = document.getElementById("overlayTipo");
    var usuarioAtual = '';
    var btnCancelarUsuario = document.getElementById("btnCancelarUsuario");
    try {
        var storedUsuario = localStorage.getItem('conferencia_responsavel');
        if (storedUsuario && storedUsuario.length > 0) {
            usuarioAtual = storedUsuario;
        }
    } catch (eLS) {}
    var audioDesbloqueado = false;
    var modalPacote = document.getElementById('modalPacote');
    var pacoteCodbar = document.getElementById('pacote_codbar');
    var pacoteLote = document.getElementById('pacote_lote');
    var pacoteRegional = document.getElementById('pacote_regional');
    var pacotePosto = document.getElementById('pacote_posto');
    var pacoteQtd = document.getElementById('pacote_qtd');
    var pacoteDataexp = document.getElementById('pacote_dataexp');
    var pacoteResponsavel = document.getElementById('pacote_responsavel');
    var pacoteIdx = document.getElementById('pacote_idx');
    var btnAdicionarPacote = document.getElementById('btnAdicionarPacote');
    var btnCancelarPacote = document.getElementById('btnCancelarPacote');
    var painelPacotesNovos = document.getElementById('painelPacotesNovos');
    var listaPacotesNovos = document.getElementById('listaPacotesNovos');
    var btnSalvarPacotes = document.getElementById('btnSalvarPacotes');
    var btnCancelarPacotes = document.getElementById('btnCancelarPacotes');
    var pacotesPendentes = [];

    var postoRegionalRealMap = {};
    (function() {
        var trs = document.querySelectorAll('tbody tr.linha-conferencia');
        for (var ri = 0; ri < trs.length; ri++) {
            var pv = trs[ri].getAttribute('data-posto') || '';
            var rv = trs[ri].getAttribute('data-regional-real') || trs[ri].getAttribute('data-regional') || '';
            if (pv && rv && !postoRegionalRealMap[pv]) {
                postoRegionalRealMap[pv] = rv;
            }
        }
    }());
    var mensagemLeitura = document.getElementById('mensagemLeitura');
    var postoBloqueioNumero = document.getElementById('postoBloqueioNumero');
    var postoBloqueioNome = document.getElementById('postoBloqueioNome');
    var btnAdicionarBloqueio = document.getElementById('btnAdicionarBloqueio');
    var listaPostosBloqueados = document.getElementById('listaPostosBloqueados');
    var postosBloqueados = <?php echo json_encode($postos_bloqueados); ?>;
    var postosBloqueadosMap = {};
    var tipoEscolhido = false;

    var lotesConferidos = [];
    var painelMaloteamento = document.getElementById('painelMaloteamento');
    var datasExibGlobal = <?php echo json_encode($datas_exib); ?>;
    var dataIniSql = <?php echo json_encode(isset($data_ini_sql) ? $data_ini_sql : ''); ?>;
    var dataFimSql = <?php echo json_encode(isset($data_fim_sql) ? $data_fim_sql : ''); ?>;

    var mvNextIiprId = 1;
    var mvNextCorreiosId = 1;
    var mvMalotesIIPR = [];
    var mvMalotesCorreios = [];
    var mvLotesSelecionados = {};
    var mvLotesEscaneados = {};
    var mvIiprSelecionados = {};

    function adicionarLoteSemMalote(lote, posto, regional, qtd, regionalReal, codbar) {
        for (var i = 0; i < lotesConferidos.length; i++) {
            if (lotesConferidos[i].nlote === lote && lotesConferidos[i].nposto === posto) return;
        }
        lotesConferidos.push({ nlote: lote, nposto: posto, regional: regional, qtd: qtd, regional_real: regionalReal || '', codbar: codbar || '' });
        renderizarMV();
    }

    function mvLoteDisponivel(lote) {
        for (var i = 0; i < mvMalotesIIPR.length; i++) {
            for (var j = 0; j < mvMalotesIIPR[i].lotes.length; j++) {
                if (mvMalotesIIPR[i].lotes[j].nlote === lote.nlote && mvMalotesIIPR[i].lotes[j].nposto === lote.nposto) return false;
            }
        }
        return true;
    }

    function mvGetDisponiveis() {
        var arr = [];
        for (var i = 0; i < lotesConferidos.length; i++) {
            if (mvLoteDisponivel(lotesConferidos[i])) {
                arr.push(lotesConferidos[i]);
            }
        }
        arr.sort(function(a, b) {
            var ka = a.regional + '-' + a.nposto + '-' + a.nlote;
            var kb = b.regional + '-' + b.nposto + '-' + b.nlote;
            return ka < kb ? -1 : (ka > kb ? 1 : 0);
        });
        return arr;
    }

    function mvGetIiprFechados() {
        var arr = [];
        for (var i = 0; i < mvMalotesIIPR.length; i++) {
            if (mvMalotesIIPR[i].fechado && !mvMalotesIIPR[i].correiosId) {
                arr.push(mvMalotesIIPR[i]);
            }
        }
        return arr;
    }

    function mvGetIiprById(id) {
        for (var i = 0; i < mvMalotesIIPR.length; i++) {
            if (mvMalotesIIPR[i].id === id) return mvMalotesIIPR[i];
        }
        return null;
    }

    function mvGetCorreiosById(id) {
        for (var i = 0; i < mvMalotesCorreios.length; i++) {
            if (mvMalotesCorreios[i].id === id) return mvMalotesCorreios[i];
        }
        return null;
    }

    function mvLabelCategoria(regionalReal) {
        if (regionalReal === '' || regionalReal === null || typeof regionalReal === 'undefined') return 'OUTROS';
        var r = parseInt(regionalReal, 10);
        if (isNaN(r)) return 'OUTROS';
        if (r === 0) return 'CAPITAL';
        if (r === 999) return 'CENTRAL IIPR';
        return 'REGIONAIS';
    }

    function mvCategoriaOrdem(cat) {
        if (cat === 'CAPITAL') return 1;
        if (cat === 'CENTRAL IIPR') return 2;
        if (cat === 'REGIONAIS') return 3;
        return 4;
    }

    function mvCategoriaCss(cat) {
        if (cat === 'CAPITAL') return 'capital';
        if (cat === 'CENTRAL IIPR') return 'central';
        if (cat === 'REGIONAIS') return 'regional';
        return '';
    }

    function mvToggleIiprSel(id) {
        if (mvIiprSelecionados[id]) {
            delete mvIiprSelecionados[id];
        } else {
            mvIiprSelecionados[id] = true;
        }
        renderizarMV();
    }

    function mvScanLote(codbar) {
        if (!codbar || codbar.length !== 19) return;
        var found = false;
        for (var i = 0; i < lotesConferidos.length; i++) {
            var lt = lotesConferidos[i];
            if (lt.codbar === codbar) {
                var key = lt.nlote + '-' + lt.nposto;
                if (!mvLoteDisponivel(lt)) {
                    mvShowScanMsg('Lote ' + lt.nlote + ' (P:' + lt.nposto + ') ja esta em um malote IIPR.', '#e65100');
                    falarTexto('lote ja inserido em malote');
                    return;
                }
                if (mvLotesEscaneados[key]) {
                    mvShowScanMsg('Lote ' + lt.nlote + ' (P:' + lt.nposto + ') ja escaneado para maloteamento.', '#f57f17');
                    falarTexto('lote ja escaneado');
                    return;
                }
                mvLotesEscaneados[key] = true;
                found = true;
                mvShowScanMsg('Lote ' + lt.nlote + ' - Posto ' + lt.nposto + ' adicionado!', '#2e7d32');
                falarTexto('posto ' + lt.nposto);
                renderizarMV();
                return;
            }
        }
        if (!found) {
            var nlote = codbar.substring(0, 8);
            var reg = codbar.substring(8, 11);
            var nposto = codbar.substring(11, 14);
            for (var j = 0; j < lotesConferidos.length; j++) {
                var lt2 = lotesConferidos[j];
                if (lt2.nlote === nlote && lt2.nposto === nposto) {
                    var key2 = lt2.nlote + '-' + lt2.nposto;
                    if (!mvLoteDisponivel(lt2)) {
                        mvShowScanMsg('Lote ' + lt2.nlote + ' (P:' + lt2.nposto + ') ja esta em um malote IIPR.', '#e65100');
                        falarTexto('lote ja inserido em malote');
                        return;
                    }
                    if (mvLotesEscaneados[key2]) {
                        mvShowScanMsg('Lote ' + lt2.nlote + ' (P:' + lt2.nposto + ') ja escaneado.', '#f57f17');
                        falarTexto('lote ja escaneado');
                        return;
                    }
                    mvLotesEscaneados[key2] = true;
                    found = true;
                    mvShowScanMsg('Lote ' + lt2.nlote + ' - Posto ' + lt2.nposto + ' adicionado!', '#2e7d32');
                    falarTexto('posto ' + lt2.nposto);
                    renderizarMV();
                    return;
                }
            }
        }
        if (!found) {
            mvShowScanMsg('Lote nao encontrado entre os conferidos. Escaneie na conferencia primeiro.', '#c62828');
            falarTexto('lote nao encontrado');
        }
    }

    function mvShowScanMsg(msg, color) {
        var el = document.getElementById('mvScanMsg');
        if (el) {
            el.textContent = msg;
            el.style.color = color || '#333';
        }
    }

    function mvFecharLotesComoIIPR() {
        var escaneadosDisp = [];
        var disponiveis = mvGetDisponiveis();
        for (var i = 0; i < disponiveis.length; i++) {
            var key = disponiveis[i].nlote + '-' + disponiveis[i].nposto;
            if (mvLotesEscaneados[key]) {
                escaneadosDisp.push(disponiveis[i]);
            }
        }
        if (escaneadosDisp.length === 0) {
            alert('Escaneie lotes primeiro antes de fechar o malote IIPR.');
            return;
        }
        var porPosto = {};
        var ordemPosto = [];
        for (var j = 0; j < escaneadosDisp.length; j++) {
            var p = escaneadosDisp[j].nposto;
            if (!porPosto[p]) {
                porPosto[p] = [];
                ordemPosto.push(p);
            }
            porPosto[p].push(escaneadosDisp[j]);
        }
        for (var pi = 0; pi < ordemPosto.length; pi++) {
            var postoKey = ordemPosto[pi];
            var lotesP = porPosto[postoKey];
            var lacre = prompt('Lacre IIPR para Posto ' + postoKey + ' (' + lotesP.length + ' lote' + (lotesP.length > 1 ? 's' : '') + '):');
            if (!lacre || lacre.trim() === '') {
                alert('Lacre obrigatorio. Operacao cancelada para posto ' + postoKey + '.');
                continue;
            }
            lacre = lacre.trim();
            var contadorPosto = 0;
            for (var k = 0; k < mvMalotesIIPR.length; k++) {
                if (mvMalotesIIPR[k].posto === postoKey) contadorPosto++;
            }
            var m = { id: mvNextIiprId++, lacre: lacre, fechado: true, lotes: lotesP, correiosId: null, posto: postoKey, numPosto: contadorPosto + 1 };
            mvMalotesIIPR.push(m);
            for (var li = 0; li < lotesP.length; li++) {
                var ek = lotesP[li].nlote + '-' + lotesP[li].nposto;
                delete mvLotesEscaneados[ek];
            }
            falarTexto('malote i i p r posto ' + postoKey + ' lacre ' + lacre);
        }
        renderizarMV();
    }

    function mvMoverLotesParaIIPR(maloteId) {
        var malote = mvGetIiprById(maloteId);
        if (!malote || malote.fechado) return;
        var disponiveis = mvGetDisponiveis();
        var movidos = 0;
        for (var i = 0; i < disponiveis.length; i++) {
            var key = disponiveis[i].nlote + '-' + disponiveis[i].nposto;
            if (mvLotesSelecionados[key]) {
                if (malote.posto && disponiveis[i].nposto !== malote.posto) {
                    alert('Lote ' + disponiveis[i].nlote + ' pertence ao posto ' + disponiveis[i].nposto + ', mas este malote IIPR e do posto ' + malote.posto + '. Nao e possivel misturar postos.');
                    continue;
                }
                if (!malote.posto) malote.posto = disponiveis[i].nposto;
                malote.lotes.push(disponiveis[i]);
                movidos++;
            }
        }
        if (movidos === 0) { alert('Selecione lotes do posto ' + (malote.posto || '') + ' primeiro.'); return; }
        mvLotesSelecionados = {};
        renderizarMV();
        falarTexto(movidos + ' lotes adicionados');
    }

    function mvRemoverLoteDeIIPR(maloteId, nlote, nposto) {
        var malote = mvGetIiprById(maloteId);
        if (!malote || malote.fechado) return;
        for (var i = 0; i < malote.lotes.length; i++) {
            if (malote.lotes[i].nlote === nlote && malote.lotes[i].nposto === nposto) {
                malote.lotes.splice(i, 1);
                break;
            }
        }
        renderizarMV();
    }

    function mvFecharMaloteIIPR(maloteId) {
        var malote = mvGetIiprById(maloteId);
        if (!malote) return;
        if (malote.lotes.length === 0) { alert('Adicione lotes ao malote antes de fechar.'); return; }
        var inputEl = document.getElementById('mv-lacre-iipr-' + maloteId);
        var lacre = inputEl ? inputEl.value.trim() : '';
        if (lacre === '') { alert('Informe o lacre IIPR antes de fechar.'); if (inputEl) inputEl.focus(); return; }
        malote.lacre = lacre;
        malote.fechado = true;
        renderizarMV();
        falarTexto('malote i i p r fechado');
    }

    function mvReabrirMaloteIIPR(maloteId) {
        var malote = mvGetIiprById(maloteId);
        if (!malote) return;
        if (malote.correiosId) {
            alert('Este malote IIPR ja esta dentro de um malote Correios. Remova-o primeiro.');
            return;
        }
        if (!confirm('Reabrir malote IIPR #' + maloteId + '? Os lotes voltarao para disponiveis.')) return;
        for (var i = 0; i < mvMalotesIIPR.length; i++) {
            if (mvMalotesIIPR[i].id === maloteId) {
                mvMalotesIIPR.splice(i, 1);
                break;
            }
        }
        renderizarMV();
    }

    function mvExcluirMaloteIIPR(maloteId) {
        var malote = mvGetIiprById(maloteId);
        if (!malote) return;
        if (malote.correiosId) {
            alert('Remova este malote IIPR do malote Correios primeiro.');
            return;
        }
        if (malote.lotes.length > 0 && !confirm('Excluir malote IIPR #' + maloteId + '? Os lotes voltarao para disponiveis.')) return;
        for (var i = 0; i < mvMalotesIIPR.length; i++) {
            if (mvMalotesIIPR[i].id === maloteId) {
                mvMalotesIIPR.splice(i, 1);
                break;
            }
        }
        renderizarMV();
    }

    function mvCriarMaloteCorreios() {
        var m = { id: mvNextCorreiosId++, lacre: '', etiqueta: '', fechado: false, iiprIds: [] };
        mvMalotesCorreios.push(m);
        renderizarMV();
        falarTexto('malote correios criado');
    }

    function mvMoverIiprParaCorreios(correiosId) {
        var mc = mvGetCorreiosById(correiosId);
        if (!mc || mc.fechado) return;
        var movidos = 0;
        for (var idStr in mvIiprSelecionados) {
            if (!mvIiprSelecionados.hasOwnProperty(idStr)) continue;
            var iiprId = parseInt(idStr, 10);
            var mi = mvGetIiprById(iiprId);
            if (mi && mi.fechado && !mi.correiosId) {
                mi.correiosId = correiosId;
                mc.iiprIds.push(iiprId);
                movidos++;
            }
        }
        if (movidos === 0) { alert('Selecione malotes IIPR fechados (e ainda nao atribuidos) primeiro.'); return; }
        mvIiprSelecionados = {};
        renderizarMV();
        falarTexto(movidos + ' malotes i i p r adicionados');
    }

    function mvRemoverIiprDeCorreios(correiosId, iiprId) {
        var mc = mvGetCorreiosById(correiosId);
        if (!mc || mc.fechado) return;
        var mi = mvGetIiprById(iiprId);
        if (mi) mi.correiosId = null;
        for (var i = 0; i < mc.iiprIds.length; i++) {
            if (mc.iiprIds[i] === iiprId) {
                mc.iiprIds.splice(i, 1);
                break;
            }
        }
        renderizarMV();
    }

    function mvFecharMaloteCorreios(correiosId) {
        var mc = mvGetCorreiosById(correiosId);
        if (!mc) return;
        if (mc.iiprIds.length === 0) { alert('Adicione malotes IIPR antes de fechar.'); return; }
        var inputLacre = document.getElementById('mv-lacre-correios-' + correiosId);
        var inputEtq = document.getElementById('mv-etiqueta-correios-' + correiosId);
        var lacre = inputLacre ? inputLacre.value.trim() : '';
        var etiqueta = inputEtq ? inputEtq.value.trim() : '';
        if (lacre === '') { alert('Informe o lacre Correios.'); if (inputLacre) inputLacre.focus(); return; }
        if (etiqueta === '') { alert('Informe a etiqueta Correios (35 digitos numericos).'); if (inputEtq) inputEtq.focus(); return; }
        if (!/^\d{35}$/.test(etiqueta)) { alert('Etiqueta deve conter exatamente 35 digitos numericos.'); if (inputEtq) inputEtq.focus(); return; }
        if (etiqueta !== '') {
            var etqUpper = etiqueta.toUpperCase();
            for (var ec = 0; ec < mvMalotesCorreios.length; ec++) {
                var outroMC = mvMalotesCorreios[ec];
                if (outroMC.id !== correiosId && outroMC.etiqueta && outroMC.etiqueta.toUpperCase() === etqUpper) {
                    alert('Etiqueta "' + etiqueta + '" ja esta em uso no Malote Correios #' + outroMC.id + '. Informe uma etiqueta diferente.');
                    if (inputEtq) inputEtq.focus();
                    return;
                }
            }
        }
        var postosNoMalote = {};
        for (var pi = 0; pi < mc.iiprIds.length; pi++) {
            var iiprM = mvGetIiprById(mc.iiprIds[pi]);
            if (iiprM) {
                for (var pli = 0; pli < iiprM.lotes.length; pli++) {
                    postosNoMalote[iiprM.lotes[pli].nposto] = true;
                }
            }
        }
        var postosArr = [];
        for (var pk in postosNoMalote) { if (postosNoMalote.hasOwnProperty(pk)) postosArr.push(pk); }
        if (postosArr.length > 1) {
            if (!confirm('Atencao: este malote Correios contem lotes de postos diferentes (' + postosArr.join(', ') + '). Deseja continuar?')) return;
        }
        mc.lacre = lacre;
        mc.etiqueta = etiqueta;
        mc.fechado = true;
        renderizarMV();
        falarTexto('malote correios fechado');
    }

    function mvReabrirMaloteCorreios(correiosId) {
        var mc = mvGetCorreiosById(correiosId);
        if (!mc) return;
        mc.fechado = false;
        renderizarMV();
    }

    function mvExcluirMaloteCorreios(correiosId) {
        var mc = mvGetCorreiosById(correiosId);
        if (!mc) return;
        if (mc.iiprIds.length > 0 && !confirm('Excluir malote Correios #' + correiosId + '? Os malotes IIPR voltarao para disponiveis.')) return;
        for (var i = 0; i < mc.iiprIds.length; i++) {
            var mi = mvGetIiprById(mc.iiprIds[i]);
            if (mi) mi.correiosId = null;
        }
        for (var j = 0; j < mvMalotesCorreios.length; j++) {
            if (mvMalotesCorreios[j].id === correiosId) {
                mvMalotesCorreios.splice(j, 1);
                break;
            }
        }
        renderizarMV();
    }

    function renderizarMV() {
        var todosLotes = lotesConferidos.slice();
        todosLotes.sort(function(a, b) {
            var ca = mvCategoriaOrdem(mvLabelCategoria(a.regional_real));
            var cb = mvCategoriaOrdem(mvLabelCategoria(b.regional_real));
            if (ca !== cb) return ca - cb;
            if (a.nposto < b.nposto) return -1;
            if (a.nposto > b.nposto) return 1;
            if (a.nlote < b.nlote) return -1;
            if (a.nlote > b.nlote) return 1;
            return 0;
        });
        var disponiveis = mvGetDisponiveis();
        var iiprFechados = mvGetIiprFechados();
        var countDisp = document.getElementById('mvCountDisponiveis');
        var countIIPR = document.getElementById('mvCountIIPR');
        var countCorreios = document.getElementById('mvCountCorreios');
        var countEsc = document.getElementById('mvCountEscaneados');
        if (countDisp) countDisp.textContent = String(todosLotes.length);
        if (countIIPR) countIIPR.textContent = String(mvMalotesIIPR.length);
        if (countCorreios) countCorreios.textContent = String(mvMalotesCorreios.length);
        var escCount = 0;
        for (var ek in mvLotesEscaneados) { if (mvLotesEscaneados.hasOwnProperty(ek)) escCount++; }
        if (countEsc) countEsc.textContent = String(escCount);

        var areaDisp = document.getElementById('mvLotesDisponiveis');
        if (areaDisp) {
            areaDisp.innerHTML = '';
            if (todosLotes.length === 0) {
                areaDisp.innerHTML = '<span class="mv-vazio">Nenhum lote conferido ainda. Escaneie pacotes para iniciar.</span>';
            } else {
                var categorias = {};
                var catOrdem = [];
                for (var i = 0; i < todosLotes.length; i++) {
                    var lt = todosLotes[i];
                    var cat = mvLabelCategoria(lt.regional_real);
                    if (!categorias[cat]) {
                        categorias[cat] = {};
                        catOrdem.push(cat);
                    }
                    if (!categorias[cat][lt.nposto]) {
                        categorias[cat][lt.nposto] = [];
                    }
                    categorias[cat][lt.nposto].push(lt);
                }
                catOrdem.sort(function(a, b) { return mvCategoriaOrdem(a) - mvCategoriaOrdem(b); });

                for (var ci = 0; ci < catOrdem.length; ci++) {
                    var catName = catOrdem[ci];
                    var postos = categorias[catName];
                    var grupoDiv = document.createElement('div');
                    grupoDiv.className = 'mv-grupo-categoria';

                    var headerDiv = document.createElement('div');
                    headerDiv.className = 'mv-grupo-categoria-header ' + mvCategoriaCss(catName);
                    headerDiv.textContent = catName;
                    grupoDiv.appendChild(headerDiv);

                    var postosArr = [];
                    for (var pk in postos) { if (postos.hasOwnProperty(pk)) postosArr.push(pk); }
                    postosArr.sort();

                    for (var pi = 0; pi < postosArr.length; pi++) {
                        var postoKey = postosArr[pi];
                        var lotesP = postos[postoKey];
                        var linhaDiv = document.createElement('div');
                        linhaDiv.className = 'mv-linha-posto';

                        var nomeSpan = document.createElement('span');
                        nomeSpan.className = 'mv-linha-posto-nome';
                        nomeSpan.textContent = 'Posto ' + postoKey + ' (' + lotesP.length + ')';
                        linhaDiv.appendChild(nomeSpan);

                        var lotesDiv = document.createElement('div');
                        lotesDiv.className = 'mv-linha-posto-lotes';
                        for (var li = 0; li < lotesP.length; li++) {
                            var lt2 = lotesP[li];
                            var key = lt2.nlote + '-' + lt2.nposto;
                            var isDisp = mvLoteDisponivel(lt2);
                            var isEsc = mvLotesEscaneados[key] ? true : false;
                            var chip = document.createElement('span');
                            var cls = 'mv-chip-lote';
                            if (!isDisp) cls += ' em-iipr';
                            else if (isEsc) cls += ' escaneado';
                            chip.className = cls;
                            chip.innerHTML = lt2.nlote + ' <span class="mv-chip-qtd">(' + (lt2.qtd || '?') + ')</span>';
                            lotesDiv.appendChild(chip);
                        }
                        linhaDiv.appendChild(lotesDiv);
                        grupoDiv.appendChild(linhaDiv);
                    }
                    areaDisp.appendChild(grupoDiv);
                }
            }
        }

        var btnFechar = document.getElementById('btnFecharLotesIIPR');
        if (btnFechar) btnFechar.disabled = (escCount === 0);

        var areaIIPR = document.getElementById('mvMalotesIIPR');
        if (areaIIPR) {
            areaIIPR.innerHTML = '';
            if (mvMalotesIIPR.length === 0) {
                areaIIPR.innerHTML = '<span class="mv-vazio">Nenhum malote IIPR criado.</span>';
            } else {
                for (var mi = 0; mi < mvMalotesIIPR.length; mi++) {
                    var m = mvMalotesIIPR[mi];
                    var card = document.createElement('div');
                    card.className = 'mv-card-iipr' + (m.fechado ? ' fechado' : '');

                    var tituloIipr = 'Malote IIPR ' + (m.numPosto || m.id) + ' - Posto ' + (m.posto || '?') + ' (' + m.lotes.length + ' lotes)';
                    var header = '<div class="mv-card-iipr-header"><span class="mv-card-iipr-titulo">' + tituloIipr + '</span>';
                    if (!m.fechado) {
                        header += '<button type="button" class="mv-btn mv-btn-red mv-btn-sm" onclick="mvExcluirMaloteIIPR(' + m.id + ')">X</button>';
                    }
                    header += '</div>';

                    var lotesHtml = '<div class="mv-card-iipr-lotes">';
                    for (var li = 0; li < m.lotes.length; li++) {
                        var lt2 = m.lotes[li];
                        lotesHtml += '<span class="mv-chip-dentro">' + lt2.nlote + ' <span style="font-size:9px;color:#888;">P:' + lt2.nposto + '</span></span>';
                    }
                    lotesHtml += '</div>';

                    var campoHtml = '<div class="mv-card-iipr-campo"><label>Lacre IIPR:</label>';
                    campoHtml += '<strong style="color:#2e7d32;">' + m.lacre + '</strong>';
                    campoHtml += '</div>';

                    var acoesHtml = '<div class="mv-card-iipr-acoes">';
                    acoesHtml += '<button type="button" class="mv-btn mv-btn-sm" style="background:#78909c;color:#fff;" onclick="mvReabrirMaloteIIPR(' + m.id + ')">Reabrir</button>';
                    acoesHtml += '</div>';

                    card.innerHTML = header + lotesHtml + campoHtml + acoesHtml;
                    areaIIPR.appendChild(card);
                }
            }
        }

        var areaCorreios = document.getElementById('mvMalotesCorreios');
        if (areaCorreios) {
            areaCorreios.innerHTML = '';
            if (mvMalotesCorreios.length === 0) {
                areaCorreios.innerHTML = '<span class="mv-vazio">Nenhum malote Correios criado.</span>';
            } else {
                for (var ci = 0; ci < mvMalotesCorreios.length; ci++) {
                    var mc = mvMalotesCorreios[ci];
                    var ccard = document.createElement('div');
                    ccard.className = 'mv-card-correios' + (mc.fechado ? ' fechado' : '');

                    var cheader = '<div class="mv-card-correios-header"><span class="mv-card-correios-titulo">Malote Correios #' + mc.id + ' (' + mc.iiprIds.length + ' malotes IIPR)</span>';
                    if (!mc.fechado) {
                        cheader += '<button type="button" class="mv-btn mv-btn-red mv-btn-sm" onclick="mvExcluirMaloteCorreios(' + mc.id + ')">X</button>';
                    }
                    cheader += '</div>';

                    var conteudoHtml = '<div class="mv-card-correios-conteudo">';
                    if (mc.iiprIds.length === 0) {
                        conteudoHtml += '<span class="mv-vazio">Vazio - selecione malotes IIPR fechados e clique "Adicionar"</span>';
                    } else {
                        for (var ii = 0; ii < mc.iiprIds.length; ii++) {
                            var iipr = mvGetIiprById(mc.iiprIds[ii]);
                            if (!iipr) continue;
                            conteudoHtml += '<span class="mv-mini-iipr dentro">IIPR ' + (iipr.numPosto || iipr.id) + ' P:' + (iipr.posto || '?') + ' (' + iipr.lacre + ', ' + iipr.lotes.length + ' lotes)';
                            if (!mc.fechado) {
                                conteudoHtml += ' <span style="cursor:pointer;color:#c62828;font-weight:700;margin-left:3px;" onclick="mvRemoverIiprDeCorreios(' + mc.id + ',' + iipr.id + ')">&times;</span>';
                            }
                            conteudoHtml += '</span>';
                        }
                    }
                    conteudoHtml += '</div>';

                    var ccampo1 = '<div class="mv-card-correios-campo"><label>Lacre Correios:</label>';
                    if (mc.fechado) {
                        ccampo1 += '<strong style="color:#2e7d32;">' + mc.lacre + '</strong>';
                    } else {
                        ccampo1 += '<input type="text" id="mv-lacre-correios-' + mc.id + '" placeholder="Lacre Correios" value="' + mc.lacre + '" maxlength="20">';
                    }
                    ccampo1 += '</div>';

                    var ccampo2 = '<div class="mv-card-correios-campo"><label>Etiqueta (35 digitos):</label>';
                    if (mc.fechado) {
                        ccampo2 += '<strong style="color:#2e7d32; font-size:11px; word-break:break-all;">' + (mc.etiqueta || '(vazio)') + '</strong>';
                    } else {
                        ccampo2 += '<input type="text" id="mv-etiqueta-correios-' + mc.id + '" placeholder="Escaneie a etiqueta (35 digitos)" value="' + mc.etiqueta + '" maxlength="35" style="font-size:11px;">';
                    }
                    ccampo2 += '</div>';

                    var cacoesHtml = '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
                    if (!mc.fechado) {
                        cacoesHtml += '<button type="button" class="mv-btn mv-btn-indigo mv-btn-sm" onclick="mvMoverIiprParaCorreios(' + mc.id + ')">Adicionar IIPR selecionados</button>';
                        cacoesHtml += '<button type="button" class="mv-btn mv-btn-green mv-btn-sm" onclick="mvFecharMaloteCorreios(' + mc.id + ')">Fechar malote</button>';
                    } else {
                        cacoesHtml += '<button type="button" class="mv-btn mv-btn-sm" style="background:#78909c;color:#fff;" onclick="mvReabrirMaloteCorreios(' + mc.id + ')">Reabrir</button>';
                    }
                    cacoesHtml += '</div>';

                    ccard.innerHTML = cheader + conteudoHtml + ccampo1 + ccampo2 + cacoesHtml;
                    areaCorreios.appendChild(ccard);
                }
            }

            var existSel = document.querySelector('.mv-iipr-sel-area');
            if (existSel) existSel.parentNode.removeChild(existSel);

            if (iiprFechados.length > 0) {
                var selArea = document.createElement('div');
                selArea.className = 'mv-iipr-sel-area';
                selArea.style.cssText = 'margin-top:10px;padding:8px;background:#fff3e0;border:1px solid #ffb300;border-radius:8px;';
                selArea.innerHTML = '<span style="font-size:12px;font-weight:600;color:#e65100;">Malotes IIPR fechados disponiveis:</span><br>';
                for (var fi = 0; fi < iiprFechados.length; fi++) {
                    var fm = iiprFechados[fi];
                    var miniChip = document.createElement('span');
                    miniChip.className = 'mv-mini-iipr' + (mvIiprSelecionados[fm.id] ? ' selecionado' : '');
                    miniChip.textContent = 'IIPR ' + (fm.numPosto || fm.id) + ' P:' + (fm.posto || '?') + ' (' + fm.lacre + ', ' + fm.lotes.length + ' lotes)';
                    miniChip.onclick = (function(fid) { return function() { mvToggleIiprSel(fid); }; })(fm.id);
                    miniChip.style.margin = '4px 4px 0 0';
                    selArea.appendChild(miniChip);
                }
                areaCorreios.parentNode.insertBefore(selArea, areaCorreios.nextSibling);
            }
        }
    }

    function atualizarContadoresConferidos() {
        var spans = document.querySelectorAll('.span-conferidos-count');
        for (var s = 0; s < spans.length; s++) {
            var h3 = spans[s].parentNode;
            var next = h3.nextElementSibling;
            while (next && next.tagName !== 'TABLE') {
                next = next.nextElementSibling;
            }
            if (!next) continue;
            var total = next.querySelectorAll('tbody tr.linha-conferencia').length;
            var conferidos = next.querySelectorAll('tbody tr.confirmado').length;
            spans[s].textContent = '(' + total + ' pacotes / ' + conferidos + ' conferidos)';
        }
    }

    function togglePainelMaloteamento() {
        var conteudo = document.getElementById('maloteamentoConteudo');
        var icon = document.getElementById('toggleMaloteIcon');
        if (!conteudo) return;
        if (conteudo.style.display === 'none') {
            conteudo.style.display = 'block';
            if (icon) icon.innerHTML = '&#9660;';
        } else {
            conteudo.style.display = 'none';
            if (icon) icon.innerHTML = '&#9654;';
        }
    }
    window.togglePainelMaloteamento = togglePainelMaloteamento;
    window.mvExcluirMaloteIIPR = mvExcluirMaloteIIPR;
    window.mvExcluirMaloteCorreios = mvExcluirMaloteCorreios;
    window.mvReabrirMaloteIIPR = mvReabrirMaloteIIPR;
    window.mvFecharMaloteCorreios = mvFecharMaloteCorreios;
    window.mvReabrirMaloteCorreios = mvReabrirMaloteCorreios;
    window.mvMoverIiprParaCorreios = mvMoverIiprParaCorreios;
    window.mvRemoverIiprDeCorreios = mvRemoverIiprDeCorreios;
    window.mvToggleIiprSel = mvToggleIiprSel;
    window.mvFecharLotesComoIIPR = mvFecharLotesComoIIPR;
    window.mvScanLote = mvScanLote;

    function coletarDadosMaloteamento() {
        var linhas = [];
        for (var ci = 0; ci < mvMalotesCorreios.length; ci++) {
            var mc = mvMalotesCorreios[ci];
            if (!mc.fechado) continue;
            for (var ii = 0; ii < mc.iiprIds.length; ii++) {
                var mi = mvGetIiprById(mc.iiprIds[ii]);
                if (!mi) continue;
                for (var li = 0; li < mi.lotes.length; li++) {
                    var lt = mi.lotes[li];
                    linhas.push({
                        lote: lt.nlote,
                        posto: lt.nposto,
                        regional: lt.regional,
                        regional_real: lt.regional_real || '',
                        qtd: lt.qtd || '',
                        lacre_iipr: mi.lacre,
                        lacre_correios: mc.lacre,
                        etiqueta_correios: mc.etiqueta,
                        lotes: [lt.nlote]
                    });
                }
            }
        }
        for (var i2 = 0; i2 < mvMalotesIIPR.length; i2++) {
            var mi2 = mvMalotesIIPR[i2];
            if (!mi2.fechado || mi2.correiosId) continue;
            for (var j2 = 0; j2 < mi2.lotes.length; j2++) {
                var lt2 = mi2.lotes[j2];
                linhas.push({
                    lote: lt2.nlote,
                    posto: lt2.nposto,
                    regional: lt2.regional,
                    regional_real: lt2.regional_real || '',
                    qtd: lt2.qtd || '',
                    lacre_iipr: mi2.lacre,
                    lacre_correios: '',
                    etiqueta_correios: '',
                    lotes: [lt2.nlote]
                });
            }
        }
        return linhas;
    }

    function salvarMaloteamentoAjax(sobrescrever) {
        var linhas = coletarDadosMaloteamento();
        if (linhas.length === 0) { alert('Feche pelo menos um malote Correios ou IIPR para salvar.'); return; }

        var etqUsadas = {};
        var etqDuplicadas = [];
        for (var ei = 0; ei < linhas.length; ei++) {
            var etqVal = linhas[ei].etiqueta_correios;
            if (etqVal === '') continue;
            var etqPosto = linhas[ei].posto;
            if (etqUsadas[etqVal] && etqUsadas[etqVal] !== etqPosto) {
                etqDuplicadas.push(etqVal + ' (postos ' + etqUsadas[etqVal] + ' e ' + etqPosto + ')');
            }
            if (!etqUsadas[etqVal]) {
                etqUsadas[etqVal] = etqPosto;
            }
        }
        if (etqDuplicadas.length > 0) {
            alert('Etiquetas duplicadas em postos diferentes:\n' + etqDuplicadas.join('\n') + '\n\nCorrija antes de salvar.');
            return;
        }

        if (!confirm('Salvar ' + linhas.length + ' lote(s) com lacres?')) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        var formData = new FormData();
        formData.append('ajax_salvar_oficio_conferencia', '1');
        formData.append('linhas', JSON.stringify(linhas));
        formData.append('usuario', usuarioAtual);
        formData.append('sobrescrever', sobrescrever ? '1' : '0');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        alert('Salvo com sucesso! (' + data.atualizados + ' lotes atualizados)');
                    } else {
                        alert('Erro: ' + (data.erro || 'desconhecido'));
                    }
                } catch (e) { alert('Erro ao salvar.'); }
            }
        };
        xhr.send(formData);
    }

    function imprimirMaloteamento() {
        var linhasOficio = [];
        for (var ci = 0; ci < mvMalotesCorreios.length; ci++) {
            var mc = mvMalotesCorreios[ci];
            if (!mc.fechado) continue;
            var lacresIiprList = [];
            var postoDoMC = '';
            var regionalDoMC = '';
            var regionalRealDoMC = '';
            for (var ii = 0; ii < mc.iiprIds.length; ii++) {
                var mi = mvGetIiprById(mc.iiprIds[ii]);
                if (!mi) continue;
                lacresIiprList.push(mi.lacre);
                if (mi.lotes.length > 0 && !postoDoMC) {
                    postoDoMC = mi.lotes[0].nposto;
                    regionalDoMC = mi.lotes[0].regional || '';
                    regionalRealDoMC = mi.lotes[0].regional_real || '';
                }
            }
            linhasOficio.push({
                posto: postoDoMC,
                regional: regionalDoMC,
                regional_real: regionalRealDoMC,
                lacre_iipr: lacresIiprList.join(', '),
                lacre_correios: mc.lacre,
                etiqueta_correios: mc.etiqueta
            });
        }
        for (var i2 = 0; i2 < mvMalotesIIPR.length; i2++) {
            var mi2 = mvMalotesIIPR[i2];
            if (!mi2.fechado || mi2.correiosId) continue;
            var postoSolto = '';
            var regSolto = '';
            var rrSolto = '';
            if (mi2.lotes.length > 0) {
                postoSolto = mi2.lotes[0].nposto;
                regSolto = mi2.lotes[0].regional || '';
                rrSolto = mi2.lotes[0].regional_real || '';
            }
            linhasOficio.push({
                posto: postoSolto,
                regional: regSolto,
                regional_real: rrSolto,
                lacre_iipr: mi2.lacre,
                lacre_correios: '',
                etiqueta_correios: ''
            });
        }
        if (linhasOficio.length === 0) { alert('Feche malotes antes de imprimir.'); return; }

        var painel = document.getElementById('painelImpressaoOficio');
        var corpo = document.getElementById('corpoOficioImpressao');
        var dataDiv = document.getElementById('oficioImpressaoData');
        if (!painel || !corpo) return;

        if (dataDiv) {
            var agora = new Date();
            dataDiv.textContent = 'Data: ' + ('0'+agora.getDate()).slice(-2) + '-' + ('0'+(agora.getMonth()+1)).slice(-2) + '-' + agora.getFullYear();
        }

        var catGroups = {};
        var catOrder = [];
        for (var k = 0; k < linhasOficio.length; k++) {
            var row = linhasOficio[k];
            var cat = mvLabelCategoria(row.regional_real);
            if (!catGroups[cat]) { catGroups[cat] = []; catOrder.push(cat); }
            catGroups[cat].push(row);
        }
        catOrder.sort(function(a, b) { return mvCategoriaOrdem(a) - mvCategoriaOrdem(b); });

        corpo.innerHTML = '';
        var tdStyle = 'border:1px solid #000;padding:4px 6px;font-size:11px;';
        var thStyle = 'border:1px solid #000;padding:6px 8px;font-size:12px;font-weight:700;background:#f5f5f5;text-align:left;';

        for (var gi = 0; gi < catOrder.length; gi++) {
            var catName = catOrder[gi];
            var rows = catGroups[catName];
            rows.sort(function(a, b) { return a.posto < b.posto ? -1 : (a.posto > b.posto ? 1 : 0); });

            var trCat = document.createElement('tr');
            var tdCat = document.createElement('td');
            tdCat.colSpan = 4;
            tdCat.style.cssText = thStyle;
            tdCat.textContent = catName;
            trCat.appendChild(tdCat);
            corpo.appendChild(trCat);

            for (var ri = 0; ri < rows.length; ri++) {
                var tr = document.createElement('tr');
                var tdPosto = document.createElement('td');
                tdPosto.style.cssText = tdStyle;
                tdPosto.textContent = 'Posto ' + rows[ri].posto;
                tr.appendChild(tdPosto);

                var tdLacre = document.createElement('td');
                tdLacre.style.cssText = tdStyle;
                tdLacre.textContent = rows[ri].lacre_iipr || '';
                tr.appendChild(tdLacre);

                var tdLC = document.createElement('td');
                tdLC.style.cssText = tdStyle;
                tdLC.textContent = rows[ri].lacre_correios || '';
                tr.appendChild(tdLC);

                var tdEtq = document.createElement('td');
                tdEtq.style.cssText = tdStyle;
                tdEtq.textContent = rows[ri].etiqueta_correios || '';
                tr.appendChild(tdEtq);

                corpo.appendChild(tr);
            }
        }

        painel.style.display = 'block';
        setTimeout(function() { window.print(); }, 300);
    }

    function carregarMalotesExistentesVisual(dados) {
        if (!dados || !dados.length) return;
        var iiprMap = {};
        var correiosMap = {};
        for (var i = 0; i < dados.length; i++) {
            var d = dados[i];
            var loteObj = null;
            for (var j = 0; j < lotesConferidos.length; j++) {
                if (lotesConferidos[j].nlote === d.nlote && lotesConferidos[j].nposto === d.nposto) {
                    loteObj = lotesConferidos[j];
                    break;
                }
            }
            if (!loteObj) {
                loteObj = { nlote: d.nlote, nposto: d.nposto, regional: d.regional || '', qtd: d.qtd || '', regional_real: d.regional_real || '', codbar: '' };
                lotesConferidos.push(loteObj);
            }
            if (d.lacre_iipr && d.lacre_iipr !== '') {
                if (!iiprMap[d.lacre_iipr]) {
                    iiprMap[d.lacre_iipr] = { lacre: d.lacre_iipr, lotes: [], lacre_correios: d.lacre_correios || '', etiqueta: d.etiqueta_correios || '' };
                }
                iiprMap[d.lacre_iipr].lotes.push(loteObj);
                if (d.lacre_correios) iiprMap[d.lacre_iipr].lacre_correios = d.lacre_correios;
                if (d.etiqueta_correios) iiprMap[d.lacre_iipr].etiqueta = d.etiqueta_correios;
            }
        }
        for (var lacreI in iiprMap) {
            if (!iiprMap.hasOwnProperty(lacreI)) continue;
            var info = iiprMap[lacreI];
            var iiprPosto = info.lotes.length > 0 ? info.lotes[0].nposto : '';
            var contPosto = 0;
            for (var cp = 0; cp < mvMalotesIIPR.length; cp++) { if (mvMalotesIIPR[cp].posto === iiprPosto) contPosto++; }
            var newIipr = { id: mvNextIiprId++, lacre: info.lacre, fechado: true, lotes: info.lotes, correiosId: null, posto: iiprPosto, numPosto: contPosto + 1 };
            mvMalotesIIPR.push(newIipr);
            if (info.lacre_correios) {
                var ckey = info.lacre_correios + '|' + info.etiqueta;
                if (!correiosMap[ckey]) {
                    correiosMap[ckey] = { lacre: info.lacre_correios, etiqueta: info.etiqueta, iiprIds: [] };
                }
                correiosMap[ckey].iiprIds.push(newIipr.id);
            }
        }
        for (var ck in correiosMap) {
            if (!correiosMap.hasOwnProperty(ck)) continue;
            var cinfo = correiosMap[ck];
            var newMC = { id: mvNextCorreiosId++, lacre: cinfo.lacre, etiqueta: cinfo.etiqueta, fechado: true, iiprIds: cinfo.iiprIds };
            mvMalotesCorreios.push(newMC);
            for (var ki = 0; ki < cinfo.iiprIds.length; ki++) {
                var mi3 = mvGetIiprById(cinfo.iiprIds[ki]);
                if (mi3) mi3.correiosId = newMC.id;
            }
        }
        renderizarMV();
    }

    var filaSons = [];
    var tocando = false;

    function tocarProximoSom() {
        if (filaSons.length === 0) {
            tocando = false;
            return;
        }
        tocando = true;
        var som = filaSons.shift();
        try {
            som.currentTime = 0;
            som.onended = function() {
                tocando = false;
                tocarProximoSom();
            };
            som.onerror = function() {
                tocando = false;
                tocarProximoSom();
            };
            var playPromise = som.play();
            if (playPromise && playPromise.then) {
                playPromise.catch(function() {
                    tocando = false;
                    tocarProximoSom();
                });
            }
        } catch (e) {
            tocando = false;
            tocarProximoSom();
        }
    }

    function enfileirarSom(som) {
        if (!som) return;
        filaSons.push(som);
        if (!tocando) {
            tocarProximoSom();
        }
    }

    function falarTexto(texto) {
        if (!window.speechSynthesis || !texto) return;
        try {
            var ut = new SpeechSynthesisUtterance(texto);
            ut.lang = 'pt-BR';
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(ut);
        } catch (e) {}
    }

    var listaSons = [];
    if (beep) listaSons.push(beep);
    if (concluido) listaSons.push(concluido);
    if (pacoteJaConferido) listaSons.push(pacoteJaConferido);
    if (pacoteOutraRegional) listaSons.push(pacoteOutraRegional);
    if (postoPoupaTempo) listaSons.push(postoPoupaTempo);
    if (pertenceCorreios) listaSons.push(pertenceCorreios);

    function desbloquearAudio() {
        if (audioDesbloqueado) return;
        audioDesbloqueado = true;
        for (var i = 0; i < listaSons.length; i++) {
            try {
                listaSons[i].volume = 0;
                var p = listaSons[i].play();
                if (p && p.then) {
                    p.then(function() {
                        for (var j = 0; j < listaSons.length; j++) {
                            listaSons[j].pause();
                            listaSons[j].currentTime = 0;
                            listaSons[j].volume = 1;
                        }
                    }).catch(function() {
                        for (var k = 0; k < listaSons.length; k++) {
                            listaSons[k].volume = 1;
                        }
                    });
                }
            } catch (e) {
                for (var k2 = 0; k2 < listaSons.length; k2++) {
                    listaSons[k2].volume = 1;
                }
            }
        }
    }

    input.addEventListener('focus', desbloquearAudio);
    input.addEventListener('click', desbloquearAudio);
    
    var regionalAtual = null;
    var tipoAtual = null;
    var primeiroConferido = false;

    function obterTipoInicioSelecionado() {
        var radios = document.querySelectorAll('input[name="tipo_inicio"]');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return 'correios';
    }

    function carregarMalotesExistentes() {
        if (dataIniSql === '' || dataFimSql === '') return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        var formData = new FormData();
        formData.append('ajax_carregar_malotes', '1');
        formData.append('data_inicio', dataIniSql);
        formData.append('data_fim', dataFimSql);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.lotes) {
                        carregarMalotesExistentesVisual(data.lotes);
                    }
                } catch (e) {}
            }
        };
        xhr.send(formData);
    }

    function aplicarFiltroTipo(tipo) {
        var secoes = document.querySelectorAll('.secao-tipo');
        for (var i = 0; i < secoes.length; i++) {
            var secaoTipo = secoes[i].getAttribute('data-secao-tipo');
            if (tipo === 'todos') {
                secoes[i].style.display = '';
            } else if (secaoTipo === tipo) {
                secoes[i].style.display = '';
            } else {
                secoes[i].style.display = 'none';
            }
        }
    }

    function selecionarTipoConferencia(tipo) {
        var radios = document.querySelectorAll('input[name="tipo_inicio"]');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].value === tipo) {
                radios[i].checked = true;
                break;
            }
        }
        tipoEscolhido = true;
        aplicarFiltroTipo(tipo);
        if (overlayTipo) overlayTipo.style.display = 'none';
        if (painelMaloteamento) painelMaloteamento.style.display = 'block';
        carregarMalotesExistentes();
        if (input) input.focus();
    }

    function abrirModalPacote(codigo, idx) {
        if (!modalPacote) return;
        var cod = codigo || '';
        if (pacoteIdx) pacoteIdx.value = (typeof idx === 'number') ? String(idx) : '';
        if (pacoteCodbar) pacoteCodbar.value = cod;
        if (cod.length === 19 && (typeof idx !== 'number')) {
            if (pacoteLote) pacoteLote.value = cod.substr(0, 8);
            if (pacoteRegional) pacoteRegional.value = cod.substr(8, 3);
            if (pacotePosto) pacotePosto.value = cod.substr(11, 3);
            if (pacoteQtd) pacoteQtd.value = parseInt(cod.substr(14, 5), 10) || '';
        }
        if (pacoteDataexp && !pacoteDataexp.value) {
            var now = new Date();
            var mm = ('0' + (now.getMonth() + 1)).slice(-2);
            var dd = ('0' + now.getDate()).slice(-2);
            pacoteDataexp.value = now.getFullYear() + '-' + mm + '-' + dd;
        }
        modalPacote.style.display = 'flex';
        if (pacoteLote) pacoteLote.focus();
    }

    function fecharModalPacote() {
        if (modalPacote) modalPacote.style.display = 'none';
        if (pacoteIdx) pacoteIdx.value = '';
        if (pacoteResponsavel) pacoteResponsavel.value = '';
    }

    var turnoNomes = ['Madrugada', 'Manha', 'Tarde', 'Noite'];

    function sortearPendentes() {
        pacotesPendentes.sort(function(a, b) {
            var ra = (a.responsavel || '').trim().toLowerCase();
            var rb = (b.responsavel || '').trim().toLowerCase();
            if (ra < rb) return -1;
            if (ra > rb) return 1;
            return 0;
        });
    }

    function renderizarPacotesPendentes() {
        if (!listaPacotesNovos) return;
        sortearPendentes();
        listaPacotesNovos.innerHTML = '';
        var tdStyle = 'padding:3px 6px; border:1px solid #ddd; font-size:12px;';
        var ultimoResp = null;
        for (var i = 0; i < pacotesPendentes.length; i++) {
            var p = pacotesPendentes[i];
            var respAtual = (p.responsavel || '').trim();
            if (respAtual !== ultimoResp) {
                ultimoResp = respAtual;
                var count = 0;
                for (var c = i; c < pacotesPendentes.length; c++) {
                    if ((pacotesPendentes[c].responsavel || '').trim() === respAtual) { count++; } else { break; }
                }
                var trHeader = document.createElement('tr');
                trHeader.innerHTML = '<td colspan="8" style="background:#dbeafe; color:#1e40af; font-weight:bold; padding:5px 10px; font-size:12px; border:1px solid #93c5fd; border-left:4px solid #3b82f6;">' +
                    (respAtual ? respAtual : '(sem responsavel)') + ' &mdash; ' + count + ' lote(s)</td>';
                listaPacotesNovos.appendChild(trHeader);
            }
            var turnoVal = (p.turno !== undefined && p.turno !== null) ? p.turno : 1;
            var tr = document.createElement('tr');
            var turnoSelect = '<select data-turno-idx="' + i + '" style="font-size:11px; padding:2px 4px; border:1px solid #ced4da; border-radius:3px;">' +
                '<option value="0"' + (turnoVal === 0 ? ' selected' : '') + '>Madrugada</option>' +
                '<option value="1"' + (turnoVal === 1 ? ' selected' : '') + '>Manha</option>' +
                '<option value="2"' + (turnoVal === 2 ? ' selected' : '') + '>Tarde</option>' +
                '<option value="3"' + (turnoVal === 3 ? ' selected' : '') + '>Noite</option>' +
                '</select>';
            var respInput = '<input type="text" data-resp-idx="' + i + '" value="' + (p.responsavel || '').replace(/"/g, '&quot;') + '" placeholder="Responsavel" style="font-size:11px; padding:2px 4px; border:1px solid #ced4da; border-radius:3px; width:120px;">';
            tr.innerHTML =
                '<td style="' + tdStyle + '">' + p.lote + '</td>' +
                '<td style="' + tdStyle + '">' + p.regional + '</td>' +
                '<td style="' + tdStyle + '">' + p.posto + '</td>' +
                '<td style="' + tdStyle + '">' + p.quantidade + '</td>' +
                '<td style="' + tdStyle + '">' + p.dataexp + '</td>' +
                '<td style="' + tdStyle + '">' + turnoSelect + '</td>' +
                '<td style="' + tdStyle + '">' + respInput + '</td>' +
                '<td style="' + tdStyle + '">' +
                '<button type="button" class="btn-acao btn-cancelar" data-remover="' + i + '">Remover</button>' +
                '</td>';
            listaPacotesNovos.appendChild(tr);
        }
        if (painelPacotesNovos) {
            painelPacotesNovos.style.display = pacotesPendentes.length ? 'block' : 'none';
        }
        var pendDataExp = document.getElementById('pendentesDataExp');
        var pendResp = document.getElementById('pendentesResponsavel');
        if (pendDataExp && !pendDataExp.value) {
            var hoje = new Date();
            var dd2 = ('0' + hoje.getDate()).slice(-2);
            var mm2 = ('0' + (hoje.getMonth() + 1)).slice(-2);
            pendDataExp.value = hoje.getFullYear() + '-' + mm2 + '-' + dd2;
        }
        if (pendResp && !pendResp.value && usuarioAtual) {
            pendResp.value = usuarioAtual;
        }
    }

    function adicionarPacotePendente(obj) {
        if (!obj || !obj.lote || !obj.regional || !obj.posto || !obj.quantidade || !obj.dataexp) {
            return false;
        }
        for (var i = 0; i < pacotesPendentes.length; i++) {
            if (pacotesPendentes[i].codbar === obj.codbar) {
                return false;
            }
        }
        pacotesPendentes.push(obj);
        renderizarPacotesPendentes();
        return true;
    }

    if (btnAdicionarPacote) {
        btnAdicionarPacote.addEventListener('click', function() {
            var obj = {
                codbar: pacoteCodbar ? pacoteCodbar.value.trim() : '',
                lote: pacoteLote ? pacoteLote.value.trim() : '',
                regional: pacoteRegional ? pacoteRegional.value.trim() : '',
                posto: pacotePosto ? pacotePosto.value.trim() : '',
                quantidade: pacoteQtd ? pacoteQtd.value.trim() : '',
                dataexp: pacoteDataexp ? pacoteDataexp.value.trim() : '',
                responsavel: pacoteResponsavel ? pacoteResponsavel.value.trim() : ''
            };
            if (!obj.lote || !obj.regional || !obj.posto || !obj.quantidade || !obj.dataexp) {
                alert('Preencha todos os campos do pacote.');
                return;
            }
            var idx = pacoteIdx ? parseInt(pacoteIdx.value, 10) : -1;
            if (!isNaN(idx) && idx >= 0 && pacotesPendentes[idx]) {
                pacotesPendentes[idx] = obj;
                renderizarPacotesPendentes();
            } else {
                adicionarPacotePendente(obj);
            }
            fecharModalPacote();
        });
    }

    if (btnCancelarPacote) {
        btnCancelarPacote.addEventListener('click', function() {
            fecharModalPacote();
        });
    }

    if (listaPacotesNovos) {
        listaPacotesNovos.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) return;
            if (target.tagName === 'BUTTON' && target.getAttribute('data-remover') !== null) {
                var idxRem = parseInt(target.getAttribute('data-remover'), 10);
                if (!isNaN(idxRem)) {
                    pacotesPendentes.splice(idxRem, 1);
                    renderizarPacotesPendentes();
                }
            }
        });
        listaPacotesNovos.addEventListener('change', function(e) {
            var target = e.target;
            if (!target) return;
            var idxT = target.getAttribute('data-turno-idx');
            if (idxT !== null && idxT !== undefined) {
                var ti = parseInt(idxT, 10);
                if (!isNaN(ti) && pacotesPendentes[ti]) {
                    pacotesPendentes[ti].turno = parseInt(target.value, 10);
                }
            }
        });
        listaPacotesNovos.addEventListener('input', function(e) {
            var target = e.target;
            if (!target) return;
            var idxR = target.getAttribute('data-resp-idx');
            if (idxR !== null && idxR !== undefined) {
                var ri2 = parseInt(idxR, 10);
                if (!isNaN(ri2) && pacotesPendentes[ri2]) {
                    pacotesPendentes[ri2].responsavel = target.value;
                }
            }
        });
        listaPacotesNovos.addEventListener('blur', function(e) {
            var target = e.target;
            if (!target) return;
            var idxR = target.getAttribute('data-resp-idx');
            if (idxR !== null && idxR !== undefined) {
                var ri2 = parseInt(idxR, 10);
                if (!isNaN(ri2) && pacotesPendentes[ri2]) {
                    pacotesPendentes[ri2].responsavel = target.value;
                    renderizarPacotesPendentes();
                }
            }
        }, true);
    }

    if (btnCancelarPacotes) {
        btnCancelarPacotes.addEventListener('click', function() {
            pacotesPendentes = [];
            renderizarPacotesPendentes();
        });
    }

    if (btnSalvarPacotes) {
        btnSalvarPacotes.addEventListener('click', function() {
            var pendDataExp = document.getElementById('pendentesDataExp');
            var pendResp = document.getElementById('pendentesResponsavel');
            var dataExpGlobal = pendDataExp ? pendDataExp.value.trim() : '';
            var respGlobal = pendResp ? pendResp.value.trim() : '';

            if (!respGlobal && !usuarioAtual) {
                alert('Informe o responsavel pela expedicao.');
                return;
            }
            if (!respGlobal) {
                respGlobal = usuarioAtual;
            }
            if (!dataExpGlobal) {
                alert('Informe a data de expedicao.');
                return;
            }
            if (!pacotesPendentes.length) return;

            var pendentesTurnoEl = document.getElementById('pendentesTurno');
            var turnoGlobal = pendentesTurnoEl ? parseInt(pendentesTurnoEl.value, 10) : 1;
            var pacotesParaSalvar = [];
            for (var ps = 0; ps < pacotesPendentes.length; ps++) {
                var pItem = pacotesPendentes[ps];
                var turnoItem = (pItem.turno !== undefined && pItem.turno !== null) ? pItem.turno : turnoGlobal;
                var respItem = (pItem.responsavel && pItem.responsavel.trim()) ? pItem.responsavel.trim() : respGlobal;
                pacotesParaSalvar.push({
                    codbar: pItem.codbar,
                    lote: pItem.lote,
                    regional: pItem.regional,
                    posto: pItem.posto,
                    quantidade: pItem.quantidade,
                    dataexp: dataExpGlobal,
                    turno: turnoItem,
                    responsavel: respItem
                });
            }

            if (!confirm('Gravar ' + pacotesParaSalvar.length + ' lote(s) em ciPostos com data ' + dataExpGlobal + '?')) return;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            var formData = new FormData();
            formData.append('inserir_pacotes_nao_listados', '1');
            formData.append('usuario', respGlobal);
            formData.append('pacotes', JSON.stringify(pacotesParaSalvar));
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data && data.success) {
                            var msgSucesso = 'Lotes gravados com sucesso: ' + data.inseridos + ' lote(s) inseridos.';
                            if (data.erros && data.erros.length > 0) {
                                msgSucesso += '\nAvisos: ' + data.erros.join('; ');
                            }
                            alert(msgSucesso);
                            pacotesPendentes = [];
                            renderizarPacotesPendentes();
                            window.location.reload();
                        } else {
                            alert('Erro ao gravar lotes: ' + (data.erro || 'desconhecido'));
                        }
                    } catch (e) {
                        alert('Erro ao gravar lotes.');
                    }
                }
            };
            xhr.send(formData);
        });
    }
    
    if (usuarioAtual && usuarioAtual.length > 0) {
        if (usuarioInputModal) usuarioInputModal.value = usuarioAtual;
    }
    if (usuarioInputModal) {
        usuarioInputModal.focus();
    }

    function liberarPaginaComUsuario(nome) {
        usuarioAtual = nome;
        try { localStorage.setItem('conferencia_responsavel', nome); } catch (eLS) {}
        if (usuarioBadge) {
            usuarioBadge.textContent = nome;
        }
        if (overlayUsuario) {
            overlayUsuario.style.display = 'none';
        }
        if (conteudoPagina) {
            conteudoPagina.classList.remove('page-locked');
        }
        tipoEscolhido = false;
        if (overlayTipo) {
            overlayTipo.style.display = 'flex';
        }
    }

    if (btnConfirmarUsuario) {
        btnConfirmarUsuario.addEventListener('click', function() {
            var nome = usuarioInputModal ? usuarioInputModal.value.trim() : '';
            if (!nome) {
                alert('Informe o responsavel da conferencia.');
                if (usuarioInputModal) usuarioInputModal.focus();
                return;
            }
            liberarPaginaComUsuario(nome);
        });
    }

    if (btnCancelarUsuario) {
        btnCancelarUsuario.addEventListener('click', function() {
            window.location.href = 'inicio.php';
        });
    }

    var radiosInicio = document.querySelectorAll('input[name="tipo_inicio"]');
    for (var ri = 0; ri < radiosInicio.length; ri++) {
        radiosInicio[ri].addEventListener('change', function() {
            var tipoSel = obterTipoInicioSelecionado();
            aplicarFiltroTipo(tipoSel);
        });
    }

    if (overlayTipo) {
        overlayTipo.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) return;
            var tipo = target.getAttribute('data-tipo');
            if (tipo) {
                selecionarTipoConferencia(tipo);
            }
        });
    }

    if (usuarioInputModal) {
        usuarioInputModal.addEventListener('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                if (btnConfirmarUsuario) btnConfirmarUsuario.click();
            }
        });
    }
    
    function salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, usuario) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        var formData = new FormData();
        formData.append('salvar_lote_ajax', '1');
        formData.append('lote', lote);
        formData.append('regional', regional);
        formData.append('posto', posto);
        formData.append('dataexp', dataexp);
        formData.append('qtd', qtd);
        formData.append('codbar', codbar);
        formData.append('usuario', usuario);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (!data.sucesso) {
                        if (typeof console !== 'undefined') console.log('Erro ao salvar:', data.erro);
                    }
                } catch (e) {
                    if (typeof console !== 'undefined') console.log('Erro AJAX:', e);
                }
            }
        };
        xhr.send(formData);
    }
    
    var scanLock = false;
    var scanLockTimer = null;
    var scanDebounce8 = null;

    function resolverBarcode(raw) {
        if (raw.length === 19) return raw;
        for (var w = 0; w + 19 <= raw.length; w++) {
            var cand = raw.substr(w, 19);
            if (document.querySelector('tr[data-codigo="' + cand + '"]')) {
                return cand;
            }
        }
        return raw.substr(raw.length - 19, 19);
    }

    input.addEventListener("input", function() {
        var raw = input.value.replace(/\D/g, '');
        if (scanLock) { input.value = ''; return; }
        input.value = raw;
        if (raw.length >= 19) {
            var captured = raw;
            input.value = '';
            scanLock = true;
            if (scanLockTimer) clearTimeout(scanLockTimer);
            scanLockTimer = setTimeout(function() { scanLock = false; }, 400);
            if (scanDebounce8) { clearTimeout(scanDebounce8); scanDebounce8 = null; }
            processarScan(resolverBarcode(captured));
            return;
        }
        if (raw.length === 8) {
            if (scanDebounce8) clearTimeout(scanDebounce8);
            var capLote = raw;
            scanDebounce8 = setTimeout(function() {
                var curr = input.value.replace(/\D/g, '');
                if (curr === capLote) {
                    input.value = '';
                    processarScan(capLote);
                }
            }, 200);
        }
    });

    function processarScan(valor) {

        if (valor.length >= 14) {
            var postoLido = valor.substr(11, 3);
            if (postosBloqueadosMap[postoLido]) {
                falarTexto('nao enviar este posto');
                if (mensagemLeitura) {
                    mensagemLeitura.innerHTML = '<strong>Posto bloqueado:</strong> ' + postoLido + ' ' + (postosBloqueadosMap[postoLido].nome || '');
                }
                input.value = "";
                return;
            }
        }
        
        var linha = null;
        var loteBusca = valor.substr(0, 8);

        if (valor.length === 19) {
            linha = document.querySelector('tr[data-codigo="' + valor + '"]');
        }

        if (!linha && valor.length === 19) {
            var postoFb = valor.substr(11, 3);
            var trsFb = document.querySelectorAll('tbody tr.linha-conferencia');
            for (var fi = 0; fi < trsFb.length; fi++) {
                var trLoteFb = trsFb[fi].getAttribute('data-lote') || '';
                var trPostoFb = trsFb[fi].getAttribute('data-posto') || '';
                if (trLoteFb === loteBusca && trPostoFb === postoFb) {
                    if (!trsFb[fi].classList.contains('confirmado')) { linha = trsFb[fi]; break; }
                    if (!linha) linha = trsFb[fi];
                }
            }
        }

        if (!linha && valor.length === 8) {
            var postoBusca = '';
            var todasTrs = document.querySelectorAll('tbody tr.linha-conferencia');
            var candidataNaoConf = null;
            for (var fb = 0; fb < todasTrs.length; fb++) {
                var trLote = todasTrs[fb].getAttribute('data-lote') || '';
                if (trLote === loteBusca) {
                    if (!todasTrs[fb].classList.contains('confirmado')) {
                        candidataNaoConf = todasTrs[fb];
                        break;
                    }
                    if (!linha) {
                        linha = todasTrs[fb];
                    }
                }
            }
            if (candidataNaoConf) {
                linha = candidataNaoConf;
            }
        }
        
        if (!linha) {
            var dataPadrao = dataIniSql || '';
            if (dataPadrao === '') {
                var now = new Date();
                var mm = ('0' + (now.getMonth() + 1)).slice(-2);
                var dd = ('0' + now.getDate()).slice(-2);
                dataPadrao = now.getFullYear() + '-' + mm + '-' + dd;
            }

            var postoDetectado = valor.length >= 14 ? valor.substr(11, 3) : '000';
            var regionalDetectada = postoRegionalRealMap[postoDetectado] || (valor.length >= 11 ? valor.substr(8, 3) : '000');
            var pendentesTurnoEl = document.getElementById('pendentesTurno');
            var turnoDefault = pendentesTurnoEl ? parseInt(pendentesTurnoEl.value, 10) : 1;
            var obj = {
                codbar: valor,
                lote: loteBusca,
                regional: regionalDetectada,
                posto: postoDetectado,
                quantidade: valor.length >= 19 ? (parseInt(valor.substr(14, 5), 10) || 1) : 1,
                dataexp: dataPadrao,
                responsavel: '',
                turno: turnoDefault
            };
            adicionarPacotePendente(obj);
            falarTexto('pacote nao carregado');
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Pacote nao carregado:</strong> lote ' + obj.lote + ' posto ' + obj.posto + ' nao esta na tela.';
            }
            return;
        }

        if (!usuarioAtual) {
            alert('Informe o responsavel da conferencia para iniciar.');
            input.value = "";
            if (overlayUsuario) { overlayUsuario.style.display = 'flex'; }
            if (conteudoPagina) { conteudoPagina.classList.add('page-locked'); }
            if (usuarioInputModal) { usuarioInputModal.focus(); }
            return;
        }

        if (!tipoEscolhido) {
            if (overlayTipo) overlayTipo.style.display = 'flex';
            input.value = "";
            return;
        }
        
        var regionalDoPacote = linha.getAttribute("data-regional");
        var isPoupaTempo = linha.getAttribute("data-ispt") === "1";
        var tipoPacote = isPoupaTempo ? 'poupatempo' : 'correios';
        
        if (linha.classList.contains("confirmado")) {
            if (!muteBeep || !muteBeep.checked) {
                enfileirarSom(beep);
            }
            setTimeout(function() {
                falarTexto('conferido');
            }, 400);
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong style="color:#856404;">Pacote ja conferido:</strong> ' + valor;
            }
            input.value = "";
            return;
        }
        
        var somAlerta = null;
        var podeConferir = true;
        
        if (!primeiroConferido) {
            tipoAtual = obterTipoInicioSelecionado();
            if (tipoAtual === 'todos') {
                regionalAtual = regionalDoPacote;
                podeConferir = true;
            } else if (tipoAtual === tipoPacote) {
                regionalAtual = regionalDoPacote;
            } else {
                podeConferir = false;
                if (tipoAtual === 'correios' && tipoPacote === 'poupatempo') {
                    somAlerta = postoPoupaTempo;
                }
                if (tipoAtual === 'poupatempo' && tipoPacote === 'correios') {
                    somAlerta = pertenceCorreios;
                }
            }
            if (podeConferir) {
                primeiroConferido = true;
            }
        }
        else if (tipoAtual === 'correios' && tipoPacote === 'poupatempo') {
            somAlerta = postoPoupaTempo;
            podeConferir = false;
        }
        else if (tipoAtual === 'poupatempo' && tipoPacote === 'correios') {
            somAlerta = pertenceCorreios;
            podeConferir = false;
        }
        else if (regionalDoPacote !== regionalAtual && (tipoPacote === tipoAtual || tipoAtual === 'todos')) {
            enfileirarSom(pacoteOutraRegional);
            var msgTroca = 'Pacote pertence a regional ' + regionalDoPacote + ', mas voce esta conferindo a regional ' + regionalAtual + '.\n\nDeseja mudar para a regional ' + regionalDoPacote + '?';
            if (confirm(msgTroca)) {
                regionalAtual = regionalDoPacote;
                podeConferir = true;
                somAlerta = null;
            } else {
                podeConferir = false;
                input.value = "";
                return;
            }
        }

        if (!podeConferir) {
            if (somAlerta) {
                enfileirarSom(somAlerta);
            }
            input.value = "";
            return;
        }
        
        linha.classList.add("confirmado");
        atualizarContadoresConferidos();

        var celLidoEm = linha.querySelector('.cel-lido-em');
        if (celLidoEm && celLidoEm.textContent.trim() === '') {
            var agora = new Date();
            var dd = agora.getDate(); if (dd < 10) dd = '0' + dd;
            var mm = agora.getMonth() + 1; if (mm < 10) mm = '0' + mm;
            var yyyy = agora.getFullYear();
            var hh = agora.getHours(); if (hh < 10) hh = '0' + hh;
            var mi = agora.getMinutes(); if (mi < 10) mi = '0' + mi;
            var ss = agora.getSeconds(); if (ss < 10) ss = '0' + ss;
            celLidoEm.textContent = dd + '/' + mm + '/' + yyyy + ' ' + hh + ':' + mi + ':' + ss;
        }
        
        if (!muteBeep || !muteBeep.checked) {
            enfileirarSom(beep);
        }
        if (somAlerta) {
            enfileirarSom(somAlerta);
        }

        if (mensagemLeitura) {
            mensagemLeitura.textContent = '';
        }
        
        var ultimas = document.querySelectorAll('tr.ultimo-lido');
        for (var u = 0; u < ultimas.length; u++) {
            ultimas[u].classList.remove('ultimo-lido');
        }
        linha.classList.add('ultimo-lido');

        var rect = linha.getBoundingClientRect();
        var alvo = rect.top + window.pageYOffset - (window.innerHeight / 2) + (rect.height / 2);
        window.scrollTo({ top: alvo, behavior: 'smooth' });
        
        var lote = linha.getAttribute("data-lote");
        var regional = linha.getAttribute("data-regional");
        var posto = linha.getAttribute("data-posto");
        var dataexp = linha.getAttribute("data-data-sql") || linha.getAttribute("data-data");
        var qtd = linha.getAttribute("data-qtd");
        var codbar = linha.getAttribute("data-codigo");

        if (radioAutoSalvar.checked) {
            salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, usuarioAtual);
        }

        var regionalReal = linha.getAttribute("data-regional-real") || regional;
        adicionarLoteSemMalote(lote, posto, regional, qtd, regionalReal, codbar);
        
        var grupoAtual = null;
        var todasLinhas = document.querySelectorAll('tbody tr');
        var linhasDoGrupo = [];
        
        if (tipoAtual === 'poupatempo') {
            grupoAtual = linha.getAttribute('data-pt-group') || linha.getAttribute('data-posto');
            for (var i = 0; i < todasLinhas.length; i++) {
                if (todasLinhas[i].getAttribute('data-ispt') === '1' &&
                    (todasLinhas[i].getAttribute('data-pt-group') === grupoAtual || todasLinhas[i].getAttribute('data-posto') === grupoAtual)) {
                    linhasDoGrupo.push(todasLinhas[i]);
                }
            }
        } else if (tipoAtual === 'todos') {
            grupoAtual = regionalAtual;
            for (var i = 0; i < todasLinhas.length; i++) {
                var rrGrupo = todasLinhas[i].getAttribute('data-regional-real') || todasLinhas[i].getAttribute('data-regional');
                if (rrGrupo === regionalAtual || todasLinhas[i].getAttribute('data-regional') === regionalAtual) {
                    linhasDoGrupo.push(todasLinhas[i]);
                }
            }
        } else {
            grupoAtual = regionalAtual;
            for (var i = 0; i < todasLinhas.length; i++) {
                var rrGrupo2 = todasLinhas[i].getAttribute('data-regional-real') || todasLinhas[i].getAttribute('data-regional');
                if ((rrGrupo2 === regionalAtual || todasLinhas[i].getAttribute('data-regional') === regionalAtual) && 
                    todasLinhas[i].getAttribute('data-ispt') !== '1') {
                    linhasDoGrupo.push(todasLinhas[i]);
                }
            }
        }
        
        var conferidosDoGrupo = 0;
        for (var j = 0; j < linhasDoGrupo.length; j++) {
            if (linhasDoGrupo[j].classList.contains('confirmado')) {
                conferidosDoGrupo++;
            }
        }
        
        if (conferidosDoGrupo === linhasDoGrupo.length && linhasDoGrupo.length > 0) {
            enfileirarSom(concluido);
            regionalAtual = null;
            primeiroConferido = false;
            // Verifica se TODOS os Correios foram concluidos
            setTimeout(function() { verificarTodosCorreiosConferidos(); }, 800);
        }
    }

    // ============================================================
    // SOM FINAL + CREDITOS AO CONCLUIR TODOS OS CORREIOS
    // ============================================================
    var somFinalJaTocou = false;
    var somFinalAudio = document.getElementById("somFinalConf");
    var chkSomFinal = document.getElementById("toggleSomFinal");
    var chkCreditos = document.getElementById("toggleCreditos");

    function verificarTodosCorreiosConferidos() {
        var linhasCorreios = document.querySelectorAll(
            ".secao-tipo[data-secao-tipo=\"correios\"] tbody tr.linha-conferencia"
        );
        if (!linhasCorreios || linhasCorreios.length === 0) return;
        var total = linhasCorreios.length;
        var conf = 0;
        for (var i = 0; i < linhasCorreios.length; i++) {
            if (linhasCorreios[i].classList.contains("confirmado")) conf++;
        }
        if (conf === total && !somFinalJaTocou) {
            somFinalJaTocou = true;
            iniciarFimConferencia();
        }
    }

    function iniciarFimConferencia() {
        /* concluido.mp3 esta sendo tocado pela fila — aguarda terminar antes de som_final */
        var somFinalOk = false;
        function dispararSomFinal() {
            if (somFinalOk) return;
            somFinalOk = true;
            concluido.removeEventListener("ended", dispararSomFinal);
            clearTimeout(fbTimer);
            if (chkSomFinal && chkSomFinal.checked && somFinalAudio) {
                somFinalAudio.currentTime = 0;
                somFinalAudio.play();
            }
        }
        var fbTimer = setTimeout(dispararSomFinal, 10000);
        concluido.addEventListener("ended", dispararSomFinal);
        if (chkCreditos && chkCreditos.checked) {
            setTimeout(function() { mostrarCreditos(); }, 1500);
        }
    }

    function pararSomFinal() {
        if (somFinalAudio) { somFinalAudio.pause(); somFinalAudio.currentTime = 0; }
    }

    // Toggle som: desligar para imediatamente
    if (chkSomFinal) {
        chkSomFinal.addEventListener("change", function() {
            if (!this.checked) pararSomFinal();
        });
    }

    function coletarResumoCreditos() {
        var linhasConf = document.querySelectorAll(
            ".secao-tipo[data-secao-tipo=\"correios\"] tbody tr.linha-conferencia.confirmado"
        );
        var linhasNaoConf = document.querySelectorAll(
            ".secao-tipo[data-secao-tipo=\"correios\"] tbody tr.linha-conferencia:not(.confirmado)"
        );
        var postos = {};
        var regionais = {};
        var totalLotes = 0;
        var totalPacotes = 0;
        var totalLotesNaoConf = 0;
        for (var i = 0; i < linhasConf.length; i++) {
            var tr = linhasConf[i];
            var p = tr.getAttribute("data-posto") || "?";
            var rr = tr.getAttribute("data-regional-real") || tr.getAttribute("data-regional") || "?";
            var qtd = parseInt(tr.getAttribute("data-qtd") || "0", 10) || 0;
            if (!postos[p]) postos[p] = {qtdConf:0, lotesConf:0, lotesNaoConf:0, regional:rr};
            postos[p].qtdConf += qtd;
            postos[p].lotesConf++;
            totalLotes++;
            totalPacotes += qtd;
            if (!regionais[rr]) regionais[rr] = {lotesConf:0, lotesNaoConf:0, pacotes:0, postos:[]};
            regionais[rr].lotesConf++;
            regionais[rr].pacotes += qtd;
            if (regionais[rr].postos.indexOf(p) === -1) regionais[rr].postos.push(p);
        }
        for (var j = 0; j < linhasNaoConf.length; j++) {
            var tr2 = linhasNaoConf[j];
            var p2 = tr2.getAttribute("data-posto") || "?";
            var rr2 = tr2.getAttribute("data-regional-real") || tr2.getAttribute("data-regional") || "?";
            if (!postos[p2]) postos[p2] = {qtdConf:0, lotesConf:0, lotesNaoConf:0, regional:rr2};
            postos[p2].lotesNaoConf++;
            totalLotesNaoConf++;
            if (!regionais[rr2]) regionais[rr2] = {lotesConf:0, lotesNaoConf:0, pacotes:0, postos:[]};
            regionais[rr2].lotesNaoConf++;
        }
        var totalPostos = 0;
        for (var pp in postos) totalPostos++;
        return {
            postos:postos, regionais:regionais,
            totalLotes:totalLotes, totalPacotes:totalPacotes,
            totalPostos:totalPostos, totalLotesNaoConf:totalLotesNaoConf
        };
    }

    function nomeRegional(rr){
        if(rr==="0") return "Capital";
        if(rr==="999") return "Metropolitana";
        return "Regional "+rr;
    }

    function mostrarCreditos(){
        var overlay=document.getElementById("creditsOverlay");
        var track=document.getElementById("creditsTrack");
        if(!overlay||!track) return;
        var pad=function(n){return n<10?"0"+n:""+n;};
        var agora=new Date();
        var dataStr=pad(agora.getDate())+"/"+pad(agora.getMonth()+1)+"/"+agora.getFullYear();
        var horaStr=pad(agora.getHours())+":"+pad(agora.getMinutes())+":"+pad(agora.getSeconds());
        var anoStr=agora.getFullYear();
        var res=coletarResumoCreditos();
        var postos=res.postos; var regs=res.regionais;
        var tL=res.totalLotes; var tP=res.totalPacotes;
        var tPosto=res.totalPostos; var tNaoConf=res.totalLotesNaoConf;
        var h="";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-cat cr-cat-first'>Sistema de Conferencia de Pacotes</div>";
        h+="<div class='cr-pessoa'>Celepar</div>";
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Data da Conferencia</div>";
        h+="<div class='cr-pessoa'>"+dataStr+"</div>";
        h+="<div class='cr-sub'>"+horaStr+"</div>";
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Responsavel pela Conferencia</div>";
        h+="<div class='cr-pessoa'>"+(usuarioAtual||"Nao informado")+"</div>";
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Resultado Geral</div>";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-num'>"+tL+"</div>";
        h+="<div class='cr-num-label'>Lotes Conferidos</div>";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-num'>"+tP+"</div>";
        h+="<div class='cr-num-label'>Pacotes Totais</div>";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-num'>"+tPosto+"</div>";
        h+="<div class='cr-num-label'>Postos Atendidos</div>";
        if(tNaoConf>0){
            h+="<div style='height:40px'></div>";
            h+="<div class='cr-num alerta'>"+tNaoConf+"</div>";
            h+="<div class='cr-num-label' style='color:#6b0000'>Lotes Nao Conferidos</div>";
            h+="<div class='cr-linha' style='color:#6b0000'>Atencao: "+tNaoConf+" lote(s) presentes na tela nao foram lidos nesta sessao.</div>";
        }
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Resultado por Regional</div>";
        var rrK=[];
        for(var rk in regs) rrK.push(rk);
        rrK.sort(function(a,b){return parseInt(a,10)-parseInt(b,10);});
        for(var ri=0;ri<rrK.length;ri++){
            var rk2=rrK[ri]; var rd=regs[rk2];
            h+="<div style='height:50px'></div>";
            h+="<div class='cr-sub'>"+nomeRegional(rk2)+"</div>";
            h+="<div class='cr-linha'>"+rd.lotesConf+" lotes conferidos</div>";
            h+="<div class='cr-linha'>"+rd.pacotes+" pacotes</div>";
            h+="<div class='cr-linha'>"+rd.postos.length+" posto(s)</div>";
            if(rd.lotesNaoConf>0) h+="<div class='cr-linha' style='color:#6b0000'>"+rd.lotesNaoConf+" nao conferido(s)</div>";
        }
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Detalhe por Posto</div>";
        var pK=[];
        for(var pk in postos) pK.push(pk);
        pK.sort(function(a,b){return parseInt(a,10)-parseInt(b,10);});
        var lastReg="";
        for(var pi=0;pi<pK.length;pi++){
            var pk2=pK[pi]; var pd=postos[pk2];
            var rn=nomeRegional(pd.regional);
            if(rn!==lastReg){ h+="<div style='height:50px'></div><div class='cr-sub'>"+rn+"</div>"; lastReg=rn; }
            h+="<div style='height:24px'></div>";
            h+="<div class='cr-linha'>Posto "+pk2+"</div>";
            if(pd.lotesConf>0) h+="<div class='cr-linha-sm'>"+pd.lotesConf+" lote(s) &bull; "+pd.qtdConf+" pacote(s)</div>";
            if(pd.lotesNaoConf>0) h+="<div class='cr-linha-sm' style='color:#6b0000'>"+pd.lotesNaoConf+" nao conferido(s)</div>";
        }
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Participacao Especial</div>";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-sub'>Responsavel pela Conferencia</div>";
        h+="<div class='cr-pessoa'>"+(usuarioAtual||"Nao informado")+"</div>";
        h+="<div style='height:40px'></div>";
        h+="<div class='cr-sub'>Postos Participantes</div>";
        for(var ppi=0;ppi<pK.length;ppi++) h+="<div class='cr-linha'>Posto "+pK[ppi]+"</div>";
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Destaque Especial</div>";
        h+="<div style='height:30px'></div>";
        h+="<div class='cr-sub'>Destacador Oficial de Peliculas</div>";
        h+="<div class='cr-pessoa'>Arlindo Borges</div>";
        h+="<hr class='cr-sep'>";
        h+="<div style='height:80px'></div>";
        h+="<div class='cr-cat'>Producao</div>";
        h+="<div class='cr-pessoa'>Celepar</div>";
        h+="<div class='cr-sub'>v0.9.46 &bull; "+anoStr+"</div>";
        h+="<div style='height:400px'></div>";
        track.style.animation="none";
        track.style.top="";
        track.innerHTML=h;
        track.offsetHeight;
        var H=track.scrollHeight;
        var V=window.innerHeight||900;
        /* --- calculo geometrico correto do crawl 3D ---
           perspective-origin: 50% 0%  (ponto de fuga no TOPO da tela)
           transform-origin:   50% 100% (pivo na BASE do elemento)
           rotateX(25deg):     conteudo acima da base tem z negativo (afastado do viewer)
           Formula: startTop = V * (persp + H*sin25) / persp
           Com essa posicao o topo projetado do elemento esta exatamente na borda inferior da viewport,
           e a base (z=0) permanece abaixo — nada visivel no inicio. */
        var persp=350;
        var sin25=0.4226; /* sin(25deg) */
        var startTop=Math.ceil(V*(persp+H*sin25)/persp);
        var endTop=-(H+200);
        var totalPx=startTop-endTop;
        var speed=60; /* px/s aparente na linha inferior (base z=0) */
        var durSec=Math.ceil(totalPx/speed);
        var oldSt=document.getElementById("crDynStyle");
        if(oldSt) oldSt.parentNode.removeChild(oldSt);
        var stEl=document.createElement("style");
        stEl.id="crDynStyle";
        /* keyframe anima apenas 'top'; transform fixo no CSS */
        stEl.innerHTML="@keyframes creditsSobe{from{top:"+startTop+"px;}to{top:"+endTop+"px;}}";
        document.head.appendChild(stEl);
        track.style.top=startTop+"px";
        track.style.animationName="creditsSobe";
        track.style.animationDuration=durSec+"s";
        track.style.animationTimingFunction="linear";
        track.style.animationFillMode="forwards";
        /* THE END dispara quando a base (z=0) cruza o topo da viewport:
           isso ocorre quando element_css_bottom = 0  =>  top = -H
           distancia percorrida = startTop - (-H) = startTop + H */
        var theEndPx=startTop+H;
        var theEndMs=Math.ceil(theEndPx/speed)*1000;
        overlay.style.display="block";
        var timerTE=setTimeout(function(){
            var ted=document.getElementById("theEndDiv");
            if(ted&&overlay.style.display==="block"){
                ted.style.display="block";
                setTimeout(function(){ document.addEventListener("mousemove",fecharNaMouse); },3000);
            }
        },theEndMs);
        function fecharTudo(){
            clearTimeout(timerTE);
            overlay.style.display="none";
            var ted2=document.getElementById("theEndDiv");
            if(ted2) ted2.style.display="none";
            pararSomFinal();
            overlay.removeEventListener("click",fecharTudo);
            document.removeEventListener("keydown",fecharTudo);
            document.removeEventListener("mousemove",fecharNaMouse);
        }
        function fecharNaMouse(){ fecharTudo(); }
        overlay.addEventListener("click",fecharTudo);
        document.addEventListener("keydown",fecharTudo);
    }

        // Resetar flag ao reiniciar conferencia
    btnResetar.addEventListener("click", function() {
        if (confirm("Tem certeza que deseja reiniciar a conferencia? Isso ira APAGAR todos os dados conferidos do banco!")) {
            somFinalJaTocou = false;
            pararSomFinal();
            var ov = document.getElementById("creditsOverlay");
            if (ov) ov.style.display = "none";
            var trsConfirmados = document.querySelectorAll("tr.confirmado");
            for (var j = 0; j < trsConfirmados.length; j++) {
                trsConfirmados[j].classList.remove("confirmado");
                trsConfirmados[j].style.backgroundColor = '';
                trsConfirmados[j].removeAttribute('data-conferido');
            }

            var allTrs = document.querySelectorAll('tbody tr');
            for (var k = 0; k < allTrs.length; k++) {
                allTrs[k].style.backgroundColor = '';
                allTrs[k].classList.remove('confirmado');
                allTrs[k].classList.remove('ultimo-lido');
            }
            atualizarContadoresConferidos();

            var valoresCards = document.querySelectorAll('.card-resumo .valor');
            for (var c = 0; c < valoresCards.length; c++) {
                valoresCards[c].textContent = '0';
            }
            
            regionalAtual = null;
            tipoAtual = null;
            primeiroConferido = false;
            input.value = "";
            input.focus();

            var datasExib = <?php echo json_encode($datas_exib); ?>;
            if (datasExib.length > 0) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                var formData = new FormData();
                formData.append('excluir_lote_ajax', '1');
                formData.append('datas', datasExib.join(','));
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.sucesso) {
                                alert('Conferencias resetadas com sucesso!');
                            }
                        } catch (e) {}
                    }
                };
                xhr.send(formData);
            }
        }
    });

    window.toggleIndicadorDias = function() {
        var el = document.getElementById('indicador-dias');
        if (!el) return;
        if (el.classList.contains('collapsed')) {
            el.classList.remove('collapsed');
        } else {
            el.classList.add('collapsed');
        }
    };

    function atualizarMapaBloqueados() {
        postosBloqueadosMap = {};
        for (var i = 0; i < postosBloqueados.length; i++) {
            postosBloqueadosMap[postosBloqueados[i].posto] = postosBloqueados[i];
        }
    }

    function renderizarPostosBloqueados() {
        if (!listaPostosBloqueados) return;
        listaPostosBloqueados.innerHTML = '';
        for (var i = 0; i < postosBloqueados.length; i++) {
            var p = postosBloqueados[i];
            var div = document.createElement('div');
            div.className = 'bloqueio-item';
            div.setAttribute('data-posto', p.posto);
            div.innerHTML = '<div><span class="posto">' + p.posto + '</span> ' + (p.nome || '') + '</div>' +
                '<button type="button" class="btn-acao btn-cancelar" data-remover="' + p.posto + '">Remover</button>';
            listaPostosBloqueados.appendChild(div);
        }
        atualizarMapaBloqueados();
    }

    if (btnAdicionarBloqueio) {
        btnAdicionarBloqueio.addEventListener('click', function() {
            var posto = postoBloqueioNumero ? postoBloqueioNumero.value.trim() : '';
            var nome = postoBloqueioNome ? postoBloqueioNome.value.trim() : '';
            if (!posto) {
                alert('Informe o numero do posto.');
                if (postoBloqueioNumero) postoBloqueioNumero.focus();
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            var formData = new FormData();
            formData.append('salvar_posto_bloqueado', '1');
            formData.append('posto', posto);
            formData.append('nome', nome);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data && data.success) {
                            postosBloqueados.push({ posto: posto, nome: nome });
                            renderizarPostosBloqueados();
                            if (postoBloqueioNumero) postoBloqueioNumero.value = '';
                            if (postoBloqueioNome) postoBloqueioNome.value = '';
                        } else {
                            alert('Erro ao salvar posto bloqueado.');
                        }
                    } catch (e) {
                        alert('Erro ao salvar posto bloqueado.');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    if (listaPostosBloqueados) {
        listaPostosBloqueados.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) return;
            var posto = target.getAttribute('data-remover');
            if (!posto) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            var formData = new FormData();
            formData.append('excluir_posto_bloqueado', '1');
            formData.append('posto', posto);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data && data.success) {
                            var novos = [];
                            for (var i = 0; i < postosBloqueados.length; i++) {
                                if (postosBloqueados[i].posto !== posto) {
                                    novos.push(postosBloqueados[i]);
                                }
                            }
                            postosBloqueados = novos;
                            renderizarPostosBloqueados();
                        } else {
                            alert('Erro ao remover posto bloqueado.');
                        }
                    } catch (e) {
                        alert('Erro ao remover posto bloqueado.');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    renderizarPostosBloqueados();

    var btnFecharIIPR = document.getElementById('btnFecharLotesIIPR');
    if (btnFecharIIPR) btnFecharIIPR.addEventListener('click', function() { mvFecharLotesComoIIPR(); });

    var mvScanInput = document.getElementById('mvScanInput');
    if (mvScanInput) {
        mvScanInput.addEventListener('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                var val = mvScanInput.value.trim();
                mvScanLote(val);
                mvScanInput.value = '';
                mvScanInput.focus();
            }
        });
        mvScanInput.addEventListener('input', function() {
            var val = mvScanInput.value.trim();
            if (val.length === 19) {
                mvScanLote(val);
                mvScanInput.value = '';
                mvScanInput.focus();
            }
        });
    }

    var btnCriarCorreios = document.getElementById('btnCriarMaloteCorreios');
    if (btnCriarCorreios) btnCriarCorreios.addEventListener('click', mvCriarMaloteCorreios);

    var btnSalvarMV = document.getElementById('btnSalvarMaloteamento');
    if (btnSalvarMV) btnSalvarMV.addEventListener('click', function() { salvarMaloteamentoAjax(false); });

    var btnSobrescreverMV = document.getElementById('btnSobrescreverMaloteamento');
    if (btnSobrescreverMV) btnSobrescreverMV.addEventListener('click', function() { salvarMaloteamentoAjax(true); });

    var btnImprimirMV = document.getElementById('btnImprimirMaloteamento');
    if (btnImprimirMV) btnImprimirMV.addEventListener('click', imprimirMaloteamento);

    document.addEventListener('click', desbloquearAudio);
    document.addEventListener('touchstart', desbloquearAudio);
});
</script>

<div id="creditsOverlay">
    <div id="creditsScene">
        <div id="creditsTrack"></div>
    </div>
    <div id="creditsDica">Clique ou pressione qualquer tecla para fechar</div>
</div>
<div id="theEndDiv"><span class="the-end-texto">THE END</span></div>

</body>
</html>