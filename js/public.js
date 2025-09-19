// JavaScript para páginas públicas do EcoSwap

$(document).ready(function() {
    console.log('Public.js carregado, pathname:', window.location.pathname);
    
    // Só executa se estamos na página inicial
    const isHomePage = window.location.pathname.includes('index.html') || 
                       window.location.pathname === '/' || 
                       window.location.pathname.endsWith('/trabalho-final/') ||
                       window.location.pathname.endsWith('/trabalho-final/index.html');
    
    console.log('É página inicial?', isHomePage);
    
    if (isHomePage) {
        console.log('Iniciando configuração da página inicial...');
        initHomePage();
    }
});

function initHomePage() {
    console.log('Inicializando página inicial...');
    
    // Verificar se foi redirecionado após logout
    checkLogoutSuccess();
    
    personalizeInterface();
    loadPublicCatalog();
    setupFilters();
    setupSearch();
    setupLogout();
    
    // Verificar se deve abrir item específico via parâmetro da URL
    checkItemParameter();
    
    // Tornar função global para uso inline
    window.showItemDetails = showItemDetails;
}

function loadPublicCatalog() {
    console.log('loadPublicCatalog chamada');
    
    // Mostrar loading
    $('#loadingItems').show();
    $('#itemsGrid').empty();
    $('#noItemsMessage').hide();
    
    // Buscar catálogo do backend
    fetch('backend/api.php?action=getPublicItems')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateCategoryFilter(data.categories);
                displayItems(data.items);
                
                // Atualizar título do catálogo
                $('#catalogTitle').text(data.catalogTitle || 'Itens Disponíveis para Troca');
            } else {
                displayItems([]);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar catálogo público:', error);
            displayItems([]);
        })
        .finally(() => {
            $('#loadingItems').hide();
        });
}

function populateCategoryFilter(categories) {
    const $categoryFilter = $('#categoryFilter');
    $categoryFilter.empty().append('<option value="">Todas as categorias</option>');
    
    if (categories && categories.length > 0) {
        categories.forEach(category => {
            $categoryFilter.append(`<option value="${category}">${category}</option>`);
        });
    }
}

function populateCategoryFilterFallback() {
    const $categoryFilter = $('#categoryFilter');
    const categories = ['Eletrônicos', 'Roupas', 'Casa e Jardim', 'Livros', 'Esportes', 'Música', 'Brinquedos', 'Veículos', 'Beleza', 'Outros'];
    categories.forEach(category => {
        $categoryFilter.append(`<option value="${category}">${category}</option>`);
    });
}

function displayItems(items) {
    const $itemsGrid = $('#itemsGrid');
    const $noItemsMessage = $('#noItemsMessage');

    if (items.length === 0) {
        $itemsGrid.empty();
        // Buscar mensagem de "sem itens" do backend
        fetch('backend/api.php?action=getNoPublicItemsMessage')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $noItemsMessage.html(data.message).show();
                }
            });
        return;
    }

    $noItemsMessage.hide();
    
    let html = '';
    items.forEach(item => {
        html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card item-card h-100">
                    <img src="${item.image_url}" class="card-img-top item-image" alt="${item.title}">
                    <div class="card-body">
                        <h5 class="card-title">${item.title}</h5>
                        <p class="card-text">${item.description.substring(0, 100)}${item.description.length > 100 ? '...' : ''}</p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary">${item.category}</span>
                            <span class="badge badge-available">${item.condition}</span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-person"></i> ${item.owner_name}<br>
                            <i class="bi bi-geo-alt"></i> ${item.owner_location || 'Não informado'}<br>
                            <i class="bi bi-calendar"></i> ${formatDate(item.created_at)}
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-outline-primary w-100" onclick="showItemDetails('${item.id}')">
                            Ver Detalhes
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    $itemsGrid.html(html);
}

function setupFilters() {
    $('#categoryFilter, #conditionFilter').on('change', function() {
        applyFilters();
    });
}

function setupSearch() {
    let searchTimeout;
    
    // Event listener para busca global
    $(document).on('input', '#searchInput', function(e) {
        const query = e.target.value.trim();
        
        // Limpar timeout anterior
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length >= 2) {
            // Adicionar debounce para evitar muitas requisições
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            $('#searchResults').empty().hide();
        }
    });
    
    // Fechar resultados de busca ao clicar fora
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-form').length) {
            $('#searchResults').hide();
        }
    });
}

