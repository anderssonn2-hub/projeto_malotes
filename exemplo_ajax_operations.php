<?php
// ajax_operations v9.8.4 (mesma lógica da v9.8.3, apenas revisão de fechamento de conexão)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Conexão com o banco de dados
$host = getenv('DB_HOST') ?: '10.15.61.169';
$dbname = getenv('DB_NAME') ?: 'controle';
$user = getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat');
$pass = getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $createObservacoes = "CREATE TABLE IF NOT EXISTS `observacoes_postos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `posto` varchar(15) NOT NULL,
        `observacao` text NOT NULL,
        `tipo` enum('aviso','bloqueio') NOT NULL DEFAULT 'aviso',
        `ativo` tinyint(1) NOT NULL DEFAULT 1,
        `data_criacao` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `posto` (`posto`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
    $pdo->exec($createObservacoes);

    $createConferencia = "CREATE TABLE IF NOT EXISTS `conferencia_pacotes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `regional` varchar(40) NOT NULL,
        `nlote` int(10) NOT NULL,
        `nposto` varchar(15) NOT NULL,
        `dataexp` date NOT NULL,
        `qtd` int(5) NOT NULL,
        `codbar` bigint(35) NOT NULL,
        `conf` char(1) NOT NULL DEFAULT 'n',
        PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
    $pdo->exec($createConferencia);

} catch (PDOException $e) {
    echo json_encode(array('success' => false, 'message' => 'Erro de conexão: ' . $e->getMessage()));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Método não permitido'));
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(array('success' => false, 'message' => 'Dados inválidos'));
    exit;
}

$action = isset($data['action']) ? $data['action'] : '';

switch ($action) {
    case 'salvar_conferencia':
        salvarConferencia($pdo, $data['dados']);
        break;
    case 'excluir_conferencia_data':
        excluirConferenciaPorData($pdo, $data);
        break;
    case 'salvar_observacao_posto':
        salvarObservacaoPosto($pdo, $data);
        break;
    case 'buscar_observacao_posto':
        buscarObservacaoPosto($pdo, $data);
        break;
    case 'listar_observacoes':
        listarObservacoes($pdo);
        break;
    case 'excluir_observacao_posto':
        excluirObservacaoPosto($pdo, $data);
        break;
    default:
        echo json_encode(array('success' => false, 'message' => 'Ação não reconhecida'));
        break;
}

function salvarConferencia($pdo, $dados) {
    try {
        $dataPartes = explode('-', $dados['dataexp']);
        if (count($dataPartes) === 3) {
            $dataFormatada = $dataPartes[2] . '-' . $dataPartes[1] . '-' . $dataPartes[0];
        } else {
            throw new Exception('Formato de data inválido');
        }

        $stmt = $pdo->prepare("SELECT id FROM conferencia_pacotes WHERE nlote = ? AND regional = ? AND nposto = ?");
        $stmt->execute(array(
            (int)$dados['nlote'], 
            $dados['regional'], 
            $dados['nposto']
        ));

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE conferencia_pacotes SET conf = 's', qtd = ?, codbar = ?, dataexp = ? WHERE nlote = ? AND regional = ? AND nposto = ?");
            $stmt->execute(array(
                (int)$dados['qtd'],
                (int)$dados['codbar'],
                $dataFormatada,
                (int)$dados['nlote'],
                $dados['regional'],
                $dados['nposto']
            ));
        } else {
            $stmt = $pdo->prepare("INSERT INTO conferencia_pacotes (regional, nlote, nposto, dataexp, qtd, codbar, conf) VALUES (?, ?, ?, ?, ?, ?, 's')");
            $stmt->execute(array(
                $dados['regional'],
                (int)$dados['nlote'],
                $dados['nposto'],
                $dataFormatada,
                (int)$dados['qtd'],
                (int)$dados['codbar']
            ));
        }

        echo json_encode(array('success' => true, 'message' => 'Conferência salva com sucesso'));

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()));
    }
}

function excluirConferenciaPorData($pdo, $data) {
    try {
        $dataConferencia = $data['data'];
        $dataPartes = explode('-', $dataConferencia);
        if (count($dataPartes) === 3 && strlen($dataPartes[0]) === 2) {
            $dataFormatada = $dataPartes[2] . '-' . $dataPartes[1] . '-' . $dataPartes[0];
        } else {
            $dataFormatada = $dataConferencia;
        }

        $stmt = $pdo->prepare("DELETE FROM conferencia_pacotes WHERE dataexp = ?");
        $stmt->execute(array($dataFormatada));

        echo json_encode(array(
            'success' => true,
            'message' => "Excluídos " . $stmt->rowCount() . " registros da data $dataConferencia"
        ));
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()));
    }
}

function salvarObservacaoPosto($pdo, $data) {
    try {
        $posto = $data['posto'];
        $observacao = $data['observacao'];
        $tipo = isset($data['tipo']) ? $data['tipo'] : 'aviso';

        $stmt = $pdo->prepare("SELECT id FROM observacoes_postos WHERE posto = ?");
        $stmt->execute(array($posto));

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE observacoes_postos SET observacao = ?, tipo = ?, ativo = 1 WHERE posto = ?");
            $stmt->execute(array($observacao, $tipo, $posto));
        } else {
            $stmt = $pdo->prepare("INSERT INTO observacoes_postos (posto, observacao, tipo, data_criacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute(array($posto, $observacao, $tipo));
        }

        echo json_encode(array('success' => true, 'message' => 'Observação salva com sucesso'));

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao salvar observação: ' . $e->getMessage()));
    }
}

function buscarObservacaoPosto($pdo, $data) {
    try {
        $posto = $data['posto'];
        $stmt = $pdo->prepare("SELECT observacao, tipo FROM observacoes_postos WHERE posto = ? AND ativo = 1");
        $stmt->execute(array($posto));
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            echo json_encode(array(
                'success' => true,
                'tem_observacao' => true,
                'observacao' => $resultado['observacao'],
                'tipo' => $resultado['tipo']
            ));
        } else {
            echo json_encode(array('success' => true, 'tem_observacao' => false));
        }

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao buscar observação: ' . $e->getMessage()));
    }
}

function listarObservacoes($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT posto, observacao, tipo, data_criacao FROM observacoes_postos WHERE ativo = 1 ORDER BY posto ASC");
        $stmt->execute();
        $observacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array('success' => true, 'observacoes' => $observacoes));

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao listar observações: ' . $e->getMessage()));
    }
}

function excluirObservacaoPosto($pdo, $data) {
    try {
        $posto = $data['posto'];
        $stmt = $pdo->prepare("DELETE FROM observacoes_postos WHERE posto = ?");
        $stmt->execute(array($posto));

        if ($stmt->rowCount() > 0) {
            echo json_encode(array('success' => true, 'message' => 'Observação excluída com sucesso'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Observação não encontrada'));
        }

    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erro ao excluir observação: ' . $e->getMessage()));
    }
}

// v9.8.3: fecha conexão
if (isset($pdo)) { $pdo = null; }
?>