// JavaScript para gerenciamento de itens

let currentEditingItemId = null;

$(document).ready(function() {
    // Verificar autenticação e inicializar página
    checkAuthAndInitializeItems();
    
    // Configurar evento de logout
    setupLogout();
});

function checkAuthAndInitializeItems() {
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                // Usuário autenticado - inicializar página
                initializeItemsPage();
                setupItemsEvents();
            } else {
                // Não autenticado - redirecionar para login
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Erro ao verificar autenticação:', error);
            window.location.href = 'login.html';
        });
}

function initializeItemsPage() {
    // Carregar dados do usuário do backend
    loadPageData();
    
    // Carregar categorias do backend
    populateCategories();
    
    // Carregar itens do usuário do backend
    loadUserItems();

    // Verificar se deve abrir modal de adicionar item
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        $('#addItemModal').modal('show');
    }
}

function loadPageData() {
    // Buscar dados da página do backend
    fetch('backend/api.php?action=getItemsPageData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar informações do usuário no header
                $('#userNameNav').text(data.user.name);
                
                // Atualizar títulos da página
                $('#pageTitle').text(data.pageTitle);
                $('#pageDescription').text(data.pageDescription);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados da página:', error);
        });
}

function populateCategories() {
    // Buscar categorias do backend
    fetch('backend/api.php?action=getCategories')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Popular filtro de categoria
                const $categoryFilter = $('#categoryFilter');
                $categoryFilter.empty().append('<option value="">Todas as categorias</option>');
                data.categories.forEach(category => {
                    $categoryFilter.append(`<option value="${category}">${category}</option>`);
                });

                // Popular select do modal
                const $categorySelect = $('#category');
                $categorySelect.empty().append('<option value="">Selecione...</option>');
                data.categories.forEach(category => {
                    $categorySelect.append(`<option value="${category}">${category}</option>`);
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar categorias:', error);
        });
}

function loadUserItems() {
    // Buscar itens do usuário do backend
    fetch('backend/api.php?action=getUserItems')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayItems(data.items);
            } else {
                displayItems([]);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar itens do usuário:', error);
            displayItems([]);
        });
}

function displayItems(items) {
    const $itemsGrid = $('#itemsGrid');
    const $noItemsMessage = $('#noItemsMessage');

    if (items.length === 0) {
        $itemsGrid.empty();
        // Buscar mensagem de "sem itens" do backend
        fetch('backend/api.php?action=getNoItemsMessage')
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
        const statusBadge = getStatusBadge(item.status);
        const actionButtons = getActionButtons(item);

        html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card item-card h-100">
                    <img src="${item.image_url}" class="card-img-top item-image" alt="${item.title}" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">${item.title}</h5>
                        <p class="card-text">${item.description.substring(0, 100)}${item.description.length > 100 ? '...' : ''}</p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary">${item.category}</span>
                            ${statusBadge}
                        </div>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-star"></i> Condição: ${item.condition}<br>
                            <i class="bi bi-calendar"></i> ${formatDate(item.created_at)}
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        ${actionButtons}
                    </div>
                </div>
            </div>
        `;
    });

    $itemsGrid.html(html);
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

function getActionButtons(item) {
    let buttons = '';
    
    if (item.status === 'available') {
        buttons += `
            <button class="btn btn-sm btn-outline-primary me-2" onclick="editItem('${item.id}')">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-sm btn-outline-success me-2" onclick="markAsTraded('${item.id}')">
                <i class="bi bi-check"></i> Marcar como Trocado
            </button>
        `;
    }
    
    if (item.status !== 'removed') {
        buttons += `
            <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('${item.id}', '${item.title.replace(/'/g, "\\'")}')">
                <i class="bi bi-trash"></i> Excluir
            </button>
        `;
    }

    return `<div class="d-flex flex-wrap gap-1">${buttons}</div>`;
}

// Função auxiliar para formatação de data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function setupItemsEvents() {
    // Filtros
    $('#categoryFilter, #statusFilter').on('change', applyFilters);
    $('#searchItemsInput').on('input', applyFilters);
    
    // Busca global na navegação
    setupGlobalSearch();

    // Modal events
    $('#addItemModal').on('show.bs.modal', function() {
        if (!currentEditingItemId) {
            $('#itemModalTitle').text('Adicionar Item');
            $('#saveItemText').text('Salvar Item');
            $('#itemForm')[0].reset();
            $('#itemId').val('');
            $('#imagePreview').hide();
            FormValidator.clearValidation('#itemForm');
        }
    });

    // Preview de imagem
    $('#imageUrl').on('input', function() {
        const url = $(this).val().trim();
        if (url && isValidUrl(url)) {
            $('#previewImg').attr('src', url);
            $('#imagePreview').show();
        } else {
            $('#imagePreview').hide();
        }
    });

    // Salvar item
    $('#saveItemBtn').on('click', handleSaveItem);

    // Validação em tempo real
    $('#title').on('blur', validateTitleField);
    $('#description').on('blur', validateDescriptionField);
    $('#category').on('change', validateCategoryField);
    $('#condition').on('change', validateConditionField);
    $('#imageUrl').on('blur', validateImageUrlField);
}

function applyFilters() {
    // Aplicar filtros via API
    const selectedCategory = $('#categoryFilter').val();
    const selectedStatus = $('#statusFilter').val();
    const searchTerm = $('#searchItemsInput').val().trim();
    
    // Construir parâmetros para a API
    const params = new URLSearchParams();
    params.append('action', 'getUserItems');
    
    if (selectedCategory) {
        params.append('category', selectedCategory);
    }
    
    if (selectedStatus) {
        params.append('status', selectedStatus);
    }
    
    if (searchTerm && searchTerm.length >= 1) {
        params.append('search', searchTerm);
    }
    
    // Buscar itens filtrados
    fetch(`backend/api.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayItems(data.items);
            } else {
                displayItems([]);
            }
        })
        .catch(error => {
            console.error('Erro ao aplicar filtros:', error);
            displayItems([]);
        });
}

