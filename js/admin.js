// js/admin.js

$(document).ready(function() {
    initializeAdminDashboard();
});

function initializeAdminDashboard() {
    fetch('backend/api.php?action=getAdminStatistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadAdminStatistics(data.statistics);
                loadCategoryChart(data.charts.byCategory, data.statistics.totalItems);
                loadStatusChart(data.charts.byStatus, data.statistics.totalItems);
                loadRecentActivity(data.activity);
            } else {
                alert('Erro ao carregar dados do painel: ' + (data.message || 'Erro desconhecido'));
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Erro de conexão ao carregar dados do painel:', error);
            alert('Não foi possível carregar os dados do painel. Verifique sua conexão ou o estado do servidor.');
        });
}

function loadAdminStatistics(stats) {
    console.log('Estatísticas do admin recebidas:', stats);
    animateCounter('#totalUsersCount', stats.totalUsers);
    animateCounter('#totalItemsCount', stats.totalItems);
    animateCounter('#availableItemsCount', stats.availableItems);
    animateCounter('#tradedItemsCount', stats.tradedItems);
}

function loadCategoryChart(categoryData, totalItems) {
    const chartContainer = $('#categoryChart');
    chartContainer.empty();
    if (categoryData.length === 0) {
        chartContainer.html('<p class="text-muted">Nenhum item para exibir.</p>');
        return;
    }
    
    let html = '<div class="row">';
    categoryData.forEach(cat => {
        const percentage = totalItems > 0 ? Math.round((cat.count / totalItems) * 100) : 0;
        html += `
            <div class="col-12 mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small><strong>${cat.category}</strong></small>
                    <small>${cat.count} itens (${percentage}%)</small>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar" role="progressbar" style="width: ${percentage}%"></div>
                </div>
            </div>`;
    });
    html += '</div>';
    chartContainer.html(html);
}

function loadStatusChart(statusData, totalItems) {
    const chartContainer = $('#statusChart');
    chartContainer.empty();
    const statusMap = {
        disponivel: { label: 'Disponíveis', color: 'bg-success' },
        trocado: { label: 'Trocados', color: 'bg-info' },
        removido: { label: 'Removidos', color: 'bg-danger' }
    };
    
    let html = '<div class="row">';
    Object.keys(statusMap).forEach(statusKey => {
        const statusInfo = statusMap[statusKey];
        const data = statusData.find(s => s.status === statusKey);
        const count = data ? parseInt(data.count) : 0;
        const percentage = totalItems > 0 ? Math.round((count / totalItems) * 100) : 0;

        html += `
            <div class="col-12 mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small><strong>${statusInfo.label}</strong></small>
                    <small>${count} (${percentage}%)</small>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar ${statusInfo.color}" role="progressbar" style="width: ${percentage}%"></div>
                </div>
            </div>`;
    });
    html += '</div>';
    chartContainer.html(html);
}

function loadRecentActivity(activityData) {
    const activityContainer = $('#recentActivity');
    activityContainer.empty();
    let activities = [];

    (activityData.users || []).forEach(user => activities.push({
        type: 'user_registered', date: new Date(user.data_cadastro), description: `${user.nome} se cadastrou.`
    }));
    (activityData.items || []).forEach(item => activities.push({
        type: 'item_added', date: new Date(item.data_cadastro), description: `${item.nome_usuario} cadastrou: "${item.titulo}"`
    }));

    activities.sort((a, b) => b.date - a.date);
    activities = activities.slice(0, 10);
    $('#activityCount').text(`${activities.length} atividades`);

    if (activities.length === 0) {
        activityContainer.html('<p class="text-muted text-center">Nenhuma atividade recente.</p>');
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    activities.forEach(activity => {
        const icon = activity.type === 'user_registered' ? 'person-plus' : 'box';
        const iconColor = activity.type === 'user_registered' ? 'text-primary' : 'text-success';
        html += `
            <div class="list-group-item border-0 px-0">
                <div class="d-flex align-items-start">
                    <i class="bi bi-${icon} ${iconColor} me-3 mt-1"></i>
                    <div>
                        <p class="mb-1">${activity.description}</p>
                        <small class="text-muted">${formatRelativeTime(activity.date)}</small>
                    </div>
                </div>
            </div>`;
    });
    html += '</div>';
    activityContainer.html(html);
}

function animateCounter(selector, targetValue) {
    const $element = $(selector);
    if (!$element.length) return;
    let startValue = 0;
    const duration = 1500;
    const stepTime = 20;
    const totalSteps = duration / stepTime;
    const increment = (targetValue - startValue) / totalSteps;

    const timer = setInterval(() => {
        startValue += increment;
        if (startValue >= targetValue) {
            startValue = targetValue;
            clearInterval(timer);
        }
        $element.text(Math.ceil(startValue));
    }, stepTime);
}

function formatRelativeTime(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 1) return 'Agora mesmo';
    if (diffInMinutes < 60) return `${diffInMinutes} min atrás`;
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h atrás`;
    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays} dia(s) atrás`;
}

// Funções de Ações Rápidas (deixadas como exemplo, podem precisar de mais lógica)
function generateReport() { alert('Função Gerar Relatório chamada.'); }
function exportData() { alert('Função Exportar Dados chamada.'); }