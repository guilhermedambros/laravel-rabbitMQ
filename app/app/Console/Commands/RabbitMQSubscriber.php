<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQPubSubService;

class RabbitMQSubscriber extends Command
{
    protected $signature = 'rabbitmq:subscribe {name}';
    protected $description = 'Cria um consumidor que recebe TODAS as mensagens publicadas (Pub/Sub)';

    public function handle()
    {
        $consumerName = $this->argument('name');
        
        $this->info("🎧 Iniciando consumidor: {$consumerName}");
        $this->info("💡 Este consumidor receberá TODAS as mensagens publicadas\n");
        
        $pubsub = new RabbitMQPubSubService();
        
        $pubsub->subscribe($consumerName, function ($msg) use ($consumerName) {
            // Simula processamento específico
            $this->processMessage($consumerName, $msg->body);
        });
    }

    private function processMessage(string $consumerName, string $message)
    {
        // Cada consumidor pode processar a mensagem de forma diferente
        switch ($consumerName) {
            case 'EmailService':
                $this->info("📧 [{$consumerName}] Enviando email: {$message}");
                sleep(1);
                $this->line("✅ Email enviado!\n");
                break;
                
            case 'SMSService':
                $this->info("📱 [{$consumerName}] Enviando SMS: {$message}");
                sleep(1);
                $this->line("✅ SMS enviado!\n");
                break;
                
            case 'Analytics':
                $this->info("📊 [{$consumerName}] Registrando analytics: {$message}");
                sleep(1);
                $this->line("✅ Registrado no dashboard!\n");
                break;
                
            default:
                $this->info("⚙️ [{$consumerName}] Processando: {$message}");
                sleep(1);
                $this->line("✅ Processado!\n");
        }
    }
}
