// JavaScript para o Dashboard do usuário

$(document).ready(function() {
    // Verificar autenticação e inicializar dashboard
    checkAuthAndInitialize();
    
    // Configurar evento de logout
    setupLogout();
});

function checkAuthAndInitialize() {
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                // Limpar flag de login recente se existir
                sessionStorage.removeItem('justLoggedIn');
                
                // Usuário autenticado - inicializar dashboard
                initializeDashboard();
            } else {
                // Não autenticado - redirecionar para login apenas se não acabou de logar
                if (!sessionStorage.getItem('justLoggedIn')) {
                    window.location.href = 'login.html';
                } else {
                    // Se acabou de logar mas a sessão não foi encontrada, aguardar um pouco
                    setTimeout(() => {
                        checkAuthAndInitialize();
                    }, 500);
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar autenticação:', error);
            
            // Só redirecionar se não acabou de logar
            if (!sessionStorage.getItem('justLoggedIn')) {
                window.location.href = 'login.html';
            } else {
                // Se acabou de logar mas houve erro, tentar novamente
                setTimeout(() => {
                    checkAuthAndInitialize();
                }, 500);
            }
        });
}

function initializeDashboard() {
    // Carregar dados do usuário do backend
    loadUserData();
    
    // Carregar estatísticas do backend
    loadUserStatistics();
    
    // Carregar itens recentes do backend
    loadRecentItems();
    
    // Carregar dica sustentável do backend
    loadSustainabilityTip();
}

function loadUserData() {
    // Buscar dados do usuário do backend
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar nome do usuário no header e dashboard
                $('#userNameNav').text(data.user.name);
                $('#userName').text(data.user.name);
                $('#welcomeText').text(data.welcomeMessage);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados do usuário:', error);
        });
}

function loadUserStatistics() {
    // Buscar estatísticas do usuário do backend
    fetch('backend/api.php?action=getUserStatistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar contadores com animação
                animateCounter('#totalItemsCount', data.statistics.totalItems);
                animateCounter('#availableItemsCount', data.statistics.availableItems);
                animateCounter('#tradedItemsCount', data.statistics.tradedItems);
                animateCounter('#daysSinceJoin', data.statistics.daysSinceJoin);
                
                // Atualizar labels
                $('#totalItemsLabel').text(data.labels.totalItems);
                $('#availableItemsLabel').text(data.labels.availableItems);
                $('#tradedItemsLabel').text(data.labels.tradedItems);
                $('#daysSinceJoinLabel').text(data.labels.daysSinceJoin);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar estatísticas:', error);
        });
}

function animateCounter(selector, targetValue) {
    const $element = $(selector);
    let currentValue = 0;
    const increment = Math.ceil(targetValue / 20);
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        $element.text(currentValue);
    }, 50);
}

function loadRecentItems() {
    // Buscar itens recentes do backend
    fetch('backend/api.php?action=getUserRecentItems&limit=3')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const $recentItemsList = $('#recentItemsList');
                const $noRecentItems = $('#noRecentItems');
                
                if (data.items.length === 0) {
                    $recentItemsList.hide();
                    $noRecentItems.html(data.noItemsMessage).show();
                    return;
                }
                
                let html = '<div class="row">';
                data.items.forEach(item => {
                    const statusBadge = getStatusBadge(item.status);
                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <img src="${item.image_url}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${item.title}">
                                <div class="card-body">
                                    <h6 class="card-title">${item.title}</h6>
                                    <p class="card-text small text-muted">${item.description.substring(0, 60)}${item.description.length > 60 ? '...' : ''}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">${item.category}</span>
                                        ${statusBadge}
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> ${formatDate(item.created_at)}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                $recentItemsList.html(html);
                $noRecentItems.hide();
            }
        })
        .catch(error => {
            console.error('Erro ao carregar itens recentes:', error);
        });
}

function getStatusBadge(status) {
    switch (status) {
        case 'available':
            return '<span class="badge badge-available">Disponível</span>';
        case 'traded':
            return '<span class="badge badge-traded">Trocado</span>';
        case 'removed':
            return '<span class="badge badge-removed">Removido</span>';
        default:
            return '<span class="badge bg-secondary">Desconhecido</span>';
    }
}

function loadSustainabilityTip() {
    // Buscar dica sustentável do backend
    fetch('backend/api.php?action=getSustainabilityTip')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#sustainabilityTip').html(`<p class="mb-0">${data.tip}</p>`);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dica sustentável:', error);
        });
}

// Função auxiliar para formatação de data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Configurar funcionalidade de logout
function setupLogout() {
    $(document).on('click', '.logout-btn', function(e) {
        e.preventDefault();
        
        if (confirm('Tem certeza que deseja sair da sua conta?')) {
            // Logout via API
            fetch('backend/api.php?action=logout', {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpar dados locais
                    sessionStorage.clear();
                    localStorage.removeItem('ecoswap_current_user');
                    
                    // Redirecionar para página inicial
                    window.location.href = 'index.html?logout=success';
                } else {
                    alert('Erro ao fazer logout: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro no logout:', error);
                alert('Erro de conexão ao fazer logout');
            });
        }
    });
}