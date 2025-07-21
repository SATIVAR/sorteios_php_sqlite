# Testes do Sistema de Sorteios

Este diretório contém os scripts de teste para validar o funcionamento, performance e segurança do Sistema de Sorteios.

## Estrutura de Testes

### Testes Funcionais
- `test_fluxo_sorteio.php`: Testa o fluxo completo de criação e execução de sorteio
- `test_participacao_publica.php`: Testa o sistema de participação pública
- `test_relatorios.php`: Testa a geração de relatórios e exportações
- `test_responsividade.php`: Verifica a responsividade em diferentes dispositivos

### Testes de Performance e Segurança
- `test_performance_grande_volume.php`: Testa o sistema com grande volume de participantes (1000+)
- `test_seguranca.php`: Valida as proteções de segurança implementadas
- `test_performance_hospedagem.php`: Verifica a performance em ambiente de hospedagem compartilhada
- `test_compatibilidade_php.php`: Testa a compatibilidade com diferentes versões do PHP

### Scripts de Execução
- `run_functional_tests.php`: Executa todos os testes funcionais
- `run_performance_security_tests.php`: Executa todos os testes de performance e segurança
- `run_all_tests.php`: Executa todos os testes do sistema

## Como Executar os Testes

Para executar todos os testes:

```bash
php tests/run_all_tests.php
```

Para executar apenas os testes funcionais:

```bash
php tests/run_functional_tests.php
```

Para executar apenas os testes de performance e segurança:

```bash
php tests/run_performance_security_tests.php
```

Para executar um teste específico:

```bash
php tests/test_fluxo_sorteio.php
```

## Requisitos para Execução dos Testes

- PHP 7.4+ (recomendado PHP 8.0+)
- Extensões: PDO, PDO_SQLite, JSON, MBString, FileInfo
- Memória: 64MB mínimo
- Tempo de execução: 300 segundos mínimo para testes completos

## Interpretação dos Resultados

Os testes utilizam os seguintes símbolos para indicar o resultado:

- ✓: Teste bem-sucedido
- ✗: Teste falhou
- ⚠: Aviso (o teste passou, mas com ressalvas)

## Notas Importantes

1. Os testes de grande volume podem demorar vários minutos para serem concluídos
2. Os testes de compatibilidade são baseados em análise estática e podem não detectar todos os problemas
3. Os testes de performance simulam um ambiente de hospedagem compartilhada com recursos limitados
4. O banco de dados de teste é criado separadamente e não afeta o banco de dados principal