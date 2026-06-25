<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
/* encontra_posto.php — v1.0.18
 * CHANGELOG v1.0.17 (17/04/2026):
 * - [NOVO] Campo "Triador" (responsavel pela triagem) salvo em lotes_na_estante.triado_por
 * - [NOVO] Campo triador com persistencia via localStorage (triador_nome)
 * - [NOVO] triado_por enviado no AJAX e gravado no INSERT INTO lotes_na_estante
 * CHANGELOG v1.0.16 (16/04/2026):
 * - [VERSAO] Tela consolidada para v1.0.17
 * - [CORRIGIDO] Vocalizacao da regional/posto passa a disparar imediatamente na leitura
 * - [CORRIGIDO] Cada codigo de barras vocaliza apenas uma vez por leitura
 * - [CORRIGIDO] Lotes fora do periodo falam "Lote de outra data" e lotes sem upload falam "Lote não carregado"
 * - [MELHORADO] Cabecalho do resultado aparece imediatamente com pre-visualizacao local da leitura
 * - [CORRIGIDO] Postos regionais vocalizam "Regional X" conforme a regional da tabela ciRegionais
 * Triagem rapida: leitura de codigo de barras, busca em ciRegionais,
 * vocalizacao e exibicao visual do posto.
 * Registra leituras para controle da estante.
 */

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function normalizarDataEntrada($s) {
    $s = trim((string)$s);
    if ($s === '') return '';
    if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $s, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $s, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    return '';
}

function normalizarDataIso($s) {
    $s = trim((string)$s);
    if ($s === '') return '';
    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $s, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    return '';
}

function parseDatasAlvo($raw) {
    $out = array();
    $partes = preg_split('/[;,\s]+/', (string)$raw);
    foreach ($partes as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $n = normalizarDataEntrada($p);
        if ($n !== '') {
            $out[] = $n;
        }
    }
    $out = array_values(array_unique($out));
    return $out;
}

function obterColunasTabela($pdo, $tabela) {
    static $cache = array();
    if (!isset($cache[$tabela])) {
        $stmtCols = $pdo->query("SHOW COLUMNS FROM `" . $tabela . "`");
        $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 0);
        $cache[$tabela] = is_array($cols) ? $cols : array();
    }
    return $cache[$tabela];
}

function tabelaTemColuna($pdo, $tabela, $coluna) {
    return in_array($coluna, obterColunasTabela($pdo, $tabela), true);
}

function mapearTurnoCiPostos($turno) {
    $turno = trim((string)$turno);
    if ($turno === 'Madrugada') {
        return 0;
    }
    if ($turno === 'Tarde') {
        return 2;
    }
    if ($turno === 'Noite') {
        return 3;
    }
    return 1;
}

function normalizarDataSqlPacote($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '';
    }
    if (preg_match('/^(\d{2})\-(\d{2})\-(\d{4})$/', $valor, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return $valor;
    }
    return '';
}

function normalizarDataHoraSql($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '';
    }
    $valor = str_replace('T', ' ', $valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $valor)) {
        return $valor . ':00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $valor)) {
        return $valor;
    }
    return '';
}

function resolverNomePostoCiPostos($pdo, $posto) {
    $posto = trim((string)$posto);
    if ($posto === '') {
        return '';
    }
    if (!preg_match('/^\d+$/', $posto)) {
        return $posto;
    }
    $postoPad = str_pad((string)((int)$posto), 3, '0', STR_PAD_LEFT);
    try {
        $stmt = $pdo->prepare("SELECT posto FROM ciPostos WHERE posto LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute(array($postoPad . ' -%'));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['posto'])) {
            return $row['posto'];
        }
    } catch (Exception $e) {
    }
    return $postoPad . ' - POSTO';
}

function normalizarEntregaTipoTriagem($entrega) {
    $entrega = strtolower(trim(str_replace(' ', '', (string)$entrega)));
    if ($entrega === '') {
        return null;
    }
    if (strpos($entrega, 'poupa') !== false || strpos($entrega, 'tempo') !== false) {
        return 'poupatempo';
    }
    if (strpos($entrega, 'correio') !== false) {
        return 'correios';
    }
    return null;
}

function montarDescricaoTriagem($posto_pad, $regional_real, $entrega_tipo) {
    $posto_int = (int)$posto_pad;
    $regional_int = (int)$regional_real;
    $tipo_posto = 'correios';
    $tipo_estante = 'regional';
    $voz = '';
    $label_tipo = '';

    if ($entrega_tipo === 'poupatempo') {
        $tipo_posto = 'poupatempo';
        $tipo_estante = 'poupatempo';
        $voz = 'Poupa Tempo ' . $posto_int;
        $label_tipo = 'Poupa Tempo ' . $posto_int;
    } elseif ($regional_int === 0) {
        $tipo_estante = 'capital';
        $voz = 'Posto ' . $posto_int . ' capital';
        $label_tipo = 'Posto ' . $posto_int . ' Capital';
    } elseif ($regional_int === 999) {
        $tipo_estante = 'central';
        $voz = 'Posto ' . $posto_int . ' central';
        $label_tipo = 'Posto ' . $posto_int . ' Central';
    } else {
        $voz = 'Regional ' . $regional_int;
        $label_tipo = 'Regional ' . $regional_int;
    }

    return array(
        'voz' => $voz,
        'label_tipo' => $label_tipo,
        'tipo_posto' => $tipo_posto,
        'tipo_estante' => $tipo_estante
    );
}

function montarCondicaoPeriodoSql($campo, $data_ini, $data_fim, $datas_alvo, &$params) {
    $params = array();
    if ($data_ini !== '') {
        $params[] = $data_ini;
        $params[] = $data_fim;
        return 'DATE(' . $campo . ') BETWEEN ? AND ?';
    }
    if (!empty($datas_alvo)) {
        $params = array_values($datas_alvo);
        return 'DATE(' . $campo . ') IN (' . implode(',', array_fill(0, count($datas_alvo), '?')) . ')';
    }
    return '1 = 0';
}

function obterLinhasEstanteAtiva($pdo, $data_ini, $data_fim, $datas_alvo) {
    $params_estante = array();
    $params_carga = array();
    $params_conf = array();
    $cond_estante = montarCondicaoPeriodoSql('l.triado_em', $data_ini, $data_fim, $datas_alvo, $params_estante);
    $cond_carga = montarCondicaoPeriodoSql('c.dataCarga', $data_ini, $data_fim, $datas_alvo, $params_carga);
    $cond_conf = montarCondicaoPeriodoSql('cp.dataexp', $data_ini, $data_fim, $datas_alvo, $params_conf);

    $sql = "SELECT DISTINCT
                LPAD(l.lote,8,'0') AS lote,
                LPAD(l.posto,3,'0') AS posto,
                LPAD(l.regional,3,'0') AS regional,
                LOWER(TRIM(REPLACE(COALESCE(r.entrega,''),' ',''))) AS entrega,
                LPAD(COALESCE(NULLIF(CAST(csv.regional_csv AS CHAR), ''), CAST(l.regional AS CHAR)),3,'0') AS regional_csv
            FROM lotes_na_estante l
            LEFT JOIN ciRegionais r ON LPAD(r.posto,3,'0') = LPAD(l.posto,3,'0')
            LEFT JOIN (
                SELECT lote, MAX(regional) AS regional_csv
                FROM ciPostosCsv
                GROUP BY lote
            ) csv ON csv.lote = l.lote
            WHERE $cond_estante
              AND EXISTS (
                SELECT 1
                FROM ciPostosCsv c
                WHERE c.lote = l.lote
                  AND LPAD(c.posto,3,'0') = LPAD(l.posto,3,'0')
                  AND $cond_carga
              )
              AND NOT EXISTS (
                SELECT 1
                FROM conferencia_pacotes cp
                WHERE UPPER(TRIM(cp.conf)) = 'S'
                  AND LPAD(cp.nlote,8,'0') = LPAD(l.lote,8,'0')
                  AND LPAD(cp.nposto,3,'0') = LPAD(l.posto,3,'0')
                  AND $cond_conf
              )
            ORDER BY LPAD(l.lote,8,'0'), LPAD(l.posto,3,'0')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params_estante, $params_carga, $params_conf));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function acumularStatsEstante(&$stats, $row) {
    $entrega = strtolower(trim(str_replace(' ', '', (string)(isset($row['entrega']) ? $row['entrega'] : ''))));
    $regional = isset($row['regional_csv']) ? (int)$row['regional_csv'] : (isset($row['regional']) ? (int)$row['regional'] : 0);
    $stats['total']++;
    if (strpos($entrega, 'poupa') !== false || strpos($entrega, 'tempo') !== false) {
        $stats['poupatempo']++;
    } elseif ($regional === 0) {
        $stats['capital']++;
    } elseif ($regional === 999) {
        $stats['central']++;
    } else {
        $stats['regional']++;
    }
}

function montarLayoutEstante($linhas_estante) {
    $layout = array(
        'correios' => array(),
        'poupatempo' => array(),
        'totais' => array('correios_lotes' => 0, 'poupatempo_lotes' => 0)
    );
    $postos_pt = array('005','006','023','024','025','026','028','080','110','315','375','487','526','527','667','730','747','790','825','880');
    $correios_keys = array('022','060','100','105','150','200','250','300','350','400','450','490','500','501','507','550','600','650','700','701','710','750','755','758','779','800','808','809','850','900','950');
    foreach ($linhas_estante as $row) {
        $qtd = 1;
        $posto = (int)(isset($row['posto']) ? $row['posto'] : 0);
        $regional = (int)(isset($row['regional']) ? $row['regional'] : 0);
        $regional_csv = isset($row['regional_csv']) ? (int)$row['regional_csv'] : 0;
        $posto_pad = str_pad((string)$posto, 3, '0', STR_PAD_LEFT);
        $is_pt = in_array($posto_pad, $postos_pt, true);
        if ($is_pt) {
            $key = $posto_pad;
            if (!isset($layout['poupatempo'][$key])) { $layout['poupatempo'][$key] = 0; }
            $layout['poupatempo'][$key] += $qtd;
            $layout['totais']['poupatempo_lotes'] += $qtd;
        } else {
            if ($regional_csv === 0 || $regional === 0) {
                if (!isset($layout['correios']['capital'])) { $layout['correios']['capital'] = 0; }
                $layout['correios']['capital'] += $qtd;
            } elseif ($regional_csv === 999 || $regional === 999) {
                if (!isset($layout['correios']['central'])) { $layout['correios']['central'] = 0; }
                $layout['correios']['central'] += $qtd;
            } else {
                $key_regional_csv = str_pad((string)$regional_csv, 3, '0', STR_PAD_LEFT);
                $key_regional = str_pad((string)$regional, 3, '0', STR_PAD_LEFT);
                $key = in_array($key_regional_csv, $correios_keys, true) ? $key_regional_csv : null;
                if ($key === null && in_array($key_regional, $correios_keys, true)) {
                    $key = $key_regional;
                }
                if ($key === null && in_array($posto_pad, $correios_keys, true)) {
                    $key = $posto_pad;
                }
                if ($key === null) {
                    $key = $key_regional_csv !== '000' ? $key_regional_csv : $key_regional;
                }
                if (!isset($layout['correios'][$key])) { $layout['correios'][$key] = 0; }
                $layout['correios'][$key] += $qtd;
            }
            if ($posto === 1) {
                if (!isset($layout['correios']['posto001'])) { $layout['correios']['posto001'] = 0; }
                $layout['correios']['posto001'] += $qtd;
            }
            $layout['totais']['correios_lotes'] += $qtd;
        }
    }
    return $layout;
}

$dbOk = false;

