// js/admin-profile.js

$(document).ready(function() {
    loadProfilePage();
    initEventListeners();
});

function loadProfilePage() {
    fetch('backend/api.php?action=getAdminProfileData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const profile = data.profile;
                const stats = data.stats;
                
                // Preencher formulário
                $('#name').val(profile.name);
                $('#email').val(profile.email);
                $('#phone').val(profile.phone || '');
                $('#location').val(profile.location || '');
                
                // Preencher stats
                $('#adminSince').text(new Date(profile.createdAt).toLocaleDateString('pt-BR'));
                $('#totalUsers').text(stats.totalUsers);
                $('#totalSystemItems').text(stats.totalItems);

            } else {
                alert("Erro ao carregar perfil: " + data.message);
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error("Erro de conexão:", error);
            alert("Não foi possível carregar os dados do perfil.");
        });
}

function initEventListeners() {
    $('#adminProfileForm').on('submit', handleProfileUpdate);
    $('#confirmNewPassword').on('input', validatePasswordMatch);
}

function handleProfileUpdate(e) {
    e.preventDefault();
    
    const saveBtn = $('#saveProfileBtn');
    const spinner = $('#saveSpinner');
    saveBtn.prop('disabled', true);
    spinner.removeClass('d-none');
    
    const formData = {
        name: $('#name').val().trim(),
        phone: $('#phone').val().trim(),
        location: $('#location').val().trim(),
        password: $('#newPassword').val()
    };

    const confirmPassword = $('#confirmNewPassword').val();

    if (formData.password && formData.password !== confirmPassword) {
        showAlert('As novas senhas não coincidem.', 'danger');
        resetSaveButton(saveBtn, spinner);
        return;
    }

    fetch('backend/api.php?action=updateAdminProfile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Perfil atualizado com sucesso!', 'success');
            $('#newPassword, #confirmNewPassword').val(''); // Limpa campos de senha
        } else {
            showAlert('Erro ao atualizar: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error("Erro ao salvar perfil:", error);
        showAlert('Erro de conexão ao salvar o perfil.', 'danger');
    })
    .finally(() => {
        resetSaveButton(saveBtn, spinner);
    });
}

function resetSaveButton(saveBtn, spinner) {
    saveBtn.prop('disabled', false);
    spinner.addClass('d-none');
}

function validatePasswordMatch() {
    const newPassword = $('#newPassword').val();
    const confirmField = $('#confirmNewPassword');
    if (newPassword) {
        if (newPassword === confirmField.val()) {
            confirmField.removeClass('is-invalid').addClass('is-valid');
        } else {
            confirmField.removeClass('is-valid').addClass('is-invalid');
        }
    } else {
        confirmField.removeClass('is-valid is-invalid');
    }
}

function showAlert(message, type) {
    const alertDiv = $('#alertMessage');
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    alertDiv
        .removeClass('d-none alert-success alert-danger alert-info')
        .addClass(`alert-${type}`)
        .html(`<i class="bi bi-${icon} me-2"></i>${message}`)
        .show();

    setTimeout(() => { alertDiv.fadeOut(); }, 5000);
}