<?php
/* conferencia_pacotes.php — v9.8.8.1
 * CHANGELOG v9.8.3:
 * - [MELHORIA] Revisão de conexões/queries: fecha conexões/cursores sempre que possível.
 * - [ADICIONADO] Total de carteiras expedidas (db servico.tbl_ci_filadeimpressao) no topo, para comparação.
 *
 * CHANGELOG v9.8.4:
 * - [CORRIGIDO] Auto-carregamento otimizado: escolhe datas sem conferência por checagem simples (sem JOIN/NOT EXISTS pesado).
 * - [ALTERADO] Filtro: removido modo rápido, mantendo apenas filtro avançado.
 * - [ALTERADO] Ao abrir sem filtro, carrega automaticamente o período com produção sem conferência registrada.
 * - [ADICIONADO] Mostra postos retirados (extraído do protocolo de ciRetirada) e destaca diferença entre carteiras na tela x expedidas.
 *
 * CHANGELOG v9.8.2:
 * - [ADICIONADO] Filtro avançado de datas (intervalo e/ou dias alternados além do padrão das últimas 5 datas).
 * - [ADICIONADO] Totais no topo: Total de pacotes, Total de carteiras (soma da quantidade),
 *   Total de retiradas/agilizações (ciRetirada) e Total de canceladas (status=0 em ciRetirada).
 * - [CORRIGIDO] Ao carregar a página (inclusive escolhendo datas antigas), as linhas já conferidas (conf='s')
 *   agora aparecem em verde corretamente (normalização do lote + chave de conferência).
 * - [ADICIONADO] Coluna "Conferido em" (lido_em) ao lado do Código de Barras.
 * - Mantém toda funcionalidade anterior.
 */

function colunaExiste(PDO $pdo, $tabela, $coluna) {
    try {
        $sql = "SHOW COLUMNS FROM `" . str_replace('`','',$tabela) . "` LIKE ?";
        $st = $pdo->prepare($sql);
        $st->execute(array($coluna));
        return (bool)$st->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

// Normaliza data para YYYY-MM-DD (aceita YYYY-MM-DD, DD-MM-YYYY e DD/MM/YYYY)
function normalizarDataSQL($dataStr) {
    $dataStr = trim($dataStr);
    if ($dataStr === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataStr)) {
        return $dataStr;
    }
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dataStr)) {
        $p = explode('-', $dataStr);
        return $p[2] . '-' . $p[1] . '-' . $p[0];
    }
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataStr)) {
        $p = explode('/', $dataStr);
        return $p[2] . '-' . $p[1] . '-' . $p[0];
    }
    return '';
}

// Formata datetime SQL (YYYY-MM-DD HH:MM:SS) para pt-BR sem converter fuso (evita diferenças PHP/JS)
// Retorna "DD/MM/YYYY, HH:MM:SS" quando possível.
function formatarLidoEmBR($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    // Já está no formato brasileiro
    if (preg_match('/^\d{2}\/\d{2}\/\d{4},\s*\d{2}:\d{2}:\d{2}$/', $raw)) return $raw;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2}$/', $raw)) return str_replace('  ', ' ', str_replace(' ', ', ', $raw));
    // Formato SQL completo
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $raw, $p)) {
        return $p[3] . '/' . $p[2] . '/' . $p[1] . ', ' . $p[4] . ':' . $p[5] . ':' . $p[6];
    }
    // Formato SQL sem segundos
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})$/', $raw, $p)) {
        return $p[3] . '/' . $p[2] . '/' . $p[1] . ', ' . $p[4] . ':' . $p[5] . ':00';
    }
    return $raw;
}


// Inicializa variáveis
$total_codigos = 0;
$total_carteiras = 0;
$total_retiradas = 0;
$total_canceladas = 0;
        $postos_retirados_lista = '';
$total_expedidas_periodo = 0;
$regionais_data = array();
$postos_retirados_lista = '';
// v9.8.2: Filtro avançado
$dt_ini = isset($_GET['dt_ini']) ? trim($_GET['dt_ini']) : '';
$dt_fim = isset($_GET['dt_fim']) ? trim($_GET['dt_fim']) : '';
$datas_custom = isset($_GET['datas_custom']) ? trim($_GET['datas_custom']) : '';
$userProvidedFilter = (isset($_GET['dt_ini']) || isset($_GET['dt_fim']) || isset($_GET['datas_custom']));
$poupaTempoPostos = array();
$conferencias = array();

// Conexão
$host = getenv('DB_HOST') ?: '10.15.61.169';
$dbname = getenv('DB_NAME') ?: 'controle';
$user = getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat');
$pass = getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // v9.8: Detecta colunas opcionais
    // v9.8.7: Descobre qual coluna de data/hora existe para exibir "Conferido em"
    $CONF_TS_COL = null;
    if (colunaExiste($pdo, 'conferencia_pacotes', 'lido_em')) {
        $CONF_TS_COL = 'lido_em';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'lidoEm')) {
        $CONF_TS_COL = 'lidoEm';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'datahora')) {
        $CONF_TS_COL = 'datahora';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'data_hora')) {
        $CONF_TS_COL = 'data_hora';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'data')) {
        $CONF_TS_COL = 'data';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'created_at')) {
        $CONF_TS_COL = 'created_at';
    } elseif (colunaExiste($pdo, 'conferencia_pacotes', 'createdAt')) {
        $CONF_TS_COL = 'createdAt';
    }
    $HAS_LIDO_EM = ($CONF_TS_COL !== null);
    $HAS_CODBAR  = colunaExiste($pdo, 'conferencia_pacotes', 'codbar');