try {
    $pdo = new PDO(
        "mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
        (getenv('DB_USER') ?: 'controle_mat'),
        (getenv('DB_PASS') ?: '375256')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS lotes_na_estante (
        id INT NOT NULL AUTO_INCREMENT,
        lote INT(8) NOT NULL,
        regional INT(3) NOT NULL,
        posto INT(3) NOT NULL,
        quantidade INT(5) NOT NULL,
        producao_de DATE NOT NULL,
        triado_em DATETIME NOT NULL,
        triado_por VARCHAR(100) NOT NULL DEFAULT '',
        PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    try { $pdo->exec("ALTER TABLE lotes_na_estante ADD COLUMN triado_por VARCHAR(100) NOT NULL DEFAULT '' AFTER triado_em"); } catch (Exception $e) {}

    /* v1.2.2: AJAX — histórico de triagens */
    if (isset($_GET['ajax_historico_triagens'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 150;
        try {
            $stHist = $pdo->prepare(
                "SELECT DATE_FORMAT(triado_em,'%d/%m/%Y %H:%i') AS dt,
                        LPAD(posto,3,'0') AS posto,
                        LPAD(regional,3,'0') AS regional,
                        quantidade,
                        triado_por,
                        LPAD(lote,8,'0') AS lote
                 FROM lotes_na_estante
                 ORDER BY triado_em DESC, id DESC
                 LIMIT ?"
            );
            $stHist->execute(array($limit));
            die(json_encode(array('success' => true, 'rows' => $stHist->fetchAll())));
        } catch (Exception $eH) {
            die(json_encode(array('success' => false, 'erro' => $eH->getMessage())));
        }
    }

    /* Leitura de display (35 digitos) -> posto associado (cadastroMalotes) */
    if (isset($_GET['resolver_display'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $leitura = isset($_GET['leitura']) ? preg_replace('/\D+/', '', $_GET['leitura']) : '';
        if (strlen($leitura) > 35) { $leitura = substr($leitura, -35); }
        if (strlen($leitura) !== 35) {
            die(json_encode(array('success' => false, 'erro' => 'Codigo invalido (precisa de 35 digitos)')));
        }
        $cep = substr($leitura, 0, 8);
        $seq = substr($leitura, -5);
        $posto = '';
        try {
            $st = $pdo->prepare("SELECT posto FROM cadastroMalotes WHERE leitura = ? ORDER BY id DESC LIMIT 1");
            $st->execute(array($leitura));
            $r = $st->fetch(PDO::FETCH_ASSOC);
            if ($r && trim((string)$r['posto']) !== '') { $posto = trim((string)$r['posto']); }
            if ($posto === '') {
                $st = $pdo->prepare("SELECT posto FROM cadastroMalotes WHERE cep = ? AND sequencial = ? ORDER BY id DESC LIMIT 1");
                $st->execute(array($cep, $seq));
                $r = $st->fetch(PDO::FETCH_ASSOC);
                if ($r && trim((string)$r['posto']) !== '') { $posto = trim((string)$r['posto']); }
            }
            if ($posto === '') {
                $st = $pdo->prepare("SELECT posto FROM cadastroMalotes WHERE cep = ? ORDER BY id DESC LIMIT 1");
                $st->execute(array($cep));
                $r = $st->fetch(PDO::FETCH_ASSOC);
                if ($r && trim((string)$r['posto']) !== '') { $posto = trim((string)$r['posto']); }
            }
        } catch (Exception $eRD) {
            die(json_encode(array('success' => false, 'erro' => 'Erro ao consultar: ' . $eRD->getMessage())));
        }
        if ($posto === '') {
            die(json_encode(array('success' => true, 'encontrado' => false, 'leitura' => $leitura, 'cep' => $cep, 'sequencial' => $seq)));
        }
        $ehNum = preg_match('/^\d+$/', $posto) ? true : false;
        $postoPad = $ehNum ? str_pad((string)((int)$posto), 3, '0', STR_PAD_LEFT) : $posto;
        $postoNum = $ehNum ? (int)$posto : 0;
        $nome = resolverNomePostoCiPostos($pdo, $posto);
        $nomeLimpo = trim(preg_replace('/^\s*\d+\s*-\s*/', '', (string)$nome));
        if (strtoupper($nomeLimpo) === 'POSTO') { $nomeLimpo = ''; }
        $voz = 'Posto ' . ($ehNum ? $postoNum : $postoPad);
        if ($nomeLimpo !== '') { $voz .= ', ' . $nomeLimpo; }
        die(json_encode(array(
            'success' => true,
            'encontrado' => true,
            'leitura' => $leitura,
            'cep' => $cep,
            'sequencial' => $seq,
            'posto' => $postoPad,
            'posto_num' => $postoNum,
            'nome' => $nome,
            'voz' => $voz
        )));
    }

    /* T006: resolver posto/regional p/ VOCALIZACAO (READ-ONLY, NAO grava na estante).
       Usado pela versao mobile encontra_posto_mobile.php (camera/leitor + voz). */
    if (isset($_GET['resolver_posto_voz'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $codbar = isset($_GET['codbar']) ? preg_replace('/\D+/', '', $_GET['codbar']) : '';
        if (strlen($codbar) > 19) { $codbar = substr($codbar, -19); }
        if (!preg_match('/^\d{19}$/', $codbar)) {
            die(json_encode(array('success' => false, 'erro' => 'Codigo de barras invalido (precisa de 19 digitos)')));
        }
        $lote = substr($codbar, 0, 8);
        $regional_csv = substr($codbar, 8, 3);
        $posto_num = substr($codbar, 11, 3);
        $posto_pad = str_pad($posto_num, 3, '0', STR_PAD_LEFT);

        $regional_real = (int)$regional_csv;
        $entrega_tipo = null;
        $posto_encontrado = false;
        try {
            $stmt = $pdo->prepare("SELECT LPAD(posto,3,'0') AS posto,
                                          CAST(regional AS UNSIGNED) AS regional,
                                          LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega
                                   FROM ciRegionais
                                   WHERE LPAD(posto,3,'0') = ?
                                   LIMIT 1");
            $stmt->execute(array($posto_pad));
            $postoRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($postoRow) {
                $posto_encontrado = true;
                $regional_real = (int)$postoRow['regional'];
                $entrega_tipo = normalizarEntregaTipoTriagem($postoRow['entrega']);
            }
        } catch (Exception $eRPV) {
        }

        $nome_posto = resolverNomePostoCiPostos($pdo, $posto_num);
        $nomeLimpo = trim(preg_replace('/^\s*\d+\s*-\s*/', '', (string)$nome_posto));
        if (strtoupper($nomeLimpo) === 'POSTO') { $nomeLimpo = ''; }

        $descricao = montarDescricaoTriagem($posto_pad, $regional_real, $entrega_tipo);
        $voz = $descricao['voz'];
        if ($nomeLimpo !== '') { $voz .= ', ' . $nomeLimpo; }

        die(json_encode(array(
            'success' => true,
            'encontrado' => $posto_encontrado,
            'posto' => $posto_pad,
            'posto_int' => (int)$posto_num,
            'regional' => $regional_real,
            'regional_pad' => str_pad((string)$regional_real, 3, '0', STR_PAD_LEFT),
            'label_tipo' => $descricao['label_tipo'],
            'tipo_posto' => $descricao['tipo_posto'],
            'nome' => $nome_posto,
            'lote' => $lote,
            'voz' => $voz,
            'codbar' => $codbar
        )));
    }

    if (isset($_POST['inserir_pacotes_nao_listados'])) {
        header('Content-Type: application/json');
        $payload = isset($_POST['pacotes']) ? $_POST['pacotes'] : '';
        $usuario_conf = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $autor_salvamento = isset($_POST['autor_salvamento']) ? trim($_POST['autor_salvamento']) : '';
        $criado_salvamento = isset($_POST['criado_salvamento']) ? trim($_POST['criado_salvamento']) : '';
        $turno_salvamento = isset($_POST['turno_salvamento']) ? trim($_POST['turno_salvamento']) : 'Manhã';
        $consolidar_salvamento = !empty($_POST['consolidar_salvamento']);

        if ($usuario_conf === '' && $autor_salvamento !== '') {
            $usuario_conf = $autor_salvamento;
        }
        if ($usuario_conf === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }
        if ($autor_salvamento === '') {
            $autor_salvamento = $usuario_conf;
        }

        $pacotes = json_decode($payload, true);
        if (!is_array($pacotes) || empty($pacotes)) {
            die(json_encode(array('success' => false, 'erro' => 'Fila vazia ou invalida')));
        }

        $criado_sql = normalizarDataHoraSql($criado_salvamento);
        if ($criado_sql === '') {
            $criado_sql = date('Y-m-d H:i:s');
        }
        $turno_codigo = mapearTurnoCiPostos($turno_salvamento);

        $ok = 0;
        $ok_postos = 0;
        $erros = array();
        $stmtCsv = $pdo->prepare("INSERT INTO ciPostosCsv (lote, posto, regional, quantidade, dataCarga, data, usuario) VALUES (?,?,?,?,?,NOW(),?)");
        $gruposCiPostos = array();

        foreach ($pacotes as $p) {
            try {
                $lote = isset($p['lote']) ? trim((string)$p['lote']) : '';
                $posto = isset($p['posto']) ? trim((string)$p['posto']) : '';
                $regional = isset($p['regional']) ? trim((string)$p['regional']) : '';
                $quantidade = isset($p['quantidade']) ? (int)$p['quantidade'] : 0;
                $dataexp = isset($p['dataexp']) ? trim((string)$p['dataexp']) : '';
                $usuario_pacote = isset($p['responsavel']) ? trim((string)$p['responsavel']) : '';
                if ($usuario_pacote === '') {
                    $usuario_pacote = $usuario_conf;
                }

                if ($lote === '' || $posto === '' || $regional === '' || $quantidade <= 0 || $dataexp === '') {
                    throw new Exception('Campos obrigatorios ausentes');
                }

                $data_sql = normalizarDataSqlPacote($dataexp);
                if ($data_sql === '') {
                    throw new Exception('Data invalida');
                }

                $stmtCsv->execute(array($lote, $posto, $regional, $quantidade, $data_sql, $usuario_pacote));
                $ok++;

                $chaveGrupo = $posto . '|' . $data_sql . '|' . ($consolidar_salvamento ? $usuario_pacote : $lote . '|' . $regional);
                if (!isset($gruposCiPostos[$chaveGrupo])) {
                    $gruposCiPostos[$chaveGrupo] = array(
                        'posto' => $posto,
                        'dia' => $data_sql,
                        'quantidade' => 0,
                        'turno' => $turno_codigo,
                        'autor' => $autor_salvamento,
                        'criado' => $criado_sql,
                        'regional' => $regional,
                        'lote' => $lote,
                        'responsavel' => $usuario_pacote
                    );
                }
                $gruposCiPostos[$chaveGrupo]['quantidade'] += $quantidade;
            } catch (Exception $ex) {
                $erros[] = $ex->getMessage();
            }
        }

        foreach ($gruposCiPostos as $grupo) {
            try {
                $campos = array();
                $vals = array();
                $pars = array();

                if (tabelaTemColuna($pdo, 'ciPostos', 'posto')) {
                    $campos[] = 'posto';
                    $vals[] = '?';
                    $pars[] = resolverNomePostoCiPostos($pdo, $grupo['posto']);
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'dia')) {
                    $campos[] = 'dia';
                    $vals[] = '?';
                    $pars[] = $grupo['dia'];
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'quantidade')) {
                    $campos[] = 'quantidade';
                    $vals[] = '?';
                    $pars[] = (int)$grupo['quantidade'];
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'turno')) {
                    $campos[] = 'turno';
                    $vals[] = '?';
                    $pars[] = (int)$grupo['turno'];
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'autor')) {
                    $campos[] = 'autor';
                    $vals[] = '?';
                    $pars[] = $grupo['autor'];
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'criado')) {
                    $campos[] = 'criado';
                    $vals[] = '?';
                    $pars[] = $grupo['criado'];
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'regional')) {
                    $campos[] = 'regional';
                    $vals[] = '?';
                    $pars[] = is_numeric($grupo['regional']) ? (int)$grupo['regional'] : null;
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'lote') && !$consolidar_salvamento) {
                    $campos[] = 'lote';
                    $vals[] = '?';
                    $pars[] = is_numeric($grupo['lote']) ? (int)$grupo['lote'] : 0;
                }
                if (tabelaTemColuna($pdo, 'ciPostos', 'situacao')) {
                    $campos[] = 'situacao';
                    $vals[] = '?';
                    $pars[] = 0;
                }

                if (!empty($campos)) {
                    $sqlPostos = "INSERT INTO ciPostos (" . implode(',', $campos) . ") VALUES (" . implode(',', $vals) . ")";
                    $stmtPostos = $pdo->prepare($sqlPostos);
                    $stmtPostos->execute($pars);
                    $ok_postos++;
                }
            } catch (Exception $ex) {
                $erros[] = $ex->getMessage();
            }
        }

        $stmtCsv = null;
        die(json_encode(array(
            'success' => $ok > 0,
            'inseridos' => $ok,
            'inseridos_postos' => $ok_postos,
            'consolidado' => $consolidar_salvamento,
            'erros' => $erros
        )));
    }

    if (isset($_POST['ajax_estante_status'])) {
        header('Content-Type: application/json');
        $datas_alvo = parseDatasAlvo(isset($_POST['datas_alvo']) ? $_POST['datas_alvo'] : '');
        $data_ini = normalizarDataIso(isset($_POST['data_ini']) ? $_POST['data_ini'] : '');
        $data_fim = normalizarDataIso(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
        $hoje = date('Y-m-d');
        if ($data_ini === '' && $data_fim === '' && empty($datas_alvo)) {
            $data_ini = $hoje;
            $data_fim = $hoje;
        }
        if ($data_ini !== '' && $data_fim === '') {
            $data_fim = $data_ini;
        }
        if ($data_fim !== '' && $data_ini === '') {
            $data_ini = $data_fim;
        }
        if (empty($datas_alvo) && $data_ini === '') {
            die(json_encode(array(
                'success' => true,
                'estante' => array('total' => 0, 'capital' => 0, 'central' => 0, 'regional' => 0, 'poupatempo' => 0),
                'layout' => array('correios' => array(), 'poupatempo' => array(), 'totais' => array('correios_lotes' => 0, 'poupatempo_lotes' => 0))
            )));
        }
        $estante_stats = array('total' => 0, 'capital' => 0, 'central' => 0, 'regional' => 0, 'poupatempo' => 0);
        $sem_upload = array('total' => 0, 'lotes' => array());
        $layout = array('correios' => array(), 'poupatempo' => array(), 'totais' => array('correios_lotes' => 0, 'poupatempo_lotes' => 0));
        try {
            $linhas_estante = obterLinhasEstanteAtiva($pdo, $data_ini, $data_fim, $datas_alvo);
            foreach ($linhas_estante as $row) {
                acumularStatsEstante($estante_stats, $row);
            }
            $sem_upload = array('total' => 0, 'lotes' => array());
            $layout = montarLayoutEstante($linhas_estante);
        } catch (Exception $e) {
            // ignore
        }
        die(json_encode(array('success' => true, 'estante' => $estante_stats, 'sem_upload' => $sem_upload, 'layout' => $layout)));
    }

    if (isset($_POST['ajax_buscar_posto'])) {
        header('Content-Type: application/json');
        $codbar = isset($_POST['codbar']) ? trim($_POST['codbar']) : '';
        $codbar_limpo = preg_replace('/\D+/', '', $codbar);
        $triado_por = isset($_POST['triado_por']) ? trim($_POST['triado_por']) : '';
        $datas_alvo = parseDatasAlvo(isset($_POST['datas_alvo']) ? $_POST['datas_alvo'] : '');
        $data_ini = normalizarDataIso(isset($_POST['data_ini']) ? $_POST['data_ini'] : '');
        $data_fim = normalizarDataIso(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
        $hoje = date('Y-m-d');
        if ($data_ini === '' && $data_fim === '' && empty($datas_alvo)) {
            $data_ini = $hoje;
            $data_fim = $hoje;
        }
        if ($data_ini !== '' && $data_fim === '') {
            $data_fim = $data_ini;
        }
        if ($data_fim !== '' && $data_ini === '') {
            $data_ini = $data_fim;
        }

        if (empty($datas_alvo) && $data_ini === '') {
            die(json_encode(array('success' => false, 'erro' => 'Informe o periodo da estante')));
        }

        if (!preg_match('/^\d{19}$/', $codbar_limpo)) {
            die(json_encode(array('success' => false, 'erro' => 'Codigo de barras invalido (19 digitos)')));
        }

        $lote = substr($codbar_limpo, 0, 8);
        $regional_csv = substr($codbar_limpo, 8, 3);
        $posto_num = substr($codbar_limpo, 11, 3);
        $quantidade = strlen($codbar_limpo) >= 19 ? substr($codbar_limpo, 14, 5) : '00001';
        $posto_pad = str_pad($posto_num, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("SELECT LPAD(posto,3,'0') AS posto,
                                      CAST(regional AS UNSIGNED) AS regional,
                                      LOWER(TRIM(REPLACE(entrega,' ',''))) AS entrega
                               FROM ciRegionais
                               WHERE LPAD(posto,3,'0') = ?
                               LIMIT 1");
        $stmt->execute(array($posto_pad));
        $postoRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $regional_real = null;
        $entrega_tipo = null;
        $posto_encontrado = false;

        if ($postoRow) {
            $posto_encontrado = true;
            $regional_real = (int)$postoRow['regional'];
            $entrega_tipo = normalizarEntregaTipoTriagem($postoRow['entrega']);
        } else {
            $regional_real = (int)$regional_csv;
        }

        $posto_int = (int)$posto_num;
        $descricao_triagem = montarDescricaoTriagem($posto_pad, $regional_real, $entrega_tipo);
        $voz = $descricao_triagem['voz'];
        $tipo_posto = $descricao_triagem['tipo_posto'];
        $label_tipo = $descricao_triagem['label_tipo'];
        $tipo_estante = $descricao_triagem['tipo_estante'];

        $data_producao = null;
        $tem_carga_csv = false;
        try {
            // Usa regional_real (de ciRegionais) em vez de regional_csv (do codigo de barras),
            // pois o barcode pode conter a regional-pai e não a regional real do posto.
            // Ex: barcode R500+P507 mas ciPostosCsv armazena regional=507 para o posto 507.
            $regional_busca = ($regional_real !== null) ? $regional_real : (int)$regional_csv;
            $stmtProd = $pdo->prepare("SELECT COUNT(*) AS total, MAX(DATE(COALESCE(dataCarga, data))) AS data_prod
                FROM ciPostosCsv
                WHERE LPAD(CAST(lote AS CHAR),8,'0') = ?
                  AND LPAD(CAST(posto AS CHAR),3,'0') = ?");
            $stmtProd->execute(array(
                str_pad((string)$lote, 8, '0', STR_PAD_LEFT),
                str_pad((string)$posto_num, 3, '0', STR_PAD_LEFT)
            ));
            $rowProd = $stmtProd->fetch(PDO::FETCH_ASSOC);
            if ($rowProd && (int)$rowProd['total'] > 0) {
                $tem_carga_csv = true;
            }
            if ($rowProd && !empty($rowProd['data_prod'])) {
                $data_producao = $rowProd['data_prod'];
            }
        } catch (Exception $e) {
            $data_producao = null;
            $tem_carga_csv = false;
        }

        $fora_periodo = false;
        if ($data_producao && $data_ini !== '' && ($data_producao < $data_ini || $data_producao > $data_fim)) {
            $fora_periodo = true;
        }
        if ($data_producao && $data_ini === '' && !in_array($data_producao, $datas_alvo)) {
            $fora_periodo = true;
        }

        $status_estante = $tem_carga_csv ? ($fora_periodo ? 'fora_periodo' : 'ok') : 'sem_upload';
        $data_alvo = ($data_ini !== '' ? $data_ini : $datas_alvo[0]);

        $estante_novo = false;
        try {
            $stmtIns = $pdo->prepare("INSERT INTO lotes_na_estante (lote, regional, posto, quantidade, producao_de, triado_em, triado_por)
                SELECT ?, ?, ?, ?, ?, NOW(), ?
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM lotes_na_estante WHERE lote = ?
                )");
            $stmtIns->execute(array(
                (int)$lote,
                (int)$regional_real,
                (int)$posto_num,
                (int)$quantidade,
                $data_alvo,
                $triado_por,
                (int)$lote
            ));
            $estante_novo = $stmtIns->rowCount() > 0;
        } catch (Exception $e) {
            $estante_novo = false;
        }

        $estante_stats = array('total' => 0, 'capital' => 0, 'central' => 0, 'regional' => 0, 'poupatempo' => 0);
        $sem_upload = array('total' => 0, 'lotes' => array());
        $layout = array('correios' => array(), 'poupatempo' => array(), 'totais' => array('correios_lotes' => 0, 'poupatempo_lotes' => 0));
        try {
            $linhas_estante = obterLinhasEstanteAtiva($pdo, $data_ini, $data_fim, $datas_alvo);
            foreach ($linhas_estante as $row) {
                acumularStatsEstante($estante_stats, $row);
            }
            $layout = montarLayoutEstante($linhas_estante);
            $sem_upload = array('total' => 0, 'lotes' => array());
            if ($status_estante === 'sem_upload') {
                $sem_upload['total'] = 1;
                $sem_upload['lotes'][] = array(
                    'lote' => str_pad((string)$lote, 8, '0', STR_PAD_LEFT),
                    'posto' => str_pad((string)$posto_num, 3, '0', STR_PAD_LEFT),
                    'regional' => str_pad((string)(($regional_real !== null) ? $regional_real : (int)$regional_csv), 3, '0', STR_PAD_LEFT)
                );
            }
        } catch (Exception $e) {
            // ignore
        }

        die(json_encode(array(
            'success' => true,
            'posto' => $posto_pad,
            'posto_int' => $posto_int,
            'regional' => $regional_real,
            'regional_csv' => (int)$regional_csv,
            'regional_pad' => str_pad((string)$regional_real, 3, '0', STR_PAD_LEFT),
            'entrega' => $entrega_tipo,
            'tipo_posto' => $tipo_posto,
            'label_tipo' => $label_tipo,
            'voz' => $voz,
            'lote' => $lote,
            'quantidade' => $quantidade,
            'posto_encontrado' => $posto_encontrado,
            'codbar' => $codbar_limpo,
            'estante_novo' => $estante_novo,
            'estante' => $estante_stats,
            'sem_upload' => $sem_upload,
            'layout' => $layout,
            'status_estante' => $status_estante,
            'data_alvo' => $data_alvo,
            'data_producao' => $data_producao ? date('d-m-Y', strtotime($data_producao)) : null
        )));
    }

} catch (PDOException $e) {
    if (isset($_POST['ajax_buscar_posto'])) {
        header('Content-Type: application/json');
        die(json_encode(array('success' => false, 'erro' => 'Erro de conexao: ' . $e->getMessage())));
    }
    if (isset($_GET['resolver_display'])) {
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode(array('success' => false, 'erro' => 'Banco indisponivel: ' . $e->getMessage())));
    }
    if (isset($_GET['resolver_posto_voz'])) {
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode(array('success' => false, 'erro' => 'Banco indisponivel: ' . $e->getMessage())));
    }
}

$mapa_postos_triagem = array();
if ($dbOk) {
    try {
        $stmtMapaPostos = $pdo->query("SELECT LPAD(posto,3,'0') AS posto, CAST(regional AS UNSIGNED) AS regional, LOWER(TRIM(REPLACE(COALESCE(entrega,''),' ',''))) AS entrega FROM ciRegionais");
        while ($rowMapa = $stmtMapaPostos->fetch(PDO::FETCH_ASSOC)) {
            $postoMapa = isset($rowMapa['posto']) ? (string)$rowMapa['posto'] : '';
            if ($postoMapa === '') {
                continue;
            }
            $descricaoMapa = montarDescricaoTriagem($postoMapa, isset($rowMapa['regional']) ? (int)$rowMapa['regional'] : 0, normalizarEntregaTipoTriagem(isset($rowMapa['entrega']) ? $rowMapa['entrega'] : ''));
            $mapa_postos_triagem[$postoMapa] = array(
                'posto' => $postoMapa,
                'regional' => isset($rowMapa['regional']) ? (int)$rowMapa['regional'] : 0,
                'voz' => $descricaoMapa['voz'],
                'label_tipo' => $descricaoMapa['label_tipo'],
                'tipo_posto' => $descricaoMapa['tipo_posto'],
                'tipo_estante' => $descricaoMapa['tipo_estante']
            );
        }
        $stmtMapaPostos = null;
    } catch (Exception $eMapaPostos) {
        $mapa_postos_triagem = array();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encontra Posto v1.2.0 - Triagem Rapida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Trebuchet MS", "Tahoma", "Verdana", sans-serif;
            background: #f5f5f5;
            padding: 20px;
            padding-top: 80px;
        }

        .topo-fixo {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 12px 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .topo-fixo h1 { font-size: 18px; font-weight: 700; }
        .topo-fixo .versao {
            background: #4caf50; color: white;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 700;
        }

        .toggle-voz {
            display: inline-flex; align-items: center; gap: 8px;
            cursor: pointer; font-size: 13px; color: white;
        }
        .toggle-voz input { display: none; }
        .toggle-slider {
            position: relative; width: 36px; height: 20px;
            background: rgba(255,255,255,0.3); border-radius: 10px;
            transition: background 0.3s;
        }
        .toggle-slider:after {
            content: ''; position: absolute; top: 2px; left: 2px;
            width: 16px; height: 16px; background: white;
            border-radius: 50%; transition: transform 0.3s;
        }
        .toggle-voz input:checked + .toggle-slider { background: #4caf50; }
        .toggle-voz input:checked + .toggle-slider:after { transform: translateX(16px); }

        .area-principal { max-width: 1200px; margin: 0 auto; }

        .painel-leitura {
            background: white; border-radius: 10px;
            padding: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        .painel-leitura label {
            font-weight: 700; color: #333;
            display: block; margin-bottom: 8px; font-size: 14px;
        }
        #input_codbar {
            width: 100%; max-width: 500px;
            padding: 14px 16px; font-size: 20px;
            border: 3px solid #1a237e; border-radius: 8px;
            background: #e8eaf6; font-weight: 700; letter-spacing: 2px;
        }
        #input_codbar:focus { outline: none; border-color: #4caf50; background: #e8f5e9; }

        .painel-datas {
            background: white; border-radius: 10px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .painel-datas label { font-weight: 700; color: #333; display:block; margin-bottom:6px; font-size:13px; }
        .data-estante {
            width: 100%; max-width: 220px;
            padding: 10px 12px; font-size: 14px;
            border: 2px solid #3949ab; border-radius: 6px;
            background: #e8eaf6; font-weight: 700;
        }
        .linha-datas {
            display:flex; flex-wrap:wrap; gap:10px; align-items:center;
        }
        .nota-datas { font-size: 11px; color:#666; margin-top:6px; }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px; margin-bottom: 20px;
        }
        .stat-card {
            background: white; border-radius: 8px;
            padding: 12px 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #1a237e;
            text-align: center;
        }
        .stat-card h4 { font-size: 11px; color: #777; text-transform: uppercase; margin-bottom: 4px; }
        .stat-card .valor { font-size: 22px; font-weight: 700; color: #1a237e; }

        .painel-sem-upload {
            background: #ffffff; border-radius: 10px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .painel-sem-upload h3 { margin: 0 0 8px; font-size: 15px; color:#333; }
        .lista-lotes { display:flex; flex-wrap:wrap; gap:6px; }
        .lote-badge {
            background:#263238; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;
        }
        .lote-badge small {
            display: inline-block;
            margin-left: 6px;
            font-size: 10px;
            font-weight: 600;
            opacity: 0.88;
        }

        .painel-pendencias {
            background: #ffffff; border-radius: 10px;
            padding: 0; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .pendencias-summary {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; cursor: pointer; gap: 12px;
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            transition: background 0.2s;
        }
        .pendencias-summary:hover { background: linear-gradient(135deg, #0d1757 0%, #1a237e 100%); }
        .pendencias-summary.tem-lotes { background: linear-gradient(135deg, #b71c1c 0%, #c62828 100%); }
        .pendencias-summary.tem-lotes:hover { background: linear-gradient(135deg, #880e0e 0%, #b71c1c 100%); }
        .pendencias-summary-main { display:flex; align-items:center; gap:14px; }
        .pendencias-badge {
            font-size: 42px; font-weight: 900; color: #fff;
            line-height: 1; min-width: 52px; text-align: center;
            text-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }
        .pendencias-summary-text { display:flex; flex-direction:column; gap:3px; }
        .pendencias-summary-titulo { font-size: 14px; font-weight: 700; color:#fff; }
        .pendencias-summary-sub { font-size: 12px; color: rgba(255,255,255,0.8); }
        .pendencias-toggle {
            padding: 6px 14px; border: 2px solid rgba(255,255,255,0.6);
            background: transparent; color: #fff; border-radius: 20px;
            font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
            transition: background 0.15s;
        }
        .pendencias-toggle:hover { background: rgba(255,255,255,0.15); }
        .pendencias-conteudo { padding: 16px 20px; border-top: 2px solid #edf1f5; }
        .painel-pendencias .resumo { font-size: 12px; color:#5f6b7a; margin-bottom: 12px; }
        .linha-pendencias {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap:10px;
            margin-bottom: 12px;
        }
        .campo-pendencia label {
            font-weight: 700; color: #333; display:block; margin-bottom:6px; font-size:12px;
        }
        .campo-pendencia input,
        .campo-pendencia select {
            width: 100%;
            padding: 9px 10px;
            font-size: 13px;
            border: 1px solid #ccd6eb;
            border-radius: 6px;
            background: #fff;
        }
        .check-consolidar {
            display:flex; align-items:center; gap:8px;
            font-size:12px; color:#455a64; margin-bottom: 10px;
        }
        .check-consolidar input { width:auto; }
        .tabela-pendencias-wrap { overflow-x:auto; }
        .tabela-pendencias {
            width: 100%;
            min-width: 640px;
            border-collapse: collapse;
            background: #fff;
        }
        .tabela-pendencias th,
        .tabela-pendencias td {
            padding: 9px 10px;
            border-bottom: 1px solid #edf1f5;
            font-size: 12px;
            text-align: left;
        }
        .tabela-pendencias th {
            background: #eef3ff;
            color: #1a237e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 11px;
        }
        .vazio-pendencias {
            padding: 16px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
        .acoes-pendencias {
            display:flex; gap:10px; flex-wrap:wrap; margin-top: 12px;
        }
        .btn-acao {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-salvar { background:#2e7d32; color:#fff; }
        .btn-cancelar { background:#eceff1; color:#37474f; }
        .btn-remover-pendente { background:#ffebee; color:#b71c1c; }

        .painel-historico {
            background: #ffffff; border-radius: 10px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .painel-historico h3 { margin: 0 0 6px; font-size: 15px; color:#333; }
        .painel-historico .subtitulo { font-size: 12px; color:#5f6b7a; margin-bottom: 10px; }
        .historico-tabela-wrap {
            overflow-x: auto;
            border: 1px solid #e4e8ee;
            border-radius: 8px;
        }
        .historico-tabela {
            width: 100%;
            min-width: 640px;
            border-collapse: collapse;
            background: #fff;
        }
        .historico-tabela th,
        .historico-tabela td {
            padding: 10px 12px;
            border-bottom: 1px solid #edf1f5;
            font-size: 12px;
            text-align: left;
        }
        .historico-tabela th {
            background: #eef3ff;
            color: #1a237e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 11px;
        }
        .historico-tabela tbody tr:nth-child(even) {
            background: #fafbfd;
        }
        .historico-vazio {
            padding: 16px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .resultado-posto {
            border-radius: 12px; padding: 0; margin-bottom: 20px;
            overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: none;
        }
        .resultado-header {
            padding: 24px 28px; color: white;
            font-weight: 700; text-align: center;
        }
        .resultado-header .numero-posto {
            font-size: 56px; font-weight: 800; line-height: 1; margin-bottom: 8px;
        }
        .resultado-header .tipo-label { font-size: 24px; opacity: 0.9; }
        .resultado-body {
            background: white; padding: 16px 24px;
        }
        .resultado-body .info-linha {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px;
        }
        .resultado-body .info-linha:last-child { border-bottom: none; }
        .resultado-body .info-label { color: #777; font-weight: 600; }
        .resultado-body .info-valor { color: #333; font-weight: 700; }

        .bg-capital { background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); }
        .bg-central { background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%); }
        .bg-regional { background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%); }
        .bg-poupatempo { background: linear-gradient(135deg, #e65100 0%, #bf360c 100%); }
        .bg-desconhecido { background: linear-gradient(135deg, #616161 0%, #424242 100%); }

        .estantes-container {
            background: #ffffff; border-radius: 12px;
            padding: 20px; margin: 0 auto 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 100%;
        }
        .estantes-header {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            flex-wrap: wrap; margin-bottom: 8px;
        }
        .estantes-header h3 {
            color: #1b1f3b; font-size: 16px; font-weight: 800;
            padding-left: 10px; border-left: 4px solid #ff6f00; margin: 0;
        }
        .estantes-toggle { display: flex; gap: 8px; flex-wrap: wrap; }
        .estantes-toggle button {
            border: 2px solid #1b1f3b; background: #fff; color: #1b1f3b;
            padding: 6px 12px; border-radius: 20px; font-weight: 800; cursor: pointer;
        }
        .estantes-toggle button.ativo {
            background: #1b1f3b; color: #fff;
        }
        .estantes-resumo {
            font-size: 12px; color: #4e4e4e; margin-bottom: 12px;
        }
        .estantes-grid {
            display: grid; gap: 16px;
        }
        .estante {
            background: linear-gradient(160deg, #fff8e1 0%, #ffffff 100%);
            border: 1px solid #f1e4c8; border-radius: 12px; padding: 14px;
            display: grid; gap: 12px;
            grid-template-columns: repeat(2, minmax(320px, 1fr));
        }
        .estante[data-grupo="poupatempo"] {
            background: linear-gradient(160deg, #e8f5e9 0%, #ffffff 100%);
            border-color: #c8e6c9;
        }
        .estante-coluna {
            display: grid; gap: 10px; min-width: 320px;
        }
        .estante-titulo {
            font-weight: 800; color: #1b1f3b; text-transform: uppercase; font-size: 12px;
            letter-spacing: 0.6px;
        }
        .prateleira {
            display: grid;
            grid-template-columns: repeat(4, minmax(70px, 1fr));
            gap: 8px;
            padding: 8px;
            background: rgba(27,31,59,0.06);
            border-radius: 10px;
            border: 1px dashed rgba(27,31,59,0.15);
        }
        .slot {
            background: #1b1f3b;
            color: #fff;
            border-radius: 8px;
            padding: 6px 4px;
            text-align: center;
            box-shadow: inset 0 -2px 0 rgba(255,255,255,0.15);
        }
        .slot-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.4px;
            text-transform: uppercase; opacity: 0.8;
        }
        .slot-valor {
            font-size: 16px; font-weight: 800; margin-top: 4px;
        }
        .slot-vazio {
            background: #90a4ae;
            color: #fff;
        }

        .btn-voltar {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            background: rgba(255,255,255,0.15);
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-voltar:hover {
            background: rgba(255,255,255,0.3);
        }

        .banner-datas {
            position: sticky;
            top: 64px;
            z-index: 900;
            background: #0d47a1;
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 10px auto 16px;
            max-width: 800px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .banner-datas .datas-ativas { font-weight: 700; font-size: 13px; }
        .banner-datas button {
            background: #ffeb3b;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            font-weight: 800;
            cursor: pointer;
        }

        .overlay-datas {
            position: fixed; left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        .overlay-datas .card {
            background: #fff;
            padding: 18px;
            border-radius: 10px;
            width: 420px;
            max-width: 92%;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
        }
        .overlay-datas h3 { margin: 0 0 8px; color:#1a237e; }
        .overlay-datas .hint { font-size: 12px; color:#666; margin-bottom: 10px; }
        .overlay-datas input {
            width: 100%; padding: 10px 12px;
            border: 2px solid #3949ab; border-radius: 6px;
            background: #e8eaf6; font-weight: 700;
        }
        .overlay-datas .data-estante {
            max-width: 180px;
        }
        .overlay-datas .acoes {
            margin-top: 12px; display:flex; gap:8px; justify-content:flex-end;
        }
        .overlay-datas .btn-primario {
            background:#1a237e; color:#fff; border:none; border-radius:6px; padding:8px 12px; font-weight:800;
        }
        .overlay-datas .btn-sec {
            background:#cfd8dc; color:#333; border:none; border-radius:6px; padding:8px 12px; font-weight:700;
        }

        @media (max-width: 600px) {
            .topo-fixo { flex-wrap: wrap; gap: 8px; }
            .topo-fixo h1 { font-size: 15px; }
            #input_codbar { font-size: 16px; }
            .resultado-header .numero-posto { font-size: 40px; }
        }
    </style>
</head>
<body>

<div class="topo-fixo">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="inicio.php" class="btn-voltar">&larr; Inicio</a>
        <h1>Encontra Posto</h1>
        <span class="versao">v2.0.3</span>
        <span style="font-size:11px; opacity:0.85;">build <?php echo date('d-m-Y H:i'); ?></span>
    </div>
    <label class="toggle-voz">
        <input type="checkbox" id="toggleVoz" checked>
        <span class="toggle-slider"></span>
        Voz ativa
    </label>
</div>

<div class="area-principal">

    <div class="banner-datas" id="bannerDatas" style="display:none;">
        <div class="datas-ativas" id="datasAtivasTexto">Periodo ativo:</div>
        <button type="button" id="btnAlterarDatas">Alterar datas</button>
    </div>

    <div class="painel-leitura">
        <div style="margin-bottom:10px; display:flex; align-items:center; gap:10px;">
            <div style="flex:1;">
                <label style="font-weight:700; font-size:12px; color:#1a237e; display:block; margin-bottom:4px;">Triador (responsavel pela triagem):</label>
                <input type="text" id="triado_por_input" placeholder="Nome de quem esta escaneando..." autocomplete="off" style="width:100%; padding:8px 10px; font-size:13px; border:2px solid #9fa8da; border-radius:6px; box-sizing:border-box;">
            </div>
        </div>
        <!-- v1.2.2: posto de referência para alerta "display de outro posto" -->
        <div style="margin-bottom:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div>
                <label style="font-weight:700; font-size:12px; color:#1a237e; display:block; margin-bottom:4px;">Posto de referência <span style="font-weight:400; color:#777;">(opcional)</span>:</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="text" id="posto_referencia_input" placeholder="Ex: 758" autocomplete="off" style="width:90px; padding:8px 10px; font-size:13px; border:2px solid #ffd600; border-radius:6px; box-sizing:border-box;">
                    <span style="font-size:11px; color:#777;">Se preenchido, avisa em voz quando o posto do barcode for diferente.</span>
                </div>
            </div>
        </div>
        <label>Codigo de Barras do Pacote (19 digitos):</label>
        <input type="text" id="input_codbar" placeholder="Escaneie ou digite o codigo..." autocomplete="off" autofocus>
        <div id="indicadorFoco" style="margin-top:8px; font-size:13px; font-weight:700; color:#4caf50;">Pronto para leitura</div>
        <button type="button" id="btnCameraEP" onclick="abrirCameraEP();" style="margin-top:10px; width:100%; padding:12px; font-size:15px; font-weight:700; color:#fff; background:#1a237e; border:none; border-radius:8px; cursor:pointer;">&#128247; Ler com a c&acirc;mera (celular)</button>
    </div>

    <div class="painel-codbar" style="border:2px solid #9fa8da; border-radius:10px; padding:14px; margin-top:14px; background:#f3f4fb;">
        <label style="font-weight:700; font-size:13px; color:#1a237e; display:block; margin-bottom:6px;">&#128269; Ler display &rarr; posto <span style="font-weight:400; color:#777;">(c&oacute;digo de 35 d&iacute;gitos)</span></label>
        <input type="text" id="input_display_posto" placeholder="Escaneie o c&oacute;digo do display (35 d&iacute;gitos)..." autocomplete="off" style="width:100%; padding:10px; font-size:15px; border:2px solid #9fa8da; border-radius:6px; box-sizing:border-box;">
        <div id="resultado_display_posto" style="display:none; margin-top:10px; padding:12px; border:2px solid #9fa8da; border-radius:8px; background:#fff;"></div>
    </div>

    <div class="painel-datas">
        <label>Periodo da estante (inicio e fim):</label>
        <div class="linha-datas">
            <input type="date" id="data_ini_estante" class="data-estante">
            <input type="date" id="data_fim_estante" class="data-estante">
        </div>
        <div class="nota-datas">As leituras serao contabilizadas apenas no periodo informado.</div>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <h4>Lotes na estante</h4>
            <div class="valor" id="statTotal">0</div>
        </div>
        <div class="stat-card" style="border-left-color:#1565c0;">
            <h4>Capital</h4>
            <div class="valor" id="statCapital" style="color:#1565c0;">0</div>
        </div>
        <div class="stat-card" style="border-left-color:#6a1b9a;">
            <h4>Metropolitana</h4>
            <div class="valor" id="statCentral" style="color:#6a1b9a;">0</div>
        </div>
        <div class="stat-card" style="border-left-color:#2e7d32;">
            <h4>Regional</h4>
            <div class="valor" id="statRegional" style="color:#2e7d32;">0</div>
        </div>
        <div class="stat-card" style="border-left-color:#e65100;">
            <h4>Poupa Tempo</h4>
            <div class="valor" id="statPT" style="color:#e65100;">0</div>
        </div>
        <div class="stat-card" style="border-left-color:#b71c1c;">
            <h4>Sem Upload</h4>
            <div class="valor" id="statSemUpload" style="color:#b71c1c;">0</div>
        </div>
    </div>

    <!-- v1.2.2: Histórico de triagens -->
    <div class="painel-historico" id="painelHistorico">
        <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="toggleHistorico()">
            <h3>&#128336; Histórico de Triagens</h3>
            <button type="button" id="btnHistoricoToggle" style="background:none; border:1px solid #c5cae9; border-radius:6px; padding:4px 12px; font-size:12px; color:#1a237e; cursor:pointer; font-weight:700;">Expandir &#9660;</button>
        </div>
        <div id="historicoConteudo" style="display:none; margin-top:10px;">
            <p class="subtitulo">Últimos 150 registros de triagem — do mais recente ao mais antigo.</p>
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                <button type="button" onclick="carregarHistorico()" style="font-size:11px; padding:3px 10px; border:1px solid #9fa8da; border-radius:5px; background:#e8eaf6; color:#1a237e; cursor:pointer;">&#8635; Atualizar</button>
            </div>
            <div class="historico-tabela-wrap">
                <table class="historico-tabela">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Lote</th>
                            <th>Posto</th>
                            <th>Regional</th>
                            <th>Quantidade</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody id="corpoHistorico">
                        <tr><td colspan="6" class="historico-vazio">Clique em "Expandir" para carregar o histórico.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="painel-sem-upload" id="painelSemUpload" style="display:none;">
        <h3>📦 Lote atual sem upload no ciPostosCsv</h3>
        <div class="lista-lotes" id="listaSemUpload"></div>
    </div>

    <div class="painel-pendencias" id="painelPendenciasNaoCarregadas">
        <div class="pendencias-summary" id="pendenciasSummary" onclick="togglePainelPendencias()">
            <div class="pendencias-summary-main">
                <span class="pendencias-badge" id="pendenciasBadge">0</span>
                <div class="pendencias-summary-text">
                    <span class="pendencias-summary-titulo">Lotes não carregados</span>
                    <span class="pendencias-summary-sub" id="pendenciasCarteirasLabel">Nenhum lote pendente neste periodo</span>
                </div>
            </div>
            <button type="button" class="pendencias-toggle" id="pendenciasToggleBtn">Expandir ▼</button>
        </div>
        <div class="pendencias-conteudo" id="pendenciasConteudo" style="display:none;">
            <div class="resumo" id="resumoPendenciasNaoCarregadas">Selecione os lotes de cada responsavel e carregue separadamente.</div>
            <div class="linha-pendencias">
                <div class="campo-pendencia">
                    <label for="responsavel_pendencias">Responsavel pelos lotes selecionados</label>
                    <input type="text" id="responsavel_pendencias" placeholder="Quem produziu os lotes selecionados">
                </div>
                <div class="campo-pendencia">
                    <label for="turno_pendencias">Turno</label>
                    <select id="turno_pendencias">
                        <option>Manhã</option>
                        <option>Tarde</option>
                        <option>Noite</option>
                        <option>Madrugada</option>
                    </select>
                </div>
                <div class="campo-pendencia">
                    <label for="criado_pendencias">Data de produção</label>
                    <input type="datetime-local" id="criado_pendencias">
                </div>
                <div class="campo-pendencia">
                    <label for="periodo_pendencias">Periodo ativo</label>
                    <input type="text" id="periodo_pendencias" readonly>
                </div>
            </div>
            <label class="check-consolidar"><input type="checkbox" id="consolidar_pendencias"> Consolidar lancamentos em ciPostos por responsavel e data</label>
            <div style="display:flex; gap:8px; align-items:center; margin:8px 0 4px 0; flex-wrap:wrap;">
                <button type="button" class="btn-acao" id="btnSelecionarTodosPendencias" style="font-size:12px; padding:4px 10px; background:#e8eaf6; border:1px solid #9fa8da; color:#1a237e;">Selecionar todos</button>
                <button type="button" class="btn-acao" id="btnDesmarcarTodosPendencias" style="font-size:12px; padding:4px 10px; background:#fce4ec; border:1px solid #f48fb1; color:#880e4f;">Desmarcar todos</button>
                <button type="button" class="btn-acao" id="btnAtribuirResponsavelPendencias" style="font-size:12px; padding:4px 10px; background:#e8f5e9; border:1px solid #a5d6a7; color:#1b5e20;">Atribuir Responsavel aos Marcados</button>
                <span id="contadorSelecionadosPendencias" style="font-size:12px; color:#555; font-weight:600;"></span>
            </div>
            <div class="tabela-pendencias-wrap">
                <table class="tabela-pendencias">
                    <thead>
                        <tr>
                            <th style="width:32px;"><input type="checkbox" id="chkTodosPendencias" title="Selecionar todos"></th>
                            <th>Lote</th>
                            <th>Regional</th>
                            <th>Posto</th>
                            <th>Qtd Carteiras</th>
                            <th>Data</th>
                            <th>Responsavel</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody id="listaPendenciasNaoCarregadas">
                        <tr><td colspan="8" class="vazio-pendencias">Nenhum lote pendente neste periodo.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="acoes-pendencias">
                <button type="button" class="btn-acao btn-salvar" id="btnSalvarPendencias">Carregar Selecionados</button>
                <button type="button" class="btn-acao btn-cancelar" id="btnLimparPendencias">Limpar fila do periodo</button>
            </div>
            <div id="msgSalvamentoPendencias" style="display:none; margin-top:12px; padding:12px 16px; border-radius:8px; font-weight:700; font-size:14px;"></div>
            <div id="historicoCarregamentos" style="margin-top:14px;"></div>
        </div>
    </div>

    <div class="resultado-posto" id="resultadoPosto">
        <div class="resultado-header" id="resultadoHeader">
            <div class="numero-posto" id="resultadoNumero"></div>
            <div class="tipo-label" id="resultadoTipo"></div>
        </div>
        <div class="resultado-body" id="resultadoBody"></div>
    </div>

</div>

<audio id="audioBeep" src="beep.mp3" preload="auto"></audio>

<div class="overlay-datas" id="overlayDatas" style="display:flex;">
    <div class="card">
        <h3>Periodo da Estante</h3>
        <div class="hint">Informe o periodo que sera triado (formato yyyy-mm-dd).</div>
        <div class="linha-datas">
            <input type="date" id="data_ini_modal" class="data-estante">
            <input type="date" id="data_fim_modal" class="data-estante">
        </div>
        <div class="acoes">
            <button type="button" class="btn-sec" id="btnCancelarDatas">Cancelar</button>
            <button type="button" class="btn-primario" id="btnConfirmarDatas">Aplicar</button>
        </div>
    </div>
</div>

<script>
var vozAtiva = true;
var estanteLayout = { correios: {}, poupatempo: {}, totais: {} };
var estanteView = 'todas';
var contTotal = 0;
var contCapital = 0;
var contCentral = 0;
var contRegional = 0;
var contPT = 0;
var contSemUpload = 0;
var lotesSemUpload = [];
var pendenciasNaoCarregadas = [];
var audioFilaAtiva = false;
var audioFila = [];
var ultimaFalaTexto = '';
var ultimaFalaEm = 0;
var ultimaFalaCodbar = '';
var ultimaFalaLeituraId = 0;
var leiturasJaVocalizadas = {};
var ordemLeiturasVocalizadas = [];
var ultimaLeituraCodbar = '';
var ultimaLeituraEm = 0;
var ultimaLeituraId = 0;
var codigosEmLeitura = {};
var mapaPostosTriagem = <?php echo json_encode($mapa_postos_triagem); ?>;

var leituraFila = [];
var leituraAtiva = false;

function formatarHoje() {
    var d = new Date();
    var yyyy = d.getFullYear();
    var mm = (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1);
    var dd = (d.getDate() < 10 ? '0' : '') + d.getDate();
    return yyyy + '-' + mm + '-' + dd;
}

function formatarDataBr(valor) {
    var texto = String(valor || '').trim();
    var m = texto.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) {
        return m[3] + '-' + m[2] + '-' + m[1];
    }
    return texto;
}

function formatarDateTimeLocal(data) {
    var yyyy = data.getFullYear();
    var mm = String(data.getMonth() + 1).padStart(2, '0');
    var dd = String(data.getDate()).padStart(2, '0');
    var hh = String(data.getHours()).padStart(2, '0');
    var ii = String(data.getMinutes()).padStart(2, '0');
    return yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + ii;
}

function chavePendenciasPeriodo() {
    var chave = periodoAtualKey();
    return chave ? ('encontra_posto_pendencias_' + chave) : '';
}

function normalizarPendencia(item) {
    if (!item) return null;
    var lote = String(item.lote || '').replace(/\D+/g, '').padStart(8, '0');
    var regional = String(item.regional || '').replace(/\D+/g, '').padStart(3, '0');
    var posto = String(item.posto || '').replace(/\D+/g, '').padStart(3, '0');
    var quantidade = parseInt(item.quantidade, 10);
    if (!lote || !regional || !posto || isNaN(quantidade) || quantidade <= 0) {
        return null;
    }
    return {
        codbar: String(item.codbar || '').replace(/\D+/g, ''),
        lote: lote,
        regional: regional,
        posto: posto,
        quantidade: quantidade,
        dataexp: String(item.dataexp || '').trim(),
        responsavel: String(item.responsavel || '').trim()
    };
}

function salvarPendenciasPeriodo() {
    var chave = chavePendenciasPeriodo();
    if (!chave) return;
    localStorage.setItem(chave, JSON.stringify(pendenciasNaoCarregadas));
}

function limparPendenciasPeriodoPersistidas() {
    var chave = chavePendenciasPeriodo();
    if (chave) {
        localStorage.removeItem(chave);
    }
}

function atualizarPeriodoPendencias() {
    var campo = document.getElementById('periodo_pendencias');
    var ini = obterDataIni();
    var fim = obterDataFim() || ini;
    if (!campo) return;
    campo.value = ini ? (formatarDataBr(ini) + ' a ' + formatarDataBr(fim)) : '';
}

function getIndicesSelecionadosPendencias() {
    var corpo = document.getElementById('listaPendenciasNaoCarregadas');
    if (!corpo) return [];
    var chks = corpo.querySelectorAll('input.chk-pend-item:checked');
    var indices = [];
    for (var i = 0; i < chks.length; i++) {
        var idx = parseInt(chks[i].getAttribute('data-idx'), 10);
        if (!isNaN(idx)) indices.push(idx);
    }
    return indices;
}

function atualizarContadorSelecionadosPendencias() {
    var corpo = document.getElementById('listaPendenciasNaoCarregadas');
    var contador = document.getElementById('contadorSelecionadosPendencias');
    var btnSalvar = document.getElementById('btnSalvarPendencias');
    if (!corpo || !contador) return;
    var total = corpo.querySelectorAll('input.chk-pend-item').length;
    var sel = corpo.querySelectorAll('input.chk-pend-item:checked').length;
    contador.textContent = sel > 0 ? (sel + ' de ' + total + ' selecionado(s)') : (total > 0 ? total + ' lote(s) na fila — nenhum selecionado' : '');
    if (btnSalvar) btnSalvar.textContent = sel > 0 ? ('Carregar Selecionados (' + sel + ')') : 'Carregar Selecionados';
}

function chaveHistoricoCarregamentos() {
    var ini = obterDataIni();
    var fim = obterDataFim() || ini;
    return ini ? ('hist_carg_' + ini + '_' + fim) : null;
}

function adicionarCarregamentoHistorico(responsavel, turno, criado, pacotes) {
    var chave = chaveHistoricoCarregamentos();
    if (!chave) return;
    var hist = [];
    try { hist = JSON.parse(localStorage.getItem(chave) || '[]'); } catch (e) { hist = []; }
    hist.push({ responsavel: responsavel, turno: turno, criado: criado, qtd: pacotes.length, lotes: pacotes, em: new Date().toISOString() });
    localStorage.setItem(chave, JSON.stringify(hist));
    renderizarHistoricoCarregamentos();
}

function renderizarHistoricoCarregamentos() {
    var div = document.getElementById('historicoCarregamentos');
    if (!div) return;
    var chave = chaveHistoricoCarregamentos();
    var hist = [];
    if (chave) { try { hist = JSON.parse(localStorage.getItem(chave) || '[]'); } catch (e) { hist = []; } }
    if (!hist.length) { div.innerHTML = ''; return; }
    var html = '<div style="border-top:2px solid #4caf50; margin-top:10px; padding-top:10px;">';
    html += '<strong style="font-size:13px; color:#1b5e20;">Carregamentos realizados neste periodo:</strong>';
    html += '<table style="width:100%; border-collapse:collapse; margin-top:6px; font-size:12px;">';
    html += '<tr style="background:#e8f5e9;"><th style="padding:4px 6px; text-align:left;">Responsavel</th><th style="padding:4px 6px;">Turno</th><th style="padding:4px 6px;">Data producao</th><th style="padding:4px 6px;">Lotes</th><th style="padding:4px 6px;">Horario carga</th></tr>';
    for (var i = 0; i < hist.length; i++) {
        var h = hist[i];
        var horario = h.em ? (new Date(h.em)).toLocaleString('pt-BR') : '';
        html += '<tr style="border-bottom:1px solid #c8e6c9;">' +
            '<td style="padding:4px 6px; font-weight:600;">' + (h.responsavel || '') + '</td>' +
            '<td style="padding:4px 6px; text-align:center;">' + (h.turno || '') + '</td>' +
            '<td style="padding:4px 6px; text-align:center;">' + (h.criado || '') + '</td>' +
            '<td style="padding:4px 6px; text-align:center; font-weight:bold;">' + (h.qtd || 0) + '</td>' +
            '<td style="padding:4px 6px; text-align:center; color:#555;">' + horario + '</td>' +
            '</tr>';
    }
    html += '</table></div>';
    div.innerHTML = html;
}

function atualizarSummaryPendencias() {
    var badge = document.getElementById('pendenciasBadge');
    var labelCart = document.getElementById('pendenciasCarteirasLabel');
    var summary = document.getElementById('pendenciasSummary');
    var total = pendenciasNaoCarregadas.length;
    var carteiras = 0;
    for (var k = 0; k < pendenciasNaoCarregadas.length; k++) {
        carteiras += parseInt(pendenciasNaoCarregadas[k].quantidade, 10) || 0;
    }
    if (badge) badge.textContent = total;
    if (labelCart) {
        if (total === 0) {
            labelCart.textContent = 'Nenhum lote pendente neste periodo';
        } else {
            labelCart.textContent = total + ' lote(s) aguardando \u2014 ' + carteiras + ' carteiras no total';
        }
    }
    if (summary) {
        if (total > 0) {
            summary.className = 'pendencias-summary tem-lotes';
        } else {
            summary.className = 'pendencias-summary';
        }
    }
}

function togglePainelPendencias() {
    var conteudo = document.getElementById('pendenciasConteudo');
    var btnToggle = document.getElementById('pendenciasToggleBtn');
    if (!conteudo) return;
    if (conteudo.style.display === 'none' || conteudo.style.display === '') {
        conteudo.style.display = 'block';
        if (btnToggle) btnToggle.textContent = 'Recolher \u25b2';
    } else {
        conteudo.style.display = 'none';
        if (btnToggle) btnToggle.textContent = 'Expandir \u25bc';
    }
}

function expandirPainelPendencias() {
    var conteudo = document.getElementById('pendenciasConteudo');
    var btnToggle = document.getElementById('pendenciasToggleBtn');
    if (conteudo && (conteudo.style.display === 'none' || conteudo.style.display === '')) {
        conteudo.style.display = 'block';
        if (btnToggle) btnToggle.textContent = 'Recolher \u25b2';
    }
}

function renderizarPendenciasNaoCarregadas() {
    var corpo = document.getElementById('listaPendenciasNaoCarregadas');
    var resumo = document.getElementById('resumoPendenciasNaoCarregadas');
    if (!corpo) return;
    atualizarPeriodoPendencias();
    atualizarSummaryPendencias();
    if (!pendenciasNaoCarregadas.length) {
        corpo.innerHTML = '<tr><td colspan="8" class="vazio-pendencias">Nenhum lote pendente neste periodo.</td></tr>';
        atualizarContadorSelecionadosPendencias();
        renderizarHistoricoCarregamentos();
        return;
    }
    var html = '';
    for (var i = 0; i < pendenciasNaoCarregadas.length; i++) {
        var item = pendenciasNaoCarregadas[i];
        html += '<tr>' +
            '<td style="text-align:center;"><input type="checkbox" class="chk-pend-item" data-idx="' + i + '" onchange="atualizarContadorSelecionadosPendencias()"></td>' +
            '<td>' + item.lote + '</td>' +
            '<td>' + item.regional + '</td>' +
            '<td>' + item.posto + '</td>' +
            '<td style="text-align:right; font-weight:700;">' + item.quantidade + '</td>' +
            '<td>' + formatarDataBr(item.dataexp) + '</td>' +
            '<td>' + (item.responsavel || '') + '</td>' +
            '<td><button type="button" class="btn-acao btn-remover-pendente" data-remover-pendente="' + i + '">Remover</button></td>' +
            '</tr>';
    }
    corpo.innerHTML = html;
    if (resumo) {
        resumo.textContent = pendenciasNaoCarregadas.length + ' lote(s) nao carregados na fila. Selecione quais deseja carregar para um responsavel.';
    }
    atualizarContadorSelecionadosPendencias();
    renderizarHistoricoCarregamentos();
}

function carregarPendenciasPeriodo() {
    var chave = chavePendenciasPeriodo();
    pendenciasNaoCarregadas = [];
    if (!chave) {
        renderizarPendenciasNaoCarregadas();
        return;
    }
    try {
        var bruto = localStorage.getItem(chave);
        var lista = bruto ? JSON.parse(bruto) : [];
        if (Array.isArray(lista)) {
            for (var i = 0; i < lista.length; i++) {
                var item = normalizarPendencia(lista[i]);
                if (item) pendenciasNaoCarregadas.push(item);
            }
        }
    } catch (e) {
        pendenciasNaoCarregadas = [];
    }
    renderizarPendenciasNaoCarregadas();
}

function adicionarPendenciaNaoCarregada(item) {
    var normalizado = normalizarPendencia(item);
    if (!normalizado) return false;
    for (var i = 0; i < pendenciasNaoCarregadas.length; i++) {
        // Mesmo codigo de barras = mesma leitura (barcode unico por lote)
        if (pendenciasNaoCarregadas[i].codbar && normalizado.codbar &&
            pendenciasNaoCarregadas[i].codbar === normalizado.codbar) {
            return false;
        }
        // Fallback sem codbar: mesmo lote+regional+posto = mesmo lote (nao duplicar)
        if (!pendenciasNaoCarregadas[i].codbar && !normalizado.codbar &&
            pendenciasNaoCarregadas[i].lote === normalizado.lote &&
            pendenciasNaoCarregadas[i].regional === normalizado.regional &&
            pendenciasNaoCarregadas[i].posto === normalizado.posto) {
            return false;
        }
    }
    pendenciasNaoCarregadas.push(normalizado);
    salvarPendenciasPeriodo();
    renderizarPendenciasNaoCarregadas();
    expandirPainelPendencias();
    return true;
}

function obterDataIni() {
    var input = document.getElementById('data_ini_estante');
    return input ? input.value.trim() : '';
}

function obterDataFim() {
    var input = document.getElementById('data_fim_estante');
    return input ? input.value.trim() : '';
}

function salvarDatasAlvo() {
    var ini = obterDataIni();
    var fim = obterDataFim();
    if (ini && !fim) fim = ini;
    if (fim && !ini) ini = fim;
    if (ini && fim && ini > fim) {
        var tmp = ini;
        ini = fim;
        fim = tmp;
    }
    if (ini) {
        localStorage.setItem('estante_data_ini', ini);
        localStorage.setItem('estante_data_fim', fim);
        var chave = ini + '|' + fim;
        if (localStorage.getItem('estante_periodo_confirmado') !== chave) {
            localStorage.removeItem('estante_periodo_confirmado');
        }
    }
    atualizarBannerDatas();
    carregarEstanteInicial();
    carregarPendenciasPeriodo();
}

function periodoAtualKey() {
    var ini = obterDataIni();
    var fim = obterDataFim();
    if (ini && !fim) fim = ini;
    if (fim && !ini) ini = fim;
    if (!ini) return '';
    if (ini > fim) {
        var tmp = ini;
        ini = fim;
        fim = tmp;
    }
    return ini + '|' + fim;
}

function periodoConfirmado() {
    var chave = periodoAtualKey();
    if (!chave) return false;
    return localStorage.getItem('estante_periodo_confirmado') === chave;
}

function confirmarPeriodoAtual() {
    var chave = periodoAtualKey();
    if (!chave) return;
    localStorage.setItem('estante_periodo_confirmado', chave);
}

function atualizarBannerDatas() {
    var banner = document.getElementById('bannerDatas');
    var texto = document.getElementById('datasAtivasTexto');
    var ini = obterDataIni();
    var fim = obterDataFim();
    if (!banner || !texto) return;
    if (ini) {
        var confirmado = periodoConfirmado();
        texto.textContent = 'Periodo ativo: ' + formatarDataBr(ini) + ' a ' + formatarDataBr(fim || ini) + (confirmado ? '' : ' (nao confirmado)');
        banner.style.display = 'flex';
    } else {
        banner.style.display = 'none';
    }
}

function abrirModalDatas() {
    var overlay = document.getElementById('overlayDatas');
    var inputIni = document.getElementById('data_ini_modal');
    var inputFim = document.getElementById('data_fim_modal');
    if (inputIni) {
        inputIni.value = obterDataIni() || '';
        inputIni.focus();
    }
    if (inputFim) {
        inputFim.value = obterDataFim() || '';
    }
    if (overlay) overlay.style.display = 'flex';
}

function fecharModalDatas() {
    var overlay = document.getElementById('overlayDatas');
    if (overlay) overlay.style.display = 'none';
}

document.getElementById('toggleVoz').onchange = function() {
    vozAtiva = this.checked;
};

function atualizarIndicadorFoco() {
    var campo = document.getElementById('input_codbar');
    var indicador = document.getElementById('indicadorFoco');
    if (!indicador) return;
    if (document.activeElement === campo) {
        indicador.textContent = 'Pronto para leitura';
        indicador.style.color = '#4caf50';
    } else {
        indicador.textContent = 'Toque para ativar leitura';
        indicador.style.color = '#f44336';
    }
}

function processarCodigoBruto(valor) {
    var val = (valor || '').replace(/\D+/g, '');
    if (val.length < 19) {
        return;
    }
    if (val.length > 19) {
        val = val.slice(-19);
    }
    if (codigosEmLeitura[val]) {
        return;
    }
    if (ultimaLeituraCodbar === val && (Date.now() - ultimaLeituraEm) < 1500) {
        return;
    }
    ultimaLeituraCodbar = val;
    ultimaLeituraEm = Date.now();
    ultimaLeituraId++;
    codigosEmLeitura[val] = true;
    buscarPosto(val, ultimaLeituraId);
}

document.getElementById('input_codbar').addEventListener('input', function() {
    var val = this.value;
    if (!val) return;
    var limpo = val.replace(/\D+/g, '');
    if (limpo.length >= 19) {
        this.value = '';
        processarCodigoBruto(limpo);
    }
});

document.getElementById('input_codbar').onkeydown = function(ev) {
    if (ev.keyCode === 13) {
        var val = this.value;
        this.value = '';
        processarCodigoBruto(val);
    }
};

document.getElementById('input_codbar').onfocus = function() {
    atualizarIndicadorFoco();
};

document.getElementById('input_codbar').onblur = function() {
    atualizarIndicadorFoco();
};

document.addEventListener('keydown', function(ev) {
    var campo = document.getElementById('input_codbar');
    if (!campo) return;
    var ativo = document.activeElement;
    if (ativo && ativo !== campo) {
        var tag = ativo.tagName ? ativo.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button') return;
    }
    if (ativo !== campo) campo.focus();
});

/* Leitor de display (35 digitos) -> posto associado. Secao independente da triagem. */
(function() {
    var inp = document.getElementById('input_display_posto');
    var box = document.getElementById('resultado_display_posto');
    if (!inp || !box) return;
    var seqVoz = 900000; // ids altos p/ nao colidir com leituras de lote
    var timer = null;

    function escDisp(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function soDigDisp(s) { return String(s || '').replace(/\D+/g, ''); }
    function mostrarDisp(html, cor) {
        box.style.display = 'block';
        box.style.borderColor = cor || '#9fa8da';
        box.innerHTML = html;
    }
    function consultarDisp(codigo) {
        var d = soDigDisp(codigo);
        if (d.length > 35) d = d.slice(-35);
        if (d.length !== 35) {
            mostrarDisp('C&oacute;digo inv&aacute;lido: precisa de 35 d&iacute;gitos (lido ' + d.length + ').', '#e53935');
            return;
        }
        mostrarDisp('Consultando display...', '#9fa8da');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'encontra_posto.php?resolver_display=1&leitura=' + encodeURIComponent(d), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            var resp = null;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = null; }
            if (!resp || !resp.success) {
                mostrarDisp('Erro ao consultar' + (resp && resp.erro ? ': ' + escDisp(resp.erro) : '') + '.', '#e53935');
                return;
            }
            if (!resp.encontrado) {
                mostrarDisp('Display n&atilde;o cadastrado.<br><span style="font-size:12px;color:#777;">CEP ' + escDisp(resp.cep) + ' &middot; seq ' + escDisp(resp.sequencial) + '</span>', '#e53935');
                falar('Display n\u00e3o cadastrado', d, ++seqVoz);
                return;
            }
            var nome = resp.nome ? escDisp(resp.nome) : '';
            mostrarDisp(
                '<div style="font-size:13px;color:#777;">Posto associado ao display</div>' +
                '<div style="font-size:34px;font-weight:800;color:#1a237e;line-height:1.1;">' + escDisp(String(resp.posto)) + '</div>' +
                (nome ? '<div style="font-size:15px;font-weight:600;color:#283593;margin-top:2px;">' + nome + '</div>' : '') +
                '<div style="font-size:12px;color:#777;margin-top:6px;">CEP ' + escDisp(resp.cep) + ' &middot; seq ' + escDisp(resp.sequencial) + '</div>',
                '#2e7d32'
            );
            falar(resp.voz || ('Posto ' + resp.posto_num), d, ++seqVoz);
        };
        xhr.send();
    }

    inp.addEventListener('input', function() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function() {
            timer = null;
            var d = soDigDisp(inp.value);
            if (d.length >= 35) { consultarDisp(inp.value); inp.value = ''; }
        }, 90);
    });
    inp.addEventListener('keydown', function(ev) {
        if (ev.keyCode === 13) {
            ev.preventDefault();
            if (timer) { clearTimeout(timer); timer = null; }
            var d = soDigDisp(inp.value);
            if (d.length === 0) return;
            consultarDisp(inp.value);
            inp.value = '';
        }
    });
    // Exposto para o leitor por camera (abrirCameraEP) chamar a consulta de display.
    window.lerDisplayPosto = consultarDisp;
})();

function falar(texto, codbar, leituraId) {
    if (!vozAtiva) return;
    if (typeof speechSynthesis === 'undefined') return;
    texto = String(texto || '').trim();
    if (texto === '') return;

    if (leituraId && leiturasJaVocalizadas[leituraId]) {
        return;
    }

    var codbarAtual = String(codbar || '').replace(/\D+/g, '');

    ultimaFalaTexto = texto;
    ultimaFalaEm = Date.now();
    if (leituraId) {
        ultimaFalaLeituraId = leituraId;
        leiturasJaVocalizadas[leituraId] = true;
        ordemLeiturasVocalizadas.push(leituraId);
        while (ordemLeiturasVocalizadas.length > 200) {
            var leituraAntiga = ordemLeiturasVocalizadas.shift();
            delete leiturasJaVocalizadas[leituraAntiga];
        }
    }
    if (codbarAtual) {
        ultimaFalaCodbar = codbarAtual;
    }

    try {
        audioFila = [];
        audioFilaAtiva = false;
        speechSynthesis.cancel();
        if (typeof speechSynthesis.resume === 'function') {
            speechSynthesis.resume();
        }
    } catch (eCancel) {}

    var utt = new SpeechSynthesisUtterance(texto);
    utt.lang = 'pt-BR';
    utt.rate = 1.7;
    utt.pitch = 1;
    utt.volume = 1;

    utt.onend = function() { audioFilaAtiva = false; };
    utt.onerror = function() { audioFilaAtiva = false; };
    audioFilaAtiva = true;
    speechSynthesis.speak(utt);
}

function processarFilaVoz() {
    if (audioFilaAtiva) return;
    if (audioFila.length === 0) return;
    audioFilaAtiva = true;
    var utt = audioFila.shift();
    utt.onend = function() { audioFilaAtiva = false; processarFilaVoz(); };
    utt.onerror = function() { audioFilaAtiva = false; processarFilaVoz(); };
    speechSynthesis.speak(utt);
}

function tocarBeep() {
    try {
        var audio = document.getElementById('audioBeep');
        audio.currentTime = 0;
        audio.play();
    } catch (e) {}
}

function preverVozLocal(codbar) {
    var limpo = String(codbar || '').replace(/\D+/g, '');
    var postoPad;
    var infoPosto;
    if (limpo.length < 14) {
        return '';
    }
    postoPad = limpo.substr(11, 3);
    infoPosto = mapaPostosTriagem && mapaPostosTriagem[postoPad] ? mapaPostosTriagem[postoPad] : null;
    if (infoPosto && infoPosto.voz) {
        return String(infoPosto.voz);
    }
    return '';
}

function falarPrevisaoLeitura(codbar, leituraId) {
    var vozPrevista = preverVozLocal(codbar);
    if (!vozPrevista) return;
    falar(vozPrevista, codbar, leituraId);
}

function finalizarLeitura(codbarConcluido) {
    var codigo = String(codbarConcluido || '').replace(/\D+/g, '');
    if (codigo) {
        delete codigosEmLeitura[codigo];
    }
    leituraAtiva = false;
    if (leituraFila.length > 0) {
        var prox = leituraFila.shift();
        if (prox && typeof prox === 'object') {
            buscarPosto(prox.codbar, prox.leituraId || 0);
            return;
        }
        buscarPosto(prox, 0);
    }
}

function extrairDadosLocaisCodbar(codbar) {
    var limpo = String(codbar || '').replace(/\D+/g, '');
    var postoPad;
    var infoPosto;
    if (limpo.length < 19) {
        return null;
    }
    limpo = limpo.slice(-19);
    postoPad = limpo.substr(11, 3);
    infoPosto = mapaPostosTriagem && mapaPostosTriagem[postoPad] ? mapaPostosTriagem[postoPad] : null;
    return {
        codbar: limpo,
        lote: limpo.substr(0, 8),
        regional_csv: limpo.substr(8, 3),
        posto: postoPad,
        quantidade: limpo.substr(14, 5),
        info_posto: infoPosto
    };
}

function aplicarCabecalhoResultado(dados) {
    var header = document.getElementById('resultadoHeader');
    var numDiv = document.getElementById('resultadoNumero');
    var tipoDiv = document.getElementById('resultadoTipo');
    if (!header || !numDiv || !tipoDiv || !dados) return;

    header.className = 'resultado-header';
    numDiv.style.fontSize = '';
    tipoDiv.style.fontSize = '';
    tipoDiv.style.fontWeight = '';

    if (dados.entrega === 'poupatempo') {
        header.className += ' bg-poupatempo';
        numDiv.textContent = 'Posto ' + dados.posto;
        tipoDiv.textContent = 'Poupa Tempo';
    } else if (dados.regional === 0) {
        header.className += ' bg-capital';
        numDiv.textContent = 'Posto ' + dados.posto;
        tipoDiv.textContent = dados.label_tipo || 'Capital';
    } else if (dados.regional === 999) {
        header.className += ' bg-central';
        numDiv.textContent = 'Posto ' + dados.posto;
        tipoDiv.textContent = dados.label_tipo || 'Central';
    } else if (dados.posto_encontrado) {
        header.className += ' bg-regional';
        numDiv.textContent = 'Regional ' + dados.regional_pad;
        tipoDiv.textContent = 'Posto ' + dados.posto;
        numDiv.style.fontSize = '64px';
        tipoDiv.style.fontSize = '18px';
        tipoDiv.style.fontWeight = '700';
    } else {
        header.className += ' bg-desconhecido';
        numDiv.textContent = 'Posto ' + dados.posto;
        tipoDiv.textContent = dados.label_tipo || 'Posto não localizado';
    }
}

function exibirResultadoParcial(codbar) {
    var dadosLocais = extrairDadosLocaisCodbar(codbar);
    var div = document.getElementById('resultadoPosto');
    var body = document.getElementById('resultadoBody');
    var infoPosto;
    if (!dadosLocais || !div || !body) return;
    infoPosto = dadosLocais.info_posto || null;
    aplicarCabecalhoResultado({
        entrega: infoPosto ? infoPosto.tipo_posto : null,
        regional: infoPosto ? parseInt(infoPosto.regional, 10) || 0 : parseInt(dadosLocais.regional_csv, 10) || 0,
        regional_pad: infoPosto ? String(infoPosto.regional || '').replace(/\D+/g, '').padStart(3, '0') : dadosLocais.regional_csv,
        posto: dadosLocais.posto,
        posto_encontrado: !!infoPosto,
        label_tipo: infoPosto ? String(infoPosto.label_tipo || '') : ''
    });
    body.innerHTML = '<div class="info-linha"><span class="info-label">Leitura</span><span class="info-valor">Processando...</span></div>';
    div.style.display = 'block';
}

function obterTextoVozResultado(dados) {
    if (!dados) return '';
    if (dados.status_estante === 'fora_periodo') return 'Lote de outra data';
    if (dados.status_estante === 'sem_upload') return 'Lote não carregado';
    /* v1.2.2: alerta "display de outro posto" quando posto_referencia está definido */
    var refInput = document.getElementById('posto_referencia_input');
    var refVal = refInput ? String(refInput.value).replace(/\D+/g, '').replace(/^0+/, '') : '';
    if (refVal !== '') {
        var postoScan = String(dados.posto || '').replace(/\D+/g, '').replace(/^0+/, '');
        if (postoScan !== '' && postoScan !== refVal) {
            return 'display de outro posto';
        }
    }
    return String(dados.voz || '').trim();
}

function buscarPosto(codbar, leituraId) {
    var dataIni = obterDataIni();
    var dataFim = obterDataFim();
    var vozResposta = '';
    if (!dataIni) {
        delete codigosEmLeitura[String(codbar || '').replace(/\D+/g, '')];
        exibirErro('Informe o periodo da estante');
        return;
    }
    if (!periodoConfirmado()) {
        delete codigosEmLeitura[String(codbar || '').replace(/\D+/g, '')];
        exibirErro('Confirme o periodo da estante');
        abrirModalDatas();
        return;
    }
    if (!dataFim) {
        dataFim = dataIni;
    }
    if (dataIni > dataFim) {
        var tmp = dataIni;
        dataIni = dataFim;
        dataFim = tmp;
    }
    exibirResultadoParcial(codbar);
    falarPrevisaoLeitura(codbar, leituraId);
    if (leituraAtiva) {
        tocarBeep();
        leituraFila.push({ codbar: codbar, leituraId: leituraId || 0 });
        return;
    }
    leituraAtiva = true;
    tocarBeep();
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'encontra_posto.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        exibirResultado(resp);
                        vozResposta = obterTextoVozResultado(resp);
                        if (vozResposta) {
                            falar(vozResposta, codbar, leituraId);
                        }
                    } else {
                        exibirErro(resp.erro || 'Erro desconhecido');
                    }
                } catch (e) {
                    exibirErro('Erro ao processar resposta');
                }
            } else {
                exibirErro('Erro de conexao');
            }
            finalizarLeitura(codbar);
        }
    };
    var triadoPorEl = document.getElementById('triado_por_input');
    var triadoPorVal = triadoPorEl ? triadoPorEl.value.trim() : '';
    xhr.send('ajax_buscar_posto=1&codbar=' + encodeURIComponent(codbar) + '&data_ini=' + encodeURIComponent(dataIni) + '&data_fim=' + encodeURIComponent(dataFim) + '&triado_por=' + encodeURIComponent(triadoPorVal));
}

function exibirResultado(dados) {
    var div = document.getElementById('resultadoPosto');
    var body = document.getElementById('resultadoBody');

    aplicarCabecalhoResultado(dados);

    body.innerHTML = '';

    if (dados.voz) {
        var linhaVoz = document.createElement('div');
        linhaVoz.className = 'info-linha';
        var labelVoz = document.createElement('span');
        labelVoz.className = 'info-label';
        labelVoz.textContent = 'Vocalizar';
        var valorVoz = document.createElement('span');
        valorVoz.className = 'info-valor';
        valorVoz.textContent = dados.voz;
        linhaVoz.appendChild(labelVoz);
        linhaVoz.appendChild(valorVoz);
        body.appendChild(linhaVoz);
    }

    if (dados.estante_novo === false) {
        var linhaLido = document.createElement('div');
        linhaLido.className = 'info-linha';
        var labelLido = document.createElement('span');
        labelLido.className = 'info-label';
        labelLido.textContent = 'Status';
        var valorLido = document.createElement('span');
        valorLido.className = 'info-valor';
        valorLido.textContent = 'Lote ja contabilizado';
        linhaLido.appendChild(labelLido);
        linhaLido.appendChild(valorLido);
        body.appendChild(linhaLido);
    }

    if (dados.status_estante === 'sem_upload') {
        var linhaSem = document.createElement('div');
        linhaSem.className = 'info-linha';
        var labelSem = document.createElement('span');
        labelSem.className = 'info-label';
        labelSem.textContent = 'Status';
        var valorSem = document.createElement('span');
        valorSem.className = 'info-valor';
        valorSem.textContent = 'Lote não carregado no ciPostosCsv';
        linhaSem.appendChild(labelSem);
        linhaSem.appendChild(valorSem);
        body.appendChild(linhaSem);

        var linhaFila = document.createElement('div');
        linhaFila.className = 'info-linha';
        var labelFila = document.createElement('span');
        labelFila.className = 'info-label';
        labelFila.textContent = 'Fila';
        var valorFila = document.createElement('span');
        valorFila.className = 'info-valor';
        var responsavelAtual = document.getElementById('responsavel_pendencias');
        var adicionado = adicionarPendenciaNaoCarregada({
            codbar: dados.codbar || '',
            lote: dados.lote || '',
            regional: dados.regional_csv || dados.regional_pad || dados.regional || '',
            posto: dados.posto || '',
            quantidade: dados.quantidade || 0,
            dataexp: dados.data_alvo || '',
            responsavel: responsavelAtual ? responsavelAtual.value.trim() : ''
        });
        valorFila.textContent = adicionado ? 'Lote adicionado a fila de nao carregados' : 'Lote ja estava na fila do periodo';
        linhaFila.appendChild(labelFila);
        linhaFila.appendChild(valorFila);
        body.appendChild(linhaFila);
    }

    if (dados.status_estante === 'fora_periodo') {
        var linhaFora = document.createElement('div');
        linhaFora.className = 'info-linha';
        var labelFora = document.createElement('span');
        labelFora.className = 'info-label';
        labelFora.textContent = 'Status';
        var valorFora = document.createElement('span');
        valorFora.className = 'info-valor';
        valorFora.textContent = 'Lote de outra data';
        linhaFora.appendChild(labelFora);
        linhaFora.appendChild(valorFora);
        body.appendChild(linhaFora);
    }

    if (dados.data_producao) {
        var linhaData = document.createElement('div');
        linhaData.className = 'info-linha';
        var labelData = document.createElement('span');
        labelData.className = 'info-label';
        labelData.textContent = 'Data producao';
        var valorData = document.createElement('span');
        valorData.className = 'info-valor';
        valorData.textContent = dados.data_producao;
        linhaData.appendChild(labelData);
        linhaData.appendChild(valorData);
        body.appendChild(linhaData);
    }

    div.style.display = 'block';

    if (dados.estante) {
        contTotal = dados.estante.total || 0;
        contCapital = dados.estante.capital || 0;
        contCentral = dados.estante.central || 0;
        contRegional = dados.estante.regional || 0;
        contPT = dados.estante.poupatempo || 0;
    }
    if (dados.sem_upload) {
        if (dados.status_estante === 'sem_upload') {
            var novoLoteSem = {
                lote: String(dados.lote || '').trim(),
                posto: String(dados.posto || '').trim(),
                regional: String(dados.regional_csv || dados.regional_pad || dados.regional || '').replace(/\D+/g, '').padStart(3, '0')
            };
            var jaExisteSem = false;
            for (var kk = 0; kk < lotesSemUpload.length; kk++) {
                if (lotesSemUpload[kk].lote === novoLoteSem.lote && lotesSemUpload[kk].posto === novoLoteSem.posto) {
                    jaExisteSem = true;
                    break;
                }
            }
            if (!jaExisteSem) {
                contSemUpload += 1;
                lotesSemUpload.push(novoLoteSem);
            }
        }
        renderizarSemUpload();
    }

    if (dados.layout) {
        estanteLayout = dados.layout || { correios: {}, poupatempo: {}, totais: {} };
        renderizarEstantes();
    }

    atualizarStats();
    document.getElementById('input_codbar').focus();
}

function exibirErro(msg) {
    var div = document.getElementById('resultadoPosto');
    var header = document.getElementById('resultadoHeader');
    document.getElementById('resultadoNumero').textContent = 'Erro';
    document.getElementById('resultadoTipo').textContent = msg;
    document.getElementById('resultadoBody').innerHTML = '';
    header.className = 'resultado-header bg-desconhecido';
    div.style.display = 'block';
}

/* v1.2.2 — Histórico de triagens */
var historicoAberto = false;
function toggleHistorico() {
    var cont = document.getElementById('historicoConteudo');
    var btn  = document.getElementById('btnHistoricoToggle');
    if (!cont) return;
    historicoAberto = !historicoAberto;
    cont.style.display = historicoAberto ? 'block' : 'none';
    btn.innerHTML = historicoAberto ? 'Recolher &#9650;' : 'Expandir &#9660;';
    if (historicoAberto) carregarHistorico();
}

function carregarHistorico() {
    var corpo = document.getElementById('corpoHistorico');
    if (!corpo) return;
    corpo.innerHTML = '<tr><td colspan="6" class="historico-vazio">Carregando...</td></tr>';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'encontra_posto.php?ajax_historico_triagens=1&limit=150', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success && resp.rows && resp.rows.length > 0) {
                    var html = '';
                    for (var i = 0; i < resp.rows.length; i++) {
                        var r = resp.rows[i];
                        html += '<tr>'
                            + '<td>' + (r.dt || '') + '</td>'
                            + '<td style="font-family:monospace;">' + (r.lote || '') + '</td>'
                            + '<td style="font-weight:700;">' + (r.posto || '') + '</td>'
                            + '<td>' + (r.regional || '') + '</td>'
                            + '<td style="text-align:right;">' + (r.quantidade || '') + '</td>'
                            + '<td>' + (r.triado_por ? r.triado_por : '<span style="color:#aaa;">—</span>') + '</td>'
                            + '</tr>';
                    }
                    corpo.innerHTML = html;
                } else {
                    corpo.innerHTML = '<tr><td colspan="6" class="historico-vazio">Nenhum registro encontrado.</td></tr>';
                }
            } catch (eH) {
                corpo.innerHTML = '<tr><td colspan="6" class="historico-vazio">Erro ao carregar histórico.</td></tr>';
            }
        } else {
            corpo.innerHTML = '<tr><td colspan="6" class="historico-vazio">Erro de conexão.</td></tr>';
        }
    };
    xhr.send();
}

function atualizarStats() {
    document.getElementById('statTotal').textContent = contTotal;
    document.getElementById('statCapital').textContent = contCapital;
    document.getElementById('statCentral').textContent = contCentral;
    document.getElementById('statRegional').textContent = contRegional;
    document.getElementById('statPT').textContent = contPT;
    var elSem = document.getElementById('statSemUpload');
    if (elSem) elSem.textContent = contSemUpload;
}

function renderizarSemUpload() {
    var painel = document.getElementById('painelSemUpload');
    var lista = document.getElementById('listaSemUpload');
    var item;
    var lote;
    var posto;
    var regional;
    if (!painel || !lista) return;
    if (!lotesSemUpload || lotesSemUpload.length === 0) {
        painel.style.display = 'none';
        lista.innerHTML = '';
        return;
    }
    painel.style.display = 'block';
    var html = '';
    for (var i = 0; i < lotesSemUpload.length; i++) {
        item = lotesSemUpload[i] || {};
        if (typeof item === 'string') {
            item = { lote: item, posto: '', regional: '' };
        }
        lote = String(item.lote || '').trim();
        posto = String(item.posto || '').trim();
        regional = String(item.regional || '').trim();
        html += '<span class="lote-badge" title="Lote ' + lote + ' - Regional ' + regional + ' - Posto ' + posto + '">' + lote + '<small>R ' + regional + ' • P ' + posto + '</small></span>';
    }
    lista.innerHTML = html;
}

var prateleirasCorreiosA = [
    [
        { key: 'capital', label: 'Capital' },
        { key: 'posto001', label: 'Posto 001' },
        { key: 'central', label: 'Central IIPR' },
        { key: '__vazio1', label: 'Livre', empty: true }
    ],
    [
        { key: 'r022', label: 'R 022' },
        { key: 'r060', label: 'R 060' },
        { key: 'r100', label: 'R 100' },
        { key: 'r105', label: 'R 105' }
    ],
    [
        { key: 'r150', label: 'R 150' },
        { key: 'r200', label: 'R 200' },
        { key: 'r250', label: 'R 250' },
        { key: 'r300', label: 'R 300' }
    ],
    [
        { key: 'r350', label: 'R 350' },
        { key: 'r400', label: 'R 400' },
        { key: 'r450', label: 'R 450' },
        { key: 'r490', label: 'R 490' }
    ],
    [
        { key: 'r500', label: 'R 500' },
        { key: 'r501', label: 'R 501' },
        { key: 'r507', label: 'R 507' },
        { key: 'r550', label: 'R 550' }
    ]
];

var prateleirasCorreiosB = [
    [
        { key: 'r600', label: 'R 600' },
        { key: 'r650', label: 'R 650' },
        { key: 'r700', label: 'R 700' },
        { key: 'r701', label: 'R 701' }
    ],
    [
        { key: 'r710', label: 'R 710' },
        { key: 'r750', label: 'R 750' },
        { key: 'r755', label: 'R 755' },
        { key: 'r758', label: 'R 758' }
    ],
    [
        { key: 'r779', label: 'R 779' },
        { key: 'r800', label: 'R 800' },
        { key: 'r808', label: 'R 808' },
        { key: 'r809', label: 'R 809' }
    ],
    [
        { key: 'r850', label: 'R 850' },
        { key: 'r900', label: 'R 900' },
        { key: 'r950', label: 'R 950' },
        { key: '__vazio2', label: 'Livre', empty: true }
    ]
];

var prateleirasPoupaTempo = [
    [
        { key: 'p005', label: '005' },
        { key: 'p006', label: '006' },
        { key: 'p023', label: '023' },
        { key: 'p024', label: '024' }
    ],
    [
        { key: 'p025', label: '025' },
        { key: 'p026', label: '026' },
        { key: 'p028', label: '028' },
        { key: 'p080', label: '080' }
    ],
    [
        { key: 'p110', label: '110' },
        { key: 'p315', label: '315' },
        { key: 'p375', label: '375' },
        { key: 'p487', label: '487' }
    ],
    [
        { key: 'p526', label: '526' },
        { key: 'p527', label: '527' },
        { key: 'p667', label: '667' },
        { key: 'p730', label: '730' }
    ],
    [
        { key: 'p747', label: '747' },
        { key: 'p790', label: '790' },
        { key: 'p825', label: '825' },
        { key: 'p880', label: '880' }
    ]
];

function obterValorLayout(grupo, chave) {
    if (!estanteLayout || !estanteLayout[grupo]) return 0;
    var v = estanteLayout[grupo][chave];
    return v ? v : 0;
}

function montarPrateleirasHtml(prateleiras, grupo) {
    var html = '';
    for (var i = 0; i < prateleiras.length; i++) {
        html += '<div class="prateleira">';
        for (var j = 0; j < prateleiras[i].length; j++) {
            var slot = prateleiras[i][j];
            var valor = slot.empty ? 0 : obterValorLayout(grupo, slot.key);
            var classe = slot.empty ? 'slot slot-vazio' : 'slot';
            html += '<div class="' + classe + '">' +
                '<div class="slot-label">' + slot.label + '</div>' +
                '<div class="slot-valor">' + valor + '</div>' +
                '</div>';
        }
        html += '</div>';
    }
    return html;
}

function atualizarResumoEstantes() {
    var resumo = document.getElementById('estantesResumo');
    if (!resumo) return;
    var totalCorreios = 0;
    var totalPT = 0;
    if (estanteLayout && estanteLayout.totais) {
        totalCorreios = estanteLayout.totais.correios_lotes || 0;
        totalPT = estanteLayout.totais.poupatempo_lotes || 0;
    }
    resumo.textContent = 'Correios: ' + totalCorreios + ' lotes | Poupa Tempo: ' + totalPT + ' lotes';
}

function renderizarEstantes() {
    var elA = document.getElementById('estanteCorreiosA');
    var elB = document.getElementById('estanteCorreiosB');
    var elPT = document.getElementById('estantePoupaTempo');
    if (elA) { elA.innerHTML = montarPrateleirasHtml(prateleirasCorreiosA, 'correios'); }
    if (elB) { elB.innerHTML = montarPrateleirasHtml(prateleirasCorreiosB, 'correios'); }
    if (elPT) { elPT.innerHTML = montarPrateleirasHtml(prateleirasPoupaTempo, 'poupatempo'); }
    atualizarResumoEstantes();
    aplicarVisaoEstantes(estanteView);
}

function aplicarVisaoEstantes(view) {
    estanteView = view || 'todas';
    var botoes = document.querySelectorAll('#estantesToggle button[data-view]');
    for (var i = 0; i < botoes.length; i++) {
        var alvo = botoes[i].getAttribute('data-view');
        if (alvo === estanteView) {
            botoes[i].classList.add('ativo');
        } else {
            botoes[i].classList.remove('ativo');
        }
    }
    var estantes = document.querySelectorAll('.estante[data-grupo]');
    for (var j = 0; j < estantes.length; j++) {
        var grupo = estantes[j].getAttribute('data-grupo');
        if (estanteView === 'todas' || estanteView === grupo) {
            estantes[j].style.display = 'grid';
        } else {
            estantes[j].style.display = 'none';
        }
    }
}

function carregarEstanteInicial() {
    var dataIni = obterDataIni();
    var dataFim = obterDataFim();
    if (!dataIni) {
        contTotal = 0; contCapital = 0; contCentral = 0; contRegional = 0; contPT = 0; contSemUpload = 0; lotesSemUpload = [];
        renderizarSemUpload();
        carregarPendenciasPeriodo();
        atualizarStats();
        estanteLayout = { correios: {}, poupatempo: {}, totais: {} };
        renderizarEstantes();
        return;
    }
    if (!dataFim) {
        dataFim = dataIni;
    }
    if (dataIni > dataFim) {
        var tmp = dataIni;
        dataIni = dataFim;
        dataFim = tmp;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'encontra_posto.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success && resp.estante) {
                    contTotal = resp.estante.total || 0;
                    contCapital = resp.estante.capital || 0;
                    contCentral = resp.estante.central || 0;
                    contRegional = resp.estante.regional || 0;
                    contPT = resp.estante.poupatempo || 0;
                    // sem_upload é acumulado por leitura; não resetar no refresh periódico
                    if (resp.layout) {
                        estanteLayout = resp.layout || { correios: {}, poupatempo: {}, totais: {} };
                        renderizarEstantes();
                    }
                    atualizarStats();
                }
            } catch (e) {}
        }
    };
    xhr.send('ajax_estante_status=1&data_ini=' + encodeURIComponent(dataIni) + '&data_fim=' + encodeURIComponent(dataFim));
}

// v2.1: Wake Lock API - manter tela ativa durante leituras
var wakeLockSentinel = null;

function solicitarWakeLock() {
    if ('wakeLock' in navigator) {
        navigator.wakeLock.request('screen').then(function(sentinel) {
            wakeLockSentinel = sentinel;
            console.log('[WakeLock] Tela mantida ativa');
            sentinel.addEventListener('release', function() {
                console.log('[WakeLock] Liberado - tentando readquirir');
                wakeLockSentinel = null;
            });
        }).catch(function(err) {
            console.log('[WakeLock] Nao suportado ou negado:', err.message);
        });
    }
}

solicitarWakeLock();

document.addEventListener('visibilitychange', function() {
    var campo = document.getElementById('input_codbar');
    if (document.visibilityState === 'visible') {
        if (!wakeLockSentinel) { solicitarWakeLock(); }
        if (campo) { campo.value = ''; campo.focus(); }
        atualizarIndicadorFoco();
    } else {
        if (campo) { campo.value = ''; }
    }
});

window.addEventListener('focus', function() {
    if (!wakeLockSentinel) { solicitarWakeLock(); }
    var campo = document.getElementById('input_codbar');
    if (campo) { campo.value = ''; campo.focus(); }
    atualizarIndicadorFoco();
});

setInterval(function() {
    if (document.visibilityState === 'visible' && !wakeLockSentinel) {
        solicitarWakeLock();
    }
    atualizarIndicadorFoco();
}, 30000);

try {
    if (typeof speechSynthesis !== 'undefined') {
        speechSynthesis.getVoices();
        if (typeof speechSynthesis.onvoiceschanged !== 'undefined') {
            speechSynthesis.onvoiceschanged = function() {
                try { speechSynthesis.getVoices(); } catch (eVoices) {}
            };
        }
    }
} catch (eWarmup) {}

atualizarIndicadorFoco();
var inputIni = document.getElementById('data_ini_estante');
var inputFim = document.getElementById('data_fim_estante');
var triadoPorInput = document.getElementById('triado_por_input');
if (triadoPorInput) {
    try {
        var savedTriador = localStorage.getItem('triador_nome');
        if (savedTriador) triadoPorInput.value = savedTriador;
    } catch (e) {}
    triadoPorInput.addEventListener('input', function() {
        try { localStorage.setItem('triador_nome', this.value); } catch (e) {}
    });
    triadoPorInput.addEventListener('blur', function() {
        setTimeout(function() {
            var ativo = document.activeElement;
            if (!ativo || ativo === document.body) {
                var campo = document.getElementById('input_codbar');
                if (campo) campo.focus();
            }
        }, 50);
    });
}
var responsavelPendencias = document.getElementById('responsavel_pendencias');
var turnoPendencias = document.getElementById('turno_pendencias');
var criadoPendencias = document.getElementById('criado_pendencias');
var consolidarPendencias = document.getElementById('consolidar_pendencias');
var listaPendenciasNaoCarregadas = document.getElementById('listaPendenciasNaoCarregadas');
if (criadoPendencias && !criadoPendencias.value) {
    criadoPendencias.value = formatarDateTimeLocal(new Date());
}
if (inputIni && inputFim) {
    var hoje = formatarHoje();
    inputIni.value = hoje;
    inputFim.value = hoje;
    localStorage.setItem('estante_data_ini', hoje);
    localStorage.setItem('estante_data_fim', hoje);
    inputIni.addEventListener('change', salvarDatasAlvo);
    inputFim.addEventListener('change', salvarDatasAlvo);
    inputIni.addEventListener('blur', salvarDatasAlvo);
    inputFim.addEventListener('blur', salvarDatasAlvo);
}
var btnAlterar = document.getElementById('btnAlterarDatas');
if (btnAlterar) {
    btnAlterar.addEventListener('click', abrirModalDatas);
}
var btnConfirmar = document.getElementById('btnConfirmarDatas');
if (btnConfirmar) {
    btnConfirmar.addEventListener('click', function() {
        var iniModal = document.getElementById('data_ini_modal');
        var fimModal = document.getElementById('data_fim_modal');
        if (iniModal && inputIni) {
            inputIni.value = iniModal.value.trim();
        }
        if (fimModal && inputFim) {
            inputFim.value = fimModal.value.trim();
        }
        salvarDatasAlvo();
        confirmarPeriodoAtual();
        fecharModalDatas();
    });
}
var btnCancelar = document.getElementById('btnCancelarDatas');
if (btnCancelar) {
    btnCancelar.addEventListener('click', fecharModalDatas);
}
atualizarBannerDatas();
confirmarPeriodoAtual();
fecharModalDatas();
carregarPendenciasPeriodo();
var toggleBtns = document.querySelectorAll('#estantesToggle button[data-view]');
for (var i = 0; i < toggleBtns.length; i++) {
    toggleBtns[i].addEventListener('click', function() {
        var view = this.getAttribute('data-view');
        aplicarVisaoEstantes(view);
    });
}
if (listaPendenciasNaoCarregadas) {
    listaPendenciasNaoCarregadas.addEventListener('click', function(ev) {
        var alvo = ev.target;
        if (!alvo) return;
        var idx = alvo.getAttribute('data-remover-pendente');
        if (idx === null) return;
        idx = parseInt(idx, 10);
        if (isNaN(idx) || !pendenciasNaoCarregadas[idx]) return;
        pendenciasNaoCarregadas.splice(idx, 1);
        salvarPendenciasPeriodo();
        renderizarPendenciasNaoCarregadas();
    });
}
var btnLimparPendencias = document.getElementById('btnLimparPendencias');
if (btnLimparPendencias) {
    btnLimparPendencias.addEventListener('click', function() {
        pendenciasNaoCarregadas = [];
        limparPendenciasPeriodoPersistidas();
        renderizarPendenciasNaoCarregadas();
    });
}
var btnSelecionarTodosPend = document.getElementById('btnSelecionarTodosPendencias');
var btnDesmarcarTodosPend = document.getElementById('btnDesmarcarTodosPendencias');
var chkTodosPend = document.getElementById('chkTodosPendencias');
if (btnSelecionarTodosPend) {
    btnSelecionarTodosPend.addEventListener('click', function() {
        var corpo = document.getElementById('listaPendenciasNaoCarregadas');
        if (!corpo) return;
        var chks = corpo.querySelectorAll('input.chk-pend-item');
        for (var i = 0; i < chks.length; i++) chks[i].checked = true;
        if (chkTodosPend) chkTodosPend.checked = true;
        atualizarContadorSelecionadosPendencias();
    });
}
if (btnDesmarcarTodosPend) {
    btnDesmarcarTodosPend.addEventListener('click', function() {
        var corpo = document.getElementById('listaPendenciasNaoCarregadas');
        if (!corpo) return;
        var chks = corpo.querySelectorAll('input.chk-pend-item');
        for (var i = 0; i < chks.length; i++) chks[i].checked = false;
        if (chkTodosPend) chkTodosPend.checked = false;
        atualizarContadorSelecionadosPendencias();
    });
}
if (chkTodosPend) {
    chkTodosPend.addEventListener('change', function() {
        var corpo = document.getElementById('listaPendenciasNaoCarregadas');
        if (!corpo) return;
        var chks = corpo.querySelectorAll('input.chk-pend-item');
        for (var i = 0; i < chks.length; i++) chks[i].checked = chkTodosPend.checked;
        atualizarContadorSelecionadosPendencias();
    });
}
var btnAtribuirResponsavel = document.getElementById('btnAtribuirResponsavelPendencias');
if (btnAtribuirResponsavel) {
    btnAtribuirResponsavel.addEventListener('click', function() {
        var corpo = document.getElementById('listaPendenciasNaoCarregadas');
        if (!corpo) return;
        var chks = corpo.querySelectorAll('input.chk-pend-item:checked');
        if (!chks.length) { alert('Marque ao menos um lote antes de atribuir um responsavel.'); return; }
        var nome = prompt('Nome do responsavel para os ' + chks.length + ' lote(s) selecionado(s):');
        if (nome === null) return;
        nome = nome.trim();
        for (var i = 0; i < chks.length; i++) {
            var idx = parseInt(chks[i].getAttribute('data-idx'), 10);
            if (!isNaN(idx) && idx >= 0 && idx < pendenciasNaoCarregadas.length) {
                pendenciasNaoCarregadas[idx].responsavel = nome;
            }
        }
        pendenciasNaoCarregadas.sort(function(a, b) {
            var ra = (a.responsavel || '').toLowerCase();
            var rb = (b.responsavel || '').toLowerCase();
            if (ra < rb) return -1;
            if (ra > rb) return 1;
            return 0;
        });
        renderizarPendenciasNaoCarregadas();
    });
}
var btnSalvarPendencias = document.getElementById('btnSalvarPendencias');
if (btnSalvarPendencias) {
    btnSalvarPendencias.addEventListener('click', function() {
        var responsavel = responsavelPendencias ? responsavelPendencias.value.trim() : '';
        var criado = criadoPendencias ? criadoPendencias.value.trim() : '';
        var turno = turnoPendencias ? turnoPendencias.value : 'Manhã';
        var formData;
        var indicesSelecionados = getIndicesSelecionadosPendencias();
        if (!pendenciasNaoCarregadas.length) {
            alert('Nao ha lotes pendentes para salvar neste periodo.');
            return;
        }
        if (!indicesSelecionados.length) {
            alert('Selecione pelo menos um lote antes de carregar.');
            return;
        }
        if (!responsavel) {
            alert('Informe o responsavel antes de salvar.');
            if (responsavelPendencias) responsavelPendencias.focus();
            return;
        }
        if (!criado) {
            alert('Informe a data de producao.');
            if (criadoPendencias) criadoPendencias.focus();
            return;
        }
        var pacotesSelecionados = [];
        for (var s = 0; s < indicesSelecionados.length; s++) {
            pacotesSelecionados.push(pendenciasNaoCarregadas[indicesSelecionados[s]]);
        }
        var msgDiv = document.getElementById('msgSalvamentoPendencias');
        var btnSalvar = document.getElementById('btnSalvarPendencias');
        if (msgDiv) { msgDiv.style.display = 'none'; msgDiv.textContent = ''; }
        if (btnSalvar) { btnSalvar.disabled = true; btnSalvar.textContent = 'Carregando...'; }
        formData = new FormData();
        formData.append('inserir_pacotes_nao_listados', '1');
        formData.append('usuario', responsavel);
        formData.append('autor_salvamento', responsavel);
        formData.append('turno_salvamento', turno);
        formData.append('criado_salvamento', criado);
        if (consolidarPendencias && consolidarPendencias.checked) {
            formData.append('consolidar_salvamento', '1');
        }
        formData.append('pacotes', JSON.stringify(pacotesSelecionados));
        fetch('encontra_posto.php', { method: 'POST', body: formData })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (btnSalvar) { btnSalvar.disabled = false; btnSalvar.textContent = 'Carregar Selecionados'; }
                if (data && data.success) {
                    var totalLotes = data.inseridos || 0;
                    var totalPostos = data.inseridos_postos || 0;
                    var erros = (data.erros && data.erros.length) ? data.erros.length : 0;
                    var msg = totalLotes + ' lote(s) carregados para ' + responsavel + ' em ciPostosCsv e ' + totalPostos + ' lancamento(s) em ciPostos';
                    if (erros > 0) { msg += ' (' + erros + ' erro(s) ignorado(s))'; }
                    if (msgDiv) {
                        msgDiv.textContent = msg;
                        msgDiv.style.display = 'block';
                        msgDiv.style.background = '#e8f5e9';
                        msgDiv.style.color = '#1b5e20';
                        msgDiv.style.border = '1px solid #a5d6a7';
                        setTimeout(function() { if (msgDiv) { msgDiv.style.display = 'none'; } }, 10000);
                    }
                    // Registrar no histórico
                    adicionarCarregamentoHistorico(responsavel, turno, criado, pacotesSelecionados);
                    // Remover apenas os selecionados da fila (do maior índice para o menor)
                    var indicesDesc = indicesSelecionados.slice().sort(function(a,b){ return b-a; });
                    for (var r = 0; r < indicesDesc.length; r++) {
                        pendenciasNaoCarregadas.splice(indicesDesc[r], 1);
                    }
                    salvarPendenciasPeriodo();
                    renderizarPendenciasNaoCarregadas();
                    carregarEstanteInicial();
                } else {
                    var erroMsg = (data && data.erro) ? data.erro : 'Erro ao salvar lotes nao carregados.';
                    if (msgDiv) {
                        msgDiv.textContent = erroMsg;
                        msgDiv.style.display = 'block';
                        msgDiv.style.background = '#ffebee';
                        msgDiv.style.color = '#b71c1c';
                        msgDiv.style.border = '1px solid #ef9a9a';
                    }
                }
            })
            .catch(function() {
                if (btnSalvar) { btnSalvar.disabled = false; btnSalvar.textContent = 'Carregar Selecionados'; }
                if (msgDiv) {
                    msgDiv.textContent = 'Erro de comunicacao ao salvar lotes nao carregados.';
                    msgDiv.style.display = 'block';
                    msgDiv.style.background = '#ffebee';
                    msgDiv.style.color = '#b71c1c';
                    msgDiv.style.border = '1px solid #ef9a9a';
                }
            });
    });
}
carregarEstanteInicial();

// Leitura por CAMERA ao vivo (celular). Roteia 35 ANTES de 19 porque o codigo
// do display (35 dig) contem uma sub-sequencia de 19 dig do lote.
function abrirCameraEP() {
    if (typeof CamScanner === 'undefined' || !CamScanner.start) {
        alert('Leitor de camera nao disponivel nesta pagina.');
        return;
    }
    CamScanner.start({
        titulo: 'Ler posto pela camera',
        onRead: function (bruto) {
            var d = ('' + (bruto || '')).replace(/\D+/g, '');
            if (d.length >= 35) {
                if (window.lerDisplayPosto) { window.lerDisplayPosto(d.slice(-35)); }
            } else if (d.length >= 19) {
                processarCodigoBruto(d.slice(-19));
            }
        }
    });
}

</script>

<?php include __DIR__ . '/processando_overlay.php'; ?>
<?php include __DIR__ . '/util_botoes_fixos.php'; ?>

<script src="lib_zxing.min.js"></script>
<script src="lib_cam_scanner.js"></script>
<?php include __DIR__ . '/_acess.php'; ?>
</body>
</html>