function performSearch(query) {
    console.log('Realizando busca por:', query);
    
    // Buscar itens via API
    fetch(`backend/api.php?action=searchItems&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const $resultsContainer = $('#searchResults');
            
            if (data.success && data.items.length > 0) {
                let html = '<div class="search-results bg-white border rounded shadow-sm">';
                data.items.forEach(item => {
                    html += `
                        <div class="search-result-item p-3 border-bottom" data-item-id="${item.id}" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <img src="${item.image_url}" alt="${item.title}" class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${item.title}</div>
                                    <small class="text-muted">${item.category} - ${item.owner_location || 'Localização não informada'}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $resultsContainer.html(html).show();
            } else {
                $resultsContainer.html(`
                    <div class="search-results bg-white border rounded shadow-sm">
                        <div class="search-result-item p-3 text-muted text-center">
                            <i class="bi bi-search me-2"></i>Nenhum item encontrado para "${query}"
                        </div>
                    </div>
                `).show();
            }
        })
        .catch(error => {
            console.error('Erro na busca:', error);
            $('#searchResults').html(`
                <div class="search-results bg-white border rounded shadow-sm">
                    <div class="search-result-item p-3 text-danger text-center">
                        <i class="bi bi-exclamation-triangle me-2"></i>Erro ao realizar busca
                    </div>
                </div>
            `).show();
        });
}

function applyFilters() {
    const selectedCategory = $('#categoryFilter').val();
    const selectedCondition = $('#conditionFilter').val();
    
    // Buscar itens filtrados do backend
    const params = new URLSearchParams();
    params.append('action', 'getPublicItems');
    if (selectedCategory) params.append('category', selectedCategory);
    if (selectedCondition) params.append('condition', selectedCondition);
    
    fetch(`backend/api.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayItems(data.items);
            }
        })
        .catch(error => {
            console.error('Erro ao aplicar filtros:', error);
        });
}

function showItemDetails(itemId) {
    console.log('Carregando detalhes do item:', itemId);
    
    // Mostrar loading no modal
    $('#itemModalTitle').text('Carregando...');
    $('#itemModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
    $('#itemDetailsModal').modal('show');
    
    // Buscar detalhes do item do backend
    fetch(`backend/api.php?action=getItemDetails&id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Resposta da API:', data);
            if (data.success) {
                // Mostrar modal com detalhes do item
                showItemDetailsModal(data.item);
            } else {
                $('#itemModalTitle').text('Erro');
                $('#itemModalBody').html(`<div class="alert alert-danger">${data.message || 'Erro ao carregar detalhes do item'}</div>`);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do item:', error);
            $('#itemModalTitle').text('Erro');
            $('#itemModalBody').html('<div class="alert alert-danger">Erro de conexão ao carregar detalhes do item</div>');
        });
}

// Event listener para resultados de busca
$(document).on('click', '.search-result-item', function() {
    const itemId = $(this).data('item-id');
    if (itemId) {
        $('#searchResults').hide();
        $('#searchInput').val('');
        showItemDetails(itemId);
    }
});

// Função para personalizar a interface baseada no tipo de usuário
function personalizeInterface() {
    console.log('personalizeInterface chamada');
    // Verificar usuário atual via API
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            let currentUser = null;
            
            if (data.success && data.user) {
                currentUser = data.user;
            }
            
            console.log('Usuário atual:', currentUser);
            
            if (!currentUser) {
                // Usuário não logado - mostrar interface para visitantes
                showGuestInterface();
            } else if (currentUser.type === 'admin') {
                // Usuário admin - mostrar interface administrativa
                showAdminInterface(currentUser);
            } else {
                // Usuário comum - mostrar interface de usuário
                showUserInterface(currentUser);
            }
        })
        .catch(error => {
            console.log('Usuário não logado ou erro:', error);
            showGuestInterface();
        });
}

