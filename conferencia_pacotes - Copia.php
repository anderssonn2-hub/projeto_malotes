<?php
/* conferencia_pacotes.php — v0.9.25.3
 * CHANGELOG v9.24.2:
 * - [CORRIGIDO] Pacotes nao listados salvam no ciPostosCsv ao adicionar
 * - [NOVO] Aviso "Pacote de outra data" quando filtro nao inclui o pacote
 *
 * CHANGELOG v9.24.1:
 * - [NOVO] Escolha obrigatoria de tipo apos informar responsavel
 * - [NOVO] Tela inicial separada (inicio.php)
 *
 * CHANGELOG v9.24.0:
 * - [NOVO] Postos bloqueados (nao enviar este posto) com audio dinamico
 * - [NOVO] Pacotes nao encontrados acumulados para salvar ao final
 * - [NOVO] Audio dinamico para pacote nao encontrado
 * - [MELHORIA] Layout responsivo para uso em celulares
 * - [AJUSTE] Status de conferencias recolhido no topo esquerdo
 * - [AJUSTE] Toggle deslizante para beep e rotulo de responsavel
 * - [MELHORIA] Banners para grupos Correios e Poupa Tempo
 *
 * CHANGELOG v9.23.6:
 * - [CORRIGIDO] Fallback por lote para linhas já conferidas
 *
 * CHANGELOG v9.23.5:
 * - [CORRIGIDO] Correspondência de conferência com/sem zeros à esquerda
 *
 * CHANGELOG v9.23.4:
 * - [CORRIGIDO] Linhas verdes por codbar e chave normalizada
 * - [CORRIGIDO] Não marca como conferido quando pacote é de outra regional/tipo
 * - [CORRIGIDO] Áudio pertence_aos_correios para PT selecionado
 *
 * CHANGELOG v9.23.3:
 * - [CORRIGIDO] Alerta PT no primeiro pacote quando tipo inicial é Correios
 * - [REMOVIDO] Card de últimas conferências
 * - [MANTIDO] Pacotes já conferidos em verde
 *
 * CHANGELOG v9.23.2:
 * - [NOVO] Inserção de pacotes não listados (ciPostosCsv + ciPostos)
 * - [NOVO] Seleção do tipo de conferência (Correios/PT)
 * - [NOVO] Alerta pertence_aos_correios.mp3 (Correios no meio de PT)
 * - [NOVO] Opção de silenciar beep.mp3
 * - [AJUSTE] Status de conferências no topo (não acompanha scroll)
 *
 * CHANGELOG v9.23.1:
 * - [NOVO] Bloqueio inicial até informar usuário
 * - [NOVO] Usuário exibido após liberação
 *
 * CHANGELOG v9.23.0:
 * - [NOVO] Usuário obrigatório para iniciar conferência
 * - [NOVO] Card Status de Conferências (últimas/pendentes)
 * - [CORRIGIDO] Salvamento de dataexp na conferência
 *
 * CHANGELOG v9.22.9:
 * - [CORRIGIDO] Inputs do filtro visíveis
 * - [MELHORADO] PT segregado por posto (concluido por grupo)
 *
 * CHANGELOG v9.22.8:
 * - [NOVO] Filtro por intervalo + datas avulsas
 * - [NOVO] Cards de resumo (carteiras, conferidas, postos)
 * - [NOVO] Lista das últimas conferências
 * - [MELHORADO] Scroll central e pulsação da última leitura
 * - [MELHORADO] Desbloqueio de áudio para beep
 *
 * CHANGELOG v9.22.7:
 * - [NOVO] Fila de áudio sem sobreposição
 * - [NOVO] beep.mp3 em toda leitura válida de código
 * - [NOVO] concluido.mp3 ao finalizar grupo (mesmo com 1 pacote)
 * - [NOVO] pacotedeoutraregional.mp3 para regional diferente
 * - [NOVO] posto_poupatempo.mp3 para PT no meio de correios (e PT único)
 * 
 * LÓGICA INTELIGENTE DE SONS BASEADA NO CONTEXTO:
 * - beep.mp3: toda leitura válida de código de barras
 * - posto_poupatempo.mp3: PT aparece enquanto confere correios (misturado!)
 * - pacotedeoutraregional.mp3: Regional diferente OU correios no meio do PT
 * - pacotejaconferido.mp3: Pacote já conferido
 * - concluido.mp3: Grupo completo conferido (PT/Capital/R01/999/regionais)
 * 
 * Agrupamento (fonte: ciRegionais):
 * 1. Postos do Poupa Tempo - UMA tabela (ciRegionais.entrega)
 * 2. Postos do Posto 01 - UMA tabela (ciRegionais.regional = 1, exceto PT)
 * 3. Postos da Capital - UMA tabela (ciRegionais.regional = 0)
 * 4. Postos da Central IIPR - UMA tabela (ciRegionais.regional = 999)
 * 5. Regionais - ordem crescente (100, 105, 200...)
 */