function editItem(itemId) {
    // Buscar item via API
    fetch(`backend/api.php?action=getItemById&id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.item) {
                const item = data.item;
                // Preencher formulário de edição
                fillEditForm(item);
            } else {
                alert('Item não encontrado');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar item:', error);
            alert('Erro ao carregar item para edição');
        });
}

function fillEditForm(item) {
    currentEditingItemId = item.id;

    // Preencher formulário com dados do banco
    $('#itemId').val(item.id);
    $('#title').val(item.nome);
    $('#description').val(item.descricao);
    $('#category').val(item.categoria);
    $('#condition').val(item.condicao);
    $('#imageUrl').val(item.imagem);

    // Mostrar preview da imagem
    if (item.imagem) {
        $('#previewImg').attr('src', item.imagem);
        $('#imagePreview').show();
    } else {
        $('#imagePreview').hide();
    }

    // Atualizar modal
    $('#itemModalTitle').text('Editar Item');
    $('#saveItemText').text('Atualizar Item');

    // Limpar validações
    FormValidator.clearValidation('#itemForm');

    // Abrir modal
    $('#addItemModal').modal('show');
}

function markAsTraded(itemId) {
    if (confirm('Marcar este item como trocado? Ele não aparecerá mais no catálogo público.')) {
        // Atualizar via API
        fetch('backend/api.php?action=updateItem', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: itemId,
                status: 'traded'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Item marcado como trocado com sucesso!', 'success');
                loadUserItems();
            } else {
                showAlert(data.message || 'Erro ao atualizar item.', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar item:', error);
            showAlert('Erro de conexão.', 'danger');
        });
    }
}

function deleteItem(itemId, itemTitle) {
    $('#deleteItemName').text(`"${itemTitle}"`);
    $('#confirmDeleteBtn').data('item-id', itemId);
    $('#deleteConfirmModal').modal('show');
}

// Event handler para confirmar exclusão
$(document).on('click', '#confirmDeleteBtn', function() {
    const itemId = $(this).data('item-id');
    
    // Deletar via API
    fetch('backend/api.php?action=deleteItem', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Item excluído com sucesso!', 'success');
            loadUserItems();
            $('#deleteConfirmModal').modal('hide');
        } else {
            showAlert(data.message || 'Erro ao excluir item.', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao excluir item:', error);
        showAlert('Erro de conexão.', 'danger');
    });
});

function handleSaveItem() {
    // Limpar validações anteriores
    FormValidator.clearValidation('#itemForm');

    const formData = {
        title: $('#title').val().trim(),
        description: $('#description').val().trim(),
        category: $('#category').val(),
        condition: $('#condition').val(),
        imageUrl: $('#imageUrl').val().trim()
    };

    // Validação
    let isValid = true;

    if (!FormValidator.validateRequired(formData.title)) {
        FormValidator.showError('#title', 'Título é obrigatório');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.description)) {
        FormValidator.showError('#description', 'Descrição é obrigatória');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.category)) {
        FormValidator.showError('#category', 'Categoria é obrigatória');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.condition)) {
        FormValidator.showError('#condition', 'Condição é obrigatória');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.imageUrl)) {
        FormValidator.showError('#imageUrl', 'URL da imagem é obrigatória');
        isValid = false;
    } else if (!isValidUrl(formData.imageUrl)) {
        FormValidator.showError('#imageUrl', 'URL inválida');
        isValid = false;
    }

    if (!isValid) {
        showAlert('Por favor, corrija os erros no formulário.', 'danger');
        return;
    }

    // Mostrar loading
    const $saveBtn = $('#saveItemBtn');
    const $spinner = $('#saveSpinner');
    $saveBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Preparar dados do item
    const itemData = {
        name: formData.title,
        description: formData.description,
        category: formData.category,
        condition: formData.condition,
        location: formData.location || 'Não informado',
        image: formData.imageUrl
    };

    // Determinar ação (adicionar ou editar)
    const apiAction = currentEditingItemId ? 'updateItem' : 'addItem';
    const apiUrl = `backend/api.php?action=${apiAction}`;
    
    if (currentEditingItemId) {
        itemData.id = currentEditingItemId;
    }

    // Salvar via API
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(itemData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const action = currentEditingItemId ? 'atualizado' : 'adicionado';
            showAlert(`Item ${action} com sucesso!`, 'success');
            
            // Fechar modal e recarregar itens
            $('#addItemModal').modal('hide');
            loadUserItems();
            
            // Reset
            currentEditingItemId = null;
            $('#itemForm')[0].reset();
            $('#imagePreview').hide();
        } else {
            showAlert(data.message || 'Erro ao salvar item.', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao salvar item:', error);
        showAlert('Erro de conexão.', 'danger');
    })
    .finally(() => {
        // Remover loading
        $saveBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    });
}

function showAlert(message, type) {
    const $alert = $('#alertMessage');
    $alert.removeClass('d-none alert-success alert-danger alert-warning alert-info')
          .addClass(`alert-${type}`)
          .text(message);

    // Auto-hide success messages
    if (type === 'success') {
        setTimeout(() => {
            $alert.addClass('d-none');
        }, 3000);
    }

    // Scroll para o topo
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Validações individuais
function validateTitleField() {
    const title = $('#title').val().trim();
    if (!FormValidator.validateRequired(title)) {
        FormValidator.showError('#title', 'Título é obrigatório');
        return false;
    } else {
        FormValidator.showSuccess('#title');
        return true;
    }
}

function validateDescriptionField() {
    const description = $('#description').val().trim();
    if (!FormValidator.validateRequired(description)) {
        FormValidator.showError('#description', 'Descrição é obrigatória');
        return false;
    } else {
        FormValidator.showSuccess('#description');
        return true;
    }
}

function validateCategoryField() {
    const category = $('#category').val();
    if (!FormValidator.validateRequired(category)) {
        FormValidator.showError('#category', 'Categoria é obrigatória');
        return false;
    } else {
        FormValidator.showSuccess('#category');
        return true;
    }
}

function validateConditionField() {
    const condition = $('#condition').val();
    if (!FormValidator.validateRequired(condition)) {
        FormValidator.showError('#condition', 'Condição é obrigatória');
        return false;
    } else {
        FormValidator.showSuccess('#condition');
        return true;
    }
}

function validateImageUrlField() {
    const imageUrl = $('#imageUrl').val().trim();
    if (!FormValidator.validateRequired(imageUrl)) {
        FormValidator.showError('#imageUrl', 'URL da imagem é obrigatória');
        return false;
    } else if (!isValidUrl(imageUrl)) {
        FormValidator.showError('#imageUrl', 'URL inválida');
        return false;
    } else {
        FormValidator.showSuccess('#imageUrl');
        return true;
    }
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
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

// Configurar busca global na barra de navegação
function setupGlobalSearch() {
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
                performGlobalSearch(query);
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
    
    // Event listener para clique nos resultados de busca
    $(document).on('click', '.search-result-item', function() {
        const itemId = $(this).data('item-id');
        if (itemId) {
            $('#searchResults').hide();
            $('#searchInput').val('');
            // Redirecionar para página inicial com modal de detalhes
            window.location.href = `index.html?item=${itemId}`;
        }
    });
}

function performGlobalSearch(query) {
    console.log('Realizando busca global por:', query);
    
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
            console.error('Erro na busca global:', error);
            $('#searchResults').html(`
                <div class="search-results bg-white border rounded shadow-sm">
                    <div class="search-result-item p-3 text-danger text-center">
                        <i class="bi bi-exclamation-triangle me-2"></i>Erro ao realizar busca
                    </div>
                </div>
            `).show();
        });
}