// Interface para visitantes (não logados)
function showGuestInterface() {
    console.log('Mostrando interface para visitantes');
    $('#guestNavbar').show();
    $('#loggedNavbar').hide();
    $('#guestHero').show();
    $('#userHero').hide();
    $('#adminHero').hide();
}

// Interface para usuários comuns
function showUserInterface(user) {
    console.log('Mostrando interface para usuário:', user.name);
    $('#guestNavbar').hide();
    $('#loggedNavbar').show();
    $('#guestHero').hide();
    $('#userHero').show();
    $('#adminHero').hide();
    
    // Personalizar com dados do usuário
    $('#userNameNav').text(user.name);
    $('#userNameHero').text(user.name);
    $('#userTypeHeader').text('Usuário');
    
    // Configurar link do dashboard baseado no tipo de usuário
    console.log('Configurando dashboard para usuário tipo:', user.type);
    if (user.type === 'admin') {
        // Para administradores, redirecionar para dashboard admin
        console.log('Redirecionando admin para admin-dashboard.html');
        $('#userDashboardLink').attr('href', 'admin-dashboard.html');
        $('#userDashboardLink .bi').removeClass('bi-house').addClass('bi-speedometer2');
        $('#userDashboardLink').show();
        $('#adminDashboardLink').hide();
    } else {
        // Para usuários comuns, manter dashboard normal
        console.log('Redirecionando usuário comum para dashboard.html');
        $('#userDashboardLink').attr('href', 'dashboard.html');
        $('#userDashboardLink .bi').removeClass('bi-speedometer2').addClass('bi-house');
        $('#userDashboardLink').show();
        $('#adminDashboardLink').hide();
    }
}

// Interface para administradores
function showAdminInterface(user) {
    console.log('Mostrando interface para admin:', user.name);
    $('#guestNavbar').hide();
    $('#loggedNavbar').show();
    $('#guestHero').hide();
    $('#userHero').hide();
    $('#adminHero').show();
    
    // Personalizar com dados do admin
    $('#userNameNav').text(user.name);
    $('#userTypeHeader').text('Administrador');
    
    // Configurar links do dashboard para administradores
    $('#userDashboardLink').hide();
    $('#adminDashboardLink').show();
}

// Configurar funcionalidade de logout
function setupLogout() {
    $('#logoutLink').on('click', function(e) {
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
                    
                    // Recarregar a página para mostrar interface de visitante
                    window.location.reload();
                } else {
                    console.error('Erro no logout:', data.message);
                    // Mesmo com erro, recarregar a página
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro no logout:', error);
                // Mesmo com erro, recarregar a página
                window.location.reload();
            });
        }
    });
}

// Função auxiliar para formatação de data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Funções auxiliares para o modal de detalhes
function getStatusBadgeClass(status) {
    switch (status) {
        case 'available': return 'bg-success';
        case 'traded': return 'bg-warning';
        case 'removed': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'available': return 'Disponível';
        case 'traded': return 'Trocado';
        case 'removed': return 'Removido';
        default: return 'Desconhecido';
    }
}

