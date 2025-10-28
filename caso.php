<?php
// caso.php
require_once 'config.php';

$caso_id = $_GET['id'] ?? '';

if (empty($caso_id) || !is_numeric($caso_id)) {
    header('Location: casos.php');
    exit;
}

try {
    $pdo = getDB();
    
    // Buscar dados do caso
    $stmt = $pdo->prepare("SELECT * FROM casos WHERE id = ?");
    $stmt->execute([$caso_id]);
    $caso = $stmt->fetch();
    
    if (!$caso) {
        header('Location: casos.php');
        exit;
    }
    
    // Buscar anotações do caso
    $stmt = $pdo->prepare("SELECT * FROM anotacoes WHERE caso_id = ? ORDER BY urgente DESC, data_criacao DESC");
    $stmt->execute([$caso_id]);
    $anotacoes = $stmt->fetchAll();
    
    // Buscar dicas verificadas do caso
    $stmt = $pdo->prepare("SELECT * FROM dicas WHERE caso_id = ? AND status = 'verificada' ORDER BY data_criacao DESC");
    $stmt->execute([$caso_id]);
    $dicas = $stmt->fetchAll();
    
} catch(Exception $e) {
    error_log("Erro ao buscar caso: " . $e->getMessage());
    header('Location: casos.php');
    exit;
}

