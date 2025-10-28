<?php
// dicas.php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';
$caso_selecionado = $_GET['caso_id'] ?? '';

// Processar envio de nova dica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        
        $caso_id = $_POST['caso_id'] ?? '';
        $descricao = trim($_POST['descricao'] ?? '');
        $nome_contato = trim($_POST['nome_contato'] ?? '');
        $telefone_contato = trim($_POST['telefone_contato'] ?? '');
        $email_contato = trim($_POST['email_contato'] ?? '');
        
        if (empty($caso_id) || empty($descricao)) {
            throw new Exception('Caso e descrição são obrigatórios');
        }
        
        // Verificar se o caso existe
        $stmt = $pdo->prepare("SELECT nome_crianca FROM casos WHERE id = ?");
        $stmt->execute([$caso_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Caso não encontrado');
        }
        
        // Inserir dica
        $sql = "INSERT INTO dicas (caso_id, descricao, nome_contato, telefone_contato, email_contato) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$caso_id, $descricao, $nome_contato, $telefone_contato, $email_contato]);
        
        $mensagem = 'Dica enviada com sucesso! Obrigado pela sua colaboração.';
        $tipo_mensagem = 'success';
        
        // Limpar formulário
        $_POST = [];
        
    } catch(Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("Erro ao enviar dica: " . $e->getMessage());
    }
}

