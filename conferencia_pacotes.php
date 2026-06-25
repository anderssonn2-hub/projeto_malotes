<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
/* conferencia_pacotes.php — v1.2.2
 * CHANGELOG v2.0.4 (badge da tela):
 * - [CORRIGIDO] Rolagem "trava-e-pula" ao conferir: havia DOIS scrolls suaves
 *   concorrentes por leitura (linha.scrollIntoView + centralizarElemento no chip),
 *   um cancelando o outro no meio do caminho. Agora e UMA unica rolagem por leitura,
 *   agendada por requestAnimationFrame (deixa o layout assentar e supera agendamentos
 *   anteriores), centralizando no chip do lote se visivel, senao na linha. Mantem a
 *   pulsacao da linha/chip e a regra de deixar o ultimo lote sempre no meio da tela.
 *
 * CHANGELOG v1.0.0:
 * - [AJUSTE] Versao consolidada para v1.0.0
 * - [AJUSTE] Tela e snapshot exibem a versao v1.0.0
 *
 * CHANGELOG v9.25.23:
 * - [AJUSTE] Versao atualizada para 0.9.25.23
 *
 * CHANGELOG v9.25.18:
 * - [CORRIGIDO] Aviso de pacote de outra regional volta a bloquear leituras de outro posto dentro da Capital e da Central
 *
 * CHANGELOG v9.25.17:
 * - [CORRIGIDO] Capital e Central passam a agrupar pelo atributo de regional real da linha, liberando concluido por posto finalizado
 *
 * CHANGELOG v9.25.16:
 * - [CORRIGIDO] Audio de concluido em Capital e Central dispara por posto finalizado, sem depender dos demais postos do grupo
 * - [AJUSTE] Tela e snapshot exibem a versao 0.9.25.23
 *
 * CHANGELOG v9.25.15:
 * - [CORRIGIDO] Leitura usa sempre os últimos 19 dígitos válidos para ignorar sobra residual no input
 * - [CORRIGIDO] Segmentação de lote, regional, posto e quantidade centralizada para evitar pendentes com dados deslocados
 * - [CORRIGIDO] Campo do scanner limpa sobras parciais automaticamente e não reprocessa por eventos redundantes
 *
 * CHANGELOG v9.25.14:
 * - [CORRIGIDO] Scanner não processa a mesma leitura por múltiplos canais quando o campo principal está em foco
 * - [CORRIGIDO] Callback assíncrono de pacote não encontrado é descartado após confirmação recente do mesmo contexto
 *
 * CHANGELOG v9.25.13:
 * - [CORRIGIDO] Áudio e aviso de pacote não encontrado só disparam se o lote realmente não estiver na tela
 * - [CORRIGIDO] Revalidação da linha após checagem assíncrona evita falso pendente com linha já marcada em verde
 *
 * CHANGELOG v9.25.12:
 * - [NOVO] Controle remoto por celular com comandos de malote sincronizados via servidor
 * - [NOVO] Canal remoto para operar lacres e etiqueta sem depender de voz no navegador
 *
 * CHANGELOG v9.25.11:
 * - [NOVO] Comandos de voz para armar e preencher lacres/etiqueta no painel de malotes
 * - [NOVO] Prévia dinâmica do ofício em segunda tela via sincronização local do navegador
 *
 * CHANGELOG v9.25.10:
 * - [NOVO] Grupos de malote IIPR e malote Correios para separar linhas repetidas do mesmo posto
 * - [NOVO] Persistência dos grupos no modo chips para futura renderização fiel do ofício
 *
 * CHANGELOG v9.25.9:
 * - [NOVO] Atribuição de lotes a lacres IIPR e malotes Correios no modo chips
 * - [NOVO] Persistência por lote em conferencia_pacotes_lacres para reaproveitar no ofício
 * - [AJUSTE] Reset da conferência também limpa vínculos do período filtrado
 *
 * CHANGELOG v9.25.8:
 * - [AJUSTE] Filtro inicial usa a data do dia por padrao
 * - [AJUSTE] Versao atualizada para 0.9.25.8
 *
 * CHANGELOG v9.25.7:
 * - [CORRIGIDO] Aviso de pacote não encontrado emitido apenas uma vez por leitura
 * - [NOVO] Classificação por chips e modo tradicional iniciam recolhidos e alternam entre si
 * - [AJUSTE] Painel de operação com títulos destacados, contadores de Pacotes, Conferidos e Pendentes
 * - [AJUSTE] Datas exibidas no padrão brasileiro e reset visual sincronizado entre tabela e chips
 *
 * CHANGELOG v9.25.6:
 * - [NOVO] Card Operação com conferência por chips e detalhamento por lote
 * - [NOVO] Rolagem automática para o chip correspondente ao código lido
 * - [NOVO] Indicador por posto com pacotes, conferidos e sem upload
 *
 * CHANGELOG v9.25.5:
 * - [NOVO] Opção "Todos" no início da conferência
 * - [CORRIGIDO] Aviso explícito para código dos Correios durante modo Poupa Tempo
 * - [CORRIGIDO] Áudio mp3 para "pacote não encontrado"
 *
 * CHANGELOG v9.25.4:
 * - [NOVO] Aviso visual e fala para "pacote não encontrado"
 * - [NOVO] Salvamento em fila com autor, turno e data de criação
 * - [NOVO] Consolidação opcional de lançamentos em ciPostos no momento do salvamento
 * - [NOVO] Inserção dos novos lotes em ciPostosCsv ao finalizar a fila
 * - [AJUSTE] Pacotes não encontrados não são mais salvos imediatamente ao adicionar
 *
 * CHANGELOG v9.24.8:
 * - [NOVO] Total de pacotes na estante por leitura (encontra_posto)
 * - [NOVO] Lotes na estante sem upload no filtro atual
 * - [AJUSTE] Versao atualizada
 *
 * CHANGELOG v9.24.6:
 * - [NOVO] Coluna com responsavel pela producao do lote
 * - [NOVO] Coluna com data/hora da conferencia
 * - [NOVO] Capital e Central separados por posto
 *
 * CHANGELOG v0.9.25.1:
 * - [CORRIGIDO] Confirmacao verde somente quando conferido no banco
 * - [CORRIGIDO] Persistencia das conferencias em conferencia_pacotes
 * - [CORRIGIDO] Desbloqueio de audio para beep e voz
 * - [NOVO] Historico de bloqueios com responsavel
 * - [AJUSTE] Bloqueio/desbloqueio exige responsavel
 *
 * CHANGELOG v9.24.5:
 * - [AJUSTE] Responsavel aparece apenas uma vez por sessao
 * - [AJUSTE] Contagem por tabela atualiza ao conferir
 * - [NOVO] Ordenacao por Lote e Data de Expedicao
 * - [NOVO] Link "Voltar ao Inicio" na barra superior
 *
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
$conferencias_info = array();
$conferencias_lote = array();
$dias_com_conferencia = array();
$dias_sem_conferencia = array();
$metadados_dias = array();
$controle_canal = isset($_GET['canal_controle']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['canal_controle']) : 'principal';
if ($controle_canal === '') {
    $controle_canal = 'principal';
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
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

// Conexão
$host = (getenv('DB_HOST') ?: '10.15.61.169');
$dbname = (getenv('DB_NAME') ?: 'controle');
$user = (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'));
$pass = (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256'));

function criarPdoLegadoControle($host, $dbname, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass);
    } catch (Exception $e) {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
        $pdo = new PDO($dsn, $user, $pass);
        try {
            $pdo->exec("SET NAMES utf8");
        } catch (Exception $e2) {
        }
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

try {
    $pdo = criarPdoLegadoControle($host, $dbname, $user, $pass);

    // v9.24.0: Postos bloqueados
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosBloqueados (
        id INT NOT NULL AUTO_INCREMENT,
        posto VARCHAR(10) NOT NULL,
        nome VARCHAR(120) DEFAULT NULL,
        motivo VARCHAR(255) DEFAULT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado DATETIME NOT NULL,
        atualizado DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY posto (posto)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $colsMotivo = $pdo->query("SHOW COLUMNS FROM ciPostosBloqueados LIKE 'motivo'")->fetchAll();
    if (count($colsMotivo) === 0) {
        $pdo->exec("ALTER TABLE ciPostosBloqueados ADD COLUMN motivo VARCHAR(255) DEFAULT NULL AFTER nome");
    }

    // v9.24.6: Historico de bloqueios
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosBloqueadosHistorico (
        id INT NOT NULL AUTO_INCREMENT,
        posto VARCHAR(10) NOT NULL,
        acao VARCHAR(20) NOT NULL,
        motivo VARCHAR(255) DEFAULT NULL,
        responsavel VARCHAR(120) NOT NULL,
        criado DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_posto (posto),
        KEY idx_criado (criado)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    // v9.24.6: Data/hora de conferencia
    $colsConf = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes LIKE 'conferido_em'")->fetchAll();
    if (count($colsConf) === 0) {
        $pdo->exec("ALTER TABLE conferencia_pacotes ADD COLUMN conferido_em DATETIME DEFAULT NULL");
    }

    // v9.24.8: Controle de pacotes lidos na estante
    $pdo->exec("CREATE TABLE IF NOT EXISTS lotes_na_estante (
        id INT NOT NULL AUTO_INCREMENT,
        lote INT(8) NOT NULL,
        regional INT(3) NOT NULL,
        posto INT(3) NOT NULL,
        quantidade INT(5) NOT NULL,
        producao_de DATE NOT NULL,
        triado_em DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    // v9.25.9: Vinculo fino entre lote conferido e malotes/lacres usados no modo chips
    $pdo->exec("CREATE TABLE IF NOT EXISTS conferencia_pacotes_lacres (
        id INT NOT NULL AUTO_INCREMENT,
        codbar VARCHAR(25) NOT NULL,
        lote VARCHAR(8) NOT NULL,
        regional VARCHAR(3) NOT NULL,
        posto VARCHAR(10) NOT NULL,
        dataexp DATE NOT NULL,
        qtd INT(5) NOT NULL DEFAULT 0,
        lacre_iipr INT(11) DEFAULT NULL,
        grupo_iipr VARCHAR(40) DEFAULT NULL,
        lacre_correios INT(11) DEFAULT NULL,
        grupo_correios VARCHAR(40) DEFAULT NULL,
        etiqueta_correios VARCHAR(35) DEFAULT NULL,
        usuario_lacre VARCHAR(120) DEFAULT NULL,
        atualizado_em DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_codbar (codbar),
        KEY idx_periodo (dataexp),
        KEY idx_posto_lote (posto, lote)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $colsGrupoIipr = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes_lacres LIKE 'grupo_iipr'")->fetchAll();
    if (count($colsGrupoIipr) === 0) {
        $pdo->exec("ALTER TABLE conferencia_pacotes_lacres ADD COLUMN grupo_iipr VARCHAR(40) DEFAULT NULL AFTER lacre_iipr");
    }
    $colsGrupoCorreios = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes_lacres LIKE 'grupo_correios'")->fetchAll();
    if (count($colsGrupoCorreios) === 0) {
        $pdo->exec("ALTER TABLE conferencia_pacotes_lacres ADD COLUMN grupo_correios VARCHAR(40) DEFAULT NULL AFTER lacre_correios");
    }

    // v9.25.12: Comandos remotos para o painel de malotes
    $pdo->exec("CREATE TABLE IF NOT EXISTS conferencia_pacotes_controle (
        id INT NOT NULL AUTO_INCREMENT,
        canal VARCHAR(40) NOT NULL,
        comando VARCHAR(40) NOT NULL,
        valor VARCHAR(120) DEFAULT NULL,
        valor_aux VARCHAR(120) DEFAULT NULL,
        usuario VARCHAR(120) DEFAULT NULL,
        criado_em DATETIME NOT NULL,
        processado_em DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_canal_processado (canal, processado_em, id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $pdo->exec("CREATE TABLE IF NOT EXISTS conferencia_pacotes_controle_estado (
        canal VARCHAR(40) NOT NULL,
        usuario VARCHAR(120) DEFAULT NULL,
        posto VARCHAR(120) DEFAULT NULL,
        regional VARCHAR(120) DEFAULT NULL,
        resumo VARCHAR(255) DEFAULT NULL,
        lacre_iipr VARCHAR(20) DEFAULT NULL,
        lacre_correios VARCHAR(20) DEFAULT NULL,
        etiqueta_correios VARCHAR(35) DEFAULT NULL,
        atualizado_em DATETIME NOT NULL,
        PRIMARY KEY (canal)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    try {
        $colunaPostoControle = $pdo->query("SHOW COLUMNS FROM conferencia_pacotes_controle_estado LIKE 'posto'")->fetch(PDO::FETCH_ASSOC);
        if ($colunaPostoControle && isset($colunaPostoControle['Type']) && stripos((string)$colunaPostoControle['Type'], 'varchar(120)') === false) {
            $pdo->exec("ALTER TABLE conferencia_pacotes_controle_estado MODIFY posto VARCHAR(120) DEFAULT NULL");
        }
    } catch (Exception $e) {
    }

    if (isset($_POST['enviar_comando_remoto_ajax'])) {
        $canal = isset($_POST['canal']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_POST['canal']) : 'principal';
        $comando = isset($_POST['comando']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_POST['comando'])) : '';
        $valor = isset($_POST['valor']) ? substr(trim((string)$_POST['valor']), 0, 120) : '';
        $valorAux = isset($_POST['valor_aux']) ? substr(trim((string)$_POST['valor_aux']), 0, 120) : '';
        $usuario = isset($_POST['usuario']) ? substr(trim((string)$_POST['usuario']), 0, 120) : '';
        if ($canal === '') {
            $canal = 'principal';
        }
        if ($comando === '') {
            die(json_encode(array('success' => false, 'erro' => 'Comando obrigatorio')));
        }
        $stmt = $pdo->prepare("INSERT INTO conferencia_pacotes_controle (canal, comando, valor, valor_aux, usuario, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(array($canal, $comando, ($valor === '' ? null : $valor), ($valorAux === '' ? null : $valorAux), ($usuario === '' ? null : $usuario)));
        $id = (int)$pdo->lastInsertId();
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true, 'id' => $id)));
    }

    if (isset($_GET['buscar_comandos_remoto_ajax'])) {
        $canal = isset($_GET['canal']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['canal']) : 'principal';
        if ($canal === '') {
            $canal = 'principal';
        }
        $stmt = $pdo->prepare("SELECT id, comando, valor, valor_aux, usuario, criado_em FROM conferencia_pacotes_controle WHERE canal = ? AND processado_em IS NULL ORDER BY id ASC LIMIT 30");
        $stmt->execute(array($canal));
        $comandos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = array();
        foreach ($comandos as $cmd) {
            $ids[] = (int)$cmd['id'];
        }
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmtUpd = $pdo->prepare("UPDATE conferencia_pacotes_controle SET processado_em = NOW() WHERE id IN ($ph)");
            $stmtUpd->execute($ids);
            $stmtUpd = null;
        }
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true, 'comandos' => $comandos)));
    }

    if (isset($_POST['atualizar_estado_remoto_ajax'])) {
        $canal = isset($_POST['canal']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_POST['canal']) : 'principal';
        if ($canal === '') {
            $canal = 'principal';
        }
        $usuario = isset($_POST['usuario']) ? substr(trim((string)$_POST['usuario']), 0, 120) : '';
        $posto = isset($_POST['posto']) ? substr(trim((string)$_POST['posto']), 0, 120) : '';
        $regional = isset($_POST['regional']) ? substr(trim((string)$_POST['regional']), 0, 120) : '';
        $resumo = isset($_POST['resumo']) ? substr(trim((string)$_POST['resumo']), 0, 255) : '';
        $lacreIipr = isset($_POST['lacre_iipr']) ? substr(trim((string)$_POST['lacre_iipr']), 0, 20) : '';
        $lacreCorreios = isset($_POST['lacre_correios']) ? substr(trim((string)$_POST['lacre_correios']), 0, 20) : '';
        $etiquetaCorreios = isset($_POST['etiqueta_correios']) ? substr(trim((string)$_POST['etiqueta_correios']), 0, 35) : '';
        $stmt = $pdo->prepare("INSERT INTO conferencia_pacotes_controle_estado (canal, usuario, posto, regional, resumo, lacre_iipr, lacre_correios, etiqueta_correios, atualizado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                usuario = VALUES(usuario),
                posto = VALUES(posto),
                regional = VALUES(regional),
                resumo = VALUES(resumo),
                lacre_iipr = VALUES(lacre_iipr),
                lacre_correios = VALUES(lacre_correios),
                etiqueta_correios = VALUES(etiqueta_correios),
                atualizado_em = NOW()");
        $stmt->execute(array(
            $canal,
            ($usuario === '' ? null : $usuario),
            ($posto === '' ? null : $posto),
            ($regional === '' ? null : $regional),
            ($resumo === '' ? null : $resumo),
            ($lacreIipr === '' ? null : $lacreIipr),
            ($lacreCorreios === '' ? null : $lacreCorreios),
            ($etiquetaCorreios === '' ? null : $etiquetaCorreios)
        ));
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true)));
    }

    if (isset($_GET['ler_estado_remoto_ajax'])) {
        $canal = isset($_GET['canal']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['canal']) : 'principal';
        if ($canal === '') {
            $canal = 'principal';
        }
        $stmt = $pdo->prepare("SELECT canal, usuario, posto, regional, resumo, lacre_iipr, lacre_correios, etiqueta_correios, atualizado_em FROM conferencia_pacotes_controle_estado WHERE canal = ? LIMIT 1");
        $stmt->execute(array($canal));
        $estado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true, 'estado' => $estado ? $estado : null)));
    }

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
        
        $sql = "INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf, usuario, conferido_em) 
            VALUES (?, ?, ?, ?, ?, ?, 's', ?, NOW())
            ON DUPLICATE KEY UPDATE conf='s', qtd=VALUES(qtd), codbar=VALUES(codbar), dataexp=VALUES(dataexp), usuario=VALUES(usuario), conferido_em=NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($regional, $lote, $posto, $dataexp, $qtd, $codbar, $usuario_conf));
        $stmt = null; // v8.17.4: Libera statement
        $pdo = null;  // v8.17.4: Fecha conexão
        die(json_encode(array('success' => true)));
    }

    if (isset($_POST['salvar_atribuicao_lacres_ajax'])) {
        $payload = isset($_POST['pacotes']) ? $_POST['pacotes'] : '';
        $usuario_lacre = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        if ($usuario_lacre === '') {
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }

        $pacotes = json_decode($payload, true);
        if (!is_array($pacotes) || empty($pacotes)) {
            die(json_encode(array('success' => false, 'erro' => 'Nenhum lote informado')));
        }

        $sqlUpsert = "INSERT INTO conferencia_pacotes_lacres
            (codbar, lote, regional, posto, dataexp, qtd, lacre_iipr, grupo_iipr, lacre_correios, grupo_correios, etiqueta_correios, usuario_lacre, atualizado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                lote = VALUES(lote),
                regional = VALUES(regional),
                posto = VALUES(posto),
                dataexp = VALUES(dataexp),
                qtd = VALUES(qtd),
                lacre_iipr = VALUES(lacre_iipr),
                grupo_iipr = VALUES(grupo_iipr),
                lacre_correios = VALUES(lacre_correios),
                grupo_correios = VALUES(grupo_correios),
                etiqueta_correios = VALUES(etiqueta_correios),
                usuario_lacre = VALUES(usuario_lacre),
                atualizado_em = NOW()";
        $stmtUpsert = $pdo->prepare($sqlUpsert);
        $stmtDelete = $pdo->prepare("DELETE FROM conferencia_pacotes_lacres WHERE codbar = ?");

        $salvos = 0;
        foreach ($pacotes as $pacote) {
            $codbar = isset($pacote['codbar']) ? preg_replace('/\D+/', '', (string)$pacote['codbar']) : '';
            $lote = isset($pacote['lote']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['lote']), 8, '0', STR_PAD_LEFT) : '';
            $regional = isset($pacote['regional']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['regional']), 3, '0', STR_PAD_LEFT) : '';
            $posto = isset($pacote['posto']) ? str_pad(preg_replace('/\D+/', '', (string)$pacote['posto']), 3, '0', STR_PAD_LEFT) : '';
            $dataexp = isset($pacote['dataexp']) ? normalizarDataSqlPacote((string)$pacote['dataexp']) : '';
            $qtd = isset($pacote['qtd']) ? (int)$pacote['qtd'] : 0;
            $lacreI = isset($pacote['lacre_iipr']) ? preg_replace('/\D+/', '', (string)$pacote['lacre_iipr']) : '';
            $grupoI = isset($pacote['grupo_iipr']) ? trim((string)$pacote['grupo_iipr']) : '';
            $lacreC = isset($pacote['lacre_correios']) ? preg_replace('/\D+/', '', (string)$pacote['lacre_correios']) : '';
            $grupoC = isset($pacote['grupo_correios']) ? trim((string)$pacote['grupo_correios']) : '';
            $etiqueta = isset($pacote['etiqueta_correios']) ? trim((string)$pacote['etiqueta_correios']) : '';

            if ($codbar === '' && $lote !== '' && $regional !== '' && $posto !== '' && $qtd > 0) {
                $codbar = $lote . $regional . $posto . str_pad((string)$qtd, 5, '0', STR_PAD_LEFT);
            }
            if ($codbar === '' || $lote === '' || $posto === '' || $dataexp === '') {
                continue;
            }

            $lacreIVal = ($lacreI === '' ? null : (int)$lacreI);
            $lacreCVal = ($lacreC === '' ? null : (int)$lacreC);
            $etiquetaVal = ($etiqueta === '' ? null : $etiqueta);

            if ($lacreIVal === null && $lacreCVal === null && $etiquetaVal === null) {
                $stmtDelete->execute(array($codbar));
                $salvos++;
                continue;
            }

            $stmtUpsert->execute(array(
                $codbar,
                $lote,
                $regional,
                $posto,
                $dataexp,
                $qtd,
                $lacreIVal,
                ($grupoI === '' ? null : $grupoI),
                $lacreCVal,
                ($grupoC === '' ? null : $grupoC),
                $etiquetaVal,
                $usuario_lacre
            ));
            $salvos++;
        }

        $stmtUpsert = null;
        $stmtDelete = null;
        $pdo = null;
        die(json_encode(array('success' => true, 'salvos' => $salvos)));
    }

    // v9.23.2: Inserir pacotes não listados (ciPostosCsv + ciPostos)
    if (isset($_POST['inserir_pacotes_nao_listados'])) {
        $payload = isset($_POST['pacotes']) ? $_POST['pacotes'] : '';
        $usuario_conf = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $autor_salvamento = isset($_POST['autor_salvamento']) ? trim($_POST['autor_salvamento']) : '';
        $criado_salvamento = isset($_POST['criado_salvamento']) ? trim($_POST['criado_salvamento']) : '';
        $turno_salvamento = isset($_POST['turno_salvamento']) ? trim($_POST['turno_salvamento']) : 'Manhã';
        $consolidar_salvamento = !empty($_POST['consolidar_salvamento']);
        if ($usuario_conf === '') {
            die(json_encode(array('success' => false, 'erro' => 'Usuario obrigatorio')));
        }
        if ($autor_salvamento === '') {
            $autor_salvamento = $usuario_conf;
        }
        $pacotes = json_decode($payload, true);
        if (!is_array($pacotes)) {
            die(json_encode(array('success' => false, 'erro' => 'Payload invalido')));
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
        $pdo = null;
        die(json_encode(array(
            'success' => $ok > 0,
            'inseridos' => $ok,
            'inseridos_postos' => $ok_postos,
            'consolidado' => $consolidar_salvamento,
            'erros' => $erros
        )));
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
        $regional_barcode = substr($codbar, 8, 3);
        $posto = substr($codbar, 11, 3);
        $posto_pad_veri = str_pad($posto, 3, '0', STR_PAD_LEFT);

        // Resolver regional real via ciRegionais (barcode pode ter regional-pai diferente)
        $regional = $regional_barcode;
        try {
            $stmtRegVeri = $pdo->prepare("SELECT CAST(regional AS UNSIGNED) AS regional FROM ciRegionais WHERE LPAD(posto,3,'0') = ? LIMIT 1");
            $stmtRegVeri->execute(array($posto_pad_veri));
            $rowRegVeri = $stmtRegVeri->fetch(PDO::FETCH_ASSOC);
            if ($rowRegVeri) {
                $regional = str_pad((string)(int)$rowRegVeri['regional'], 3, '0', STR_PAD_LEFT);
            }
        } catch (Exception $exReg) {
            // fallback: usar regional do barcode
        }

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
        $datasFiltro = array();
        if (isset($_POST['datas']) && trim((string)$_POST['datas']) !== '') {
            $partesDatas = explode(',', (string)$_POST['datas']);
            foreach ($partesDatas as $parteData) {
                $dataNorm = normalizarDataSqlPacote($parteData);
                if ($dataNorm !== '') {
                    $datasFiltro[] = $dataNorm;
                }
            }
        }

        if (!empty($datasFiltro)) {
            $datasFiltro = array_values(array_unique($datasFiltro));
            $phDatas = implode(',', array_fill(0, count($datasFiltro), '?'));

            $stmt = $pdo->prepare("DELETE FROM conferencia_pacotes WHERE DATE(dataexp) IN ($phDatas)");
            $stmt->execute($datasFiltro);

            $stmt = $pdo->prepare("DELETE FROM conferencia_pacotes_lacres WHERE dataexp IN ($phDatas)");
            $stmt->execute($datasFiltro);

            $stmt = null;
            $pdo = null;
            die(json_encode(array('success' => true)));
        }

        $lote = trim($_POST['lote']);
        $regional = trim($_POST['regional']);
        $posto = trim($_POST['posto']);

        $sql = "DELETE FROM conferencia_pacotes WHERE nlote = ? AND regional = ? AND nposto = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($lote, $regional, $posto));

        $codbar = isset($_POST['codbar']) ? preg_replace('/\D+/', '', (string)$_POST['codbar']) : '';
        if ($codbar !== '') {
            $stmt = $pdo->prepare("DELETE FROM conferencia_pacotes_lacres WHERE codbar = ?");
            $stmt->execute(array($codbar));
        }

        $stmt = null; // v8.17.4: Libera statement
        $pdo = null;  // v8.17.4: Fecha conexão
        die(json_encode(array('success' => true)));
    }

    // v9.24.0: Salvar posto bloqueado
    if (isset($_POST['salvar_posto_bloqueado'])) {
        $posto = trim($_POST['posto']);
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        if ($posto === '') {
            die(json_encode(array('success' => false, 'erro' => 'Posto obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }
        if ($motivo === '') {
            die(json_encode(array('success' => false, 'erro' => 'Motivo obrigatorio')));
        }
        $stmt = $pdo->prepare("SELECT id FROM ciPostosBloqueados WHERE posto = ?");
        $stmt->execute(array($posto));
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE ciPostosBloqueados SET nome = ?, motivo = ?, ativo = 1, atualizado = NOW() WHERE posto = ?");
            $stmt->execute(array($motivo, $motivo, $posto));
        } else {
            $stmt = $pdo->prepare("INSERT INTO ciPostosBloqueados (posto, nome, motivo, ativo, criado) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute(array($posto, $motivo, $motivo));
        }
        $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'BLOQUEIO', ?, ?, NOW())");
        $stmtHist->execute(array($posto, $motivo, $responsavel));
        $stmt = null;
        $pdo = null;
        die(json_encode(array('success' => true)));
    }

    // v9.24.0: Excluir posto bloqueado
    if (isset($_POST['excluir_posto_bloqueado'])) {
        $posto = trim($_POST['posto']);
        $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
        if ($posto === '') {
            die(json_encode(array('success' => false, 'erro' => 'Posto obrigatorio')));
        }
        if ($responsavel === '') {
            die(json_encode(array('success' => false, 'erro' => 'Responsavel obrigatorio')));
        }
        $stmt = $pdo->prepare("DELETE FROM ciPostosBloqueados WHERE posto = ?");
        $stmt->execute(array($posto));
        $stmtHist = $pdo->prepare("INSERT INTO ciPostosBloqueadosHistorico (posto, acao, motivo, responsavel, criado) VALUES (?, 'DESBLOQUEIO', ?, ?, NOW())");
        $stmtHist->execute(array($posto, $motivo, $responsavel));
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
    $stmt = $pdo->query("SELECT nlote, regional, nposto, codbar, conferido_em, dataexp FROM conferencia_pacotes WHERE conf='s'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nlote_raw = trim((string)$row['nlote']);
        $regional_raw = trim((string)$row['regional']);
        $posto_raw = trim((string)$row['nposto']);
        $conferido_em = isset($row['conferido_em']) ? trim((string)$row['conferido_em']) : '';
        $dataexp_row = isset($row['dataexp']) ? trim((string)$row['dataexp']) : '';
        if ($conferido_em === '' && $dataexp_row !== '') {
            $conferido_em = $dataexp_row . ' 00:00:00';
        }

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
            if ($conferido_em !== '') {
                $conferencias_info[$k] = $conferido_em;
            }
        }

        // v0.9.25.1: remove conferencias_lote para evitar marcar lote inteiro como conferido
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

    // v9.25.8: Se nenhum filtro, usa a data atual
    if ($data_ini_sql === '' && $data_fim_sql === '' && empty($datas_sql)) {
        $hoje = date('Y-m-d');
        $data_ini_sql = $hoje;
        $data_fim_sql = $hoje;
        if ($data_ini === '') {
            $data_ini = $hoje;
        }
        if ($data_fim === '') {
            $data_fim = $hoje;
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

    $atribuicoes_lacres_por_codigo = array();
    $atribuicoes_lacres_por_chave = array();

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

        $sqlLacres = "SELECT codbar, lote, regional, posto, dataexp, qtd, lacre_iipr, grupo_iipr, lacre_correios, grupo_correios, etiqueta_correios, usuario_lacre, atualizado_em
            FROM conferencia_pacotes_lacres
            WHERE (" . implode(' OR ', array_map(function($condicao) {
                return str_replace('DATE(dataCarga)', 'dataexp', $condicao);
            }, $condicoes_data)) . ")";
        $stmtLacres = $pdo->prepare($sqlLacres);
        $stmtLacres->execute($params_data);
        while ($rowLacre = $stmtLacres->fetch(PDO::FETCH_ASSOC)) {
            $codbarLacre = preg_replace('/\D+/', '', (string)$rowLacre['codbar']);
            $loteLacre = str_pad(preg_replace('/\D+/', '', (string)$rowLacre['lote']), 8, '0', STR_PAD_LEFT);
            $postoLacre = str_pad(preg_replace('/\D+/', '', (string)$rowLacre['posto']), 3, '0', STR_PAD_LEFT);
            $dataLacre = isset($rowLacre['dataexp']) ? trim((string)$rowLacre['dataexp']) : '';
            $atrib = array(
                'lacre_iipr' => isset($rowLacre['lacre_iipr']) && $rowLacre['lacre_iipr'] !== null ? (int)$rowLacre['lacre_iipr'] : 0,
                'grupo_iipr' => isset($rowLacre['grupo_iipr']) ? (string)$rowLacre['grupo_iipr'] : '',
                'lacre_correios' => isset($rowLacre['lacre_correios']) && $rowLacre['lacre_correios'] !== null ? (int)$rowLacre['lacre_correios'] : 0,
                'grupo_correios' => isset($rowLacre['grupo_correios']) ? (string)$rowLacre['grupo_correios'] : '',
                'etiqueta_correios' => isset($rowLacre['etiqueta_correios']) ? (string)$rowLacre['etiqueta_correios'] : '',
                'usuario_lacre' => isset($rowLacre['usuario_lacre']) ? (string)$rowLacre['usuario_lacre'] : '',
                'atualizado_em' => isset($rowLacre['atualizado_em']) ? (string)$rowLacre['atualizado_em'] : ''
            );
            if ($codbarLacre !== '') {
                $atribuicoes_lacres_por_codigo[$codbarLacre] = $atrib;
            }
            $atribuicoes_lacres_por_chave[$postoLacre . '|' . $loteLacre . '|' . $dataLacre] = $atrib;
            $atribuicoes_lacres_por_chave[$postoLacre . '|' . $loteLacre] = $atrib;
        }

        $sql = "SELECT lote, posto, regional, quantidade, dataCarga, usuario 
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
                $usuario_prod = isset($row['usuario']) ? trim((string)$row['usuario']) : '';
                $atribuicao_lacre = null;
                if (isset($atribuicoes_lacres_por_codigo[$codigo_barras])) {
                    $atribuicao_lacre = $atribuicoes_lacres_por_codigo[$codigo_barras];
                } elseif (isset($atribuicoes_lacres_por_chave[$posto . '|' . $lote . '|' . $data_sql_row])) {
                    $atribuicao_lacre = $atribuicoes_lacres_por_chave[$posto . '|' . $lote . '|' . $data_sql_row];
                } elseif (isset($atribuicoes_lacres_por_chave[$posto . '|' . $lote])) {
                    $atribuicao_lacre = $atribuicoes_lacres_por_chave[$posto . '|' . $lote];
                }
                
                // v9.0: Usa informações CORRETAS de ciRegionais
                $regional_real = isset($postosInfo[$posto]) ? $postosInfo[$posto]['regional'] : $regional_csv;
                $tipoEntrega = isset($postosInfo[$posto]) ? $postosInfo[$posto]['entrega'] : null;
                $isPT = ($tipoEntrega == 'poupatempo') ? 1 : 0;
                
                // Verifica se já foi conferido
                $lote_pad = str_pad($lote, 8, '0', STR_PAD_LEFT);
                $posto_pad = str_pad($posto, 3, '0', STR_PAD_LEFT);
                $regional_pad_csv = str_pad($regional_str, 3, '0', STR_PAD_LEFT);

                // v9.3: Poupa Tempo usa próprio posto como regional na exibição
                // v1.1.12: Usa regional REAL de ciRegionais para exibição (não o valor bruto de ciPostosCsv)
                $regional_real_str = str_pad((string)$regional_real, 3, '0', STR_PAD_LEFT);
                $regional_exibida = ($isPT == 1) ? $posto : $regional_real_str;
                $regional_pad_exib = str_pad($regional_exibida, 3, '0', STR_PAD_LEFT);
                $regional_grupo = $regional_real_str;
                $regional_label = $regional_exibida;
                if ($isPT != 1) {
                    if ((int)$regional_real === 0) {
                        $regional_label = 'Postos Capital';
                    } elseif ((int)$regional_real === 999) {
                        $regional_label = 'Postos Central';
                    } elseif ((int)$regional_real === 1) {
                        $regional_label = 'Posto 01';
                    }
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
                $conferido_em = '';
                foreach ($keysToTry as $kTry) {
                    if (isset($conferencias[$kTry])) {
                        $conferido = 1;
                        if (isset($conferencias_info[$kTry])) {
                            $conferido_em = $conferencias_info[$kTry];
                        }
                        break;
                    }
                }
                // v0.9.25.1: nao marcar por lote inteiro, apenas por chave exata

                // v9.0: Agrupa por REGIONAL REAL (de ciRegionais)
                if (!isset($regionais_data[$regional_real])) {
                    $regionais_data[$regional_real] = array();
                }


                $regionais_data[$regional_real][] = array(
                    'lote' => $lote,
                    'posto' => $posto,
                    'regional' => $regional_exibida,
                    'regional_grupo' => $regional_grupo,
                    'regional_label' => $regional_label,
                    'tipoEntrega' => $tipoEntrega,
                    'data' => $data_formatada,
                    'data_sql' => $data_sql_row,
                    'qtd' => ltrim($quantidade, '0'),
                    'codigo' => $codigo_barras,
                    'usuario_prod' => $usuario_prod,
                    'lacre_iipr' => $atribuicao_lacre ? (int)$atribuicao_lacre['lacre_iipr'] : 0,
                    'grupo_iipr' => $atribuicao_lacre ? (string)$atribuicao_lacre['grupo_iipr'] : '',
                    'lacre_correios' => $atribuicao_lacre ? (int)$atribuicao_lacre['lacre_correios'] : 0,
                    'grupo_correios' => $atribuicao_lacre ? (string)$atribuicao_lacre['grupo_correios'] : '',
                    'etiqueta_correios' => $atribuicao_lacre ? (string)$atribuicao_lacre['etiqueta_correios'] : '',
                    'usuario_lacre' => $atribuicao_lacre ? (string)$atribuicao_lacre['usuario_lacre'] : '',
                    'atualizado_lacre_em' => $atribuicao_lacre ? (string)$atribuicao_lacre['atualizado_em'] : '',
                    'conferido_em' => $conferido_em,
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

    $estante_stats = array(
        'total' => 0,
        'capital' => 0,
        'central' => 0,
        'regional' => 0,
        'poupatempo' => 0
    );
    $estante_lotes_sem_upload = array();
    $estante_sem_upload_por_posto = array();

    $periodo_operacao_label = 'Periodo nao informado';
    if ($data_ini !== '' && $data_fim !== '') {
        $periodo_operacao_label = normalizarDataExib($data_ini) . ' a ' . normalizarDataExib($data_fim);
    } elseif (!empty($datas_exib)) {
        if (count($datas_exib) === 1) {
            $periodo_operacao_label = $datas_exib[0];
        } else {
            $periodo_operacao_label = $datas_exib[0] . ' a ' . $datas_exib[count($datas_exib) - 1];
        }
    }

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
            $data_fmt = date('d-m-Y', strtotime($row['data']));
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
                $dias_com_conferencia[] = date('d-m-Y', strtotime($row['data']));
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

    // v9.24.8: Estatisticas da estante (leituras do encontra_posto)
    try {
        if (!empty($condicoes_data)) {
            $condicoes_estante = array();
            $params_estante = array();
            if ($data_ini_sql !== '' && $data_fim_sql !== '') {
                $condicoes_estante[] = "producao_de BETWEEN ? AND ?";
                $params_estante[] = $data_ini_sql;
                $params_estante[] = $data_fim_sql;
            }
            if (!empty($datas_sql)) {
                $phEst = implode(',', array_fill(0, count($datas_sql), '?'));
                $condicoes_estante[] = "producao_de IN ($phEst)";
                $params_estante = array_merge($params_estante, $datas_sql);
            }

            if (!empty($condicoes_estante)) {
                $whereEstante = "WHERE (" . implode(' OR ', $condicoes_estante) . ")";

                $stmtTot = $pdo->prepare("SELECT COUNT(DISTINCT lote) FROM lotes_na_estante $whereEstante");
                $stmtTot->execute($params_estante);
                $estante_stats['total'] = (int)$stmtTot->fetchColumn();

                $stmtTipos = $pdo->prepare("SELECT DISTINCT l.lote, l.posto, l.regional, r.entrega
                    FROM lotes_na_estante l
                    LEFT JOIN ciRegionais r ON LPAD(r.posto,3,'0') = LPAD(l.posto,3,'0')
                    $whereEstante");
                $stmtTipos->execute($params_estante);
                while ($row = $stmtTipos->fetch(PDO::FETCH_ASSOC)) {
                    $entrega = strtolower(trim(str_replace(' ', '', (string)$row['entrega'])));
                    if (strpos($entrega, 'poupa') !== false || strpos($entrega, 'tempo') !== false) {
                        $estante_stats['poupatempo']++;
                    } elseif ((int)$row['regional'] === 0) {
                        $estante_stats['capital']++;
                    } elseif ((int)$row['regional'] === 999) {
                        $estante_stats['central']++;
                    } else {
                        $estante_stats['regional']++;
                    }
                }

                // Sem filtro de data em ciPostosCsv: se o lote foi carregado em qualquer data,
                // nao deve aparecer como "sem upload" (lotes do dia anterior tambem valem).
                $stmtSem = $pdo->prepare("SELECT DISTINCT LPAD(l.lote,8,'0') AS lote, LPAD(l.posto,3,'0') AS posto, LPAD(l.regional,3,'0') AS regional
                    FROM lotes_na_estante l
                    $whereEstante
                    AND NOT EXISTS (
                        SELECT 1 FROM ciPostosCsv c
                        WHERE c.lote = l.lote
                    )
                    ORDER BY l.lote");
                $stmtSem->execute($params_estante);
                while ($row = $stmtSem->fetch(PDO::FETCH_ASSOC)) {
                    $estante_lotes_sem_upload[] = array(
                        'lote' => isset($row['lote']) ? $row['lote'] : '',
                        'posto' => isset($row['posto']) ? $row['posto'] : '',
                        'regional' => isset($row['regional']) ? $row['regional'] : ''
                    );
                    $posto_sem_upload = isset($row['posto']) ? $row['posto'] : '';
                    if ($posto_sem_upload !== '') {
                        if (!isset($estante_sem_upload_por_posto[$posto_sem_upload])) {
                            $estante_sem_upload_por_posto[$posto_sem_upload] = 0;
                        }
                        $estante_sem_upload_por_posto[$posto_sem_upload]++;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $estante_lotes_sem_upload = array();
        $estante_sem_upload_por_posto = array();
    }

    // v9.24.0: Carregar postos bloqueados
    $postos_bloqueados = array();
    try {
        $stmtBloq = $pdo->query("SELECT posto, nome, motivo FROM ciPostosBloqueados WHERE ativo = 1 ORDER BY posto ASC");
        while ($row = $stmtBloq->fetch(PDO::FETCH_ASSOC)) {
            $postos_bloqueados[] = array(
                'posto' => $row['posto'],
                'nome' => $row['nome'],
                'motivo' => $row['motivo']
            );
        }
    } catch (Exception $e) {
        $postos_bloqueados = array();
    }

    // v1.2.2: Carregar restricoes de postos (segurar, adiantar, fechado, etc)
    $postos_restricoes = array();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ciPostosRestricoes (
            id INT NOT NULL AUTO_INCREMENT, posto VARCHAR(10) NOT NULL,
            nome VARCHAR(120) DEFAULT NULL, tipo VARCHAR(60) NOT NULL DEFAULT 'segurar',
            motivo VARCHAR(255) DEFAULT NULL, responsavel VARCHAR(120) DEFAULT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1, criado DATETIME NOT NULL,
            atualizado DATETIME DEFAULT NULL,
            PRIMARY KEY (id), UNIQUE KEY uk_posto (posto)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        $stmtRest = $pdo->query("SELECT r.posto, r.tipo, r.motivo,
            COALESCE(t.cor,'#607d8b') AS cor
            FROM ciPostosRestricoes r
            LEFT JOIN ciRestricoesTipos t ON t.label = r.tipo
            WHERE r.ativo = 1 ORDER BY r.posto ASC");
        while ($row = $stmtRest->fetch(PDO::FETCH_ASSOC)) {
            $postos_restricoes[$row['posto']] = array(
                'tipo'   => $row['tipo'],
                'motivo' => $row['motivo'],
                'cor'    => $row['cor']
            );
        }
    } catch (Exception $e) {
        $postos_restricoes = array();
    }

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
    <title>Conferência de Pacotes v1.0.0</title>
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

        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            background: #1f2b6d;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
        }
        .btn-voltar:hover { background: #162057; }

        .grupo-capital-wrapper,
        .grupo-central-wrapper {
            background: #ffffff;
            border: 2px solid #cfd8dc;
            border-radius: 10px;
            padding: 12px;
            margin: 10px 0 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .grupo-capital-titulo,
        .grupo-central-titulo {
            font-weight: 700;
            color: #37474f;
            margin-bottom: 8px;
        }
        .subgrupo-posto {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            margin: 8px 0;
            background: #fafafa;
        }

        th.sortable { cursor: pointer; user-select: none; }
        th.sortable .sort-indicator { margin-left: 6px; font-size: 11px; opacity: 0.7; }
        
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
        .modo-consulta .linha-conferencia:not(.confirmado) { display: none; }
        .modo-consulta #codigo_barras,
        .modo-consulta #resetar {
            display: none;
        }
        #modoConsultaBadge {
            display: none;
            margin-top: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            background: #ffd54f;
            color: #4a3b00;
            font-size: 11px;
            font-weight: 700;
        }
        .modo-consulta #modoConsultaBadge { display: inline-flex; }
        #btnAtivarConferencia {
            display: none;
            margin-top: 6px;
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            background: #1f2b6d;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }
        .modo-consulta #btnAtivarConferencia { display: inline-flex; }
        
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
        @keyframes pulseChipAtivo {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,230,109,0.42); }
            50% { transform: scale(1.03); box-shadow: 0 0 0 8px rgba(255,230,109,0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,230,109,0); }
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
        .painel-estante {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .painel-estante h4 { margin: 0 0 8px; color: #333; font-size: 13px; }
        .painel-estante .breakdown { font-size: 11px; color: #666; margin-bottom: 8px; }
        .lista-lotes { display: flex; flex-wrap: wrap; gap: 6px; }
        .lote-badge {
            background: #263238;
            color: #fff;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .painel-leitura {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            border: 1px solid #dde6f1;
        }
        .painel-leitura-topo {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .painel-leitura-acoes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .btn-camera-scanner {
            background: #0a7d3a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-camera-scanner:hover { background: #0c8f43; }
        /* Overlay do leitor por camera (a camera vira o "leitor" de codigo de barras) */
        .cam-scan-overlay {
            position: fixed;
            inset: 0;
            z-index: 30000;
            display: none;
            background: #000;
            flex-direction: column;
        }
        .cam-scan-overlay.aberto { display: flex; }
        .cam-scan-video-wrap {
            position: relative;
            flex: 1 1 auto;
            overflow: hidden;
            background: #000;
        }
        #camScanVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        /* A "mira": retangulo central onde o usuario encaixa o codigo de barras */
        .cam-scan-mira {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 84%;
            max-width: 560px;
            height: 150px;
            border: 3px solid rgba(0, 224, 96, 0.95);
            border-radius: 14px;
            box-shadow: 0 0 0 100vmax rgba(0, 0, 0, 0.45);
            pointer-events: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .cam-scan-mira::after {
            content: "";
            position: absolute;
            left: 6%;
            right: 6%;
            top: 50%;
            height: 2px;
            background: rgba(255, 60, 60, 0.9);
            box-shadow: 0 0 8px rgba(255, 60, 60, 0.9);
        }
        .cam-scan-video-wrap.lido .cam-scan-mira {
            border-color: #00e060;
            box-shadow: 0 0 0 100vmax rgba(0, 120, 40, 0.55);
        }
        .cam-scan-dica {
            position: absolute;
            left: 0;
            right: 0;
            top: 16px;
            text-align: center;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
            padding: 0 12px;
            pointer-events: none;
        }
        .cam-scan-status {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 96px;
            text-align: center;
            color: #d8ffe6;
            font-size: 16px;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0,0,0,0.85);
            padding: 0 12px;
            pointer-events: none;
        }
        .cam-scan-barra {
            display: flex;
            gap: 10px;
            padding: 14px;
            background: #0a0a0a;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        .cam-scan-barra button {
            border: none;
            border-radius: 10px;
            padding: 13px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        #camScanFechar { background: #c62828; color: #fff; }
        #camScanTorch { background: #37474f; color: #fff; }
        .cam-scan-erro-box {
            display: none;
            color: #fff;
            background: rgba(0,0,0,0.85);
            margin: 14px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .cam-scan-erro-box.aberto { display: block; }
        .cam-scan-erro-box code {
            background: #222;
            color: #9fe3ff;
            padding: 1px 5px;
            border-radius: 4px;
            word-break: break-all;
        }
        @media print {
            .btn-camera-scanner, .cam-scan-overlay { display: none !important; }
        }
        .modos-visualizacao {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .secao-visualizacao {
            display: block;
            margin-top: 10px;
        }
        .secao-visualizacao.oculta {
            display: none;
        }
        .painel-historico-leitura {
            margin-top: 10px;
            border: 1px solid #dbe5f1;
            border-radius: 10px;
            background: #f8fbff;
            overflow: hidden;
        }
        .painel-historico-topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            background: #eef5fc;
        }
        .painel-historico-titulo {
            font-size: 13px;
            font-weight: 800;
            color: #12395d;
        }
        .painel-historico-subtitulo {
            font-size: 11px;
            color: #597795;
            margin-top: 2px;
        }
        .painel-historico-corpo {
            padding: 10px 12px 12px;
            display: none;
        }
        .painel-historico-leitura.aberto .painel-historico-corpo {
            display: block;
        }
        .historico-leitura-lista {
            list-style: none;
            display: grid;
            gap: 8px;
        }
        .historico-leitura-item {
            border: 1px solid #d8e3ef;
            border-radius: 8px;
            background: #fff;
            padding: 8px 10px;
        }
        .historico-leitura-item strong {
            color: #12395d;
        }
        .historico-leitura-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 11px;
            color: #6a8098;
            margin-top: 4px;
        }
        .historico-leitura-vazio {
            font-size: 12px;
            color: #6a8098;
        }
        .grupo-tradicional {
            margin-top: 18px;
            border: 1px solid #d9e2ec;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(15, 35, 60, 0.06);
            overflow: hidden;
        }
        .grupo-tradicional-titulo {
            margin: 0;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e7edf4;
            background: linear-gradient(180deg, #ffffff 0%, #f5f8fc 100%);
        }
        .grupo-tradicional-info {
            min-width: 0;
        }
        .grupo-tradicional-meta {
            font-size: 11px;
            color: #667c94;
            margin-top: 3px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .grupo-tradicional-conteudo {
            padding: 0 14px 14px;
        }
        .grupo-tradicional.recolhido .grupo-tradicional-conteudo {
            display: none;
        }
        .btn-recolher-tradicional,
        .btn-toggle-historico {
            border: 1px solid #c8d6e5;
            background: #fff;
            color: #12395d;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            min-width: 52px;
        }
        .btn-recolher-tradicional:hover,
        .btn-toggle-historico:hover {
            background: #f1f6fb;
        }
        .btn-recolher-global {
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.08);
            color: #f8fbff;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            min-width: 160px;
        }
        .btn-recolher-global:hover {
            background: rgba(255,255,255,0.14);
        }
        .painel-operacao-acoes,
        .secao-tradicional-acoes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .secao-tradicional-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 10px 0 6px;
        }
        .secao-tradicional-topo h3 {
            margin: 0;
            padding-left: 0;
            border: 0;
            color: #1b1f3b;
        }
        .secao-tradicional-acoes .btn-recolher-tradicional {
            color: #12395d;
            background: #fff;
            border-color: #c8d6e5;
            min-width: 180px;
        }
        .operacao-grupo-conteudo {
            display: block;
        }
        .operacao-grupo.recolhido .operacao-grupo-conteudo {
            display: none;
        }
        .painel-operacao {
            background: linear-gradient(180deg, #0b2d4d 0%, #071a2d 100%);
            border-radius: 14px;
            padding: 14px;
            margin: 14px 0 12px;
            color: #fff;
            box-shadow: 0 10px 24px rgba(0,0,0,0.18);
            overflow: hidden;
        }
        .painel-operacao-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .painel-operacao .operacao-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #86d8ff;
            margin-bottom: 4px;
        }
        .painel-operacao .operacao-titulo {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #f9fbff;
        }
        .painel-operacao .operacao-periodo {
            font-size: 12px;
            color: #c9def4;
            font-weight: 600;
        }
        .operacao-grade {
            display: grid;
            gap: 10px;
        }
        .operacao-grupo {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            padding: 10px;
        }
        .operacao-grupo.tipo-pt { border-left: 5px solid #8de96b; }
        .operacao-grupo.tipo-r01 { border-left: 5px solid #ffd54f; }
        .operacao-grupo.tipo-capital { border-left: 5px solid #53c2ff; }
        .operacao-grupo.tipo-central { border-left: 5px solid #ff8a65; }
        .operacao-grupo.tipo-regional { border-left: 5px solid #b39ddb; }
        .operacao-grupo-titulo,
        .operacao-grade-header,
        .operacao-posto-row {
            display: grid;
            grid-template-columns: 72px minmax(220px, 1.1fr) minmax(240px, 2fr) 72px 72px 112px;
            gap: 10px;
            align-items: center;
        }
        .operacao-grupo-titulo {
            padding: 6px 0 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 6px;
        }
        .operacao-grupo-info {
            grid-column: 1 / span 3;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .operacao-grupo-nome {
            font-size: 24px;
            font-weight: 900;
            color: #f8fbff;
            line-height: 1.1;
        }
        .operacao-grupo-resumo {
            font-size: 12px;
            color: #a7cae7;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.9px;
        }
        .operacao-grade-header {
            font-size: 11px;
            font-weight: 800;
            color: #b7d8f8;
            text-transform: uppercase;
            padding: 0 0 6px;
        }
        .operacao-posto-row {
            background: linear-gradient(90deg, rgba(0,198,255,0.18) 0%, rgba(6,16,28,0.3) 52%, rgba(0,198,255,0.12) 100%);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 8px 10px;
            margin-top: 8px;
            scroll-margin-top: 110px;
        }
        .operacao-posto-row.ativo {
            border-color: #ffe66d;
            box-shadow: 0 0 0 2px rgba(255,230,109,0.18);
            animation: pulse 1.8s ease-in-out infinite;
        }
        .operacao-posicao {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 54px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(180deg, #21ff8b 0%, #11c55d 100%);
            color: #03220f;
            font-size: 20px;
            font-weight: 900;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.18);
        }
        .operacao-posto-meta { min-width: 0; }
        .operacao-posto-nome { font-size: 18px; font-weight: 900; color: #ffffff; }
        .operacao-posto-sub { font-size: 11px; color: #b9cde0; margin-top: 2px; }
        .operacao-posto-aux { font-size: 10px; color: #ffe39a; margin-top: 3px; font-weight: 700; }
        .operacao-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 34px;
        }
        .operacao-chip {
            border: 1px solid rgba(255,255,255,0.12);
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            color: #f4f7fb;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            white-space: nowrap;
        }
        .operacao-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.16);
        }
        .operacao-chip.confirmado {
            background: linear-gradient(180deg, #2aff75 0%, #18b958 100%);
            color: #03220f;
            border-color: rgba(255,255,255,0.22);
        }
        .operacao-chip.ativo {
            outline: 2px solid #ffe66d;
            outline-offset: 1px;
            animation: pulseChipAtivo 1.8s ease-in-out infinite;
        }
        .operacao-chip.sem-upload {
            border-style: dashed;
        }
        .operacao-chip.tem-iipr {
            box-shadow: inset 0 0 0 1px rgba(255,209,102,0.9);
        }
        .operacao-chip.tem-correios {
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.12), 0 0 0 2px rgba(83,194,255,0.28);
        }
        .operacao-chip.tem-iipr::after {
            content: ' I';
            display: inline-block;
            margin-left: 6px;
            padding: 1px 5px;
            border-radius: 999px;
            background: rgba(255, 209, 102, 0.24);
            color: #ffe39a;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.6px;
        }
        .operacao-chip.tem-correios::after {
            content: ' C';
            display: inline-block;
            margin-left: 6px;
            padding: 1px 5px;
            border-radius: 999px;
            background: rgba(83, 194, 255, 0.22);
            color: #d8f4ff;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.6px;
        }
        .operacao-posto-row.selecionado-malote {
            border-color: #7bdff2;
            box-shadow: 0 0 0 2px rgba(123,223,242,0.18);
        }
        .operacao-numero {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: #ffffff;
        }
        .operacao-numero-label {
            display: block;
            font-size: 9px;
            color: #b9cde0;
            margin-top: 1px;
            letter-spacing: 0.7px;
        }
        .operacao-pendentes {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: #ffe39a;
        }
        .operacao-pendentes .operacao-numero-label { color: #f4d68b; }
        .modal-chip {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2300;
        }
        .modal-chip .card {
            background: #fff;
            width: 560px;
            max-width: 94%;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 12px 24px rgba(0,0,0,0.28);
        }
        .modal-chip h3 { margin: 0 0 10px; color: #16324f; }
        .modal-chip table { margin-top: 8px; }
        .modal-chip .acoes { margin-top: 12px; text-align: right; }
        .modal-chip .acoes button {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            background: #0d6efd;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .painel-malotes {
            background: #ffffff;
            border: 1px solid #d8e4ef;
            border-radius: 14px;
            padding: 16px;
            margin: 12px 0 8px;
            box-shadow: 0 8px 20px rgba(8,32,58,0.08);
        }
        .painel-malotes-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .painel-malotes-topo h3 {
            margin: 0;
            color: #16324f;
            font-size: 20px;
        }
        .painel-malotes-topo .sub {
            margin-top: 4px;
            font-size: 12px;
            color: #5b7188;
        }
        .painel-malotes-resumo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .painel-malotes-badge {
            min-width: 98px;
            border-radius: 10px;
            padding: 10px 12px;
            background: linear-gradient(180deg, #eef6ff 0%, #dbeafe 100%);
            color: #173a57;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .painel-malotes-badge strong {
            display: block;
            margin-top: 4px;
            font-size: 22px;
            color: #0b3b66;
        }
        .painel-malotes-grid {
            display: grid;
            grid-template-columns: minmax(320px, 1.2fr) minmax(280px, 0.8fr);
            gap: 16px;
        }
        .painel-malotes-coluna {
            border: 1px solid #e6edf5;
            border-radius: 12px;
            padding: 12px;
            background: #f9fbfd;
        }
        .painel-malotes-coluna h4 {
            margin: 0 0 8px;
            color: #153754;
            font-size: 14px;
        }
        .painel-malotes-vazio {
            color: #60758b;
            font-size: 12px;
            background: #f2f6fa;
            border: 1px dashed #c8d6e5;
            border-radius: 10px;
            padding: 14px;
        }
        .tabela-malotes {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }
        .tabela-malotes th,
        .tabela-malotes td {
            border-bottom: 1px solid #e3ebf3;
            padding: 8px 6px;
            text-align: left;
            vertical-align: top;
        }
        .tabela-malotes th {
            color: #5a7086;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .malote-status {
            display: inline-flex;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .malote-status.pendente { background: #fff4d6; color: #946200; }
        .malote-status.iipr { background: #e8f2ff; color: #0f4d85; }
        .malote-status.correios { background: #dbf8e5; color: #136c3a; }
        .painel-malotes-form {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .painel-malotes-form label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #51677c;
            margin-bottom: 4px;
        }
        .painel-malotes-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 10px;
            border-radius: 8px;
            border: 1px solid #c8d6e5;
            background: #fff;
            font-size: 13px;
        }
        .painel-malotes-acoes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .painel-malotes-acoes button {
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn-malote-iipr { background: #0d6efd; color: #fff; }
        .btn-malote-correios { background: #198754; color: #fff; }
        .btn-malote-limpar { background: #f1f3f5; color: #495057; }
        .painel-malotes-ajuda {
            margin-top: 10px;
            font-size: 11px;
            color: #687b8d;
            line-height: 1.5;
        }
        .painel-malotes-utilitarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }
        .painel-voz,
        .painel-controle-remoto,
        .painel-previsao-malotes {
            border: 1px solid #e6edf5;
            border-radius: 12px;
            padding: 12px;
            background: #f9fbfd;
        }
        .painel-voz-topo,
        .painel-controle-topo,
        .painel-previsao-topo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }
        .painel-voz h4,
        .painel-controle-remoto h4,
        .painel-previsao-malotes h4 {
            margin: 0;
            color: #153754;
            font-size: 14px;
        }
        .painel-voz-sub,
        .painel-controle-sub,
        .painel-previsao-sub {
            margin-top: 4px;
            font-size: 12px;
            color: #5b7188;
        }
        .btn-voz-toggle,
        .btn-controle-remoto,
        .btn-previsao-malotes {
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn-voz-toggle {
            background: #173a57;
            color: #fff;
        }
        .btn-voz-toggle.ativo {
            background: #b42318;
        }
        .btn-previsao-malotes {
            background: #0f766e;
            color: #fff;
        }
        .btn-controle-remoto {
            background: #7c3aed;
            color: #fff;
        }
        .controle-canal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 999px;
            background: #efe7ff;
            color: #5b21b6;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .voz-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 999px;
            background: #eef4fb;
            color: #204264;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .voz-status-pill.escutando {
            background: #fee4e2;
            color: #b42318;
        }
        .voz-status-pill.aguardando {
            background: #e8f2ff;
            color: #0f4d85;
        }
        .voz-status-pill.erro {
            background: #fff4d6;
            color: #946200;
        }
        .painel-voz-dicas,
        .painel-controle-dicas,
        .painel-previsao-dicas {
            margin-top: 10px;
            font-size: 11px;
            color: #687b8d;
            line-height: 1.5;
        }
        .painel-voz-diagnostico {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d6e2ee;
            background: #fff;
            font-size: 11px;
            color: #4e657d;
            line-height: 1.55;
        }
        .painel-voz-diagnostico strong {
            display: block;
            margin-bottom: 6px;
            color: #173a57;
            font-size: 12px;
        }
        .painel-voz-diagnostico ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }
        .painel-voz-diagnostico li {
            margin: 4px 0;
        }
        .painel-voz-diagnostico .ok {
            color: #136c3a;
            font-weight: 700;
        }
        .painel-voz-diagnostico .erro {
            color: #b42318;
            font-weight: 700;
        }
        .painel-voz-diagnostico .aviso {
            color: #946200;
            font-weight: 700;
        }
        .painel-ultimas {
            background: #fff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .painel-ultimas ul { margin: 8px 0 0; padding-left: 18px; }
        .btn-toggle {
            padding: 10px 16px;
            background: #1f5f8b;
            color: #fff;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 700;
            margin-top: 6px;
            transition: background .15s ease, transform .15s ease;
        }
        .btn-toggle:hover { transform: translateY(-1px); }
        .btn-toggle.ativo { background: #0d2f4f; box-shadow: inset 0 0 0 2px rgba(255,255,255,0.16); }
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
        .overlay-usuario .btn-opcao-sec {
            background:#f3f3f3;
            color:#333;
            border:1px solid #bbb;
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
        .overlay-confirmacao {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2200;
        }
        .overlay-confirmacao .card {
            background:#fff;
            padding:18px 20px;
            border-radius:10px;
            width: 360px;
            max-width: 92%;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        }
        .overlay-confirmacao h3 { margin:0 0 8px 0; color:#28a745; }
        .overlay-confirmacao p { margin:0 0 12px 0; font-size:13px; color:#333; }
        .overlay-confirmacao button {
            padding: 8px 14px;
            background:#28a745;
            color:#fff;
            border:none;
            border-radius:4px;
            cursor:pointer;
            font-weight:700;
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
            .operacao-grupo-titulo,
            .operacao-grade-header,
            .operacao-posto-row {
                grid-template-columns: 58px minmax(140px, 1fr);
            }
            .operacao-grupo-info {
                grid-column: 1 / -1;
            }
            .operacao-grade-header .hide-mobile,
            .operacao-posto-row .operacao-chips,
            .operacao-posto-row .operacao-numero,
            .operacao-posto-row .operacao-pendentes,
            .operacao-grupo-titulo .operacao-numero,
            .operacao-grupo-titulo .operacao-pendentes {
                grid-column: 1 / -1;
            }
            .operacao-numero,
            .operacao-pendentes { text-align: left; }
            .operacao-posto-row.subnivel { margin-left: 0; }
            #resetar { margin-left: 0; margin-top: 8px; width: 100%; }
            .painel-malotes-utilitarios { grid-template-columns: 1fr; }
            .painel-malotes-grid { grid-template-columns: 1fr; }
            .painel-malotes-resumo { width: 100%; }
            .painel-malotes-badge { flex: 1 1 96px; }
        }
    </style>
</head>
<body>
<div class="topo-status">
    <div class="versao">v2.0.4</div>
</div>

<h2>📋 Conferência de Pacotes v2.0.4</h2>

<div class="overlay-usuario" id="overlayUsuario">
    <div class="card">
        <h3>👤 Informe o responsável</h3>
        <div style="font-size:12px; color:#666;">Obrigatório para iniciar a conferência.</div>
        <input type="text" id="usuario_conf_modal" placeholder="Digite o responsável" autocomplete="off">
        <button type="button" id="btnConfirmarUsuario">Confirmar</button>
        <button type="button" id="btnSomenteVisualizar" class="btn-opcao-sec">Somente visualizar</button>
        <div style="margin-top:12px;">
            <a href="inicio.php" style="display:inline-block; color:#1b3a57; font-weight:600; font-size:13px; text-decoration:none;">&#8592; Voltar ao início</a>
        </div>
    </div>
</div>

<div class="overlay-tipo" id="overlayTipo" style="display:none;">
    <div class="card">
        <h3>🎯 Tipo de conferência</h3>
        <div style="font-size:12px; color:#666;">Escolha para iniciar.</div>
        <button type="button" class="btn-opcao" data-tipo="todos">Todos</button>
        <button type="button" class="btn-opcao" data-tipo="correios">Correios</button>
        <button type="button" class="btn-opcao pt" data-tipo="poupatempo">Poupa Tempo</button>
    </div>
</div>

<div id="conteudoPagina" class="page-locked">

<!-- Barras no topo -->
<div class="barras-topo">
    <div class="radio-box">
        <a class="btn-voltar" href="inicio.php">← Inicio</a>
    </div>
    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">👤 Responsável da conferência</div>
        <span class="usuario-badge" id="usuarioBadge">Não informado</span>
        <div id="modoConsultaBadge">Modo consulta: somente conferidos</div>
        <button type="button" id="btnAtivarConferencia">Iniciar conferência</button>
    </div>

    <div class="radio-box">
        <div style="color:#fff; font-weight:600; margin-bottom:8px;">🎯 Tipo de conferência</div>
        <label style="gap:8px; margin-right:16px;">
            <input type="radio" name="tipo_inicio" value="todos">
            Todos
        </label>
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
    <div style="width:100%; margin-top:8px;">
        <button type="button" id="btnConfCamera" onclick="abrirConferenciaCamera()" style="padding:9px 18px; background:#0b5e57; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:700;">📷 Conferir por câmera (celular)</button>
        <span style="font-size:11px; color:#666; margin-left:8px;">Abre os lotes desta tela para conferência por câmera/foto no celular (offline).</span>
    </div>
    <form id="formConfCamera" method="post" action="conferencia_camera.php" target="_blank" style="display:none;">
        <input type="hidden" name="lotes_json" id="confCameraLotesJson" value="">
        <input type="hidden" name="periodo" id="confCameraPeriodo" value="<?php echo e($periodo_operacao_label); ?>">
        <input type="hidden" name="usuario" id="confCameraUsuario" value="">
    </form>
    <script>
    function abrirConferenciaCamera() {
        var linhas = document.querySelectorAll('tr.linha-conferencia');
        var vistos = {};
        var lista = [];
        for (var i = 0; i < linhas.length; i++) {
            var tr = linhas[i];
            var codigo = (tr.getAttribute('data-codigo') || '').replace(/\D/g, '');
            var lote = (tr.getAttribute('data-lote') || '').replace(/\D/g, '');
            if (!lote && codigo.length >= 8) { lote = codigo.substr(0, 8); }
            if (!lote) { continue; }
            var chave = lote + '|' + codigo;
            if (vistos[chave]) { continue; }
            vistos[chave] = true;
            lista.push({
                lote: lote,
                codigo: codigo,
                regional: tr.getAttribute('data-regional') || '',
                regional_codigo: tr.getAttribute('data-regional-real') || tr.getAttribute('data-regional') || '',
                posto: tr.getAttribute('data-posto') || '',
                qtd: tr.getAttribute('data-qtd') || '',
                data: tr.getAttribute('data-data') || '',
                data_sql: tr.getAttribute('data-data-sql') || tr.getAttribute('data-data') || '',
                ispt: (tr.getAttribute('data-ispt') === '1') ? 1 : 0,
                pt_group: tr.getAttribute('data-pt-group') || '',
                usuario_prod: tr.getAttribute('data-usuario') || tr.getAttribute('data-usuario-prod') || '',
                conf: (tr.className.indexOf('confirmado') !== -1) ? 1 : 0
            });
        }
        if (!lista.length) {
            alert('Nenhum lote na tela para conferir. Aplique um filtro primeiro.');
            return;
        }
        document.getElementById('confCameraLotesJson').value = JSON.stringify(lista);
        var campoUsr = document.getElementById('confCameraUsuario');
        if (campoUsr) { campoUsr.value = (typeof usuarioAtual !== 'undefined' && usuarioAtual) ? usuarioAtual : ''; }
        document.getElementById('formConfCamera').submit();
    }
    </script>
</div>

<div class="cards-resumo" id="cardsResumoFixos">
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
    <div class="card-resumo">
        <h4>Pacotes na estante</h4>
        <div class="valor"><?php echo (int)$estante_stats['total']; ?></div>
    </div>
    <div class="card-resumo">
        <h4>Lotes sem upload</h4>
        <div class="valor"><?php echo (int)count($estante_lotes_sem_upload); ?></div>
    </div>
</div>

<div class="painel-estante" id="painelEstanteFixo">
    <h4>🔎 Lotes na estante sem upload</h4>
    <div class="breakdown">
        Capital: <?php echo (int)$estante_stats['capital']; ?> | Central: <?php echo (int)$estante_stats['central']; ?> | Regional: <?php echo (int)$estante_stats['regional']; ?> | PT: <?php echo (int)$estante_stats['poupatempo']; ?>
    </div>
    <?php if (!empty($estante_lotes_sem_upload)) { ?>
        <div class="lista-lotes">
            <?php
            $limite = 50;
            $total_lotes = count($estante_lotes_sem_upload);
            $mostrar = array_slice($estante_lotes_sem_upload, 0, $limite);
            foreach ($mostrar as $itemSemUpload) {
                $loteBadge = isset($itemSemUpload['lote']) ? $itemSemUpload['lote'] : '';
                $regionalBadge = isset($itemSemUpload['regional']) ? $itemSemUpload['regional'] : '';
                $postoBadge = isset($itemSemUpload['posto']) ? $itemSemUpload['posto'] : '';
                $tituloBadge = 'Lote ' . $loteBadge;
                $detalheBadge = trim('Regional ' . $regionalBadge . ' | Posto ' . $postoBadge, ' |');
                echo '<span class="lote-badge" title="' . e($tituloBadge . ' - ' . $detalheBadge) . '">' . e($loteBadge) . ' <small>R ' . e($regionalBadge) . ' • P ' . e($postoBadge) . '</small></span>';
            }
            if ($total_lotes > $limite) {
                echo '<span class="lote-badge">+ ' . e($total_lotes - $limite) . ' outros</span>';
            }
            ?>
        </div>
    <?php } else { ?>
        <div style="font-size:12px; color:#666;">Nenhum lote pendente no filtro atual.</div>
    <?php } ?>
</div>


<div class="painel-pacotes-novos" id="painelPacotesNovos" style="display:none;">
    <strong>📥 Pacotes não listados</strong>
    <div style="margin-top:6px; font-size:12px; color:#666;" id="resumoPacotesPendentes">Pacotes aguardando carga em ciPostos e ciPostosCsv.</div>
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
    <div style="margin-top:10px; padding:10px; border:1px solid #d7e2f2; border-radius:8px; background:#f7fbff; display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px; align-items:end;">
        <div>
            <label for="autor_salvamento_pacotes" style="display:block; font-size:12px; color:#555; margin-bottom:4px;">Autor</label>
            <input type="text" id="autor_salvamento_pacotes" placeholder="Responsável pela carga">
        </div>
        <div>
            <label for="turno_salvamento_pacotes" style="display:block; font-size:12px; color:#555; margin-bottom:4px;">Turno</label>
            <select id="turno_salvamento_pacotes" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <option value="Madrugada">Madrugada</option>
                <option value="Manhã" selected>Manhã</option>
                <option value="Tarde">Tarde</option>
                <option value="Noite">Noite</option>
            </select>
        </div>
        <div>
            <label for="criado_salvamento_pacotes" style="display:block; font-size:12px; color:#555; margin-bottom:4px;">Criado em</label>
            <input type="datetime-local" id="criado_salvamento_pacotes">
        </div>
        <div style="display:flex; align-items:center; gap:8px; min-height:40px;">
            <input type="checkbox" id="consolidar_salvamento_pacotes">
            <label for="consolidar_salvamento_pacotes" style="margin:0; font-size:12px; color:#333;">Consolidar lançamentos por responsável</label>
        </div>
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
            <button type="button" class="btn-acao btn-salvar" id="btnAdicionarPacote">Adicionar à fila</button>
            <button type="button" class="btn-acao btn-cancelar" id="btnCancelarPacote">Cancelar</button>
        </div>
    </div>
</div>

<div class="overlay-confirmacao" id="overlayConfirmacao">
    <div class="card">
        <h3>Pacotes salvos</h3>
        <p id="confirmacaoTexto">Dados salvos com sucesso!</p>
        <button type="button" id="btnConfirmacaoOk">OK</button>
    </div>
</div>

<div class="modal-chip" id="modalChipDetalhe">
    <div class="card">
        <h3>Detalhes do lote</h3>
        <table>
            <tbody id="tabelaDetalheChip"></tbody>
        </table>
        <div class="acoes">
            <button type="button" id="btnFecharModalChip">Fechar</button>
        </div>
    </div>
</div>

<div class="painel-leitura">
    <div class="painel-leitura-topo">
        <input type="text" id="codigo_barras" placeholder="Escaneie o código de barras (19 dígitos)" maxlength="19" autofocus>
        <div class="painel-leitura-acoes">
            <button type="button" id="btnCameraScanner" class="btn-camera-scanner">📷 Ler com a câmera</button>
            <button id="resetar">🔄 Resetar Conferência</button>
        </div>
    </div>
    <div class="mensagem-leitura" id="mensagemLeitura"></div>
    <div class="painel-historico-leitura" id="painelHistoricoLeitura">
        <div class="painel-historico-topo">
            <div>
                <div class="painel-historico-titulo">Painel da última leitura</div>
                <div class="painel-historico-subtitulo">Recolhido por padrão. Expanda quando quiser acompanhar o histórico operacional.</div>
            </div>
            <button type="button" class="btn-toggle-historico" id="btnToggleHistoricoLeitura" aria-expanded="false">+</button>
        </div>
        <div class="painel-historico-corpo" id="historicoLeituraCorpo">
            <ul class="historico-leitura-lista" id="listaHistoricoLeitura">
                <li class="historico-leitura-vazio">Nenhuma leitura registrada ainda.</li>
            </ul>
        </div>
    </div>
    <div class="modos-visualizacao">
        <button class="btn-toggle" type="button" id="btnMostrarClassificacao" data-target="classificacao" aria-expanded="false">🏆 Classificação por chips</button>
        <button class="btn-toggle" type="button" id="btnMostrarTradicional" data-target="tradicional" aria-expanded="false">📋 Modo tradicional por regional</button>
    </div>
</div>

<div id="secaoClassificacao" class="secao-visualizacao oculta">
<div id="painel-estatisticas" style="display:block;">
    <div class="painel-operacao" id="painelOperacao">
        <div class="painel-operacao-topo">
            <div>
                <span class="operacao-tag">Operação</span>
                <div class="operacao-titulo">Conferência Produção Período</div>
                <div class="operacao-periodo"><?php echo e($periodo_operacao_label); ?></div>
            </div>
            <div class="painel-operacao-acoes">
                <button type="button" class="btn-recolher-global" id="btnToggleTodosChips" aria-expanded="true">Recolher chips</button>
            </div>
        </div>
        <div class="operacao-grade" id="operacaoGrade">
            <?php
            if (!empty($grupo_pt)) {
                renderizarLinhasOperacao('Poupa Tempo', $grupo_pt, $estante_sem_upload_por_posto);
            }
            if (!empty($grupo_r01)) {
                renderizarLinhasOperacao('Posto 001', $grupo_r01, $estante_sem_upload_por_posto);
            }
            if (!empty($grupo_capital)) {
                renderizarLinhasOperacao('Capital', $grupo_capital, $estante_sem_upload_por_posto);
            }
            if (!empty($grupo_999)) {
                renderizarLinhasOperacao('Central IIPR', $grupo_999, $estante_sem_upload_por_posto);
            }
            if (!empty($grupo_outros)) {
                foreach ($grupo_outros as $regional => $postosGrupo) {
                    $regionalStrCard = str_pad($regional, 3, '0', STR_PAD_LEFT);
                    renderizarLinhasOperacao('Regional ' . $regionalStrCard, $postosGrupo, $estante_sem_upload_por_posto);
                }
            }
            if (empty($regionais_data)) {
                echo '<div style="font-size:12px; color:#d7e6f5;">Nenhum pacote encontrado para o período selecionado.</div>';
            }
            ?>
        </div>
    </div>
</div>

</div>

    <script>
    (function() {
        function formatarAgora() {
            var d = new Date();
            var dd = String(d.getDate()).padStart(2, '0');
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var yy = d.getFullYear();
            var hh = String(d.getHours()).padStart(2, '0');
            var mi = String(d.getMinutes()).padStart(2, '0');
            return dd + '-' + mm + '-' + yy + ' ' + hh + ':' + mi;
        }

        function bindFallback() {
            var input = document.getElementById('codigo_barras');
            if (!input || input.__fallbackBound) return;
            if (typeof window.iniciarConferenciaPacotes === 'function') return;
            if (typeof window.processarLeituraCodigo === 'function') return;
            if (window.__conferenciaPrincipalAtiva) return;
            input.__fallbackBound = true;
            var audioDesbloqueado = false;

            function normalize(val) {
                return String(val || '').replace(/\D+/g, '');
            }

            function desbloquearAudios() {
                if (audioDesbloqueado) return;
                audioDesbloqueado = true;
                var ids = ['beep', 'concluido', 'pacotejaconferido', 'pacotedeoutraregional', 'posto_poupatempo', 'pertence_correios', 'pacote_nao_encontrado'];
                for (var i = 0; i < ids.length; i++) {
                    var a = document.getElementById(ids[i]);
                    if (!a) continue;
                    try {
                        a.volume = 0;
                        var p = a.play();
                        (function(audio){
                            if (p && p.then) {
                                p.then(function() {
                                    audio.pause();
                                    audio.currentTime = 0;
                                    audio.volume = 1;
                                }).catch(function() { audio.volume = 1; });
                            } else {
                                audio.pause();
                                audio.currentTime = 0;
                                audio.volume = 1;
                            }
                        })(a);
                    } catch (e) {}
                }
            }

            function handle() {
                var digits = normalize(input.value);
                if (digits.length < 19) return;
                if (digits.length > 19) digits = digits.substr(0, 19);

                desbloquearAudios();

                if (!window.__conferenciaInit && typeof window.iniciarConferenciaPacotes === 'function') {
                    try { window.iniciarConferenciaPacotes(); } catch (e) {}
                }
                if (window.processarLeituraCodigo) {
                    window.processarLeituraCodigo(digits);
                    return;
                }

                var loteDigitos = digits.substr(0, 8);
                var todasTr = document.querySelectorAll('tbody tr[data-lote]');
                var linha = null;
                for (var _fi = 0; _fi < todasTr.length; _fi++) {
                    var _lot = String(todasTr[_fi].getAttribute('data-lote') || '').replace(/\D+/g, '');
                    if (_lot === loteDigitos && !todasTr[_fi].classList.contains('confirmado')) {
                        linha = todasTr[_fi]; break;
                    }
                }
                if (!linha) {
                    for (var _fi2 = 0; _fi2 < todasTr.length; _fi2++) {
                        var _lot2 = String(todasTr[_fi2].getAttribute('data-lote') || '').replace(/\D+/g, '');
                        if (_lot2 === loteDigitos) { linha = todasTr[_fi2]; break; }
                    }
                }
                var msg = document.getElementById('mensagemLeitura');
                var pacoteJaConferido = document.getElementById('pacotejaconferido');
                var muteBeep = document.getElementById('muteBeep');
                var beep = document.getElementById('beep');
                if (!linha) {
                    if (msg) {
                        msg.innerHTML = '<strong>Pacote não encontrado:</strong> adicionado à lista pendente.';
                    }
                    if (window.adicionarPacotePendente) {
                        var now = new Date();
                        var mm = String(now.getMonth() + 1).padStart(2, '0');
                        var dd = String(now.getDate()).padStart(2, '0');
                        var dataPadrao = now.getFullYear() + '-' + mm + '-' + dd;
                        var obj = {
                            codbar: digits,
                            lote: digits.substr(0, 8),
                            regional: digits.substr(8, 3),
                            posto: digits.substr(11, 3),
                            quantidade: parseInt(digits.substr(14, 5), 10) || 1,
                            dataexp: dataPadrao,
                            responsavel: ''
                        };
                        window.adicionarPacotePendente(obj);
                        var painel = document.getElementById('painelPacotesNovos');
                        if (painel) {
                            painel.style.display = 'block';
                            painel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                    var audioNaoEncontrado = document.getElementById('pacote_nao_encontrado');
                    if (audioNaoEncontrado) {
                        try {
                            audioNaoEncontrado.currentTime = 0;
                            audioNaoEncontrado.play();
                        } catch (e) {}
                    } else if (window.speechSynthesis) {
                        try {
                            var ut = new SpeechSynthesisUtterance('pacote não encontrado');
                            ut.lang = 'pt-BR';
                            window.speechSynthesis.cancel();
                            window.speechSynthesis.speak(ut);
                        } catch (e) {}
                    }
                    input.value = '';
                    return;
                }

                if (linha.classList.contains('confirmado')) {
                    if (pacoteJaConferido) {
                        try { pacoteJaConferido.currentTime = 0; pacoteJaConferido.play(); } catch (e) {}
                    }
                    destacarChipOperacao(linha.getAttribute('data-codigo') || digits);
                    input.value = '';
                    return;
                }

                linha.classList.add('confirmado');
                var tdConf = linha.querySelector('.col-conferido-em');
                if (tdConf) tdConf.textContent = formatarAgora();
                var chipFallback = atualizarChipOperacaoPorCodigo(linha.getAttribute('data-codigo') || digits, true);
                if (chipFallback) {
                    chipFallback.setAttribute('data-conferido-em', formatarAgora());
                }

                var ultimas = document.querySelectorAll('tr.ultimo-lido');
                for (var u = 0; u < ultimas.length; u++) {
                    ultimas[u].classList.remove('ultimo-lido');
                }
                linha.classList.add('ultimo-lido');
                // v2.0.4: UMA unica rolagem suave por leitura (evita o "trava-e-pula"
                // causado por dois scrolls concorrentes). Centraliza no chip do lote se
                // visivel; senao, na propria linha. O agendamento por rAF deixa o layout
                // assentar antes de medir, mantendo o ultimo lote sempre no centro.
                var centralizouChip = destacarChipOperacao(linha.getAttribute('data-codigo') || digits);
                if (!centralizouChip) {
                    centralizarElemento(linha);
                }

                if (msg) msg.textContent = '';

                if (beep && (!muteBeep || !muteBeep.checked)) {
                    try { beep.currentTime = 0; beep.play(); } catch (e) {}
                }

                var usuario = '';
                try { usuario = sessionStorage.getItem('conferencia_responsavel') || ''; } catch (e) {}
                if (!usuario) {
                    var badge = document.getElementById('usuarioBadge');
                    if (badge) usuario = (badge.textContent || '').trim();
                }
                if (usuario) {
                    var formData = new FormData();
                    formData.append('salvar_lote_ajax', '1');
                    formData.append('lote', linha.getAttribute('data-lote') || '');
                    formData.append('regional', linha.getAttribute('data-regional') || '');
                    formData.append('posto', linha.getAttribute('data-posto') || '');
                    formData.append('dataexp', linha.getAttribute('data-data-sql') || linha.getAttribute('data-data') || '');
                    formData.append('qtd', linha.getAttribute('data-qtd') || '');
                    formData.append('codbar', linha.getAttribute('data-codigo') || digits);
                    formData.append('usuario', usuario);
                    fetch(window.location.href, { method: 'POST', body: formData }).catch(function(){});
                }
                input.value = '';
            }

            input.addEventListener('input', handle);
            input.addEventListener('change', handle);
            input.addEventListener('keydown', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    handle();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(bindFallback, 1200);
            });
        } else {
            setTimeout(bindFallback, 1200);
        }
    })();
    </script>

<div id="secaoTradicional" class="secao-visualizacao oculta">

<?php
// v2.8.2: contagem de POSTOS/DISPLAYS no topo do "Modo tradicional por regional".
// Regra do usuario (a ideia e saber de antemao quantos displays serao usados):
//  - Posto 01 = 1;
//  - cada posto da CAPITAL conta 1 (ex.: 031,032,036 = 3);
//  - CENTRAL: independentemente da quantidade de postos = 1 (vao todos no MESMO malote/display);
//  - cada REGIONAL = 1 (ex.: 5 regionais = 5).
// Isso equivale ao numero de displays/malotes dos CORREIOS. Para o POUPA TEMPO, contam display
// apenas os postos 110..880 (os PT capital 05..80 NAO usam display dos Correios).
$cont_postos_r01 = array();
foreach ($grupo_r01 as $pR01) {
    $kR01 = isset($pR01['posto']) ? (string)$pR01['posto'] : '';
    if ($kR01 !== '') { $cont_postos_r01[$kR01] = true; }
}
$cont_postos_capital = array();
foreach ($grupo_capital as $pCap) {
    $kCap = isset($pCap['posto']) ? (string)$pCap['posto'] : '';
    if ($kCap !== '') { $cont_postos_capital[$kCap] = true; }
}
$cont_correios_displays = count($cont_postos_r01)        // Posto 01
                        + count($cont_postos_capital)    // 1 por posto da Capital
                        + (empty($grupo_999) ? 0 : 1)    // Central = 1 no total
                        + count($grupo_outros);          // 1 por Regional
$cont_pt_displays = 0;
foreach ($grupo_pt as $postoKeyPt => $listaPt) {
    $numPt = (int)preg_replace('/\D+/', '', (string)$postoKeyPt);
    if ($numPt >= 110 && $numPt <= 880) { $cont_pt_displays++; }
}
$cont_total_displays = $cont_correios_displays + $cont_pt_displays;
?>
<div class="secao-tradicional-topo">
    <h3>Modo tradicional por regional
        <span class="contagem-displays-topo" title="Posto 01 = 1; cada posto da Capital = 1; Central (qualquer quantidade) = 1; cada Regional = 1. Poupa Tempo conta display apenas nos postos 110 a 880 (os PT 05 a 80 nao usam display)." style="font-size:14px; font-weight:normal; color:#1b3a57; white-space:nowrap;">— Correios: <?php echo (int)$cont_correios_displays; ?> postos/displays · Poupa Tempo: <?php echo (int)$cont_pt_displays; ?> displays · Total: <?php echo (int)$cont_total_displays; ?></span>
    </h3>
    <div class="secao-tradicional-acoes">
        <button type="button" class="btn-recolher-tradicional" id="btnToggleTodosTradicional" aria-expanded="true">Recolher regionais</button>
    </div>
</div>

<!-- Tabelas Agrupadas -->
<div id="tabelas">
<?php
// ========================================
// v9.0: AGRUPAMENTO USANDO DADOS DE ciRegionais
// Classificação baseada em regional e entrega REAIS
// ========================================

// v9.24.0: Banner por grupo
function renderizarBanner($texto, $classe) {
    $tipoView = ($classe === 'banner-pt') ? 'poupatempo' : 'correios';
    echo '<div class="banner-grupo ' . $classe . '" data-view="' . $tipoView . '">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
}

function obterClasseGrupoOperacao($tituloGrupo) {
    if ($tituloGrupo === 'Poupa Tempo') return 'tipo-pt';
    if ($tituloGrupo === 'Posto 001') return 'tipo-r01';
    if ($tituloGrupo === 'Capital') return 'tipo-capital';
    if ($tituloGrupo === 'Central IIPR') return 'tipo-central';
    return 'tipo-regional';
}

function renderizarLinhasOperacao($tituloGrupo, $dados, $estanteSemUploadPorPosto) {
    if (empty($dados)) {
        return;
    }

    $primeiro = reset($dados);
    $eh_array_plano = isset($primeiro['lote']);
    $postos = array();
    if ($eh_array_plano) {
        foreach ($dados as $item) {
            $postos[] = $item;
        }
    } else {
        foreach ($dados as $lista) {
            foreach ($lista as $item) {
                $postos[] = $item;
            }
        }
    }

    $porPosto = array();
    foreach ($postos as $item) {
        $postoKey = isset($item['posto']) ? $item['posto'] : '000';
        if (!isset($porPosto[$postoKey])) {
            $porPosto[$postoKey] = array();
        }
        $porPosto[$postoKey][] = $item;
    }
    ksort($porPosto);

    $totalGrupo = count($postos);
    $conferidosGrupo = 0;
    foreach ($postos as $itemGrupo) {
        if (!empty($itemGrupo['conf'])) {
            $conferidosGrupo++;
        }
    }
    $pendentesGrupo = max(0, $totalGrupo - $conferidosGrupo);
    $classeGrupo = obterClasseGrupoOperacao($tituloGrupo);
    $tipoViewGrupo = (!empty($postos[0]['isPT']) && (int)$postos[0]['isPT'] === 1) ? 'poupatempo' : 'correios';

    echo '<div class="operacao-grupo ' . $classeGrupo . '" data-view="' . $tipoViewGrupo . '">';
    echo '<div class="operacao-grupo-titulo">';
    echo '<div class="operacao-grupo-info">';
    echo '<div class="operacao-grupo-nome">' . htmlspecialchars($tituloGrupo, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div class="operacao-grupo-resumo">Total de pacotes ' . $totalGrupo . '</div>';
    echo '</div>';
    echo '<div class="operacao-numero"><span>' . $totalGrupo . '</span><span class="operacao-numero-label">PACOTES</span></div>';
    echo '<div class="operacao-numero"><span>' . $conferidosGrupo . '</span><span class="operacao-numero-label">CONFERIDOS</span></div>';
    echo '<div class="operacao-pendentes"><span>' . $pendentesGrupo . '</span><span class="operacao-numero-label">PENDENTES</span></div>';
    echo '</div>';
    echo '<div class="operacao-grupo-conteudo">';
    echo '<div class="operacao-grade-header">';
    echo '<div>Pos.</div>';
    echo '<div>Operação</div>';
    echo '<div>Chips</div>';
    echo '<div class="hide-mobile">Pacotes</div>';
    echo '<div class="hide-mobile">Conferidos</div>';
    echo '<div class="hide-mobile">Pendentes</div>';
    echo '</div>';

    foreach ($porPosto as $postoKey => $listaPosto) {
        $totalPacotes = count($listaPosto);
        $conferidos = 0;
        foreach ($listaPosto as $item) {
            if (!empty($item['conf'])) {
                $conferidos++;
            }
        }
        $pendentes = max(0, $totalPacotes - $conferidos);
        $semUploadCount = isset($estanteSemUploadPorPosto[$postoKey]) ? (int)$estanteSemUploadPorPosto[$postoKey] : 0;
        echo '<div class="operacao-posto-row" data-posto="' . htmlspecialchars($postoKey, ENT_QUOTES, 'UTF-8') . '" data-grupo="' . htmlspecialchars($tituloGrupo, ENT_QUOTES, 'UTF-8') . '">';
        echo '<div><span class="operacao-posicao">' . htmlspecialchars($postoKey, ENT_QUOTES, 'UTF-8') . '</span></div>';
        echo '<div class="operacao-posto-meta">';
        echo '<div class="operacao-posto-nome">Posto ' . htmlspecialchars($postoKey, ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="operacao-posto-sub">' . htmlspecialchars($tituloGrupo, ENT_QUOTES, 'UTF-8') . '</div>';
        if ($semUploadCount > 0) {
            echo '<div class="operacao-posto-aux">Sem upload: ' . $semUploadCount . '</div>';
        }
        echo '</div>';
        echo '<div class="operacao-chips">';
        foreach ($listaPosto as $item) {
            $chipClasses = 'operacao-chip';
            if (!empty($item['conf'])) {
                $chipClasses .= ' confirmado';
            }
            if (!empty($item['lacre_iipr'])) {
                $chipClasses .= ' tem-iipr';
            }
            if (!empty($item['lacre_correios']) || !empty($item['etiqueta_correios'])) {
                $chipClasses .= ' tem-correios';
            }
            if ($semUploadCount > 0) {
                $chipClasses .= ' sem-upload';
            }
            echo '<button type="button" class="' . $chipClasses . '"';
            echo ' data-codigo="' . htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-lote="' . htmlspecialchars($item['lote'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-posto="' . htmlspecialchars($item['posto'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-regional="' . htmlspecialchars($item['regional_label'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-regional-codigo="' . htmlspecialchars($item['regional_grupo'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-qtd="' . htmlspecialchars($item['qtd'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-data="' . htmlspecialchars($item['data'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-data-sql="' . htmlspecialchars($item['data_sql'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-usuario="' . htmlspecialchars($item['usuario_prod'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-ispt="' . (!empty($item['isPT']) ? '1' : '0') . '"';
            echo ' data-lacre-iipr="' . htmlspecialchars((string)$item['lacre_iipr'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-grupo-iipr="' . htmlspecialchars((string)$item['grupo_iipr'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-lacre-correios="' . htmlspecialchars((string)$item['lacre_correios'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-grupo-correios="' . htmlspecialchars((string)$item['grupo_correios'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-etiqueta-correios="' . htmlspecialchars((string)$item['etiqueta_correios'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-usuario-lacre="' . htmlspecialchars((string)$item['usuario_lacre'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-atualizado-lacre="' . htmlspecialchars((string)$item['atualizado_lacre_em'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-conferido-em="' . htmlspecialchars($item['conferido_em'], ENT_QUOTES, 'UTF-8') . '"';
            echo ' data-conf="' . (!empty($item['conf']) ? '1' : '0') . '">';
            echo htmlspecialchars($item['lote'], ENT_QUOTES, 'UTF-8');
            echo '</button>';
        }
        echo '</div>';
        echo '<div class="operacao-numero"><span data-role="pacotes">' . $totalPacotes . '</span><span class="operacao-numero-label">PACOTES</span></div>';
        echo '<div class="operacao-numero"><span data-role="conferidos">' . $conferidos . '</span><span class="operacao-numero-label">CONFERIDOS</span></div>';
        echo '<div class="operacao-pendentes"><span data-role="pendentes">' . $pendentes . '</span><span class="operacao-numero-label">PENDENTES</span></div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
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
    $tipoView = $ehPoupaTempo ? 'poupatempo' : 'correios';
    $grupoTradicionalId = preg_replace('/[^a-z0-9]+/i', '-', strtolower($titulo));
    $grupoTradicionalId = trim($grupoTradicionalId, '-');
    if ($grupoTradicionalId === '') {
        $grupoTradicionalId = 'grupo-tradicional';
    }
    $postos_distintos = array();
    foreach ($postos_para_exibir as $postoResumo) {
        $postoResumoKey = isset($postoResumo['posto']) ? (string)$postoResumo['posto'] : '';
        if ($postoResumoKey !== '') {
            $postos_distintos[$postoResumoKey] = true;
        }
    }
    $total_subpostos = count($postos_distintos);
    $total_carteiras = 0;
    foreach ($postos_para_exibir as $postoQtd) {
        $total_carteiras += isset($postoQtd['qtd']) ? (int)$postoQtd['qtd'] : 0;
    }
    
    echo '<div class="grupo-tradicional" data-group-id="' . htmlspecialchars($grupoTradicionalId, ENT_QUOTES, 'UTF-8') . '">';
    echo '<h3 class="grupo-tradicional-titulo" data-view="' . $tipoView . '">';
    echo '<div class="grupo-tradicional-info">';
    echo '<div>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    echo ' <span class="contagem-pacotes" data-total="' . $total_pacotes . '" data-conferidos="' . $total_conferidos . '" style="color:#666; font-weight:normal; font-size:14px;">(' . $total_pacotes . ' pacotes / ' . $total_conferidos . ' conferidos / ' . max(0, $total_pacotes - $total_conferidos) . ' pendentes)</span>';
    if ($ehPoupaTempo) {
        echo ' <span class="tag-pt">POUPA TEMPO</span>';
    }
    echo '</div>';
    echo '<div class="grupo-tradicional-meta">' . $total_subpostos . ' subpostos nesta visão</div>';
    echo '</div>';
    echo '<div class="grupo-tradicional-total" style="text-align:right; white-space:nowrap; font-size:14px; font-weight:700; color:#1b3a57;">Total: ' . $total_carteiras . ' carteiras</div>';
    echo '</h3>';
    echo '<div class="grupo-tradicional-conteudo" data-group-body="' . htmlspecialchars($grupoTradicionalId, ENT_QUOTES, 'UTF-8') . '">';
    echo '<table data-view="' . $tipoView . '">';
    echo '<thead><tr>';
    echo '<th>Regional</th>';
    echo '<th class="sortable" data-sort="lote">Lote <span class="sort-indicator">↕</span></th>';
    echo '<th>Posto</th>';
    echo '<th class="sortable" data-sort="data">Data Expedição <span class="sort-indicator">↕</span></th>';
    echo '<th>Quantidade</th>';
    echo '<th>Responsável Produção</th>';
    echo '<th>Código de Barras</th>';
    echo '<th>Conferido em</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($postos_para_exibir as $posto) {
        $classeConf = ($posto['conf'] == 1) ? ' confirmado' : '';
        echo '<tr class="linha-conferencia' . $classeConf . '" ';
        echo 'data-codigo="' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '" ';
        $regional_grupo_attr = isset($posto['regional_grupo']) ? $posto['regional_grupo'] : $posto['regional'];
        echo 'data-regional="' . htmlspecialchars($posto['regional'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-regional-real="' . htmlspecialchars($regional_grupo_attr, ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-lote="' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-posto="' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-data="' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '" ';
        $data_sql_attr = isset($posto['data_sql']) ? $posto['data_sql'] : '';
        echo 'data-data-sql="' . htmlspecialchars($data_sql_attr, ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-qtd="' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-usuario-prod="' . htmlspecialchars($posto['usuario_prod'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-lacre-iipr="' . htmlspecialchars((string)$posto['lacre_iipr'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-grupo-iipr="' . htmlspecialchars((string)$posto['grupo_iipr'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-lacre-correios="' . htmlspecialchars((string)$posto['lacre_correios'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-grupo-correios="' . htmlspecialchars((string)$posto['grupo_correios'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-etiqueta-correios="' . htmlspecialchars((string)$posto['etiqueta_correios'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-usuario-lacre="' . htmlspecialchars((string)$posto['usuario_lacre'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-atualizado-lacre="' . htmlspecialchars((string)$posto['atualizado_lacre_em'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-conferido-em="' . htmlspecialchars($posto['conferido_em'], ENT_QUOTES, 'UTF-8') . '" ';
        echo 'data-ispt="' . $posto['isPT'] . '" ';
        echo 'data-pt-group="' . htmlspecialchars($ptGroup, ENT_QUOTES, 'UTF-8') . '">';
        $regional_label = isset($posto['regional_label']) ? $posto['regional_label'] : $posto['regional'];
        echo '<td>' . htmlspecialchars($regional_label, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['lote'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['posto'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($posto['data'], ENT_QUOTES, 'UTF-8') . '</td>';
        $conferido_em_fmt = '';
        if (!empty($posto['conferido_em'])) {
            $ts = strtotime($posto['conferido_em']);
            if ($ts) { $conferido_em_fmt = date('d-m-Y H:i:s', $ts); }
        }
        echo '<td>' . htmlspecialchars($posto['qtd'], ENT_QUOTES, 'UTF-8') . '</td>';
        $resp_prod = (string)$posto['usuario_prod'];
        $eh_retirada = (stripos($resp_prod, 'retirada') !== false);
        echo '<td>' . htmlspecialchars($resp_prod, ENT_QUOTES, 'UTF-8');
        if ($eh_retirada && $posto['conf'] != 1) {
            echo ' <button type="button" class="btn-conferir-retirada nao-imprimir" onclick="conferirRetirada(this)" title="Marcar este lote de retirada como conferido (ja expedido, sem leitura)" style="margin-left:6px; padding:2px 8px; font-size:11px; font-weight:600; background:#e8f5e9; border:1px solid #66bb6a; color:#1b5e20; border-radius:4px; cursor:pointer;">&#10003; Conferir</button>';
        }
        echo '</td>';
        echo '<td>' . htmlspecialchars($posto['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td class="col-conferido-em">' . htmlspecialchars($conferido_em_fmt, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    echo '</div>';
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
    $capital_por_posto = array();
    foreach ($grupo_capital as $p) {
        $capital_por_posto[$p['posto']][] = $p;
    }
    ksort($capital_por_posto);
    echo '<div class="grupo-capital-wrapper" data-view="correios">';
    echo '<div class="grupo-capital-titulo">Capital</div>';
    foreach ($capital_por_posto as $postoKey => $lista) {
        echo '<div class="subgrupo-posto">';
        renderizarTabela('Posto ' . $postoKey . ' - Capital', $lista);
        echo '</div>';
    }
    echo '</div>';
}
if (!empty($grupo_999)) {
    if (!$banner_correios_exibido) {
        renderizarBanner('POSTOS DOS CORREIOS', 'banner-correios');
        $banner_correios_exibido = true;
    }
    $central_por_posto = array();
    foreach ($grupo_999 as $p) {
        $central_por_posto[$p['posto']][] = $p;
    }
    ksort($central_por_posto);
    echo '<div class="grupo-central-wrapper" data-view="correios">';
    echo '<div class="grupo-central-titulo">Central IIPR</div>';
    foreach ($central_por_posto as $postoKey => $lista) {
        echo '<div class="subgrupo-posto">';
        renderizarTabela('Posto ' . $postoKey . ' - Central', $lista);
        echo '</div>';
    }
    echo '</div>';
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

</div>

<!-- Áudios -->
<audio id="beep" src="beep_correio.mp3" preload="auto"></audio>
<audio id="concluido" src="concluido.mp3" preload="auto"></audio>
<audio id="pacotejaconferido" src="pacotejaconferido.mp3" preload="auto"></audio>
<audio id="pacotedeoutraregional" src="pacotedeoutraregional.mp3" preload="auto"></audio>
<audio id="posto_poupatempo" src="posto_poupatempo.mp3" preload="auto"></audio>
<audio id="pertence_correios" src="pertence_aos_correios.mp3" preload="auto"></audio>
<audio id="pacote_nao_encontrado" src="pacote_nao_foi_encontrado.mp3" preload="auto"></audio>

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

function iniciarConferenciaPacotes() {
    window.__conferenciaPrincipalAtiva = true;
    if (window.__conferenciaInit) return;
    window.__conferenciaInit = true;
    try {
    var input = document.getElementById("codigo_barras");
    var radioAutoSalvar = document.getElementById("autoSalvar");
    var beep = document.getElementById("beep");
    var concluido = document.getElementById("concluido");
    var pacoteJaConferido = document.getElementById("pacotejaconferido");
    var pacoteOutraRegional = document.getElementById("pacotedeoutraregional");
    var postoPoupaTempo = document.getElementById("posto_poupatempo");
    var pertenceCorreios = document.getElementById("pertence_correios");
    var pacoteNaoEncontradoAudio = document.getElementById("pacote_nao_encontrado");
    var muteBeep = document.getElementById("muteBeep");
    var btnResetar = document.getElementById("resetar");
    var usuarioBadge = document.getElementById("usuarioBadge");
    var overlayUsuario = document.getElementById("overlayUsuario");
    var conteudoPagina = document.getElementById("conteudoPagina");
    var usuarioInputModal = document.getElementById("usuario_conf_modal");
    var btnConfirmarUsuario = document.getElementById("btnConfirmarUsuario");
    var btnSomenteVisualizar = document.getElementById("btnSomenteVisualizar");
    var btnAtivarConferencia = document.getElementById("btnAtivarConferencia");
    var overlayTipo = document.getElementById("overlayTipo");
    var usuarioAtual = '';
    var audioDesbloqueado = false;
    var modoConsulta = false;
    var modalPacote = document.getElementById('modalPacote');
    var overlayConfirmacao = document.getElementById('overlayConfirmacao');
    var confirmacaoTexto = document.getElementById('confirmacaoTexto');
    var btnConfirmacaoOk = document.getElementById('btnConfirmacaoOk');
    var modalChipDetalhe = document.getElementById('modalChipDetalhe');
    var tabelaDetalheChip = document.getElementById('tabelaDetalheChip');
    var btnFecharModalChip = document.getElementById('btnFecharModalChip');
    var btnMostrarClassificacao = document.getElementById('btnMostrarClassificacao');
    var btnMostrarTradicional = document.getElementById('btnMostrarTradicional');
    var secaoClassificacao = document.getElementById('secaoClassificacao');
    var secaoTradicional = document.getElementById('secaoTradicional');
    var painelMalotesChips = document.getElementById('painelMalotesChips');
    var painelMalotesSubtitulo = document.getElementById('painelMalotesSubtitulo');
    var painelMalotesLotes = document.getElementById('painelMalotesLotes');
    var painelMalotesIipr = document.getElementById('painelMalotesIipr');
    var malotesResumoConfirmados = document.getElementById('malotesResumoConfirmados');
    var malotesResumoIipr = document.getElementById('malotesResumoIipr');
    var malotesResumoCorreios = document.getElementById('malotesResumoCorreios');
    var inputLacreIiprMalote = document.getElementById('inputLacreIiprMalote');
    var inputLacreCorreiosMalote = document.getElementById('inputLacreCorreiosMalote');
    var inputEtiquetaCorreiosMalote = document.getElementById('inputEtiquetaCorreiosMalote');
    var btnSalvarMaloteIipr = document.getElementById('btnSalvarMaloteIipr');
    var btnSalvarMaloteCorreios = document.getElementById('btnSalvarMaloteCorreios');
    var btnSalvarEtiquetaCorreios = document.getElementById('btnSalvarEtiquetaCorreios');
    var btnLimparMaloteLote = document.getElementById('btnLimparMaloteLote');
    var btnAlternarVozMalotes = document.getElementById('btnAlternarVozMalotes');
    var statusVozComando = document.getElementById('statusVozComando');
    var vozCampoAtual = document.getElementById('vozCampoAtual');
    var painelDiagnosticoVoz = document.getElementById('painelDiagnosticoVoz');
    var btnAbrirControleRemoto = document.getElementById('btnAbrirControleRemoto');
    var statusControleRemoto = document.getElementById('statusControleRemoto');
    var btnAbrirPreviaMalotes = document.getElementById('btnAbrirPreviaMalotes');
    var statusPreviaMalotes = document.getElementById('statusPreviaMalotes');
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
    var painelHistoricoLeitura = document.getElementById('painelHistoricoLeitura');
    var btnToggleHistoricoLeitura = document.getElementById('btnToggleHistoricoLeitura');
    var listaHistoricoLeitura = document.getElementById('listaHistoricoLeitura');
    var btnToggleTodosChips = document.getElementById('btnToggleTodosChips');
    var btnToggleTodosTradicional = document.getElementById('btnToggleTodosTradicional');
    var btnSalvarPacotes = document.getElementById('btnSalvarPacotes');
    var btnCancelarPacotes = document.getElementById('btnCancelarPacotes');
    var resumoPacotesPendentes = document.getElementById('resumoPacotesPendentes');
    var autorSalvamentoPacotes = document.getElementById('autor_salvamento_pacotes');
    var turnoSalvamentoPacotes = document.getElementById('turno_salvamento_pacotes');
    var criadoSalvamentoPacotes = document.getElementById('criado_salvamento_pacotes');
    var consolidarSalvamentoPacotes = document.getElementById('consolidar_salvamento_pacotes');
    var pacotesPendentes = [];
    var mensagemLeitura = document.getElementById('mensagemLeitura');
    var postoBloqueioNumero = document.getElementById('postoBloqueioNumero');
    var postoBloqueioNome = document.getElementById('postoBloqueioNome');
    var postoBloqueioResponsavel = document.getElementById('postoBloqueioResponsavel');
    var postoDesbloqueioResponsavel = document.getElementById('postoDesbloqueioResponsavel');
    var postoDesbloqueioMotivo = document.getElementById('postoDesbloqueioMotivo');
    var btnAdicionarBloqueio = document.getElementById('btnAdicionarBloqueio');
    var listaPostosBloqueados = document.getElementById('listaPostosBloqueados');
    var postosBloqueados = <?php echo json_encode($postos_bloqueados); ?>;
    var postosBloqueadosMap = {};
    // v1.2.2: Restricoes de postos (segurar/adiantar/fechado/personalizados)
    var postosRestricoes = <?php echo json_encode($postos_restricoes); ?>;
    var tipoEscolhido = false;
    var datasFiltroSql = <?php echo json_encode($datas_sql); ?>;
    var storageUsuarioKey = 'conferencia_responsavel';
    var storageTipoKey = 'conferencia_tipo_inicio';
    var storageModoKey = 'conferencia_modo';
    var storageHistoricoLeituraKey = 'conferencia_historico_leitura_aberto';
    var storageChipsRecolhidosKey = 'conferencia_chips_recolhidos';
    var storageTradicionalRecolhidoKey = 'conferencia_tradicional_recolhido';
    var previewStorageKey = 'conferencia_previa_malotes_v1';
    var controleCanal = <?php echo json_encode($controle_canal); ?>;
    var contextoSelecionadoMalote = '';
    var tipoContextoSelecionadoMalote = '';
    var rotuloContextoSelecionadoMalote = '';
    var previewChannel = null;
    var previewWindowRef = null;
    var reconhecimentoVoz = null;
    var vozEscutaAtiva = false;
    var vozModoAtual = '';
    var vozReinicioManual = false;
    var pollingRemotoAtivo = false;
    var ultimoLacreIiprAplicado = '';
    var ultimoLacreCorreiosAplicado = '';
    var ultimaEtiquetaCorreiosAplicada = '';
    var contextoSplitSequencia = {};
    var valoresDigitadosPorContexto = {};
    var comandosCodigoBarras = {
        '990000000000000000001': { tipo: 'iipr', mensagem: 'Aguardando leitura do lacre IIPR.' },
        '990000000000000000002': { tipo: 'correios_lacre', mensagem: 'Aguardando leitura do lacre Correios.' },
        '990000000000000000003': { tipo: 'correios_etiqueta', mensagem: 'Aguardando leitura da etiqueta Correios.' },
        '990000000000000000009': { tipo: 'cancelar', mensagem: 'Comando cancelado.' }
    };

    if (window.BroadcastChannel) {
        try {
            previewChannel = new BroadcastChannel('conferencia_previa_malotes');
        } catch (e0) {
            previewChannel = null;
        }
    }

    function aplicarModoConsulta(ativo) {
        modoConsulta = !!ativo;
        if (modoConsulta) {
            document.body.classList.add('modo-consulta');
            if (overlayUsuario) overlayUsuario.style.display = 'none';
            if (conteudoPagina) conteudoPagina.classList.remove('page-locked');
            tipoEscolhido = false;
            usuarioAtual = '';
        } else {
            document.body.classList.remove('modo-consulta');
        }
    }

    function ativarConsulta() {
        try { localStorage.setItem(storageModoKey, 'consulta'); } catch (e) {}
        aplicarModoConsulta(true);
        atualizarResumoTodasTabelas();
    }

    function ativarConferencia() {
        try { localStorage.removeItem(storageModoKey); } catch (e) {}
        aplicarModoConsulta(false);
        if (overlayUsuario) overlayUsuario.style.display = 'flex';
        if (conteudoPagina) conteudoPagina.classList.add('page-locked');
        if (usuarioInputModal) usuarioInputModal.focus();
    }

    // v9.22.7: Fila de áudio para evitar sobreposição
    var filaSons = [];
    var tocando = false;
    var ultimoCodLido = '';
    var codigosEmProcessamento = {};
    var ultimoCodigoProcessado = '';
    var ultimaLeituraProcessadaEm = 0;
    var ultimoAvisoScanner = { chave: '', quando: 0 };
    var ultimaLeituraConfirmada = { codigo: '', contexto: '', quando: 0 };
    var limpezaParcialInputTimer = null;
    var chipsRecolhidos = false;
    var tradicionalRecolhido = false;
    var wakeLockSentinel = null;

    try { chipsRecolhidos = true; localStorage.setItem(storageChipsRecolhidosKey, '1'); } catch (eChips) { chipsRecolhidos = true; }
    try { tradicionalRecolhido = localStorage.getItem(storageTradicionalRecolhidoKey) === '1'; } catch (eTrad) {}

    function mostrarConfirmacao(texto, autoFechar) {
        if (confirmacaoTexto) {
            confirmacaoTexto.textContent = texto || 'Dados salvos com sucesso!';
        }
        if (overlayConfirmacao) {
            overlayConfirmacao.style.display = 'flex';
        }
        if (autoFechar) {
            setTimeout(function() {
                if (overlayConfirmacao) overlayConfirmacao.style.display = 'none';
            }, 1200);
        }
    }

    if (btnConfirmacaoOk) {
        btnConfirmacaoOk.addEventListener('click', function() {
            if (overlayConfirmacao) overlayConfirmacao.style.display = 'none';
        });
    }

    if (btnFecharModalChip) {
        btnFecharModalChip.addEventListener('click', function() {
            if (modalChipDetalhe) modalChipDetalhe.style.display = 'none';
        });
    }

    function escapeHtml(texto) {
        return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizarNumeroLacre(valor) {
        return String(valor || '').replace(/\D+/g, '');
    }

    function formatarCodigoComZeros(valor, tamanho) {
        var d = String(valor || '').replace(/\D+/g, '');
        if (!d) return '';
        return d.padStart(tamanho || 3, '0');
    }

    function ehRegionalOperacional(codigoRegional) {
        var codigo = formatarCodigoComZeros(codigoRegional, 3);
        return !!codigo && codigo !== '000' && codigo !== '001' && codigo !== '999';
    }

    function montarContextoMalote(tipo, chave, rotulo) {
        return {
            tipo: tipo || '',
            chave: chave || '',
            rotulo: rotulo || ''
        };
    }

    function obterChaveContextoMalote(contexto) {
        if (!contexto || !contexto.tipo || !contexto.chave) return '';
        return String(contexto.tipo) + '|' + String(contexto.chave);
    }

    function extrairSegmentoSplitGrupo(grupo) {
        var texto = String(grupo || '');
        var match = texto.match(/_S(\d+)(?:_|$)/);
        if (!match || typeof match[1] === 'undefined') {
            return 0;
        }
        return parseInt(match[1], 10) || 0;
    }

    function obterMaiorSegmentoContexto(contexto) {
        var chips = [];
        var maior = 0;
        var i;
        var dados;
        if (!contexto || !contexto.tipo || !contexto.chave) return 0;
        chips = document.querySelectorAll('.operacao-chip');
        for (i = 0; i < chips.length; i++) {
            dados = obterDadosChipOperacao(chips[i]);
            if (!dados) continue;
            var contextoChip = obterContextoMaloteDeDados(dados);
            if (contextoChip.tipo !== contexto.tipo || contextoChip.chave !== contexto.chave) continue;
            maior = Math.max(maior, extrairSegmentoSplitGrupo(dados.grupo_correios || dados.grupo_iipr || ''));
        }
        return maior;
    }

    function obterSegmentoAtualContexto(contexto) {
        var chaveContexto = obterChaveContextoMalote(contexto);
        if (!chaveContexto) return 0;
        if (typeof contextoSplitSequencia[chaveContexto] === 'number') {
            return contextoSplitSequencia[chaveContexto];
        }
        contextoSplitSequencia[chaveContexto] = obterMaiorSegmentoContexto(contexto);
        return contextoSplitSequencia[chaveContexto];
    }

    function obterContextoMaloteDeDados(dados) {
        if (!dados) return montarContextoMalote('', '', '');
        if (dados.isPT) {
            return montarContextoMalote('posto', formatarCodigoComZeros(dados.posto, 3), 'Posto ' + formatarCodigoComZeros(dados.posto, 3));
        }
        var regionalCodigo = formatarCodigoComZeros(dados.regional_codigo || dados.regional, 3);
        if (ehRegionalOperacional(regionalCodigo)) {
            return montarContextoMalote('regional', regionalCodigo, 'Regional ' + regionalCodigo);
        }
        var postoCodigo = formatarCodigoComZeros(dados.posto, 3);
        if (postoCodigo) {
            return montarContextoMalote('posto', postoCodigo, 'Posto ' + postoCodigo);
        }
        if (regionalCodigo) {
            return montarContextoMalote('regional', regionalCodigo, 'Regional ' + regionalCodigo);
        }
        return montarContextoMalote('', '', '');
    }

    function obterContextoMaloteDeChip(chip) {
        return obterContextoMaloteDeDados(obterDadosChipOperacao(chip));
    }

    function obterContextoMaloteDeLinha(linha) {
        if (!linha) return montarContextoMalote('', '', '');
        var chip = linha.querySelector('.operacao-chip');
        if (chip) {
            return obterContextoMaloteDeChip(chip);
        }
        var postoCodigo = formatarCodigoComZeros(linha.getAttribute('data-posto') || '', 3);
        var rotuloGrupo = String(linha.getAttribute('data-grupo') || '').trim();
        if (/^regional\s+\d+/i.test(rotuloGrupo)) {
            var m = rotuloGrupo.match(/(\d{1,3})/);
            if (m && m[1]) {
                var regionalCodigo = formatarCodigoComZeros(m[1], 3);
                return montarContextoMalote('regional', regionalCodigo, 'Regional ' + regionalCodigo);
            }
        }
        return montarContextoMalote('posto', postoCodigo, postoCodigo ? ('Posto ' + postoCodigo) : rotuloGrupo);
    }

    function obterContextoMaloteDeRegistroTabela(linha) {
        if (!linha) return montarContextoMalote('', '', '');
        var isPt = linha.getAttribute('data-ispt') === '1';
        var postoCodigo = formatarCodigoComZeros(linha.getAttribute('data-posto') || '', 3);
        var regionalCodigo = formatarCodigoComZeros(linha.getAttribute('data-regional-real') || linha.getAttribute('data-regional') || '', 3);
        if (isPt) {
            return montarContextoMalote('posto', postoCodigo, postoCodigo ? ('Posto ' + postoCodigo) : 'Posto');
        }
        if (ehRegionalOperacional(regionalCodigo)) {
            return montarContextoMalote('regional', regionalCodigo, 'Regional ' + regionalCodigo);
        }
        if (postoCodigo) {
            return montarContextoMalote('posto', postoCodigo, 'Posto ' + postoCodigo);
        }
        if (regionalCodigo) {
            return montarContextoMalote('regional', regionalCodigo, 'Regional ' + regionalCodigo);
        }
        return montarContextoMalote('', '', '');
    }

    function normalizarTextoVoz(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function extrairDigitosFalados(texto) {
        var direto = String(texto || '').replace(/\D+/g, '');
        if (direto) return direto;
        var mapa = {
            zero: '0',
            um: '1',
            uma: '1',
            dois: '2',
            duas: '2',
            tres: '3',
            quatro: '4',
            cinco: '5',
            seis: '6',
            meia: '6',
            sete: '7',
            oito: '8',
            nove: '9'
        };
        var normalizado = normalizarTextoVoz(texto);
        if (!normalizado) return '';
        var partes = normalizado.split(' ');
        var digitos = [];
        for (var i = 0; i < partes.length; i++) {
            if (mapa[partes[i]]) {
                digitos.push(mapa[partes[i]]);
            }
        }
        return digitos.join('');
    }

    function atualizarStatusVoz(texto, tipo) {
        if (statusVozComando) {
            statusVozComando.textContent = texto;
        }
        if (btnAlternarVozMalotes) {
            btnAlternarVozMalotes.classList.toggle('ativo', vozEscutaAtiva);
            btnAlternarVozMalotes.textContent = vozEscutaAtiva ? 'Desligar microfone' : 'Ativar microfone';
        }
        if (vozCampoAtual) {
            vozCampoAtual.className = 'voz-status-pill';
            if (tipo) {
                vozCampoAtual.classList.add(tipo);
            }
        }
    }

    function atualizarCampoVoz(texto, tipo) {
        if (!vozCampoAtual) return;
        vozCampoAtual.textContent = texto;
        vozCampoAtual.className = 'voz-status-pill';
        if (tipo) {
            vozCampoAtual.classList.add(tipo);
        }
    }

    function obterDiagnosticoVoz() {
        var SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
        var protocolo = window.location && window.location.protocol ? window.location.protocol : '';
        var host = window.location && window.location.host ? window.location.host : '';
        var hostname = window.location && window.location.hostname ? window.location.hostname : '';
        var secure = !!window.isSecureContext;
        var localhost = hostname === 'localhost' || hostname === '127.0.0.1';
        var mediaDevices = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        var userAgent = navigator.userAgent || '';
        var navegador = 'Navegador não identificado';

        if (/Edg\//.test(userAgent)) {
            navegador = 'Microsoft Edge';
        } else if (/Chrome\//.test(userAgent) && !/Edg\//.test(userAgent)) {
            navegador = 'Google Chrome';
        } else if (/Firefox\//.test(userAgent)) {
            navegador = 'Mozilla Firefox';
        } else if (/Safari\//.test(userAgent) && !/Chrome\//.test(userAgent)) {
            navegador = 'Safari';
        } else if (/OPR\//.test(userAgent)) {
            navegador = 'Opera';
        }

        var causas = [];
        if (!SpeechRecognitionApi) {
            causas.push('A API SpeechRecognition não existe neste navegador.');
        }
        if (!secure && !localhost) {
            causas.push('A página não está em HTTPS nem em localhost.');
        }
        if (/Firefox\//.test(userAgent)) {
            causas.push('Firefox normalmente não expõe a API usada nesta implementação.');
        }
        if (/OPR\//.test(userAgent)) {
            causas.push('Opera pode esconder essa API mesmo com microfone liberado.');
        }
        if (!mediaDevices) {
            causas.push('O navegador não expõe getUserMedia para testes de microfone.');
        }

        return {
            speech: !!SpeechRecognitionApi,
            media: mediaDevices,
            secure: secure,
            localhost: localhost,
            protocolo: protocolo,
            host: host,
            navegador: navegador,
            userAgent: userAgent,
            causas: causas
        };
    }

    function renderizarDiagnosticoVoz() {
        if (!painelDiagnosticoVoz) return;
        var diag = obterDiagnosticoVoz();
        var linhas = [];
        linhas.push('<li><span class="' + (diag.speech ? 'ok' : 'erro') + '">SpeechRecognition:</span> ' + (diag.speech ? 'disponível' : 'indisponível') + '</li>');
        linhas.push('<li><span class="' + (diag.media ? 'ok' : 'erro') + '">Microfone do navegador:</span> ' + (diag.media ? 'API disponível' : 'API indisponível') + '</li>');
        linhas.push('<li><span class="' + ((diag.secure || diag.localhost) ? 'ok' : 'aviso') + '">Origem da página:</span> ' + (diag.protocolo || '-') + '//' + (diag.host || '-') + '</li>');
        linhas.push('<li><span class="ok">Navegador detectado:</span> ' + diag.navegador + '</li>');

        if (diag.causas.length) {
            linhas.push('<li><span class="erro">Causa provável:</span> ' + diag.causas.join(' ')+ '</li>');
        } else {
            linhas.push('<li><span class="ok">Compatibilidade:</span> o navegador parece apto para reconhecimento de voz.</li>');
        }

        painelDiagnosticoVoz.innerHTML = '<strong>Diagnóstico de voz</strong><ul>' + linhas.join('') + '</ul>';
    }

    function obterInputPorModoVoz(modo) {
        if (modo === 'iipr') return inputLacreIiprMalote;
        if (modo === 'correios_lacre') return inputLacreCorreiosMalote;
        if (modo === 'correios_etiqueta') return inputEtiquetaCorreiosMalote;
        return null;
    }

    function limparModoVoz(motivo) {
        vozModoAtual = '';
        atualizarCampoVoz('Nenhum campo armado.', '');
        if (motivo) {
            atualizarStatusVoz(motivo, vozEscutaAtiva ? 'escutando' : '');
        }
    }

    function definirModoVoz(modo, mensagem) {
        var input = obterInputPorModoVoz(modo);
        vozModoAtual = modo;
        if (input) {
            input.focus();
            input.select();
        }
        atualizarCampoVoz(mensagem, 'aguardando');
        atualizarStatusVoz('Microfone ativo. Aguardando o valor do campo armado.', 'escutando');
        falarTexto(mensagem);
    }

    function preencherCampoPorVoz(valor) {
        var limite = vozModoAtual === 'correios_etiqueta' ? 35 : 12;
        var valorFinal = String(valor || '').replace(/\D+/g, '').slice(0, limite);
        if (!valorFinal) {
            atualizarStatusVoz('Nenhum dígito válido foi reconhecido.', 'erro');
            return false;
        }
        if (vozModoAtual === 'iipr') {
            aplicarLacreIiprNoContexto(valorFinal, 'voz');
            return true;
        }
        if (vozModoAtual === 'correios_lacre') {
            aplicarLacreCorreiosNoContexto(valorFinal, 'voz');
            return true;
        }
        if (vozModoAtual === 'correios_etiqueta') {
            aplicarEtiquetaCorreiosNoContexto(valorFinal, 'voz');
            return true;
        }
        atualizarStatusVoz('Nenhum campo de voz está armado no momento.', 'erro');
        return false;
    }

    function compactarSequenciaNumerica(valores) {
        var numeros = [];
        var textos = [];
        var vistos = {};
        for (var i = 0; i < valores.length; i++) {
            var atual = String(valores[i] || '').trim();
            if (!atual || vistos[atual]) continue;
            vistos[atual] = true;
            if (/^\d+$/.test(atual)) {
                numeros.push(parseInt(atual, 10));
            } else {
                textos.push(atual);
            }
        }
        numeros.sort(function(a, b) { return a - b; });
        var partes = [];
        var inicio = null;
        var anterior = null;
        for (var j = 0; j < numeros.length; j++) {
            var numero = numeros[j];
            if (inicio === null) {
                inicio = numero;
                anterior = numero;
                continue;
            }
            if (numero === anterior + 1) {
                anterior = numero;
                continue;
            }
            partes.push(inicio === anterior ? String(inicio) : (inicio + '-' + anterior));
            inicio = numero;
            anterior = numero;
        }
        if (inicio !== null) {
            partes.push(inicio === anterior ? String(inicio) : (inicio + '-' + anterior));
        }
        for (var k = 0; k < textos.length; k++) {
            partes.push(textos[k]);
        }
        return partes.join(', ');
    }

    function abrirPreviaMalotes() {
        previewWindowRef = window.open('conferencia_pacotes_previa.php?canal_controle=' + encodeURIComponent(controleCanal || 'principal'), '_blank');
        if (previewWindowRef) {
            if (statusPreviaMalotes) {
                statusPreviaMalotes.textContent = 'Prévia aberta. Arraste a janela para a segunda tela.';
            }
            publicarResumoPrevia();
        } else if (statusPreviaMalotes) {
            statusPreviaMalotes.textContent = 'O navegador bloqueou a abertura automática da prévia.';
        }
    }

    function montarResumoPreviaMalotes() {
        var chips = document.querySelectorAll('.operacao-chip');
        var grupos = {};
        var pendentes = {};
        var contextos = {};
        var totalConfirmados = 0;

        function obterChaveContextoResumo(contexto, dados) {
            var tipo = String(contexto && contexto.tipo ? contexto.tipo : 'posto');
            var chave = '';
            if (tipo === 'regional') {
                chave = String(contexto && contexto.chave ? contexto.chave : (dados && (dados.regional_codigo || dados.regional) ? (dados.regional_codigo || dados.regional) : ''));
            } else {
                chave = String(contexto && contexto.chave ? contexto.chave : (dados && dados.posto ? dados.posto : ''));
            }
            return tipo + '|' + chave;
        }

        function obterOuCriarContextoResumo(contexto, dados) {
            var chaveContexto = obterChaveContextoResumo(contexto, dados);
            if (!contextos[chaveContexto]) {
                contextos[chaveContexto] = {
                    chave_contexto: chaveContexto,
                    posto: dados && dados.posto ? dados.posto : '',
                    regional: dados && dados.regional ? dados.regional : '',
                    regional_codigo: dados && (dados.regional_codigo || dados.regional) ? (dados.regional_codigo || dados.regional) : '',
                    contexto_tipo: contexto && contexto.tipo ? contexto.tipo : '',
                    contexto_chave: contexto && contexto.chave ? contexto.chave : '',
                    contexto_rotulo: contexto && contexto.rotulo ? contexto.rotulo : '',
                    tem_linha_fechada: false,
                    tem_linha_pendente: false,
                    tem_trabalho_restante: false
                };
            }
            return contextos[chaveContexto];
        }

        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            if (!dados || dados.isPT) continue;
            var contextoChip = obterContextoMaloteDeDados(dados);
            var contextoInfo = obterOuCriarContextoResumo(contextoChip, dados);
            if (!dados.conferido) {
                contextoInfo.tem_trabalho_restante = true;
                continue;
            }
            totalConfirmados++;
            if (!valorResumoPreenchido(dados.lacre_iipr)) {
                var chavePendente = contextoInfo.chave_contexto;
                if (!pendentes[chavePendente]) {
                    pendentes[chavePendente] = {
                        posto: contextoInfo.posto || '',
                        regional: contextoInfo.regional || '',
                        regional_codigo: contextoInfo.regional_codigo || '',
                        contexto_tipo: contextoInfo.contexto_tipo || '',
                        contexto_chave: contextoInfo.contexto_chave || '',
                        contexto_rotulo: contextoInfo.contexto_rotulo || '',
                        lotes: [],
                        qtd_total: 0
                    };
                }
                pendentes[chavePendente].lotes.push(dados.lote || '');
                pendentes[chavePendente].qtd_total += parseInt(dados.qtd || 0, 10) || 0;
                contextoInfo.tem_linha_pendente = true;
                continue;
            }

            contextoInfo.tem_linha_fechada = true;
            var chaveResumo = contextoInfo.contexto_chave || '';
            var agrupadorMalote = dados.grupo_correios || dados.grupo_iipr || '';
            var chaveGrupo = [contextoInfo.contexto_tipo || '', chaveResumo, agrupadorMalote, dados.regional || ''].join('|');
            if (!grupos[chaveGrupo]) {
                grupos[chaveGrupo] = {
                    regional: contextoInfo.regional || '',
                    regional_codigo: contextoInfo.regional_codigo || '',
                    posto: contextoInfo.posto || '',
                    contexto_tipo: contextoInfo.contexto_tipo || '',
                    contexto_chave: chaveResumo,
                    contexto_rotulo: contextoInfo.contexto_rotulo || '',
                    lotes: [],
                    qtd_total: 0,
                    lacres_iipr: [],
                    lacres_correios: [],
                    etiqueta_correios: '',
                    grupo_iipr: dados.grupo_iipr || '',
                    grupo_correios: dados.grupo_correios || ''
                };
            }
            grupos[chaveGrupo].lotes.push(dados.lote || '');
            grupos[chaveGrupo].qtd_total += parseInt(dados.qtd || 0, 10) || 0;
            if (dados.lacre_iipr) grupos[chaveGrupo].lacres_iipr.push(dados.lacre_iipr);
            if (dados.lacre_correios) grupos[chaveGrupo].lacres_correios.push(dados.lacre_correios);
            if (!grupos[chaveGrupo].etiqueta_correios && dados.etiqueta_correios) {
                grupos[chaveGrupo].etiqueta_correios = dados.etiqueta_correios;
            }
        }

        var resumo = [];
        for (var chave in grupos) {
            if (!Object.prototype.hasOwnProperty.call(grupos, chave)) continue;
            resumo.push({
                row_key: 'grp:' + String(chave),
                regional: grupos[chave].regional,
                regional_codigo: grupos[chave].regional_codigo,
                posto: grupos[chave].posto,
                contexto_tipo: grupos[chave].contexto_tipo,
                contexto_chave: grupos[chave].contexto_chave,
                contexto_rotulo: grupos[chave].contexto_rotulo,
                lotes: grupos[chave].lotes,
                qtd_total: grupos[chave].qtd_total,
                lacre_iipr: compactarSequenciaNumerica(grupos[chave].lacres_iipr),
                lacre_correios: compactarSequenciaNumerica(grupos[chave].lacres_correios),
                etiqueta_correios: grupos[chave].etiqueta_correios,
                grupo_iipr: grupos[chave].grupo_iipr,
                grupo_correios: grupos[chave].grupo_correios
            });
        }

        resumo.sort(function(a, b) {
            var postoA = parseInt(a.posto || 0, 10) || 0;
            var postoB = parseInt(b.posto || 0, 10) || 0;
            if (postoA !== postoB) return postoA - postoB;
            if (a.regional < b.regional) return -1;
            if (a.regional > b.regional) return 1;
            return 0;
        });

        var listaPendentes = [];
        for (var pendente in pendentes) {
            if (!Object.prototype.hasOwnProperty.call(pendentes, pendente)) continue;
            listaPendentes.push(pendentes[pendente]);
        }
        listaPendentes.sort(function(a, b) {
            var postoA = parseInt(a.posto || 0, 10) || 0;
            var postoB = parseInt(b.posto || 0, 10) || 0;
            return postoA - postoB;
        });

        for (var p = 0; p < listaPendentes.length; p++) {
            resumo.push({
                row_key: 'pend:' + String(listaPendentes[p].contexto_tipo || 'posto') + ':' + String(listaPendentes[p].contexto_chave || listaPendentes[p].posto || p),
                regional: listaPendentes[p].regional || '',
                regional_codigo: listaPendentes[p].regional_codigo || (listaPendentes[p].contexto_tipo === 'regional' ? (listaPendentes[p].contexto_chave || '') : (listaPendentes[p].regional || '')),
                posto: listaPendentes[p].posto,
                contexto_tipo: listaPendentes[p].contexto_tipo,
                contexto_chave: listaPendentes[p].contexto_chave,
                contexto_rotulo: listaPendentes[p].contexto_rotulo,
                lotes: listaPendentes[p].lotes,
                qtd_total: listaPendentes[p].qtd_total,
                lacre_iipr: '',
                lacre_correios: '',
                etiqueta_correios: '',
                grupo_iipr: '',
                grupo_correios: '',
                grupos_correios: [],
                pendente_lacre: true
            });
        }

        for (var chaveContexto in contextos) {
            if (!Object.prototype.hasOwnProperty.call(contextos, chaveContexto)) continue;
            var contextoLinha = contextos[chaveContexto];
            if (!contextoLinha.tem_linha_fechada || contextoLinha.tem_linha_pendente || !contextoLinha.tem_trabalho_restante) continue;
            resumo.push({
                row_key: 'pend:' + String(contextoLinha.contexto_tipo || 'posto') + ':' + String(contextoLinha.contexto_chave || contextoLinha.posto || chaveContexto),
                regional: contextoLinha.regional || '',
                regional_codigo: contextoLinha.regional_codigo || '',
                posto: contextoLinha.posto || '',
                contexto_tipo: contextoLinha.contexto_tipo || '',
                contexto_chave: contextoLinha.contexto_chave || '',
                contexto_rotulo: contextoLinha.contexto_rotulo || '',
                lotes: [],
                qtd_total: 0,
                lacre_iipr: '',
                lacre_correios: '',
                etiqueta_correios: '',
                grupo_iipr: '',
                grupo_correios: '',
                grupos_correios: [],
                pendente_lacre: true
            });
        }

        var destinoDigitadoPorContexto = {};
        for (var r = 0; r < resumo.length; r++) {
            var chaveContextoResumo = String(resumo[r].contexto_tipo || '') + '|' + String(resumo[r].contexto_chave || '');
            if (resumo[r].pendente_lacre && typeof destinoDigitadoPorContexto[chaveContextoResumo] === 'undefined') {
                destinoDigitadoPorContexto[chaveContextoResumo] = r;
            }
        }

        for (var chaveDigitada in valoresDigitadosPorContexto) {
            if (!Object.prototype.hasOwnProperty.call(valoresDigitadosPorContexto, chaveDigitada)) continue;
            var indiceDestino = typeof destinoDigitadoPorContexto[chaveDigitada] !== 'undefined' ? destinoDigitadoPorContexto[chaveDigitada] : -1;
            if (indiceDestino < 0) {
                for (var busca = resumo.length - 1; busca >= 0; busca--) {
                    var chaveBusca = String(resumo[busca].contexto_tipo || '') + '|' + String(resumo[busca].contexto_chave || '');
                    if (chaveBusca === chaveDigitada) {
                        indiceDestino = busca;
                        break;
                    }
                }
            }
            if (indiceDestino < 0) continue;
            var digitado = valoresDigitadosPorContexto[chaveDigitada] || null;
            if (!digitado) continue;
            if (digitado.lacre_iipr) {
                resumo[indiceDestino].lacre_iipr = digitado.lacre_iipr;
            }
            if (digitado.lacre_correios) {
                resumo[indiceDestino].lacre_correios = digitado.lacre_correios;
            }
            if (digitado.etiqueta_correios) {
                resumo[indiceDestino].etiqueta_correios = digitado.etiqueta_correios;
            }
        }

        resumo.sort(function(a, b) {
            var regA = parseInt(a.regional_codigo || 0, 10) || 0;
            var regB = parseInt(b.regional_codigo || 0, 10) || 0;
            if (regA !== regB) return regA - regB;
            if (!!a.pendente_lacre !== !!b.pendente_lacre) return a.pendente_lacre ? 1 : -1;
            var grupoA = String(a.grupo_correios || a.grupo_iipr || a.row_key || '');
            var grupoB = String(b.grupo_correios || b.grupo_iipr || b.row_key || '');
            if (grupoA < grupoB) return -1;
            if (grupoA > grupoB) return 1;
            return 0;
        });

        return {
            versao: '1.0.0',
            gerado_em: formatarDataHoraAtual(),
            usuario: usuarioAtual || '',
            contexto_selecionado: contextoSelecionadoMalote || '',
            contexto_tipo: tipoContextoSelecionadoMalote || '',
            contexto_rotulo: rotuloContextoSelecionadoMalote || '',
            datas_filtro: datasFiltroSql.slice(0),
            total_confirmados: totalConfirmados,
            total_fechados: resumo.length,
            resumo: resumo,
            pendentes: listaPendentes
        };
    }

    function obterChaveResumoPrevia(item, indice) {
        if (!item) return 'idx:' + indice;
        if (item.row_key) return String(item.row_key);
        var tipo = String(item.contexto_tipo || 'posto');
        var contexto = String(item.contexto_chave || item.posto || item.regional_codigo || indice);
        var grupo = String(item.grupo_correios || item.grupo_iipr || '');
        return [tipo, contexto, grupo].join('|');
    }

    function valorResumoPreenchido(valor) {
        var texto = String(valor == null ? '' : valor).trim();
        return texto !== '' && texto !== '0';
    }

    function mesclarSnapshotPreviaComAnterior(snapshot) {
        if (!snapshot || !snapshot.resumo || !snapshot.resumo.length) return snapshot;
        var anterior = null;
        try {
            anterior = JSON.parse(localStorage.getItem(previewStorageKey) || 'null');
        } catch (e) {
            anterior = null;
        }
        if (!anterior || !anterior.resumo || !anterior.resumo.length) {
            return snapshot;
        }

        var mapaAnterior = {};
        for (var i = 0; i < anterior.resumo.length; i++) {
            var itemAnterior = anterior.resumo[i] || {};
            mapaAnterior[obterChaveResumoPrevia(itemAnterior, i)] = itemAnterior;
        }

        for (var j = 0; j < snapshot.resumo.length; j++) {
            var itemNovo = snapshot.resumo[j] || {};
            var itemAnteriorMesmo = mapaAnterior[obterChaveResumoPrevia(itemNovo, j)] || null;
            if (!itemAnteriorMesmo) continue;

            if (!valorResumoPreenchido(itemNovo.lacre_iipr) && valorResumoPreenchido(itemAnteriorMesmo.lacre_iipr)) {
                itemNovo.lacre_iipr = itemAnteriorMesmo.lacre_iipr;
            }
            if (!valorResumoPreenchido(itemNovo.lacre_correios) && valorResumoPreenchido(itemAnteriorMesmo.lacre_correios)) {
                itemNovo.lacre_correios = itemAnteriorMesmo.lacre_correios;
            }
            if (!valorResumoPreenchido(itemNovo.etiqueta_correios) && valorResumoPreenchido(itemAnteriorMesmo.etiqueta_correios)) {
                itemNovo.etiqueta_correios = itemAnteriorMesmo.etiqueta_correios;
            }
            snapshot.resumo[j] = itemNovo;
        }

        return snapshot;
    }

    function publicarResumoPrevia() {
        var snapshot = montarResumoPreviaMalotes();
        snapshot = mesclarSnapshotPreviaComAnterior(snapshot);
        try {
            localStorage.setItem(previewStorageKey, JSON.stringify(snapshot));
        } catch (e1) {}
        if (previewChannel) {
            try {
                previewChannel.postMessage(snapshot);
            } catch (e2) {}
        }
        if (statusPreviaMalotes) {
            statusPreviaMalotes.textContent = 'Última atualização: ' + snapshot.gerado_em + ' • linhas prontas: ' + snapshot.total_fechados;
        }
    }

    function interpretarComandoVoz(transcricao) {
        var comando = normalizarTextoVoz(transcricao);
        if (!comando) return;

        if (comando.indexOf('abrir previa') !== -1 || comando.indexOf('abrir pre via') !== -1) {
            abrirPreviaMalotes();
            return;
        }
        if (comando.indexOf('cancelar comando') !== -1 || comando.indexOf('parar comando') !== -1 || comando.indexOf('limpar comando') !== -1) {
            limparModoVoz('Comando de voz cancelado.');
            falarTexto('comando cancelado');
            return;
        }
        if (comando.indexOf('salvar malote iipr') !== -1 || comando.indexOf('gravar malote iipr') !== -1) {
            if (ultimoLacreIiprAplicado) {
                aplicarLacreIiprNoContexto(ultimoLacreIiprAplicado, 'voz');
            }
            return;
        }
        if (comando.indexOf('salvar malote correios') !== -1 || comando.indexOf('gravar malote correios') !== -1 || comando.indexOf('vincular malote correios') !== -1) {
            if (ultimoLacreCorreiosAplicado) {
                aplicarLacreCorreiosNoContexto(ultimoLacreCorreiosAplicado, 'voz');
            }
            return;
        }
        if (comando.indexOf('fechar malote iipr') !== -1 || comando.indexOf('lacre iipr') !== -1) {
            definirModoVoz('iipr', 'Aguardando lacre IIPR');
            return;
        }
        if (comando.indexOf('fechar malote correios') !== -1 || comando.indexOf('lacre correios') !== -1) {
            definirModoVoz('correios_lacre', 'Aguardando lacre Correios');
            return;
        }
        if (comando.indexOf('etiqueta correios') !== -1) {
            definirModoVoz('correios_etiqueta', 'Aguardando etiqueta Correios');
            return;
        }
        if (vozModoAtual) {
            var digitos = extrairDigitosFalados(transcricao);
            if (digitos) {
                preencherCampoPorVoz(digitos);
                return;
            }
        }
        atualizarStatusVoz('Comando não reconhecido: ' + transcricao, 'erro');
    }

    function configurarReconhecimentoVoz() {
        if (reconhecimentoVoz) return reconhecimentoVoz;
        var SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognitionApi) {
            renderizarDiagnosticoVoz();
            atualizarStatusVoz('Reconhecimento de voz indisponível neste navegador ou nesta origem da página.', 'erro');
            if (btnAlternarVozMalotes) btnAlternarVozMalotes.disabled = true;
            return null;
        }
        reconhecimentoVoz = new SpeechRecognitionApi();
        reconhecimentoVoz.lang = 'pt-BR';
        reconhecimentoVoz.continuous = true;
        reconhecimentoVoz.interimResults = false;

        reconhecimentoVoz.onstart = function() {
            vozEscutaAtiva = true;
            renderizarDiagnosticoVoz();
            atualizarStatusVoz('Microfone ativo. Aguardando comando.', 'escutando');
        };
        reconhecimentoVoz.onresult = function(event) {
            for (var i = event.resultIndex; i < event.results.length; i++) {
                if (!event.results[i].isFinal) continue;
                var texto = event.results[i][0] ? event.results[i][0].transcript : '';
                interpretarComandoVoz(texto);
            }
        };
        reconhecimentoVoz.onerror = function(event) {
            renderizarDiagnosticoVoz();
            atualizarStatusVoz('Falha no reconhecimento de voz: ' + (event && event.error ? event.error : 'erro desconhecido'), 'erro');
        };
        reconhecimentoVoz.onend = function() {
            if (vozEscutaAtiva && !vozReinicioManual) {
                try {
                    reconhecimentoVoz.start();
                    return;
                } catch (e3) {}
            }
            vozEscutaAtiva = false;
            vozReinicioManual = false;
            renderizarDiagnosticoVoz();
            atualizarStatusVoz('Microfone desligado.', '');
        };
        return reconhecimentoVoz;
    }

    function alternarReconhecimentoVoz() {
        var reconhecimento = configurarReconhecimentoVoz();
        if (!reconhecimento) return;
        if (vozEscutaAtiva) {
            vozReinicioManual = true;
            vozEscutaAtiva = false;
            try {
                reconhecimento.stop();
            } catch (e4) {}
            limparModoVoz('Microfone desligado.');
            return;
        }
        vozReinicioManual = false;
        try {
            reconhecimento.start();
        } catch (e5) {
            atualizarStatusVoz('Não foi possível ativar o microfone agora.', 'erro');
        }
    }

    function obterChipsPorContextoMalote() {
        if (!contextoSelecionadoMalote || !tipoContextoSelecionadoMalote) return [];
        var chips = document.querySelectorAll('.operacao-chip');
        var lista = [];
        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            var contexto = obterContextoMaloteDeDados(dados);
            if (contexto.tipo === tipoContextoSelecionadoMalote && contexto.chave === contextoSelecionadoMalote) {
                lista.push(chips[i]);
            }
        }
        return lista;
    }

    function obterDadosChipOperacao(chip) {
        if (!chip) return null;
        return {
            codigo: chip.getAttribute('data-codigo') || '',
            lote: chip.getAttribute('data-lote') || '',
            posto: chip.getAttribute('data-posto') || '',
            regional: chip.getAttribute('data-regional') || '',
            regional_codigo: chip.getAttribute('data-regional-codigo') || '',
            qtd: chip.getAttribute('data-qtd') || '',
            data: chip.getAttribute('data-data') || '',
            data_sql: chip.getAttribute('data-data-sql') || '',
            isPT: chip.getAttribute('data-ispt') === '1',
            conferido: chip.getAttribute('data-conf') === '1',
            lacre_iipr: normalizarNumeroLacre(chip.getAttribute('data-lacre-iipr') || ''),
            grupo_iipr: chip.getAttribute('data-grupo-iipr') || '',
            lacre_correios: normalizarNumeroLacre(chip.getAttribute('data-lacre-correios') || ''),
            grupo_correios: chip.getAttribute('data-grupo-correios') || '',
            etiqueta_correios: String(chip.getAttribute('data-etiqueta-correios') || '').trim(),
            usuario_lacre: chip.getAttribute('data-usuario-lacre') || '',
            atualizado_lacre: chip.getAttribute('data-atualizado-lacre') || ''
        };
    }

    function aplicarAtribuicaoNoChip(chip, dados) {
        if (!chip) return;
        var lacreIipr = dados && dados.lacre_iipr ? normalizarNumeroLacre(dados.lacre_iipr) : '';
        var lacreCorreios = dados && dados.lacre_correios ? normalizarNumeroLacre(dados.lacre_correios) : '';
        var etiquetaCorreios = dados && dados.etiqueta_correios ? String(dados.etiqueta_correios).trim() : '';
        var usuarioLacre = dados && dados.usuario_lacre ? String(dados.usuario_lacre).trim() : '';
        var atualizadoLacre = dados && dados.atualizado_lacre ? String(dados.atualizado_lacre).trim() : '';
        chip.setAttribute('data-lacre-iipr', lacreIipr);
        chip.setAttribute('data-grupo-iipr', dados && dados.grupo_iipr ? String(dados.grupo_iipr) : '');
        chip.setAttribute('data-lacre-correios', lacreCorreios);
        chip.setAttribute('data-grupo-correios', dados && dados.grupo_correios ? String(dados.grupo_correios) : '');
        chip.setAttribute('data-etiqueta-correios', etiquetaCorreios);
        chip.setAttribute('data-usuario-lacre', usuarioLacre);
        chip.setAttribute('data-atualizado-lacre', atualizadoLacre);
        chip.classList.toggle('tem-iipr', lacreIipr !== '');
        chip.classList.toggle('tem-correios', lacreCorreios !== '' || etiquetaCorreios !== '');

        var linhaTabela = document.querySelector('tr[data-codigo="' + chip.getAttribute('data-codigo') + '"]');
        if (linhaTabela) {
            linhaTabela.setAttribute('data-lacre-iipr', lacreIipr);
            linhaTabela.setAttribute('data-grupo-iipr', dados && dados.grupo_iipr ? String(dados.grupo_iipr) : '');
            linhaTabela.setAttribute('data-lacre-correios', lacreCorreios);
            linhaTabela.setAttribute('data-grupo-correios', dados && dados.grupo_correios ? String(dados.grupo_correios) : '');
            linhaTabela.setAttribute('data-etiqueta-correios', etiquetaCorreios);
            linhaTabela.setAttribute('data-usuario-lacre', usuarioLacre);
            linhaTabela.setAttribute('data-atualizado-lacre', atualizadoLacre);
        }
    }

    function selecionarContextoMalote(contexto) {
        contextoSelecionadoMalote = contexto && contexto.chave ? contexto.chave : '';
        tipoContextoSelecionadoMalote = contexto && contexto.tipo ? contexto.tipo : '';
        rotuloContextoSelecionadoMalote = contexto && contexto.rotulo ? contexto.rotulo : '';

        var linhas = document.querySelectorAll('.operacao-posto-row.selecionado-malote');
        for (var i = 0; i < linhas.length; i++) {
            linhas[i].classList.remove('selecionado-malote');
        }
        if (contextoSelecionadoMalote) {
            var chips = obterChipsPorContextoMalote();
            for (var j = 0; j < chips.length; j++) {
                var linhaSelecionada = chips[j].closest('.operacao-posto-row');
                if (linhaSelecionada) {
                    linhaSelecionada.classList.add('selecionado-malote');
                }
            }
        }
        renderizarPainelMalotes();
        publicarEstadoRemoto();
    }

    function montarPacoteParaPersistencia(chip, sobrescritas) {
        var dados = obterDadosChipOperacao(chip);
        if (!dados) return null;
        var payload = {
            codbar: dados.codigo,
            lote: dados.lote,
            regional: normalizarRegionalValor(dados.regional_codigo || dados.regional),
            posto: dados.posto,
            dataexp: dados.data_sql || dados.data,
            qtd: dados.qtd,
            lacre_iipr: dados.lacre_iipr,
            grupo_iipr: dados.grupo_iipr,
            lacre_correios: dados.lacre_correios,
            grupo_correios: dados.grupo_correios,
            etiqueta_correios: dados.etiqueta_correios
        };
        if (sobrescritas) {
            for (var chave in sobrescritas) {
                if (Object.prototype.hasOwnProperty.call(sobrescritas, chave)) {
                    payload[chave] = sobrescritas[chave];
                }
            }
        }
        return payload;
    }

    function persistirAtribuicoesLote(pacotes, callback) {
        if (!pacotes || !pacotes.length) {
            alert('Selecione pelo menos um lote.');
            return;
        }
        if (!usuarioAtual) {
            alert('Informe o responsável da conferência antes de salvar os malotes.');
            return;
        }
        var formData = new FormData();
        formData.append('salvar_atribuicao_lacres_ajax', '1');
        formData.append('pacotes', JSON.stringify(pacotes));
        formData.append('usuario', usuarioAtual);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    alert((data && data.erro) ? data.erro : 'Não foi possível salvar a atribuição.');
                    return;
                }
                if (callback) callback();
                renderizarPainelMalotes();
                mostrarConfirmacao('Vínculos de malote salvos com sucesso.', true);
            })
            .catch(function() {
                alert('Erro ao salvar a atribuição dos malotes.');
            });
    }

    function atualizarMemoriaMalotes(tipo, valor) {
        if (tipo === 'iipr') {
            ultimoLacreIiprAplicado = valor || '';
        } else if (tipo === 'correios_lacre') {
            ultimoLacreCorreiosAplicado = valor || '';
        } else if (tipo === 'correios_etiqueta') {
            ultimaEtiquetaCorreiosAplicada = valor || '';
        }
        publicarEstadoRemoto();
    }

    function obterChaveContextoAtual() {
        if (!tipoContextoSelecionadoMalote || !contextoSelecionadoMalote) return '';
        return String(tipoContextoSelecionadoMalote) + '|' + String(contextoSelecionadoMalote);
    }

    function registrarValorDigitadoNoContexto(tipo, valor) {
        var chave = obterChaveContextoAtual();
        if (!chave) return;
        if (!valoresDigitadosPorContexto[chave]) {
            valoresDigitadosPorContexto[chave] = {
                lacre_iipr: '',
                lacre_correios: '',
                etiqueta_correios: ''
            };
        }
        if (tipo === 'iipr') {
            valoresDigitadosPorContexto[chave].lacre_iipr = valor || '';
        } else if (tipo === 'correios_lacre') {
            valoresDigitadosPorContexto[chave].lacre_correios = valor || '';
        } else if (tipo === 'correios_etiqueta') {
            valoresDigitadosPorContexto[chave].etiqueta_correios = valor || '';
        }
    }

    function sincronizarValorDigitadoNaPrevia(tipo, valor) {
        registrarValorDigitadoNoContexto(tipo, valor);
        atualizarMemoriaMalotes(tipo, valor);
        publicarResumoPrevia();
    }

    function garantirContextoMaloteAtual() {
        if (!contextoSelecionadoMalote && regionalAtual && ehRegionalOperacional(regionalAtual)) {
            selecionarContextoMalote(montarContextoMalote('regional', regionalAtual, 'Regional ' + regionalAtual));
        }
        return !!(contextoSelecionadoMalote && tipoContextoSelecionadoMalote);
    }

    function aplicarLacreIiprNoContexto(valorLacre, origem) {
        var lacreIipr = normalizarNumeroLacre(valorLacre || '');
        var chips = [];
        var contextoAtual = null;
        var grupoIipr = '';
        var pacotes = [];
        var segmentoSplit = 0;
        if (!lacreIipr) {
            mostrarConfirmacao('Informe o lacre IIPR antes de atribuir.', true);
            return false;
        }
        if (!garantirContextoMaloteAtual()) {
            mostrarConfirmacao('Nenhuma regional ativa foi identificada para o lacre IIPR.', true);
            return false;
        }
        sincronizarValorDigitadoNaPrevia('iipr', lacreIipr);
        chips = obterChipsConfirmadosSemIiprNoContexto();
        if (!chips.length) {
            mostrarConfirmacao('O contexto atual não possui lotes confirmados sem lacre IIPR.', true);
            return false;
        }
        contextoAtual = montarContextoMalote(tipoContextoSelecionadoMalote, contextoSelecionadoMalote, rotuloContextoSelecionadoMalote);
        segmentoSplit = obterSegmentoAtualContexto(contextoAtual);
        grupoIipr = 'GI_' + (contextoAtual.tipo === 'regional' ? ('R' + contextoAtual.chave) : ('P' + contextoAtual.chave)) + '_S' + segmentoSplit + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

        for (var i = 0; i < chips.length; i++) {
            var contextoChip = obterContextoMaloteDeChip(chips[i]);
            if (contextoChip.tipo !== contextoAtual.tipo || contextoChip.chave !== contextoAtual.chave) {
                continue;
            }
            var payload = montarPacoteParaPersistencia(chips[i], { lacre_iipr: lacreIipr, grupo_iipr: grupoIipr });
            if (payload) pacotes.push(payload);
        }
        if (!pacotes.length) {
            mostrarConfirmacao('Os lotes confirmados não pertencem ao contexto atual.', true);
            return false;
        }

        persistirAtribuicoesLote(pacotes, function() {
            var agora = formatarDataHoraAtual();
            for (var j = 0; j < chips.length; j++) {
                var dadosAtuais = obterDadosChipOperacao(chips[j]);
                aplicarAtribuicaoNoChip(chips[j], {
                    lacre_iipr: lacreIipr,
                    grupo_iipr: grupoIipr,
                    lacre_correios: dadosAtuais ? dadosAtuais.lacre_correios : '',
                    grupo_correios: dadosAtuais ? dadosAtuais.grupo_correios : '',
                    etiqueta_correios: dadosAtuais ? dadosAtuais.etiqueta_correios : '',
                    usuario_lacre: usuarioAtual,
                    atualizado_lacre: agora
                });
            }
            limparModoVoz('Lacre IIPR atribuído' + (origem ? ' por ' + origem : '') + '.');
        });
        return true;
    }

    function aplicarLacreCorreiosNoContexto(valorLacre, origem) {
        var lacreCorreios = normalizarNumeroLacre(valorLacre || '');
        var gruposMarcados = [];
        var grupoCorreios = '';
        var chips = [];
        var pacotes = [];
        var segmentoSplit = 0;
        if (!lacreCorreios) {
            mostrarConfirmacao('Informe o lacre Correios antes de atribuir.', true);
            return false;
        }
        if (!garantirContextoMaloteAtual()) {
            mostrarConfirmacao('Nenhuma regional ativa foi identificada para o lacre Correios.', true);
            return false;
        }
        sincronizarValorDigitadoNaPrevia('correios_lacre', lacreCorreios);
        gruposMarcados = obterGruposIiprSemCorreiosNoContexto();
        if (!gruposMarcados.length) {
            mostrarConfirmacao('O contexto atual não possui malotes IIPR aguardando lacre Correios.', true);
            return false;
        }

        if (gruposMarcados.length) {
            segmentoSplit = extrairSegmentoSplitGrupo(gruposMarcados[0]);
        }
        grupoCorreios = 'GC_' + (tipoContextoSelecionadoMalote === 'regional' ? ('R' + contextoSelecionadoMalote) : ('P' + contextoSelecionadoMalote)) + '_S' + segmentoSplit + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
        chips = obterChipsPorGrupoIipr(gruposMarcados);
        for (var i = 0; i < chips.length; i++) {
            var payload = montarPacoteParaPersistencia(chips[i], {
                lacre_correios: lacreCorreios,
                grupo_correios: grupoCorreios,
                etiqueta_correios: ''
            });
            if (payload) pacotes.push(payload);
        }
        if (!pacotes.length) {
            mostrarConfirmacao('Nenhum lote elegível foi encontrado para o lacre Correios.', true);
            return false;
        }

        persistirAtribuicoesLote(pacotes, function() {
            var agora = formatarDataHoraAtual();
            for (var j = 0; j < chips.length; j++) {
                var dadosAtuais = obterDadosChipOperacao(chips[j]);
                aplicarAtribuicaoNoChip(chips[j], {
                    lacre_iipr: dadosAtuais ? dadosAtuais.lacre_iipr : '',
                    grupo_iipr: dadosAtuais ? dadosAtuais.grupo_iipr : '',
                    lacre_correios: lacreCorreios,
                    grupo_correios: grupoCorreios,
                    etiqueta_correios: dadosAtuais ? dadosAtuais.etiqueta_correios : '',
                    usuario_lacre: usuarioAtual,
                    atualizado_lacre: agora
                });
            }
            limparModoVoz('Lacre Correios atribuído' + (origem ? ' por ' + origem : '') + '.');
        });
        return true;
    }

    function aplicarEtiquetaCorreiosNoContexto(valorEtiqueta, origem) {
        var etiquetaCorreios = String(valorEtiqueta || '').replace(/\D+/g, '').slice(0, 35);
        var grupoAberto = null;
        var pacotes = [];
        if (!etiquetaCorreios) {
            mostrarConfirmacao('Informe a etiqueta Correios antes de atribuir.', true);
            return false;
        }
        if (!garantirContextoMaloteAtual()) {
            mostrarConfirmacao('Nenhuma regional ativa foi identificada para a etiqueta Correios.', true);
            return false;
        }
        sincronizarValorDigitadoNaPrevia('correios_etiqueta', etiquetaCorreios);
        grupoAberto = obterGrupoCorreiosAbertoAtual();
        if (!grupoAberto || !grupoAberto.chips || !grupoAberto.chips.length) {
            mostrarConfirmacao('Não há malote Correios aberto neste contexto para receber a etiqueta.', true);
            return false;
        }

        for (var i = 0; i < grupoAberto.chips.length; i++) {
            var dadosGrupo = obterDadosChipOperacao(grupoAberto.chips[i]);
            var payload = montarPacoteParaPersistencia(grupoAberto.chips[i], {
                lacre_correios: dadosGrupo ? dadosGrupo.lacre_correios : grupoAberto.lacre_correios,
                grupo_correios: dadosGrupo ? dadosGrupo.grupo_correios : grupoAberto.grupo_correios,
                etiqueta_correios: etiquetaCorreios
            });
            if (payload) pacotes.push(payload);
        }
        if (!pacotes.length) {
            mostrarConfirmacao('Nenhum lote elegível foi encontrado para a etiqueta Correios.', true);
            return false;
        }

        persistirAtribuicoesLote(pacotes, function() {
            var agora = formatarDataHoraAtual();
            for (var j = 0; j < grupoAberto.chips.length; j++) {
                var dadosAtuais = obterDadosChipOperacao(grupoAberto.chips[j]);
                aplicarAtribuicaoNoChip(grupoAberto.chips[j], {
                    lacre_iipr: dadosAtuais ? dadosAtuais.lacre_iipr : '',
                    grupo_iipr: dadosAtuais ? dadosAtuais.grupo_iipr : '',
                    lacre_correios: dadosAtuais ? dadosAtuais.lacre_correios : grupoAberto.lacre_correios,
                    grupo_correios: dadosAtuais ? dadosAtuais.grupo_correios : grupoAberto.grupo_correios,
                    etiqueta_correios: etiquetaCorreios,
                    usuario_lacre: usuarioAtual,
                    atualizado_lacre: agora
                });
            }
            limparModoVoz('Etiqueta Correios atribuída' + (origem ? ' por ' + origem : '') + '.');
        });
        return true;
    }

    function processarCodigoDeComando(valorBruto) {
        var codigo = String(valorBruto || '').replace(/\D+/g, '');
        var comando = comandosCodigoBarras[codigo] || null;
        if (!comando) return false;
        if (comando.tipo === 'cancelar') {
            limparModoVoz(comando.mensagem);
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Comando:</strong> ' + comando.mensagem;
            }
            falarTexto('comando cancelado');
            return true;
        }
        definirModoVoz(comando.tipo, comando.mensagem);
        if (mensagemLeitura) {
            mensagemLeitura.innerHTML = '<strong>Comando:</strong> ' + comando.mensagem;
        }
        return true;
    }

    function processarValorDeComando(valorBruto) {
        var valor = String(valorBruto || '').replace(/\D+/g, '');
        if (!vozModoAtual || !valor) return false;
        return preencherCampoPorVoz(valor);
    }

    function renderizarPainelMalotes() {
        publicarResumoPrevia();
        publicarEstadoRemoto();
    }

    function abrirControleRemoto() {
        var url = 'conferencia_pacotes_controle.php?canal_controle=' + encodeURIComponent(controleCanal || 'principal');
        var ref = window.open(url, '_blank');
        if (ref) {
            if (statusControleRemoto) {
                statusControleRemoto.textContent = 'Controle remoto aberto no canal ' + (controleCanal || 'principal') + '.';
            }
        } else if (statusControleRemoto) {
            statusControleRemoto.textContent = 'O navegador bloqueou a abertura automática do controle remoto.';
        }
    }

    function marcarSplitNoContextoAtual(origem) {
        var contextoAtual;
        var chaveContexto;
        var proximoSegmento;
        if (!garantirContextoMaloteAtual()) {
            mostrarConfirmacao('Nenhum contexto ativo foi identificado para aplicar o split.', true);
            return false;
        }
        contextoAtual = montarContextoMalote(tipoContextoSelecionadoMalote, contextoSelecionadoMalote, rotuloContextoSelecionadoMalote);
        chaveContexto = obterChaveContextoMalote(contextoAtual);
        if (!chaveContexto) {
            mostrarConfirmacao('Nenhum contexto ativo foi identificado para aplicar o split.', true);
            return false;
        }
        proximoSegmento = obterSegmentoAtualContexto(contextoAtual) + 1;
        contextoSplitSequencia[chaveContexto] = proximoSegmento;
        limparModoVoz('Split preparado' + (origem ? ' por ' + origem : '') + '.');
        return true;
    }

    function processarComandoRemoto(cmd) {
        if (!cmd || !cmd.comando) return;
        // v1.2.7: CORRIGIDO - antes havia codigo PHP cru (getenv('DB_NAME') ?: 'controle')
        // dentro do JS, causando SyntaxError que matava o script inteiro e deixava
        // os botoes "Classificacao por chips" e "Modo tradicional" inoperantes.
        var origemRemota = <?php echo json_encode((string)(getenv('DB_NAME') ?: 'controle')); ?>;
        if (cmd.comando === 'marcar_split') {
            marcarSplitNoContextoAtual(origemRemota);
            return;
        }
        if (cmd.comando === 'atribuir_iipr') {
            aplicarLacreIiprNoContexto(cmd.valor || '', origemRemota);
            return;
        }
        if (cmd.comando === 'atribuir_correios') {
            aplicarLacreCorreiosNoContexto(cmd.valor || '', origemRemota);
            return;
        }
        if (cmd.comando === 'atribuir_display') {
            aplicarEtiquetaCorreiosNoContexto(cmd.valor_aux || '', origemRemota);
        }
    }

    function consultarComandosRemotos() {
        if (pollingRemotoAtivo) return;
        pollingRemotoAtivo = true;
        fetch(window.location.pathname + '?buscar_comandos_remoto_ajax=1&canal=' + encodeURIComponent(controleCanal || 'principal'), { cache: 'no-store' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                var comandos = data && data.comandos ? data.comandos : [];
                for (var i = 0; i < comandos.length; i++) {
                    processarComandoRemoto(comandos[i]);
                }
            })
            .catch(function() {})
            .finally(function() {
                pollingRemotoAtivo = false;
            });
    }

    function obterChipsMarcadosNoPainel() {
        var checks = painelMalotesLotes ? painelMalotesLotes.querySelectorAll('.check-malote-lote:checked') : [];
        var chips = [];
        for (var i = 0; i < checks.length; i++) {
            var codigo = checks[i].getAttribute('data-codigo') || '';
            var chip = codigo ? document.querySelector('.operacao-chip[data-codigo="' + codigo + '"]') : null;
            if (chip) chips.push(chip);
        }
        return chips;
    }

    function obterChipsConfirmadosSemIiprNoContexto() {
        var chips = obterChipsPorContextoMalote();
        var selecionados = [];
        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            if (!dados || !dados.conferido) continue;
            if (dados.lacre_iipr) continue;
            selecionados.push(chips[i]);
        }
        return selecionados;
    }

    function obterGruposIiprNoContexto() {
        var chips = obterChipsPorContextoMalote();
        var grupos = {};
        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            if (!dados || !dados.conferido || !dados.grupo_iipr) continue;
            if (!grupos[dados.grupo_iipr]) {
                grupos[dados.grupo_iipr] = {
                    grupo_iipr: dados.grupo_iipr,
                    lacre_iipr: dados.lacre_iipr || '',
                    grupo_correios: dados.grupo_correios || '',
                    lacre_correios: dados.lacre_correios || '',
                    etiqueta_correios: dados.etiqueta_correios || '',
                    chips: []
                };
            }
            grupos[dados.grupo_iipr].chips.push(chips[i]);
            if (!grupos[dados.grupo_iipr].grupo_correios && dados.grupo_correios) {
                grupos[dados.grupo_iipr].grupo_correios = dados.grupo_correios;
            }
            if (!grupos[dados.grupo_iipr].lacre_correios && dados.lacre_correios) {
                grupos[dados.grupo_iipr].lacre_correios = dados.lacre_correios;
            }
            if (!grupos[dados.grupo_iipr].etiqueta_correios && dados.etiqueta_correios) {
                grupos[dados.grupo_iipr].etiqueta_correios = dados.etiqueta_correios;
            }
        }
        return grupos;
    }

    function obterGruposIiprMarcadosNoPainel() {
        var checks = painelMalotesIipr ? painelMalotesIipr.querySelectorAll('.check-malote-iipr:checked') : [];
        var grupos = [];
        for (var i = 0; i < checks.length; i++) {
            var grupoIipr = checks[i].getAttribute('data-grupo-iipr') || '';
            if (grupoIipr) grupos.push(grupoIipr);
        }
        return grupos;
    }

    function obterGruposIiprSemCorreiosNoContexto() {
        var grupos = obterGruposIiprNoContexto();
        var lista = [];
        var menorSegmento = null;
        for (var chave in grupos) {
            if (!Object.prototype.hasOwnProperty.call(grupos, chave)) continue;
            if (grupos[chave].grupo_correios) continue;
            var segmento = extrairSegmentoSplitGrupo(grupos[chave].grupo_iipr || chave);
            if (menorSegmento === null || segmento < menorSegmento) {
                menorSegmento = segmento;
            }
            lista.push({ chave: chave, segmento: segmento });
        }
        if (menorSegmento === null) {
            return [];
        }
        var filtrados = [];
        for (var i = 0; i < lista.length; i++) {
            if (lista[i].segmento === menorSegmento) {
                filtrados.push(lista[i].chave);
            }
        }
        return filtrados;
    }

    function obterChipsPorGrupoIipr(gruposIipr) {
        var grupos = obterGruposIiprNoContexto();
        var chips = [];
        var vistos = {};
        for (var i = 0; i < gruposIipr.length; i++) {
            var grupo = grupos[gruposIipr[i]];
            if (!grupo || !grupo.chips) continue;
            for (var j = 0; j < grupo.chips.length; j++) {
                var codigo = grupo.chips[j].getAttribute('data-codigo') || '';
                if (codigo && !vistos[codigo]) {
                    vistos[codigo] = true;
                    chips.push(grupo.chips[j]);
                }
            }
        }
        return chips;
    }

    function obterGruposCorreiosAbertosNoContexto() {
        var chips = obterChipsPorContextoMalote();
        var grupos = {};
        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            if (!dados || !dados.conferido || !dados.grupo_correios) continue;
            if (!grupos[dados.grupo_correios]) {
                grupos[dados.grupo_correios] = {
                    grupo_correios: dados.grupo_correios,
                    lacre_correios: dados.lacre_correios || '',
                    etiqueta_correios: dados.etiqueta_correios || '',
                    chips: []
                };
            }
            grupos[dados.grupo_correios].chips.push(chips[i]);
            if (!grupos[dados.grupo_correios].etiqueta_correios && dados.etiqueta_correios) {
                grupos[dados.grupo_correios].etiqueta_correios = dados.etiqueta_correios;
            }
        }
        return grupos;
    }

    function obterGrupoCorreiosAbertoAtual() {
        var grupos = obterGruposCorreiosAbertosNoContexto();
        var encontrados = [];
        for (var chave in grupos) {
            if (!Object.prototype.hasOwnProperty.call(grupos, chave)) continue;
            if (grupos[chave].etiqueta_correios) continue;
            encontrados.push(grupos[chave]);
        }
        if (!encontrados.length) {
            return null;
        }
        encontrados.sort(function(a, b) {
            return extrairSegmentoSplitGrupo(a.grupo_correios || '') - extrairSegmentoSplitGrupo(b.grupo_correios || '');
        });
        return encontrados[0];
    }

    function obterResumoContextoAtual() {
        var chips = obterChipsPorContextoMalote();
        var confirmados = 0;
        var comIipr = 0;
        var comCorreios = 0;
        for (var i = 0; i < chips.length; i++) {
            var dados = obterDadosChipOperacao(chips[i]);
            if (!dados || !dados.conferido) continue;
            confirmados++;
            if (dados.lacre_iipr) comIipr++;
            if (dados.lacre_correios || dados.etiqueta_correios) comCorreios++;
        }
        return confirmados + ' confirmados • ' + comIipr + ' com IIPR • ' + comCorreios + ' com Correios';
    }

    function obterRotuloRemotoContextoAtual() {
        var postoCodigo = formatarCodigoComZeros(contextoSelecionadoMalote || '', 3);
        var chips = obterChipsPorContextoMalote();
        var dados = chips.length ? obterDadosChipOperacao(chips[0]) : null;
        var regionalCodigo = dados ? formatarCodigoComZeros(dados.regional_codigo || dados.regional, 3) : '';

        if (tipoContextoSelecionadoMalote === 'regional') {
            return rotuloContextoSelecionadoMalote || (contextoSelecionadoMalote ? ('Regional ' + formatarCodigoComZeros(contextoSelecionadoMalote, 3)) : '-');
        }

        if (!postoCodigo) {
            return rotuloContextoSelecionadoMalote || '-';
        }

        if (dados && dados.isPT) {
            return 'Posto ' + postoCodigo + ' Poupa Tempo';
        }
        if (regionalCodigo === '000') {
            return 'Posto ' + postoCodigo + ' Capital';
        }
        if (regionalCodigo === '999') {
            return 'Posto ' + postoCodigo + ' Central';
        }
        return rotuloContextoSelecionadoMalote || ('Posto ' + postoCodigo);
    }

    function publicarEstadoRemoto() {
        if (!controleCanal) return;
        var rotuloContextoRemoto = obterRotuloRemotoContextoAtual();
        var formData = new FormData();
        formData.append('atualizar_estado_remoto_ajax', '1');
        formData.append('canal', controleCanal);
        formData.append('usuario', usuarioAtual || '');
        formData.append('posto', tipoContextoSelecionadoMalote === 'posto' ? rotuloContextoRemoto : '');
        formData.append('regional', tipoContextoSelecionadoMalote === 'regional' ? rotuloContextoRemoto : '');
        formData.append('resumo', obterResumoContextoAtual());
        formData.append('lacre_iipr', ultimoLacreIiprAplicado || '');
        formData.append('lacre_correios', ultimoLacreCorreiosAplicado || '');
        formData.append('etiqueta_correios', ultimaEtiquetaCorreiosAplicada || '');
        fetch(window.location.href, { method: 'POST', body: formData }).catch(function() {});
    }

    function obterChipsDosIiprMarcados() {
        var checks = painelMalotesIipr ? painelMalotesIipr.querySelectorAll('.check-malote-iipr:checked') : [];
        var chips = [];
        var vistos = {};
        for (var i = 0; i < checks.length; i++) {
            var grupoIipr = checks[i].getAttribute('data-grupo-iipr') || '';
            if (!grupoIipr) continue;
            var chipsMesmoLacre = obterChipsPorContextoMalote();
            for (var j = 0; j < chipsMesmoLacre.length; j++) {
                if ((chipsMesmoLacre[j].getAttribute('data-grupo-iipr') || '') !== grupoIipr) continue;
                var codigo = chipsMesmoLacre[j].getAttribute('data-codigo') || '';
                if (!vistos[codigo]) {
                    vistos[codigo] = true;
                    chips.push(chipsMesmoLacre[j]);
                }
            }
        }
        return chips;
    }

    if (btnSalvarMaloteIipr) {
        btnSalvarMaloteIipr.addEventListener('click', function() {
            var lacreIipr = normalizarNumeroLacre(inputLacreIiprMalote ? inputLacreIiprMalote.value : '');
            var chips = obterChipsMarcadosNoPainel();
            if (!lacreIipr) {
                alert('Informe o lacre IIPR.');
                if (inputLacreIiprMalote) inputLacreIiprMalote.focus();
                return;
            }
            if (!chips.length) {
                chips = obterChipsConfirmadosSemIiprNoContexto();
            }
            if (!chips.length) {
                alert('Selecione os lotes que entraram no mesmo malote IIPR ou deixe o contexto todo conferido em verde para usar automaticamente os lotes ainda sem IIPR.');
                return;
            }

            var contextoAtual = montarContextoMalote(tipoContextoSelecionadoMalote, contextoSelecionadoMalote, rotuloContextoSelecionadoMalote);
            if (!contextoAtual.chave || !contextoAtual.tipo) {
                alert('Selecione uma regional ou posto antes de fechar o malote IIPR.');
                return;
            }
            var grupoIipr = 'GI_' + (contextoAtual.tipo === 'regional' ? ('R' + contextoAtual.chave) : ('P' + contextoAtual.chave)) + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

            var pacotes = [];
            for (var i = 0; i < chips.length; i++) {
                var contextoChip = obterContextoMaloteDeChip(chips[i]);
                if (contextoChip.tipo !== contextoAtual.tipo || contextoChip.chave !== contextoAtual.chave) {
                    continue;
                }
                var payload = montarPacoteParaPersistencia(chips[i], { lacre_iipr: lacreIipr });
                if (payload) payload.grupo_iipr = grupoIipr;
                if (payload) pacotes.push(payload);
            }
            if (!pacotes.length) {
                alert('Os lotes marcados não pertencem ao contexto atual do malote.');
                return;
            }
            persistirAtribuicoesLote(pacotes, function() {
                var agora = formatarDataHoraAtual();
                for (var j = 0; j < chips.length; j++) {
                    var contextoChipAtual = obterContextoMaloteDeChip(chips[j]);
                    if (contextoChipAtual.tipo !== contextoAtual.tipo || contextoChipAtual.chave !== contextoAtual.chave) {
                        continue;
                    }
                    var dadosAtuais = obterDadosChipOperacao(chips[j]);
                    aplicarAtribuicaoNoChip(chips[j], {
                        lacre_iipr: lacreIipr,
                        grupo_iipr: grupoIipr,
                        lacre_correios: dadosAtuais ? dadosAtuais.lacre_correios : '',
                        grupo_correios: dadosAtuais ? dadosAtuais.grupo_correios : '',
                        etiqueta_correios: dadosAtuais ? dadosAtuais.etiqueta_correios : '',
                        usuario_lacre: usuarioAtual,
                        atualizado_lacre: agora
                    });
                }
                if (inputLacreIiprMalote) inputLacreIiprMalote.value = '';
                limparModoVoz('Lacre IIPR salvo.');
            });
        });
    }

    if (btnSalvarMaloteCorreios) {
        btnSalvarMaloteCorreios.addEventListener('click', function() {
            var lacreCorreios = normalizarNumeroLacre(inputLacreCorreiosMalote ? inputLacreCorreiosMalote.value : '');
            var gruposMarcados = obterGruposIiprMarcadosNoPainel();
            if (!gruposMarcados.length) {
                gruposMarcados = obterGruposIiprSemCorreiosNoContexto();
            }
            if (!gruposMarcados.length) {
                alert('Selecione um ou mais malotes IIPR já fechados ou deixe o contexto com malotes IIPR ainda sem Correios para seleção automática.');
                return;
            }
            if (!lacreCorreios) {
                alert('Informe o lacre Correios antes de atribuir.');
                if (inputLacreCorreiosMalote) inputLacreCorreiosMalote.focus();
                return;
            }

            var grupoCorreios = 'GC_' + (tipoContextoSelecionadoMalote === 'regional' ? ('R' + contextoSelecionadoMalote) : ('P' + contextoSelecionadoMalote)) + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
            var chips = obterChipsPorGrupoIipr(gruposMarcados);

            var pacotes = [];
            for (var i = 0; i < chips.length; i++) {
                var payload = montarPacoteParaPersistencia(chips[i], {
                    lacre_correios: lacreCorreios,
                    grupo_correios: grupoCorreios,
                    etiqueta_correios: ''
                });
                if (payload) pacotes.push(payload);
            }
            persistirAtribuicoesLote(pacotes, function() {
                var agora = formatarDataHoraAtual();
                for (var j = 0; j < chips.length; j++) {
                    var dadosAtuais = obterDadosChipOperacao(chips[j]);
                    aplicarAtribuicaoNoChip(chips[j], {
                        lacre_iipr: dadosAtuais ? dadosAtuais.lacre_iipr : '',
                        grupo_iipr: dadosAtuais ? dadosAtuais.grupo_iipr : '',
                        lacre_correios: lacreCorreios,
                        grupo_correios: grupoCorreios,
                        etiqueta_correios: dadosAtuais ? dadosAtuais.etiqueta_correios : '',
                        usuario_lacre: usuarioAtual,
                        atualizado_lacre: agora
                    });
                }
                if (inputLacreCorreiosMalote) inputLacreCorreiosMalote.value = '';
                limparModoVoz('Lacre Correios atribuído.');
            });
        });
    }

    if (btnSalvarEtiquetaCorreios) {
        btnSalvarEtiquetaCorreios.addEventListener('click', function() {
            var etiquetaCorreios = inputEtiquetaCorreiosMalote ? String(inputEtiquetaCorreiosMalote.value || '').trim() : '';
            if (!etiquetaCorreios) {
                alert('Leia ou digite a etiqueta Correios antes de atribuir o display.');
                if (inputEtiquetaCorreiosMalote) inputEtiquetaCorreiosMalote.focus();
                return;
            }

            var grupoAberto = obterGrupoCorreiosAbertoAtual();
            if (!grupoAberto || !grupoAberto.chips || !grupoAberto.chips.length) {
                alert('Não há malote Correios aberto neste contexto para receber o display.');
                return;
            }

            var pacotes = [];
            for (var i = 0; i < grupoAberto.chips.length; i++) {
                var dadosGrupo = obterDadosChipOperacao(grupoAberto.chips[i]);
                var payload = montarPacoteParaPersistencia(grupoAberto.chips[i], {
                    lacre_correios: dadosGrupo ? dadosGrupo.lacre_correios : grupoAberto.lacre_correios,
                    grupo_correios: dadosGrupo ? dadosGrupo.grupo_correios : grupoAberto.grupo_correios,
                    etiqueta_correios: etiquetaCorreios
                });
                if (payload) pacotes.push(payload);
            }

            persistirAtribuicoesLote(pacotes, function() {
                var agora = formatarDataHoraAtual();
                for (var j = 0; j < grupoAberto.chips.length; j++) {
                    var dadosAtuais = obterDadosChipOperacao(grupoAberto.chips[j]);
                    aplicarAtribuicaoNoChip(grupoAberto.chips[j], {
                        lacre_iipr: dadosAtuais ? dadosAtuais.lacre_iipr : '',
                        grupo_iipr: dadosAtuais ? dadosAtuais.grupo_iipr : '',
                        lacre_correios: dadosAtuais ? dadosAtuais.lacre_correios : grupoAberto.lacre_correios,
                        grupo_correios: dadosAtuais ? dadosAtuais.grupo_correios : grupoAberto.grupo_correios,
                        etiqueta_correios: etiquetaCorreios,
                        usuario_lacre: usuarioAtual,
                        atualizado_lacre: agora
                    });
                }
                if (inputEtiquetaCorreiosMalote) inputEtiquetaCorreiosMalote.value = '';
                limparModoVoz('Display Correios atribuído.');
            });
        });
    }

    if (btnLimparMaloteLote) {
        btnLimparMaloteLote.addEventListener('click', function() {
            var chips = obterChipsMarcadosNoPainel();
            if (!chips.length) {
                alert('Selecione os lotes que terão os vínculos apagados.');
                return;
            }
            var pacotes = [];
            for (var i = 0; i < chips.length; i++) {
                var payload = montarPacoteParaPersistencia(chips[i], {
                    lacre_iipr: '',
                    grupo_iipr: '',
                    lacre_correios: '',
                    grupo_correios: '',
                    etiqueta_correios: ''
                });
                if (payload) pacotes.push(payload);
            }
            persistirAtribuicoesLote(pacotes, function() {
                for (var j = 0; j < chips.length; j++) {
                    aplicarAtribuicaoNoChip(chips[j], {
                        lacre_iipr: '',
                        grupo_iipr: '',
                        lacre_correios: '',
                        grupo_correios: '',
                        etiqueta_correios: '',
                        usuario_lacre: '',
                        atualizado_lacre: ''
                    });
                }
                limparModoVoz('Vínculos dos lotes removidos.');
            });
        });
    }

    if (btnAlternarVozMalotes) {
        btnAlternarVozMalotes.addEventListener('click', function() {
            alternarReconhecimentoVoz();
        });
    }

    if (btnAbrirPreviaMalotes) {
        btnAbrirPreviaMalotes.addEventListener('click', function() {
            abrirPreviaMalotes();
        });
    }

    if (btnAbrirControleRemoto) {
        btnAbrirControleRemoto.addEventListener('click', function() {
            abrirControleRemoto();
        });
    }

    if (modalChipDetalhe) {
        modalChipDetalhe.addEventListener('click', function(e) {
            if (e.target === modalChipDetalhe) {
                modalChipDetalhe.style.display = 'none';
            }
        });
    }

    document.addEventListener('click', function(e) {
        var chip = e.target && e.target.closest ? e.target.closest('.operacao-chip') : null;
        if (chip) {
            selecionarContextoMalote(obterContextoMaloteDeChip(chip));
            destacarChipOperacao(chip.getAttribute('data-codigo') || '');
            abrirModalChipDetalhe(chip);
            return;
        }
        var linhaTabela = e.target && e.target.closest ? e.target.closest('tr.linha-conferencia') : null;
        if (linhaTabela) {
            selecionarContextoMalote(obterContextoMaloteDeRegistroTabela(linhaTabela));
            destacarChipOperacao(linhaTabela.getAttribute('data-codigo') || '');
            return;
        }
        var linhaPosto = e.target && e.target.closest ? e.target.closest('.operacao-posto-row') : null;
        if (linhaPosto) {
            selecionarContextoMalote(obterContextoMaloteDeLinha(linhaPosto));
        }
    });

    function entradaBloqueiaScanner(alvo) {
        if (!alvo) return false;
        if (alvo === input) return false;
        if (alvo.id === 'usuario_conf_modal' || alvo.id === 'pacote_lote' || alvo.id === 'pacote_regional' || alvo.id === 'pacote_posto' || alvo.id === 'pacote_qtd' || alvo.id === 'pacote_dataexp' || alvo.id === 'pacote_responsavel') {
            return true;
        }
        if (alvo.id === 'inputLacreIiprMalote' || alvo.id === 'inputLacreCorreiosMalote' || alvo.id === 'inputEtiquetaCorreiosMalote') {
            return true;
        }
        var tag = String(alvo.tagName || '').toUpperCase();
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }
        return !!alvo.isContentEditable;
    }

    function alternarModoVisualizacao(modo) {
        var abrirClassificacao = modo === 'classificacao';
        var abrirTradicional = modo === 'tradicional';
        if (secaoClassificacao) {
            secaoClassificacao.classList.toggle('oculta', !abrirClassificacao);
        }
        if (secaoTradicional) {
            secaoTradicional.classList.toggle('oculta', !abrirTradicional);
        }
        if (btnMostrarClassificacao) {
            btnMostrarClassificacao.classList.toggle('ativo', abrirClassificacao);
            btnMostrarClassificacao.setAttribute('aria-expanded', abrirClassificacao ? 'true' : 'false');
        }
        if (btnMostrarTradicional) {
            btnMostrarTradicional.classList.toggle('ativo', abrirTradicional);
            btnMostrarTradicional.setAttribute('aria-expanded', abrirTradicional ? 'true' : 'false');
        }
    }

    function nomeResponsavelValido(nome) {
        var texto = String(nome || '').trim();
        if (!texto) return false;
        var invalido = texto.toLowerCase();
        return invalido !== 'teste' && invalido !== 'não informado' && invalido !== 'nao informado';
    }

    var _centralizarAlvo = null;
    var _centralizarRAF = null;
    function _executarCentralizar() {
        _centralizarRAF = null;
        var alvo = _centralizarAlvo;
        _centralizarAlvo = null;
        if (!alvo || alvo.offsetParent === null) return;
        var rect = alvo.getBoundingClientRect();
        var topo = rect.top + window.pageYOffset - (window.innerHeight / 2) + (rect.height / 2);
        try {
            window.scrollTo({ top: Math.max(0, topo), behavior: 'smooth' });
        } catch (e) {
            window.scrollTo(0, Math.max(0, topo));
        }
    }
    // v2.0.4: centralizacao robusta — agenda UMA rolagem por requestAnimationFrame
    // (deixa o layout assentar e supera qualquer agendamento anterior), evitando
    // scrolls concorrentes que faziam a tela "travar e pular". Retorna false se o
    // alvo nao esta visivel (para o chamador cair na linha como alvo alternativo).
    function centralizarElemento(elemento) {
        if (!elemento || elemento.offsetParent === null) return false;
        _centralizarAlvo = elemento;
        if (_centralizarRAF && window.cancelAnimationFrame) {
            cancelAnimationFrame(_centralizarRAF);
            _centralizarRAF = null;
        }
        if (window.requestAnimationFrame) {
            _centralizarRAF = requestAnimationFrame(function() {
                _centralizarRAF = requestAnimationFrame(_executarCentralizar);
            });
        } else {
            _centralizarRAF = null;
            setTimeout(_executarCentralizar, 30);
        }
        return true;
    }

    function correspondeTipoVisual(tipo, isPt) {
        if (tipo === 'todos') return true;
        if (tipo === 'poupatempo') return !!isPt;
        return !isPt;
    }

    function aplicarFiltroTipoVisual(tipo) {
        var tipoAtualVisual = tipo || obterTipoInicioSelecionado();
        var linhas = document.querySelectorAll('#tabelas tbody tr');
        for (var i = 0; i < linhas.length; i++) {
            var linha = linhas[i];
            var isPt = linha.getAttribute('data-ispt') === '1';
            linha.style.display = correspondeTipoVisual(tipoAtualVisual, isPt) ? '' : 'none';
        }

        var tabelas = document.querySelectorAll('#tabelas table[data-view]');
        for (var j = 0; j < tabelas.length; j++) {
            var tabela = tabelas[j];
            var visiveis = tabela.querySelectorAll('tbody tr:not([style*="display: none"])').length;
            var exibirTabela = visiveis > 0;
            tabela.style.display = exibirTabela ? '' : 'none';
            var titulo = obterTituloTabela(tabela);
            if (titulo) titulo.style.display = exibirTabela ? '' : 'none';
            var grupoTradicional = tabela.closest ? tabela.closest('.grupo-tradicional') : null;
            if (grupoTradicional) {
                grupoTradicional.style.display = exibirTabela ? '' : 'none';
            }
        }

        var blocosCorreios = document.querySelectorAll('.grupo-capital-wrapper[data-view], .grupo-central-wrapper[data-view]');
        for (var k = 0; k < blocosCorreios.length; k++) {
            var bloco = blocosCorreios[k];
            var temTabelaVisivel = bloco.querySelector('table[data-view]:not([style*="display: none"])');
            bloco.style.display = temTabelaVisivel ? '' : 'none';
        }

        var banners = document.querySelectorAll('.banner-grupo[data-view]');
        for (var b = 0; b < banners.length; b++) {
            var banner = banners[b];
            var view = banner.getAttribute('data-view') || 'todos';
            banner.style.display = (tipoAtualVisual === 'todos' || view === tipoAtualVisual) ? '' : 'none';
        }

        var gruposOperacao = document.querySelectorAll('.operacao-grupo[data-view]');
        for (var g = 0; g < gruposOperacao.length; g++) {
            var grupo = gruposOperacao[g];
            var groupView = grupo.getAttribute('data-view') || 'todos';
            grupo.style.display = (tipoAtualVisual === 'todos' || groupView === tipoAtualVisual) ? '' : 'none';
        }

        atualizarResumoTodasTabelas();
        sincronizarPainelOperacao();
    }

    if (btnMostrarClassificacao) {
        btnMostrarClassificacao.addEventListener('click', function() {
            var aberto = secaoClassificacao && !secaoClassificacao.classList.contains('oculta');
            alternarModoVisualizacao(aberto ? '' : 'classificacao');
        });
    }

    if (btnMostrarTradicional) {
        btnMostrarTradicional.addEventListener('click', function() {
            var aberto = secaoTradicional && !secaoTradicional.classList.contains('oculta');
            alternarModoVisualizacao(aberto ? '' : 'tradicional');
            definirRecolhimentoTradicional(tradicionalRecolhido);
        });
    }

    if (btnToggleTodosChips) {
        btnToggleTodosChips.addEventListener('click', function() {
            definirRecolhimentoChips(!chipsRecolhidos);
        });
    }

    if (btnToggleTodosTradicional) {
        btnToggleTodosTradicional.addEventListener('click', function() {
            definirRecolhimentoTradicional(!tradicionalRecolhido);
        });
    }

    if (btnToggleHistoricoLeitura) {
        btnToggleHistoricoLeitura.addEventListener('click', function() {
            var aberto = !painelHistoricoLeitura || !painelHistoricoLeitura.classList.contains('aberto');
            definirPainelHistoricoLeitura(aberto);
        });
    }

    definirPainelHistoricoLeitura(false);
    try {
        if (localStorage.getItem(storageHistoricoLeituraKey) === '1') {
            definirPainelHistoricoLeitura(true);
        }
    } catch (eHistorico) {}
    definirRecolhimentoChips(chipsRecolhidos);
    definirRecolhimentoTradicional(tradicionalRecolhido);
    solicitarWakeLockTela();
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            solicitarWakeLockTela();
        }
    });
    window.addEventListener('focus', solicitarWakeLockTela);
    document.addEventListener('keydown', solicitarWakeLockTela, true);
    document.addEventListener('pointerdown', solicitarWakeLockTela, true);

    function obterTituloTabela(tabela) {
        if (!tabela) return null;
        var grupo = tabela.closest ? tabela.closest('.grupo-tradicional') : null;
        if (grupo) {
            return grupo.querySelector('.grupo-tradicional-titulo');
        }
        var titulo = tabela.previousElementSibling;
        while (titulo && titulo.tagName !== 'H3') {
            titulo = titulo.previousElementSibling;
        }
        return titulo;
    }

    function atualizarResumoTabela(tabela) {
        if (!tabela) return;
        var tbody = tabela.tBodies && tabela.tBodies[0] ? tabela.tBodies[0] : null;
        if (!tbody) return;
        var linhas = tbody.rows;
        var total = 0;
        var conferidos = 0;
        for (var i = 0; i < linhas.length; i++) {
            if (linhas[i].style.display === 'none') {
                continue;
            }
            total++;
            if (linhas[i].classList.contains('confirmado')) {
                conferidos++;
            }
        }
        var titulo = obterTituloTabela(tabela);
        if (!titulo) return;
        var span = titulo.querySelector('.contagem-pacotes');
        if (!span) return;
        span.textContent = '(' + total + ' pacotes / ' + conferidos + ' conferidos / ' + Math.max(0, total - conferidos) + ' pendentes)';
        span.setAttribute('data-total', String(total));
        span.setAttribute('data-conferidos', String(conferidos));
        sincronizarPainelOperacao();
    }

    function atualizarResumoTodasTabelas() {
        var tabelas = document.querySelectorAll('#tabelas table');
        for (var i = 0; i < tabelas.length; i++) {
            atualizarResumoTabela(tabelas[i]);
        }
    }

    function atualizarLinhaOperacao(row) {
        if (!row) return;
        var chips = row.querySelectorAll('.operacao-chip');
        var total = chips.length;
        var conferidos = 0;
        for (var i = 0; i < chips.length; i++) {
            if (chips[i].getAttribute('data-conf') === '1') {
                conferidos++;
            }
        }
        var pacotes = row.querySelector('[data-role="pacotes"]');
        var conferidosEl = row.querySelector('[data-role="conferidos"]');
        var pendentes = row.querySelector('[data-role="pendentes"]');
        if (pacotes) pacotes.textContent = String(total);
        if (conferidosEl) conferidosEl.textContent = String(conferidos);
        if (pendentes) pendentes.textContent = String(Math.max(0, total - conferidos));
    }

    function atualizarChipOperacaoPorCodigo(codigo, confirmado) {
        if (!codigo) return null;
        var chip = document.querySelector('.operacao-chip[data-codigo="' + codigo + '"]');
        if (!chip) return null;
        chip.setAttribute('data-conf', confirmado ? '1' : '0');
        if (confirmado) {
            chip.classList.add('confirmado');
        } else {
            chip.classList.remove('confirmado');
        }
        atualizarLinhaOperacao(chip.closest('.operacao-posto-row'));
        return chip;
    }

    function destacarChipOperacao(codigo) {
        var chipsAtivos = document.querySelectorAll('.operacao-chip.ativo');
        for (var i = 0; i < chipsAtivos.length; i++) {
            chipsAtivos[i].classList.remove('ativo');
        }
        var linhasAtivas = document.querySelectorAll('.operacao-posto-row.ativo');
        for (var j = 0; j < linhasAtivas.length; j++) {
            linhasAtivas[j].classList.remove('ativo');
        }
        if (!codigo) return false;
        var chip = document.querySelector('.operacao-chip[data-codigo="' + codigo + '"]');
        if (!chip) return false;
        chip.classList.add('ativo');
        selecionarContextoMalote(obterContextoMaloteDeChip(chip));
        var linha = chip.closest('.operacao-posto-row');
        var grupo = chip.closest('.operacao-grupo[data-view]');
        if (chipsRecolhidos && grupo) {
            aplicarFocoGrupoOperacao(grupo);
        }
        if (linha) {
            linha.classList.add('ativo');
        }
        // Centraliza SEMPRE no chip do lote lido (e nao na linha do posto, que e
        // compartilhada por todos os lotes do posto) para que a tela acompanhe
        // cada leitura, e nao apenas a primeira de cada posto/regional.
        // Retorna se conseguiu centralizar (chip visivel) para o chamador decidir
        // se precisa cair na linha como alvo alternativo.
        return centralizarElemento(chip);
    }

    function sincronizarPainelOperacao() {
        var chips = document.querySelectorAll('.operacao-chip');
        for (var i = 0; i < chips.length; i++) {
            var chip = chips[i];
            var codigo = chip.getAttribute('data-codigo') || '';
            var linhaTabela = codigo ? document.querySelector('tr[data-codigo="' + codigo + '"]') : null;
            var confirmado = !!(linhaTabela && linhaTabela.classList.contains('confirmado'));
            chip.setAttribute('data-conf', confirmado ? '1' : '0');
            if (confirmado) {
                chip.classList.add('confirmado');
            } else {
                chip.classList.remove('confirmado');
            }
            atualizarLinhaOperacao(chip.closest('.operacao-posto-row'));
        }
        renderizarPainelMalotes();
    }

    function abrirModalChipDetalhe(chip) {
        if (!chip || !modalChipDetalhe || !tabelaDetalheChip) return;
        var itens = [
            ['Lote', chip.getAttribute('data-lote') || ''],
            ['Posto', chip.getAttribute('data-posto') || ''],
            ['Regional', chip.getAttribute('data-regional') || ''],
            ['Quantidade', chip.getAttribute('data-qtd') || ''],
            ['Data', formatarDataExibicao(chip.getAttribute('data-data') || '')],
            ['Responsável produção', chip.getAttribute('data-usuario') || ''],
            ['Lacre IIPR', chip.getAttribute('data-lacre-iipr') || 'Pendente'],
            ['Grupo IIPR', chip.getAttribute('data-grupo-iipr') || 'Pendente'],
            ['Lacre Correios', chip.getAttribute('data-lacre-correios') || 'Pendente'],
            ['Grupo Correios', chip.getAttribute('data-grupo-correios') || 'Pendente'],
            ['Etiqueta Correios', chip.getAttribute('data-etiqueta-correios') || 'Pendente'],
            ['Código de barras', chip.getAttribute('data-codigo') || ''],
            ['Conferido em', formatarDataExibicao(chip.getAttribute('data-conferido-em') || '') || 'Pendente']
        ];
        tabelaDetalheChip.innerHTML = '';
        for (var i = 0; i < itens.length; i++) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<th style="text-align:left; width:170px;">' + itens[i][0] + '</th><td>' + itens[i][1] + '</td>';
            tabelaDetalheChip.appendChild(tr);
        }
        modalChipDetalhe.style.display = 'flex';
    }

    function normalizarDataOrdem(valor) {
        if (!valor) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) return valor;
        if (/^\d{2}-\d{2}-\d{4}$/.test(valor)) {
            var p = valor.split('-');
            return p[2] + '-' + p[1] + '-' + p[0];
        }
        return valor;
    }

    function formatarDataExibicao(valor) {
        if (!valor) return '';
        var texto = String(valor).trim();
        var m = texto.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/);
        if (m) {
            var base = m[3] + '-' + m[2] + '-' + m[1];
            if (m[4] && m[5]) {
                return base + ' ' + m[4] + ':' + m[5];
            }
            return base;
        }
        return texto;
    }

    function formatarDataHoraAtual() {
        var d = new Date();
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var yy = d.getFullYear();
        var hh = String(d.getHours()).padStart(2, '0');
        var mi = String(d.getMinutes()).padStart(2, '0');
        return dd + '-' + mm + '-' + yy + ' ' + hh + ':' + mi;
    }

    function obterValorOrdenacao(linha, chave) {
        if (!linha) return '';
        if (chave === 'lote') {
            return linha.getAttribute('data-lote') || '';
        }
        if (chave === 'data') {
            var ds = linha.getAttribute('data-data-sql') || linha.getAttribute('data-data') || '';
            return normalizarDataOrdem(ds);
        }
        return '';
    }

    function ordenarTabela(tabela, chave, asc) {
        if (!tabela) return;
        var tbody = tabela.tBodies && tabela.tBodies[0] ? tabela.tBodies[0] : null;
        if (!tbody) return;
        var linhas = Array.prototype.slice.call(tbody.rows);
        linhas.sort(function(a, b) {
            var va = obterValorOrdenacao(a, chave);
            var vb = obterValorOrdenacao(b, chave);
            if (va < vb) return asc ? -1 : 1;
            if (va > vb) return asc ? 1 : -1;
            return 0;
        });
        for (var i = 0; i < linhas.length; i++) {
            tbody.appendChild(linhas[i]);
        }
    }

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

    function tocarBeepLeitura() {
        if (!beep) return;
        if (muteBeep && muteBeep.checked) return;
        try {
            beep.pause();
        } catch (e1) {}
        try {
            beep.currentTime = 0;
            var playPromise = beep.play();
            if (playPromise && playPromise.catch) {
                playPromise.catch(function() {
                    try {
                        var beepClone = beep.cloneNode(true);
                        beepClone.play().catch(function() {
                            enfileirarSom(beep);
                        });
                    } catch (e2) {
                        enfileirarSom(beep);
                    }
                });
            }
        } catch (e3) {
            try {
                var beepFallback = beep.cloneNode(true);
                beepFallback.play().catch(function() {
                    enfileirarSom(beep);
                });
            } catch (e4) {
                enfileirarSom(beep);
            }
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

    function avisoScannerFoiEmitido(chave) {
        var agora = Date.now();
        var chaveAtual = String(chave || '');
        if (ultimoAvisoScanner.chave === chaveAtual && (agora - ultimoAvisoScanner.quando) < 900) {
            return true;
        }
        ultimoAvisoScanner = { chave: chaveAtual, quando: agora };
        return false;
    }

    function avisarSomOuFala(chave, audio, textoFallback) {
        if (avisoScannerFoiEmitido(chave)) return;
        if (audio) {
            enfileirarSom(audio);
            return;
        }
        falarTexto(textoFallback);
    }

    function definirPainelHistoricoLeitura(aberto) {
        if (!painelHistoricoLeitura || !btnToggleHistoricoLeitura) return;
        painelHistoricoLeitura.classList.toggle('aberto', !!aberto);
        btnToggleHistoricoLeitura.textContent = aberto ? '-' : '+';
        btnToggleHistoricoLeitura.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        try {
            localStorage.setItem(storageHistoricoLeituraKey, aberto ? '1' : '0');
        } catch (e) {}
    }

    function registrarHistoricoLeitura(status, detalhe, codigo) {
        if (!listaHistoricoLeitura) return;
        var vazio = listaHistoricoLeitura.querySelector('.historico-leitura-vazio');
        if (vazio && vazio.parentNode) {
            vazio.parentNode.removeChild(vazio);
        }
        var item = document.createElement('li');
        item.className = 'historico-leitura-item';
        var horario = formatarDataHoraAtual();
        var html = '<strong>' + escapeHtml(status || 'Leitura') + '</strong>';
        if (detalhe) {
            html += '<div>' + escapeHtml(detalhe) + '</div>';
        }
        html += '<div class="historico-leitura-meta"><span>' + escapeHtml(horario) + '</span>';
        if (codigo) {
            html += '<span>Código ' + escapeHtml(String(codigo)) + '</span>';
        }
        html += '</div>';
        item.innerHTML = html;
        listaHistoricoLeitura.insertBefore(item, listaHistoricoLeitura.firstChild);
        while (listaHistoricoLeitura.children.length > 8) {
            listaHistoricoLeitura.removeChild(listaHistoricoLeitura.lastChild);
        }
    }

    function definirRecolhimentoChips(recolhido) {
        chipsRecolhidos = !!recolhido;
        aplicarFocoGrupoOperacao(null);
        if (btnToggleTodosChips) {
            btnToggleTodosChips.textContent = chipsRecolhidos ? 'Expandir chips' : 'Recolher chips';
            btnToggleTodosChips.setAttribute('aria-expanded', chipsRecolhidos ? 'false' : 'true');
        }
        try { localStorage.setItem(storageChipsRecolhidosKey, chipsRecolhidos ? '1' : '0'); } catch (e) {}
    }

    function aplicarFocoGrupoOperacao(grupoAtivo) {
        var grupos = document.querySelectorAll('.operacao-grupo[data-view]');
        for (var i = 0; i < grupos.length; i++) {
            if (!chipsRecolhidos) {
                grupos[i].classList.remove('recolhido');
                continue;
            }
            if (grupoAtivo && grupos[i] === grupoAtivo) {
                grupos[i].classList.remove('recolhido');
            } else {
                grupos[i].classList.add('recolhido');
            }
        }
    }

    function definirRecolhimentoTradicional(recolhido) {
        tradicionalRecolhido = !!recolhido;
        var grupos = document.querySelectorAll('.grupo-tradicional[data-group-id]');
        for (var i = 0; i < grupos.length; i++) {
            grupos[i].classList.toggle('recolhido', tradicionalRecolhido);
        }
        if (btnToggleTodosTradicional) {
            btnToggleTodosTradicional.textContent = tradicionalRecolhido ? 'Expandir regionais' : 'Recolher regionais';
            btnToggleTodosTradicional.setAttribute('aria-expanded', tradicionalRecolhido ? 'false' : 'true');
        }
        try { localStorage.setItem(storageTradicionalRecolhidoKey, tradicionalRecolhido ? '1' : '0'); } catch (e) {}
    }

    function solicitarWakeLockTela() {
        if (!('wakeLock' in navigator)) return;
        if (document.visibilityState !== 'visible') return;
        if (wakeLockSentinel) return;
        navigator.wakeLock.request('screen').then(function(sentinel) {
            wakeLockSentinel = sentinel;
            sentinel.addEventListener('release', function() {
                wakeLockSentinel = null;
            });
        }).catch(function() {});
    }

    function tocarPacoteNaoEncontrado() {
        avisarSomOuFala('pacote_nao_encontrado', pacoteNaoEncontradoAudio, 'pacote não encontrado');
    }

    function avisarIncompatibilidadeTipo(tipoPacote) {
        if (tipoPacote === 'correios') {
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Posto dos Correios:</strong> altere o tipo para Correios ou Todos.';
            }
            avisarSomOuFala('incompatibilidade_correios', pertenceCorreios, 'posto dos correios');
            return;
        }
        if (mensagemLeitura) {
            mensagemLeitura.innerHTML = '<strong>Posto do Poupa Tempo:</strong> altere o tipo para Poupa Tempo ou Todos.';
        }
        avisarSomOuFala('incompatibilidade_poupatempo', postoPoupaTempo, 'posto do poupa tempo');
    }

    // Encadeia para tocar o próximo som quando o atual terminar
    var listaSons = [];
    if (beep) listaSons.push(beep);
    if (concluido) listaSons.push(concluido);
    if (pacoteJaConferido) listaSons.push(pacoteJaConferido);
    if (pacoteOutraRegional) listaSons.push(pacoteOutraRegional);
    if (postoPoupaTempo) listaSons.push(postoPoupaTempo);
    if (pertenceCorreios) listaSons.push(pertenceCorreios);
    if (pacoteNaoEncontradoAudio) listaSons.push(pacoteNaoEncontradoAudio);
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

    if (input) {
        input.addEventListener('focus', desbloquearAudio);
        input.addEventListener('click', desbloquearAudio);
    }
    document.addEventListener('keydown', desbloquearAudio, { once: true });
    
    // v9.23.2: Variáveis de contexto para sons inteligentes
    var regionalAtual = null;
    var postoAtual = null;
    var tipoAtual = null; // 'poupatempo' ou 'correios'
    var primeiroConferido = false;
    var ultimaRegionalLida = null;
    var ultimoPostoLido = null;
    var ultimoTipoLido = null;
    // T5b: troca de regional por beep repetido. Conta quantas vezes seguidas o MESMO
    // codigo de barras foi bloqueado por "outra regional"; no 4o beep, troca a conferencia
    // para a regional/posto desse lote.
    var trocaRegionalCodigo = '';
    var trocaRegionalContador = 0;

    function contextoCorreiosExigeMesmoPosto(regionalNorm) {
        return regionalNorm === '000' || regionalNorm === '999';
    }

    function obterTipoInicioSelecionado() {
        var radios = document.querySelectorAll('input[name="tipo_inicio"]');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return 'correios';
    }

    if (radioAutoSalvar) {
        radioAutoSalvar.checked = true;
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
        regionalAtual = null;
        postoAtual = null;
        tipoAtual = null;
        primeiroConferido = false;
        ultimaRegionalLida = null;
        ultimoPostoLido = null;
        ultimoTipoLido = null;
        try {
            sessionStorage.setItem(storageTipoKey, tipo);
        } catch (e) {}
        aplicarFiltroTipoVisual(tipo);
        if (input) input.focus();
    }

    function abrirModalPacote(codigo, idx) {
        if (!modalPacote) return;
        var cod = codigo || '';
        var partesCodigo = extrairPartesCodigo(cod);
        if (pacoteIdx) pacoteIdx.value = (typeof idx === 'number') ? String(idx) : '';
        if (pacoteCodbar) pacoteCodbar.value = cod;
        if (partesCodigo && (typeof idx !== 'number')) {
            if (pacoteLote) pacoteLote.value = partesCodigo.lote;
            if (pacoteRegional) pacoteRegional.value = partesCodigo.regional;
            if (pacotePosto) pacotePosto.value = partesCodigo.posto;
            if (pacoteQtd) pacoteQtd.value = partesCodigo.quantidade || '';
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

    function formatarDateTimeLocal(data) {
        var ano = data.getFullYear();
        var mes = String(data.getMonth() + 1).padStart(2, '0');
        var dia = String(data.getDate()).padStart(2, '0');
        var hora = String(data.getHours()).padStart(2, '0');
        var minuto = String(data.getMinutes()).padStart(2, '0');
        return ano + '-' + mes + '-' + dia + 'T' + hora + ':' + minuto;
    }

    function atualizarOpcoesSalvamentoPendentes() {
        if (autorSalvamentoPacotes && !autorSalvamentoPacotes.value && usuarioAtual) {
            autorSalvamentoPacotes.value = usuarioAtual;
        }
        if (criadoSalvamentoPacotes && !criadoSalvamentoPacotes.value) {
            criadoSalvamentoPacotes.value = formatarDateTimeLocal(new Date());
        }
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
                '<td>' + formatarDataExibicao(p.dataexp) + '</td>' +
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
        if (resumoPacotesPendentes) {
            resumoPacotesPendentes.textContent = pacotesPendentes.length
                ? (pacotesPendentes.length + ' pacote(s) aguardando carga em ciPostos e ciPostosCsv.')
                : 'Pacotes aguardando carga em ciPostos e ciPostosCsv.';
        }
        if (pacotesPendentes.length) {
            atualizarOpcoesSalvamentoPendentes();
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
        if (mensagemLeitura) {
            mensagemLeitura.innerHTML = '<strong>Pacote não encontrado:</strong> adicionado à lista pendente.';
        }
        return true;
    }

    window.adicionarPacotePendente = adicionarPacotePendente;

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

    if (btnAdicionarPacote) {
        btnAdicionarPacote.addEventListener('click', function() {
            var obj = {
                codbar: pacoteCodbar ? normalizarCodigoLeitura(pacoteCodbar.value.trim()) : '',
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
            if (autorSalvamentoPacotes && usuarioAtual) {
                autorSalvamentoPacotes.value = usuarioAtual;
            }
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
            atualizarOpcoesSalvamentoPendentes();
            var autorSalvar = autorSalvamentoPacotes ? autorSalvamentoPacotes.value.trim() : '';
            var criadoSalvar = criadoSalvamentoPacotes ? criadoSalvamentoPacotes.value.trim() : '';
            var turnoSalvar = turnoSalvamentoPacotes ? turnoSalvamentoPacotes.value : 'Manhã';
            var consolidarSalvar = consolidarSalvamentoPacotes ? !!consolidarSalvamentoPacotes.checked : false;
            if (!autorSalvar) {
                alert('Informe o autor do salvamento.');
                if (autorSalvamentoPacotes) autorSalvamentoPacotes.focus();
                return;
            }
            if (!criadoSalvar) {
                alert('Informe a data de criação.');
                if (criadoSalvamentoPacotes) criadoSalvamentoPacotes.focus();
                return;
            }
            var formData = new FormData();
            formData.append('inserir_pacotes_nao_listados', '1');
            formData.append('usuario', usuarioAtual);
            formData.append('autor_salvamento', autorSalvar);
            formData.append('turno_salvamento', turnoSalvar);
            formData.append('criado_salvamento', criadoSalvar);
            if (consolidarSalvar) {
                formData.append('consolidar_salvamento', '1');
            }
            formData.append('pacotes', JSON.stringify(pacotesPendentes));
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        alert('Dados salvos com sucesso!');
                        mostrarConfirmacao('Dados salvos com sucesso! ' + data.inseridos + ' lote(s) enviados para ciPostosCsv e ' + (data.inseridos_postos || 0) + ' lançamento(s) em ciPostos.', true);
                        pacotesPendentes = [];
                        renderizarPacotesPendentes();
                        setTimeout(function() { window.location.reload(); }, 1400);
                    } else {
                        alert((data && data.erro) ? data.erro : 'Erro ao inserir pacotes.');
                    }
                })
                .catch(function(){ alert('Erro ao inserir pacotes.'); });
        });
    }
    
    if (usuarioInputModal) {
        usuarioInputModal.focus();
    }

    function liberarPaginaComUsuario(nome, restaurar) {
        if (!nomeResponsavelValido(nome)) {
            if (usuarioInputModal) usuarioInputModal.focus();
            return;
        }
        aplicarModoConsulta(false);
        try { localStorage.removeItem(storageModoKey); } catch (e) {}
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
        try {
            sessionStorage.setItem(storageUsuarioKey, nome);
        } catch (e) {}
        if (restaurar) {
            var tipoSalvo = '';
            try {
                tipoSalvo = sessionStorage.getItem(storageTipoKey) || '';
            } catch (e2) {}
            if (tipoSalvo) {
                selecionarTipoConferencia(tipoSalvo);
                return;
            }
        }
        if (overlayTipo) {
            overlayTipo.style.display = 'flex';
        }
    }

    if (btnConfirmarUsuario) {
        btnConfirmarUsuario.addEventListener('click', function() {
            var nome = usuarioInputModal ? usuarioInputModal.value.trim() : '';
            if (!nomeResponsavelValido(nome)) {
                alert('Informe o responsável da conferência.');
                if (usuarioInputModal) usuarioInputModal.focus();
                return;
            }
            liberarPaginaComUsuario(nome, false);
        });
    }

    if (btnSomenteVisualizar) {
        btnSomenteVisualizar.addEventListener('click', function() {
            var nome = usuarioInputModal ? usuarioInputModal.value.trim() : '';
            if (nome) return;
            ativarConsulta();
        });
    }

    if (btnAtivarConferencia) {
        btnAtivarConferencia.addEventListener('click', function() {
            ativarConferencia();
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

    var radiosTipo = document.querySelectorAll('input[name="tipo_inicio"]');
    for (var rt = 0; rt < radiosTipo.length; rt++) {
        radiosTipo[rt].addEventListener('change', function() {
            selecionarTipoConferencia(this.value);
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

    function atualizarBotoesOverlayUsuario() {
        var nome = usuarioInputModal ? usuarioInputModal.value.trim() : '';
        var temNome = !!nome;
        if (btnSomenteVisualizar) {
            btnSomenteVisualizar.disabled = temNome;
            btnSomenteVisualizar.style.opacity = temNome ? '0.35' : '';
            btnSomenteVisualizar.style.pointerEvents = temNome ? 'none' : '';
        }
        if (btnConfirmarUsuario) {
            btnConfirmarUsuario.disabled = !temNome;
            btnConfirmarUsuario.style.opacity = temNome ? '' : '0.35';
            btnConfirmarUsuario.style.pointerEvents = temNome ? '' : 'none';
        }
    }
    if (usuarioInputModal) {
        usuarioInputModal.addEventListener('input', atualizarBotoesOverlayUsuario);
        atualizarBotoesOverlayUsuario();
    }

    var thSort = document.querySelectorAll('th.sortable');
    for (var ti = 0; ti < thSort.length; ti++) {
        thSort[ti].addEventListener('click', function() {
            var chave = this.getAttribute('data-sort') || '';
            if (!chave) return;
            var atual = this.getAttribute('data-order') || 'asc';
            var asc = atual !== 'asc';
            this.setAttribute('data-order', asc ? 'asc' : 'desc');
            var tabela = this.closest('table');
            ordenarTabela(tabela, chave, asc);
        });
    }

    try {
        var modoSalvo = localStorage.getItem(storageModoKey) || '';
        if (modoSalvo === 'consulta') {
            ativarConsulta();
        } else {
            var nomeSalvo = sessionStorage.getItem(storageUsuarioKey) || '';
            if (nomeResponsavelValido(nomeSalvo)) {
                if (usuarioInputModal) usuarioInputModal.value = nomeSalvo;
                liberarPaginaComUsuario(nomeSalvo, true);
            } else {
                try { sessionStorage.removeItem(storageUsuarioKey); } catch (e4) {}
            }
        }
    } catch (e3) {}

    aplicarFiltroTipoVisual(obterTipoInicioSelecionado());
    atualizarResumoTodasTabelas();
    sincronizarPainelOperacao();
    renderizarDiagnosticoVoz();
    publicarResumoPrevia();
    
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
    
    function normalizarRegionalValor(valor) {
        var d = String(valor || '').replace(/\D+/g, '');
        if (!d) return '';
        if (d.length > 3) d = d.substr(0, 3);
        return d.padStart(3, '0');
    }

    function normalizarCodigoLeitura(valor) {
        var digits = String(valor || '').replace(/\D+/g, '');
        if (digits.length > 19) {
            digits = digits.slice(-19);
        }
        return digits;
    }

    function extrairPartesCodigo(codigo) {
        var codigoNormalizado = normalizarCodigoLeitura(codigo);
        if (codigoNormalizado.length !== 19) {
            return null;
        }
        return {
            codigo: codigoNormalizado,
            lote: codigoNormalizado.substr(0, 8),
            regional: codigoNormalizado.substr(8, 3),
            posto: codigoNormalizado.substr(11, 3),
            quantidade: parseInt(codigoNormalizado.substr(14, 5), 10) || 1
        };
    }

    function obterRegionalLinha(linha) {
        if (!linha) return '';
        var v = linha.getAttribute('data-regional-real') || linha.getAttribute('data-regional') || '';
        var n = normalizarRegionalValor(v);
        if (!n) {
            n = normalizarRegionalValor(linha.getAttribute('data-pt-group') || '');
        }
        if (!n) {
            n = normalizarRegionalValor(linha.getAttribute('data-posto') || '');
        }
        return n;
    }

    function localizarLinhaPorCodigo(codigo) {
        var codigoNormalizado = String(codigo || '').replace(/\D+/g, '');
        if (!codigoNormalizado) return null;
        var linha = document.querySelector('tr[data-codigo="' + codigoNormalizado + '"]');
        if (linha) return linha;
        var linhas = document.querySelectorAll('tbody tr[data-codigo]');
        for (var i = 0; i < linhas.length; i++) {
            var codigoLinha = String(linhas[i].getAttribute('data-codigo') || '').replace(/\D+/g, '');
            if (codigoLinha === codigoNormalizado) {
                return linhas[i];
            }
        }
        return null;
    }

    function extrairContextoCodigo(codigo) {
        var partes = extrairPartesCodigo(codigo);
        if (!partes) {
            return null;
        }
        return {
            lote: partes.lote,
            regional: normalizarRegionalValor(partes.regional),
            posto: partes.posto
        };
    }

    function obterChaveContextoCodigo(codigo) {
        var contexto = extrairContextoCodigo(codigo);
        if (!contexto) return '';
        return [contexto.lote, contexto.regional, contexto.posto].join('|');
    }

    function registrarLeituraConfirmada(linha, codigo) {
        var codigoLinha = '';
        var contextoLinha = '';
        if (linha) {
            codigoLinha = String(linha.getAttribute('data-codigo') || '').replace(/\D+/g, '');
            contextoLinha = [
                String(linha.getAttribute('data-lote') || ''),
                normalizarRegionalValor(linha.getAttribute('data-regional-real') || linha.getAttribute('data-regional') || ''),
                String(linha.getAttribute('data-posto') || '')
            ].join('|');
        }
        ultimaLeituraConfirmada = {
            codigo: codigoLinha || String(codigo || '').replace(/\D+/g, ''),
            contexto: contextoLinha || obterChaveContextoCodigo(codigo),
            quando: Date.now()
        };
    }

    function houveConfirmacaoRecenteRelacionada(codigo) {
        var agora = Date.now();
        var codigoNormalizado = String(codigo || '').replace(/\D+/g, '');
        var contextoAtual = obterChaveContextoCodigo(codigoNormalizado);
        if (!ultimaLeituraConfirmada.quando || (agora - ultimaLeituraConfirmada.quando) > 2000) {
            return false;
        }
        if (ultimaLeituraConfirmada.codigo && codigoNormalizado && ultimaLeituraConfirmada.codigo === codigoNormalizado) {
            return true;
        }
        if (ultimaLeituraConfirmada.contexto && contextoAtual && ultimaLeituraConfirmada.contexto === contextoAtual) {
            return true;
        }
        return false;
    }

    function localizarLinhaPorContexto(codigo) {
        var contexto = extrairContextoCodigo(codigo);
        var linhas;
        var linha;
        var regionalLinha;
        var linhaConfirmada = null;
        if (!contexto) return null;
        linhas = document.querySelectorAll('tbody tr[data-lote][data-posto]');
        for (var i = 0; i < linhas.length; i++) {
            linha = linhas[i];
            regionalLinha = normalizarRegionalValor(linha.getAttribute('data-regional-real') || linha.getAttribute('data-regional') || '');
            if (String(linha.getAttribute('data-lote') || '') !== contexto.lote) {
                continue;
            }
            if (String(linha.getAttribute('data-posto') || '') !== contexto.posto) {
                continue;
            }
            if (contexto.regional && regionalLinha && regionalLinha !== contexto.regional) {
                continue;
            }
            if (!linha.classList.contains('confirmado')) {
                return linha;
            }
            if (!linhaConfirmada) {
                linhaConfirmada = linha;
            }
        }
        return linhaConfirmada;
    }

    function localizarLinhaNaTela(codigo) {
        // v1.1.10: busca apenas pelos 8 primeiros dígitos (lote)
        // eliminando problemas de regional/posto divergente no código escaneado
        var digitos = String(codigo || '').replace(/\D+/g, '');
        var lote = digitos.substr(0, 8);
        if (!lote || lote.length < 8) return null;
        var linhas = document.querySelectorAll('tbody tr[data-lote]');
        var linhaConfirmada = null;
        for (var i = 0; i < linhas.length; i++) {
            var loteLinha = String(linhas[i].getAttribute('data-lote') || '').replace(/\D+/g, '');
            if (loteLinha === lote) {
                if (!linhas[i].classList.contains('confirmado')) {
                    return linhas[i];
                }
                if (!linhaConfirmada) linhaConfirmada = linhas[i];
            }
        }
        return linhaConfirmada;
    }

    function processarLeituraCodigo(valorBruto) {
        if (!input) return;
        if (modoConsulta) {
            if (mensagemLeitura) {
                mensagemLeitura.textContent = 'Modo consulta ativo.';
            }
            registrarHistoricoLeitura('Modo consulta', 'Leitura ignorada porque o modo consulta está ativo.', valorBruto);
            input.value = '';
            return;
        }
        var valorOriginal = String(valorBruto || '').trim();
        if (processarCodigoDeComando(valorOriginal)) {
            input.value = '';
            return;
        }
        if (processarValorDeComando(valorOriginal)) {
            input.value = '';
            return;
        }
        var valor = normalizarCodigoLeitura(valorOriginal);
        if (valor.length < 19) {
            return;
        }
        if (valor.length !== 19) {
            input.value = '';
            return;
        }

        var partesCodigo = extrairPartesCodigo(valor);
        if (!partesCodigo) {
            input.value = '';
            return;
        }

        var agoraLeitura = Date.now();
        if (codigosEmProcessamento[valor]) {
            return;
        }
        if (ultimoCodigoProcessado === valor && (agoraLeitura - ultimaLeituraProcessadaEm) < 700) {
            return;
        }
        codigosEmProcessamento[valor] = true;
        ultimoCodigoProcessado = valor;
        ultimaLeituraProcessadaEm = agoraLeitura;

        // T5b: qualquer leitura de um codigo DIFERENTE interrompe a sequencia de troca
        // de regional (a contagem so vale para 4 beeps CONSECUTIVOS do MESMO lote).
        if (valor !== trocaRegionalCodigo) {
            trocaRegionalCodigo = '';
            trocaRegionalContador = 0;
        }

        function finalizarProcessamento(limparInput) {
            delete codigosEmProcessamento[valor];
            if (limparInput) {
                input.value = '';
            }
            ultimoCodLido = '';
            if (scanTimer) {
                clearTimeout(scanTimer);
                scanTimer = null;
            }
            scanBuffer = '';
            if (input && document.activeElement !== input) {
                // preventScroll: mantem o cursor no input sem fazer a pagina subir
                // ate ele (evita o "sobe e desce"); a pagina so anda para a linha lida.
                try { input.focus({ preventScroll: true }); } catch (e) {
                    try { input.focus(); } catch (e2) {}
                }
            }
        }

        desbloquearAudio();

        var postoLido = partesCodigo.posto;
        if (postosBloqueadosMap[postoLido]) {
            var dadosBloq = postosBloqueadosMap[postoLido] || {};
            var motivoBloq = (dadosBloq.motivo || dadosBloq.nome || '').toString().trim();
            var textoVoz = motivoBloq ? motivoBloq : 'posto bloqueado';
            avisarSomOuFala('posto_bloqueado:' + postoLido, null, textoVoz);
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Posto bloqueado:</strong> ' + postoLido + ' ' + (motivoBloq || '');
            }
            registrarHistoricoLeitura('Posto bloqueado', 'Posto ' + postoLido + ' bloqueado para conferência.', valor);
            finalizarProcessamento(true);
            return;
        }

        // v1.2.2: Aviso de restricao de posto (nao bloqueia fluxo, apenas alerta)
        if (postosRestricoes && postosRestricoes[postoLido]) {
            var dadosRest  = postosRestricoes[postoLido];
            var tipoRest   = (dadosRest.tipo   || 'restricao').toString();
            var motivoRest = (dadosRest.motivo || '').toString().trim();
            var corRest    = (dadosRest.cor    || '#e65100').toString();
            var textoVozR  = tipoRest + (motivoRest ? ': ' + motivoRest : '');
            avisarSomOuFala('restricao_posto:' + postoLido, null, textoVozR);
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong style="color:' + corRest + '">[!] Restricao [' + tipoRest + ']:</strong> Posto ' + postoLido + (motivoRest ? ' -- ' + motivoRest : '');
            }
            registrarHistoricoLeitura('Restricao de posto', 'Posto ' + postoLido + ' tem restricao: ' + tipoRest + (motivoRest ? ' (' + motivoRest + ')' : ''), valor);
        }

        var linha = localizarLinhaNaTela(valor);

        if (!linha) {
            verificarPacoteOutraData(valor, function(resp) {
                var linhaRevalidada = localizarLinhaNaTela(valor);
                if (linhaRevalidada) {
                    removerPendentePorCodbar(valor);
                    if (mensagemLeitura && linhaRevalidada.classList.contains('confirmado')) {
                        mensagemLeitura.textContent = '';
                    }
                    registrarHistoricoLeitura('Pacote localizado na tela', 'Aviso de não encontrado ignorado porque a linha apareceu na tela durante a leitura.', valor);
                    finalizarProcessamento(true);
                    return;
                }

                if (houveConfirmacaoRecenteRelacionada(valor)) {
                    removerPendentePorCodbar(valor);
                    registrarHistoricoLeitura('Aviso descartado', 'Leitura duplicada ignorada para evitar falso pacote não encontrado.', valor);
                    finalizarProcessamento(true);
                    return;
                }

                if (resp && resp.success && resp.status === 'outra_data') {
                    if (mensagemLeitura) {
                        mensagemLeitura.innerHTML = '<strong>Pacote de outra data:</strong> ' + formatarDataBr(resp.data || '');
                    }
                    registrarHistoricoLeitura('Pacote de outra data', 'Pacote localizado fora do filtro atual.', valor);
                    var ptsDtRD = (resp.data || '').split('-');
                    var diaRD = ptsDtRD.length >= 3 ? String(parseInt(ptsDtRD[2], 10)) : '';
                    var nomesMesesRD = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
                    var mesIdxRD = ptsDtRD.length >= 2 ? (parseInt(ptsDtRD[1], 10) - 1) : -1;
                    var mesRD = (mesIdxRD >= 0 && mesIdxRD < 12) ? nomesMesesRD[mesIdxRD] : '';
                    var textoOD = diaRD ? ('Lote do dia ' + diaRD + (mesRD ? ' de ' + mesRD : '')) : 'pacote de outra data';
                    avisarSomOuFala('pacote_outra_data:' + valor, null, textoOD);
                    finalizarProcessamento(true);
                    return;
                }

                var now = new Date();
                var mm = String(now.getMonth() + 1).padStart(2, '0');
                var dd = String(now.getDate()).padStart(2, '0');
                var dataPadrao = now.getFullYear() + '-' + mm + '-' + dd;

                var obj = {
                    codbar: partesCodigo.codigo,
                    lote: partesCodigo.lote,
                    regional: partesCodigo.regional,
                    posto: partesCodigo.posto,
                    quantidade: partesCodigo.quantidade,
                    dataexp: dataPadrao,
                    responsavel: ''
                };
                adicionarPacotePendente(obj);
                if (painelPacotesNovos) {
                    painelPacotesNovos.style.display = 'block';
                    painelPacotesNovos.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                tocarPacoteNaoEncontrado();
                if (mensagemLeitura) {
                    mensagemLeitura.innerHTML = '<strong>Pacote não encontrado:</strong> adicionado à lista pendente.';
                }
                registrarHistoricoLeitura('Pacote não encontrado', 'Pacote enviado para a fila de pendentes.', valor);
                finalizarProcessamento(true);
            });
            return;
        }

        if (!usuarioAtual) {
            // Recupera o responsavel ja informado nesta sessao. O overlay de nome
            // pode ter sido liberado pelo handler de fallback (grava no
            // sessionStorage mas nao seta usuarioAtual) ou usuarioAtual pode ter
            // sido zerado por uma sincronizacao. Sem isto a camera pediria o nome
            // de novo a CADA leitura (alerta/beep continuo).
            var respRecuperado = '';
            try { respRecuperado = sessionStorage.getItem(storageUsuarioKey) || ''; } catch (eRec) {}
            if (nomeResponsavelValido(respRecuperado)) {
                usuarioAtual = respRecuperado;
                if (usuarioBadge) { usuarioBadge.textContent = respRecuperado; }
            }
        }
        if (!usuarioAtual) {
            alert('Informe o responsável da conferência para iniciar.');
            if (overlayUsuario) { overlayUsuario.style.display = 'flex'; }
            if (conteudoPagina) { conteudoPagina.classList.add('page-locked'); }
            if (usuarioInputModal) { usuarioInputModal.focus(); }
            finalizarProcessamento(true);
            return;
        }

        if (!tipoEscolhido) {
            if (overlayTipo) overlayTipo.style.display = 'flex';
            finalizarProcessamento(true);
            return;
        }

        var tipoSelecionado = obterTipoInicioSelecionado();
        var modoTodos = tipoSelecionado === 'todos';
        var regionalDoPacote = obterRegionalLinha(linha);
        var regionalDoPacoteNorm = normalizarRegionalValor(regionalDoPacote);
        var postoDoPacote = formatarCodigoComZeros(linha.getAttribute('data-posto') || '', 3);
        var isPoupaTempo = linha.getAttribute('data-ispt') === '1';
        var tipoPacote = isPoupaTempo ? 'poupatempo' : 'correios';

        if (linha.classList.contains('confirmado')) {
            enfileirarSom(pacoteJaConferido);
            destacarChipOperacao(linha.getAttribute('data-codigo') || valor);
            registrarHistoricoLeitura('Pacote já conferido', 'O pacote já estava marcado como conferido.', valor);
            finalizarProcessamento(true);
            return;
        }

        var somAlerta = null;
        var podeConferir = true;
        var silenciarConfirmacao = false; // troca de regional (4a leitura) = so o anuncio, sem beep/alerta extra

        if (podeConferir && tipoPacote === 'correios' && tipoAtual === 'correios') {
            var regionalAtualNormCheck = normalizarRegionalValor(regionalAtual);
            if (regionalAtualNormCheck && regionalDoPacoteNorm && regionalDoPacoteNorm !== regionalAtualNormCheck) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            } else if (contextoCorreiosExigeMesmoPosto(regionalAtualNormCheck) && postoAtual && postoDoPacote && postoDoPacote !== postoAtual) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            }
        }

        if (ultimoTipoLido === tipoPacote && tipoPacote === 'correios') {
            if (ultimaRegionalLida && regionalDoPacoteNorm && regionalDoPacoteNorm !== ultimaRegionalLida) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            } else if (contextoCorreiosExigeMesmoPosto(ultimaRegionalLida) && ultimoPostoLido && postoDoPacote && postoDoPacote !== ultimoPostoLido) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            }
        }

        if (podeConferir && !primeiroConferido) {
            tipoAtual = tipoSelecionado;
            if (modoTodos || tipoAtual === tipoPacote) {
                tipoAtual = tipoPacote;
                regionalAtual = regionalDoPacoteNorm || regionalDoPacote;
                postoAtual = tipoPacote === 'correios' ? postoDoPacote : null;
            } else {
                podeConferir = false;
                avisarIncompatibilidadeTipo(tipoPacote);
            }
            if (podeConferir) {
                primeiroConferido = true;
            }
        } else if (!modoTodos && podeConferir && tipoAtual === 'correios' && tipoPacote === 'poupatempo') {
            somAlerta = postoPoupaTempo;
            podeConferir = false;
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Posto do Poupa Tempo:</strong> altere o tipo para Poupa Tempo ou Todos.';
            }
            // som tocado 1x no bloqueio abaixo (enfileirarSom(somAlerta)) -> evita audio duplicado
        } else if (!modoTodos && podeConferir && tipoAtual === 'poupatempo' && tipoPacote === 'correios') {
            somAlerta = pertenceCorreios;
            podeConferir = false;
            if (mensagemLeitura) {
                mensagemLeitura.innerHTML = '<strong>Posto dos Correios:</strong> altere o tipo para Correios ou Todos.';
            }
            // som tocado 1x no bloqueio abaixo (enfileirarSom(somAlerta)) -> evita audio duplicado
        } else if (!modoTodos && podeConferir && tipoPacote === tipoAtual) {
            var regionalContextoAtual = normalizarRegionalValor(regionalAtual);
            if (regionalDoPacoteNorm && regionalDoPacoteNorm !== regionalContextoAtual) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            } else if (contextoCorreiosExigeMesmoPosto(regionalContextoAtual) && postoAtual && postoDoPacote && postoDoPacote !== postoAtual) {
                somAlerta = pacoteOutraRegional;
                podeConferir = false;
            }
        }

        if (podeConferir && tipoPacote === 'poupatempo') {
            var totalPT = document.querySelectorAll('tbody tr[data-ispt="1"]').length;
            if (totalPT === 1 && !somAlerta) {
                somAlerta = postoPoupaTempo;
            }
        }

        if (!podeConferir) {
            // T5b: troca de regional no 4o beep seguido do MESMO lote de outra regional.
            if (somAlerta === pacoteOutraRegional) {
                if (trocaRegionalCodigo === valor) {
                    trocaRegionalContador++;
                } else {
                    trocaRegionalCodigo = valor;
                    trocaRegionalContador = 1;
                }
                if (trocaRegionalContador >= 4) {
                    trocaRegionalCodigo = '';
                    trocaRegionalContador = 0;
                    // Confirma a intencao de trocar para a regional/posto do lote beepado.
                    tipoAtual = tipoPacote;
                    regionalAtual = regionalDoPacoteNorm || regionalDoPacote;
                    postoAtual = (tipoPacote === 'correios') ? postoDoPacote : null;
                    ultimaRegionalLida = null;
                    ultimoPostoLido = null;
                    ultimoTipoLido = null;
                    primeiroConferido = true;
                    var regAviso = (regionalDoPacoteNorm || regionalDoPacote || '').toString();
                    var msgTroca = 'Mudando a conferência para a regional ' + regAviso;
                    if (mensagemLeitura) {
                        mensagemLeitura.innerHTML = '<strong>' + msgTroca + '</strong>';
                    }
                    avisarSomOuFala('troca_regional:' + regAviso, null, msgTroca);
                    registrarHistoricoLeitura('Troca de regional', msgTroca + ' (4 leituras seguidas do mesmo lote).', valor);
                    somAlerta = null;            // nao repetir "pacote de outra regional" na confirmacao
                    silenciarConfirmacao = true; // 4a leitura: tocar SO o anuncio de troca de regional
                    podeConferir = true; // segue para a confirmacao normal abaixo
                }
            } else {
                // Bloqueio por outro motivo zera a contagem de troca.
                trocaRegionalCodigo = '';
                trocaRegionalContador = 0;
            }
            if (!podeConferir) {
                if (somAlerta) {
                    enfileirarSom(somAlerta);
                }
                registrarHistoricoLeitura('Leitura bloqueada', 'O pacote pertence a outro contexto de conferência.', valor);
                finalizarProcessamento(true);
                return;
            }
        }

        // Conferencia confirmada: zera a contagem de troca de regional.
        trocaRegionalCodigo = '';
        trocaRegionalContador = 0;
        linha.classList.add('confirmado');
        var conferidoAgora = formatarDataHoraAtual();
        linha.setAttribute('data-conferido-em', conferidoAgora);
        registrarLeituraConfirmada(linha, valor);
        var tdConf = linha.querySelector('.col-conferido-em');
        if (tdConf) tdConf.textContent = conferidoAgora;
        selecionarContextoMalote(obterContextoMaloteDeRegistroTabela(linha));
        var chipPrincipal = atualizarChipOperacaoPorCodigo(linha.getAttribute('data-codigo') || valor, true);
        if (chipPrincipal) {
            chipPrincipal.setAttribute('data-conferido-em', conferidoAgora);
        }
        tipoAtual = tipoPacote;
        if (tipoPacote === 'correios') {
            regionalAtual = regionalDoPacoteNorm || regionalDoPacote;
            postoAtual = postoDoPacote;
        }
        atualizarResumoTabela(linha.closest('table'));

        if (!silenciarConfirmacao) {
            // 4a leitura de troca de regional ja tocou SO o anuncio acima; nas demais,
            // toca o beep de sucesso (+ alerta, se houver) -> 1 som por evento.
            tocarBeepLeitura();
            if (somAlerta) {
                enfileirarSom(somAlerta);
            }
        }

        if (mensagemLeitura) {
            mensagemLeitura.textContent = '';
        }
        registrarHistoricoLeitura('Pacote conferido', 'Leitura confirmada com sucesso.', valor);

        var ultimas = document.querySelectorAll('tr.ultimo-lido');
        for (var u = 0; u < ultimas.length; u++) {
            ultimas[u].classList.remove('ultimo-lido');
        }
        linha.classList.add('ultimo-lido');

        centralizarElemento(linha);
        destacarChipOperacao(linha.getAttribute('data-codigo') || valor);

        ultimaRegionalLida = regionalDoPacoteNorm || regionalDoPacote;
        ultimoPostoLido = tipoPacote === 'correios' ? postoDoPacote : null;
        ultimoTipoLido = tipoPacote;

        if (usuarioAtual) {
            var lote = linha.getAttribute('data-lote');
            var regional = linha.getAttribute('data-regional');
            var posto = linha.getAttribute('data-posto');
            var dataexp = linha.getAttribute('data-data-sql') || linha.getAttribute('data-data');
            var qtd = linha.getAttribute('data-qtd');
            var codbar = linha.getAttribute('data-codigo');
            salvarConferencia(lote, regional, posto, dataexp, qtd, codbar, usuarioAtual);
        }

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
        } else {
            var regionalAtualNorm = normalizarRegionalValor(regionalAtual || regionalDoPacoteNorm);
            if (regionalAtualNorm === '000' || regionalAtualNorm === '999') {
                grupoAtual = linha.getAttribute('data-posto');
                for (var i2 = 0; i2 < todasLinhas.length; i2++) {
                    if (obterRegionalLinha(todasLinhas[i2]) === regionalAtualNorm &&
                        todasLinhas[i2].getAttribute('data-ispt') !== '1' &&
                        todasLinhas[i2].getAttribute('data-posto') === grupoAtual) {
                        linhasDoGrupo.push(todasLinhas[i2]);
                    }
                }
            } else {
                grupoAtual = regionalAtualNorm;
                for (var i3 = 0; i3 < todasLinhas.length; i3++) {
                    if (obterRegionalLinha(todasLinhas[i3]) === regionalAtualNorm &&
                        todasLinhas[i3].getAttribute('data-ispt') !== '1') {
                        linhasDoGrupo.push(todasLinhas[i3]);
                    }
                }
            }
        }

        var codigosDoGrupo = {};
        var codigosConferidosDoGrupo = {};
        var totalCodigosGrupo = 0;
        var totalCodigosConferidosGrupo = 0;
        for (var j = 0; j < linhasDoGrupo.length; j++) {
            var codigoGrupo = String(linhasDoGrupo[j].getAttribute('data-codigo') || '').replace(/\D+/g, '');
            if (!codigoGrupo) {
                codigoGrupo = 'linha:' + j;
            }
            if (!codigosDoGrupo[codigoGrupo]) {
                codigosDoGrupo[codigoGrupo] = true;
                totalCodigosGrupo++;
            }
            if (linhasDoGrupo[j].classList.contains('confirmado') && !codigosConferidosDoGrupo[codigoGrupo]) {
                codigosConferidosDoGrupo[codigoGrupo] = true;
                totalCodigosConferidosGrupo++;
            }
        }

        if (totalCodigosGrupo > 0 && totalCodigosConferidosGrupo === totalCodigosGrupo) {
            enfileirarSom(concluido);
            regionalAtual = null;
            postoAtual = null;
            tipoAtual = null;
            primeiroConferido = false;
            ultimaRegionalLida = null;
            ultimoPostoLido = null;
            ultimoTipoLido = null;
        }

        finalizarProcessamento(true);
    }

    window.processarLeituraCodigo = processarLeituraCodigo;

    // T5a: confirma um lote de RETIRADA (ja expedido, sem leitura) direto pelo botao,
    // sem precisar copiar o codigo de barras e colar no scanner do topo.
    function conferirRetirada(btn) {
        var linha = btn;
        while (linha && linha.nodeName !== 'TR') { linha = linha.parentNode; }
        if (!linha) return;
        if (linha.classList.contains('confirmado')) {
            if (btn && btn.parentNode) { btn.style.display = 'none'; }
            return;
        }
        if (!usuarioAtual) {
            alert('Informe o responsável da conferência para confirmar.');
            if (overlayUsuario) { overlayUsuario.style.display = 'flex'; }
            if (conteudoPagina) { conteudoPagina.classList.add('page-locked'); }
            if (usuarioInputModal) { usuarioInputModal.focus(); }
            return;
        }
        var codigo = linha.getAttribute('data-codigo') || '';
        linha.classList.add('confirmado');
        var conferidoAgora = formatarDataHoraAtual();
        linha.setAttribute('data-conferido-em', conferidoAgora);
        registrarLeituraConfirmada(linha, codigo);
        var tdConf = linha.querySelector('.col-conferido-em');
        if (tdConf) tdConf.textContent = conferidoAgora;
        var chipPrincipal = atualizarChipOperacaoPorCodigo(codigo, true);
        if (chipPrincipal) { chipPrincipal.setAttribute('data-conferido-em', conferidoAgora); }
        if (linha.closest) { atualizarResumoTabela(linha.closest('table')); }
        tocarBeepLeitura();
        registrarHistoricoLeitura('Pacote conferido (retirada)', 'Lote de retirada confirmado manualmente pelo botão.', codigo);
        var lote = linha.getAttribute('data-lote');
        var regional = linha.getAttribute('data-regional');
        var posto = linha.getAttribute('data-posto');
        var dataexp = linha.getAttribute('data-data-sql') || linha.getAttribute('data-data');
        var qtd = linha.getAttribute('data-qtd');
        salvarConferencia(lote, regional, posto, dataexp, qtd, codigo, usuarioAtual);
        if (btn && btn.parentNode) { btn.style.display = 'none'; }
    }
    window.conferirRetirada = conferirRetirada;

    // Scanner de código de barras
    if (input) {
        input.addEventListener("input", function() {
            var digits = normalizarCodigoLeitura(input.value);
            if (limpezaParcialInputTimer) {
                clearTimeout(limpezaParcialInputTimer);
                limpezaParcialInputTimer = null;
            }

            if (digits.length >= 19) {
                processarLeituraCodigo(digits);
                return;
            }

            if (digits.length > 0) {
                limpezaParcialInputTimer = setTimeout(function() {
                    if (!input) return;
                    var digitsPendentes = normalizarCodigoLeitura(input.value);
                    if (digitsPendentes.length > 0 && digitsPendentes.length < 19) {
                        input.value = '';
                    }
                }, 180);
            }
        });
    }

    var scanBuffer = '';
    var scanTimer = null;
    document.addEventListener('keydown', function(e) {
        if (!e) return;
        var alvo = e.target;
        if (input && alvo === input) {
            scanBuffer = '';
            return;
        }
        if (entradaBloqueiaScanner(alvo)) {
            scanBuffer = '';
            return;
        }
        if (e.keyCode === 13) {
            if (scanBuffer.length >= 19) {
                processarLeituraCodigo(scanBuffer);
                scanBuffer = '';
            }
            return;
        }
        var digit = null;
        var k = e.key;
        if (k && k.length === 1 && k >= '0' && k <= '9') {
            digit = k;
        } else if (e.keyCode >= 48 && e.keyCode <= 57) {
            digit = String.fromCharCode(e.keyCode);
        } else if (e.keyCode >= 96 && e.keyCode <= 105) {
            digit = String(e.keyCode - 96);
        }
        if (!digit) return;
        scanBuffer += digit;
        if (scanTimer) clearTimeout(scanTimer);
        scanTimer = setTimeout(function() { scanBuffer = ''; }, 300);
        if (scanBuffer.length >= 19) {
            processarLeituraCodigo(scanBuffer);
            scanBuffer = '';
        }
    });
    
    // Resetar conferência
    btnResetar.addEventListener("click", function() {
        if (confirm("Tem certeza que deseja reiniciar a conferência? Isso irá APAGAR todos os dados conferidos do banco!")) {
            // Obter datas filtradas
            var datas = [];

            for (var i = 0; i < datasFiltroSql.length; i++) {
                if (datasFiltroSql[i]) {
                    datas.push(datasFiltroSql[i]);
                }
            }
            
            // Resetar visualmente
            var trsConfirmados = document.querySelectorAll("tr.confirmado");
            for (var j = 0; j < trsConfirmados.length; j++) {
                trsConfirmados[j].classList.remove("confirmado");
            }
            var chipsAtribuidos = document.querySelectorAll('.operacao-chip');
            for (var c = 0; c < chipsAtribuidos.length; c++) {
                chipsAtribuidos[c].setAttribute('data-conf', '0');
                aplicarAtribuicaoNoChip(chipsAtribuidos[c], {
                    lacre_iipr: '',
                    grupo_iipr: '',
                    lacre_correios: '',
                    grupo_correios: '',
                    etiqueta_correios: '',
                    usuario_lacre: '',
                    atualizado_lacre: ''
                });
            }
            atualizarResumoTodasTabelas();
            sincronizarPainelOperacao();
            
            regionalAtual = null;
            postoAtual = null;
            tipoAtual = null; // v9.2: Reseta tipo
            primeiroConferido = false; // v9.2: Reseta flag
            ultimaRegionalLida = null;
            ultimoPostoLido = null;
            ultimoTipoLido = null;
            valoresDigitadosPorContexto = {};
            ultimoLacreIiprAplicado = '';
            ultimoLacreCorreiosAplicado = '';
            ultimaEtiquetaCorreiosAplicada = '';
            input.value = "";
            input.focus();
            contextoSelecionadoMalote = '';
            tipoContextoSelecionadoMalote = '';
            rotuloContextoSelecionadoMalote = '';
            renderizarPainelMalotes();
            
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
            var motivoTxt = (p.motivo || p.nome || '').toString().trim();
            div.innerHTML = '<div><span class="posto">' + p.posto + '</span> ' + motivoTxt + '</div>' +
                '<button type="button" class="btn-acao btn-cancelar" data-remover="' + p.posto + '">Remover</button>';
            listaPostosBloqueados.appendChild(div);
        }
        atualizarMapaBloqueados();
    }

    if (btnAdicionarBloqueio) {
        btnAdicionarBloqueio.addEventListener('click', function() {
            var posto = postoBloqueioNumero ? postoBloqueioNumero.value.trim() : '';
            var motivo = postoBloqueioNome ? postoBloqueioNome.value.trim() : '';
            var responsavel = postoBloqueioResponsavel ? postoBloqueioResponsavel.value.trim() : '';
            if (!posto) {
                alert('Informe o numero do posto.');
                if (postoBloqueioNumero) postoBloqueioNumero.focus();
                return;
            }
            if (!motivo) {
                alert('Informe o motivo do bloqueio.');
                if (postoBloqueioNome) postoBloqueioNome.focus();
                return;
            }
            if (!responsavel) {
                alert('Informe o responsavel pelo bloqueio.');
                if (postoBloqueioResponsavel) postoBloqueioResponsavel.focus();
                return;
            }
            var formData = new FormData();
            formData.append('salvar_posto_bloqueado', '1');
            formData.append('posto', posto);
            formData.append('motivo', motivo);
            formData.append('responsavel', responsavel);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        postosBloqueados.push({ posto: posto, nome: motivo, motivo: motivo });
                        renderizarPostosBloqueados();
                        if (postoBloqueioNumero) postoBloqueioNumero.value = '';
                        if (postoBloqueioNome) postoBloqueioNome.value = '';
                        if (postoBloqueioResponsavel) postoBloqueioResponsavel.value = '';
                    } else {
                        alert(data && data.erro ? data.erro : 'Erro ao salvar posto bloqueado.');
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
            var responsavel = postoDesbloqueioResponsavel ? postoDesbloqueioResponsavel.value.trim() : '';
            var motivo = postoDesbloqueioMotivo ? postoDesbloqueioMotivo.value.trim() : '';
            if (!responsavel) {
                alert('Informe o responsavel pelo desbloqueio.');
                if (postoDesbloqueioResponsavel) postoDesbloqueioResponsavel.focus();
                return;
            }
            var formData = new FormData();
            formData.append('excluir_posto_bloqueado', '1');
            formData.append('posto', posto);
            formData.append('responsavel', responsavel);
            formData.append('motivo', motivo);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (data && data.success) {
                        postosBloqueados = postosBloqueados.filter(function(p){ return p.posto !== posto; });
                        renderizarPostosBloqueados();
                        if (postoDesbloqueioMotivo) postoDesbloqueioMotivo.value = '';
                    } else {
                        alert(data && data.erro ? data.erro : 'Erro ao remover posto bloqueado.');
                    }
                })
                .catch(function(){ alert('Erro ao remover posto bloqueado.'); });
        });
    }

    renderizarPostosBloqueados();
    consultarComandosRemotos();
    setInterval(consultarComandosRemotos, 1200);
    } catch (e) {
        try { console.error('Erro ao iniciar conferência', e); } catch (e2) {}
    }
}

window.iniciarConferenciaPacotes = iniciarConferenciaPacotes;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarConferenciaPacotes);
} else {
    iniciarConferenciaPacotes();
}
</script>

<script>
(function() {
    function confirmarResponsavelFallback() {
        var input = document.getElementById('usuario_conf_modal');
        var nome = input ? input.value.trim() : '';
        if (!nome) {
            alert('Informe o responsável da conferência.');
            if (input) input.focus();
            return;
        }
        var badge = document.getElementById('usuarioBadge');
        if (badge) badge.textContent = nome;
        var overlay = document.getElementById('overlayUsuario');
        if (overlay) overlay.style.display = 'none';
        var conteudo = document.getElementById('conteudoPagina');
        if (conteudo) conteudo.classList.remove('page-locked');
        try {
            sessionStorage.setItem('conferencia_responsavel', nome);
        } catch (e) {}
        var overlayTipo = document.getElementById('overlayTipo');
        if (overlayTipo) overlayTipo.style.display = 'flex';
    }

    var btn = document.getElementById('btnConfirmarUsuario');
    if (btn && !btn.__fallbackBound) {
        btn.addEventListener('click', confirmarResponsavelFallback);
        btn.__fallbackBound = true;
    }

    var input = document.getElementById('usuario_conf_modal');
    if (input && !input.__fallbackBound) {
        input.addEventListener('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                confirmarResponsavelFallback();
            }
        });
        input.__fallbackBound = true;
    }
})();
</script>

<script>
(function() {
    function selecionarTipoFallback(tipo) {
        if (!tipo) return;
        var radios = document.querySelectorAll('input[name="tipo_inicio"]');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].value === tipo) {
                radios[i].checked = true;
                break;
            }
        }
        var overlay = document.getElementById('overlayTipo');
        if (overlay) overlay.style.display = 'none';
        try {
            sessionStorage.setItem('conferencia_tipo_inicio', tipo);
        } catch (e) {}
        var input = document.getElementById('codigo_barras');
        if (input) input.focus();
    }

    var overlayTipo = document.getElementById('overlayTipo');
    if (overlayTipo && !overlayTipo.__fallbackBound) {
        overlayTipo.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) return;
            var tipo = target.getAttribute('data-tipo');
            if (!tipo && target.parentNode && target.parentNode.getAttribute) {
                tipo = target.parentNode.getAttribute('data-tipo');
            }
            if (tipo) {
                selecionarTipoFallback(tipo);
            }
        });
        overlayTipo.__fallbackBound = true;
    }
})();
</script>

<!-- Leitor por camera: a camera do celular vira o "leitor" de codigo de barras -->
<div id="camScanOverlay" class="cam-scan-overlay" aria-hidden="true">
    <div class="cam-scan-video-wrap" id="camScanWrap">
        <video id="camScanVideo" playsinline autoplay muted></video>
        <div class="cam-scan-mira"></div>
        <div class="cam-scan-dica">Encaixe o código de barras dentro do retângulo</div>
        <div class="cam-scan-status" id="camScanStatus">Abrindo câmera...</div>
        <div class="cam-scan-status" id="camScanDiag" style="opacity:.75;font-size:11px;font-family:monospace;min-height:14px;"></div>
    </div>
    <div class="cam-scan-erro-box" id="camScanErro"></div>
    <div class="cam-scan-barra">
        <button type="button" id="camScanTorch">🔦 Lanterna</button>
        <button type="button" id="camScanFechar">✖ Fechar</button>
    </div>
</div>

<script src="lib_zxing.min.js"></script>
<script>
(function () {
    "use strict";

    var btnAbrir = document.getElementById('btnCameraScanner');
    var overlay = document.getElementById('camScanOverlay');
    var wrap = document.getElementById('camScanWrap');
    var video = document.getElementById('camScanVideo');
    var statusEl = document.getElementById('camScanStatus');
    var erroBox = document.getElementById('camScanErro');
    var btnFechar = document.getElementById('camScanFechar');
    var btnTorch = document.getElementById('camScanTorch');
    if (!btnAbrir || !overlay || !video) { return; }

    var ativo = false;
    var torchOn = false;
    var ultimoCodigo = '';
    var ultimoEm = 0;
    var flashTimer = null;
    var stream = null;          // MediaStream da camera (gerenciado por nos)
    var loopTimer = null;       // timer do laco de leitura ao vivo
    var leitor = null;          // ZXing.MultiFormatReader (decodifica quadros)
    var hintsLeitura = null;    // hints (TRY_HARDER) reaproveitados a cada decode
    var roiCanvas = null;       // canvas oculto p/ recortar a regiao da mira
    var roiCtx = null;
    var fallbackReader = null;  // BrowserMultiFormatReader (reserva, se ROI indisponivel)
    var abrindo = false;        // getUserMedia em andamento (evita abrir 2x)
    var geracao = 0;            // invalida streams que resolvem APOS fechar
    var detectorNativo = null;  // BarcodeDetector NATIVO (Android Chrome) — preferido
    var nativoFalhou = false;   // se detect() rejeitar (nao-transiente), cai no ZXing
    var quadros = 0;            // contador de quadros (diagnostico na tela)

    function setStatus(txt) { if (statusEl) { statusEl.textContent = txt || ''; } }
    function setDiag(txt) { var d = document.getElementById('camScanDiag'); if (d) { d.textContent = txt || ''; } }

    function temDetectorNativo() { return (typeof window.BarcodeDetector === 'function'); }

    function criarDetectorNativo() {
        if (detectorNativo || nativoFalhou || !temDetectorNativo()) { return; }
        // Sem {formats}: detecta TODOS os formatos suportados pelo aparelho.
        try { detectorNativo = new window.BarcodeDetector(); }
        catch (e) { detectorNativo = null; nativoFalhou = true; }
    }

    function escolherCodigoNativo(cods) {
        for (var i = 0; i < cods.length; i++) {
            if (('' + cods[i].rawValue).replace(/\D/g, '').length >= 19) { return cods[i].rawValue; }
        }
        return cods[0].rawValue;
    }

    function mostrarErro(html) {
        if (!erroBox) { return; }
        erroBox.innerHTML = html;
        erroBox.className = 'cam-scan-erro-box aberto';
    }
    function limparErro() {
        if (erroBox) { erroBox.className = 'cam-scan-erro-box'; erroBox.innerHTML = ''; }
    }

    function montarHints() {
        var hints = new Map();
        if (ZXing.DecodeHintType) {
            // TRY_HARDER e ESSENCIAL para leitura AO VIVO 1D (Code-128): sem ele o leitor
            // varre poucas linhas/angulos e quase nunca dispara apontando a camera.
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            if (ZXing.BarcodeFormat) {
                // SO formatos 1D: assim a lib usa UM unico leitor 1D e PULA os detectores
                // 2D (QR/DataMatrix/Aztec/PDF417), multiplicando as tentativas por segundo.
                // Conjunto amplo de 1D evita o caso "nada acontece" por tipo inesperado.
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.ITF,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.CODE_93,
                    ZXing.BarcodeFormat.CODABAR,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8
                ]);
            }
        }
        return hints;
    }

    function flashLido() {
        if (!wrap) { return; }
        wrap.className = 'cam-scan-video-wrap lido';
        if (flashTimer) { clearTimeout(flashTimer); }
        flashTimer = setTimeout(function () {
            wrap.className = 'cam-scan-video-wrap';
        }, 280);
    }

    function aoLer(texto) {
        var bruto = ('' + (texto || ''));
        var d = bruto.replace(/\D/g, '');
        if (d.length > 19) { d = d.slice(-19); }
        if (d.length < 19) {
            // Detectou um codigo, mas nao com os 19 digitos do codigo do sistema.
            // Mostra feedback p/ o usuario saber que a leitura ESTA funcionando.
            if (d.length > 0) { setStatus('Detectado ' + d.length + ' díg — alinhe o código todo na mira'); }
            return;
        }
        var agora = Date.now();
        // Evita reprocessar o MESMO codigo enquanto ele continua na mira.
        if (d === ultimoCodigo && (agora - ultimoEm) < 1500) { return; }
        ultimoCodigo = d;
        ultimoEm = agora;
        flashLido();
        setStatus('Lido: ' + d.substr(0, 8));
        if (typeof window.processarLeituraCodigo === 'function') {
            try { window.processarLeituraCodigo(d); } catch (e) {}
        }
    }

    // Recorta a FAIXA CENTRAL do video (onde fica a mira), amplia e decodifica SO essa
    // regiao. Focar a area do codigo + ampliar deixa as barras 1D muito mais legiveis do
    // que decodificar o quadro inteiro -> leitura ao vivo bem mais confiavel.
    function decodificarRegiao() {
        if (!leitor || !ZXing.HTMLCanvasElementLuminanceSource) { return false; }
        var vw = video.videoWidth || 0;
        var vh = video.videoHeight || 0;
        if (vw < 2 || vh < 2) { return false; }
        // ROI ~ mira: 88% da largura, 34% da altura, centralizado.
        var roiW = Math.round(vw * 0.88);
        var roiH = Math.round(vh * 0.34);
        var roiX = Math.round((vw - roiW) / 2);
        var roiY = Math.round((vh - roiH) / 2);
        // Amplia ~2x quando a regiao e pequena, p/ reforcar barras finas.
        var escala = roiW < 900 ? 2 : 1;
        var cw = roiW * escala;
        var ch = roiH * escala;
        if (!roiCanvas) {
            roiCanvas = document.createElement('canvas');
            roiCtx = roiCanvas.getContext('2d');
        }
        if (roiCanvas.width !== cw) { roiCanvas.width = cw; }
        if (roiCanvas.height !== ch) { roiCanvas.height = ch; }
        try {
            roiCtx.drawImage(video, roiX, roiY, roiW, roiH, 0, 0, cw, ch);
        } catch (e) { return false; }
        try {
            var src = new ZXing.HTMLCanvasElementLuminanceSource(roiCanvas);
            var bmp = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(src));
            // decodeWithState reaproveita os leitores ja preparados (setHints no abrir),
            // sem reconstruir o array de leitores a cada quadro.
            var result = leitor.decodeWithState ? leitor.decodeWithState(bmp) : leitor.decode(bmp, hintsLeitura);
            if (result) {
                aoLer(result.getText ? result.getText() : ('' + result));
                return true;
            }
        } catch (e) {
            // NotFoundException = nada nesse quadro; segue tentando.
        }
        return false;
    }

    function laco(minhaGeracao) {
        if (!ativo || minhaGeracao !== geracao) { return; }
        var vw = video.videoWidth || 0, vh = video.videoHeight || 0;
        quadros++;

        // ---- Caminho 1 (PREFERIDO): detector NATIVO (ML Kit no Android Chrome) ----
        // Decodifica o QUADRO INTEIRO; tolera o blur do celular MUITO melhor que o ZXing.
        if (detectorNativo && !nativoFalhou) {
            if (!vw || !vh) {
                loopTimer = setTimeout(function () { laco(minhaGeracao); }, 120);
                return;
            }
            setDiag('Leitor nativo • ' + vw + 'x' + vh + ' • q' + quadros);
            detectorNativo.detect(video).then(function (cods) {
                if (!ativo || minhaGeracao !== geracao) { return; }
                if (cods && cods.length) {
                    var bruto = escolherCodigoNativo(cods);
                    setDiag('Nativo leu ' + ('' + bruto).replace(/\D/g, '').length + ' díg [' + (cods[0].format || '?') + ']');
                    try { aoLer(bruto); } catch (ePipe) {}
                }
            }).catch(function (err) {
                // InvalidStateError = video ainda nao pronto (transiente): nao desliga.
                if (err && err.name && err.name !== 'InvalidStateError') {
                    nativoFalhou = true;
                    setDiag('Nativo indisponível (' + err.name + ') — usando ZXing');
                }
            }).then(function () {
                if (!ativo || minhaGeracao !== geracao) { return; }
                loopTimer = setTimeout(function () { laco(minhaGeracao); }, 120);
            });
            return; // o reagendamento acontece na continuacao da promise (1 detect por vez)
        }

        // ---- Caminho 2 (FALLBACK): ZXing por REGIAO DE INTERESSE (ROI) ----
        try { decodificarRegiao(); } catch (e) {}
        if (vw && vh) { setDiag('ZXing • ' + vw + 'x' + vh + ' • q' + quadros); }
        // Reagenda DEPOIS de terminar o decode (evita acumular quadros).
        loopTimer = setTimeout(function () { laco(minhaGeracao); }, 180);
    }

    function configurarTorch() {
        try {
            var track = stream && stream.getVideoTracks ? stream.getVideoTracks()[0] : null;
            var caps = track && track.getCapabilities ? track.getCapabilities() : {};
            if (btnTorch) { btnTorch.style.display = (caps && caps.torch) ? '' : 'none'; }
        } catch (e) { if (btnTorch) { btnTorch.style.display = 'none'; } }
    }

    function aplicarTorch(ligar) {
        try {
            var track = stream && stream.getVideoTracks ? stream.getVideoTracks()[0] : null;
            if (!track || !track.applyConstraints) { return; }
            var caps = track.getCapabilities ? track.getCapabilities() : {};
            if (!caps || !caps.torch) {
                if (btnTorch) { btnTorch.style.display = 'none'; }
                return;
            }
            track.applyConstraints({ advanced: [{ torch: !!ligar }] });
            torchOn = !!ligar;
        } catch (e) {}
    }

    // Toque na imagem: pede refoco (best-effort) e dispara uma leitura imediata.
    function tocarParaFocar() {
        try {
            var track = stream && stream.getVideoTracks ? stream.getVideoTracks()[0] : null;
            if (track && track.applyConstraints) {
                try { track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }); } catch (e) {}
            }
        } catch (e) {}
        try { decodificarRegiao(); } catch (e) {}
    }

    function abrir() {
        if (ativo || abrindo) { return; }  // ja aberto / abrindo: ignora clique repetido
        limparErro();
        overlay.className = 'cam-scan-overlay aberto';
        overlay.setAttribute('aria-hidden', 'false');
        if (typeof ZXing === 'undefined') {
            setStatus('');
            mostrarErro('O leitor não carregou (arquivo <code>lib_zxing.min.js</code> ausente). Use o leitor físico ou digite o código.');
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
            setStatus('');
            mostrarErro(
                '<strong>A câmera ao vivo precisa de conexão segura (HTTPS).</strong><br>' +
                'Na rede interna (HTTP), libere uma vez por aparelho no Chrome do celular:<br>' +
                '1) Abra <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code><br>' +
                '2) Cole o endereço deste sistema (ex.: <code>http://' +
                (location.host || '10.15.61.169:porta') + '</code>) e marque <em>Enabled</em><br>' +
                '3) Toque em <em>Relaunch</em> e abra esta página de novo.<br>' +
                'Enquanto isso, o leitor físico e a digitação continuam funcionando.'
            );
            return;
        }
        setStatus('Abrindo câmera...');
        ultimoCodigo = '';
        abrindo = true;
        var minhaGeracao = ++geracao;  // se fechar() rodar antes do then, esta stream e descartada
        // Prepara o leitor de quadros (MultiFormatReader + hints TRY_HARDER).
        try {
            hintsLeitura = montarHints();
            leitor = new ZXing.MultiFormatReader();
            if (leitor.setHints) { leitor.setHints(hintsLeitura); }
        } catch (e) { leitor = null; }

        // Tenta o ideal e, se o aparelho recusar, cai para configuracoes mais simples.
        // Alguns Android recusam 1920x1080 ou "advanced focusMode" no proprio
        // getUserMedia (OverconstrainedError) — por isso o foco continuo vai por
        // applyConstraints DEPOIS de abrir (best-effort), nunca dentro do getUserMedia.
        var tentativasCam = [
            { audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } } },
            { audio: false, video: { facingMode: { ideal: 'environment' } } },
            { audio: false, video: { facingMode: 'environment' } },
            { audio: false, video: true }
        ];
        function pedirCamera(i) {
            return navigator.mediaDevices.getUserMedia(tentativasCam[i]).catch(function (err) {
                // Permissao negada de verdade nao adianta retentar; demais erros sim.
                if (i + 1 < tentativasCam.length && err && err.name !== 'NotAllowedError' && err.name !== 'SecurityError') {
                    return pedirCamera(i + 1);
                }
                throw err;
            });
        }
        pedirCamera(0).then(function (s) {
            abrindo = false;
            // Se o usuario fechou enquanto a permissao era pedida, descarta esta stream
            // (senao a camera fica ligada com o overlay escondido = vazamento).
            if (minhaGeracao !== geracao) {
                try {
                    var td = s.getTracks ? s.getTracks() : [];
                    for (var j = 0; j < td.length; j++) { try { td[j].stop(); } catch (e0) {} }
                } catch (e1) {}
                return;
            }
            stream = s;
            try { video.setAttribute('playsinline', ''); } catch (e) {}
            video.srcObject = s;
            var p = video.play ? video.play() : null;
            if (p && p.then) { p.then(function () {}).catch(function () {}); }
            ativo = true;
            setStatus('Aponte para o código de barras (toque para focar)');
            configurarTorch();
            // Autofoco continuo best-effort, fora do getUserMedia p/ nao recusar a abertura.
            try {
                var vt = s.getVideoTracks ? s.getVideoTracks()[0] : null;
                if (vt && vt.applyConstraints) { vt.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(function () {}); }
            } catch (eF) {}
            quadros = 0;
            criarDetectorNativo();
            if ((detectorNativo && !nativoFalhou) || (leitor && ZXing.HTMLCanvasElementLuminanceSource)) {
                laco(minhaGeracao);
            } else {
                // Sem detector nativo nem primitivas de ROI: decode continuo do ZXing.
                iniciarFallback();
            }
        }).catch(function (err) {
            abrindo = false;
            if (minhaGeracao !== geracao) { return; }  // usuario ja fechou: nao polui overlay escondido
            setStatus('');
            var nome = (err && err.name) ? err.name : 'erro';
            var dica;
            if (nome === 'NotReadableError' || nome === 'TrackStartError' || nome === 'AbortError') {
                dica = 'A câmera parece estar em uso por outra aba ou aplicativo. Feche as outras abas/apps que usam a câmera e tente de novo.';
            } else if (nome === 'NotAllowedError' || nome === 'SecurityError') {
                dica = 'Permissão da câmera negada para este site. Toque no cadeado/ⓘ ao lado do endereço, permita a <em>Câmera</em> e tente de novo.';
            } else if (nome === 'NotFoundError' || nome === 'OverconstrainedError') {
                dica = 'Não encontrei uma câmera compatível neste aparelho. Use o leitor físico ou digite o código.';
            } else {
                dica = 'Toque novamente; se persistir, use o leitor físico ou digite o código.';
            }
            mostrarErro('Não consegui abrir a câmera (' + nome + '). ' + dica);
        });
    }

    // Reserva: se o navegador/lib nao permitir o ROI, cai no decode continuo do ZXing.
    function iniciarFallback() {
        try {
            fallbackReader = new ZXing.BrowserMultiFormatReader(hintsLeitura || montarHints(), 150);
            fallbackReader.decodeFromStream(stream, 'camScanVideo', function (result) {
                if (result) { aoLer(result.getText ? result.getText() : ('' + result)); }
            });
        } catch (e) {}
    }

    function pararCamera() {
        if (loopTimer) { clearTimeout(loopTimer); loopTimer = null; }
        try { if (fallbackReader && fallbackReader.reset) { fallbackReader.reset(); } } catch (e) {}
        fallbackReader = null;
        try { if (torchOn) { aplicarTorch(false); } } catch (e) {}
        try {
            if (stream && stream.getTracks) {
                var ts = stream.getTracks();
                for (var i = 0; i < ts.length; i++) { try { ts[i].stop(); } catch (e2) {} }
            }
        } catch (e) {}
        stream = null;
        try { if (video.pause) { video.pause(); } } catch (e) {}
        try { video.srcObject = null; } catch (e) {}
    }

    function fechar() {
        ativo = false;
        abrindo = false;
        geracao++;  // invalida qualquer getUserMedia ainda pendente (descarta a stream no then)
        pararCamera();
        ultimoCodigo = '';
        overlay.className = 'cam-scan-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        // devolve o foco ao input para o leitor fisico / digitacao seguirem normais
        var inputLeitura = document.getElementById('codigo_barras');
        if (inputLeitura) {
            try { inputLeitura.focus({ preventScroll: true }); } catch (e) { try { inputLeitura.focus(); } catch (e2) {} }
        }
    }

    btnAbrir.addEventListener('click', abrir);
    if (btnFechar) { btnFechar.addEventListener('click', fechar); }
    if (btnTorch) { btnTorch.addEventListener('click', function () { aplicarTorch(!torchOn); }); }
    if (wrap) { wrap.addEventListener('click', tocarParaFocar); }
    document.addEventListener('keydown', function (e) {
        if (ativo && (e.key === 'Escape' || e.keyCode === 27)) { fechar(); }
    });
})();
</script>

<?php include __DIR__ . '/processando_overlay.php'; ?>
<?php include __DIR__ . '/util_botoes_fixos.php'; ?>

<?php include __DIR__ . '/_acess.php'; ?>
</body>
</html>
