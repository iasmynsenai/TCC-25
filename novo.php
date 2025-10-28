<?php
// novo.php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        
        // Validar dados obrigatórios
        $nome_crianca = trim($_POST['nome_crianca'] ?? '');
        $idade = $_POST['idade'] ?? '';
        $data_desaparecimento = $_POST['data_desaparecimento'] ?? '';
        $local_desaparecimento = trim($_POST['local_desaparecimento'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $contato_responsavel = trim($_POST['contato_responsavel'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $informacoes_adicionais = trim($_POST['informacoes_adicionais'] ?? '');
        
        if (empty($nome_crianca) || empty($idade) || empty($data_desaparecimento) || 
            empty($local_desaparecimento) || empty($descricao) || empty($contato_responsavel)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
        }
        
        // Validar idade
        if (!is_numeric($idade) || $idade < 0 || $idade > 18) {
            throw new Exception('Idade deve ser um número entre 0 e 18 anos');
        }
        
        // Validar data
        $data_obj = DateTime::createFromFormat('Y-m-d', $data_desaparecimento);
        if (!$data_obj || $data_obj > new DateTime()) {
            throw new Exception('Data de desaparecimento inválida');
        }
        
        // Processar upload da foto
        $foto_nome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/fotos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_nome = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $foto_nome;
            
            // Validar tipo de arquivo
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG ou GIF');
            }
            
            // Validar tamanho (máximo 5MB)
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Arquivo muito grande. Máximo 5MB');
            }
            
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                throw new Exception('Erro ao fazer upload da foto');
            }
        }
        
        // Inserir no banco de dados
        $sql = "INSERT INTO casos (nome_crianca, idade, data_desaparecimento, local_desaparecimento, 
                descricao, foto, contato_responsavel, telefone, email, informacoes_adicionais) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_crianca, $idade, $data_desaparecimento, $local_desaparecimento,
            $descricao, $foto_nome, $contato_responsavel, $telefone, $email, $informacoes_adicionais
        ]);
        
        $caso_id = $pdo->lastInsertId();
        
        $mensagem = 'Caso registrado com sucesso! ID do caso: ' . $caso_id;
        $tipo_mensagem = 'success';
        
        // Limpar formulário
        $_POST = [];
        
    } catch(Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("Erro ao cadastrar caso: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Caso - MCA</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-search-location logo-icon"></i>
                <div>
                    <h1>MCA</h1>
                    <span>Missing Child Alert</span>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="casos.php">Casos</a></li>
                    <li><a href="dicas.php">Dicas</a></li>
                    <li><a href="anotacoes.php">Anotações</a></li>
                    <li><a href="novo.php" class="active">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Registrar Novo Caso</h1>
            <p>Preencha todas as informações disponíveis sobre o desaparecimento</p>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form action="novo.php" method="POST" enctype="multipart/form-data" id="form-novo-caso">
                    <div class="form-section-title">
                        <h2><i class="fas fa-child"></i> Informações da Criança</h2>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_crianca">Nome Completo *</label>
                            <input type="text" id="nome_crianca" name="nome_crianca" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['nome_crianca'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="idade">Idade *</label>
                            <input type="number" id="idade" name="idade" class="form-control" min="0" max="18"
                                   value="<?php echo htmlspecialchars($_POST['idade'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="foto">Foto da Criança</label>
                        <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                        <small>Formatos aceitos: JPG, PNG, GIF. Máximo 5MB.</small>
                    </div>

                    <div class="form-section-title">
                        <h2><i class="fas fa-map-marker-alt"></i> Informações do Desaparecimento</h2>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_desaparecimento">Data do Desaparecimento *</label>
                            <input type="date" id="data_desaparecimento" name="data_desaparecimento" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['data_desaparecimento'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="local_desaparecimento">Local do Desaparecimento *</label>
                            <input type="text" id="local_desaparecimento" name="local_desaparecimento" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['local_desaparecimento'] ?? ''); ?>" 
                                   placeholder="Ex: Shopping Center, Escola, Parque..." required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição do Ocorrido *</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="4" required
                                  placeholder="Descreva as circunstâncias do desaparecimento, roupas que a criança estava usando, características físicas, etc."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="informacoes_adicionais">Informações Adicionais</label>
                        <textarea id="informacoes_adicionais" name="informacoes_adicionais" class="form-control" rows="3"
                                  placeholder="Qualquer informação adicional que possa ser útil..."><?php echo htmlspecialchars($_POST['informacoes_adicionais'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-section-title">
                        <h2><i class="fas fa-phone"></i> Informações de Contato</h2>
                    </div>

                    <div class="form-group">
                        <label for="contato_responsavel">Nome do Responsável *</label>
                        <input type="text" id="contato_responsavel" name="contato_responsavel" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['contato_responsavel'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" 
                                   placeholder="(11) 99999-9999">
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="exemplo@email.com">
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Importante:</strong> Após o registro, o caso será disponibilizado publicamente para ajudar na busca. 
                        Certifique-se de que todas as informações estão corretas.
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-save"></i> Registrar Caso
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpar Formulário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 MCA - Missing Child Alert. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Validação do formulário
        document.getElementById('form-novo-caso').addEventListener('submit', function(e) {
            const telefone = document.getElementById('telefone').value;
            const email = document.getElementById('email').value;
            
            // Pelo menos um contato deve ser fornecido
            if (!telefone && !email) {
                e.preventDefault();
                alert('Por favor, forneça pelo menos um meio de contato (telefone ou e-mail).');
                return;
            }
            
            // Validar formato do telefone se fornecido
            if (telefone && !/^\(\d{2}\)\s\d{4,5}-\d{4}$/.test(telefone)) {
                e.preventDefault();
                alert('Por favor, use o formato (11) 99999-9999 para o telefone.');
                return;
            }
            
            // Confirmar submissão
            if (!confirm('Tem certeza que deseja registrar este caso? Todas as informações serão tornadas públicas.')) {
                e.preventDefault();
            }
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });
        
        // Limitar data máxima para hoje
        document.getElementById('data_desaparecimento').max = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>