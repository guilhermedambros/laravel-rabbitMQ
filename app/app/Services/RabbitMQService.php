<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class RabbitMQService
{
    private $connection;
    private $channel;
    private $queue;

    public function __construct()
    {
        $this->queue = env('RABBITMQ_QUEUE', 'fila_teste');
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest')
        );
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, true, false, false);
    }

    public function sendMessage(string $message): void
    {
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', $this->queue);
        echo "Mensagem enviada: {$message}\n";
    }

    public function consumeMessages(callable $callback): void
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);
        // Some versions of php-amqplib do not provide is_consuming().
        // Use the channel callbacks list to determine whether to continue waiting.
        while (count($this->channel->callbacks)) {
            try {
                $this->channel->wait(null, false, 3); // Wait max 3 seconds per iteration
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Timeout is normal, just continue waiting
                continue;
            } catch (\Exception $e) {
                echo "⚠️  Erro ao aguardar mensagens: {$e->getMessage()}\n";
                break;
            }
        }
    }

    /**
     * Get a single message from the queue (non-blocking).
     * Returns null if no message is available.
     */
    public function getMessage(): ?AMQPMessage
    {
        return $this->channel->basic_get($this->queue, true);
    }

    /**
     * Get messages with a timeout (for HTTP endpoints).
     * Returns an array of messages received within the timeout period.
     */
    public function getMessagesWithTimeout(int $timeoutSeconds = 5): array
    {
        $messages = [];
        $startTime = time();
        
        $callback = function ($msg) use (&$messages) {
            $messages[] = $msg->body;
        };
        
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);
        
        while (count($this->channel->callbacks) && (time() - $startTime) < $timeoutSeconds) {
            try {
                $this->channel->wait(null, false, 1); // Wait max 1 second per iteration
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                break; // Timeout reached, exit gracefully
            }
        }
        
        return $messages;
    }

    public function __destruct()
    {
        try {
            if ($this->channel && $this->channel->is_open()) {
                $this->channel->close();
            }
            if ($this->connection && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            // Silently ignore errors during cleanup
        }
    }
}