// Handler AJAX salvar
    if (isset($_POST['salvar_lote_ajax'])) {
        $lote = trim($_POST['lote']);
        $regional = trim($_POST['regional']);
        $posto = trim($_POST['posto']);
        $dataexp = trim($_POST['dataexp']);
        // v9.8.1: Normaliza dataexp (aceita YYYY-MM-DD, DD-MM-YYYY ou DD/MM/YYYY)
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dataexp)) {
            $p = explode('-', $dataexp);
            $dataexp = $p[2] . '-' . $p[1] . '-' . $p[0];
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataexp)) {
            $p = explode('/', $dataexp);
            $dataexp = $p[2] . '-' . $p[1] . '-' . $p[0];
        }

        // v9.8.2: Se dataexp vier vazia, tenta derivar da ciPostosCsv pela combinação lote+posto
        if ($dataexp === '' || $dataexp === '0000-00-00') {
            try {
                $lote_pad = str_pad($lote, 8, '0', STR_PAD_LEFT);
                $posto_pad = str_pad($posto, 3, '0', STR_PAD_LEFT);
                $stD = $pdo->prepare("SELECT DATE(dataCarga) AS dc FROM ciPostosCsv WHERE lote = ? AND LPAD(posto,3,'0') = ? ORDER BY dataCarga DESC LIMIT 1");
                $stD->execute(array($lote_pad, $posto_pad));
                $rD = $stD->fetch(PDO::FETCH_ASSOC);
                if ($rD && !empty($rD['dc'])) {
                    $dataexp = $rD['dc'];
                }
            } catch (Exception $e) {
                // mantém como veio
            }
        }

        $qtd = (int)$_POST['qtd'];
        $codbar = trim($_POST['codbar']);
        
        if ($HAS_LIDO_EM && !empty($CONF_TS_COL)) {
            // v9.8.7: usa a coluna real de data/hora encontrada em $CONF_TS_COL
            $tsCol = '`' . str_replace('`','', $CONF_TS_COL) . '`';
            $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf, " . $tsCol . ")
                    VALUES (?, ?, ?, ?, ?, ?, 's', NOW())
                    ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar), " . $tsCol . "=NOW()";
        } else {
            $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf) 
                    VALUES (?, ?, ?, ?, ?, ?, 's')
                    ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($regional, $lote, $posto, $dataexp, $qtd, $codbar));
        $stmt = null; // v8.17.4: Libera statement
        // v9.8.8: retorna o timestamp REAL salvo no banco (evita diferença de fuso entre PHP/Browser/MySQL)
        $lido_fmt = '';
        if ($HAS_LIDO_EM && !empty($CONF_TS_COL)) {
            $tsColSel = '`' . str_replace('`','', $CONF_TS_COL) . '`';
            try {
                $stTS = $pdo->prepare("SELECT DATE_FORMAT(" . $tsColSel . ", '%d/%m/%Y, %H:%i:%s') AS lido_fmt
                                       FROM conferencia_pacotes
                                       WHERE CAST(nlote AS UNSIGNED)=CAST(? AS UNSIGNED)
                                         AND CAST(regional AS UNSIGNED)=CAST(? AS UNSIGNED)
                                         AND LPAD(nposto,3,'0')=LPAD(?,3,'0')
                                       ORDER BY id DESC
                                       LIMIT 1");
                $stTS->execute(array($lote, $regional, $posto));
                $rTS = $stTS->fetch(PDO::FETCH_ASSOC);
                if ($rTS && !empty($rTS['lido_fmt'])) {
                    $lido_fmt = $rTS['lido_fmt'];
                }
            } catch (Exception $e) {
                // fallback
                $lido_fmt = '';
            }
        }

        
        $pdo = null;  // Fecha conexão (v9.8.8)
die(json_encode(array('success' => true, 'sucesso' => true, 'lido_em' => $lido_fmt)));
    }

    // Handler AJAX excluir
    if (isset($_POST['excluir_lote_ajax'])) {
        $lote = trim($_POST['lote']);
        $regional = trim($_POST['regional']);
        $posto = trim($_POST['posto']);
        
        $sql = "DELETE FROM conferencia_pacotes WHERE nlote = ? AND regional = ? AND nposto = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($lote, $regional, $posto));
        $stmt = null; // v8.17.4: Libera statement
        $pdo = null;  // v8.17.4: Fecha conexão
        die(json_encode(array('success' => true, 'sucesso' => true, 'lido_em' => '')));
    }


    
    // Handler AJAX inserir pacote não carregado (barcode não encontrado na lista)
    // v9.8.6:
    // - Suporta salvar 1 item OU vários (itens_json) para permitir "fila" e salvamento em lote.
    // - Preenche ciPostosCsv.data (datetime) quando a coluna existir.
    // - Usa data_ref + hora atual para preencher datetime (dataCarga/data).
    if (isset($_POST['inserir_nao_carregado_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');

        // Colunas opcionais (detecta 1x)
        $HAS_USUARIO_CSV = colunaExiste($pdo, 'ciPostosCsv', 'usuario');
        $HAS_DATA_CSV    = colunaExiste($pdo, 'ciPostosCsv', 'data');

        // Normaliza lista de itens (single ou lote)
        $lista = array();
        if (isset($_POST['itens_json']) && trim($_POST['itens_json']) !== '') {
            $tmp = json_decode($_POST['itens_json'], true);
            if (is_array($tmp)) {
                $lista = $tmp;
            }
        } else {
            $lista[] = array(
                'codbar'   => isset($_POST['codbar']) ? $_POST['codbar'] : '',
                'data_ref' => isset($_POST['data_ref']) ? $_POST['data_ref'] : '',
                'turno'    => isset($_POST['turno']) ? $_POST['turno'] : '',
                'usuario'  => isset($_POST['usuario']) ? $_POST['usuario'] : ''
            );
        }

        if (empty($lista)) {
            $pdo = null;
            die(json_encode(array('success' => false, 'msg' => 'Nenhum item para salvar.')));
        }

        $results = array();

        foreach ($lista as $it) {
            $codbar_raw = isset($it['codbar']) ? $it['codbar'] : '';
            $codbar = preg_replace('/\D/', '', $codbar_raw);

            if (strlen($codbar) != 19) {
                $results[] = array('codbar' => $codbar_raw, 'success' => false, 'msg' => 'Código inválido (19 dígitos).');
                continue;
            }

            $lote_str    = substr($codbar, 0, 8);
            $regional_str = substr($codbar, 8, 3);
            $posto_str   = substr($codbar, 11, 3);
            $qtd_str     = substr($codbar, 14, 5);

            $regional_int = (int)ltrim($regional_str, '0');
            $posto_int    = (int)ltrim($posto_str, '0');
            $qtd_int      = (int)ltrim($qtd_str, '0');

            $data_ref = isset($it['data_ref']) ? trim($it['data_ref']) : '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ref)) {
                $data_ref = date('Y-m-d');
            }

            $turno = isset($it['turno']) ? (int)$it['turno'] : 2;
            if ($turno < 1 || $turno > 4) $turno = 2;

            $usuario_nome = isset($it['usuario']) ? trim($it['usuario']) : '';
            if ($usuario_nome === '') $usuario_nome = 'desconhecido';

            // datetime completo (evita ficar NULL/0000-00-00 em colunas datetime/date)
            $data_dt = $data_ref . ' ' . date('H:i:s');

            // --- Inserir/atualizar em ciPostosCsv ---
            try {
                $cols = array('lote', 'posto', 'regional', 'quantidade', 'dataCarga');
                $vals = array($lote_str, $posto_int, $regional_int, $qtd_int, $data_dt);

                if ($HAS_USUARIO_CSV) { $cols[] = 'usuario'; $vals[] = $usuario_nome; }
                if ($HAS_DATA_CSV)    { $cols[] = '`data`';  $vals[] = $data_dt; }

                $ph = implode(',', array_fill(0, count($cols), '?'));

                $sqlCsv = "INSERT INTO ciPostosCsv (" . implode(',', $cols) . ") VALUES (" . $ph . ")";
                // tenta ON DUPLICATE (se houver chave única)
                $upd = array("quantidade=VALUES(quantidade)", "dataCarga=VALUES(dataCarga)");
                if ($HAS_USUARIO_CSV) { $upd[] = "usuario=VALUES(usuario)"; }
                if ($HAS_DATA_CSV)    { $upd[] = "`data`=VALUES(`data`)"; }

                $sqlCsvDup = $sqlCsv . " ON DUPLICATE KEY UPDATE " . implode(', ', $upd);

                $stCsv = $pdo->prepare($sqlCsvDup);
                $stCsv->execute($vals);
            } catch (Exception $e) {
                // fallback sem ON DUPLICATE
                try {
                    $stCsv2 = $pdo->prepare($sqlCsv);
                    $stCsv2->execute($vals);
                } catch (Exception $e2) {
                    $results[] = array('codbar' => $codbar, 'success' => false, 'msg' => 'Falha ciPostosCsv: ' . $e2->getMessage());
                    continue;
                }
            }

            // --- Inserir também em ciPostos (se existir) ---
            try {
                $posto_pad  = str_pad($posto_int, 3, '0', STR_PAD_LEFT);
                $posto_full = $posto_pad . ' - (POSTO NÃO CADASTRADO)';

                $stP = $pdo->prepare("SELECT posto FROM ciPostos WHERE posto LIKE ? ORDER BY id DESC LIMIT 1");
                $stP->execute(array($posto_pad . ' -%'));
                $rP = $stP->fetch(PDO::FETCH_ASSOC);
                if ($rP && !empty($rP['posto'])) {
                    $posto_full = $rP['posto'];
                }

                $campos = array();
                $vals2  = array();
                $pars2  = array();

                if (colunaExiste($pdo, 'ciPostos', 'posto')) { $campos[]='posto'; $vals2[]='?'; $pars2[]=$posto_full; }
                if (colunaExiste($pdo, 'ciPostos', 'dia')) { $campos[]='dia'; $vals2[]='?'; $pars2[]=$data_ref; }
                if (colunaExiste($pdo, 'ciPostos', 'quantidade')) { $campos[]='quantidade'; $vals2[]='?'; $pars2[]=$qtd_int; }
                if (colunaExiste($pdo, 'ciPostos', 'turno')) { $campos[]='turno'; $vals2[]='?'; $pars2[]=$turno; }
                if (colunaExiste($pdo, 'ciPostos', 'regional')) { $campos[]='regional'; $vals2[]='?'; $pars2[]=$regional_int; }
                if (colunaExiste($pdo, 'ciPostos', 'situacao')) { $campos[]='situacao'; $vals2[]='?'; $pars2[]=0; }

                $lote_int = (int)ltrim($lote_str, '0');
                if (colunaExiste($pdo, 'ciPostos', 'lote')) { $campos[]='lote'; $vals2[]='?'; $pars2[]=$lote_int; }

                if (colunaExiste($pdo, 'ciPostos', 'autor')) { $campos[]='autor'; $vals2[]='?'; $pars2[]=$usuario_nome; }

                if (colunaExiste($pdo, 'ciPostos', 'criado')) { $campos[]='criado'; $vals2[]='?'; $pars2[]=$data_dt; }

                if (!empty($campos)) {
                    $sqlIns = "INSERT INTO ciPostos (" . implode(',', $campos) . ") VALUES (" . implode(',', $vals2) . ")";
                    $stIns = $pdo->prepare($sqlIns);
                    $stIns->execute($pars2);
                }
            } catch (Exception $e) {
                // não bloqueia: ciPostos pode não existir/ter outro schema
            }

            $results[] = array(
                'codbar'   => $codbar,
                'success'  => true,
                'lote'     => $lote_str,
                'regional' => $regional_str,
                'posto'    => $posto_str,
                'qtd'      => $qtd_int,
                'data_ref' => $data_ref,
                'usuario'  => $usuario_nome,
                'turno'    => $turno
            );
        }

        $pdo = null;
        die(json_encode(array(
            'success' => true,
            'results' => $results
        )));
    }

// v9.0: Busca REGIONAL e ENTREGA de ciRegionais (fonte da verdade)
    $postosInfo = array(); // posto => array('regional' => X, 'entrega' => 'poupatempo'/'correios'/null)
    $sql = "SELECT LPAD(posto,3,'0') AS posto, 
                   CAST(regional AS UNSIGNED) AS regional,
                   LOWER(TRIM(REPLACE(entrega,' ',''))) AS entrega
            FROM ciRegionais 
            LIMIT 1000";
    $stmt = $pdo->query($sql);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $posto_pad = $r['posto'];
        $regional_real = (int)$r['regional'];
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

    
    // v9.8.8.1: Mapa opcional de nomes de posto (para narração). Não quebra se coluna/tabela não existir.
    $postosNome = array(); // posto(3) => nome
    $colNomePosto = null;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM ciPostosCsv")->fetchAll(PDO::FETCH_COLUMN, 0);
        $candidatos = array('numeroenomedoposto','nomeposto','nome_posto','posto_nome','descricao_posto');
        foreach ($candidatos as $c) {
            if (in_array($c, $cols, true)) { $colNomePosto = $c; break; }
        }
        if ($colNomePosto) {
            $sqlN = "SELECT LPAD(posto,3,'0') AS posto, MAX(`" . $colNomePosto . "`) AS nome
                     FROM ciPostosCsv
                     WHERE `" . $colNomePosto . "` IS NOT NULL AND TRIM(`" . $colNomePosto . "`) <> ''
                     GROUP BY LPAD(posto,3,'0')
                     LIMIT 2000";
            $stN = $pdo->query($sqlN);
            while ($rn = $stN->fetch(PDO::FETCH_ASSOC)) {
                $p3 = $rn['posto'];
                $nm = trim((string)$rn['nome']);
                if ($p3 !== '' && $nm !== '') { $postosNome[$p3] = $nm; }
            }
        }
    } catch (Exception $e) {
        // silencioso
        $postosNome = array();
    }

// Busca conferências já realizadas (com LIMIT)
// v9.8.7: conf pode estar 's' ou 'S' e a coluna de data/hora pode variar
    if ($HAS_LIDO_EM && $HAS_CODBAR && !empty($CONF_TS_COL)) {
        $tsCol = '`' . str_replace('`','', $CONF_TS_COL) . '`';
        $stmt = $pdo->query("SELECT nlote, regional, nposto, " . $tsCol . " AS lido_em, codbar FROM conferencia_pacotes WHERE UPPER(TRIM(conf))='S' LIMIT 50000");
    } elseif ($HAS_LIDO_EM && !empty($CONF_TS_COL)) {
        $tsCol = '`' . str_replace('`','', $CONF_TS_COL) . '`';
        $stmt = $pdo->query("SELECT nlote, regional, nposto, " . $tsCol . " AS lido_em FROM conferencia_pacotes WHERE UPPER(TRIM(conf))='S' LIMIT 50000");
    } else {
        $stmt = $pdo->query("SELECT nlote, regional, nposto FROM conferencia_pacotes WHERE UPPER(TRIM(conf))='S' LIMIT 50000");
    }
