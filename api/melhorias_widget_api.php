<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start();
}

function melhorias_json($payload, $statusCode) {
    if (function_exists('http_response_code')) {
        http_response_code($statusCode);
    }
    die(json_encode($payload));
}

function melhorias_normalizar_texto($valor, $maxLen) {
    $valor = trim((string)$valor);
    if ($maxLen > 0 && strlen($valor) > $maxLen) {
        $valor = substr($valor, 0, $maxLen);
    }
    return $valor;
}

function melhorias_obter_pdo() {
    $pdo = new PDO(
        (getenv('DB_HOST') ? 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8' : 'mysql:host=' . (getenv('DB_HOST') ?: (getenv('DB_HOST') ?: '10.15.61.169')) . ';dbname=' . (getenv('DB_NAME') ?: (getenv('DB_NAME') ?: 'controle')) . ';charset=utf8'),
        (getenv('DB_USER') ?: (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'))),
        (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256')))
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function melhorias_garantir_tabela(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciMelhoriasProjeto (
        id INT NOT NULL AUTO_INCREMENT,
        titulo VARCHAR(120) NOT NULL,
        descricao TEXT NULL,
        pagina VARCHAR(120) NULL,
        status ENUM('pendente','implementado') NOT NULL DEFAULT 'pendente',
        criado_por VARCHAR(80) NULL,
        atualizado_por VARCHAR(80) NULL,
        criado_em DATETIME NOT NULL,
        atualizado_em DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_status (status),
        KEY idx_pagina (pagina),
        KEY idx_atualizado_em (atualizado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

try {
    $pdo = melhorias_obter_pdo();
    melhorias_garantir_tabela($pdo);

    $acao = isset($_REQUEST['acao']) ? trim((string)$_REQUEST['acao']) : 'listar';
    $usuario = isset($_SESSION['usuario']) && trim((string)$_SESSION['usuario']) !== ''
        ? trim((string)$_SESSION['usuario'])
        : 'sistema';

    if ($acao === 'listar') {
        $stmt = $pdo->query("SELECT id, titulo, descricao, pagina, status, criado_por, atualizado_por, criado_em, atualizado_em
                             FROM ciMelhoriasProjeto
                             ORDER BY atualizado_em DESC, id DESC");
        melhorias_json(array('success' => true, 'itens' => $stmt->fetchAll()), 200);
    }

    if ($acao === 'criar') {
        $titulo = melhorias_normalizar_texto(isset($_POST['titulo']) ? $_POST['titulo'] : '', 120);
        $descricao = melhorias_normalizar_texto(isset($_POST['descricao']) ? $_POST['descricao'] : '', 2000);
        $pagina = melhorias_normalizar_texto(isset($_POST['pagina']) ? $_POST['pagina'] : '', 120);
        $status = melhorias_normalizar_texto(isset($_POST['status']) ? $_POST['status'] : 'pendente', 20);

        if ($titulo === '') {
            melhorias_json(array('success' => false, 'erro' => 'Titulo obrigatorio'), 400);
        }
        if ($status !== 'pendente' && $status !== 'implementado') {
            $status = 'pendente';
        }

        $stmt = $pdo->prepare("INSERT INTO ciMelhoriasProjeto (titulo, descricao, pagina, status, criado_por, atualizado_por, criado_em, atualizado_em)
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute(array($titulo, $descricao, $pagina, $status, $usuario, $usuario));
        melhorias_json(array('success' => true, 'id' => (int)$pdo->lastInsertId()), 200);
    }

    if ($acao === 'atualizar_status') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = melhorias_normalizar_texto(isset($_POST['status']) ? $_POST['status'] : 'pendente', 20);
        if ($id <= 0) {
            melhorias_json(array('success' => false, 'erro' => 'ID invalido'), 400);
        }
        if ($status !== 'pendente' && $status !== 'implementado') {
            melhorias_json(array('success' => false, 'erro' => 'Status invalido'), 400);
        }
        $stmt = $pdo->prepare("UPDATE ciMelhoriasProjeto SET status = ?, atualizado_por = ?, atualizado_em = NOW() WHERE id = ?");
        $stmt->execute(array($status, $usuario, $id));
        melhorias_json(array('success' => true), 200);
    }

    if ($acao === 'excluir') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            melhorias_json(array('success' => false, 'erro' => 'ID invalido'), 400);
        }
        $stmt = $pdo->prepare("DELETE FROM ciMelhoriasProjeto WHERE id = ?");
        $stmt->execute(array($id));
        melhorias_json(array('success' => true), 200);
    }

    melhorias_json(array('success' => false, 'erro' => 'Acao nao suportada'), 400);
} catch (Exception $e) {
    melhorias_json(array('success' => false, 'erro' => $e->getMessage()), 500);
}