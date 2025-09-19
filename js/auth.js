// JavaScript para autenticação (login e cadastro)

$(document).ready(function() {
    setupAuthEvents();
    
    // Verificar se já está logado via API (apenas se não estiver vindo de um redirect)
    if (!sessionStorage.getItem('justLoggedIn')) {
        checkAuthStatus();
    } else {
        // Limpar flag e permitir permanecer na página
        sessionStorage.removeItem('justLoggedIn');
    }
});

function checkAuthStatus() {
    // Só verificar se estamos nas páginas de auth e não acabamos de fazer login
    if (!window.location.pathname.includes('login.html') && !window.location.pathname.includes('cadastro.html')) {
        return;
    }
    
    fetch('backend/api.php?action=getUserData')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                // Usuário está logado - redirecionar apenas se não estiver vindo de login
                console.log('Usuário já logado, redirecionando...');
                
                if (data.user.tipo_usuario === 'admin') {
                    window.location.href = 'admin-dashboard.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }
        })
        .catch(error => {
            // Usuário não está logado ou erro - permanecer na página
            console.log('Usuário não está logado:', error);
        });
}

function setupAuthEvents() {
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        
        const icon = $(this).find('i');
        icon.toggleClass('bi-eye bi-eye-slash');
    });

    // Login form submit
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        handleLogin();
    });

    // Cadastro form submit
    $('#cadastroForm').on('submit', function(e) {
        e.preventDefault();
        handleRegistration();
    });

    // Validação em tempo real
    $('#email').on('blur', function() {
        validateEmailField();
    });

    $('#password').on('input', function() {
        if (window.location.pathname.includes('cadastro.html')) {
            validatePasswordField();
        }
    });

    $('#confirmPassword').on('input', function() {
        validateConfirmPasswordField();
    });

    $('#name').on('blur', function() {
        validateNameField();
    });

    $('#phone').on('blur', function() {
        validatePhoneField();
    });

    // Máscara para telefone
    $('#phone').on('input', function() {
        applyPhoneMask(this);
    });
}

function handleLogin() {
    const email = $('#email').val().trim();
    const password = $('#password').val();

    // Validação básica
    if (!FormValidator.validateEmail(email)) {
        showAlert('Por favor, informe um email válido.', 'danger');
        FormValidator.showError('#email', 'Email inválido');
        return;
    }

    if (!FormValidator.validateRequired(password)) {
        showAlert('Por favor, informe sua senha.', 'danger');
        FormValidator.showError('#password', 'Senha é obrigatória');
        return;
    }

    // Mostrar loading
    const $loginBtn = $('#loginBtn');
    const $spinner = $('#loginSpinner');
    $loginBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Fazer login via API
    fetch('backend/api.php?action=login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Login realizado com sucesso!', 'success');
            
            // Definir flag para evitar loop de verificação
            sessionStorage.setItem('justLoggedIn', 'true');
            
            setTimeout(() => {
                if (data.user && data.user.tipo_usuario === 'admin') {
                    window.location.href = 'admin-dashboard.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }, 1000);
        } else {
            showAlert(data.message || 'Erro no login', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro no login:', error);
        showAlert('Erro de conexão. Tente novamente.', 'danger');
    })
    .finally(() => {
        // Remover loading
        $loginBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    });
}

function handleRegistration() {
    // Limpar validações anteriores
    FormValidator.clearValidation('#cadastroForm');

    const formData = {
        name: $('#name').val().trim(),
        email: $('#email').val().trim(),
        password: $('#password').val(),
        confirmPassword: $('#confirmPassword').val(),
        phone: $('#phone').val().trim(),
        location: $('#location').val().trim()
    };

    // Validação
    let isValid = true;

    if (!FormValidator.validateRequired(formData.name)) {
        FormValidator.showError('#name', 'Nome é obrigatório');
        isValid = false;
    }

    if (!FormValidator.validateEmail(formData.email)) {
        FormValidator.showError('#email', 'Email inválido');
        isValid = false;
    }

    if (!FormValidator.validatePassword(formData.password)) {
        FormValidator.showError('#password', 'Senha deve ter pelo menos 6 caracteres');
        isValid = false;
    }

    if (formData.password !== formData.confirmPassword) {
        FormValidator.showError('#confirmPassword', 'Senhas não coincidem');
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

    if (!isValid) {
        showAlert('Por favor, corrija os erros no formulário.', 'danger');
        return;
    }

    // Mostrar loading
    const $cadastroBtn = $('#cadastroBtn');
    const $spinner = $('#cadastroSpinner');
    $cadastroBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Fazer cadastro via API
    fetch('backend/api.php?action=register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: formData.name,
            email: formData.email,
            password: formData.password,
            phone: formData.phone,
            location: formData.location
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cadastro realizado com sucesso!', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            showAlert(data.message || 'Erro no cadastro', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro no cadastro:', error);
        showAlert('Erro de conexão. Tente novamente.', 'danger');
    })
    .finally(() => {
        // Remover loading
        $cadastroBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    });
}

function quickLogin(email, password) {
    $('#email').val(email);
    $('#password').val(password);
    handleLogin();
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
}

// Validações individuais
function validateEmailField() {
    const email = $('#email').val().trim();
    if (email === '') {
        // Campo vazio - remover validação visual
        $('#email').removeClass('is-valid is-invalid');
        $('#email').siblings('.invalid-feedback').remove();
        return false;
    } else if (!FormValidator.validateEmail(email)) {
        FormValidator.showError('#email', 'Email inválido');
        return false;
    } else {
        FormValidator.showSuccess('#email');
        return true;
    }
}

function validatePasswordField() {
    const password = $('#password').val();
    if (password === '') {
        // Campo vazio - remover validação visual
        $('#password').removeClass('is-valid is-invalid');
        $('#password').siblings('.invalid-feedback').remove();
        return false;
    } else if (!FormValidator.validatePassword(password)) {
        FormValidator.showError('#password', 'Senha deve ter pelo menos 6 caracteres');
        return false;
    } else {
        FormValidator.showSuccess('#password');
        return true;
    }
}

function validateConfirmPasswordField() {
    const password = $('#password').val();
    const confirmPassword = $('#confirmPassword').val();
    
    if (confirmPassword === '') {
        // Campo vazio - remover validação visual
        $('#confirmPassword').removeClass('is-valid is-invalid');
        $('#confirmPassword').siblings('.invalid-feedback').remove();
        return false;
    } else if (password !== confirmPassword) {
        FormValidator.showError('#confirmPassword', 'Senhas não coincidem');
        return false;
    } else {
        FormValidator.showSuccess('#confirmPassword');
        return true;
    }
}

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
    if (phone === '') {
        // Campo vazio - remover validação visual
        $('#phone').removeClass('is-valid is-invalid');
        $('#phone').siblings('.invalid-feedback').remove();
        return false;
    } else if (!FormValidator.validatePhone(phone)) {
        FormValidator.showError('#phone', 'Formato inválido. Use (XX) XXXXX-XXXX');
        return false;
    } else {
        FormValidator.showSuccess('#phone');
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