// Sistema de Logs Visual - Frontend (admin only)
document.addEventListener('DOMContentLoaded', function () {
  if (!window.isAdminLogsEnabled) return;

  // Tipos de log disponíveis
  const logTypes = [
    { value: 'system', label: 'Sistema' },
    { value: 'activity', label: 'Atividades' },
    { value: 'backup', label: 'Backups' }
  ];
  let currentType = 'system';

  // Bubble
  const bubble = document.createElement('div');
  bubble.id = 'logs-bubble';
  bubble.className = 'fixed bottom-6 right-6 z-50 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center cursor-pointer hover:bg-blue-700 transition w-14 h-14';
  bubble.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
  document.body.appendChild(bubble);

  // Modal
  const modal = document.createElement('div');
  modal.id = 'logs-modal';
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden';
  modal.innerHTML = `
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full p-6 relative">
      <button id="logs-modal-close" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
      <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Logs do Sistema</h2>
      <div class="mb-3">
        <label for="logs-type-select" class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Tipo:</label>
        <select id="logs-type-select" class="border rounded px-2 py-1 text-sm dark:bg-gray-800 dark:text-white">
          ${logTypes.map(t => `<option value="${t.value}">${t.label}</option>`).join('')}
        </select>
      </div>
      <div id="logs-content" class="overflow-y-auto max-h-96 font-mono text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700"></div>
    </div>
  `;
  document.body.appendChild(modal);

  // Abrir modal
  bubble.addEventListener('click', function () {
    modal.classList.remove('hidden');
    fetchLogs(currentType);
  });
  // Fechar modal
  modal.querySelector('#logs-modal-close').onclick = function () {
    modal.classList.add('hidden');
  };
  // Fechar ao clicar fora
  modal.addEventListener('click', function (e) {
    if (e.target === modal) modal.classList.add('hidden');
  });

  // Troca de tipo de log
  const typeSelect = modal.querySelector('#logs-type-select');
  typeSelect.value = currentType;
  typeSelect.addEventListener('change', function () {
    currentType = this.value;
    fetchLogs(currentType);
  });

  // Buscar logs via AJAX
  function fetchLogs(type) {
    const content = document.getElementById('logs-content');
    content.innerHTML = '<div class="text-center text-gray-400 py-8">Carregando logs...</div>';
    fetch('/ajax/logs.php?type=' + encodeURIComponent(type))
      .then(r => r.json())
      .then(data => {
        if (data.success && Array.isArray(data.logs)) {
          content.innerHTML = data.logs.map(l => `<div>${l.replace(/</g, '&lt;')}</div>`).join('');
        } else {
          content.innerHTML = '<div class="text-red-500">Erro ao carregar logs</div>';
        }
      })
      .catch(() => {
        content.innerHTML = '<div class="text-red-500">Erro ao carregar logs</div>';
      });
  }
});
