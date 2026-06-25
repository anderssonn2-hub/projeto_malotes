<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
// despachos_poupatempo.php
// Tela de consulta de ofícios e lotes (inicialmente focado no POUPA TEMPO)

// 1) CONEXÃO DIRETA COM O BANCO --------------------------------------------
// COPIE daqui o mesmo DSN/usuário/senha que já usa no lacres_novo.php
try {
    $pdo_controle = new PDO(
        "mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=controle;charset=utf8", // <-- AJUSTE HOST/DB
        (getenv('DB_USER') ?: 'controle_mat'),                                          // <-- AJUSTE USUÁRIO
        (getenv('DB_PASS') ?: '375256')                                             // <-- AJUSTE SENHA
    );
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// 2) SESSÃO (se precisar do usuário no futuro) ------------------------------
if (!isset($_SESSION)) {
    session_start();
}

// 3) FILTROS ----------------------------------------------------------------
$grupo       = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
$f_data_ini  = isset($_GET['data_ini']) ? trim($_GET['data_ini']) : '';
$f_data_fim  = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';
$f_datas     = isset($_GET['datas']) ? trim($_GET['datas']) : '';
$f_lote      = isset($_GET['lote'])  ? trim($_GET['lote'])  : '';
$f_etiqueta  = isset($_GET['etiqueta']) ? trim($_GET['etiqueta']) : '';
$id_despacho = isset($_GET['id'])    ? (int)$_GET['id']     : 0;

// Converter datas para formato SQL (Versao 4)
function converterDataParaSQL($data) {
    if (empty($data)) return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return $data;
    }
    if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $data, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return $data;
}

$data_ini_sql = converterDataParaSQL($f_data_ini);
$data_fim_sql = converterDataParaSQL($f_data_fim);
if (empty($data_fim_sql) && !empty($data_ini_sql)) {
    $data_fim_sql = $data_ini_sql;
}

// 4) LISTA DE DESPACHOS -----------------------------------------------------
$params = array();

// SELECT base (Versao 5: contagem de postos via ciDespachoLotes)
$sqlLista = "
    SELECT 
        d.id,
        d.grupo,
        d.datas_str,
        d.usuario,
        d.ativo,
        d.criado_at,
        (SELECT COUNT(DISTINCT lp.posto) FROM ciDespachoLotes lp WHERE lp.id_despacho = d.id) AS num_postos,
        (SELECT SUM(COALESCE(lp2.quantidade,0)) FROM ciDespachoLotes lp2 WHERE lp2.id_despacho = d.id) AS total_carteiras
    FROM ciDespachos d
    LEFT JOIN ciDespachoItens i ON i.id_despacho = d.id
";

// Se houver filtro por lote ou etiqueta, junta com a tabela de lotes
if ($f_lote !== '' || $f_etiqueta !== '') {
    $sqlLista .= " LEFT JOIN ciDespachoLotes l ON l.id_despacho = d.id ";
}

$sqlLista .= " WHERE 1=1 ";

// filtro por grupo (Versao 4: apenas Poupa Tempo ou Correios)
if ($grupo !== '' && $grupo !== 'TODOS') {
    $sqlLista   .= " AND d.grupo = ? ";
    $params[]    = $grupo;
}

// filtro por intervalo de datas (Versao 4: calendario usando ciDespachoLotes)
if (!empty($data_ini_sql)) {
    $sqlLista .= " AND EXISTS (
        SELECT 1 FROM ciDespachoLotes dl 
        WHERE dl.id_despacho = d.id 
        AND dl.data_carga >= ? 
        AND dl.data_carga <= ?
    ) ";
    $params[] = $data_ini_sql;
    $params[] = $data_fim_sql;
}

// filtro por datas_str (texto salvo no ofício, ex.: 21/11/2025,22/11/2025)
if ($f_datas !== '') {
    $sqlLista   .= " AND d.datas_str LIKE ? ";
    $params[]    = '%' . $f_datas . '%';
}

// filtro por etiqueta correios (Versao 4)
if ($f_etiqueta !== '') {
    $sqlLista .= " AND (i.etiqueta_correios LIKE ? OR l.etiqueta_correios LIKE ?) ";
    $params[] = '%' . $f_etiqueta . '%';
    $params[] = '%' . $f_etiqueta . '%';
}

// filtro por lote
if ($f_lote !== '') {
    $sqlLista   .= " AND l.lote LIKE ? ";
    $params[]    = '%' . $f_lote . '%';
}

