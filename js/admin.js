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

// Funções de Ações Rápidas
function generateReport() {
    if (!confirm('Deseja gerar o relatório completo do sistema? Isso pode levar alguns segundos.')) {
        return;
    }
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm me-2"></i>Gerando...';
    
    fetch('backend/api.php?action=generateReport')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Converter dados para CSV/Excel
                generateExcelReport(data.data);
                
                // Mostrar mensagem de sucesso
                setTimeout(() => {
                    alert('Relatório gerado com sucesso! O download deve iniciar automaticamente.');
                }, 500);
            } else {
                alert('Erro ao gerar relatório: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao gerar relatório:', error);
            alert('Erro de conexão ao gerar relatório');
        })
        .finally(() => {
            // Restaurar botão
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function exportData() {
    if (!confirm('Deseja exportar todos os dados do sistema em formato JSON?')) {
        return;
    }
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-spinner spinner-border spinner-border-sm me-2"></i>Exportando...';
    
    fetch('backend/api.php?action=exportData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fazer download do JSON
                downloadJSON(data.data, data.filename);
                
                // Mostrar mensagem de sucesso
                setTimeout(() => {
                    alert('Dados exportados com sucesso! O arquivo JSON foi baixado.');
                }, 500);
            } else {
                alert('Erro ao exportar dados: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao exportar dados:', error);
            alert('Erro de conexão ao exportar dados');
        })
        .finally(() => {
            // Restaurar botão
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function generateExcelReport(data) {
    // Criar conteúdo CSV
    let csv = [];
    
    // Cabeçalho do relatório
    csv.push(['RELATÓRIO ECOSWAP - GERADO EM: ' + new Date(data.generated_at).toLocaleString('pt-BR')]);
    csv.push([]);
    
    // Estatísticas gerais
    csv.push(['=== ESTATÍSTICAS GERAIS ===']);
    csv.push(['Total de Usuários:', data.statistics.total_usuarios]);
    csv.push(['Total de Itens:', data.statistics.total_itens]);
    csv.push(['Itens Disponíveis:', data.statistics.total_disponiveis]);
    csv.push(['Itens Trocados:', data.statistics.total_trocados]);
    csv.push([]);
    
    // Dados dos usuários
    csv.push(['=== USUÁRIOS ===']);
    csv.push(['Nome', 'Email', 'Telefone', 'Localização', 'Data Cadastro', 'Total Itens', 'Disponíveis', 'Trocados', 'Removidos']);
    
    data.users.forEach(user => {
        csv.push([
            user.usuario_nome,
            user.usuario_email,
            user.usuario_telefone || 'N/A',
            user.usuario_localizacao || 'N/A',
            new Date(user.usuario_data_cadastro).toLocaleDateString('pt-BR'),
            user.total_itens,
            user.itens_disponiveis,
            user.itens_trocados,
            user.itens_removidos
        ]);
    });
    
    csv.push([]);
    
    // Dados dos itens
    csv.push(['=== ITENS ===']);
    csv.push(['Título', 'Descrição', 'Categoria', 'Condição', 'Status', 'Data Cadastro', 'Usuário', 'Email Usuário']);
    
    data.items.forEach(item => {
        csv.push([
            item.titulo,
            item.descricao,
            item.categoria,
            item.condicao,
            item.status,
            new Date(item.data_cadastro).toLocaleDateString('pt-BR'),
            item.usuario_nome,
            item.usuario_email
        ]);
    });
    
    // Converter para string CSV
    const csvContent = csv.map(row => {
        return row.map(cell => {
            // Escapar células que contenham vírgulas, aspas ou quebras de linha
            if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                return '"' + cell.replace(/"/g, '""') + '"';
            }
            return cell;
        }).join(',');
    }).join('\n');
    
    // Fazer download
    const filename = `relatorio_ecoswap_${new Date().toISOString().slice(0,10)}.csv`;
    downloadFile(csvContent, filename, 'text/csv;charset=utf-8;');
}

function downloadJSON(data, filename) {
    const jsonString = JSON.stringify(data, null, 2);
    downloadFile(jsonString, filename, 'application/json;charset=utf-8;');
}

function downloadFile(content, filename, mimeType) {
    // Adicionar BOM para UTF-8 (especialmente importante para CSV com acentos)
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + content], { type: mimeType });
    
    // Criar link temporário para download
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    
    // Adicionar ao DOM temporariamente e clicar
    document.body.appendChild(link);
    link.click();
    
    // Limpar
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}