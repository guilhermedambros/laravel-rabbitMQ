<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;

class RabbitMQWorker extends Command
{
    protected $signature = 'rabbitmq:worker {name?}';
    protected $description = 'Inicia um worker para consumir mensagens do RabbitMQ';

    public function handle()
    {
        $workerName = $this->argument('name') ?? 'Worker-' . uniqid();
        
        $this->info("🚀 Iniciando {$workerName}...");
        
        $rabbit = new RabbitMQService();
        
        $this->info("👂 {$workerName} aguardando mensagens...\n");
        
        $rabbit->consumeMessages(function ($msg) use ($workerName) {
            $this->line("📨 [{$workerName}] Recebeu: {$msg->body}");
            
            // Simula processamento
            sleep(2);
            
            $this->info("✅ [{$workerName}] Processou: {$msg->body}\n");
        });
    }
}
