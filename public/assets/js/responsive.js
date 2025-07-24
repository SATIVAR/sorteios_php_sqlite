/**
 * Scripts de Responsividade - Sistema de Sorteios
 * Implementa melhorias de responsividade para diferentes dispositivos
 */

class ResponsiveManager {
    constructor() {
        this.breakpoints = {
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            '2xl': 1536
        };
        
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.init();
    }
    
    init() {
        this.setupResponsiveTables();
        this.setupResponsiveNavigation();
        this.setupResponsiveImages();
        this.setupOrientationChanges();
        this.setupResizeListener();
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        
        if (width < this.breakpoints.sm) return 'xs';
        if (width < this.breakpoints.md) return 'sm';
        if (width < this.breakpoints.lg) return 'md';
        if (width < this.breakpoints.xl) return 'lg';
        if (width < this.breakpoints['2xl']) return 'xl';
        return '2xl';
    }
    
    setupResponsiveTables() {
        // Converter tabelas para formato responsivo em dispositivos móveis
        const tables = document.querySelectorAll('.table-responsive-stack');
        
        tables.forEach(table => {
            const thElements = table.querySelectorAll('th');
            const tdElements = table.querySelectorAll('td');
            
            // Adicionar atributos data-label para cada célula baseado no cabeçalho
            tdElements.forEach(td => {
                const index = Array.from(td.parentElement.children).indexOf(td);
                if (thElements[index]) {
                    td.setAttribute('data-label', thElements[index].textContent);
                }
            });
        });
    }
    
    setupResponsiveNavigation() {
        // Ajustar navegação para dispositivos móveis
        const navItems = document.querySelectorAll('.nav-responsive .nav-item');
        
        if (this.isMobile()) {
            navItems.forEach(item => {
                item.classList.add('w-full', 'mb-2');
            });
        } else {
            navItems.forEach(item => {
                item.classList.remove('w-full', 'mb-2');
            });
        }
    }
    
    setupResponsiveImages() {
        // Carregar imagens diferentes baseado no tamanho da tela
        document.querySelectorAll('[data-src-mobile], [data-src-tablet], [data-src-desktop]').forEach(img => {
            this.updateResponsiveImage(img);
        });
    }
    
    updateResponsiveImage(img) {
        const mobile = img.getAttribute('data-src-mobile');
        const tablet = img.getAttribute('data-src-tablet');
        const desktop = img.getAttribute('data-src-desktop');
        
        if (this.isMobile() && mobile) {
            img.src = mobile;
        } else if (this.isTablet() && tablet) {
            img.src = tablet;
        } else if (desktop) {
            img.src = desktop;
        }
    }
    
    setupOrientationChanges() {
        // Detectar mudanças de orientação
        window.addEventListener('orientationchange', () => {
            this.handleOrientationChange();
        });
    }
    
    handleOrientationChange() {
        // Ajustar elementos baseado na orientação
        const isLandscape = window.matchMedia('(orientation: landscape)').matches;
        
        if (isLandscape && this.isMobile()) {
            // Ajustes para modo paisagem em dispositivos móveis
            document.querySelectorAll('.modal-content').forEach(modal => {
                modal.style.maxHeight = '80vh';
            });
        } else {
            // Restaurar para modo retrato
            document.querySelectorAll('.modal-content').forEach(modal => {
                modal.style.maxHeight = '';
            });
        }
    }
    
    setupResizeListener() {
        // Detectar mudanças de tamanho da janela
        let resizeTimeout;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const newBreakpoint = this.getCurrentBreakpoint();
                
                if (newBreakpoint !== this.currentBreakpoint) {
                    this.currentBreakpoint = newBreakpoint;
                    this.handleBreakpointChange();
                }
            }, 250);
        });
    }
    
    handleBreakpointChange() {
        // Atualizar elementos baseado no novo breakpoint
        this.setupResponsiveNavigation();
        this.setupResponsiveImages();
        
        // Disparar evento personalizado para outros componentes
        const event = new CustomEvent('breakpointChanged', {
            detail: { breakpoint: this.currentBreakpoint }
        });
        
        document.dispatchEvent(event);
    }
    
    isMobile() {
        return this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
    }
    
    isTablet() {
        return this.currentBreakpoint === 'md';
    }
    
    isDesktop() {
        return this.currentBreakpoint === 'lg' || this.currentBreakpoint === 'xl' || this.currentBreakpoint === '2xl';
    }
    
    // Método para converter tabelas normais em responsivas
    makeTableResponsive(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        table.classList.add('table-responsive-stack');
        
        const thElements = table.querySelectorAll('th');
        const tdElements = table.querySelectorAll('td');
        
        // Adicionar atributos data-label para cada célula baseado no cabeçalho
        tdElements.forEach(td => {
            const index = Array.from(td.parentElement.children).indexOf(td);
            if (thElements[index]) {
                td.setAttribute('data-label', thElements[index].textContent);
            }
        });
    }
    
    // Método para ajustar formulários em dispositivos móveis
    makeFormResponsive(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;
        
        if (this.isMobile()) {
            form.classList.add('form-responsive');
            
            // Tornar botões de envio full-width em dispositivos móveis
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.classList.add('btn-block-sm');
            });
        } else {
            form.classList.remove('form-responsive');
            
            // Restaurar botões de envio
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.classList.remove('btn-block-sm');
            });
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.responsiveManager = new ResponsiveManager();
    
    // Tornar tabelas específicas responsivas (apenas se tiverem ID)
    document.querySelectorAll('table.table').forEach(table => {
        if (table.id) {
            table.classList.add('table-responsive-stack');
            window.responsiveManager.makeTableResponsive('#' + table.id);
        }
    });
    
    // Tornar formulários específicos responsivos (apenas se tiverem ID)
    document.querySelectorAll('form').forEach(form => {
        if (form.id) {
            window.responsiveManager.makeFormResponsive('#' + form.id);
        }
    });
});