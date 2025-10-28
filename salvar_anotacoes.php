<?php
// salvar_anotacao.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $pdo = getDB();
    
    // Validar dados obrigatórios
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $caso_id = $_POST['caso_id'] ?? '';
    $conteudo = trim($_POST['conteudo'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $urgente = isset($_POST['urgente']) ? 1 : 0;
    $anotacao_id = $_POST['anotacao_id'] ?? '';
    
    if (empty($titulo) || empty($categoria) || empty($caso_id) || empty($conteudo)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
        exit;
    }
    
    // Verificar se o caso existe
    $stmt = $pdo->prepare("SELECT id FROM casos WHERE id = ?");
    $stmt->execute([$caso_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Caso não encontrado']);
        exit;
    }
    
    // Processar upload de arquivos
    $anexos_salvos = [];
    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        $upload_dir = 'uploads/anotacoes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['anexos']['name'] as $key => $filename) {
            if ($_FILES['anexos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // Validar tipo de arquivo
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'mp4', 'avi'];
                if (in_array(strtolower($file_extension), $allowed_types)) {
                    if (move_uploaded_file($_FILES['anexos']['tmp_name'][$key], $upload_path)) {
                        $anexos_salvos[] = $new_filename;
                    }
                }
            }
        }
    }
    
    $anexos_json = !empty($anexos_salvos) ? json_encode($anexos_salvos) : null;
    
    if (!empty($anotacao_id)) {
        // Atualizar anotação existente
        $sql = "UPDATE anotacoes SET 
                titulo = ?, categoria = ?, caso_id = ?, conteudo = ?, 
                tags = ?, urgente = ?, anexos = COALESCE(?, anexos), 
                data_atualizacao = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $params = [$titulo, $categoria, $caso_id, $conteudo, $tags, $urgente, $anexos_json, $anotacao_id];
    } else {
        // Criar nova anotação
        $sql = "INSERT INTO anotacoes (titulo, categoria, caso_id, conteudo, tags, urgente, anexos) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$titulo, $categoria, $caso_id, $conteudo, $tags, $urgente, $anexos_json];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if (!empty($anotacao_id)) {
        echo json_encode(['success' => true, 'message' => 'Anotação atualizada com sucesso']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Anotação criada com sucesso']);
    }
    
    // Redirecionar para a página de anotações
    header('Location: anotacoes.php?success=1');
    exit;
    
} catch(Exception $e) {
    error_log("Erro ao salvar anotação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>