// js/admin-users.js

let allUsersData = []; // Armazena os dados originais para filtrar e ordenar

$(document).ready(function() {
    loadUsersPage();
    $('#searchUsers, #sortBy, #sortOrder').on('input change', filterAndRenderUsers);
});

function loadUsersPage() {
    const $tableBody = $('#usersTableBody');
    $tableBody.html(`<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></td></tr>`);

    fetch('backend/api.php?action=getUsersAdmin')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allUsersData = data.users;
                updateStatCards(data.stats);
                renderUsersTable(data.users);
            } else {
                alert('Erro: ' + data.message);
                $tableBody.html('<tr><td colspan="7" class="text-center text-danger py-4">Não foi possível carregar os usuários.</td></tr>');
            }
        })
        .catch(error => {
            console.error('Erro de conexão:', error);
            $tableBody.html('<tr><td colspan="7" class="text-center text-danger py-4">Erro de conexão ao buscar usuários.</td></tr>');
        });
}

function updateStatCards(stats) {
    $('#totalUsersCount').text(stats.totalUsers);
    $('#activeUsersCount').text(stats.activeUsers);
    $('#newUsersThisMonth').text(stats.newThisMonth);
    $('#averageItemsPerUser').text(stats.averageItems);
}

function filterAndRenderUsers() {
    let filteredUsers = [...allUsersData];
    const searchTerm = $('#searchUsers').val().toLowerCase();
    const sortBy = $('#sortBy').val();
    const sortOrder = $('#sortOrder').val();

    if (searchTerm) {
        filteredUsers = filteredUsers.filter(user =>
            user.nome.toLowerCase().includes(searchTerm) || user.email.toLowerCase().includes(searchTerm)
        );
    }

    filteredUsers.sort((a, b) => {
        let valA, valB;
        switch (sortBy) {
            case 'itemCount': valA = a.itemCount; valB = b.itemCount; break;
            case 'createdAt': valA = new Date(a.data_cadastro); valB = new Date(b.data_cadastro); break;
            case 'email': valA = a.email.toLowerCase(); valB = b.email.toLowerCase(); break;
            default: valA = a.nome.toLowerCase(); valB = b.nome.toLowerCase(); break;
        }
        if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
        return 0;
    });

    renderUsersTable(filteredUsers);
}

function renderUsersTable(users) {
    const $tableBody = $('#usersTableBody');
    $tableBody.empty();

    if (users.length === 0) {
        $tableBody.html('<tr><td colspan="7" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>');
        return;
    }

    users.forEach(user => {
        const joinDate = new Date(user.data_cadastro).toLocaleDateString('pt-BR');
        $tableBody.append(`
            <tr>
                <td><strong>${user.nome}</strong></td>
                <td>${user.email}</td>
                <td>${user.localizacao || 'N/A'}</td>
                <td>${user.telefone || 'N/A'}</td>
                <td>${joinDate}</td>
                <td><span class="badge bg-primary rounded-pill">${user.itemCount}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" title="Ver Detalhes"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-danger" title="Excluir Usuário"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `);
    });
}