<?php
/**
 * Schema do Banco de Dados - Sistema de Sorteios
 * Script de criação de tabelas, índices e sistema de migração
 */

class DatabaseSchema {
    private $db;
    private $currentVersion = 1;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Inicializa o schema do banco de dados
     */
    public function initialize() {
        try {
            // Cria tabela de controle de versão se não existir
            $this->createVersionTable();
            
            // Verifica versão atual do banco
            $currentDbVersion = $this->getCurrentDatabaseVersion();
            
            // Executa migrações necessárias
            if ($currentDbVersion < $this->currentVersion) {
                $this->runMigrations($currentDbVersion);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao inicializar schema: " . $e->getMessage());
            throw new Exception("Falha na inicialização do banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Cria tabela de controle de versão
     */
    private function createVersionTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS schema_version (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                version INTEGER NOT NULL,
                description TEXT,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->db->query($sql);
        
        // Insere versão inicial se tabela estiver vazia
        $count = $this->db->fetchOne("SELECT COUNT(*) as count FROM schema_version");
        if ($count['count'] == 0) {
            $this->db->query(
                "INSERT INTO schema_version (version, description) VALUES (?, ?)",
                [0, 'Versão inicial']
            );
        }
    }
    
    /**
     * Obtém versão atual do banco
     */
    private function getCurrentDatabaseVersion() {
        $result = $this->db->fetchOne(
            "SELECT MAX(version) as version FROM schema_version"
        );
        
        return (int) $result['version'];
    }
    
    /**
     * Executa migrações necessárias
     */
    private function runMigrations($fromVersion) {
        $migrations = $this->getMigrations();
        
        foreach ($migrations as $version => $migration) {
            if ($version > $fromVersion) {
                echo "Executando migração para versão $version...\n";
                
                try {
                    $this->db->beginTransaction();
                    
                    // Executa migração
                    $migration['up']();
                    
                    // Registra versão aplicada
                    $this->db->query(
                        "INSERT INTO schema_version (version, description) VALUES (?, ?)",
                        [$version, $migration['description']]
                    );
                    
                    $this->db->commit();
                    
                    echo "Migração $version concluída com sucesso.\n";
                    
                } catch (Exception $e) {
                    $this->db->rollback();
                    throw new Exception("Erro na migração $version: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Define todas as migrações disponíveis
     */
    private function getMigrations() {
        return [
            1 => [
                'description' => 'Criação das tabelas principais do sistema',
                'up' => function() {
                    $this->createInitialTables();
                    $this->createIndexes();
                }
            ]
        ];
    }
    
    /**
     * Cria tabelas iniciais do sistema
     */
    private function createInitialTables() {
        // Tabela de configurações do sistema
        $this->db->query("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome_empresa TEXT NOT NULL,
                regras_padrao TEXT,
                admin_email TEXT,
                admin_password TEXT,
                timezone TEXT DEFAULT 'America/Sao_Paulo',
                theme TEXT DEFAULT 'light',
                backup_enabled INTEGER DEFAULT 1,
                backup_frequency INTEGER DEFAULT 24,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de sorteios
        $this->db->query("
            CREATE TABLE IF NOT EXISTS sorteios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                descricao TEXT,
                data_inicio DATETIME,
                data_fim DATETIME,
                max_participantes INTEGER DEFAULT 0,
                qtd_sorteados INTEGER DEFAULT 1,
                campos_config TEXT, -- JSON com configuração de campos
                public_url TEXT UNIQUE,
                status TEXT DEFAULT 'ativo' CHECK (status IN ('ativo', 'pausado', 'finalizado')),
                allow_duplicates INTEGER DEFAULT 0,
                require_cpf INTEGER DEFAULT 0,
                require_whatsapp INTEGER DEFAULT 1,
                require_email INTEGER DEFAULT 0,
                custom_fields TEXT, -- JSON com campos personalizados
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de participantes
        $this->db->query("
            CREATE TABLE IF NOT EXISTS participantes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sorteio_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                whatsapp TEXT,
                cpf TEXT,
                email TEXT,
                campos_extras TEXT, -- JSON com campos personalizados
                ip_address TEXT,
                user_agent TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sorteio_id) REFERENCES sorteios(id) ON DELETE CASCADE
            )
        ");
        
        // Tabela de resultados de sorteios
        $this->db->query("
            CREATE TABLE IF NOT EXISTS sorteio_resultados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sorteio_id INTEGER NOT NULL,
                participante_id INTEGER NOT NULL,
                posicao INTEGER NOT NULL,
                resultado_id TEXT, -- ID único para agrupar resultados de um mesmo sorteio
                premio TEXT,
                data_sorteio DATETIME DEFAULT CURRENT_TIMESTAMP,
                observacoes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sorteio_id) REFERENCES sorteios(id) ON DELETE CASCADE,
                FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
                UNIQUE(sorteio_id, participante_id, posicao)
            )
        ");
        
        // Tabela de templates de relatórios
        $this->db->query("
            CREATE TABLE IF NOT EXISTS relatorio_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                tipo TEXT NOT NULL CHECK (tipo IN ('participacao', 'resultados', 'analytics', 'custom')),
                configuracao TEXT, -- JSON com config do template
                is_default INTEGER DEFAULT 0,
                created_by TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de logs de atividades
        $this->db->query("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT,
                action TEXT NOT NULL,
                entity_type TEXT,
                entity_id INTEGER,
                details TEXT, -- JSON com detalhes da ação
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de configurações de email (para relatórios automáticos)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS email_configs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                smtp_host TEXT,
                smtp_port INTEGER DEFAULT 587,
                smtp_username TEXT,
                smtp_password TEXT,
                from_email TEXT,
                from_name TEXT,
                is_enabled INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    /**
     * Cria índices para otimização de performance
     */
    private function createIndexes() {
        $indexes = [
            // Índices para tabela sorteios
            "CREATE INDEX IF NOT EXISTS idx_sorteios_status ON sorteios(status)",
            "CREATE INDEX IF NOT EXISTS idx_sorteios_public_url ON sorteios(public_url)",
            "CREATE INDEX IF NOT EXISTS idx_sorteios_data_inicio ON sorteios(data_inicio)",
            "CREATE INDEX IF NOT EXISTS idx_sorteios_data_fim ON sorteios(data_fim)",
            "CREATE INDEX IF NOT EXISTS idx_sorteios_created_at ON sorteios(created_at)",
            
            // Índices para tabela participantes
            "CREATE INDEX IF NOT EXISTS idx_participantes_sorteio ON participantes(sorteio_id)",
            "CREATE INDEX IF NOT EXISTS idx_participantes_cpf ON participantes(cpf)",
            "CREATE INDEX IF NOT EXISTS idx_participantes_email ON participantes(email)",
            "CREATE INDEX IF NOT EXISTS idx_participantes_whatsapp ON participantes(whatsapp)",
            "CREATE INDEX IF NOT EXISTS idx_participantes_active ON participantes(is_active)",
            "CREATE INDEX IF NOT EXISTS idx_participantes_created_at ON participantes(created_at)",
            
            // Índices para tabela sorteio_resultados
            "CREATE INDEX IF NOT EXISTS idx_sorteio_resultados_sorteio ON sorteio_resultados(sorteio_id)",
            "CREATE INDEX IF NOT EXISTS idx_sorteio_resultados_participante ON sorteio_resultados(participante_id)",
            "CREATE INDEX IF NOT EXISTS idx_sorteio_resultados_posicao ON sorteio_resultados(posicao)",
            "CREATE INDEX IF NOT EXISTS idx_sorteio_resultados_data ON sorteio_resultados(data_sorteio)",
            "CREATE INDEX IF NOT EXISTS idx_sorteio_resultados_resultado_id ON sorteio_resultados(resultado_id)",
            
            // Índices para tabela relatorio_templates
            "CREATE INDEX IF NOT EXISTS idx_relatorio_templates_tipo ON relatorio_templates(tipo)",
            "CREATE INDEX IF NOT EXISTS idx_relatorio_templates_default ON relatorio_templates(is_default)",
            
            // Índices para tabela activity_logs
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_entity ON activity_logs(entity_type, entity_id)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at)",
            
            // Índices compostos para queries frequentes
            "CREATE INDEX IF NOT EXISTS idx_participantes_sorteio_active ON participantes(sorteio_id, is_active)",
            "CREATE INDEX IF NOT EXISTS idx_sorteios_status_created ON sorteios(status, created_at)",
        ];
        
        foreach ($indexes as $indexSql) {
            try {
                $this->db->query($indexSql);
            } catch (Exception $e) {
                error_log("Erro ao criar índice: " . $e->getMessage());
                // Continua execução mesmo se um índice falhar
            }
        }
    }    
    /*
*
     * Cria triggers para atualização automática de timestamps
     */
    private function createTriggers() {
        $triggers = [
            // Trigger para atualizar updated_at em configuracoes
            "CREATE TRIGGER IF NOT EXISTS update_configuracoes_timestamp 
             AFTER UPDATE ON configuracoes
             BEGIN
                UPDATE configuracoes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
             END",
            
            // Trigger para atualizar updated_at em sorteios
            "CREATE TRIGGER IF NOT EXISTS update_sorteios_timestamp 
             AFTER UPDATE ON sorteios
             BEGIN
                UPDATE sorteios SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
             END",
            
            // Trigger para atualizar updated_at em participantes
            "CREATE TRIGGER IF NOT EXISTS update_participantes_timestamp 
             AFTER UPDATE ON participantes
             BEGIN
                UPDATE participantes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
             END",
            
            // Trigger para atualizar updated_at em relatorio_templates
            "CREATE TRIGGER IF NOT EXISTS update_relatorio_templates_timestamp 
             AFTER UPDATE ON relatorio_templates
             BEGIN
                UPDATE relatorio_templates SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
             END",
            
            // Trigger para atualizar updated_at em email_configs
            "CREATE TRIGGER IF NOT EXISTS update_email_configs_timestamp 
             AFTER UPDATE ON email_configs
             BEGIN
                UPDATE email_configs SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
             END"
        ];
        
        foreach ($triggers as $triggerSql) {
            try {
                $this->db->query($triggerSql);
            } catch (Exception $e) {
                error_log("Erro ao criar trigger: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Insere dados iniciais padrão
     */
    public function insertDefaultData() {
        try {
            // Verifica se já existem configurações
            $configExists = $this->db->fetchOne("SELECT COUNT(*) as count FROM configuracoes");
            
            if ($configExists['count'] == 0) {
                // Insere configuração padrão
                $this->db->query("
                    INSERT INTO configuracoes (
                        nome_empresa, 
                        regras_padrao, 
                        admin_email,
                        timezone,
                        theme
                    ) VALUES (?, ?, ?, ?, ?)
                ", [
                    'Sistema de Sorteios',
                    'Regulamento padrão do sorteio. Edite conforme necessário.',
                    'admin@sistema.com',
                    'America/Sao_Paulo',
                    'light'
                ]);
            }
            
            // Insere templates de relatório padrão
            $this->insertDefaultReportTemplates();
            
        } catch (Exception $e) {
            error_log("Erro ao inserir dados padrão: " . $e->getMessage());
        }
    }
    
    /**
     * Insere templates de relatório padrão
     */
    private function insertDefaultReportTemplates() {
        $templates = [
            [
                'nome' => 'Relatório de Participação Básico',
                'tipo' => 'participacao',
                'configuracao' => json_encode([
                    'fields' => ['nome', 'whatsapp', 'cpf', 'created_at'],
                    'format' => 'table',
                    'include_summary' => true
                ]),
                'is_default' => 1
            ],
            [
                'nome' => 'Relatório de Resultados',
                'tipo' => 'resultados',
                'configuracao' => json_encode([
                    'fields' => ['posicao', 'nome', 'whatsapp', 'data_sorteio'],
                    'format' => 'table',
                    'include_details' => true
                ]),
                'is_default' => 1
            ],
            [
                'nome' => 'Analytics Dashboard',
                'tipo' => 'analytics',
                'configuracao' => json_encode([
                    'metrics' => ['total_sorteios', 'total_participantes', 'conversion_rate'],
                    'charts' => ['participation_timeline', 'top_sorteios'],
                    'period' => 'last_30_days'
                ]),
                'is_default' => 1
            ]
        ];
        
        foreach ($templates as $template) {
            // Verifica se template já existe
            $exists = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM relatorio_templates WHERE nome = ? AND tipo = ?",
                [$template['nome'], $template['tipo']]
            );
            
            if ($exists['count'] == 0) {
                $this->db->query("
                    INSERT INTO relatorio_templates (nome, tipo, configuracao, is_default, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $template['nome'],
                    $template['tipo'],
                    $template['configuracao'],
                    $template['is_default'],
                    'system'
                ]);
            }
        }
    }
    
    /**
     * Verifica integridade do banco de dados
     */
    public function checkIntegrity() {
        try {
            $result = $this->db->fetchOne("PRAGMA integrity_check");
            return $result['integrity_check'] === 'ok';
        } catch (Exception $e) {
            error_log("Erro na verificação de integridade: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Otimiza o banco de dados
     */
    public function optimize() {
        try {
            // Executa VACUUM para compactar o banco
            $this->db->query("VACUUM");
            
            // Atualiza estatísticas das tabelas
            $this->db->query("ANALYZE");
            
            // Executa otimização automática
            $this->db->query("PRAGMA optimize");
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro na otimização: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém informações do schema
     */
    public function getSchemaInfo() {
        try {
            $info = [];
            
            // Versão atual
            $info['version'] = $this->getCurrentDatabaseVersion();
            $info['latest_version'] = $this->currentVersion;
            $info['needs_migration'] = $info['version'] < $info['latest_version'];
            
            // Tabelas
            $tables = $this->db->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
            );
            $info['tables'] = array_column($tables, 'name');
            $info['table_count'] = count($info['tables']);
            
            // Índices
            $indexes = $this->db->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%'"
            );
            $info['indexes'] = array_column($indexes, 'name');
            $info['index_count'] = count($info['indexes']);
            
            // Triggers
            $triggers = $this->db->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='trigger'"
            );
            $info['triggers'] = array_column($triggers, 'name');
            $info['trigger_count'] = count($info['triggers']);
            
            return $info;
            
        } catch (Exception $e) {
            error_log("Erro ao obter informações do schema: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Executa setup completo do banco
     */
    public function setupDatabase() {
        try {
            echo "Iniciando setup do banco de dados...\n";
            
            // Inicializa schema
            $this->initialize();
            echo "Schema inicializado.\n";
            
            // Cria triggers
            $this->createTriggers();
            echo "Triggers criados.\n";
            
            // Insere dados padrão
            $this->insertDefaultData();
            echo "Dados padrão inseridos.\n";
            
            // Verifica integridade
            if ($this->checkIntegrity()) {
                echo "Verificação de integridade: OK\n";
            } else {
                throw new Exception("Falha na verificação de integridade");
            }
            
            // Otimiza banco
            $this->optimize();
            echo "Banco otimizado.\n";
            
            echo "Setup concluído com sucesso!\n";
            return true;
            
        } catch (Exception $e) {
            error_log("Erro no setup do banco: " . $e->getMessage());
            throw new Exception("Falha no setup do banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Reset completo do banco (CUIDADO!)
     */
    public function resetDatabase() {
        try {
            $tables = [
                'activity_logs',
                'email_configs',
                'relatorio_templates',
                'sorteio_resultados',
                'participantes',
                'sorteios',
                'configuracoes',
                'schema_version'
            ];
            
            foreach ($tables as $table) {
                $this->db->query("DROP TABLE IF EXISTS $table");
            }
            
            // Recria tudo
            $this->setupDatabase();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro no reset do banco: " . $e->getMessage());
            throw new Exception("Falha no reset do banco de dados: " . $e->getMessage());
        }
    }
}

// Função auxiliar para inicializar o schema
function initializeDatabase() {
    $schema = new DatabaseSchema();
    return $schema->setupDatabase();
}

// Função auxiliar para verificar se o banco precisa de setup
function needsDatabaseSetup() {
    try {
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name='configuracoes'");
        return $result['count'] == 0;
    } catch (Exception $e) {
        return true; // Se der erro, assume que precisa de setup
    }
}

// Função auxiliar para criar tabelas básicas rapidamente
function createDatabaseTables() {
    try {
        $schema = new DatabaseSchema();
        return $schema->setupDatabase();
    } catch (Exception $e) {
        error_log("Erro ao criar tabelas: " . $e->getMessage());
        return false;
    }
}
?>