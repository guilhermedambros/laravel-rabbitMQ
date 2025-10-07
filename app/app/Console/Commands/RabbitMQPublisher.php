<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQPubSubService;

class RabbitMQPublisher extends Command
{
    protected $signature = 'rabbitmq:publish {message}';
    protected $description = 'Publica uma mensagem para todos os consumidores (Pub/Sub)';

    public function handle()
    {
        $message = $this->argument('message');
        
        $this->info("📢 Publicando mensagem para TODOS os consumidores...");
        
        $pubsub = new RabbitMQPubSubService();
        $pubsub->publish($message);
        
        $this->info("✅ Mensagem enviada!");
    }
}