// Inicializa variáveis
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

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Conexão
$host = getenv('DB_HOST') ?: '10.15.61.169';
$dbname = getenv('DB_NAME') ?: 'controle';
$user = getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat');
$pass = getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // v9.24.0: Postos bloqueados
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

    // Handler AJAX salvar
    if (isset($_POST['salvar_lote_ajax'])) {
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
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }
        
        $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario) 
                VALUES (?, ?, ?, ?, ?, ?, 's', ?)
                ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar), dataexp=VALUES(dataexp), usuario=VALUES(usuario)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($regional, $lote, $posto, $dataexp, $qtd, $codbar, $usuario_conf));
        $stmt = null; // v8.17.4: Libera statement
        $pdo = null;  // v8.17.4: Fecha conexão
        die(json_encode(array('success' => true)));
    }

    // v9.23.2: Inserir pacotes não listados (ciPostosCsv + ciPostos)
    if (isset($_POST['inserir_pacotes_nao_listados'])) {
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
        $stmtCsv = $pdo->prepare("INSERT INTO ciPostosCsv (lote, posto, regional, quantidade, dataCarga, data, usuario) VALUES (?,?,?,?,?,NOW(),?)");
        $stmtPostos = $pdo->prepare("
            INSERT INTO ciPostos (posto, dia, quantidade, turno, regional, lote, autor, criado, situacao)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, 0)
        ");

        foreach ($pacotes as $p) {
            try {
                $lote = isset($p['lote']) ? trim($p['lote']) : '';
                $posto = isset($p['posto']) ? trim($p['posto']) : '';
                $regional = isset($p['regional']) ? trim($p['regional']) : '';
                $quantidade = isset($p['quantidade']) ? (int)$p['quantidade'] : 0;
                $dataexp = isset($p['dataexp']) ? trim($p['dataexp']) : '';
                $usuario_pacote = isset($p['responsavel']) ? trim($p['responsavel']) : '';
                if ($usuario_pacote === '') {
                    $usuario_pacote = $usuario_conf;
                }

                if ($lote === '' || $posto === '' || $regional === '' || $quantidade <= 0 || $dataexp === '') {
                    throw new Exception('Campos obrigatorios ausentes');
                }

                if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $dataexp, $m)) {
                    $data_sql = $m[3] . '-' . $m[2] . '-' . $m[1];
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataexp)) {
                    $data_sql = $dataexp;
                } else {
                    throw new Exception('Data invalida');
                }

                $stmtCsv->execute(array($lote, $posto, $regional, $quantidade, $data_sql, $usuario_pacote));

                $nome_posto = sprintf('%03d - POSTO', (int)$posto);
                $criado = $data_sql . ' 10:10:10';
                $stmtPostos->execute(array(
                    $nome_posto,
                    $data_sql,
                    $quantidade,
                    'M',
                    (int)$lote,
                    $usuario_pacote,
                    $criado
                ));
                $ok++;
            } catch (Exception $ex) {
                $erros[] = $ex->getMessage();
            }
        }

        $stmtCsv = null;
        $stmtPostos = null;
        $pdo = null;
        die(json_encode(array('success' => true, 'inseridos' => $ok, 'erros' => $erros)));
    }

    // v9.24.2: Verificar se pacote existe em outra data
    if (isset($_POST['verificar_pacote_data'])) {
        $codbar = isset($_POST['codbar']) ? preg_replace('/\D+/', '', $_POST['codbar']) : '';
        $datasFiltro = array();
        if (isset($_POST['datas_sql'])) {
            $tmp = json_decode($_POST['datas_sql'], true);
            if (is_array($tmp)) { $datasFiltro = $tmp; }
        }

        if (strlen($codbar) !== 19) {
            die(json_encode(array('success' => false, 'status' => 'invalido')));
        }

        $lote = substr($codbar, 0, 8);
        $regional = substr($codbar, 8, 3);
        $posto = substr($codbar, 11, 3);

        $status = 'nao_encontrado';
        $dataEncontrada = '';

        try {
            if (!empty($datasFiltro)) {
                $ph = implode(',', array_fill(0, count($datasFiltro), '?'));
                $sqlCheck = "SELECT COUNT(*) FROM ciPostosCsv WHERE lote = ? AND regional = ? AND posto = ? AND DATE(dataCarga) IN ($ph)";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $params = array_merge(array($lote, $regional, $posto), $datasFiltro);
                $stmtCheck->execute($params);
                $existeNaData = (int)$stmtCheck->fetchColumn();
                if ($existeNaData > 0) {
                    $status = 'na_data';
                }
            }

            if (empty($datasFiltro)) {
                $stmtAny = $pdo->prepare("SELECT DATE(dataCarga) as data FROM ciPostosCsv WHERE lote = ? AND regional = ? AND posto = ? ORDER BY dataCarga DESC LIMIT 1");
                $stmtAny->execute(array($lote, $regional, $posto));
                $rowAny = $stmtAny->fetch(PDO::FETCH_ASSOC);
                if ($rowAny && !empty($rowAny['data'])) {
                    $status = 'na_data';
                }
            } elseif ($status !== 'na_data') {
                $stmtAny = $pdo->prepare("SELECT DATE(dataCarga) as data FROM ciPostosCsv WHERE lote = ? AND regional = ? AND posto = ? ORDER BY dataCarga DESC LIMIT 1");
                $stmtAny->execute(array($lote, $regional, $posto));
                $rowAny = $stmtAny->fetch(PDO::FETCH_ASSOC);
                if ($rowAny && !empty($rowAny['data'])) {
                    $status = 'outra_data';
                    $dataEncontrada = $rowAny['data'];
                }
            }
        } catch (Exception $ex) {
            $status = 'erro';
        }

        die(json_encode(array('success' => true, 'status' => $status, 'data' => $dataEncontrada)));
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
        die(json_encode(array('success' => true)));
    }

    // v9.24.0: Salvar posto bloqueado
    if (isset($_POST['salvar_posto_bloqueado'])) {
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

    // v9.24.0: Excluir posto bloqueado
    if (isset($_POST['excluir_posto_bloqueado'])) {
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

    // Busca conferências já realizadas (sem LIMIT)
    $stmt = $pdo->query("SELECT nlote, regional, nposto, codbar FROM conferencia_pacotes WHERE conf='s'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nlote_raw = trim((string)$row['nlote']);
        $regional_raw = trim((string)$row['regional']);
        $posto_raw = trim((string)$row['nposto']);

        $nlote_pad = str_pad($nlote_raw, 8, '0', STR_PAD_LEFT);
        $regional_pad = str_pad($regional_raw, 3, '0', STR_PAD_LEFT);
        $posto_pad = str_pad($posto_raw, 3, '0', STR_PAD_LEFT);

        $keys = array();
        $keys[] = $nlote_pad . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $regional_pad . '|' . $posto_pad;
        $keys[] = $nlote_pad . '|' . $posto_pad;
        $keys[] = $nlote_raw . '|' . $posto_pad;

        if (!empty($row['codbar'])) {
            $cb = preg_replace('/\D+/', '', (string)$row['codbar']);
            if (strlen($cb) >= 14) {
                $lote_cb = substr($cb, 0, 8);
                $reg_cb = substr($cb, 8, 3);
                $pst_cb = substr($cb, 11, 3);
                $lote_cb_pad = str_pad($lote_cb, 8, '0', STR_PAD_LEFT);
                $reg_cb_pad = str_pad($reg_cb, 3, '0', STR_PAD_LEFT);
                $pst_cb_pad = str_pad($pst_cb, 3, '0', STR_PAD_LEFT);
                $keys[] = $lote_cb_pad . '|' . $reg_cb_pad . '|' . $pst_cb_pad;
                $keys[] = $lote_cb_pad . '|' . $pst_cb_pad;
            }
        }

        foreach ($keys as $k) {
            $conferencias[$k] = 1;
        }

        if ($nlote_raw !== '') {
            $conferencias_lote[$nlote_raw] = 1;
        }
        $conferencias_lote[$nlote_pad] = 1;
    }

    // v9.22.8: Normalizar datas (intervalo + avulsas)
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

    // v8.17.2: Se nenhum filtro, carrega APENAS última data (rápido)
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

    // Busca últimas 5 datas para seletor
    $stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(dataCarga, '%d-%m-%Y') as data 
                         FROM ciPostosCsv 
                         WHERE dataCarga IS NOT NULL 
                         ORDER BY dataCarga DESC 
                         LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $datas_expedicao[] = $row['data'];
    }

    // Busca dados do ciPostosCsv (com LIMIT)
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
                ORDER BY regional, lote, posto 
                LIMIT 3000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params_data);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (empty($row['dataCarga'])) continue;

                $data_formatada = date('d-m-Y', strtotime($row['dataCarga']));
                $data_sql_row = date('Y-m-d', strtotime($row['dataCarga']));

                $lote = str_pad($row['lote'], 8, '0', STR_PAD_LEFT);
                $posto = str_pad($row['posto'], 3, '0', STR_PAD_LEFT);
                $regional_csv = (int)$row['regional']; // Regional do CSV (para código de barras)
                $regional_str = str_pad($regional_csv, 3, '0', STR_PAD_LEFT);
                $quantidade = str_pad($row['quantidade'], 5, '0', STR_PAD_LEFT);

                $codigo_barras = $lote . $regional_str . $posto . $quantidade;
                
                // v9.0: Usa informações CORRETAS de ciRegionais
                $regional_real = isset($postosInfo[$posto]) ? $postosInfo[$posto]['regional'] : $regional_csv;
                $tipoEntrega = isset($postosInfo[$posto]) ? $postosInfo[$posto]['entrega'] : null;
                $isPT = ($tipoEntrega == 'poupatempo') ? 1 : 0;
                
                // Verifica se já foi conferido
                $lote_pad = str_pad($lote, 8, '0', STR_PAD_LEFT);
                $posto_pad = str_pad($posto, 3, '0', STR_PAD_LEFT);
                $regional_pad_csv = str_pad($regional_str, 3, '0', STR_PAD_LEFT);

                // v9.3: Poupa Tempo usa próprio posto como regional na exibição
                $regional_exibida = ($isPT == 1) ? $posto : $regional_str;
                $regional_pad_exib = str_pad($regional_exibida, 3, '0', STR_PAD_LEFT);

                $keysToTry = array(
                    $lote_pad . '|' . $regional_pad_exib . '|' . $posto_pad,
                    $lote . '|' . $regional_pad_exib . '|' . $posto_pad,
                    $lote_pad . '|' . $regional_pad_csv . '|' . $posto_pad,
                    $lote . '|' . $regional_pad_csv . '|' . $posto_pad,
                    $lote_pad . '|' . $posto_pad,
                    $lote . '|' . $posto_pad
                );

                $conferido = 0;
                foreach ($keysToTry as $kTry) {
                    if (isset($conferencias[$kTry])) { $conferido = 1; break; }
                }
                if ($conferido === 0) {
                    if (isset($conferencias_lote[$lote]) || isset($conferencias_lote[$lote_pad])) {
                        $conferido = 1;
                    }
                }

                // v9.0: Agrupa por REGIONAL REAL (de ciRegionais)
                if (!isset($regionais_data[$regional_real])) {
                    $regionais_data[$regional_real] = array();
                }


                $regionais_data[$regional_real][] = array(
                    'lote' => $lote,
                    'posto' => $posto,
                    'regional' => $regional_exibida,
                    'tipoEntrega' => $tipoEntrega,
                    'data' => $data_formatada,
                    'data_sql' => $data_sql_row,
                    'qtd' => ltrim($quantidade, '0'),
                    'codigo' => $codigo_barras,
                    'isPT' => $isPT,
                    'conf' => $conferido
                );

            $total_codigos++;
        }
    }

    sort($datas_expedicao);

    // v9.22.8: Montar datas exibidas para filtros/estatísticas
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

    // v9.22.8: Estatísticas
    $stats = array(
        'carteiras_emitidas' => 0,
        'carteiras_conferidas' => 0,
        'postos_conferidos' => 0,
        'pacotes_conferidos' => 0
    );

    // v9.23.0: Status de conferências (últimos 30 dias)
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
                1 => 'DOM',
                2 => 'SEG',
                3 => 'TER',
                4 => 'QUA',
                5 => 'QUI',
                6 => 'SEX',
                7 => 'SAB'
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
    } catch (Exception $e) {
        // ignore
    }

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

    // v9.24.0: Carregar postos bloqueados
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
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferência de Pacotes v0.9.25.3</title>
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
        .versao {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
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
        .painel-ultimas {
            background: #fff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .painel-ultimas ul { margin: 8px 0 0; padding-left: 18px; }
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .2s;
            border-radius: 999px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
        .overlay-tipo .card h3 { margin:0 0 10px 0; }
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
        .btn-secundario { background:#6c757d; color:#fff; }
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
    </style>
</head>
<body>
<div class="topo-status">
    <div class="versao">v0.9.25.3</div>
    <div id="indicador-dias" class="collapsed">
        <div class="indicador-header" onclick="toggleIndicadorDias()" title="Recolher/Expandir">
            <span>📅 Status de Conferências</span>
            <span class="indicador-toggle">▼</span>
        </div>
        <div class="indicador-conteudo">
            <div style="margin:10px 0;">
                <strong style="color:#28a745;font-size:12px;">✓ Últimas Conferências:</strong><br>
                <div style="margin-top:5px;">
                    <?php 
                    $ultimas_cinco = array_slice($dias_com_conferencia, 0, 5);
                    if (!empty($ultimas_cinco)) {
                        foreach ($ultimas_cinco as $data) {
                            $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                            $badge_label = !empty($label_dia) ? " <span class='badge-dia'>$label_dia</span>" : '';
                            echo '<span class="badge-data conferida">' . htmlspecialchars($data) . $badge_label . '</span>';
                        }
                    } else {
                        echo '<span style="color:#999;font-size:11px;">Nenhuma</span>';
                    }
                    ?>
                </div>
            </div>
            <div style="margin:10px 0;">
                <strong style="color:#ffc107;font-size:12px;">⚠ Conferências Pendentes:</strong><br>
                <div style="margin-top:5px;">
                    <?php 
                    $ultimas_pendentes = array_slice($dias_sem_conferencia, 0, 5);
                    if (!empty($ultimas_pendentes)) {
                        foreach ($ultimas_pendentes as $data) {
                            $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                            $badge_label = !empty($label_dia) ? " <span class='badge-dia'>$label_dia</span>" : '';
                            echo '<span class="badge-data pendente">' . htmlspecialchars($data) . $badge_label . '</span>';
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

<h2>📋 Conferência de Pacotes v0.9.25.3</h2>

<div class="overlay-usuario" id="overlayUsuario">
    <div class="card">
        <h3>👤 Informe o responsável</h3>
        <div style="font-size:12px; color:#666;">Obrigatório para iniciar a conferência.</div>
        <input type="text" id="usuario_conf_modal" placeholder="Digite o responsável" autocomplete="off">
        <button type="button" id="btnConfirmarUsuario">Confirmar</button>
    </div>
</div>

<div class="overlay-tipo" id="overlayTipo" style="display:none;">
    <div class="card">
        <h3>🎯 Tipo de conferência</h3>
        <div style="font-size:12px; color:#666;">Escolha para iniciar.</div>
        <button type="button" class="btn-opcao" data-tipo="correios">Correios</button>
        <button type="button" class="btn-opcao pt" data-tipo="poupatempo">Poupa Tempo</button>
    </div>
</div>

<div id="conteudoPagina" class="page-locked">

<!-- Barras no topo -->
<div class="barras-topo">
    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">👤 Responsável da conferência</div>
        <span class="usuario-badge" id="usuarioBadge">Não informado</span>
    </div>

    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">🎯 Tipo de conferência</div>
        <label style="gap:8px; margin-right:16px;">
            <input type="radio" name="tipo_inicio" value="correios" checked>
            Correios
        </label>
        <label style="gap:8px;">
            <input type="radio" name="tipo_inicio" value="poupatempo">
            Poupa Tempo
        </label>
    </div>

    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">🔔 Beep de leitura</div>
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

<!-- Filtro de datas -->
<div class="filtro-datas">
    <form method="get" action="">
        <strong>📅 Filtrar por intervalo:</strong>
        <div class="filtro-row">
            <input type="date" name="data_ini" value="<?php echo e($data_ini); ?>">
            <input type="date" name="data_fim" value="<?php echo e($data_fim); ?>">
            <input type="submit" value="🔍 Aplicar Filtro">
        </div>
        <label style="min-width:100%;">
            Datas avulsas (dd-mm-aaaa ou yyyy-mm-dd, separadas por vírgula):
            <input type="text" name="datas_avulsas" value="<?php echo e($datas_avulsas); ?>" style="width:100%; margin-top:4px;">
        </label>
    </form>
</div>

<div class="painel-bloqueio">
    <h4>🚫 Postos que nao devem ser enviados</h4>
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
    <strong>📥 Pacotes não listados</strong>
    <div style="margin-top:8px;">
        <table>
            <thead>
                <tr>
                    <th>Lote</th>
                    <th>Regional</th>
                    <th>Posto</th>
                    <th>Qtd</th>
                    <th>Data</th>
                    <th>Responsável</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="listaPacotesNovos"></tbody>
        </table>
    </div>
    <div style="margin-top:10px; display:flex; gap:8px;">
        <button type="button" class="btn-acao btn-salvar" id="btnSalvarPacotes">Salvar pacotes</button>
        <button type="button" class="btn-acao btn-cancelar" id="btnCancelarPacotes">Cancelar</button>
    </div>
</div>

<div class="modal-pacote" id="modalPacote">
    <div class="card">
        <h3>📦 Pacote não encontrado</h3>
        <div style="font-size:12px; color:#666;">Informe os dados para inserir nas bases.</div>
        <label>Código de barras</label>
        <input type="text" id="pacote_codbar" readonly>
        <label>Lote</label>
        <input type="text" id="pacote_lote">
        <label>Regional</label>
        <input type="text" id="pacote_regional">
        <label>Posto</label>
        <input type="text" id="pacote_posto">
        <label>Quantidade</label>
        <input type="number" id="pacote_qtd" min="1">
        <label>Data de expedição</label>
        <input type="date" id="pacote_dataexp">
        <label>Responsável</label>
        <input type="text" id="pacote_responsavel" placeholder="Opcional">
        <input type="hidden" id="pacote_idx" value="">
        <div style="margin-top:10px; display:flex; gap:8px;">
            <button type="button" class="btn-acao btn-salvar" id="btnAdicionarPacote">Adicionar</button>
            <button type="button" class="btn-acao btn-cancelar" id="btnCancelarPacote">Cancelar</button>
        </div>
    </div>
</div>

<button class="btn-toggle" type="button" onclick="var el=document.getElementById('painel-estatisticas'); el.style.display = (el.style.display==='none'?'block':'none');">
    📊 Mostrar/Ocultar Estatísticas
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
    <input type="text" id="codigo_barras" placeholder="Escaneie o código de barras (19 dígitos)" maxlength="19" autofocus>
    <button id="resetar">🔄 Resetar Conferência</button>
    <div class="mensagem-leitura" id="mensagemLeitura"></div>
</div>

<!-- Tabelas Agrupadas -->
<div id="tabelas">
<?php
// ========================================
// v9.0: AGRUPAMENTO USANDO DADOS DE ciRegionais
// Classificação baseada em regional e entrega REAIS
// ========================================


$grupo_pt = array();           // Poupa Tempo agrupado por posto
$grupo_r01 = array();          // Todos postos Regional 01 em UMA lista (excluindo PT)
$grupo_capital = array();      // Todos postos Capital em UMA lista
$grupo_999 = array();          // Todos postos Central IIPR em UMA lista
$grupo_outros = array();       // Regionais: array($regional => array de postos)

foreach ($regionais_data as $regional => $postos) {
    foreach ($postos as $posto) {
        // 1. Poupa Tempo (PRIORIDADE MÁXIMA - ex: posto 28, 80)
        if ($posto['tipoEntrega'] == 'poupatempo') {
            $postoKey = $posto['posto'];
            if (!isset($grupo_pt[$postoKey])) {
                $grupo_pt[$postoKey] = array();
            }
            $grupo_pt[$postoKey][] = $posto; // Agrupa por posto
            continue; // v8.17.5: NÃO classifica em outros grupos
        }
        // 2. Regional 01 (postos 01, 02, 27 - excluindo os que já foram para PT)
        if ($regional == 1) {
            $grupo_r01[] = $posto; // Adiciona direto na lista
            continue;
        }
        // 3. Capital (regional = 0)
        if ($regional == 0) {
            $grupo_capital[] = $posto; // Adiciona direto na lista
            continue;
        }
        // 4. Central IIPR (regional = 999)
        if ($regional == 999) {
            $grupo_999[] = $posto; // Adiciona direto na lista
            continue;
        }
        // 5. Demais regionais (serão ordenadas crescentemente)
        if (!isset($grupo_outros[$regional])) {
            $grupo_outros[$regional] = array();
        }
        $grupo_outros[$regional][] = $posto;
    }
}

// v8.17.5: Ordena demais regionais em ordem crescente
ksort($grupo_outros);

// v9.24.0: Banner por grupo
function renderizarBanner($texto, $classe) {
    echo '<div class="banner-grupo ' . $classe . '">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
}

// v8.17.5: Função para renderizar tabela (aceita array plano OU aninhado)
function renderizarTabela($titulo, $dados, $ehPoupaTempo = false, $ptGroup = '') {
    if (empty($dados)) {
        return;
    }
    
    // Verifica se é array plano (lista de postos) ou aninhado (regional => postos)
    $primeiro = reset($dados);
    $eh_array_plano = isset($primeiro['lote']); // Se tem 'lote', é um posto
    
    // Normaliza para formato de iteração
    $postos_para_exibir = array();
    if ($eh_array_plano) {
        // Array plano: já é lista de postos
        $postos_para_exibir = $dados;
    } else {
        // Array aninhado: achatar
        foreach ($dados as $regional => $postos) {
            foreach ($postos as $posto) {
                $postos_para_exibir[] = $posto;
            }
        }
    }
    
    // Conta total de pacotes e conferidos
    $total_pacotes = count($postos_para_exibir);
    $total_conferidos = 0;
    foreach ($postos_para_exibir as $posto) {
        if ($posto['conf'] == 1) {
            $total_conferidos++;
        }
    }
    
    echo '<h3>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    echo ' <span style="color:#666; font-weight:normal; font-size:14px;">(' . $total_pacotes . ' pacotes / ' . $total_conferidos . ' conferidos)</span>';
    if ($ehPoupaTempo) {
        echo ' <span class="tag-pt">POUPA TEMPO</span>';
    }
    echo '</h3>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>Regional</th>';
    echo '<th>Lote</th>';
    echo '<th>Posto</th>';
    echo '<th>Data Expedição</th>';
    echo '<th>Quantidade</th>';
    echo '<th>Código de Barras</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($postos_para_exibir as $posto) {
        $classeConf = ($posto['conf'] == 1) ? ' confirmado' : '';
        echo '<tr class="linha-conferencia' . $classeConf . '" ';
        echo 'data-codigo="' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-regional="' . htmlspecialchars($posto['regional'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-lote="' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-posto="' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-data="' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '" ';
        $data_sql_attr = isset($posto['data_sql']) ? $posto['data_sql'] : '';
        echo 'data-data-sql="' . htmlspecialchars($data_sql_attr, ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-qtd="' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-ispt="' . $posto['isPT'] . '" ';
        echo 'data-pt-group="' . htmlspecialchars($ptGroup, ENT_QUOTES, 'UTF-8') . '">';
        echo '<td>' . htmlspecialchars($posto['regional'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}

// v8.17.4: Renderizar na ordem correta (cada grupo = UMA tabela)
$banner_correios_exibido = false;
$banner_pt_exibido = false;
if (!empty($grupo_pt)) {
    ksort($grupo_pt);
    if (!$banner_pt_exibido) {
        renderizarBanner('POSTOS DO POUPA TEMPO', 'banner-pt');
        $banner_pt_exibido = true;
    }
    foreach ($grupo_pt as $postoKey => $postosPt) {
        renderizarTabela('Posto ' . $postoKey . ' - Poupa Tempo', $postosPt, true, $postoKey);
    }
}
if (!empty($grupo_r01)) {
    if (!$banner_correios_exibido) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
    }
    renderizarTabela('Postos do Posto 01', $grupo_r01);
}
if (!empty($grupo_capital)) {
    if (!$banner_correios_exibido) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
    }
    renderizarTabela('Postos da Capital', $grupo_capital);
}
if (!empty($grupo_999)) {
    if (!$banner_correios_exibido) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
    }
    renderizarTabela('Postos da Central IIPR', $grupo_999);
}
// v8.17.5: Demais regionais já ordenadas (ksort aplicado na linha 367)
if (!empty($grupo_outros)) {
    if (!$banner_correios_exibido) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
    }
    foreach ($grupo_outros as $regional => $postos) {
        $regionalStr = str_pad($regional, 3, '0', STR_PAD_LEFT);
        renderizarTabela($regionalStr . ' - Regional ' . $regionalStr, array($regional => $postos));
    }
}

if (empty($regionais_data)) {
    echo '<p style="text-align:center; margin-top:40px; color:#999;">Nenhum dado encontrado para as datas selecionadas.</p>';
}
?>

</div>

<!-- Áudios -->
<audio id="beep" src="beep.mp3" preload="auto"></audio>
<audio id="concluido" src="concluido.mp3" preload="auto"></audio>
<audio id="pacotejaconferido" src="pacotejaconferido.mp3" preload="auto"></audio>
<audio id="pacotedeoutraregional" src="pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="posto_poupatempo" src="posto_poupatempo.mp3" preload="auto"></audio>
<audio id="pertence_correios" src="pertence_aos_correios.mp3" preload="auto"></audio>

<script>
// ========================================
// v9.22.7: JavaScript com fila de sons sem sobreposição
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
    var mensagemLeitura = document.getElementById('mensagemLeitura');
    var postoBloqueioNumero = document.getElementById('postoBloqueioNumero');
    var postoBloqueioNome = document.getElementById('postoBloqueioNome');
    var btnAdicionarBloqueio = document.getElementById('btnAdicionarBloqueio');
    var listaPostosBloqueados = document.getElementById('listaPostosBloqueados');
    var postosBloqueados = <?php echo json_encode($postos_bloqueados); ?>;
    var postosBloqueadosMap = {};
    var tipoEscolhido = false;
    var datasFiltroSql = <?php echo json_encode($datas_sql); ?>;

    // v9.22.7: Fila de áudio para evitar sobreposição
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

    // Encadeia para tocar o próximo som quando o atual terminar
    var listaSons = [];
    if (beep) listaSons.push(beep);
    if (concluido) listaSons.push(concluido);
    if (pacoteJaConferido) listaSons.push(pacoteJaConferido);
    if (pacoteOutraRegional) listaSons.push(pacoteOutraRegional);
    if (postoPoupaTempo) listaSons.push(postoPoupaTempo);
    if (pertenceCorreios) listaSons.push(pertenceCorreios);
    for (var si = 0; si < listaSons.length; si++) {
        listaSons[si].addEventListener('ended', function() {
            tocarProximoSom();
        });
    }

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
    document.addEventListener('keydown', desbloquearAudio, { once: true });
    
    // v9.23.2: Variáveis de contexto para sons inteligentes
    var regionalAtual = null;
    var tipoAtual = null; // 'poupatempo' ou 'correios'
    var primeiroConferido = false;

    function obterTipoInicioSelecionado() {
        var radios = document.querySelectorAll('input[name="tipo_inicio"]');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return 'correios';
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
        if (overlayTipo) overlayTipo.style.display = 'none';
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
            var mm = String(now.getMonth() + 1).padStart(2, '0');
            var dd = String(now.getDate()).padStart(2, '0');
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

    function renderizarPacotesPendentes() {
        if (!listaPacotesNovos) return;
        listaPacotesNovos.innerHTML = '';
        for (var i = 0; i < pacotesPendentes.length; i++) {
            var p = pacotesPendentes[i];
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + p.lote + '</td>' +
                '<td>' + p.regional + '</td>' +
                '<td>' + p.posto + '</td>' +
                '<td>' + p.quantidade + '</td>' +
                '<td>' + p.dataexp + '</td>' +
                '<td>' + (p.responsavel || '') + '</td>' +
                '<td>' +
                '<button type="button" class="btn-acao btn-salvar" data-editar="' + i + '">Editar</button> ' +
                '<button type="button" class="btn-acao btn-cancelar" data-remover="' + i + '">Remover</button>' +
                '</td>';
            listaPacotesNovos.appendChild(tr);
        }
        if (painelPacotesNovos) {
            painelPacotesNovos.style.display = pacotesPendentes.length ? 'block' : 'none';
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

    function removerPendentePorCodbar(codbar) {
        if (!codbar) return;
        for (var i = pacotesPendentes.length - 1; i >= 0; i--) {
            if (pacotesPendentes[i].codbar === codbar) {
                pacotesPendentes.splice(i, 1);
            }
        }
        renderizarPacotesPendentes();
    }

    function formatarDataBr(dataSql) {
        if (!dataSql || typeof dataSql !== 'string') return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dataSql)) {
            var p = dataSql.split('-');
            return p[2] + '-' + p[1] + '-' + p[0];
        }
        return dataSql;
    }

    function verificarPacoteOutraData(codbar, callback) {
        var formData = new FormData();
        formData.append('verificar_pacote_data', '1');
        formData.append('codbar', codbar || '');
        formData.append('datas_sql', JSON.stringify(datasFiltroSql || []));
        fetch(window.location.href, { method: 'POST', body: formData })
            .then(function(resp){ return resp.json(); })
            .then(function(data){ if (callback) callback(data); })
            .catch(function(){ if (callback) callback({ success:false, status:'erro' }); });
    }

    function salvarPacoteNaoListado(obj, callback) {
        if (!usuarioAtual) {
            if (callback) callback(false);
            return;
        }
        var formData = new FormData();
        formData.append('inserir_pacotes_nao_listados', '1');
        formData.append('usuario', usuarioAtual);
        formData.append('pacotes', JSON.stringify([obj]));
        fetch(window.location.href, { method: 'POST', body: formData })
            .then(function(resp){ return resp.json(); })
            .then(function(data){ if (callback) callback(!!(data && data.success)); })
            .catch(function(){ if (callback) callback(false); });
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
            salvarPacoteNaoListado(obj, function(ok) {
                if (ok) {
                    removerPendentePorCodbar(obj.codbar);
                } else {
                    alert('Erro ao salvar pacote nao listado.');
                }
                fecharModalPacote();
            });
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
            if (target.getAttribute('data-remover')) {
                var idxRem = parseInt(target.getAttribute('data-remover'), 10);
                if (!isNaN(idxRem)) {
                    pacotesPendentes.splice(idxRem, 1);
                    renderizarPacotesPendentes();
                }
            }
            if (target.getAttribute('data-editar')) {
                var idxEdit = parseInt(target.getAttribute('data-editar'), 10);
                if (!isNaN(idxEdit) && pacotesPendentes[idxEdit]) {
                    var p = pacotesPendentes[idxEdit];
                    if (pacoteCodbar) pacoteCodbar.value = p.codbar || '';
                    if (pacoteLote) pacoteLote.value = p.lote || '';
                    if (pacoteRegional) pacoteRegional.value = p.regional || '';
                    if (pacotePosto) pacotePosto.value = p.posto || '';
                    if (pacoteQtd) pacoteQtd.value = p.quantidade || '';
                    if (pacoteDataexp) pacoteDataexp.value = p.dataexp || '';
                    if (pacoteResponsavel) pacoteResponsavel.value = p.responsavel || '';
                    abrirModalPacote(p.codbar || '', idxEdit);
                }
            }
        });
    }

    if (btnCancelarPacotes) {
        btnCancelarPacotes.addEventListener('click', function() {
            pacotesPendentes = [];
            renderizarPacotesPendentes();
        });
    }

    if (btnSalvarPacotes) {
        btnSalvarPacotes.addEventListener('click', function() {
            if (!usuarioAtual) {
                alert('Informe o responsável da conferência.');
                return;
            }
            if (!pacotesPendentes.length) return;
            var formData = new FormData();
            formData.append('inserir_pacotes_nao_listados', '1');
            formData.append('usuario', usuarioAtual);
            formData.append('pacotes', JSON.stringify(pacotesPendentes));
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        alert('Pacotes inseridos: ' + data.inseridos);
                        pacotesPendentes = [];
                        renderizarPacotesPendentes();
                        window.location.reload();
                    } else {
                        alert('Erro ao inserir pacotes.');
                    }
                })
                .catch(function(){ alert('Erro ao inserir pacotes.'); });
        });
    }
    
    if (usuarioInputModal) {
        usuarioInputModal.focus();
    }

    function liberarPaginaComUsuario(nome) {
        usuarioAtual = nome;
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
                alert('Informe o responsável da conferência.');
                if (usuarioInputModal) usuarioInputModal.focus();
                return;
            }
            liberarPaginaComUsuario(nome);
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
    
    // Função para salvar conferência via AJAX
    function salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, usuario) {
        var formData = new FormData();
        formData.append('salvar_lote_ajax', '1');
        formData.append('lote', lote);
        formData.append('regional', regional);
        formData.append('posto', posto);
        formData.append('dataexp', dataexp);
        formData.append('qtd', qtd);
        formData.append('codbar', codbar);
        formData.append('usuario', usuario);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (!data.sucesso) {
                console.error('Erro ao salvar:', data.erro);
            }
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

        valor = valor.replace(/\D+/g, '');
        if (valor.length !== 19) {
            input.value = "";
            return;
        }

        var postoLido = valor.substr(11, 3);
        if (postosBloqueadosMap[postoLido]) {
            falarTexto('nao enviar este posto');
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Posto bloqueado:</strong> ' + postoLido + ' ' + (postosBloqueadosMap[postoLido].nome || '');
            }
            input.value = "";
            return;
        }
        
        // Aplicar transformações opcionais
        // valor = substituirMultiplosPadroes(valor);
        
        var linha = document.querySelector('tr[data-codigo="' + valor + '"]');
        
        if (!linha) {
            verificarPacoteOutraData(valor, function(resp) {
                if (resp && resp.success && resp.status === 'outra_data') {
                    if (mensagemLeitura) {
                        mensagemLeitura.innerHTML = '<strong>Pacote de outra data:</strong> ' + formatarDataBr(resp.data || '');
                    }
                    falarTexto('pacote de outra data');
                    input.value = "";
                    return;
                }

                var now = new Date();
                var mm = String(now.getMonth() + 1).padStart(2, '0');
                var dd = String(now.getDate()).padStart(2, '0');
                var dataPadrao = now.getFullYear() + '-' + mm + '-' + dd;

                var obj = {
                    codbar: valor,
                    lote: valor.substr(0, 8),
                    regional: valor.substr(8, 3),
                    posto: valor.substr(11, 3),
                    quantidade: parseInt(valor.substr(14, 5), 10) || 1,
                    dataexp: dataPadrao,
                    responsavel: ''
                };
                adicionarPacotePendente(obj);
                falarTexto('pacote nao encontrado');
                if (mensagemLeitura) {
                    mensagemLeitura.innerHTML = '<strong>Pacote nao encontrado:</strong> adicionado a lista pendente.';
                }
                input.value = "";
            });
            return;
        }

        // v9.23.1: responsável obrigatório
        if (!usuarioAtual) {
            alert('Informe o responsável da conferência para iniciar.');
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
        
        // Verifica se já foi conferido
        if (linha.classList.contains("confirmado")) {
            if (!muteBeep || !muteBeep.checked) {
                enfileirarSom(beep);
            }
            enfileirarSom(pacoteJaConferido);
            input.value = "";
            return;
        }
        
        // v9.23.4: Lógica inteligente de sons
        var somAlerta = null;
        var podeConferir = true;
        
        // Caso 1: Primeiro pacote da conferência - sempre beep
        if (!primeiroConferido) {
            tipoAtual = obterTipoInicioSelecionado();
            if (tipoAtual === tipoPacote) {
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
        // Caso 2: Pacote Poupa Tempo aparecendo em meio aos Correios
        else if (tipoAtual === 'correios' && tipoPacote === 'poupatempo') {
            somAlerta = postoPoupaTempo; // Alerta: PT misturado com correios!
            podeConferir = false;
            // NÃO altera regionalAtual nem tipoAtual - continua conferindo correios
        }
        // Caso 3: Pacote Correios aparecendo em meio ao Poupa Tempo
        else if (tipoAtual === 'poupatempo' && tipoPacote === 'correios') {
            somAlerta = pertenceCorreios; // Alerta: pertence aos correios!
            podeConferir = false;
            // NÃO altera regionalAtual nem tipoAtual
        }
        // Caso 4: Regional diferente (mesmo tipo)
        else if (regionalDoPacote !== regionalAtual && tipoPacote === tipoAtual) {
            somAlerta = pacoteOutraRegional; // Alerta: regional diferente!
            podeConferir = false;
            // NÃO altera regionalAtual nem tipoAtual
        }

        // PT único: emitir aviso específico mesmo no primeiro pacote
        if (podeConferir && tipoPacote === 'poupatempo') {
            var totalPT = document.querySelectorAll('tbody tr[data-ispt="1"]').length;
            if (totalPT === 1 && !somAlerta) {
                somAlerta = postoPoupaTempo;
            }
        }

        // Se não pode conferir, apenas alerta
        if (!podeConferir) {
            if (somAlerta) {
                enfileirarSom(somAlerta);
            }
            input.value = "";
            return;
        }
        
        // Marca como conferido
        linha.classList.add("confirmado");
        
        // Toca os sons: beep na leitura válida, alerta se necessário
        if (!muteBeep || !muteBeep.checked) {
            enfileirarSom(beep);
        }
        if (somAlerta) {
            enfileirarSom(somAlerta);
        }

        if (mensagemLeitura) {
            mensagemLeitura.textContent = '';
        }
        
        input.value = "";
        
        // Centraliza a linha na tela e destaca última leitura
        var ultimas = document.querySelectorAll('tr.ultimo-lido');
        for (var u = 0; u < ultimas.length; u++) {
            ultimas[u].classList.remove('ultimo-lido');
        }
        linha.classList.add('ultimo-lido');

        var rect = linha.getBoundingClientRect();
        var alvo = rect.top + window.pageYOffset - (window.innerHeight / 2) + (rect.height / 2);
        window.scrollTo({ top: alvo, behavior: 'smooth' });
        
        // Salvar no banco se auto-save estiver ativo
        if (radioAutoSalvar.checked) {
            var lote = linha.getAttribute("data-lote");
            var regional = linha.getAttribute("data-regional");
            var posto = linha.getAttribute("data-posto");
            var dataexp = linha.getAttribute("data-data-sql") || linha.getAttribute("data-data");
            var qtd = linha.getAttribute("data-qtd");
            var codbar = linha.getAttribute("data-codigo");
            salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, usuarioAtual);
        }
        
        // v9.2: Verifica se completou o GRUPO atual (PT, Capital, R01, 999, ou outra regional)
        var grupoAtual = null;
        var todasLinhas = document.querySelectorAll('tbody tr');
        var linhasDoGrupo = [];
        
        // Determina qual grupo está sendo conferido
        if (tipoAtual === 'poupatempo') {
            grupoAtual = linha.getAttribute('data-pt-group') || linha.getAttribute('data-posto');
            // Linhas PT do mesmo posto
            for (var i = 0; i < todasLinhas.length; i++) {
                if (todasLinhas[i].getAttribute('data-ispt') === '1' &&
                    (todasLinhas[i].getAttribute('data-pt-group') === grupoAtual || todasLinhas[i].getAttribute('data-posto') === grupoAtual)) {
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
            enfileirarSom(concluido);
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
            var formData = new FormData();
            formData.append('salvar_posto_bloqueado', '1');
            formData.append('posto', posto);
            formData.append('nome', nome);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        postosBloqueados.push({ posto: posto, nome: nome });
                        renderizarPostosBloqueados();
                        if (postoBloqueioNumero) postoBloqueioNumero.value = '';
                        if (postoBloqueioNome) postoBloqueioNome.value = '';
                    } else {
                        alert('Erro ao salvar posto bloqueado.');
                    }
                })
                .catch(function(){ alert('Erro ao salvar posto bloqueado.'); });
        });
    }

    if (listaPostosBloqueados) {
        listaPostosBloqueados.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) return;
            var posto = target.getAttribute('data-remover');
            if (!posto) return;
            var formData = new FormData();
            formData.append('excluir_posto_bloqueado', '1');
            formData.append('posto', posto);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        postosBloqueados = postosBloqueados.filter(function(p){ return p.posto !== posto; });
                        renderizarPostosBloqueados();
                    } else {
                        alert('Erro ao remover posto bloqueado.');
                    }
                })
                .catch(function(){ alert('Erro ao remover posto bloqueado.'); });
        });
    }

    renderizarPostosBloqueados();
});
</script>

</body>
</html>