$sqlLista .= "
    GROUP BY d.id, d.grupo, d.datas_str, d.usuario, d.ativo
    ORDER BY d.id DESC
    LIMIT 200
";

$stmtLista = $pdo_controle->prepare($sqlLista);
$stmtLista->execute($params);
$despachos = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

// 5) DETALHES DE UM DESPACHO ESPECÍFICO ------------------------------------
$itens = array();
$lotes = array();
$despacho_tipo = '';

if ($id_despacho > 0) {
    // Versao 6: Primeiro verificar o tipo do despacho
    $stTipo = $pdo_controle->prepare("SELECT grupo FROM ciDespachos WHERE id = ?");
    $stTipo->execute(array($id_despacho));
    $rowTipo = $stTipo->fetch();
    $despacho_tipo = $rowTipo ? $rowTipo['grupo'] : '';
    
    // Itens por posto
    $stItens = $pdo_controle->prepare("
        SELECT id, id_despacho, regional, posto, nome_posto, endereco,
               lote, quantidade, lacre_iipr, lacre_correios, etiqueta_correios
          FROM ciDespachoItens
         WHERE id_despacho = ?
         ORDER BY LPAD(posto,3,'0')
    ");
    $stItens->execute(array($id_despacho));
    $itens = $stItens->fetchAll(PDO::FETCH_ASSOC);

    // Detalhe por lote (Versao 6: inclui etiqueta_correios e cruzamento com conferencia_pacotes)
    // NOTA: Apenas considera conferido se cp.conf = 'S' (status conferido)
    $stLotes = $pdo_controle->prepare("
        SELECT 
            l.id, 
            l.id_despacho, 
            l.posto, 
            l.lote, 
            l.quantidade, 
            l.data_carga, 
            l.responsaveis,
            l.etiqueta_correios,
            COALESCE(l.etiquetaiipr, 0) AS etiquetaiipr,
            COALESCE(l.etiquetacorreios, 0) AS etiquetacorreios,
            cp.usuario AS conferido_por,
            cp.lido_em AS conferido_em,
            CASE WHEN cp.id IS NOT NULL AND cp.conf = 'S' THEN 'S' ELSE 'N' END AS conferido
          FROM ciDespachoLotes l
          LEFT JOIN conferencia_pacotes cp ON cp.nlote = CAST(l.lote AS UNSIGNED) AND cp.conf = 'S'
         WHERE l.id_despacho = ?
         ORDER BY LPAD(l.posto,3,'0'), l.lote
    ");
    $stLotes->execute(array($id_despacho));
    $lotes = $stLotes->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Consulta de Ofícios e Lotes - Poupa Tempo</title>
<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #f2f2f2;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1200px;
        margin: 20px auto;
        background: #fff;
        padding: 15px 20px;
        border-radius: 6px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    h1 {
        margin-top: 0;
        font-size: 22px;
        text-align: center;
    }
    .filtros {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fafafa;
    }
    .filtros label {
        display: inline-block;
        margin-right: 10px;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .filtros input[type="text"],
    .filtros select {
        padding: 4px 6px;
        font-size: 13px;
    }
    .filtros button {
        padding: 6px 12px;
        font-size: 13px;
        background: #007bff;
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
    .filtros button:hover {
        background: #0056b3;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 5px 6px;
        font-size: 12px;
        text-align: left;
    }
    th {
        background: #e9e9e9;
    }
    tr:nth-child(even) {
        background: #f9f9f9;
    }
    .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        color: #fff;
    }
    .badge-ativo {
        background: #28a745;
    }
    .badge-inativo {
        background: #dc3545;
    }
    .acoes a {
        display: inline-block;
        padding: 3px 6px;
        margin-right: 4px;
        font-size: 11px;
        text-decoration: none;
        color: #fff;
        border-radius: 3px;
        background: #17a2b8;
    }
    .acoes a:hover {
        background: #11707f;
    }
    .subtitulo {
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .totais {
        font-size: 12px;
        margin: 4px 0 10px 0;
    }
</style>
</head>
<body>
<div class="container">
    <h1>Consulta de Ofícios e Lotes</h1>

    <!-- FILTROS (Versao 4) ---------------------------------------------------->
    <form method="get" class="filtros">
        <label>
            Tipo:
            <select name="grupo">
                <option value=""<?php if ($grupo=='') echo ' selected'; ?>>Todos</option>
                <option value="POUPA TEMPO"<?php if ($grupo=='POUPA TEMPO') echo ' selected'; ?>>Poupa Tempo</option>
                <option value="CORREIOS"<?php if ($grupo=='CORREIOS') echo ' selected'; ?>>Correios</option>
            </select>
        </label>

        <label>
            Data Inicial:
            <input type="date" name="data_ini" 
                   value="<?php echo htmlspecialchars($f_data_ini, ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <label>
            Data Final:
            <input type="date" name="data_fim" 
                   value="<?php echo htmlspecialchars($f_data_fim, ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <label>
            Etiqueta Correios:
            <input type="text" name="etiqueta" size="20"
                   value="<?php echo htmlspecialchars($f_etiqueta, ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="Ex: 1325467896549...">
        </label>

        <label>
            Lote:
            <input type="text" name="lote" size="12"
                   value="<?php echo htmlspecialchars($f_lote, ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="00752835">
        </label>

        <button type="submit">Filtrar</button>
        <a href="despachos_poupatempo.php" style="margin-left:8px; padding:6px 12px; background:#6c757d; color:#fff; text-decoration:none; border-radius:3px; font-size:13px;">Limpar</a>
    </form>

    <!-- LISTA DE DESPACHOS --------------------------------------------------->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Grupo</th>
                <th>Datas</th>
                <th>Usuário</th>
                <th>Status</th>
                <th>Postos</th>
                <th>Total Carteiras</th>
                <th>PDF</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($despachos)): ?>
            <tr><td colspan="9">Nenhum ofício encontrado com os filtros informados.</td></tr>
        <?php else: ?>
            <?php foreach ($despachos as $d): ?>
                <tr>
                    <td><?php echo (int)$d['id']; ?></td>
                    <td><?php echo htmlspecialchars($d['grupo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($d['datas_str'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($d['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if ($d['ativo']): ?>
                            <span class="badge badge-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-inativo">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$d['num_postos']; ?></td>
                    <td><?php echo number_format((int)$d['total_carteiras'], 0, ',', '.'); ?></td>
                    <td style="text-align:center;">
                        <?php
                        // v2.0.0: Link para <base>/cioficios/{id}_{tipo}.pdf (sem data)
                        // tipo = "correios" ou "poupatempo". <base> = dirname(SCRIPT_NAME).
                        $pdf_link = '';
                        if ((int)$d['id'] > 0 && !empty($d['grupo'])) {
                            $tipo_lower   = strtolower(str_replace(' ', '', trim((string)$d['grupo'])));
                            $nome_arquivo = (int)$d['id'] . '_' . $tipo_lower . '.pdf';
                            $base_dir     = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                            if ($base_dir === '' || $base_dir === '.') $base_dir = '';
                            $pdf_link = $base_dir . '/cioficios/' . rawurlencode($nome_arquivo);
                        }
                        ?>
                        <?php if ($pdf_link): ?>
                            <a href="<?php echo htmlspecialchars($pdf_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Abrir PDF do Oficio" style="color:#007bff; font-size:16px;">&#128196;</a>
                        <?php else: ?>
                            <span style="color:#999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="acoes">
                        <a href="?grupo=<?php echo urlencode($grupo); ?>&datas=<?php echo urlencode($f_datas); ?>&lote=<?php echo urlencode($f_lote); ?>&id=<?php echo (int)$d['id']; ?>">
                            Ver detalhes
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- DETALHES DO DESPACHO SELECIONADO ------------------------------------>
    <?php if ($id_despacho > 0): ?>
        <div id="detalhes">
            <div class="subtitulo">Detalhes do Ofício Nº <?php echo (int)$id_despacho; ?> 
                <?php if ($despacho_tipo): ?>
                    <span style="background:#fd7e14; color:#fff; padding:2px 8px; border-radius:4px; font-size:12px; margin-left:10px;">
                        <?php echo htmlspecialchars($despacho_tipo, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Versao 6: Resumo geral baseado em ciDespachoLotes (funciona para todos os tipos) -->
            <?php
            $totalPostosLotes = 0;
            $totalCarteirasLotes = 0;
            $postosUnicos = array();
            foreach ($lotes as $l) {
                $totalCarteirasLotes += (int)$l['quantidade'];
                if (!isset($postosUnicos[$l['posto']])) {
                    $postosUnicos[$l['posto']] = true;
                    $totalPostosLotes++;
                }
            }
            ?>
            <div class="totais" style="background:#d4edda; border:2px solid #28a745; padding:10px; margin:10px 0;">
                <strong style="color:#155724;">RESUMO DO DESPACHO:</strong>
                Total de postos: <strong><?php echo $totalPostosLotes; ?></strong> |
                Total de carteiras: <strong><?php echo number_format($totalCarteirasLotes, 0, ',', '.'); ?></strong> |
                Total de lotes: <strong><?php echo count($lotes); ?></strong>
            </div>

            <!-- Itens por posto (ciDespachoItens - usado principalmente para Poupa Tempo) -->
            <?php if (!empty($itens)): ?>
            <div class="subtitulo">Postos (ciDespachoItens)</div>
            <?php
            $totalCart = 0;
            foreach ($itens as $i) {
                $totalCart += (int)$i['quantidade'];
            }
            ?>
            <div class="totais">
                Total de postos: <?php echo count($itens); ?> |
                Total de carteiras: <?php echo (int)$totalCart; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Posto</th>
                        <th>Nome do Posto</th>
                        <th>Endereço</th>
                        <th>Quantidade</th>
                        <?php /* v9.23.0: nomenclatura PT (esta pagina e exclusiva Poupa Tempo) */ ?>
                        <th>Lacre Poupa Tempo</th>
                        <th>Lacre Correios Poupa Tempo</th>
                        <th>Etiqueta Correios</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $i): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['posto'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($i['nome_posto'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($i['endereco'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$i['quantidade']; ?></td>
                            <td><?php echo htmlspecialchars($i['lacre_iipr'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($i['lacre_correios'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($i['etiqueta_correios'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <!-- Versao 6: Mensagem informativa para despachos sem ciDespachoItens -->
            <div class="totais" style="background:#fff3cd; border:1px solid #ffc107; padding:10px; margin:10px 0;">
                <strong style="color:#856404;">Nota:</strong> Este despacho não possui dados na tabela ciDespachoItens.
                <?php if ($despacho_tipo === 'CORREIOS'): ?>
                Os dados de postos Correios são armazenados diretamente na tabela de lotes abaixo.
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Detalhe por lote (Versao 6: inclui etiqueta e status conferencia) -->
            <div class="subtitulo">Lotes (ciDespachoLotes)</div>
            <table>
                <thead>
                    <tr>
                        <th>Posto</th>
                        <th>Lote</th>
                        <th>Quantidade</th>
                        <th>Data de Carga</th>
                        <th>Responsáveis</th>
                        <?php /* v9.23.0: mostrar Lacres do Poupa Tempo nos lotes (como no oficio) */ ?>
                        <th>Lacre Poupa Tempo</th>
                        <th>Lacre Correios Poupa Tempo</th>
                        <th>Etiqueta Correios</th>
                        <th>Conferido</th>
                        <th>Conferido Por</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($lotes)): ?>
                    <tr><td colspan="10">Nenhum lote detalhado para este ofício.</td></tr>
                <?php else: ?>
                    <?php foreach ($lotes as $l): ?>
                        <tr style="<?php echo ($l['conferido'] === 'S') ? 'background-color:#d4edda;' : ''; ?>">
                            <td><?php echo htmlspecialchars($l['posto'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($l['lote'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$l['quantidade']; ?></td>
                            <td>
                                <?php
                                if (!empty($l['data_carga']) && $l['data_carga'] !== '0000-00-00') {
                                    $dt = DateTime::createFromFormat('Y-m-d', $l['data_carga']);
                                    echo $dt ? $dt->format('d/m/Y') : htmlspecialchars($l['data_carga'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($l['responsaveis'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php /* v9.23.0: Lacre Poupa Tempo + Lacre Correios Poupa Tempo do lote */ ?>
                            <td><?php echo htmlspecialchars(isset($l['etiquetaiipr']) ? (string)$l['etiquetaiipr'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(isset($l['etiquetacorreios']) ? (string)$l['etiquetacorreios'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="font-size:10px; max-width:150px; word-break:break-all;"><?php echo htmlspecialchars($l['etiqueta_correios'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align:center;">
                                <?php if ($l['conferido'] === 'S'): ?>
                                    <span class="badge badge-ativo">Sim</span>
                                <?php else: ?>
                                    <span class="badge badge-inativo">Não</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($l['conferido_por'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
<?php include __DIR__ . '/util_botoes_fixos.php'; ?>
<?php include __DIR__ . '/_acess.php'; ?>
</body>
</html>
<?php
// 6) FECHA A CONEXÃO EXPLICITAMENTE (opcional, mas como você pediu 😉)
$pdo_controle = null;
?>
