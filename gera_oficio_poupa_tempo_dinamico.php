<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
error_reporting(E_ALL & ~E_NOTICE);
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION)) {
    session_start();
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function normalizarDataPtSqlDinamico($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor)) {
        return $valor;
    }
    if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $valor, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+\d{2}:\d{2}:\d{2}$/', $valor, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    return '';
}

function formatarDataBrDinamico($valor) {
    $sql = normalizarDataPtSqlDinamico($valor);
    if ($sql === '') {
        return '';
    }
    return substr($sql, 8, 2) . '-' . substr($sql, 5, 2) . '-' . substr($sql, 0, 4);
}

// v2.8.2: codificacao segura p/ JSON (mesmo padrao de lacres_novo/conferencia_pacotes).
// Em PHP 5.3.3 o charset do DSN e ignorado -> o MySQL pode devolver texto em Latin-1; com
// acento o json_encode() retorna FALSE e o AJAX sairia com corpo VAZIO. Aqui garantimos
// UTF-8 valido e NUNCA um corpo vazio.
function normalizarTextoUtf8JsonSeguroDinamico($valor) {
    $valor = (string)$valor;
    if ($valor === '' || preg_match('//u', $valor)) {
        return $valor;
    }
    if (function_exists('iconv')) {
        $tmp = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false && $tmp !== '') return $tmp;
        $tmp = @iconv('Windows-1252', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false && $tmp !== '') return $tmp;
    }
    if (function_exists('utf8_encode')) {
        return @utf8_encode($valor);
    }
    return $valor;
}

function normalizarDadosUtf8JsonSeguroDinamico($valor) {
    if (is_array($valor)) {
        $out = array();
        foreach ($valor as $k => $v) {
            $kn = is_string($k) ? normalizarTextoUtf8JsonSeguroDinamico($k) : $k;
            $out[$kn] = normalizarDadosUtf8JsonSeguroDinamico($v);
        }
        return $out;
    }
    if (is_string($valor)) {
        return normalizarTextoUtf8JsonSeguroDinamico($valor);
    }
    return $valor;
}

function json_encode_legado_seguro_dinamico($valor) {
    $normalizado = normalizarDadosUtf8JsonSeguroDinamico($valor);
    $json = json_encode($normalizado);
    if ($json === false) {
        $json = json_encode(array('success' => false, 'erro' => 'Falha ao codificar a resposta (json_encode).'));
        if ($json === false) { $json = '{"success":false,"erro":"json"}'; }
    }
    return $json;
}

function tabelaTemColunaDinamico($pdo, $tabela, $coluna) {
    static $cache = array();
    $chave = $tabela . '.' . $coluna;
    if (isset($cache[$chave])) {
        return $cache[$chave];
    }
    try {
        $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . $tabela . ' LIKE ?');
        $stmt->execute(array($coluna));
        $cache[$chave] = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    } catch (Exception $e) {
        $cache[$chave] = false;
    }
    return $cache[$chave];
}

function resolverNomePostoCiPostosDinamico($pdo, $posto) {
    $posto = trim((string)$posto);
    if ($posto === '') {
        return '';
    }
    $postoPad = str_pad(preg_replace('/\D+/', '', $posto), 3, '0', STR_PAD_LEFT);
    try {
        $stmt = $pdo->prepare('SELECT posto FROM ciPostos WHERE posto LIKE ? ORDER BY id DESC LIMIT 1');
        $stmt->execute(array($postoPad . ' -%'));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['posto'])) {
            return $row['posto'];
        }
    } catch (Exception $e) {
    }
    return $postoPad . ' - POSTO';
}

