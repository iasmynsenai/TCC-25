<?php
// buscar_anotacao.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$id = $_GET['id'] ?? '';

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM anotacoes WHERE id = ?");
    $stmt->execute([$id]);
    $anotacao = $stmt->fetch();
    
    if (!$anotacao) {
        echo json_encode(['success' => false, 'message' => 'Anotação não encontrada']);
        exit;
    }
    
    echo json_encode(['success' => true, 'anotacao' => $anotacao]);
    
} catch(Exception $e) {
    error_log("Erro ao buscar anotação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>