try {
    $pdo = getDB();
    
    // Buscar casos ativos para o formulário
    $stmt = $pdo->query("SELECT id, nome_crianca, local_desaparecimento FROM casos WHERE status = 'ativo' ORDER BY data_desaparecimento DESC");
    $casos_ativos = $stmt->fetchAll();
    
    // Buscar dicas recentes (apenas as verificadas para mostrar publicamente)
    $stmt = $pdo->query("
        SELECT d.*, c.nome_crianca, c.local_desaparecimento 
        FROM dicas d 
        JOIN casos c ON d.caso_id = c.id 
        WHERE d.status = 'verificada' 
        ORDER BY d.data_criacao DESC 
        LIMIT 10
    ");
    $dicas_verificadas = $stmt->fetchAll();
    
    // Estatísticas
    $stats = [
        'total_dicas' => $pdo->query("SELECT COUNT(*) FROM dicas")->fetchColumn(),
        'dicas_pendentes' => $pdo->query("SELECT COUNT(*) FROM dicas WHERE status = 'pendente'")->fetchColumn(),
        'dicas_verificadas' => $pdo->query("SELECT COUNT(*) FROM dicas WHERE status = 'verificada'")->fetchColumn(),
        'casos_com_dicas' => $pdo->query("SELECT COUNT(DISTINCT caso_id) FROM dicas")->fetchColumn()
    ];
    
} catch(Exception $e) {
    $casos_ativos = [];
    $dicas_verificadas = [];
    $stats = ['total_dicas' => 0, 'dicas_pendentes' => 0, 'dicas_verificadas' => 0, 'casos_com_dicas' => 0];
    error_log("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dicas - MCA</title>
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
                    <li><a href="dicas.php" class="active">Dicas</a></li>
                    <li><a href="anotacoes.php">Anotações</a></li>
                    <li><a href="novo.php">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Envie uma Dica</h1>
            <p>Sua informação pode ser a chave para encontrar uma criança</p>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="stats-section small">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_dicas']; ?></h3>
                        <p>Dicas Recebidas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['dicas_pendentes']; ?></h3>
                        <p>Aguardando Verificação</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['dicas_verificadas']; ?></h3>
                        <p>Dicas Verificadas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['casos_com_dicas']; ?></h3>
                        <p>Casos com Dicas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dicas-section">
        <div class="container">
            <div class="dicas-layout">
                <!-- Formulário de Nova Dica -->
                <div class="dica-form-container">
                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                            <?php echo htmlspecialchars($mensagem); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-card">
                        <h2><i class="fas fa-lightbulb"></i> Enviar Nova Dica</h2>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Qualquer informação, por menor que pareça, pode ser valiosa. 
                            Todas as dicas são analisadas cuidadosamente.
                        </div>

                        <form action="dicas.php" method="POST" id="form-dica">
                            <div class="form-group">
                                <label for="caso_id">Caso Relacionado *</label>
                                <select name="caso_id" id="caso_id" class="form-control" required>
                                    <option value="">Selecione o caso...</option>
                                    <?php foreach($casos_ativos as $caso): ?>
                                        <option value="<?php echo $caso['id']; ?>" 
                                                <?php echo $caso_selecionado == $caso['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($caso['nome_crianca']); ?> - 
                                            <?php echo htmlspecialchars($caso['local_desaparecimento']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="descricao">Descrição da Dica *</label>
                                <textarea name="descricao" id="descricao" class="form-control" rows="4" required
                                          placeholder="Descreva detalhadamente o que você viu, ouviu ou sabe sobre o caso..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-section-title">
                                <h3>Informações de Contato (Opcional)</h3>
                                <p>Deixe seus dados caso precisemos entrar em contato para mais informações</p>
                            </div>

                            <div class="form-group">
                                <label for="nome_contato">Seu Nome</label>
                                <input type="text" name="nome_contato" id="nome_contato" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['nome_contato'] ?? ''); ?>" 
                                       placeholder="Nome (pode ser apenas primeiro nome)">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefone_contato">Telefone</label>
                                    <input type="tel" name="telefone_contato" id="telefone_contato" class="form-control"
                                           value="<?php echo htmlspecialchars($_POST['telefone_contato'] ?? ''); ?>" 
                                           placeholder="(11) 99999-9999">
                                </div>
                                <div class="form-group">
                                    <label for="email_contato">E-mail</label>
                                    <input type="email" name="email_contato" id="email_contato" class="form-control"
                                           value="<?php echo htmlspecialchars($_POST['email_contato'] ?? ''); ?>" 
                                           placeholder="exemplo@email.com">
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-shield-alt"></i>
                                <strong>Privacidade:</strong> Seus dados de contato são confidenciais e só serão usados 
                                se necessário para esclarecimentos sobre sua dica.
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <i class="fas fa-paper-plane"></i> Enviar Dica
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Limpar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Dicas Recentes Verificadas -->
                <div class="dicas-recentes">
                    <h2><i class="fas fa-check-circle"></i> Dicas Verificadas Recentes</h2>
                    
                    <?php if (empty($dicas_verificadas)): ?>
                        <div class="no-dicas">
                            <i class="fas fa-search"></i>
                            <p>Nenhuma dica verificada ainda.</p>
                        </div>
                    <?php else: ?>
                        <div class="dicas-lista">
                            <?php foreach($dicas_verificadas as $dica): ?>
                            <div class="dica-card">
                                <div class="dica-header">
                                    <div class="dica-caso">
                                        <strong><?php echo htmlspecialchars($dica['nome_crianca']); ?></strong>
                                        <span><?php echo htmlspecialchars($dica['local_desaparecimento']); ?></span>
                                    </div>
                                    <div class="dica-data">
                                        <?php echo date('d/m/Y', strtotime($dica['data_criacao'])); ?>
                                    </div>
                                </div>
                                <div class="dica-conteudo">
                                    <p><?php echo nl2br(htmlspecialchars($dica['descricao'])); ?></p>
                                </div>
                                <div class="dica-status">
                                    <span class="status-badge status-verificada">
                                        <i class="fas fa-check"></i> Verificada
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Informações de Segurança -->
    <section class="info-section">
        <div class="container">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>O que Observar</h3>
                    <ul>
                        <li>Crianças sozinhas em locais inadequados</li>
                        <li>Adultos com comportamento suspeito próximo a crianças</li>
                        <li>Crianças que parecem assustadas ou perdidas</li>
                        <li>Veículos suspeitos próximos a escolas ou parques</li>
                    </ul>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Contatos de Emergência</h3>
                    <ul>
                        <li><strong>190:</strong> Polícia Militar</li>
                        <li><strong>197:</strong> Polícia Civil</li>
                        <li><strong>100:</strong> Disque Denúncia</li>
                        <li><strong>181:</strong> Disque Denúncia SP</li>
                    </ul>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Sua Segurança</h3>
                    <ul>
                        <li>Nunca se coloque em risco</li>
                        <li>Mantenha distância segura</li>
                        <li>Anote detalhes importantes</li>
                        <li>Chame as autoridades imediatamente</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 MCA - Missing Child Alert. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Máscara para telefone
        document.getElementById('telefone_contato').addEventListener('input', function(e) {
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

        // Validação do formulário
        document.getElementById('form-dica').addEventListener('submit', function(e) {
            const descricao = document.getElementById('descricao').value.trim();
            
            if (descricao.length < 10) {
                e.preventDefault();
                alert('Por favor, forneça uma descrição mais detalhada (mínimo 10 caracteres).');
                return;
            }
            
            if (!confirm('Tem certeza que deseja enviar esta dica?')) {
                e.preventDefault();