<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Serviço Pub/Sub - Todos os consumidores recebem a mesma mensagem
 */
class RabbitMQPubSubService
{
    private $connection;
    private $channel;
    private $exchangeName = 'eventos';

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest')
        );
        $this->channel = $this->connection->channel();
        
        // Declara exchange do tipo 'fanout' (broadcast para todos)
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, false, false);
    }

    /**
     * Publica uma mensagem para TODOS os consumidores
     */
    public function publish(string $message): void
    {
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, $this->exchangeName);
        echo "📢 Mensagem publicada para todos: {$message}\n";
    }

    /**
     * Cria um consumidor com uma fila exclusiva
     * Cada consumidor recebe TODAS as mensagens
     */
    public function subscribe(string $consumerName, callable $callback): void
    {
        // Cria uma fila temporária exclusiva para este consumidor
        list($queueName, ,) = $this->channel->queue_declare('', false, false, true, false);
        
        // Vincula a fila ao exchange
        $this->channel->queue_bind($queueName, $this->exchangeName);
        
        echo "👂 {$consumerName} inscrito e aguardando mensagens...\n";
        
        $wrappedCallback = function ($msg) use ($callback, $consumerName) {
            echo "📨 [{$consumerName}] Recebeu: {$msg->body}\n";
            $callback($msg);
        };
        
        $this->channel->basic_consume($queueName, '', false, true, false, false, $wrappedCallback);
        
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
