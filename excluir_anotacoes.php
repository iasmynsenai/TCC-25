<?php
// excluir_anotacao.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    
    // Buscar a anotação para verificar se existe e pegar os anexos
    $stmt = $pdo->prepare("SELECT anexos FROM anotacoes WHERE id = ?");
    $stmt->execute([$id]);
    $anotacao = $stmt->fetch();
    
    if (!$anotacao) {
        echo json_encode(['success' => false, 'message' => 'Anotação não encontrada']);
        exit;
    }
    
    // Excluir arquivos anexos se existirem
    if (!empty($anotacao['anexos'])) {
        $anexos = json_decode($anotacao['anexos'], true);
        if (is_array($anexos)) {
            foreach ($anexos as $arquivo) {
                $caminho_arquivo = 'uploads/anotacoes/' . $arquivo;
                if (file_exists($caminho_arquivo)) {
                    unlink($caminho_arquivo);
                }
            }
        }
    }
    
    // Excluir a anotação do banco
    $stmt = $pdo->prepare("DELETE FROM anotacoes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Anotação excluída com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir anotação']);
    }
    
} catch(Exception $e) {
    error_log("Erro ao excluir anotação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>