$mostrar_apenas_pendentes = (!$userProvidedFilter);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nlote_raw = isset($row['nlote']) ? trim((string)$row['nlote']) : '';
        $regional_raw = isset($row['regional']) ? trim((string)$row['regional']) : '';
        $posto_raw = isset($row['nposto']) ? trim((string)$row['nposto']) : '';

        $nlote_pad = str_pad($nlote_raw, 8, '0', STR_PAD_LEFT);
        $regional_pad = str_pad($regional_raw, 3, '0', STR_PAD_LEFT);
        $posto_pad = str_pad($posto_raw, 3, '0', STR_PAD_LEFT);

        $base = array(
            'conf'   => 1,
            'lido_em'=> ($HAS_LIDO_EM && isset($row['lido_em'])) ? $row['lido_em'] : '',
            'codbar' => ($HAS_CODBAR && isset($row['codbar'])) ? $row['codbar'] : ''
        );

        // Chaves robustas para compatibilidade entre versões/bases:
        // - Com regional + posto (padrão)
        // - Sem padding
        // - Sem regional (fallback)
        $keys = array();
        $keys[] = $nlote_pad . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $posto_pad;

        // Se houver codbar, deriva lote/regional/posto diretamente do código (mais confiável)
        if ($HAS_CODBAR && !empty($base['codbar'])) {
            $cb = preg_replace('/\D+/', '', (string)$base['codbar']);
            if (strlen($cb) >= 14) { // 8 (lote) + 3 (regional) + 3 (posto)
                $lote_cb = substr($cb, 0, 8);
                $reg_cb  = substr($cb, 8, 3);
                $pst_cb  = substr($cb, 11, 3);

                $lote_cb_pad = str_pad($lote_cb, 8, '0', STR_PAD_LEFT);
                $reg_cb_pad  = str_pad($reg_cb, 3, '0', STR_PAD_LEFT);
                $pst_cb_pad  = str_pad($pst_cb, 3, '0', STR_PAD_LEFT);

                $keys[] = $lote_cb_pad . '|' . $reg_cb_pad . '|' . $pst_cb_pad;
                $keys[] = $lote_cb_pad . '|' . $pst_cb_pad;
            }
        }

        foreach ($keys as $k) {
            $conferencias[$k] = $base;
        }
    }

    // =========================================================
    // v9.8.4: Filtro AVANÇADO (único) + carregamento automático
    //
    // Regras:
    // - Ao carregar a página (sem parâmetros), escolhe automaticamente um período com produção
    //   onde ainda NÃO existe conferência registrada em conferencia_pacotes (conf='s').
    // - O usuário pode escolher:
    //   (a) Intervalo (dt_ini / dt_fim) OU
    //   (b) Dias alternados em "datas_custom" (lista separada por vírgula/space/;).
    //
    // Observação:
    // - dt_ini/dt_fim são preenchidos automaticamente com o período "pendente".
    // =========================================================

    $modo_filtro = 'auto'; // auto | intervalo | especificas
    $datas_sql = array();
    $range_ini_sql = normalizarDataSQL($dt_ini);
    $range_fim_sql = normalizarDataSQL($dt_fim);

    // Dias específicos digitados (aceita separador: vírgula/ponto e vírgula/espaço)
    if (trim($datas_custom) !== '') {
        $modo_filtro = 'especificas';
        $tmp = preg_split('/[\s,;]+/', $datas_custom);
        foreach ($tmp as $token) {
            $token = trim($token);
            if ($token === '') continue;
            $dsql = normalizarDataSQL($token);
            if ($dsql !== '') {
                $datas_sql[] = $dsql;
            } else {
                // tenta interpretar dd-mm-aaaa
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $token)) {
                    $p = explode('-', $token);
                    $datas_sql[] = $p[2] . '-' . $p[1] . '-' . $p[0];
                }
            }
        }
        $datas_sql = array_values(array_unique($datas_sql));
    }

    // Intervalo (se informado e NÃO estiver em modo "dias específicos")
    if ($modo_filtro !== 'especificas' && ($range_ini_sql !== '' || $range_fim_sql !== '')) {
        $modo_filtro = 'intervalo';
        if ($range_ini_sql === '' && $range_fim_sql !== '') $range_ini_sql = $range_fim_sql;
        if ($range_fim_sql === '' && $range_ini_sql !== '') $range_fim_sql = $range_ini_sql;
        if ($range_ini_sql !== '' && $range_fim_sql !== '' && $range_ini_sql > $range_fim_sql) {
            // inverte se usuário digitar ao contrário
            $tmp = $range_ini_sql;
            $range_ini_sql = $range_fim_sql;
            $range_fim_sql = $tmp;
        }
    }

    // Carregamento automático (quando usuário NÃO informou nada)
    // v9.8.4e: LÓGICA LEVE (não faz NOT EXISTS correlacionado em cima da ciPostosCsv inteira)
    // Regra:
    // - Por padrão, carregar o(s) dia(s) mais recente(s) com produção que ainda NÃO possuem nenhuma conferência (conf='s') registrada.
    // - Se a produção mais recente já estiver conferida (ou se todas estiverem), carrega apenas a última data de produção.
    if ($modo_filtro === 'auto') {
        $diasSemConferencia = array();
        $ultimaProducao = '';

        try {
            // Últimas datas de produção (limite 20)
            $diasProducao = array();
            $stD = $pdo->query("SELECT DISTINCT DATE_FORMAT(dataCarga, '%Y-%m-%d') AS dia
                                FROM ciPostosCsv
                                WHERE dataCarga IS NOT NULL
                                ORDER BY dataCarga DESC
                                LIMIT 20");
            while ($rd = $stD->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($rd['dia'])) $diasProducao[] = $rd['dia'];
            }
            $stD = null;

            if (!empty($diasProducao)) {
                $ultimaProducao = $diasProducao[0];
            }

            // Verifica se existe conferência (conf='s') por DIA (sem join pesado)
            $stHas = $pdo->prepare("SELECT 1
                                    FROM conferencia_pacotes
                                    WHERE UPPER(TRIM(conf))='S'
                                      AND dataexp = ?
                                      AND dataexp IS NOT NULL
                                      AND dataexp <> '0000-00-00'
                                    LIMIT 1");

            foreach ($diasProducao as $dia) {
                $stHas->execute(array($dia));
                $has = $stHas->fetch(PDO::FETCH_NUM);
                $stHas->closeCursor();

                if (!$has) {
                    // enquanto não houver conferência, consideramos "pendente"
                    $diasSemConferencia[] = $dia;
                } else {
                    // assim que encontramos um dia que JÁ tem conferência:
                    // - se já coletamos algum dia pendente, paramos (assumimos pendência consecutiva recente)
                    // - se ainda não coletamos nada, significa que a data mais recente já foi conferida -> fallback para última data
                    break;
                }
            }
            $stHas = null;
        } catch (Exception $e) {
            $diasSemConferencia = array();
        }

        if (!empty($diasSemConferencia)) {
            sort($diasSemConferencia);
            $range_ini_sql = $diasSemConferencia[0];
            $range_fim_sql = $diasSemConferencia[count($diasSemConferencia)-1];
            $dt_ini = $range_ini_sql; // preenche inputs type=date
            $dt_fim = $range_fim_sql;
            $modo_filtro = 'intervalo';
        } else {
            // fallback: última data de produção
            if ($ultimaProducao === '') {
                try {
                    $stL = $pdo->query("SELECT DATE_FORMAT(MAX(dataCarga), '%Y-%m-%d') AS dia FROM ciPostosCsv WHERE dataCarga IS NOT NULL");
                    $rL = $stL->fetch(PDO::FETCH_ASSOC);
                    $stL = null;
                    if ($rL && !empty($rL['dia'])) $ultimaProducao = $rL['dia'];
                } catch (Exception $e) {
                    $ultimaProducao = date('Y-m-d');
                }
            }
            $range_ini_sql = $ultimaProducao;
            $range_fim_sql = $ultimaProducao;
            $dt_ini = $ultimaProducao;
            $dt_fim = $ultimaProducao;
            $modo_filtro = 'intervalo';
        }
    }



    // =========================================================
    // v9.8.2: Totais de retiradas/agilizações (ciRetirada)
    // Regras:
    // - 1 protocolo = 1 carteira
    // - Total canceladas = status=0 (ajuste se seu padrão for outro)
    // =========================================================
    try {
        if ($modo_filtro === 'intervalo' && $range_ini_sql !== '' && $range_fim_sql !== '') {
            $stR = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) AS canceladas, GROUP_CONCAT(DISTINCT LEFT(protocolo,3) ORDER BY LEFT(protocolo,3) SEPARATOR ', ') AS postos
                                  FROM ciRetirada
                                  WHERE datasolicitacao IS NOT NULL
                                  AND DATE(datasolicitacao) BETWEEN ? AND ?");
            $stR->execute(array($range_ini_sql, $range_fim_sql));
            $rr = $stR->fetch(PDO::FETCH_ASSOC);
            if ($rr) {
                $total_retiradas = (int)$rr['total'];
                $total_canceladas = (int)$rr['canceladas'];
                $postos_retirados_lista = isset($rr['postos']) ? $rr['postos'] : '';
            }
        } elseif (!empty($datas_sql)) {
            $ph = implode(',', array_fill(0, count($datas_sql), '?'));
            $stR = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) AS canceladas, GROUP_CONCAT(DISTINCT LEFT(protocolo,3) ORDER BY LEFT(protocolo,3) SEPARATOR ', ') AS postos
                                  FROM ciRetirada
                                  WHERE datasolicitacao IS NOT NULL
                                  AND DATE(datasolicitacao) IN ($ph)");
            $stR->execute($datas_sql);
            $rr = $stR->fetch(PDO::FETCH_ASSOC);
            if ($rr) {
                $total_retiradas = (int)$rr['total'];
                $total_canceladas = (int)$rr['canceladas'];
                $postos_retirados_lista = isset($rr['postos']) ? $rr['postos'] : '';
            }
        }
    } catch (Exception $e) {
        // se não existir ciRetirada ou a coluna, mantém 0
        $total_retiradas = 0;
        $total_canceladas = 0;
    }

    
    // =========================================================
    
    // =========================================================
    // v9.8.3: Total de carteiras EXPEDIDAS (db: servico / tbl_ci_filadeimpressao)
    // Objetivo: comparar o total de carteiras "na tela" com o total expedido no período filtrado.
    //
    // IMPORTANTE (ajuste v9.8.3c):
    // - O fechamento diário (valor "final" de expedidas) acontece por volta de 02:00 da madrugada.
    // - Esse fechamento de um dia D costuma ser gravado com datafila no dia D+1 (por volta de 02:00).
    // - Portanto, para o filtro do dia D, devemos consultar a JANELA (em datafila) do dia D+1 ao redor de 02:00.
    //
    // Estratégia:
    // - Usa apenas registros com tipoentrega IS NULL (linha total do dia)
    // - Para cada "dia de fechamento" (D+1), pega MAX(expedidas) dentro da janela horária
    // =========================================================
    try {
        // Janela de captura do fechamento (ajuste se precisar)
        $JANELA_INICIO = '01:30:00'; // margem antes das 02:00
        $JANELA_FIM    = '05:00:00'; // margem depois das 02:00

        // Só conecta no banco "servico" se houver filtro aplicável
        $temPeriodo = false;
        $paramsE = array();
        $whereE = '';

        if ($modo_filtro === 'intervalo' && $range_ini_sql !== '' && $range_fim_sql !== '') {
            $temPeriodo = true;
            // Para dia D (produção), consulta fechamento em D+1
            $iniExp = date('Y-m-d', strtotime($range_ini_sql . ' +1 day'));
            $fimExp = date('Y-m-d', strtotime($range_fim_sql . ' +1 day'));

            $whereE = "DATE(datafila) BETWEEN ? AND ?
                       AND TIME(datafila) BETWEEN ? AND ?";
            $paramsE = array($iniExp, $fimExp, $JANELA_INICIO, $JANELA_FIM);

        } elseif (!empty($datas_sql)) {
            $temPeriodo = true;

            // Converte lista de dias D para dias de fechamento D+1
            $datasExp = array();
            foreach ($datas_sql as $d) {
                $d = trim($d);
                if ($d === '') continue;
                $datasExp[] = date('Y-m-d', strtotime($d . ' +1 day'));
            }

            if (!empty($datasExp)) {
                $phE = implode(',', array_fill(0, count($datasExp), '?'));
                $whereE = "DATE(datafila) IN ($phE)
                           AND TIME(datafila) BETWEEN ? AND ?";
                $paramsE = array_merge($datasExp, array($JANELA_INICIO, $JANELA_FIM));
            } else {
                $temPeriodo = false;
            }
        }

        if ($temPeriodo && $whereE !== '') {
            $pdo_servico = new PDO("mysql:host=$host;dbname=servico;charset=utf8mb4", $user, $pass);
            $pdo_servico->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlE = "SELECT DATE(datafila) AS dia_fechamento, MAX(expedidas) AS expedidas
                     FROM tbl_ci_filadeimpressao
                     WHERE tipoentrega IS NULL
                     AND $whereE
                     GROUP BY DATE(datafila)";
            $stE = $pdo_servico->prepare($sqlE);
            $stE->execute($paramsE);

            while ($re = $stE->fetch(PDO::FETCH_ASSOC)) {
                $total_expedidas_periodo += (int)$re['expedidas'];
            }
            $stE = null;
            $pdo_servico = null;
        }
    } catch (Exception $e) {
        // Se a base/tabla não existir, mantém 0 e não quebra a página
        $total_expedidas_periodo = 0;
    }

