// JavaScript para a página de perfil

$(document).ready(function() {
    // Verificar autenticação e inicializar perfil
    checkAuthAndInitializeProfile();
    
    // Configurar evento de logout
    setupLogout();
});

function checkAuthAndInitializeProfile() {
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                // Usuário autenticado - inicializar perfil
                initializeProfile(data.user);
                setupProfileEvents();
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

function initializeProfile(user) {
    // Atualizar informações do usuário no header
    $('#userNameNav').text(user.nome);
    
    // Preencher formulário
    $('#name').val(user.nome);
    $('#email').val(user.email);
    $('#phone').val(user.telefone || '');
    $('#location').val(user.localizacao || '');
    
    // Carregar estatísticas
    loadProfileStatistics();
}

function loadProfileStatistics() {
    // Buscar estatísticas via API
    fetch('backend/api.php?action=getUserStatistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Estatísticas dos itens
                $('#totalItems').text(data.statistics.totalItems);
                $('#availableItems').text(data.statistics.availableItems);
                $('#tradedItems').text(data.statistics.tradedItems);
                
                // Data de cadastro (se disponível)
                if (data.statistics.memberSince) {
                    $('#memberSince').text(data.statistics.memberSince);
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar estatísticas:', error);
        });
}

function setupProfileEvents() {
    // Máscara para telefone
    $('#phone').on('input', function() {
        applyPhoneMask(this);
    });

    // Validação em tempo real
    $('#name').on('blur', validateNameField);
    $('#phone').on('blur', validatePhoneField);
    $('#location').on('blur', validateLocationField);
    $('#newPassword').on('input', validateNewPasswordField);
    $('#confirmNewPassword').on('input', validateConfirmNewPasswordField);

    // Submit do formulário
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        handleProfileUpdate();
    });

    // Botão cancelar
    $('#cancelBtn').on('click', function() {
        if (confirm('Descartar alterações?')) {
            initializeProfile();
            FormValidator.clearValidation('#profileForm');
        }
    });

    // Checkbox de confirmação para exclusão
    $('#confirmDeletion').on('change', function() {
        $('#confirmDeleteAccount').prop('disabled', !this.checked);
    });

    // Botão de exclusão de conta
    $('#confirmDeleteAccount').on('click', handleAccountDeletion);
}

function handleProfileUpdate() {
    // Limpar validações anteriores
    FormValidator.clearValidation('#profileForm');

    const formData = {
        name: $('#name').val().trim(),
        phone: $('#phone').val().trim(),
        location: $('#location').val().trim(),
        newPassword: $('#newPassword').val(),
        confirmNewPassword: $('#confirmNewPassword').val()
    };

    // Validação
    let isValid = true;

    if (!FormValidator.validateRequired(formData.name)) {
        FormValidator.showError('#name', 'Nome é obrigatório');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.phone)) {
        FormValidator.showError('#phone', 'Telefone é obrigatório');
        isValid = false;
    } else if (!FormValidator.validatePhone(formData.phone)) {
        FormValidator.showError('#phone', 'Formato inválido. Use (XX) XXXXX-XXXX');
        isValid = false;
    }

    if (!FormValidator.validateRequired(formData.location)) {
        FormValidator.showError('#location', 'Localização é obrigatória');
        isValid = false;
    }

    // Validação de senha (se fornecida)
    if (formData.newPassword) {
        if (!FormValidator.validatePassword(formData.newPassword)) {
            FormValidator.showError('#newPassword', 'Senha deve ter pelo menos 6 caracteres');
            isValid = false;
        }

        if (formData.newPassword !== formData.confirmNewPassword) {
            FormValidator.showError('#confirmNewPassword', 'Senhas não coincidem');
            isValid = false;
        }
    }

    if (!isValid) {
        showAlert('Por favor, corrija os erros no formulário.', 'danger');
        return;
    }

    // Mostrar loading
    const $saveBtn = $('#saveProfileBtn');
    const $spinner = $('#saveSpinner');
    $saveBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Preparar dados para atualização
    const updateData = {
        name: formData.name,
        phone: formData.phone,
        location: formData.location
    };

    // Incluir senha se fornecida
    if (formData.newPassword) {
        updateData.password = formData.newPassword;
    }

    // Atualizar perfil via API
    fetch('backend/api.php?action=updateProfile', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Perfil atualizado com sucesso!', 'success');
            
            // Limpar campos de senha
            $('#newPassword, #confirmNewPassword').val('');
            
            // Recarregar estatísticas
            loadProfileStatistics();
            
            // Atualizar nome no header
            $('#userNameNav').text(formData.name);
        } else {
            showAlert(data.message || 'Erro ao atualizar perfil', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar perfil:', error);
        showAlert('Erro de conexão', 'danger');
    })
    .finally(() => {
        // Remover loading
        $saveBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    });
}