function mapearTurnoCiPostosDinamico($turno) {
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

function normalizarDataHoraSqlDinamico($valor) {
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

$pdo = null;
$erroConexao = '';
try {
    $pdo = new PDO(
        (getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4;connect_timeout=4' : 'mysql:host=' . (getenv('DB_HOST') ?: '10.15.61.169') . ';dbname=controle;charset=utf8mb4;connect_timeout=4'),
        (getenv('DB_USER') ?: (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'))),
        (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256')))
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // v2.8.2: PHP 5.3.3 ignora o charset do DSN (so vale a partir do 5.3.6). Sem isto o MySQL
    // devolve os textos dos postos em Latin-1; com acento o json_encode() retorna false e o
    // AJAX sai com corpo VAZIO ("JSON.parse: unexpected end of data"). SET NAMES forca UTF-8
    // (mesmo padrao de conferencia_pacotes.php / lacres_novo.php / devolucao_lotes.php).
    try { $pdo->exec("SET NAMES utf8"); } catch (Exception $eNames) {}
} catch (Exception $e) {
    $erroConexao = $e->getMessage();
}

$isAjax = isset($_POST['ajax_resolver_codbar_pt']) || isset($_POST['inserir_pendencias_pt']) || isset($_POST['marcar_conferidos_pt']);
if ($isAjax && !$pdo) {
    header('Content-Type: application/json');
    die(json_encode(array('success' => false, 'erro' => 'Banco de dados indisponível: ' . $erroConexao)));
}

if ($pdo && isset($_POST['ajax_resolver_codbar_pt'])) {
    header('Content-Type: application/json');
    // v2.8.2: blindagem do endpoint — qualquer PDOException nao tratada (ex.: SELECT em
    // ciPostosCsv) viraria FATAL e corpo vazio; aqui devolvemos sempre JSON legivel.
    try {
    $codbar = isset($_POST['codbar']) ? preg_replace('/\D+/', '', (string)$_POST['codbar']) : '';
    $dataPadrao = normalizarDataPtSqlDinamico(isset($_POST['data_padrao']) ? $_POST['data_padrao'] : '');
    if ($dataPadrao === '') {
        $dataPadrao = date('Y-m-d');
    }
    $responsavel = isset($_POST['responsavel']) ? trim((string)$_POST['responsavel']) : '';
    if ($responsavel === '' && isset($_SESSION['usuario'])) {
        $responsavel = trim((string)$_SESSION['usuario']);
    }

    $len = strlen($codbar);
    if ($len !== 19 && $len !== 17) {
        die(json_encode(array('success' => false, 'erro' => 'Codigo invalido. Use 17 ou 19 digitos. Recebido: ' . $len . ' digitos.')));
    }

    if ($len === 19) {
        $lote             = str_pad(substr($codbar, 0, 8), 8, '0', STR_PAD_LEFT);
        $regional         = str_pad(substr($codbar, 8, 3), 3, '0', STR_PAD_LEFT);
        $posto            = str_pad(substr($codbar, 11, 3), 3, '0', STR_PAD_LEFT);
        $quantidadeBarra  = (int)substr($codbar, 14, 5);
    } else {
        $lote             = str_pad(substr($codbar, 0, 8), 8, '0', STR_PAD_LEFT);
        $regional         = str_pad(substr($codbar, 8, 1), 3, '0', STR_PAD_LEFT);
        $posto            = str_pad(substr($codbar, 9, 3), 3, '0', STR_PAD_LEFT);
        $quantidadeBarra  = (int)substr($codbar, 12, 5);
    }

    // Cache posto info na sessao para evitar re-consultar ciRegionais a cada scan
    if (!isset($_SESSION['cache_pt_postos'])) $_SESSION['cache_pt_postos'] = array();
    $postoInt = (int)$posto;
    if (isset($_SESSION['cache_pt_postos'][$postoInt])) {
        $postoRow = $_SESSION['cache_pt_postos'][$postoInt];
    } else {
        // Comparar com inteiro para usar indice (evitar LPAD no WHERE)
        $stmtPosto = $pdo->prepare("SELECT LPAD(posto,3,'0') AS posto, COALESCE(NULLIF(TRIM(nome),''), LPAD(posto,3,'0')) AS nome, endereco,
                                         LOWER(REPLACE(COALESCE(entrega,''),' ','')) AS entrega,
                                         LOWER(COALESCE(nome,'')) AS nome_lower
                                  FROM ciRegionais
                                  WHERE posto = ?
                                  LIMIT 1");
        $stmtPosto->execute(array($postoInt));
        $postoRow = $stmtPosto->fetch(PDO::FETCH_ASSOC);
        if ($postoRow) $_SESSION['cache_pt_postos'][$postoInt] = $postoRow;
    }

    if (!$postoRow) {
        die(json_encode(array('success' => false, 'erro' => 'Posto ' . $posto . ' nao encontrado em ciRegionais. Verifique o codigo de barras.')));
    }

    $entrega = isset($postoRow['entrega']) ? (string)$postoRow['entrega'] : '';
    $nomeLower = isset($postoRow['nome_lower']) ? (string)$postoRow['nome_lower'] : '';
    $ePoupaTempo = (strpos($entrega, 'poupa') !== false || strpos($entrega, 'tempo') !== false
                   || strpos($nomeLower, 'poupa') !== false || strpos($nomeLower, 'tempo') !== false);
    if (!$ePoupaTempo) {
        die(json_encode_legado_seguro_dinamico(array('success' => false, 'erro' => 'Posto ' . $posto . ' (' . trim((string)$postoRow['nome']) . ') nao e Poupa Tempo. Entrega: ' . $entrega)));
    }

    // v2.8.3 (FIX data producao PT): o match da carga passou a ser pelo LOTE (normalizado em
    // 8 digitos com LPAD), NAO mais o filtro rigido "lote = ? AND posto = ?". Causa do bug:
    // com `WHERE lote = ? AND posto = ?` em comparacao INTEIRA, se o `posto`/`lote` em
    // ciPostosCsv nao casasse exatamente o inteiro do codigo de barras (tipo de coluna
    // VARCHAR/ZEROFILL, posto gravado diferente do barras, etc.) a linha NAO era encontrada
    // -> carregado=false -> celula amarela mesmo com o lote presente na tabela (ex.: lote
    // 00770966 com dataCarga 2026-06-13 nao aparecia). O lote e unico por pacote, entao
    // basta o LOTE para trazer a data; posto/regional viram apenas desempate no ORDER BY
    // (preferindo a linha do mesmo posto/regional do barras). LPAD(CAST(... AS CHAR),8,'0')
    // normaliza independ. do tipo da coluna — mesmo padrao ja usado no UPDATE de conf.
    $loteInt = (int)$lote;
    $regionalInt = (int)$regional;
    $stmtCarga = $pdo->prepare("SELECT LPAD(CAST(lote AS CHAR),8,'0') AS lote,
                                       LPAD(CAST(posto AS CHAR),3,'0') AS posto,
                                       LPAD(CAST(regional AS CHAR),3,'0') AS regional,
                                       COALESCE(quantidade,0) AS quantidade,
                                       DATE(COALESCE(dataCarga, data)) AS data_carga,
                                       usuario
                                FROM ciPostosCsv
                                WHERE LPAD(TRIM(CAST(lote AS CHAR)),8,'0') = ?
                                ORDER BY CASE WHEN CAST(posto AS UNSIGNED) = ? THEN 0 ELSE 1 END,
                                         CASE WHEN CAST(regional AS UNSIGNED) = ? THEN 0 ELSE 1 END,
                                         COALESCE(dataCarga, data) DESC
                                LIMIT 1");
    $stmtCarga->execute(array($lote, $postoInt, $regionalInt));
    $cargaRow = $stmtCarga->fetch(PDO::FETCH_ASSOC);

    $carregado = $cargaRow ? true : false;
    $quantidade = $carregado ? (int)$cargaRow['quantidade'] : $quantidadeBarra;
    if ($quantidade <= 0) {
        $quantidade = $quantidadeBarra > 0 ? $quantidadeBarra : 1;
    }
    // v2.8.1: a DATA de producao do oficio PT vem SOMENTE de ciPostosCsv (data de upload/
    // carga). Sem o lote em ciPostosCsv (ou sem data la), $dataEncontrada fica false e o JSON
    // devolve '' -> a montagem mostra a celula AMARELA editavel p/ digitar a data manualmente.
    // $dataCarga = hoje permanece SO para o INSERT conf='s' interno (I7 intacto), NUNCA vai
    // pro JSON. Removido o antigo fallback em conferencia_pacotes (v2.7.0): ele trazia a data
    // de conferencia/expedicao (ex.: 15/06) no lugar da data real de producao (ex.: 13/06).
    $dataEncontrada = false;
    if ($carregado && !empty($cargaRow['data_carga'])) {
        $dataCarga = normalizarDataPtSqlDinamico($cargaRow['data_carga']);
        $dataEncontrada = true;
    } else {
        $dataCarga = $dataPadrao;
    }

    $usuarioExibicao = ($carregado && !empty($cargaRow['usuario'])) ? trim((string)$cargaRow['usuario']) : $responsavel;
    $usuarioPersistencia = $responsavel !== ''
        ? $responsavel
        : (($carregado && !empty($cargaRow['usuario']))
            ? trim((string)$cargaRow['usuario'])
            : ((isset($_SESSION['usuario']) && trim((string)$_SESSION['usuario']) !== '')
                ? trim((string)$_SESSION['usuario'])
                : 'poupatempo'));

    // Gravar conf='s' em conferencia_pacotes (como os demais lotes)
    // v2.6.0 (I7): garantir a coluna conferido_em ANTES do INSERT. A tela canonica
    // (conferencia_pacotes.php) cria/migra essa coluna; aqui ela podia faltar e o INSERT
    // falhava em silencio -> nenhum lote ficava marcado como conferido. Espelha o guard
    // canonico (SHOW COLUMNS + ALTER ADD). Identificadores estaticos (sem SQL do usuario).
    $conferido_em_pt = '';
    $conferenciaErro = '';
    try {
        $colsConfPt = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes LIKE 'conferido_em'")->fetchAll();
        if (count($colsConfPt) === 0) {
            $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN conferido_em DATETIME DEFAULT NULL");
        }
    } catch (\Exception $eColConf) { /* segue; o INSERT abaixo ainda tenta e reporta o erro */ }
    try {
        $sqlConf = "INSERT INTO conferencia_pacotes
                        (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, conferido_em)
                    VALUES (?,?,?,?,?,?,'s',?,NOW())
                    ON DUPLICATE KEY UPDATE
                        conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar),
                        dataexp=VALUES(dataexp), usuario=VALUES(usuario), conferido_em=NOW()";
        $pdo->prepare($sqlConf)->execute(array(
            (int)$regional,
            (int)$lote,
            (int)$posto,
            $dataCarga,
            $quantidade,
            $codbar,
            $usuarioPersistencia
        ));
        $conferido_em_pt = date('Y-m-d H:i:s');
    } catch (\Exception $eSalvConf) {
        // v2.6.0 (I7): nao engolir mais por completo -> reporta para evitar verde enganoso
        $conferenciaErro = $eSalvConf->getMessage();
    }

    die(json_encode_legado_seguro_dinamico(array(
        'success' => true,
        'carregado' => $carregado,
        'codbar' => $codbar,
        'lote' => $lote,
        'regional' => $regional,
        'posto' => $posto,
        'nome' => isset($postoRow['nome']) ? trim((string)$postoRow['nome']) : $posto,
        'endereco' => isset($postoRow['endereco']) ? trim((string)$postoRow['endereco']) : '',
        'quantidade' => $quantidade,
        'data_carga' => ($dataEncontrada ? $dataCarga : ''),
        'data_carga_br' => ($dataEncontrada ? formatarDataBrDinamico($dataCarga) : ''),
        'data_encontrada' => $dataEncontrada,
        'responsaveis' => $usuarioExibicao,
        'conferido_em' => $conferido_em_pt,
        'conferencia_ok' => ($conferido_em_pt !== ''),
        'conferencia_erro' => $conferenciaErro,
        'mensagem' => $carregado ? 'Lote localizado em ciPostosCsv.' : 'Lote nao carregado. Adicionado com data padrao e fila de pendencias.'
    )));
    } catch (Exception $eResolver) {
        // v2.8.2: nunca devolver corpo vazio — o cliente faz r.json() e mostraria
        // "JSON.parse: unexpected end of data". Sempre devolve JSON com o erro real.
        die(json_encode_legado_seguro_dinamico(array(
            'success' => false,
            'erro' => 'Erro no servidor ao resolver a leitura: ' . $eResolver->getMessage()
        )));
    }
}

if ($pdo && isset($_POST['inserir_pendencias_pt'])) {
    header('Content-Type: application/json');
    $payload = isset($_POST['pacotes']) ? $_POST['pacotes'] : '';
    $usuarioConf = isset($_POST['usuario']) ? trim((string)$_POST['usuario']) : '';
    $autor = isset($_POST['autor_salvamento']) ? trim((string)$_POST['autor_salvamento']) : '';
    $criado = normalizarDataHoraSqlDinamico(isset($_POST['criado_salvamento']) ? $_POST['criado_salvamento'] : '');
    $turno = isset($_POST['turno_salvamento']) ? trim((string)$_POST['turno_salvamento']) : 'Manhã';
    $consolidar = !empty($_POST['consolidar_salvamento']);
    $pacotes = json_decode($payload, true);

    if (!is_array($pacotes) || empty($pacotes)) {
        die(json_encode(array('success' => false, 'erro' => 'Nenhuma pendencia para salvar.')));
    }
    if ($usuarioConf === '') {
        $usuarioConf = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
    }
    if ($usuarioConf === '') {
        die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio.')));
    }
    if ($autor === '') {
        $autor = $usuarioConf;
    }
    if ($criado === '') {
        $criado = date('Y-m-d H:i:s');
    }

    $stmtCsv = $pdo->prepare('INSERT INTO ciPostosCsv (lote, posto, regional, quantidade, dataCarga, data, usuario) VALUES (?,?,?,?,?,NOW(),?)');
    $okCsv = 0;
    $okPostos = 0;
    $erros = array();
    $grupos = array();

    foreach ($pacotes as $pacote) {
        try {
            $lote = isset($pacote['lote']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['lote']), 8, '0', STR_PAD_LEFT) : '';
            $posto = isset($pacote['posto']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['posto']), 3, '0', STR_PAD_LEFT) : '';
            $regional = isset($pacote['regional']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['regional']), 3, '0', STR_PAD_LEFT) : '';
            $quantidade = isset($pacote['quantidade']) ? (int)$pacote['quantidade'] : 0;
            $dataCarga = normalizarDataPtSqlDinamico(isset($pacote['dataexp']) ? $pacote['dataexp'] : '');
            $responsavel = isset($pacote['responsavel']) ? trim((string)$pacote['responsavel']) : '';

            if ($responsavel === '') {
                $responsavel = $usuarioConf;
            }
            if ($lote === '' || $posto === '' || $regional === '' || $quantidade <= 0 || $dataCarga === '') {
                throw new Exception('Pendencia com dados obrigatorios ausentes.');
            }

            $stmtCsv->execute(array($lote, $posto, $regional, $quantidade, $dataCarga, $responsavel));
            $okCsv++;

            $chave = $posto . '|' . $dataCarga . '|' . ($consolidar ? $responsavel : $lote . '|' . $regional);
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = array(
                    'posto' => $posto,
                    'dia' => $dataCarga,
                    'quantidade' => 0,
                    'turno' => mapearTurnoCiPostosDinamico($turno),
                    'autor' => $autor,
                    'criado' => $criado,
                    'regional' => $regional,
                    'lote' => $lote
                );
            }
            $grupos[$chave]['quantidade'] += $quantidade;
        } catch (Exception $e) {
            $erros[] = $e->getMessage();
        }
    }

    foreach ($grupos as $grupo) {
        try {
            $campos = array();
            $vals = array();
            $pars = array();

            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'posto')) {
                $campos[] = 'posto';
                $vals[] = '?';
                $pars[] = resolverNomePostoCiPostosDinamico($pdo, $grupo['posto']);
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'dia')) {
                $campos[] = 'dia';
                $vals[] = '?';
                $pars[] = $grupo['dia'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'quantidade')) {
                $campos[] = 'quantidade';
                $vals[] = '?';
                $pars[] = (int)$grupo['quantidade'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'turno')) {
                $campos[] = 'turno';
                $vals[] = '?';
                $pars[] = (int)$grupo['turno'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'autor')) {
                $campos[] = 'autor';
                $vals[] = '?';
                $pars[] = $grupo['autor'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'criado')) {
                $campos[] = 'criado';
                $vals[] = '?';
                $pars[] = $grupo['criado'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'regional')) {
                $campos[] = 'regional';
                $vals[] = '?';
                $pars[] = (int)$grupo['regional'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'lote') && !$consolidar) {
                $campos[] = 'lote';
                $vals[] = '?';
                $pars[] = (int)$grupo['lote'];
            }
            if (tabelaTemColunaDinamico($pdo, 'ciPostos', 'situacao')) {
                $campos[] = 'situacao';
                $vals[] = '?';
                $pars[] = 0;
            }

            if (!empty($campos)) {
                $sql = 'INSERT INTO ciPostos (' . implode(',', $campos) . ') VALUES (' . implode(',', $vals) . ')';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($pars);
                $okPostos++;
            }
        } catch (Exception $e) {
            $erros[] = $e->getMessage();
        }
    }

    die(json_encode(array(
        'success' => $okCsv > 0,
        'salvos_csv' => $okCsv,
        'salvos_postos' => $okPostos,
        'erros' => $erros
    )));
}

if ($pdo && isset($_POST['marcar_conferidos_pt'])) {
    header('Content-Type: application/json');
    $payload = isset($_POST['lotes']) ? $_POST['lotes'] : '[]';
    $lotes = json_decode($payload, true);
    $marcados = 0;
    if (is_array($lotes) && !empty($lotes)) {
        try {
            $placeholders = implode(',', array_fill(0, count($lotes), '?'));
            $stmt = $pdo->prepare("UPDATE ciPostosCsv SET conf=1 WHERE LPAD(CAST(lote AS CHAR),8,'0') IN ($placeholders)");
            $params = array();
            foreach ($lotes as $l) {
                $params[] = str_pad(preg_replace('/\D+/', '', (string)$l), 8, '0', STR_PAD_LEFT);
            }
            $stmt->execute($params);
            $marcados = $stmt->rowCount();
        } catch (Exception $e) {}
    }
    die(json_encode(array('success' => true, 'marcados' => $marcados)));
}

$modoCorreiosForced = !empty($_GET['modo_correios']);
$usuarioSessao = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Ofício Poupa Tempo Dinâmico v1.1.6</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Trebuchet MS", Tahoma, Verdana, sans-serif; background: linear-gradient(180deg, #f5f1ea 0%, #eef5f9 100%); color: #22313f; }
        .page { max-width: 1280px; margin: 0 auto; padding: 24px; }
        .hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 18px; }
        .hero h1 { margin: 0; font-size: 30px; color: #10324a; font-family: Georgia, "Times New Roman", serif; }
        .hero p { margin: 8px 0 0; color: #536372; }
        .badge { background: #10324a; color: #fff; padding: 8px 14px; border-radius: 999px; font-weight: bold; font-size: 12px; }
        .top-grid { display: grid; grid-template-columns: minmax(320px, 1.15fr) minmax(280px, 0.85fr); gap: 18px; margin-bottom: 18px; }
        .panel { background: rgba(255,255,255,0.94); border: 1px solid #d8e2ea; border-radius: 18px; box-shadow: 0 14px 32px rgba(16,50,74,0.1); padding: 18px; }
        .panel h2 { margin: 0 0 12px; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; color: #5b7284; }
        .scan-box { display: grid; gap: 12px; }
        #inputCodbar { width: 100%; padding: 16px 18px; border-radius: 14px; border: 2px solid #8fb3c8; font-size: 26px; letter-spacing: 1px; background: #fbfdff; }
        #inputCodbar:focus { outline: none; border-color: #0e7490; box-shadow: 0 0 0 4px rgba(14,116,144,0.15); }
        .hint { font-size: 13px; color: #667888; }
        .row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .toolbar button, .toolbar a, .btn, .field input, .field select { font: inherit; }
        .btn { border: none; border-radius: 12px; padding: 12px 16px; text-decoration: none; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; }
        .btn-secondary { background: linear-gradient(135deg, #334155 0%, #64748b 100%); color: #fff; }
        .btn-accent { background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%); color: #fff; }
        .btn-danger { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); color: #fff; }
        .metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .metric { background: #f6fafc; border: 1px solid #d9e7ef; border-radius: 14px; padding: 14px; }
        .metric strong { display: block; font-size: 26px; color: #10324a; margin-top: 6px; }
        .status { min-height: 44px; display: flex; align-items: center; padding: 10px 12px; border-radius: 12px; background: #edf6fb; color: #0f3d5a; font-weight: bold; }
        .status.error { background: #fdecec; color: #991b1b; }
        .workspace { display: grid; grid-template-columns: minmax(0, 1.9fr) minmax(300px, 0.6fr); gap: 18px; }
        .cards { display: grid; gap: 14px; }
        .posto-card { border: 1px solid #d4e0e8; border-radius: 18px; overflow: hidden; background: #fff; }
        .posto-head { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 14px 16px; background: linear-gradient(135deg, #10324a 0%, #1d5c78 100%); color: #fff; }
        .posto-head h3 { margin: 0; font-size: 18px; }
        .posto-head small { display: block; opacity: 0.9; margin-top: 4px; }
        .posto-total { font-size: 24px; font-weight: bold; white-space: nowrap; min-width: 64px; text-align: right; }
        .posto-head .posto-titulo { flex: 1 1 auto; min-width: 0; }
        .posto-head .lacre-grupo { flex: 0 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e7edf2; text-align: left; font-size: 14px; }
        th { background: #f7fafc; color: #5c7183; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; }
        tr.off td { opacity: 0.45; }
        .pill { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .pill.ok { background: #dcfce7; color: #166534; }
        .pill.pending { background: #fef3c7; color: #92400e; }
        .conferido td { background: #dcfce7 !important; }
        .side-block { margin-bottom: 16px; }
        .side-block h3 { margin: 0 0 10px; font-size: 15px; color: #24475f; }
        .empty { padding: 18px; text-align: center; color: #687b8b; border: 1px dashed #c9d6df; border-radius: 14px; background: #f9fbfd; }
        .field { display: grid; gap: 6px; margin-bottom: 10px; }
        .field label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.7px; color: #61788a; font-weight: bold; }
        .field input, .field select { width: 100%; padding: 11px 12px; border-radius: 12px; border: 1px solid #c7d6e0; background: #fff; }
        .inline-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #4d6476; margin: 10px 0 14px; }
        .footer-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .mini { font-size: 12px; color: #6e8292; }
        @media (max-width: 980px) {
            .top-grid, .workspace, .row, .metrics { grid-template-columns: 1fr; }
            .hero { flex-direction: column; }
            #inputCodbar { font-size: 20px; }
        }
    </style>
</head>
<body>
<audio id="audioPtBeep" src="beep_correio.mp3" preload="auto"></audio>
    <div class="page">
        <div class="hero">
            <div>
                <h1>Gerar Ofício Poupa Tempo Dinâmico</h1>
                <p>Leia os códigos, monte a lista por posto e envie apenas os lotes marcados para o modelo oficial.</p>
            </div>
            <div class="badge">v2.8.4</div>
        </div>

        <?php if ($erroConexao !== ''): ?>
            <div class="status error" style="margin-bottom:16px;">Falha de conexão com o banco: <?php echo e($erroConexao); ?></div>
        <?php endif; ?>

        <div class="top-grid">
            <section class="panel">
                <h2>Leitura</h2>
                <div class="scan-box">
                    <input type="text" id="inputCodbar" placeholder="Escaneie ou digite o código de barras" autocomplete="off">
                    <style>
                    @-webkit-keyframes pulsarObrig { 0%{box-shadow:0 0 0 0 rgba(255,87,34,.65);} 70%{box-shadow:0 0 0 9px rgba(255,87,34,0);} 100%{box-shadow:0 0 0 0 rgba(255,87,34,0);} }
                    @keyframes pulsarObrig { 0%{box-shadow:0 0 0 0 rgba(255,87,34,.65);} 70%{box-shadow:0 0 0 9px rgba(255,87,34,0);} 100%{box-shadow:0 0 0 0 rgba(255,87,34,0);} }
                    .campo-obrig-pulsar { border:2px solid #ff5722 !important; background:#fff8f5 !important; -webkit-animation:pulsarObrig 1.1s infinite; animation:pulsarObrig 1.1s infinite; }
                    </style>
                    <div class="row">
                        <div class="field">
                            <label for="responsavelPadrao">Responsável <span style="color:#ff5722;font-weight:bold;">*</span></label>
                            <input type="text" id="responsavelPadrao" value="" maxlength="30" placeholder="Digite seu nome (obrigatório)" oninput="atualizarPulsarResp()" class="campo-obrig-pulsar">
                        </div>
                    </div>
                    <div class="status" id="statusLeitura">Aguardando primeira leitura.</div>
                    <div class="hint">A data de produção vem do ciPostosCsv. Se o lote não estiver lá, digite a data na coluna "Data Produção".</div>
                    <div class="toolbar">
                        <button type="button" class="btn btn-primary" id="btnGerarModelo">Gerar modelo do ofício</button>
                        <button type="button" class="btn btn-secondary" id="btnFocarLeitura">Focar leitura</button>
                        <button type="button" class="btn btn-danger" id="btnLimparTudo">Limpar tela</button>
                        <a class="btn btn-accent" href="inicio.php">Voltar ao início</a>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2>Resumo</h2>
                <div class="metrics">
                    <div class="metric">Postos<strong id="metricPostos">0</strong></div>
                    <div class="metric">Lotes ativos<strong id="metricLotes">0</strong></div>
                    <div class="metric">CINs<strong id="metricQtd">0</strong></div>
                </div>
            </section>
        </div>

        <div class="workspace">
            <section class="panel">
                <h2>Montagem por posto</h2>
                <div id="cardsPostos" class="cards">
                    <div class="empty">Nenhum lote lido ainda.</div>
                </div>
            </section>

        </div>

        <form method="post" action="modelo_oficio_poupa_tempo.php" id="formModelo" style="display:none;">
            <input type="hidden" name="pt_datas" id="payloadDatas" value="">
            <input type="hidden" name="pt_dinamico_payload" id="payloadDinamico" value="">
            <input type="hidden" name="responsavel" id="payloadResponsavel" value="">
            <input type="hidden" name="pt_modo_visual" id="payloadModoVisual" value="<?php echo $modoCorreiosForced ? 'correios' : 'padrao'; ?>">
        </form>
    </div>

    <script>
    var estado = { postos: {}, ordem: [], pendencias: [] };
    var chavePendencias = 'pt_dinamico_pendencias_v1';
    var modoCorreiosAtivado = <?php echo $modoCorreiosForced ? 'true' : 'false'; ?>;
    var ultimoPostoLido = '';

    function formatarNumero(valor) {
        return Number(valor || 0).toLocaleString('pt-BR');
    }

    function formatarDataBr(valor) {
        var texto = String(valor || '').trim();
        var m = texto.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        return m ? (m[3] + '-' + m[2] + '-' + m[1]) : texto;
    }

    function status(texto, erro) {
        var el = document.getElementById('statusLeitura');
        if (!el) return;
        el.textContent = texto;
        el.className = erro ? 'status error' : 'status';
    }

    function agoraLocal() {
        var d = new Date();
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        var hh = String(d.getHours()).padStart(2, '0');
        var ii = String(d.getMinutes()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + ii;
    }

    function carregarPendencias() {
        try {
            var bruto = localStorage.getItem(chavePendencias);
            var lista = bruto ? JSON.parse(bruto) : [];
            estado.pendencias = Object.prototype.toString.call(lista) === '[object Array]' ? lista : [];
        } catch (e) {
            estado.pendencias = [];
        }
    }

    function salvarPendencias() {
        localStorage.setItem(chavePendencias, JSON.stringify(estado.pendencias));
    }

    function existeLote(codbar) {
        for (var posto in estado.postos) {
            if (!estado.postos.hasOwnProperty(posto)) continue;
            var itens = estado.postos[posto].lotes;
            for (var i = 0; i < itens.length; i++) {
                if (itens[i].codbar === codbar) {
                    return true;
                }
            }
        }
        return false;
    }

    function atualizarResumo() {
        var totalPostos = 0;
        var totalLotes = 0;
        var totalQtd = 0;
        for (var posto in estado.postos) {
            if (!estado.postos.hasOwnProperty(posto)) continue;
            var grupo = estado.postos[posto];
            var ativosDoPosto = 0;
            for (var i = 0; i < grupo.lotes.length; i++) {
                var item = grupo.lotes[i];
                if (item.ativo) {
                    ativosDoPosto++;
                    totalLotes++;
                    totalQtd += parseInt(item.quantidade, 10) || 0;
                }
            }
            if (ativosDoPosto > 0) {
                totalPostos++;
            }
        }
        document.getElementById('metricPostos').textContent = formatarNumero(totalPostos);
        document.getElementById('metricLotes').textContent = formatarNumero(totalLotes);
        document.getElementById('metricQtd').textContent = formatarNumero(totalQtd);
    }

    function renderizarPendencias() {
        var wrap = document.getElementById('listaPendenciasWrap');
        // v2.8.1: esta tela nao tem painel de pendencias no HTML; sem o elemento apenas
        // atualiza o resumo e sai (evita o crash de init em browser real, que NAO e pego
        // por php -l nem por HTTP 200).
        if (!wrap) { atualizarResumo(); return; }
        if (!estado.pendencias.length) {
            wrap.className = 'empty';
            wrap.innerHTML = 'Nenhuma pendência acumulada.';
            atualizarResumo();
            return;
        }
        var html = '<table><thead><tr><th>Lote</th><th>Posto</th><th>Qtd</th><th>Data</th><th>Responsável</th><th></th></tr></thead><tbody>';
        for (var i = 0; i < estado.pendencias.length; i++) {
            var item = estado.pendencias[i];
            html += '<tr>' +
                '<td>' + escapar(item.lote) + '</td>' +
                '<td>' + escapar(item.posto) + '</td>' +
                '<td>' + formatarNumero(item.quantidade) + '</td>' +
                '<td>' + formatarDataBr(item.dataexp) + '</td>' +
                '<td>' + escapar(item.responsavel || '') + '</td>' +
                '<td><button type="button" class="btn btn-danger" onclick="removerPendencia(' + i + ')" style="padding:6px 10px;">Remover</button></td>' +
                '</tr>';
        }
        html += '</tbody></table>';
        wrap.className = '';
        wrap.innerHTML = html;
        atualizarResumo();
    }

    function removerPendencia(idx) {
        estado.pendencias.splice(idx, 1);
        salvarPendencias();
        renderizarPendencias();
    }

    function obterTotalPosto(grupo) {
        var total = 0;
        for (var i = 0; i < grupo.lotes.length; i++) {
            if (grupo.lotes[i].ativo) {
                total += parseInt(grupo.lotes[i].quantidade, 10) || 0;
            }
        }
        return total;
    }

    function escapar(texto) {
        return String(texto || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderizarPostos() {
        var host = document.getElementById('cardsPostos');
        if (!estado.ordem.length) {
            host.innerHTML = '<div class="empty">Nenhum lote lido ainda.</div>';
            atualizarResumo();
            return;
        }
        var html = '';
        for (var o = 0; o < estado.ordem.length; o++) {
            var codigo = estado.ordem[o];
            var grupo = estado.postos[codigo];
            if (!grupo) continue;
            html += '<article class="posto-card">';
                        var lacrePtVal = escapar(grupo.lacre_pt || '');
            html += '<div class="posto-head">'
                + '<div class="posto-titulo"><h3>POUPA TEMPO ' + codigo + ' - ' + escapar(grupo.nome) + '</h3>'
                + '<small>' + escapar(grupo.endereco || 'Endereço não informado') + '</small></div>'
                + '<div class="lacre-grupo" style="display:flex;align-items:center;gap:6px;">'
                + '<label style="font-size:11px;color:rgba(255,255,255,.75);white-space:nowrap;">Lacre Poup. Tempo</label>'
                + '<input type="text" id="lacrePt_' + codigo + '" value="' + lacrePtVal + '" '
                + 'oninput="atualizarLacrePosto(\'' + codigo + '\',this.value)" '
                + 'style="width:80px;padding:4px 8px;border-radius:4px;border:none;font-size:14px;text-align:center;" '
                + 'placeholder="Nº lacre"></div>'
                + '<div class="posto-total">' + formatarNumero(obterTotalPosto(grupo)) + '</div>'
                + '</div>';
            html += '<table><thead><tr><th></th><th>Lote</th><th>Qtd</th><th>Data Produção</th><th></th></tr></thead><tbody>';
            for (var i = 0; i < grupo.lotes.length; i++) {
                var item = grupo.lotes[i];
                // v2.6.0 (I8): todo lote LIDO na montagem fica verde (conferido). Linhas
                // desativadas (excluidas da selecao) ficam apagadas ('off').
                html += '<tr class="' + (item.ativo ? 'conferido' : 'off') + '">';
                html += '<td><input type="checkbox" ' + (item.ativo ? 'checked ' : '') + 'onchange="alternarAtivo(\'' + codigo + '\',' + i + ',this.checked)"></td>';
                html += '<td>' + item.lote + '</td>';
                html += '<td>' + formatarNumero(item.quantidade) + '</td>';
                // v2.7.0 (A): data de expedicao = data de upload em ciPostosCsv. Sem data
                // encontrada -> CELULA AMARELA e input date editavel p/ digitar manual; o valor
                // (YYYY-MM-DD) flui no payload e imprime igual as demais linhas (mesmo <td>/fonte).
                var dataVal = item.data_carga || '';
                var semData = (dataVal === '');
                var estiloData = 'font-size:13px;padding:3px 5px;border-radius:4px;border:1px solid #c9d6df;' + (semData ? 'background:#fff3cd;border:2px solid #ffc107;' : '');
                var avisoData = semData ? ' <span style="font-size:10px;color:#856404;font-weight:bold;">digite a data</span>' : '';
                html += '<td' + (semData ? ' style="background:#fff3cd !important;"' : '') + '><input type="date" value="' + dataVal + '" onchange="atualizarDataPosto(\'' + codigo + '\',' + i + ',this.value)" style="' + estiloData + '">' + avisoData + '</td>';
                html += '<td><button type="button" class="btn btn-danger btn-excluir-x" onclick="removerItem(\'' + codigo + '\',' + i + ')" title="Excluir linha" style="padding:2px 9px;font-weight:bold;line-height:1;font-size:16px;">&times;</button></td>';
                html += '</tr>';
            }
            html += '</tbody></table></article>';
        }
        host.innerHTML = html;
        atualizarResumo();
    }

    function atualizarLacrePosto(codigo, valor) {
        if (estado.postos[codigo]) {
            estado.postos[codigo].lacre_pt = valor;
        }
        // v2.6.0 (I6): auto-incremento — do card editado para baixo, +1 a cada posto.
        // So quando o valor digitado for numerico; ao limpar/nao-numerico nao mexe nos de baixo.
        // Atualiza estado + .value direto (sem re-render -> preserva o foco do input atual).
        var base = parseInt(String(valor).replace(/\D/g, ''), 10);
        if (isNaN(base)) { return; }
        var startIdx = -1;
        for (var k = 0; k < estado.ordem.length; k++) {
            if (estado.ordem[k] === codigo) { startIdx = k; break; }
        }
        if (startIdx < 0) { return; }
        var n = base;
        for (var j = startIdx + 1; j < estado.ordem.length; j++) {
            n++;
            var c = estado.ordem[j];
            if (!estado.postos[c]) { continue; }
            estado.postos[c].lacre_pt = String(n);
            var inp = document.getElementById('lacrePt_' + c);
            if (inp) { inp.value = String(n); }
        }
    }

    // v2.7.0 (A): grava a data de expedicao digitada manualmente (celula amarela) e
    // re-renderiza p/ tirar o destaque amarelo assim que a data passa a existir.
    function atualizarDataPosto(codigo, idx, valor) {
        if (estado.postos[codigo] && estado.postos[codigo].lotes[idx]) {
            estado.postos[codigo].lotes[idx].data_carga = valor || '';
            // v2.8.1: a data digitada manualmente tambem sincroniza a pendencia (mesmo
            // codbar), senao "Inserir pendencias" gravaria o lote sem data e o pularia.
            var cb = estado.postos[codigo].lotes[idx].codbar;
            if (cb) {
                for (var p = 0; p < estado.pendencias.length; p++) {
                    if (estado.pendencias[p].codbar === cb) {
                        estado.pendencias[p].dataexp = valor || '';
                        break;
                    }
                }
                salvarPendencias();
                renderizarPendencias();
            }
            renderizarPostos();
        }
    }

    function alternarAtivo(codigo, idx, checked) {
        if (estado.postos[codigo] && estado.postos[codigo].lotes[idx]) {
            estado.postos[codigo].lotes[idx].ativo = !!checked;
            renderizarPostos();
        }
    }

    function removerItem(codigo, idx) {
        if (!estado.postos[codigo]) return;
        estado.postos[codigo].lotes.splice(idx, 1);
        if (!estado.postos[codigo].lotes.length) {
            delete estado.postos[codigo];
            var novaOrdem = [];
            for (var i = 0; i < estado.ordem.length; i++) {
                if (estado.ordem[i] !== codigo) {
                    novaOrdem.push(estado.ordem[i]);
                }
            }
            estado.ordem = novaOrdem;
        }
        renderizarPostos();
    }

    function adicionarPendencia(resp) {
        for (var i = 0; i < estado.pendencias.length; i++) {
            if (estado.pendencias[i].codbar === resp.codbar) {
                return;
            }
        }
        estado.pendencias.push({
            codbar: resp.codbar,
            lote: resp.lote,
            regional: resp.regional,
            posto: resp.posto,
            quantidade: resp.quantidade,
            dataexp: resp.data_carga,
            responsavel: document.getElementById('responsavelPadrao').value.trim()
        });
        salvarPendencias();
        renderizarPendencias();
    }

    // === VOZ: anuncia quando muda de posto ===
    function falarPosto(nomePosto) {
        try {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            var num = parseInt(String(nomePosto).replace(/\D+/g, ''), 10);
            var texto = 'Iniciando posto ' + (isNaN(num) ? nomePosto : num);
            var u = new SpeechSynthesisUtterance(texto);
            u.lang = 'pt-BR'; u.rate = 1.05; u.pitch = 1;
            window.speechSynthesis.speak(u);
        } catch(e) {}
    }

    function tocarBeepPt() {
        try {
            var a = document.getElementById('audioPtBeep');
            if (a) { a.currentTime = 0; a.play(); }
        } catch(e) {}
    }

    // === OTIMISTA: mostra linha/card imediatamente com dados do código de barras ===
    function adicionarLoteOtimista(codbar, lote, posto3, qtdBarra) {
        if (existeLote(codbar)) return; // já existe
        var novoPosto = !estado.postos[posto3];
        if (novoPosto) {
            estado.postos[posto3] = {
                codigo: posto3,
                nome: 'POUPA TEMPO ' + parseInt(posto3, 10) + ' — carregando…',
                endereco: '',
                usuario: '',
                lacre_pt: '',
                lotes: [],
                aguardandoNome: true
            };
            estado.ordem.push(posto3);
            estado.ordem.sort();
        }
        // Anuncia troca de posto IMEDIATAMENTE (sem aguardar AJAX)
        if (ultimoPostoLido && posto3 !== ultimoPostoLido) {
            falarPosto(posto3);
            status('⚠ Leitura do posto ' + parseInt(posto3,10) + ' (vinha do posto ' + parseInt(ultimoPostoLido,10) + ') — adicionando automaticamente.', false);
        }
        ultimoPostoLido = posto3;
        estado.postos[posto3].lotes.push({
            codbar: codbar,
            lote: lote,
            quantidade: qtdBarra,
            // v2.8.1: nasce SEM data (celula amarela) ate o AJAX trazer a data real de
            // ciPostosCsv; se nao houver, o usuario digita na coluna "Data Producao".
            data_carga: '',
            responsaveis: '',
            carregado: false,
            ativo: true,
            otimista: true
        });
        renderizarPostos();
    }

    // === Atualiza lote otimista com dados reais do servidor ===
    function atualizarLoteOtimista(resp) {
        var g = estado.postos[resp.posto];
        if (!g) return;
        // Atualizar nome/endereço do posto se ainda estava carregando
        if (g.aguardandoNome) {
            g.nome = resp.nome || g.nome;
            g.endereco = resp.endereco || '';
            g.usuario = resp.responsaveis || '';
            g.aguardandoNome = false;
        }
        // Atualizar o lote específico
        for (var i = 0; i < g.lotes.length; i++) {
            if (g.lotes[i].codbar === resp.codbar) {
                g.lotes[i].lote = resp.lote || g.lotes[i].lote;
                g.lotes[i].quantidade = parseInt(resp.quantidade, 10) || g.lotes[i].quantidade;
                g.lotes[i].data_carga = resp.data_carga || '';
                g.lotes[i].responsaveis = resp.responsaveis || '';
                g.lotes[i].carregado = !!resp.carregado;
                delete g.lotes[i].otimista;
                break;
            }
        }
        renderizarPostos();
        if (!resp.carregado) adicionarPendencia(resp);
        status((resp.carregado ? 'Lote carregado incluído: ' : 'Lote pendente incluído: ') + resp.posto + ' / ' + resp.lote, false);
    }

    function adicionarLote(resp) {
        // Se já foi adicionado otimisticamente, apenas atualizar com dados reais
        if (existeLote(resp.codbar)) {
            atualizarLoteOtimista(resp);
            return;
        }
        // Caso não-otimista (barcode 17 dígitos ou posto ainda não criado)
        tocarBeepPt();
        if (ultimoPostoLido && resp.posto !== ultimoPostoLido) {
            falarPosto(resp.posto);
        }
        ultimoPostoLido = resp.posto;
        if (!estado.postos[resp.posto]) {
            estado.postos[resp.posto] = {
                codigo: resp.posto,
                nome: resp.nome,
                endereco: resp.endereco,
                usuario: resp.responsaveis || '',
                lacre_pt: '',
                lotes: []
            };
            estado.ordem.push(resp.posto);
            estado.ordem.sort();
        }
        estado.postos[resp.posto].lotes.push({
            codbar: resp.codbar,
            lote: resp.lote,
            quantidade: parseInt(resp.quantidade, 10) || 0,
            data_carga: resp.data_carga,
            responsaveis: resp.responsaveis || '',
            carregado: !!resp.carregado,
            ativo: true
        });
        renderizarPostos();
        if (!resp.carregado) adicionarPendencia(resp);
        status((resp.carregado ? 'Lote carregado incluído: ' : 'Lote pendente incluído: ') + resp.posto + ' / ' + resp.lote, false);
    }

    function setPulsarResp(on) {
        var el = document.getElementById('responsavelPadrao');
        if (!el) return;
        var c = (' ' + el.className + ' ').replace(/\s+/g, ' ');
        c = c.replace(' campo-obrig-pulsar ', ' ');
        if (on) { c = c + 'campo-obrig-pulsar '; }
        el.className = c.replace(/^\s+|\s+$/g, '');
    }
    function atualizarPulsarResp() {
        var el = document.getElementById('responsavelPadrao');
        setPulsarResp(!el || el.value.replace(/^\s+|\s+$/g, '') === '');
    }
    function responsavelPreenchido() {
        var el = document.getElementById('responsavelPadrao');
        return !!(el && el.value.replace(/^\s+|\s+$/g, '') !== '');
    }

    function lerCodigo(codigo) {
        // ITEM 2: Responsável é OBRIGATÓRIO antes de qualquer leitura.
        if (!responsavelPreenchido()) {
            status('⚠ Digite o nome do RESPONSÁVEL antes de ler os códigos.', true);
            setPulsarResp(true);
            var elR = document.getElementById('responsavelPadrao');
            if (elR) elR.focus();
            return;
        }
        var limpo = String(codigo || '').replace(/\D+/g, '');
        if (limpo.length !== 17 && limpo.length !== 19) {
            if (limpo.length > 0) {
                status('Código inválido: ' + limpo.length + ' dígitos. Use 17 ou 19 dígitos.', true);
            }
            return;
        }
        if (limpo.length > 19) limpo = limpo.substr(0, 19);

        // === Verificar duplicata ANTES de qualquer ação ===
        if (existeLote(limpo)) {
            status('⚠ Lote já foi conferido — código já está na lista.', true);
            return;
        }

        // === FASE 1: reação IMEDIATA (sem aguardar servidor) ===
        tocarBeepPt();
        if (limpo.length === 19) {
            var loteOt  = limpo.substr(0, 8);
            var postoOt = limpo.substr(11, 3);
            var qtdOt   = parseInt(limpo.substr(14, 5), 10) || 0;
            adicionarLoteOtimista(limpo, loteOt, postoOt, qtdOt);
            status('Verificando lote ' + loteOt + ' no servidor…', false);
        } else {
            status('Buscando lote...', false);
        }

        // === FASE 2: AJAX para dados reais (nome, endereço, qtd real) ===
        var formData = new FormData();
        formData.append('ajax_resolver_codbar_pt', '1');
        formData.append('codbar', limpo);
        formData.append('responsavel', document.getElementById('responsavelPadrao').value.trim() || '');
        fetch('gera_oficio_poupa_tempo_dinamico.php', {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); }).then(function(resp) {
            if (!resp.success) {
                // Remover lote otimista que falhou
                removerLoteOtimistaPorCodbar(limpo);
                status(resp.erro || 'Falha ao resolver leitura.', true);
                return;
            }
            adicionarLote(resp);
            // v2.6.0 (I7/I8): o lote fica verde localmente (lido), mas se o conf='s' NAO
            // gravou no banco, avisa para nao dar verde enganoso (o save e best-effort).
            if (resp.conferencia_ok === false) {
                status('Lote lido, mas a conferência NÃO foi gravada no banco'
                    + (resp.conferencia_erro ? ': ' + resp.conferencia_erro : '')
                    + '. Verifique o servidor.', true);
            }
        }).catch(function(err) {
            // Erro de rede/comunicação: NÃO remove o lote da tela (mantém o que o usuário escaneou)
            status('Falha de comunicação: ' + (err && err.message ? err.message : 'verifique a conexão.'), true);
        });
    }

    // Remove lote otimista se o servidor retornar erro
    function removerLoteOtimistaPorCodbar(codbar) {
        for (var p in estado.postos) {
            if (!estado.postos.hasOwnProperty(p)) continue;
            var lotes = estado.postos[p].lotes;
            for (var i = lotes.length - 1; i >= 0; i--) {
                if (lotes[i].codbar === codbar && lotes[i].otimista) {
                    lotes.splice(i, 1);
                }
            }
            if (!lotes.length && estado.postos[p].aguardandoNome) {
                delete estado.postos[p];
                estado.ordem = estado.ordem.filter(function(x){ return x !== p; });
            }
        }
        renderizarPostos();
    }

    function montarPayloadModelo() {
        var postos = [];
        var datas = {};
        var datasLista = [];
        for (var i = 0; i < estado.ordem.length; i++) {
            var codigo = estado.ordem[i];
            var grupo = estado.postos[codigo];
            if (!grupo) continue;
            var lotes = [];
            for (var j = 0; j < grupo.lotes.length; j++) {
                var item = grupo.lotes[j];
                if (!item.ativo) continue;
                lotes.push({
                    lote: item.lote,
                    quantidade: item.quantidade,
                    data_carga: item.data_carga,
                    responsaveis: item.responsaveis || document.getElementById('responsavelPadrao').value.trim()
                });
                if (item.data_carga) {
                    datas[item.data_carga] = true;
                }
            }
            if (!lotes.length) continue;
            postos.push({
                codigo: codigo,
                nome: grupo.nome,
                endereco: grupo.endereco,
                usuario: grupo.usuario || document.getElementById('responsavelPadrao').value.trim(),
                lacre_pt: grupo.lacre_pt || '',
                lotes: lotes
            });
        }
        for (var dataRef in datas) {
            if (datas.hasOwnProperty(dataRef)) {
                datasLista.push(dataRef);
            }
        }
        return {
            postos: postos,
            datas: datasLista
        };
    }

    function temPostoInterior(postos) {
        for (var i = 0; i < postos.length; i++) {
            var num = parseInt(String(postos[i].codigo).replace(/\D+/g, ''), 10);
            if (!isNaN(num) && num > 80) {
                return true;
            }
        }
        return false;
    }

    function gerarModelo() {
        var payload = montarPayloadModelo();
        if (!payload.postos.length) {
            status('Marque ao menos um lote antes de gerar o modelo.', true);
            return;
        }
        var modoVisual = (modoCorreiosAtivado || temPostoInterior(payload.postos)) ? 'correios' : 'padrao';
        document.getElementById('payloadDinamico').value = JSON.stringify(payload);
        document.getElementById('payloadDatas').value = payload.datas.join(',');
        document.getElementById('payloadResponsavel').value = document.getElementById('responsavelPadrao').value.trim() || '';
        document.getElementById('payloadModoVisual').value = modoVisual;
        document.getElementById('formModelo').target = '_blank';
        document.getElementById('formModelo').submit();
    }

    function mostrarMsgPt(texto, sucesso) {
        var el = document.getElementById('msgSalvamentoPt');
        if (!el) return;
        el.textContent = texto;
        el.style.display = 'block';
        if (sucesso) {
            el.style.background = '#e8f5e9';
            el.style.color = '#1b5e20';
            el.style.border = '1px solid #a5d6a7';
            setTimeout(function() { el.style.display = 'none'; }, 8000);
        } else {
            el.style.background = '#fdecec';
            el.style.color = '#991b1b';
            el.style.border = '1px solid #f5c6cb';
        }
    }

    function salvarPendenciasNoBanco() {
        if (!estado.pendencias.length) {
            status('Não há pendências para salvar.', true);
            return;
        }
        var responsavelSalv = document.getElementById('responsavelSalvamento');
        var responsavel = responsavelSalv ? responsavelSalv.value.trim() : '';
        if (!responsavel) {
            responsavel = document.getElementById('responsavelPadrao').value.trim();
        }
        if (!responsavel) {
            mostrarMsgPt('Informe o responsável pelo salvamento antes de gravar.', false);
            if (responsavelSalv) responsavelSalv.focus();
            return;
        }
        var btnSalvar = document.getElementById('btnSalvarPendencias');
        if (btnSalvar) { btnSalvar.disabled = true; btnSalvar.textContent = 'Salvando...'; }
        var formData = new FormData();
        formData.append('inserir_pendencias_pt', '1');
        formData.append('pacotes', JSON.stringify(estado.pendencias));
        formData.append('usuario', responsavel);
        formData.append('autor_salvamento', responsavel);
        formData.append('criado_salvamento', document.getElementById('criadoPendencia').value || '');
        formData.append('turno_salvamento', document.getElementById('turnoPendencia').value || 'Manhã');
        if (document.getElementById('consolidarPendencia').checked) {
            formData.append('consolidar_salvamento', '1');
        }
        fetch('gera_oficio_poupa_tempo_dinamico.php', {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); }).then(function(resp) {
            if (btnSalvar) { btnSalvar.disabled = false; btnSalvar.textContent = 'Salvar pendências no banco'; }
            if (!resp.success) {
                mostrarMsgPt(resp.erro || 'Falha ao salvar pendências.', false);
                status('Falha ao salvar pendências.', true);
                return;
            }
            var erros = (resp.erros && resp.erros.length) ? resp.erros.length : 0;
            var msg = 'Gravado com sucesso! ciPostosCsv: ' + (resp.salvos_csv || 0) + ' | ciPostos: ' + (resp.salvos_postos || 0) + ' | Responsável: ' + responsavel;
            if (erros > 0) { msg += ' (' + erros + ' erro(s))'; }
            mostrarMsgPt(msg, true);
            status(msg, false);
            var lotesParaConferir = [];
            for (var kc = 0; kc < estado.pendencias.length; kc++) {
                if (estado.pendencias[kc].lote) lotesParaConferir.push(estado.pendencias[kc].lote);
            }
            if (lotesParaConferir.length) {
                var fdConf = new FormData();
                fdConf.append('marcar_conferidos_pt', '1');
                fdConf.append('lotes', JSON.stringify(lotesParaConferir));
                fetch('gera_oficio_poupa_tempo_dinamico.php', { method: 'POST', body: fdConf });
            }
            estado.pendencias = [];
            salvarPendencias();
            renderizarPendencias();
        }).catch(function() {
            if (btnSalvar) { btnSalvar.disabled = false; btnSalvar.textContent = 'Salvar pendências no banco'; }
            mostrarMsgPt('Erro de comunicação ao salvar pendências.', false);
            status('Erro ao salvar pendências.', true);
        });
    }

    function limparTudo() {
        estado.postos = {};
        estado.ordem = [];
        renderizarPostos();
        status('Tela limpa.', false);
    }

    document.getElementById('btnGerarModelo').addEventListener('click', gerarModelo);
    document.getElementById('btnLimparTudo').addEventListener('click', limparTudo);
    document.getElementById('btnFocarLeitura').addEventListener('click', function() {
        document.getElementById('inputCodbar').focus();
    });
    // Debounce: scanners de 19 digitos passam por length===17 enquanto digitam.
    // Se dispararmos imediatamente em 17 chars, truncamos um codigo de 19. Solucao:
    //  - length >= 19  => dispara IMEDIATO (nao ha como crescer mais util)
    //  - length === 17 => aguarda ~180ms; se nao chegar mais nada, dispara como 17;
    //                     se aparecerem novos digitos, o timer reseta e quando atingir 19 dispara.
    var leituraTimer = null;
    function cancelarLeituraTimer() { if (leituraTimer) { clearTimeout(leituraTimer); leituraTimer = null; } }
    document.getElementById('inputCodbar').addEventListener('input', function() {
        var campo = this;
        var limpo = campo.value.replace(/\D+/g, '');
        if (limpo.length >= 19) {
            cancelarLeituraTimer();
            campo.value = '';
            lerCodigo(limpo);
            return;
        }
        if (limpo.length === 17) {
            cancelarLeituraTimer();
            leituraTimer = setTimeout(function() {
                leituraTimer = null;
                // Re-le o valor atual: pode ter crescido enquanto esperavamos
                var atual = campo.value.replace(/\D+/g, '');
                if (atual.length === 17) {
                    campo.value = '';
                    lerCodigo(atual);
                } else if (atual.length >= 19) {
                    campo.value = '';
                    lerCodigo(atual);
                }
                // se ficou entre 18 e 19 (estranho), aguarda proximo evento
            }, 180);
            return;
        }
        // length mudou para algo diferente de 17 enquanto timer rodava -> cancela
        if (limpo.length < 17 && leituraTimer) cancelarLeituraTimer();
    });
    document.getElementById('inputCodbar').addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            var valor = this.value;
            var soDig = valor.replace(/\D+/g, '');
            // Ignora Enter quando o input tem menos de 17 digitos (digitacao acidental
            // ou Enter precoce do scanner). Evita o erro "2 digitos. Use 17 ou 19".
            if (soDig.length < 17) {
                this.value = '';
                return;
            }
            this.value = '';
            lerCodigo(valor);
        }
    });
    document.addEventListener('keydown', function() {
        var campo = document.getElementById('inputCodbar');
        var ativo = document.activeElement;
        if (ativo) {
            var tag = (ativo.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button') return;
        }
        if (ativo !== campo) campo.focus();
    });

    var elCriadoPend = document.getElementById('criadoPendencia');
    if (elCriadoPend) elCriadoPend.value = agoraLocal();
    var respPadrao = document.getElementById('responsavelPadrao');
    var respSalv = document.getElementById('responsavelSalvamento');
    if (respPadrao && respSalv && !respSalv.value) {
        respSalv.value = respPadrao.value;
    }
    if (respPadrao && respSalv) {
        respPadrao.addEventListener('input', function() {
            if (!respSalv.value) {
                respSalv.value = respPadrao.value;
            }
        });
    }
    atualizarPulsarResp();
    carregarPendencias();
    renderizarPendencias();
    renderizarPostos();
    document.getElementById('inputCodbar').focus();
    </script>
</body>
</html>