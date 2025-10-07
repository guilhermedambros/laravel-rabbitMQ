<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Serviço RabbitMQ com suporte a mensagens estruturadas (JSON)
 */
class RabbitMQJsonService
{
    private $connection;
    private $channel;
    private $queue;

    public function __construct(string $queue = null)
    {
        $this->queue = $queue ?? env('RABBITMQ_QUEUE', 'fila_teste');
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest')
        );
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, true, false, false);
    }

    /**
     * Envia um objeto/array como JSON
     * 
     * @param array $data Array ou objeto para enviar
     * @param array $properties Propriedades adicionais da mensagem
     */
    public function sendJson(array $data, array $properties = []): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        $defaultProperties = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'timestamp' => time(),
        ];
        
        $properties = array_merge($defaultProperties, $properties);
        
        $msg = new AMQPMessage($json, $properties);
        $this->channel->basic_publish($msg, '', $this->queue);
        
        echo "📨 JSON enviado para fila '{$this->queue}':\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Envia uma mensagem de tarefa (job)
     * 
     * @param string $tipo Tipo da tarefa (ex: 'enviar_email', 'processar_pedido')
     * @param array $dados Dados da tarefa
     * @param int $prioridade Prioridade (0-9, onde 9 é mais alta)
     */
    public function sendTask(string $tipo, array $dados = [], int $prioridade = 5): void
    {
        $task = [
            'tipo' => $tipo,
            'dados' => $dados,
            'criado_em' => now()->toIso8601String(),
            'id' => uniqid('task_', true)
        ];
        
        $this->sendJson($task, [
            'priority' => $prioridade,
            'app_id' => env('APP_NAME', 'laravel-app')
        ]);
    }

    /**
     * Envia um evento (para Pub/Sub)
     * 
     * @param string $evento Nome do evento (ex: 'pedido.criado')
     * @param array $payload Dados do evento
     */
    public function sendEvent(string $evento, array $payload = []): void
    {
        $event = [
            'evento' => $evento,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
            'id' => uniqid('event_', true)
        ];
        
        $this->sendJson($event, [
            'type' => 'event',
            'app_id' => env('APP_NAME', 'laravel-app')
        ]);
    }

    /**
     * Recebe e decodifica mensagem JSON
     */
    public function getJson(): ?array
    {
        $msg = $this->channel->basic_get($this->queue, true);
        
        if (!$msg) {
            return null;
        }
        
        $data = json_decode($msg->body, true);
        
        // Adiciona metadata da mensagem
        $data['_metadata'] = [
            'delivery_tag' => $msg->getDeliveryTag(),
            'redelivered' => $msg->isRedelivered(),
            'content_type' => $msg->get('content_type'),
            'timestamp' => $msg->get('timestamp'),
            'priority' => $msg->get('priority'),
        ];
        
        return $data;
    }

    /**
     * Consome mensagens JSON com callback
     */
    public function consumeJson(callable $callback): void
    {
        $wrappedCallback = function ($msg) use ($callback) {
            $data = json_decode($msg->body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "⚠️  Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
                return;
            }
            
            $callback($data, $msg);
        };
        
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $wrappedCallback);
        
        while (count($this->channel->callbacks)) {
            try {
                $this->channel->wait(null, false, 3);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                continue;
            } catch (\Exception $e) {
                echo "⚠️  Erro: {$e->getMessage()}\n";
                break;
            }
        }
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
            // Silently ignore
        }
    }
}
