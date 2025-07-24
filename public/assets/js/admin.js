/**
 * JavaScript para Dashboard Administrativo
 * Animações, gráficos e interações do dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes do dashboard
    initCounterAnimations();
    initParticipationChart();
    initRealTimeUpdates();
    initTooltips();
});

/**
 * Animação de contadores nos cards de métricas
 */
function initCounterAnimations() {
    const counters = document.querySelectorAll('.counter');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    };
    
    // Usar Intersection Observer para animar quando visível
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
}

/**
 * Inicializar gráfico de participação
 */
function initParticipationChart() {
    const canvas = document.getElementById('participationChart');
    if (!canvas || !window.dashboardData) return;
    
    const ctx = canvas.getContext('2d');
    
    // Configuração do gradiente
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: window.dashboardData.chartLabels,
            datasets: [{
                label: 'Participantes',
                data: window.dashboardData.chartData,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: 'rgb(37, 99, 235)',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return 'Data: ' + context[0].label;
                        },
                        label: function(context) {
                            return 'Participantes: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6B7280',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(107, 114, 128, 0.1)',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: '#6B7280',
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return Number.isInteger(value) ? value : '';
                        }
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

/**
 * Atualizações em tempo real via AJAX
 */
function initRealTimeUpdates() {
    // Atualizar métricas a cada 30 segundos
    setInterval(updateMetrics, 30000);
    
    // Primeira atualização após 5 segundos
    setTimeout(updateMetrics, 5000);
}

/**
 * Atualizar métricas via AJAX
 */
async function updateMetrics() {
    try {
        const response = await fetch('ajax/dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ action: 'get_metrics' })
        });
        
        if (!response.ok) throw new Error('Erro na requisição');
        
        const data = await response.json();
        
        if (data.success) {
            updateMetricCards(data.data);
            showUpdateIndicator();
        }
        
    } catch (error) {
        console.error('Erro ao atualizar métricas:', error);
    }
}

/**
 * Atualizar cards de métricas
 */
function updateMetricCards(metrics) {
    const counters = document.querySelectorAll('.counter');
    
    counters.forEach(counter => {
        const currentValue = parseInt(counter.textContent);
        const newValue = parseInt(counter.getAttribute('data-target'));
        
        // Atualizar apenas se o valor mudou
        if (currentValue !== newValue) {
            animateCounterUpdate(counter, currentValue, newValue);
        }
    });
}

/**
 * Animar atualização de contador
 */
function animateCounterUpdate(counter, from, to) {
    const duration = 1000;
    const increment = (to - from) / (duration / 16);
    let current = from;
    
    const updateCounter = () => {
        current += increment;
        if ((increment > 0 && current < to) || (increment < 0 && current > to)) {
            counter.textContent = Math.floor(current);
            requestAnimationFrame(updateCounter);
        } else {
            counter.textContent = to;
            counter.setAttribute('data-target', to);
        }
    };
    
    updateCounter();
}

/**
 * Mostrar indicador de atualização
 */
function showUpdateIndicator() {
    const indicator = document.querySelector('.bg-green-100.text-green-800');
    if (indicator) {
        indicator.classList.add('animate-pulse');
        setTimeout(() => {
            indicator.classList.remove('animate-pulse');
        }, 2000);
    }
}

/**
 * Inicializar tooltips
 */
function initTooltips() {
    // Adicionar tooltips aos cards de métricas
    const metricCards = document.querySelectorAll('[data-tooltip]');
    
    metricCards.forEach(card => {
        card.addEventListener('mouseenter', showTooltip);
        card.addEventListener('mouseleave', hideTooltip);
    });
}

/**
 * Mostrar tooltip
 */
function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-3 py-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg';
    tooltip.textContent = event.target.getAttribute('data-tooltip');
    
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    event.target._tooltip = tooltip;
}

/**
 * Esconder tooltip
 */
function hideTooltip(event) {
    if (event.target._tooltip) {
        document.body.removeChild(event.target._tooltip);
        delete event.target._tooltip;
    }
}

/**
 * Função para mostrar notificações
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `
        max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden
        transform transition-all duration-300 ease-in-out translate-x-full opacity-0
    `;
    
    const bgColor = {
        'success': 'bg-green-50 border-green-200',
        'error': 'bg-red-50 border-red-200',
        'warning': 'bg-yellow-50 border-yellow-200',
        'info': 'bg-blue-50 border-blue-200'
    }[type] || 'bg-blue-50 border-blue-200';
    
    const iconColor = {
        'success': 'text-green-400',
        'error': 'text-red-400',
        'warning': 'text-yellow-400',
        'info': 'text-blue-400'
    }[type] || 'text-blue-400';
    
    notification.innerHTML = `
        <div class="p-4 ${bgColor} border-l-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 ${iconColor}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button class="inline-flex text-gray-400 hover:text-gray-600" onclick="this.closest('.max-w-sm').remove()">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    // Auto remover após 5 segundos
    setTimeout(() => {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

/**
 * Função para atualizar gráfico com novos dados
 */
function updateChart(newLabels, newData) {
    const canvas = document.getElementById('participationChart');
    if (!canvas || !window.participationChart) return;
    
    window.participationChart.data.labels = newLabels;
    window.participationChart.data.datasets[0].data = newData;
    window.participationChart.update('active');
}

/**
 * Função para exportar dados do dashboard
 */
async function exportDashboardData(format = 'csv') {
    try {
        const response = await fetch('ajax/dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ 
                action: 'export_data',
                format: format
            })
        });
        
        if (!response.ok) throw new Error('Erro na requisição');
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `dashboard_${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Dados exportados com sucesso!', 'success');
        
    } catch (error) {
        console.error('Erro ao exportar dados:', error);
        showNotification('Erro ao exportar dados', 'error');
    }
}

// Expor funções globalmente para uso em outros scripts
window.dashboardUtils = {
    showNotification,
    updateChart,
    exportDashboardData,
    updateMetrics
};