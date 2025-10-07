<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQJsonService;

class RabbitMQJsonWorker extends Command
{
    protected $signature = 'rabbitmq:json-worker {tipo?}';
    protected $description = 'Worker que processa mensagens JSON estruturadas';

    public function handle()
    {
        $tipo = $this->argument('tipo') ?? 'geral';
        $workerName = "JsonWorker-{$tipo}-" . uniqid();
        
        $this->info("🚀 Iniciando {$workerName}...\n");
        
        $rabbit = new RabbitMQJsonService();
        
        $rabbit->consumeJson(function ($data, $msg) use ($workerName) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📨 [{$workerName}] Nova mensagem recebida!");
            
            // Mostra os dados recebidos
            $this->line("\n🔍 Dados:");
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Processa baseado no tipo
            if (isset($data['tipo'])) {
                $this->processTask($data, $workerName);
            } elseif (isset($data['evento'])) {
                $this->processEvent($data, $workerName);
            } else {
                $this->processGeneric($data, $workerName);
            }
            
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        });
    }

    private function processTask(array $data, string $workerName)
    {
        $tipo = $data['tipo'];
        $dados = $data['dados'] ?? [];
        
        $this->line("\n⚙️  Processando tarefa: {$tipo}");
        
        switch ($tipo) {
            case 'enviar_email':
                $this->info("📧 Enviando email para: {$dados['destinatario']}");
                $this->line("   Assunto: {$dados['assunto']}");
                sleep(1);
                $this->line("✅ Email enviado!");
                break;
                
            case 'processar_pedido':
                $this->info("🛒 Processando pedido #{$dados['pedido_id']}");
                $this->line("   Cliente: {$dados['cliente']['nome']}");
                $this->line("   Total: R$ {$dados['total']}");
                sleep(2);
                $this->line("✅ Pedido processado!");
                break;
                
            case 'gerar_relatorio':
                $this->info("📊 Gerando relatório: {$dados['tipo']}");
                $this->line("   Período: {$dados['periodo']}");
                sleep(2);
                $this->line("✅ Relatório gerado!");
                break;
                
            case 'processar_imagem':
                $this->info("🖼️  Processando imagem: {$dados['arquivo']}");
                $this->line("   Operação: {$dados['operacao']}");
                sleep(1);
                $this->line("✅ Imagem processada!");
                break;
                
            default:
                $this->warn("⚠️  Tipo de tarefa desconhecido: {$tipo}");
        }
    }

    private function processEvent(array $data, string $workerName)
    {
        $evento = $data['evento'];
        $payload = $data['payload'] ?? [];
        
        $this->line("\n🎉 Processando evento: {$evento}");
        
        switch ($evento) {
            case 'pedido.criado':
                $this->info("📦 Novo pedido criado!");
                $this->line("   Pedido: #{$payload['pedido_id']}");
                $this->line("   Cliente: {$payload['cliente']}");
                break;
                
            case 'usuario.cadastrado':
                $this->info("👤 Novo usuário cadastrado!");
                $this->line("   Nome: {$payload['nome']}");
                $this->line("   Email: {$payload['email']}");
                break;
                
            case 'pagamento.aprovado':
                $this->info("💳 Pagamento aprovado!");
                $this->line("   Valor: R$ {$payload['valor']}");
                break;
                
            default:
                $this->info("📢 Evento: {$evento}");
        }
        
        sleep(1);
        $this->line("✅ Evento processado!");
    }

    private function processGeneric(array $data, string $workerName)
    {
        $this->line("\n📝 Processando dados genéricos...");
        sleep(1);
        $this->line("✅ Processado!");
    }
}