// =========================================================
    // Busca dados do ciPostosCsv (com LIMIT)
    // =========================================================
    if ($modo_filtro === 'intervalo' && $range_ini_sql !== '' && $range_fim_sql !== '') {
        $sql = "SELECT lote, posto, regional, quantidade, dataCarga, usuario
                FROM ciPostosCsv
                WHERE dataCarga IS NOT NULL
                AND DATE(dataCarga) BETWEEN ? AND ?
                ORDER BY regional, lote, posto
                LIMIT 3000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($range_ini_sql, $range_fim_sql));
    } elseif (!empty($datas_sql)) {
        $placeholders = implode(',', array_fill(0, count($datas_sql), '?'));
        $sql = "SELECT lote, posto, regional, quantidade, dataCarga, usuario
                FROM ciPostosCsv
                WHERE dataCarga IS NOT NULL
                AND DATE(dataCarga) IN ($placeholders)
                ORDER BY regional, lote, posto
                LIMIT 3000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($datas_sql);
    } else {
        $stmt = null;
    }

    if ($stmt) {

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (empty($row['dataCarga'])) continue;

                $data_formatada = date('d-m-Y', strtotime($row['dataCarga']));
                $data_sql = date('Y-m-d', strtotime($row['dataCarga']));

                $lote = $row['lote'];
                $posto = str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
                $regional_csv = (int)$row['regional']; // Regional do CSV (para código de barras)
                $regional_str = str_pad($regional_csv, 3, '0', STR_PAD_LEFT);
                $quantidade_int = (int)$row['quantidade'];
                $usuario_lote = isset($row['usuario']) ? trim($row['usuario']) : '';
                $total_carteiras += $quantidade_int;
                $quantidade = str_pad($quantidade_int, 5, '0', STR_PAD_LEFT);

                $codigo_barras = $lote . $regional_str . $posto . $quantidade;
                
                // v9.0: Usa informações CORRETAS de ciRegionais
                $regional_real = isset($postosInfo[$posto]) ? $postosInfo[$posto]['regional'] : $regional_csv;
                $tipoEntrega = isset($postosInfo[$posto]) ? $postosInfo[$posto]['entrega'] : null;
                $isPT = ($tipoEntrega == 'poupatempo') ? 1 : 0;

                // v9.3: Poupa Tempo usa próprio posto como regional na exibição
                $regional_exibida = ($isPT == 1) ? $posto : str_pad($regional_real, 3, '0', STR_PAD_LEFT); // v9.8.8.1: usa regional REAL (ciRegionais) na exibição
                $posto_nome = isset($postosNome[$posto]) ? $postosNome[$posto] : '';



                // Verifica se já foi conferido (normalizando chave)
                $lote_pad = str_pad($lote, 8, '0', STR_PAD_LEFT);
                $posto_pad = str_pad($posto, 3, '0', STR_PAD_LEFT);

                // regional_str vem do CSV (parte do código de barras)
                $regional_pad_csv = str_pad($regional_str, 3, '0', STR_PAD_LEFT);

                // regional_exibida pode ser o posto (Poupa Tempo) ou a regional do código de barras
                $regional_pad_exib = str_pad($regional_exibida, 3, '0', STR_PAD_LEFT);

                $keysToTry = array(
                    // padrão (usa regional exibida)
                    $lote_pad . '|' . $regional_pad_exib . '|' . $posto_pad,
                    $lote . '|' . $regional_pad_exib . '|' . $posto_pad,

                    // fallback: regional do CSV (se a exibida divergir por regra de tela)
                    $lote_pad . '|' . $regional_pad_csv . '|' . $posto_pad,
                    $lote . '|' . $regional_pad_csv . '|' . $posto_pad,

                    // fallback final: ignora regional (compatibilidade com versões antigas)
                    $lote_pad . '|' . $posto_pad,
                    $lote . '|' . $posto_pad
                );

                $confRow = null;
                foreach ($keysToTry as $kTry) {
                    if (isset($conferencias[$kTry])) { $confRow = $conferencias[$kTry]; break; }
                }

                $conferido = ($confRow !== null) ? 1 : 0;
                if ($mostrar_apenas_pendentes && $conferido == 1) { continue; }

                $lido_em_fmt = '';
                if ($conferido && is_array($confRow) && !empty($confRow['lido_em'])) {
                    // v9.8.8: formata sem conversão de fuso
                    $lido_em_fmt = formatarLidoEmBR($confRow['lido_em']);
                }
                // v9.0: Agrupa por REGIONAL REAL (de ciRegionais)
                if (!isset($regionais_data[$regional_real])) {
                    $regionais_data[$regional_real] = array();
                }


                $regionais_data[$regional_real][] = array(
                    'lote' => $lote,
                    'posto' => $posto,
                    'posto_nome' => $posto_nome,
                    'regional' => $regional_exibida,
                    'tipoEntrega' => $tipoEntrega,
                    'data' => $data_formatada,
                    'data_sql' => $data_sql,
                    'qtd' => ltrim($quantidade, '0'),
                    'usuario' => $usuario_lote,
                    'codigo' => $codigo_barras,
                    'lido_em' => $lido_em_fmt,
                    'isPT' => $isPT,
                    'conf' => $conferido
                );

                $total_codigos++;
            }
    }

    // v9.8.3: fecha conexão principal (controle) após carregar todos os dados
    $pdo = null;
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conferência de Pacotes v9.8.8.1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h2 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        h3 { 
            color: #555; 
            margin: 30px 0 10px; 
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
        .radio-box input { margin-right: 10px; width: 18px; height: 18px; cursor: pointer; }
        
        .filtro-datas { 
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filtro-datas form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filtro-datas label { margin-right: 10px; cursor: pointer; }
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
        
        .tag-pt {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
        }
        
        .sec-divider{
            margin: 26px 0 10px;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 900;
            letter-spacing: .5px;
            display:flex;
            align-items:center;
            gap:10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .sec-divider .dot{
            width:10px; height:10px; border-radius:999px; display:inline-block;
        }
        .sec-pt{ background:#ffe4e6; color:#9f1239; border:1px solid #fecdd3; }
        .sec-pt .dot{ background:#e11d48; }
        .sec-cor{ background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
        .sec-cor .dot{ background:#2563eb; }
.versao {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
    
        /* === v9.8.7b: destaque diferença + diagnóstico === */
        .diff-alert {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 8px;
            background: #FDE68A; /* amarelo */
            color: #B91C1C;      /* vermelho */
            font-weight: 900;
            border: 2px solid rgba(0,0,0,0.15);
        }
        .diff-alert.neg { color: #92400E; } /* âmbar escuro p/ negativo */
        .diff-alert.ok  { background: transparent; border: none; color: #065F46; font-weight: 800; }
        .pulse-diff { animation: pulseDiff 1.2s infinite; }
        @keyframes pulseDiff {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.65); }
            70% { transform: scale(1.03); box-shadow: 0 0 0 12px rgba(245, 158, 11, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        details.diag {
            margin: 14px 0 0 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
        }
        details.diag summary {
            cursor: pointer;
            font-weight: 800;
            color: #111827;
        }
        .diag-grid { margin-top: 10px; display: grid; grid-template-columns: 1fr 140px; gap: 8px 12px; }
        .diag-item { padding: 8px 10px; border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
        .diag-status { padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; font-weight: 800; text-align: center; }
        .diag-ok { background: #dcfce7; color: #065f46; }
        .diag-bad { background: #fee2e2; color: #991b1b; }
        .diag-wait { background: #fef9c3; color: #854d0e; }

</style>
</head>
<body>
<div class="versao">v9.8.8.1</div>

<h2>📋 Conferência de Pacotes v9.8.8.1</h2>

<!-- Radio Auto-Save -->
<div class="radio-box">
    <label>
        <input type="radio" id="autoSalvar" checked>
        Auto-salvar conferências durante leitura
    </label>
</div>

<!-- Filtro avançado (v9.8.4) -->
<div class="filtro-datas">
    <form method="get" action="">
        <strong>📅 Filtro avançado:</strong>

        <div style="margin-top:10px; padding:10px; border:1px solid #e5e7eb; border-radius:6px; background:#fff;">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div>
                    <label style="font-weight:700;">Data inicial</label><br>
                    <input type="date" name="dt_ini" value="<?php echo htmlspecialchars($dt_ini); ?>">
                </div>
                <div>
                    <label style="font-weight:700;">Data final</label><br>
                    <input type="date" name="dt_fim" value="<?php echo htmlspecialchars($dt_fim); ?>">
                </div>
                <div style="flex:1; min-width:260px;">
                    <label style="font-weight:700;">Dias alternados (opcional)</label><br>
                    <input type="text" name="datas_custom" value="<?php echo htmlspecialchars($datas_custom); ?>" placeholder="Ex: 15/12/2025, 19/12/2025, 20/12/2025" style="width:100%;">
                </div>
                <div>
                    <input type="submit" value="🔍 Aplicar Filtro">
                </div>
            </div>

            <div style="margin-top:8px; font-size:12px; color:#6b7280;">
                Ao abrir a página sem filtro, ela carrega automaticamente o período com produção que ainda não possui conferência registrada.
            </div>
        </div>
    </form>
</div>

<?php
    $diff_expedidas = (int)$total_expedidas_periodo - (int)$total_carteiras;

    if ($diff_expedidas > 0) {
        $diff_label = '<span class="diff-alert pulse-diff">⚠️ Diferença: +' . number_format($diff_expedidas, 0, ',', '.') . ' (faltando pacote(s))</span>';
    } elseif ($diff_expedidas < 0) {
        $diff_label = '<span class="diff-alert neg pulse-diff">ℹ️ Diferença: ' . number_format($diff_expedidas, 0, ',', '.') . '</span>';
    } else {
        $diff_label = '<span class="diff-alert ok">✅ Diferença: 0</span>';
    }

    $postos_retirados_label = ($postos_retirados_lista !== '') ? $postos_retirados_lista : '-';

// === v9.8.7bb: heurística de "usuários possivelmente ausentes" (últimos 60 dias vs período atual) ===
$usuarios_periodo = array();
$usuarios_60d = array();
$usuarios_ausentes = array();

try {
    // Abre uma conexão curta só para esta heurística (a conexão principal já foi fechada após carregar os dados)
    $pdo_tmp = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo_tmp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // período atual
    if ($dt_ini !== '' && $dt_fim !== '') {
        $dt_ini_q = $dt_ini . ' 00:00:00';
        $dt_fim_q = $dt_fim . ' 23:59:59';
        $stU = $pdo_tmp->prepare("SELECT DISTINCT usuario
                                  FROM ciPostosCsv
                                  WHERE dataCarga BETWEEN ? AND ?
                                    AND usuario IS NOT NULL
                                    AND usuario <> ''");
        $stU->execute(array($dt_ini_q, $dt_fim_q));
        $usuarios_periodo = $stU->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // últimos 60 dias a partir da última produção
    $maxData = $pdo_tmp->query("SELECT MAX(dataCarga) AS mx FROM ciPostosCsv")->fetch(PDO::FETCH_ASSOC);
    $mx = isset($maxData['mx']) ? $maxData['mx'] : null;
    if ($mx) {
        $st60 = $pdo_tmp->prepare("SELECT DISTINCT usuario
                                   FROM ciPostosCsv
                                   WHERE dataCarga BETWEEN DATE_SUB(?, INTERVAL 60 DAY) AND ?
                                     AND usuario IS NOT NULL
                                     AND usuario <> ''");
        $st60->execute(array($mx, $mx));
        $usuarios_60d = $st60->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // ausentes = (últimos 60 dias) - (período atual)
    $mapPeriodo = array();
    foreach ($usuarios_periodo as $u) { $mapPeriodo[trim($u)] = true; }
    foreach ($usuarios_60d as $u) {
        $uu = trim($u);
        if ($uu !== '' && !isset($mapPeriodo[$uu])) $usuarios_ausentes[] = $uu;
    }

    $pdo_tmp = null;
} catch (Exception $e) {
    // silencioso (não quebra a página)
}


// v9.8.7: formatação de números (milhar BR)
function fmtIntBr($n) {
    return number_format((float)$n, 0, ',', '.');
}
?>
<div class="info">
    📦 Total de pacotes: <strong><?php echo fmtIntBr($total_codigos); ?></strong>
    &nbsp; | &nbsp; 🪪 Total de carteiras: <strong><?php echo fmtIntBr($total_carteiras); ?></strong>
    &nbsp; | &nbsp; 🚚 Total expedidas: <strong><?php echo fmtIntBr($total_expedidas_periodo); ?></strong>
    &nbsp; | &nbsp; <?php echo $diff_label; ?>
    &nbsp; | &nbsp; ⚡ Total de retiradas: <strong><?php echo fmtIntBr($total_retiradas); ?></strong>
    &nbsp; | &nbsp; 🏷️ Postos retirados: <strong><?php echo htmlspecialchars($postos_retirados_label); ?></strong>
    &nbsp; | &nbsp; ❌ Canceladas: <strong><?php echo fmtIntBr($total_canceladas); ?></strong>
</div>



<details class="diag">
  <summary>🕵️ Possíveis “esquecedores de upload” (heurística)</summary>
  <div style="margin-top:8px; color:#334155; font-size:13px;">
    Lista quem fez upload nos últimos 60 dias, mas <strong>não aparece</strong> no período atualmente carregado. Não prova culpa — só ajuda a investigar.
  </div>
  <div style="margin-top:10px; font-size:13px;">
    <div><strong>No período:</strong> <?php echo htmlspecialchars(implode(', ', $usuarios_periodo)); ?></div>
    <div style="margin-top:6px;"><strong>Últimos 60 dias:</strong> <?php echo htmlspecialchars(implode(', ', $usuarios_60d)); ?></div>
    <div style="margin-top:10px; padding:10px 12px; border-radius:10px; background:#fef9c3; border:1px solid #fde68a;">
      <strong>Possíveis ausentes:</strong>
      <?php echo ($usuarios_ausentes && count($usuarios_ausentes)>0) ? htmlspecialchars(implode(', ', $usuarios_ausentes)) : '<em>nenhum</em>'; ?>
    </div>
  </div>
</details>

<div>
    <input type="text" id="codigo_barras" placeholder="Escaneie o código de barras (19 dígitos)" maxlength="19" autofocus>
    <button id="resetar">🔄 Resetar Conferência</button>
</div>




<!-- Tabelas Agrupadas -->
<div id="tabelas">
<?php
// ========================================
// v9.8.6a: Renderização das tabelas (agrupadas) restaurada
// ========================================

$grupo_pt = array();           // Poupa Tempo (uma lista)
$grupo_r01 = array();          // Regional 01
$grupo_capital = array();      // Capital (regional 0)
$grupo_999 = array();          // Central IIPR (regional 999)
$grupo_outros = array();       // Demais regionais

foreach ($regionais_data as $regional => $postos) {
    foreach ($postos as $posto) {
        // 1) Poupa Tempo
        if ($posto['tipoEntrega'] === 'poupatempo') {
            $grupo_pt[] = $posto;
            continue;
        }
        // 2) Regional 01
        if ((int)$regional === 1) {
            $grupo_r01[] = $posto;
            continue;
        }
        // 3) Capital (regional REAL = 0)
        if ((int)$regional === 0) {
            $grupo_capital[] = $posto;
            continue;
        }
        // 4) Central IIPR (regional REAL = 999)
        if ((int)$regional === 999) {
            $grupo_999[] = $posto;
            continue;
        }
        // 5) Outras
        if (!isset($grupo_outros[$regional])) {
            $grupo_outros[$regional] = array();
        }
        $grupo_outros[$regional][] = $posto;
    }
}
ksort($grupo_outros);

// Render tabela (aceita lista plana OU array aninhado)
function renderizarTabela($titulo, $dados, $ehPoupaTempo = false) {
    if (empty($dados)) { return; }

    $primeiro = reset($dados);
    $eh_array_plano = is_array($primeiro) && isset($primeiro['lote']);

    $postos_para_exibir = array();
    if ($eh_array_plano) {
        $postos_para_exibir = $dados;
    } else {
        foreach ($dados as $regional => $postos) {
            foreach ($postos as $posto) { $postos_para_exibir[] = $posto; }
        }
    }

    $total_pacotes = count($postos_para_exibir);
    $total_conferidos = 0;
    foreach ($postos_para_exibir as $p) { if ((int)$p['conf'] === 1) { $total_conferidos++; } }

    echo '<h3>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    echo ' <span style="color:#666; font-weight:normal; font-size:14px;">(' . $total_pacotes . ' pacotes / ' . $total_conferidos . ' conferidos)</span>';
    if ($ehPoupaTempo) { echo ' <span class="tag-pt">POUPA TEMPO</span>'; }
    echo '</h3>';

    echo '<table>';
    echo '<thead><tr>';
    echo '<th>Regional</th>';
    echo '<th>Lote</th>';
    echo '<th>Posto</th>';
    echo '<th>Data Expedição</th>';
    echo '<th>Quantidade</th>';
    echo '<th>Feito por</th>';
    echo '<th>Código de Barras</th>';
    echo '<th>Conferido em</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($postos_para_exibir as $posto) {
        $classeConf = ((int)$posto['conf'] === 1) ? ' confirmado' : '';
        echo '<tr class="linha-conferencia' . $classeConf . '" '
            . 'data-codigo="' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '" '
            . 'data-regional="' . htmlspecialchars($posto['regional'], ENT_QUOTES, 'UTF-8') . '" '
            . 'data-lote="' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '" '
            . 'data-posto="' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '" ' . 'data-postonome="' . htmlspecialchars(isset($posto['posto_nome']) ? $posto['posto_nome'] : '', ENT_QUOTES, 'UTF-8') . '" '
            . 'data-data="' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '" '
            . 'data-qtd="' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '" '
            . 'data-usuario="' . htmlspecialchars(isset($posto['usuario']) ? $posto['usuario'] : '', ENT_QUOTES, 'UTF-8') . '" '
            . 'data-ispt="' . (int)$posto['isPT'] . '">';

        echo '<td>' . htmlspecialchars($posto['regional'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars(isset($posto['usuario']) ? $posto['usuario'] : '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td class="col-conferido-em">' . htmlspecialchars(isset($posto['lido_em']) ? $posto['lido_em'] : '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// Ordem de exibição (v9.8.7: divisores visuais Poupa Tempo x Correios)
$tem_poupa = !empty($grupo_pt);
$tem_correios = (!empty($grupo_r01) || !empty($grupo_capital) || !empty($grupo_999) || !empty($grupo_outros));

if ($tem_poupa) {
    echo '<div class="sec-divider sec-pt"><span class="dot"></span>INÍCIO — POUPA TEMPO</div>';
    renderizarTabela('Postos do Poupa Tempo', $grupo_pt, true);
}

if ($tem_correios) {
    echo '<div class="sec-divider sec-cor"><span class="dot"></span>INÍCIO — CORREIOS</div>';
    if (!empty($grupo_r01)) { renderizarTabela('Postos da Regional 01', $grupo_r01); }
    if (!empty($grupo_capital)) { renderizarTabela('Postos da Capital', $grupo_capital); }
    if (!empty($grupo_999)) { renderizarTabela('Postos da Central IIPR', $grupo_999); }

    if (!empty($grupo_outros)) {
        foreach ($grupo_outros as $regional => $postos) {
            $regionalStr = str_pad($regional, 3, '0', STR_PAD_LEFT);
            renderizarTabela($regionalStr . ' - Regional ' . $regionalStr, array($regional => $postos));
        }
    }
}

if (empty($regionais_data)) {
    echo '<p style="text-align:center; margin-top:40px; color:#999;">Nenhum dado encontrado para as datas selecionadas.</p>';
}
?>
</div>

<!-- Áudio para pacote não encontrado / não carregado -->
<audio id="audioNaoEncontrado" src="pacotenaocarregado.mp3" preload="auto"></audio>
<audio id="audioPacoteNaoCarregado" src="pacotenaocarregado.mp3" preload="auto"></audio>


<!-- Painel: Pacotes não carregados (barcode não encontrado na lista) -->
<div id="boxNaoCarregado" style="display:none; margin-top:18px; padding:12px; border:2px dashed #ef4444; border-radius:10px; background:#fff7ed;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <h3 style="margin:0; color:#b91c1c;">📦 Pacote(s) NÃO carregado(s) (não encontrados na lista)</h3>
        <button type="button" id="nc_fechar" style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:6px; cursor:pointer;">Fechar</button>
    </div>

    <div style="margin-top:10px; font-size:13px; color:#7c2d12;">
        Escaneie o código de barras do pacote que <strong>não apareceu na tela</strong>. Ele ficará em uma lista pendente.
        Depois você ajusta <strong>Data</strong>, <strong>Turno</strong> e <strong>Feito por</strong> e salva <strong>individualmente</strong> ou <strong>em lote</strong>.
    </div>

    <div style="margin-top:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div style="width:360px;">
            <label style="font-weight:700;">Último código lido</label><br>
            <input type="text" id="nc_codbar" placeholder="19 dígitos" style="width:100%; font-family:monospace;">
        </div>

        <div>
            <label style="font-weight:700;">Data (padrão)</label><br>
            <input type="date" id="nc_data" value="<?php echo htmlspecialchars($dt_fim); ?>">
        </div>

        <div>
            <label style="font-weight:700;">Turno (padrão)</label><br>
            <select id="nc_turno">
                <option value="1">Manhã</option>
                <option value="2" selected>Tarde</option>
                <option value="3">Noite</option>
                <option value="4">Madrugada</option>
            </select>
        </div>

        <div style="min-width:220px;">
            <label style="font-weight:700;">Feito por (padrão)</label><br>
            <input type="text" id="nc_usuario" placeholder="nome/usuário" style="width:100%;">
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" id="nc_aplicar" style="padding:8px 10px; border:1px solid #f59e0b; background:#fff; border-radius:8px; cursor:pointer;">🧩 Aplicar padrão a todos</button>
            <button type="button" id="nc_salvar_todos" style="padding:8px 10px; border:1px solid #10b981; background:#10b981; color:#fff; border-radius:8px; cursor:pointer;">💾 Salvar pendências</button>
            <button type="button" id="nc_limpar" style="padding:8px 10px; border:1px solid #ef4444; background:#fff; color:#ef4444; border-radius:8px; cursor:pointer;">🧹 Limpar lista</button>
        </div>
    </div>
    <div style="margin-top:6px; font-size:12px; color:#6b7280;">Dica: pressione <strong>Enter</strong> para adicionar à lista.</div>

    <div id="nc_msg" style="margin-top:10px; font-size:13px;"></div>

    <div style="margin-top:12px; overflow:auto;">
        <table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5e7eb;">
            <thead>
                <tr style="background:#f3f4f6; color:#111;">
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Código</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Lote</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Regional</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Posto</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Qtd</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Data</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Turno</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Feito por</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Status</th>
                    <th style="padding:8px; border-bottom:1px solid #e5e7eb;">Ações</th>
                </tr>
            </thead>
            <tbody id="nc_lista"></tbody>
        </table>
    </div>
</div>

<!-- Áudios -->
<audio id="beep" src="beep.mp3" preload="auto"></audio>
<audio id="concluido" src="concluido.mp3" preload="auto"></audio>
<audio id="pacotejaconferido" src="pacotejaconferido.mp3" preload="auto"></audio>
<audio id="pacotedeoutraregional" src="pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="posto_poupatempo" src="posto_poupatempo.mp3" preload="auto"></audio>

<script>
// v9.8.2: utilitário para limpar filtro avançado
function limparFiltroAvancado() {
    try {
        var ini = document.querySelector('input[name="dt_ini"]');
        var fim = document.querySelector('input[name="dt_fim"]');
        var custom = document.querySelector('input[name="datas_custom"]');
        if (ini) ini.value = '';
        if (fim) fim.value = '';
        if (custom) custom.value = '';
    } catch (e) {}
}

// ========================================
// v9.2: JavaScript com lógica inteligente de sons
// ========================================

function substituirMultiplosPadroes(inputString) {
    var stringProcessada = inputString;
    
    // Regra 1: Substituir "755" por "779" quando seguido por 5 dígitos
    var regex755 = /(\d{11})(755)(\d{5})/g;
    if (regex755.test(stringProcessada)) {
        stringProcessada = stringProcessada.replace(regex755, function(match, p1, p2) {
            return "779" + p2;
        });
    }
    
    // Regra 2: Substituir "500" por "507" quando seguido por 5 dígitos
    var regex500 = /(\d{11})(500)(\d{5})/g;
    if (regex500.test(stringProcessada)) {
        stringProcessada = stringProcessada.replace(regex500, function(match, p1, p2) {
            return "507" + p2;
        });
    }
    
    return stringProcessada;
}

document.addEventListener("DOMContentLoaded", function() {
    var input = document.getElementById("codigo_barras");
    var radioAutoSalvar = document.getElementById("autoSalvar");
    var beep = document.getElementById("beep");
    var concluido = document.getElementById("concluido");
    var pacoteJaConferido = document.getElementById("pacotejaconferido");
    var pacoteOutraRegional = document.getElementById("pacotedeoutraregional");
    var postoPoupaTempo = document.getElementById("posto_poupatempo");
    var btnResetar = document.getElementById("resetar");
    
    // v9.2: Variáveis de contexto para sons inteligentes
    var regionalAtual = null;
    var tipoAtual = null; // 'poupatempo' ou 'correios'
    var primeiroConferido = false;
    
    input.focus();

    // v9.8.8: Narração (TTS) quando for pacote de outra regional (CORREIOS)
    function falarPacoteOutraRegional(regional, posto, nomePosto) {
        try {
            if (!('speechSynthesis' in window)) return;
            var regStr = (regional === null || regional === undefined) ? "" : String(regional).trim();
            var postoStr = (posto === null || posto === undefined) ? "" : String(posto).trim();

            // Normaliza posto para 3 dígitos (ex: 45 -> 045)
            if (/^\d+$/.test(postoStr)) postoStr = postoStr.padStart(3, '0');
            // Regional pode ter 3 dígitos também (opcional)
            if (/^\d+$/.test(regStr)) regStr = regStr.padStart(3, '0');

            // Fala posto dígito a dígito para ficar claro (ex: "0 4 5")
            function digitosSeparados(s) {
                return (s || "").split("").join(" ");
            }

            // Cancela falas anteriores para não acumular
            window.speechSynthesis.cancel();

            var nomeStr = (nomePosto === null || nomePosto === undefined) ? "" : String(nomePosto).trim();
            var texto = "Regional " + regStr + ", posto " + digitosSeparados(postoStr);
            if (nomeStr) {
                // tenta falar só a parte do nome (sem o número do posto repetido)
                texto += ", " + nomeStr;
            }
            texto += ".";
            var u = new SpeechSynthesisUtterance(texto);
            u.lang = "pt-BR";
            u.rate = 1.0;
            u.pitch = 1.0;
            window.speechSynthesis.speak(u);
        } catch (e) {
            // silencioso
        }
    }

    
    // Função para salvar conferência via AJAX
    function salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, onDone) {
        var formData = new FormData();
        formData.append('salvar_lote_ajax', '1');
        formData.append('lote', lote);
        formData.append('regional', regional);
        formData.append('posto', posto);
        formData.append('dataexp', dataexp);
        formData.append('qtd', qtd);
        formData.append('codbar', codbar);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (!data || !data.sucesso) {
                console.error('Erro ao salvar:', data && data.erro ? data.erro : data);
                if (typeof onDone === 'function') { onDone({success:false, data:data}); }
                return;
            }
            if (typeof onDone === 'function') { onDone({success:true, lido_em: data.lido_em || null}); }
        })
        .catch(function(error) {
            console.error('Erro AJAX:', error);
        });
    }
    
    // Scanner de código de barras
    input.addEventListener("input", function() {
        var valor = input.value.trim();
        
        if (valor.length !== 19) {
            return;
        }
        
        // Aplicar transformações opcionais
        // valor = substituirMultiplosPadroes(valor);
        
        var linha = document.querySelector('tr[data-codigo="' + valor + '"]');
        
        
        // v9.8.6: Se não encontrou, adiciona à fila de "não carregados"
        function tocarNaoEncontrado() {
            try {
                var a1 = document.getElementById('audioPacoteNaoCarregado');
                var a2 = document.getElementById('audioNaoEncontrado');
                if (a1 && a1.play) { a1.currentTime = 0; a1.play(); return; }
                if (a2 && a2.play) { a2.currentTime = 0; a2.play(); }
            } catch (e) {}
        }

        function parseCodbar(c) {
            var s = (c || '').replace(/\D/g,'');
            if (s.length !== 19) return null;
            return {
                codbar: s,
                lote: s.substr(0,8),
                regional: s.substr(8,3),
                posto: s.substr(11,3),
                qtd: parseInt(s.substr(14,5),10)
            };
        }

        function turnoLabel(v) {
            v = String(v);
            if (v === '1') return 'Manhã';
            if (v === '2') return 'Tarde';
            if (v === '3') return 'Noite';
            if (v === '4') return 'Madrugada';
            return v;
        }

        // Estado global da fila (não recria a cada evento)
        window.__ncPendentes = window.__ncPendentes || [];
        window.__ncSeq = window.__ncSeq || 1;

        function ncFindById(id) {
            for (var i=0; i<window.__ncPendentes.length; i++) {
                if (String(window.__ncPendentes[i].id) === String(id)) return window.__ncPendentes[i];
            }
            return null;
        }

        function ncRender() {
            var tb = document.getElementById('nc_lista');
            if (!tb) return;

            tb.innerHTML = '';
            for (var i=0; i<window.__ncPendentes.length; i++) {
                var it = window.__ncPendentes[i];

                var tr = document.createElement('tr');
                tr.setAttribute('data-id', it.id);
                tr.style.borderBottom = '1px solid #e5e7eb';

                function td(html) {
                    var d = document.createElement('td');
                    d.style.padding = '8px';
                    d.innerHTML = html;
                    return d;
                }

                var statusHtml = '';
                if (it.status === 'OK') statusHtml = '<span style="color:#065f46;font-weight:800;">Salvo</span>';
                else if (it.status === 'ERRO') statusHtml = '<span style="color:#b91c1c;font-weight:800;" title="'+(it.msg||'')+'">Erro</span>';
                else statusHtml = '<span style="color:#92400e;font-weight:800;">Pendente</span>';

                tr.appendChild(td('<code style="font-size:12px;">'+it.codbar+'</code>'));
                tr.appendChild(td('<strong>'+it.lote+'</strong>'));
                tr.appendChild(td(it.regional));
                tr.appendChild(td(it.posto));
                tr.appendChild(td('<strong>'+it.qtd+'</strong>'));

                tr.appendChild(td('<input type="date" data-id="'+it.id+'" data-field="data_ref" value="'+(it.data_ref||'')+'" style="width:140px;">'));
                tr.appendChild(td('<select data-id="'+it.id+'" data-field="turno">'
                    +'<option value="1" '+(String(it.turno)==='1'?'selected':'')+'>Manhã</option>'
                    +'<option value="2" '+(String(it.turno)==='2'?'selected':'')+'>Tarde</option>'
                    +'<option value="3" '+(String(it.turno)==='3'?'selected':'')+'>Noite</option>'
                    +'<option value="4" '+(String(it.turno)==='4'?'selected':'')+'>Madrugada</option>'
                    +'</select>'));
                tr.appendChild(td('<input type="text" data-id="'+it.id+'" data-field="usuario" value="'+(it.usuario||'')+'" placeholder="nome/usuário" style="width:180px;">'));
                tr.appendChild(td(statusHtml));
                tr.appendChild(td(
                    '<button type="button" class="nc-save" data-id="'+it.id+'" style="padding:6px 8px; border:1px solid #10b981; background:#10b981; color:#fff; border-radius:8px; cursor:pointer;">Salvar</button>'
                    +'&nbsp;'
                    +'<button type="button" class="nc-del" data-id="'+it.id+'" style="padding:6px 8px; border:1px solid #ef4444; background:#fff; color:#ef4444; border-radius:8px; cursor:pointer;">Remover</button>'
                ));

                tb.appendChild(tr);
            }
        }

        function ncAdd(cod) {
            var p = parseCodbar(cod);
            if (!p) return false;

            // evita duplicar o mesmo código
            for (var i=0; i<window.__ncPendentes.length; i++) {
                if (window.__ncPendentes[i].codbar === p.codbar) return true;
            }

            var d = (document.getElementById('nc_data') && document.getElementById('nc_data').value) ? document.getElementById('nc_data').value : '';
            var t = (document.getElementById('nc_turno') && document.getElementById('nc_turno').value) ? document.getElementById('nc_turno').value : '2';
            var u = (document.getElementById('nc_usuario') && document.getElementById('nc_usuario').value) ? document.getElementById('nc_usuario').value : '';

            p.id = window.__ncSeq++;
            p.data_ref = d;
            p.turno = t;
            p.usuario = u;
            p.status = 'PENDENTE';
            p.msg = '';

            window.__ncPendentes.unshift(p);
            ncRender();
            return true;
        }

        function ncSetMsg(html, ok) {
            var el = document.getElementById('nc_msg');
            if (!el) return;
            el.innerHTML = html || '';
            el.style.color = ok ? '#065f46' : '#b91c1c';
            el.style.fontWeight = '700';
        }

        function ncAplicarDefaults() {
            var d = document.getElementById('nc_data') ? document.getElementById('nc_data').value : '';
            var t = document.getElementById('nc_turno') ? document.getElementById('nc_turno').value : '2';
            var u = document.getElementById('nc_usuario') ? document.getElementById('nc_usuario').value : '';

            for (var i=0; i<window.__ncPendentes.length; i++) {
                var it = window.__ncPendentes[i];
                if (it.status === 'OK') continue;
                if (d) it.data_ref = d;
                if (t) it.turno = t;
                if (u) it.usuario = u;
            }
            ncRender();
            ncSetMsg('✅ Padrões aplicados aos pendentes.', true);
        }

        function ncSalvarLote(itens) {
            if (!itens || !itens.length) {
                ncSetMsg('Nada para salvar.', false);
                return;
            }

            // valida mínimos
            for (var i=0; i<itens.length; i++) {
                if (!itens[i].data_ref) { ncSetMsg('Informe a <strong>data</strong> para todos os pendentes (ou use "Aplicar padrão").', false); return; }
                if (!itens[i].usuario)  { ncSetMsg('Informe o <strong>feito por</strong> para todos os pendentes (ou use "Aplicar padrão").', false); return; }
            }

            var fd = new FormData();
            fd.append('inserir_nao_carregado_ajax', '1');
            fd.append('itens_json', JSON.stringify(itens));

            fetch(window.location.pathname, { method:'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (!data || !data.success) {
                        ncSetMsg('Erro ao salvar pendências.', false);
                        return;
                    }
                    var res = data.results || [];
                    for (var j=0; j<res.length; j++) {
                        var rj = res[j];
                        // acha item por codbar
                        for (var k=0; k<window.__ncPendentes.length; k++) {
                            if (window.__ncPendentes[k].codbar === rj.codbar) {
                                window.__ncPendentes[k].status = rj.success ? 'OK' : 'ERRO';
                                window.__ncPendentes[k].msg = rj.msg || '';
                            }
                        }
                    }
                    ncRender();
                    ncSetMsg('✅ Salvamento concluído. Recarregue a página para o pacote aparecer na lista.', true);
                })
                .catch(function(){
                    ncSetMsg('Erro de rede ao salvar pendências.', false);
                });
        }

        function ncSalvarTodos() {
            var pend = [];
            for (var i=0; i<window.__ncPendentes.length; i++) {
                var it = window.__ncPendentes[i];
                if (it.status !== 'OK') {
                    pend.push({codbar: it.codbar, data_ref: it.data_ref, turno: it.turno, usuario: it.usuario});
                }
            }
            ncSalvarLote(pend);
        }

        function ncSalvarUm(id) {
            var it = ncFindById(id);
            if (!it) return;
            ncSalvarLote([{codbar: it.codbar, data_ref: it.data_ref, turno: it.turno, usuario: it.usuario}]);
        }

        function ncRemover(id) {
            var out = [];
            for (var i=0; i<window.__ncPendentes.length; i++) {
                if (String(window.__ncPendentes[i].id) !== String(id)) out.push(window.__ncPendentes[i]);
            }
            window.__ncPendentes = out;
            ncRender();
        }

        function abrirNaoCarregado(cod) {
            var box = document.getElementById('boxNaoCarregado');
            if (!box) return;
            box.style.display = 'block';

            var inpCod = document.getElementById('nc_codbar');
            if (inpCod) inpCod.value = (cod || '').replace(/\D/g,'');

            // adiciona automaticamente na lista (se válido)
            ncAdd(cod);

            // limpa input principal para não travar leitura
            try { input.value = ''; } catch(e){}

            ncSetMsg('⚠️ Pacote não encontrado. Adicionado à lista de pendências.', false);
        }

        // Bind do painel (executa 1x)
        (function bindNaoCarregado(){
            var box = document.getElementById('boxNaoCarregado');
            if (!box || box.getAttribute('data-bound') === '1') return;
            box.setAttribute('data-bound','1');

            var btnFechar = document.getElementById('nc_fechar');
            var btnAplicar = document.getElementById('nc_aplicar');
            var btnSalvarTodos = document.getElementById('nc_salvar_todos');
            var btnLimpar = document.getElementById('nc_limpar');
            var inpCod = document.getElementById('nc_codbar');

            if (btnFechar) {
                btnFechar.addEventListener('click', function(){
                    box.style.display = 'none';
                    ncSetMsg('', true);
                });
            }

            if (btnAplicar) btnAplicar.addEventListener('click', ncAplicarDefaults);
            if (btnSalvarTodos) btnSalvarTodos.addEventListener('click', ncSalvarTodos);

            if (btnLimpar) {
                btnLimpar.addEventListener('click', function(){
                    if (!confirm('Limpar a lista de pendências?')) return;
                    window.__ncPendentes = [];
                    ncRender();
                    ncSetMsg('Lista limpa.', true);
                });
            }

            if (inpCod) {
                inpCod.addEventListener('keydown', function(e){
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var v = inpCod.value.trim();
                        if (v.length === 19) {
                            ncAdd(v);
                            inpCod.value = '';
                            ncSetMsg('Adicionado à lista.', true);
                        } else {
                            ncSetMsg('Código inválido (19 dígitos).', false);
                        }
                    }
                });
            }

            // Delegação de eventos no tbody
            var tb = document.getElementById('nc_lista');
            if (tb) {
                tb.addEventListener('click', function(e){
                    var t = e.target || e.srcElement;
                    if (!t) return;
                    if (t.className && t.className.indexOf('nc-save') !== -1) {
                        ncSalvarUm(t.getAttribute('data-id'));
                    } else if (t.className && t.className.indexOf('nc-del') !== -1) {
                        ncRemover(t.getAttribute('data-id'));
                    }
                });

                tb.addEventListener('change', function(e){
                    var t = e.target || e.srcElement;
                    if (!t) return;
                    var id = t.getAttribute('data-id');
                    var field = t.getAttribute('data-field');
                    if (!id || !field) return;
                    var it = ncFindById(id);
                    if (!it) return;
                    it[field] = t.value;
                });
            }

            ncRender();
        })();

;

        
        if (!linha) {
            tocarNaoEncontrado();
            abrirNaoCarregado(valor);
            input.value = "";
            return;
        }
        
        var regionalDoPacote = linha.getAttribute("data-regional");
        var isPoupaTempo = linha.getAttribute("data-ispt") === "1";
        var tipoPacote = isPoupaTempo ? 'poupatempo' : 'correios';
        
        // Verifica se já foi conferido
        if (linha.classList.contains("confirmado")) {
            pacoteJaConferido.play();
            input.value = "";
            return;
        }
        
        // v9.2: Lógica inteligente de sons
        var somParaTocar = null;
        
        // Caso 1: Primeiro pacote da conferência - sempre beep
        if (!primeiroConferido) {
            somParaTocar = beep;
            regionalAtual = regionalDoPacote;
            tipoAtual = tipoPacote;
            primeiroConferido = true;
        }
        // Caso 2: Pacote Poupa Tempo aparecendo em meio aos Correios
        else if (tipoAtual === 'correios' && tipoPacote === 'poupatempo') {
            somParaTocar = postoPoupaTempo; // Alerta: PT misturado com correios!
            // NÃO altera regionalAtual nem tipoAtual - continua conferindo correios
        }
        // Caso 3: Pacote Correios aparecendo em meio ao Poupa Tempo
        else if (tipoAtual === 'poupatempo' && tipoPacote === 'correios') {
            somParaTocar = pacoteOutraRegional; // Alerta: correios no meio do PT!
            // NÃO altera regionalAtual nem tipoAtual
        }
        // Caso 4: Regional diferente (mesmo tipo)
        else if (regionalDoPacote !== regionalAtual && tipoPacote === tipoAtual) {

            // POUPA TEMPO: é normal avançar para outra "regional/posto" sem alerta.
            // Mantém o fluxo contínuo (não toca pacote de outra regional).
            if (tipoPacote === 'poupatempo') {
                somParaTocar = beep;
                regionalAtual = regionalDoPacote;
            } else {
                // CORREIOS: só alerta se ainda houver pendentes na regionalAtual.
                // Se a regional atual já acabou, troca automaticamente para a nova.
                var selPend = 'tr.linha-conferencia:not([data-ispt="1"])[data-regional="' + regionalAtual + '"]:not(.confirmado)';
                var pendentes = document.querySelectorAll(selPend);
                if (!pendentes || pendentes.length === 0) {
                    somParaTocar = beep;
                    regionalAtual = regionalDoPacote;
                } else {
                    somParaTocar = pacoteOutraRegional; // Alerta: regional diferente!
                }
            }
        }
        // Caso 5: Pacote correto (mesma regional, mesmo tipo)
        else {
            somParaTocar = beep; // Tudo certo!
        }
        
        // Marca como conferido
        linha.classList.add("confirmado");
        // v9.8: Preenche coluna "Conferido em" na hora
        var celConferidoEm = linha.querySelector("td.col-conferido-em");
        if (celConferidoEm) {
            try {
                celConferidoEm.textContent = "...";
            } catch (e) {
                celConferidoEm.textContent = "";
            }
        }
        
        // Toca o som apropriado
        if (somParaTocar) {
            try { somParaTocar.currentTime = 0; } catch (e) {}
            try { somParaTocar.play(); } catch (e) {}

            // v9.8.8.1: Se for CORREIOS e tocar "pacote de outra regional",
            // toca o mp3 primeiro e SÓ DEPOIS narra regional + posto (+ nome do posto, se existir).
            if (somParaTocar === pacoteOutraRegional && tipoAtual === 'correios') {
                var postoDoPacote = linha.getAttribute("data-posto");
                var nomeDoPacote = linha.getAttribute("data-postonome") || "";

                try {
                    // limpa listeners/timeout anteriores
                    if (window.__onOutraRegEnded) {
                        pacoteOutraRegional.removeEventListener('ended', window.__onOutraRegEnded);
                        window.__onOutraRegEnded = null;
                    }
                    if (window.__ttsOutraRegTimer) {
                        clearTimeout(window.__ttsOutraRegTimer);
                        window.__ttsOutraRegTimer = null;
                    }

                    var fired = false;
                    window.__onOutraRegEnded = function () {
                        if (fired) return;
                        fired = true;

                        try { pacoteOutraRegional.removeEventListener('ended', window.__onOutraRegEnded); } catch (e) {}
                        window.__onOutraRegEnded = null;

                        if (window.__ttsOutraRegTimer) {
                            clearTimeout(window.__ttsOutraRegTimer);
                            window.__ttsOutraRegTimer = null;
                        }

                        falarPacoteOutraRegional(regionalDoPacote, postoDoPacote, nomeDoPacote);
                    };

                    pacoteOutraRegional.addEventListener('ended', window.__onOutraRegEnded);
                    // fallback caso o evento 'ended' não dispare (browser/política de áudio)
                    window.__ttsOutraRegTimer = setTimeout(window.__onOutraRegEnded, 1800);
                } catch (e) {
                    setTimeout(function () {
                        falarPacoteOutraRegional(regionalDoPacote, postoDoPacote, nomeDoPacote);
                    }, 1200);
                }
            }
        }


input.value = "";
        
        // Centraliza a linha na tela
        linha.scrollIntoView({ behavior: "smooth", block: "center" });
        
        // Salvar no banco se auto-save estiver ativo
        if (radioAutoSalvar.checked) {
            var lote = linha.getAttribute("data-lote");
            var regional = linha.getAttribute("data-regional");
            var posto = linha.getAttribute("data-posto");
            var dataexp = linha.getAttribute("data-data");
            var qtd = linha.getAttribute("data-qtd");
            var codbar = linha.getAttribute("data-codigo");
            
            salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, function(resp) {
                try {
                    if (resp && resp.success && resp.lido_em) {
                        var cel = linha.querySelector('td.col-conferido-em');
                        if (cel) {
                            // v9.8.8: lido_em já vem formatado do banco (pt-BR) - não converter no navegador
                            cel.textContent = resp.lido_em;
                        }
                    }
                } catch (e) {}
            });
}
        
        // v9.2: Verifica se completou o GRUPO atual (PT, Capital, R01, 999, ou outra regional)
        var grupoAtual = null;
        var todasLinhas = document.querySelectorAll('tbody tr');
        var linhasDoGrupo = [];
        
        // Determina qual grupo está sendo conferido
        if (tipoAtual === 'poupatempo') {
            grupoAtual = 'poupatempo';
            // Todas as linhas PT
            for (var i = 0; i < todasLinhas.length; i++) {
                if (todasLinhas[i].getAttribute('data-ispt') === '1') {
                    linhasDoGrupo.push(todasLinhas[i]);
                }
            }
        } else {
            grupoAtual = regionalAtual;
            // Todas as linhas da regional atual que NÃO sejam PT
            for (var i = 0; i < todasLinhas.length; i++) {
                if (todasLinhas[i].getAttribute('data-regional') === regionalAtual && 
                    todasLinhas[i].getAttribute('data-ispt') !== '1') {
                    linhasDoGrupo.push(todasLinhas[i]);
                }
            }
        }
        
        // Conta quantos foram conferidos
        var conferidosDoGrupo = 0;
        for (var j = 0; j < linhasDoGrupo.length; j++) {
            if (linhasDoGrupo[j].classList.contains('confirmado')) {
                conferidosDoGrupo++;
            }
        }
        
        // Se completou o grupo, toca concluído e reseta contexto
        if (conferidosDoGrupo === linhasDoGrupo.length && linhasDoGrupo.length > 0) {
            setTimeout(function() {
                concluido.play();
            }, 300); // Pequeno delay para não sobrepor com o beep
            regionalAtual = null;
            tipoAtual = null;
            primeiroConferido = false;
        }
    });
    
    // Resetar conferência
    btnResetar.addEventListener("click", function() {
        if (confirm("Tem certeza que deseja reiniciar a conferência? Isso irá APAGAR todos os dados conferidos do banco!")) {
            // Obter datas filtradas
            var checkboxes = document.querySelectorAll('.filtro-datas input[type="checkbox"]:checked');
            var datas = [];
            
            for (var i = 0; i < checkboxes.length; i++) {
                datas.push(checkboxes[i].value);
            }
            
            // Resetar visualmente
            var trsConfirmados = document.querySelectorAll("tr.confirmado");
            for (var j = 0; j < trsConfirmados.length; j++) {
                trsConfirmados[j].classList.remove("confirmado");
            }
            
            regionalAtual = null;
            tipoAtual = null; // v9.2: Reseta tipo
            primeiroConferido = false; // v9.2: Reseta flag
            input.value = "";
            input.focus();

                
            // Excluir do banco via AJAX
            if (datas.length > 0) {
                var formData = new FormData();
                formData.append('excluir_lote_ajax', '1');
                formData.append('datas', datas.join(','));
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.sucesso) {
                        alert('Conferências resetadas com sucesso!');
                    } else {
                        console.error('Erro ao resetar:', data.erro);
                    }
                })
                .catch(function(error) {
                    console.error('Erro AJAX:', error);
                });
            }
        }
    });
});
</script>




</body>
</html>