function handleAccountDeletion() {
    const $deleteBtn = $('#confirmDeleteAccount');
    $deleteBtn.prop('disabled', true).html('<i class="bi bi-spinner spinner-border spinner-border-sm me-2"></i>Excluindo...');

    // Deletar conta via API
    fetch('backend/api.php?action=deleteAccount', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Conta excluída com sucesso. Você será redirecionado para a página inicial.');
            window.location.href = 'index.html';
        } else {
            alert('Erro ao excluir conta. Tente novamente.');
            $deleteBtn.prop('disabled', false).html('<i class="bi bi-trash me-2"></i>Excluir Conta');
        }
    }, 1000);
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
function validateNameField() {
    const name = $('#name').val().trim();
    if (!FormValidator.validateRequired(name)) {
        FormValidator.showError('#name', 'Nome é obrigatório');
        return false;
    } else {
        FormValidator.showSuccess('#name');
        return true;
    }
}

function validatePhoneField() {
    const phone = $('#phone').val().trim();
    if (!FormValidator.validateRequired(phone)) {
        FormValidator.showError('#phone', 'Telefone é obrigatório');
        return false;
    } else if (!FormValidator.validatePhone(phone)) {
        FormValidator.showError('#phone', 'Formato inválido. Use (XX) XXXXX-XXXX');
        return false;
    } else {
        FormValidator.showSuccess('#phone');
        return true;
    }
}

function validateLocationField() {
    const location = $('#location').val().trim();
    if (!FormValidator.validateRequired(location)) {
        FormValidator.showError('#location', 'Localização é obrigatória');
        return false;
    } else {
        FormValidator.showSuccess('#location');
        return true;
    }
}

function validateNewPasswordField() {
    const password = $('#newPassword').val();
    if (password === '') {
        // Campo vazio - remover validação visual
        $('#newPassword').removeClass('is-valid is-invalid');
        $('#newPassword').siblings('.invalid-feedback').remove();
        return true; // Campo de nova senha é opcional
    } else if (!FormValidator.validatePassword(password)) {
        FormValidator.showError('#newPassword', 'Senha deve ter pelo menos 6 caracteres');
        return false;
    } else {
        FormValidator.showSuccess('#newPassword');
        return true;
    }
}

function validateConfirmNewPasswordField() {
    const password = $('#newPassword').val();
    const confirmPassword = $('#confirmNewPassword').val();
    
    if (confirmPassword === '') {
        // Campo vazio - remover validação visual
        $('#confirmNewPassword').removeClass('is-valid is-invalid');
        $('#confirmNewPassword').siblings('.invalid-feedback').remove();
        return true; // Campo é opcional se nova senha não foi preenchida
    } else if (password !== confirmPassword) {
        FormValidator.showError('#confirmNewPassword', 'Senhas não coincidem');
        return false;
    } else {
        FormValidator.showSuccess('#confirmNewPassword');
        return true;
    }
}

function applyPhoneMask(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
    }
    
    input.value = value;
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