$categoria_icones = [
    'pista' => '🔍',
    'avistamento' => '👁️',
    'contato' => '📞',
    'observacao' => '📝',
    'urgente' => '⚠️'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($caso['nome_crianca']); ?> - MCA</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Meta tags para compartilhamento -->
    <meta property="og:title" content="Ajude a encontrar <?php echo htmlspecialchars($caso['nome_crianca']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($caso['descricao'], 0, 150)); ?>...">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php if($caso['foto']): ?>
        <meta property="og:image" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . "/uploads/fotos/" . $caso['foto']; ?>">
    <?php endif; ?>
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
                    <li><a href="novo.php">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="caso-detalhes">
        <div class="container">
            <!-- Cabeçalho do Caso -->
            <div class="caso-header">
                <div class="caso-foto-grande">
                    <?php if($caso['foto']): ?>
                        <img src="uploads/fotos/<?php echo htmlspecialchars($caso['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($caso['nome_crianca']); ?>">
                    <?php else: ?>
                        <div class="no-photo-large">
                            <i class="fas fa-user"></i>
                            <p>Sem foto disponível</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="caso-info-principal">
                    <div class="caso-status-badge status-<?php echo $caso['status']; ?>">
                        <?php echo ucfirst($caso['status']); ?>
                    </div>
                    
                    <h1><?php echo htmlspecialchars($caso['nome_crianca']); ?></h1>
                    
                    <div class="caso-detalhes-basicos">
                        <div class="detalhe">
                            <i class="fas fa-birthday-cake"></i>
                            <span><strong>Idade:</strong> <?php echo $caso['idade']; ?> anos</span>
                        </div>
                        <div class="detalhe">
                            <i class="fas fa-calendar"></i>
                            <span><strong>Desaparecimento:</strong> <?php echo date('d/m/Y', strtotime($caso['data_desaparecimento'])); ?></span>
                        </div>
                        <div class="detalhe">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Local:</strong> <?php echo htmlspecialchars($caso['local_desaparecimento']); ?></span>
                        </div>
                        <div class="detalhe">
                            <i class="fas fa-clock"></i>
                            <span><strong>Dias desaparecida:</strong> <?php echo (new DateTime())->diff(new DateTime($caso['data_desaparecimento']))->days; ?> dias</span>
                        </div>
                    </div>
                    
                    <div class="caso-actions-principais">
                        <a href="dicas.php?caso_id=<?php echo $caso['id']; ?>" class="btn btn-primary btn-large">
                            <i class="fas fa-lightbulb"></i> Enviar Dica
                        </a>
                        <button class="btn btn-secondary" onclick="compartilharCaso()">
                            <i class="fas fa-share"></i> Compartilhar
                        </button>
                        <button class="btn btn-outline" onclick="imprimirCaso()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>

            <div class="caso-conteudo">
                <!-- Descrição -->
                <div class="secao-caso">
                    <h2><i class="fas fa-info-circle"></i> Descrição do Caso</h2>
                    <div class="caso-descricao">
                        <p><?php echo nl2br(htmlspecialchars($caso['descricao'])); ?></p>
                        
                        <?php if(!empty($caso['informacoes_adicionais'])): ?>
                            <h4>Informações Adicionais:</h4>
                            <p><?php echo nl2br(htmlspecialchars($caso['informacoes_adicionais'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contato -->
                <div class="secao-caso">
                    <h2><i class="fas fa-phone"></i> Informações de Contato</h2>
                    <div class="caso-contato">
                        <div class="contato-item">
                            <strong>Responsável:</strong> <?php echo htmlspecialchars($caso['contato_responsavel']); ?>
                        </div>
                        <?php if(isset($caso['telefone']) && !empty($caso['telefone'])): ?>
                            <div class="contato-item">
                                <strong>Telefone:</strong> 
                                <a href="tel:<?php echo $caso['telefone']; ?>"><?php echo htmlspecialchars($caso['telefone']); ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($caso['email']) && !empty($caso['email'])): ?>
                            <div class="contato-item">
                                <strong>E-mail:</strong> 
                                <a href="mailto:<?php echo $caso['email']; ?>"><?php echo htmlspecialchars($caso['email']); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Anotações -->
                <?php if(!empty($anotacoes)): ?>
                <div class="secao-caso">
                    <h2><i class="fas fa-sticky-note"></i> Anotações e Pistas (<?php echo count($anotacoes); ?>)</h2>
                    <div class="anotacoes-caso">
                        <?php foreach($anotacoes as $anotacao): ?>
                        <div class="anotacao-resumo <?php echo $anotacao['urgente'] ? 'urgente' : ''; ?>">
                            <div class="anotacao-header-resumo">
                                <div class="anotacao-categoria-resumo categoria-<?php echo $anotacao['categoria']; ?>">
                                    <?php echo $categoria_icones[$anotacao['categoria']] ?? '📝'; ?> 
                                    <?php echo ucfirst($anotacao['categoria']); ?>
                                </div>
                                <div class="anotacao-data-resumo">
                                    <?php echo date('d/m/Y H:i', strtotime($anotacao['data_criacao'])); ?>
                                </div>
                                <?php if($anotacao['urgente']): ?>
                                    <div class="urgente-badge">URGENTE</div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($anotacao['titulo']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($anotacao['conteudo'])); ?></p>
                            
                            <?php if($anotacao['tags']): ?>
                                <div class="tags-resumo">
                                    <?php 
                                    $tags = explode(',', $anotacao['tags']);
                                    foreach($tags as $tag): 
                                        $tag = trim($tag);
                                        if(!empty($tag)):
                                    ?>
                                        <span class="tag-resumo"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Dicas Verificadas -->
                <?php if(!empty($dicas)): ?>
                <div class="secao-caso">
                    <h2><i class="fas fa-lightbulb"></i> Dicas Verificadas (<?php echo count($dicas); ?>)</h2>
                    <div class="dicas-caso">
                        <?php foreach($dicas as $dica): ?>
                        <div class="dica-resumo">
                            <div class="dica-header-resumo">
                                <div class="dica-status-resumo">
                                    <i class="fas fa-check-circle"></i> Verificada
                                </div>
                                <div class="dica-data-resumo">
                                    <?php echo date('d/m/Y', strtotime($dica['data_criacao'])); ?>
                                </div>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($dica['descricao'])); ?></p>
                            <?php if($dica['observacoes']): ?>
                                <div class="dica-observacoes">
                                    <strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($dica['observacoes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Call to Action -->
                <div class="secao-caso cta-section">
                    <h2><i class="fas fa-hands-helping"></i> Como Você Pode Ajudar</h2>
                    <div class="cta-grid">
                        <div class="cta-card">
                            <i class="fas fa-eye"></i>
                            <h3>Observe</h3>
                            <p>Fique atento a crianças com as características descritas</p>
                        </div>
                        <div class="cta-card">
                            <i class="fas fa-lightbulb"></i>
                            <h3>Informe</h3>
                            <p>Envie qualquer informação que possa ajudar</p>
                        </div>
                        <div class="cta-card">
                            <i class="fas fa-share"></i>
                            <h3>Compartilhe</h3>
                            <p>Divulgue este caso em suas redes sociais</p>
                        </div>
                        <div class="cta-card">
                            <i class="fas fa-phone"></i>
                            <h3>Chame</h3>
                            <p>Em caso de avistamento, chame 190 imediatamente</p>
                        </div>
                    </div>
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
        function compartilharCaso() {
            const url = window.location.href;
            const titulo = 'Ajude a encontrar <?php echo addslashes($caso['nome_crianca']); ?>';
            const texto = 'Criança desaparecida - <?php echo addslashes($caso['nome_crianca']); ?>, <?php echo $caso['idade']; ?> anos';
            
            if (navigator.share) {
                navigator.share({
                    title: titulo,
                    text: texto,
                    url: url
                });
            } else {
                // Fallback para navegadores sem suporte ao Web Share API
                const shareModal = document.createElement('div');
                shareModal.innerHTML = `
                    <div class="modal" style="display: block;">
                        <div class="modal-content">
                            <span class="modal-close" onclick="this.parentElement.parentElement.remove()">&times;</span>
                            <h2>Compartilhar Caso</h2>
                            <div class="share-buttons">
                                <button class="btn btn-facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}', '_blank')">
                                    <i class="fab fa-facebook"></i> Facebook
                                </button>
                                <button class="btn btn-twitter" onclick="window.open('https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(url)}', '_blank')">
                                    <i class="fab fa-twitter"></i> Twitter
                                </button>
                                <button class="btn btn-whatsapp" onclick="window.open('https://wa.me/?text=${encodeURIComponent(texto + ' ' + url)}', '_blank')">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </button>
                                <button class="btn btn-secondary" onclick="navigator.clipboard.writeText('${url}').then(() => alert('Link copiado!'))">
                                    <i class="fas fa-copy"></i> Copiar Link
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(shareModal);
            }
        }
        
        function imprimirCaso() {
            window.print();
        }
    </script>

    <!-- CSS específico para impressão -->
    <style media="print">
        header, footer, .caso-actions-principais, .btn, .cta-section { display: none !important; }
        .caso-detalhes { margin-top: 0 !important; }
        .caso-foto-grande img { max-width: 200px !important; }
        body { font-size: 12pt !important; line-height: 1.4 !important; }
    </style>
</body>
</html>