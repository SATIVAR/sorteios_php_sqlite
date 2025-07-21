# Guia de Acessibilidade e Responsividade - Sistema de Sorteios

Este documento descreve as melhorias de acessibilidade e responsividade implementadas no Sistema de Sorteios, seguindo as diretrizes WCAG 2.1 e práticas de design responsivo.

## Melhorias de Acessibilidade

### Navegação por Teclado

- **Skip Link**: Um link "Pular para o conteúdo principal" foi adicionado para permitir que usuários de teclado pulem diretamente para o conteúdo principal.
- **Foco Visível**: Todos os elementos interativos têm um indicador de foco visível quando navegados por teclado.
- **Ordem de Tabulação**: A ordem de tabulação foi otimizada para seguir o fluxo lógico da página.
- **Trap de Foco**: Modais e diálogos implementam trap de foco para manter o foco dentro do componente quando aberto.

### Suporte a Leitores de Tela

- **Atributos ARIA**: Foram adicionados atributos ARIA apropriados para melhorar a experiência com leitores de tela.
- **Textos Alternativos**: Todas as imagens têm textos alternativos descritivos.
- **Anúncios Dinâmicos**: Mudanças importantes na interface são anunciadas para leitores de tela usando `aria-live`.
- **Landmarks**: Foram adicionadas landmarks ARIA para facilitar a navegação por regiões da página.

### Contraste e Legibilidade

- **Contraste de Cores**: Todas as cores foram ajustadas para garantir uma relação de contraste adequada.
- **Tamanho de Texto**: O texto tem tamanho adequado e pode ser redimensionado até 200% sem perda de funcionalidade.
- **Espaçamento**: Foi adicionado espaçamento adequado entre elementos para melhorar a legibilidade.

### Formulários Acessíveis

- **Labels Associados**: Todos os campos de formulário têm labels explicitamente associados.
- **Mensagens de Erro**: Erros de validação são claramente indicados e associados aos campos correspondentes.
- **Campos Obrigatórios**: Campos obrigatórios são claramente marcados.
- **Instruções**: Instruções para preenchimento de formulários são fornecidas quando necessário.

## Melhorias de Responsividade

### Abordagem Mobile-First

- O design foi implementado seguindo a abordagem mobile-first, garantindo uma experiência otimizada em dispositivos móveis.
- Breakpoints foram definidos para adaptar o layout a diferentes tamanhos de tela.

### Layout Responsivo

- **Grids Flexíveis**: Uso de grids flexíveis que se adaptam a diferentes tamanhos de tela.
- **Imagens Responsivas**: Imagens são redimensionadas proporcionalmente e otimizadas para diferentes dispositivos.
- **Tabelas Responsivas**: Tabelas se adaptam a telas pequenas, reorganizando o conteúdo quando necessário.

### Navegação Responsiva

- **Menu Mobile**: O menu principal se adapta a dispositivos móveis, transformando-se em um menu hambúrguer.
- **Sidebar Colapsável**: A sidebar pode ser recolhida em dispositivos móveis para maximizar o espaço de conteúdo.

### Otimizações para Diferentes Dispositivos

- **Orientação**: Ajustes específicos para orientação paisagem e retrato em dispositivos móveis.
- **Altura da Tela**: Adaptações para diferentes alturas de tela, garantindo que o conteúdo importante esteja sempre visível.

## Como Usar os Recursos de Acessibilidade

### Para Usuários de Teclado

1. Use a tecla `Tab` para navegar entre elementos interativos.
2. Use `Enter` ou `Space` para ativar botões e links.
3. Use `Esc` para fechar modais e diálogos.
4. Use o link "Pular para o conteúdo principal" no início da página para ir diretamente ao conteúdo.

### Para Usuários de Leitores de Tela

1. Use as landmarks ARIA para navegar entre as diferentes regiões da página.
2. Todas as imagens têm descrições alternativas que serão lidas pelos leitores de tela.
3. Mudanças dinâmicas na interface serão anunciadas automaticamente.

### Para Usuários com Baixa Visão

1. Use o botão de alternar tema para mudar entre os modos claro e escuro.
2. O site é compatível com as ferramentas de zoom do navegador.
3. Todos os textos têm contraste adequado com o fundo.

## Testes e Conformidade

As melhorias de acessibilidade e responsividade foram testadas nos seguintes cenários:

- **Navegadores**: Chrome, Firefox, Safari, Edge
- **Dispositivos**: Desktop, Tablet, Smartphone
- **Tecnologias Assistivas**: NVDA, VoiceOver
- **Ferramentas de Validação**: Lighthouse, axe, WAVE

## Recursos Adicionais

- [Web Content Accessibility Guidelines (WCAG) 2.1](https://www.w3.org/TR/WCAG21/)
- [MDN Web Docs - Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [The A11Y Project](https://www.a11yproject.com/)
- [Responsive Web Design Basics](https://web.dev/responsive-web-design-basics/)