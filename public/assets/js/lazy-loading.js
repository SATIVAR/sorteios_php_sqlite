/**
 * Sistema de Sorteios - Lazy Loading
 * Implementa carregamento preguiçoso para listas grandes
 */

const LazyLoader = {
    // Configurações
    config: {
        threshold: 0.1, // Porcentagem visível para carregar
        loadMoreThreshold: 200, // Pixels antes do final da lista para carregar mais
        defaultLimit: 20, // Limite padrão por página
        defaultOffset: 0, // Offset inicial
        loadingTemplate: `
            <div class="flex justify-center items-center p-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Carregando...</span>
            </div>
        `
    },
    
    // Armazenamento de estado
    state: {
        observers: {},
        containers: {},
        loading: {}
    },
    
    /**
     * Inicializa lazy loading para um container
     */
    init: function(containerId, options = {}) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Mesclar opções com padrões
        const settings = {
            ...this.config,
            ...options,
            containerId
        };
        
        // Armazenar configurações
        this.state.containers[containerId] = settings;
        
        // Configurar observador de interseção para imagens
        this.setupImageObserver(containerId);
        
        // Configurar carregamento infinito
        if (settings.infiniteScroll) {
            this.setupInfiniteScroll(containerId);
        }
        
        // Carregar dados iniciais
        if (settings.autoLoad) {
            this.loadItems(containerId);
        }
    },
    
    /**
     * Configura observador para lazy loading de imagens
     */
    setupImageObserver: function(containerId) {
        // Remover observador existente
        if (this.state.observers[containerId]) {
            this.state.observers[containerId].disconnect();
        }
        
        // Criar novo observador
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            threshold: this.config.threshold
        });
        
        // Armazenar observador
        this.state.observers[containerId] = observer;
        
        // Observar imagens existentes
        this.observeImages(containerId);
    },
    
    /**
     * Observa imagens no container
     */
    observeImages: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const observer = this.state.observers[containerId];
        if (!observer) return;
        
        // Observar todas as imagens com data-src
        container.querySelectorAll('img[data-src]').forEach(img => {
            observer.observe(img);
        });
    },
    
    /**
     * Configura carregamento infinito
     */
    setupInfiniteScroll: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const settings = this.state.containers[containerId];
        if (!settings) return;
        
        // Adicionar elemento de carregamento
        const loadingElement = document.createElement('div');
        loadingElement.id = `${containerId}-loading`;
        loadingElement.innerHTML = this.config.loadingTemplate;
        loadingElement.style.display = 'none';
        container.appendChild(loadingElement);
        
        // Configurar observador de interseção para elemento de carregamento
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.state.loading[containerId]) {
                    this.loadMoreItems(containerId);
                }
            });
        }, {
            rootMargin: `0px 0px ${this.config.loadMoreThreshold}px 0px`
        });
        
        // Observar elemento de carregamento
        observer.observe(loadingElement);
    },
    
    /**
     * Carrega itens iniciais
     */
    loadItems: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const settings = this.state.containers[containerId];
        if (!settings || !settings.dataUrl) return;
        
        // Marcar como carregando
        this.state.loading[containerId] = true;
        
        // Mostrar indicador de carregamento
        container.innerHTML = this.config.loadingTemplate;
        
        // Construir parâmetros
        const params = {
            ...settings.params,
            limit: settings.limit || this.config.defaultLimit,
            offset: settings.offset || this.config.defaultOffset
        };
        
        // Fazer requisição AJAX
        $.ajax({
            url: settings.dataUrl,
            data: params,
            method: 'GET',
            success: (response) => {
                // Verificar resposta
                if (!response.success) {
                    console.error('Erro ao carregar itens:', response.message);
                    container.innerHTML = `<div class="p-4 text-center text-red-500">Erro ao carregar dados</div>`;
                    return;
                }
                
                // Renderizar itens
                this.renderItems(containerId, response.data, false);
                
                // Atualizar estado
                this.state.loading[containerId] = false;
                
                // Verificar se há mais itens para carregar
                if (settings.infiniteScroll) {
                    const loadingElement = document.getElementById(`${containerId}-loading`);
                    if (loadingElement) {
                        const hasMore = response.data.length >= params.limit;
                        loadingElement.style.display = hasMore ? 'block' : 'none';
                    }
                }
                
                // Disparar evento de itens carregados
                container.dispatchEvent(new CustomEvent('itemsLoaded', {
                    detail: {
                        items: response.data,
                        params
                    }
                }));
            },
            error: (xhr, status, error) => {
                console.error('Erro na requisição AJAX:', error);
                container.innerHTML = `<div class="p-4 text-center text-red-500">Erro ao carregar dados</div>`;
                this.state.loading[containerId] = false;
            }
        });
    },
    
    /**
     * Carrega mais itens (para scroll infinito)
     */
    loadMoreItems: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const settings = this.state.containers[containerId];
        if (!settings || !settings.dataUrl) return;
        
        // Verificar se já está carregando
        if (this.state.loading[containerId]) return;
        
        // Marcar como carregando
        this.state.loading[containerId] = true;
        
        // Mostrar indicador de carregamento
        const loadingElement = document.getElementById(`${containerId}-loading`);
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }
        
        // Calcular novo offset
        const currentItems = container.querySelectorAll('[data-item-id]').length;
        const offset = currentItems;
        
        // Construir parâmetros
        const params = {
            ...settings.params,
            limit: settings.limit || this.config.defaultLimit,
            offset: offset
        };
        
        // Fazer requisição AJAX
        $.ajax({
            url: settings.dataUrl,
            data: params,
            method: 'GET',
            success: (response) => {
                // Verificar resposta
                if (!response.success) {
                    console.error('Erro ao carregar mais itens:', response.message);
                    if (loadingElement) {
                        loadingElement.style.display = 'none';
                    }
                    this.state.loading[containerId] = false;
                    return;
                }
                
                // Renderizar itens
                this.renderItems(containerId, response.data, true);
                
                // Atualizar estado
                this.state.loading[containerId] = false;
                
                // Verificar se há mais itens para carregar
                if (loadingElement) {
                    const hasMore = response.data.length >= params.limit;
                    loadingElement.style.display = hasMore ? 'block' : 'none';
                }
                
                // Disparar evento de itens carregados
                container.dispatchEvent(new CustomEvent('moreItemsLoaded', {
                    detail: {
                        items: response.data,
                        params
                    }
                }));
            },
            error: (xhr, status, error) => {
                console.error('Erro na requisição AJAX:', error);
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                this.state.loading[containerId] = false;
            }
        });
    },
    
    /**
     * Renderiza itens no container
     */
    renderItems: function(containerId, items, append = false) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const settings = this.state.containers[containerId];
        if (!settings) return;
        
        // Verificar se há função de renderização personalizada
        if (typeof settings.renderItem === 'function') {
            let html = '';
            
            // Renderizar cada item
            items.forEach(item => {
                html += settings.renderItem(item);
            });
            
            // Adicionar ao container
            if (append) {
                // Remover elemento de carregamento
                const loadingElement = document.getElementById(`${containerId}-loading`);
                if (loadingElement) {
                    loadingElement.remove();
                }
                
                // Adicionar novos itens
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                while (tempDiv.firstChild) {
                    container.appendChild(tempDiv.firstChild);
                }
                
                // Readicionar elemento de carregamento
                if (settings.infiniteScroll) {
                    const newLoadingElement = document.createElement('div');
                    newLoadingElement.id = `${containerId}-loading`;
                    newLoadingElement.innerHTML = this.config.loadingTemplate;
                    container.appendChild(newLoadingElement);
                }
            } else {
                container.innerHTML = html;
                
                // Adicionar elemento de carregamento
                if (settings.infiniteScroll) {
                    const loadingElement = document.createElement('div');
                    loadingElement.id = `${containerId}-loading`;
                    loadingElement.innerHTML = this.config.loadingTemplate;
                    container.appendChild(loadingElement);
                }
            }
        } else {
            console.error('Função de renderização não definida para o container:', containerId);
        }
        
        // Observar novas imagens
        this.observeImages(containerId);
    },
    
    /**
     * Recarrega itens do container
     */
    reload: function(containerId, params = {}) {
        const settings = this.state.containers[containerId];
        if (!settings) return;
        
        // Atualizar parâmetros
        if (params) {
            settings.params = {
                ...settings.params,
                ...params
            };
        }
        
        // Resetar offset
        settings.offset = this.config.defaultOffset;
        
        // Carregar itens
        this.loadItems(containerId);
    }
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar lazy loading para containers com data-lazy-load
    document.querySelectorAll('[data-lazy-load]').forEach(container => {
        const containerId = container.id;
        if (!containerId) return;
        
        // Obter opções do atributo data
        const options = {
            dataUrl: container.getAttribute('data-url'),
            infiniteScroll: container.getAttribute('data-infinite-scroll') === 'true',
            autoLoad: container.getAttribute('data-auto-load') !== 'false',
            limit: parseInt(container.getAttribute('data-limit')) || LazyLoader.config.defaultLimit
        };
        
        // Obter função de renderização do atributo data
        const renderFunctionName = container.getAttribute('data-render-function');
        if (renderFunctionName && window[renderFunctionName]) {
            options.renderItem = window[renderFunctionName];
        }
        
        // Inicializar lazy loading
        LazyLoader.init(containerId, options);
    });
});