// Função para mostrar modal de detalhes do item
function showItemDetailsModal(item) {
    // Preencher título do modal
    $('#itemModalTitle').text(item.title);
    
    // Criar conteúdo do modal
    const modalContent = `
        <div class="row">
            <div class="col-md-6">
                <img src="${item.image_url}" class="img-fluid rounded" alt="${item.title}" style="max-height: 300px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-md-6">
                <h5 class="text-primary">${item.title}</h5>
                <p class="text-muted mb-3">${item.description}</p>
                
                <div class="mb-2">
                    <strong><i class="bi bi-tag-fill me-2"></i>Categoria:</strong>
                    <span class="badge bg-primary ms-1">${item.category}</span>
                </div>
                
                <div class="mb-2">
                    <strong><i class="bi bi-star-fill me-2"></i>Condição:</strong>
                    <span class="badge bg-success ms-1">${item.condition}</span>
                </div>
                
                <div class="mb-2">
                    <strong><i class="bi bi-check-circle-fill me-2"></i>Status:</strong>
                    <span class="badge ${getStatusBadgeClass(item.status)} ms-1">${getStatusText(item.status)}</span>
                </div>
                
                <div class="mb-3">
                    <strong><i class="bi bi-calendar-fill me-2"></i>Data de Cadastro:</strong>
                    <span>${formatDate(item.created_at)}</span>
                </div>
                
                <div class="border-top pt-3">
                    <h6 class="text-secondary">Informações para Contato</h6>
                    <p class="mb-1">
                        <i class="bi bi-person-fill me-2"></i>
                        <strong>${item.owner_name}</strong>
                    </p>
                    <p class="mb-1">
                        <i class="bi bi-envelope-fill me-2"></i>
                        <a href="mailto:${item.owner_email}">${item.owner_email}</a>
                    </p>
                    ${item.owner_phone ? `<p class="mb-1">
                        <i class="bi bi-telephone-fill me-2"></i>
                        <a href="tel:${item.owner_phone}">${item.owner_phone}</a>
                    </p>` : ''}
                    <p class="mb-0">
                        <i class="bi bi-geo-alt-fill me-2"></i>
                        ${item.owner_location || 'Localização não informada'}
                    </p>
                    
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Entre em contato diretamente com o proprietário para propor uma troca!
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Inserir conteúdo no modal
    $('#itemModalBody').html(modalContent);
    
    // Personalizar rodapé do modal baseado no status do usuário
    updateModalFooter();
    
    // Mostrar o modal
    $('#itemDetailsModal').modal('show');
}

// Função para atualizar o rodapé do modal baseado no status do usuário
function updateModalFooter() {
    // Verificar se o usuário está logado
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            const modalFooter = $('#itemDetailsModal .modal-footer');
            
            if (data.success && data.user) {
                // Usuário logado - remover botão de login
                modalFooter.html(`
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <small class="text-muted ms-2">
                        Use as informações de contato acima para entrar em contato
                    </small>
                `);
            } else {
                // Usuário não logado - manter botão de login
                modalFooter.html(`
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <a href="login.html" class="btn btn-primary">
                        Entrar para Ver Contatos
                    </a>
                `);
                
                // Também esconder as informações de contato
                $('#itemModalBody .border-top').html(`
                    <h6 class="text-secondary">Informações para Contato</h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Faça login para ver as informações de contato do proprietário
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status do usuário:', error);
            // Em caso de erro, assumir que não está logado
            const modalFooter = $('#itemDetailsModal .modal-footer');
            modalFooter.html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Fechar
                </button>
                <a href="login.html" class="btn btn-primary">
                    Entrar para Ver Contatos
                </a>
            `);
        });
}

// Verificar se foi redirecionado após logout
function checkLogoutSuccess() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('logout') === 'success') {
        // Mostrar mensagem de sucesso
        showTemporaryMessage('Logout realizado com sucesso!', 'success');
        
        // Limpar parâmetro da URL
        const newUrl = window.location.pathname;
        history.replaceState(null, '', newUrl);
    }
}

// Verificar se deve abrir item específico via parâmetro da URL
function checkItemParameter() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item');
    
    if (itemId) {
        // Aguardar um pouco para garantir que a página carregou
        setTimeout(() => {
            showItemDetails(itemId);
        }, 500);
        
        // Limpar parâmetro da URL
        const newUrl = window.location.pathname;
        history.replaceState(null, '', newUrl);
    }
}

// Mostrar mensagem temporária
function showTemporaryMessage(message, type = 'info') {
    // Criar elemento de alerta
    const alertDiv = $(`
        <div class="alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    // Adicionar ao body
    $('body').append(alertDiv);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        alertDiv.fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}